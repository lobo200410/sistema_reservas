<?php
session_start();
include("../conexion.php");

if ($_SESSION["rol"] !== "docente") {
    header("Location: ../login.php");
    exit;
}

$usuario = $_SESSION["nombre_usuario"];

$docente      = $_POST['docente'];
$correo       = $_POST['correo_docente'];
$asignatura   = $_POST['asignatura'];
$seccion      = $_POST['seccion'];
$tema         = $_POST['tema_video'];
$tipo_video   = $_POST['tipo_video'];   
$escenario_id = $_POST['escenario'];    // ID
$escuela      = $_POST['escuela'];      // ID
$facultad     = $_POST['facultad'];     // ID
$fecha        = $_POST['fecha_reserva'];

$bloque_sala = explode('|', $_POST['bloque_sala']); // "09:00-09:50|1"
$hora        = $bloque_sala[0];
$sala_id     = (int)$bloque_sala[1];

$recursos       = isset($_POST['recursos']) ? $_POST['recursos'] : [];
$recursos_texto = implode(", ", $recursos);

// Validación duplicados
$verificar = $conn->prepare("SELECT id FROM reservations WHERE fecha_reserva = ? AND hora_reserva = ? AND sala_id = ?");
$verificar->bind_param("ssi", $fecha, $hora, $sala_id);
$verificar->execute();
$verificar->store_result();

if ($verificar->num_rows > 0) {
    echo "<script>alert('Ya existe una reserva para esta fecha, hora y sala. Por favor seleccione otro horario.'); window.history.back();</script>";
    exit;
}
$verificar->close();

$insert = $conn->prepare("
    INSERT INTO reservations 
    (docente_nombre, docente_correo, nombre_usuario, asignatura, seccion, tema_video, tipo_video_id, escenario_id, escuela_id, facultad_id, fecha_reserva, hora_reserva, estado, sala_id, recursos) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?)
");

$insert->bind_param(
    "ssssssssssssis",
    $docente,
    $correo,
    $usuario,
    $asignatura,
    $seccion,
    $tema,
    $tipo_video,     
    $escenario_id,
    $escuela,
    $facultad,
    $fecha,
    $hora,
    $sala_id,
    $recursos_texto
);

if ($insert->execute()) {

 
    $nombre_tipo      = (string)$tipo_video;     
    $nombre_escenario = (string)$escenario_id;   
    $nombre_sala      = (string)$sala_id;        
    $nombre_escuela   = (string)$escuela;        
    $nombre_facultad  = (string)$facultad;      
    // Escenario + Sala
    if ($stmt = $conn->prepare("
        SELECT e.nombre AS escenario, s.nombre AS sala
        FROM escenarios e
        JOIN salas s ON s.id = e.sala_id
        WHERE e.id = ?
    ")) {
        $eId = (int)$escenario_id;
        $stmt->bind_param("i", $eId);
        $stmt->execute();
        $stmt->bind_result($esc, $sal);
        if ($stmt->fetch()) {
            if ($esc !== null) $nombre_escenario = $esc;
            if ($sal !== null) $nombre_sala = $sal;
        }
        $stmt->close();
    }

    // Escuela
    if ($stmt = $conn->prepare("SELECT nombre FROM escuelas WHERE id = ?")) {
        $esId = (int)$escuela;
        $stmt->bind_param("i", $esId);
        $stmt->execute();
        $stmt->bind_result($tmp);
        if ($stmt->fetch() && $tmp !== null) $nombre_escuela = $tmp;
        $stmt->close();
    }

    // Facultad
    if ($stmt = $conn->prepare("SELECT nombre FROM facultades WHERE id = ?")) {
        $fId = (int)$facultad;
        $stmt->bind_param("i", $fId);
        $stmt->execute();
        $stmt->bind_result($tmp);
        if ($stmt->fetch() && $tmp !== null) $nombre_facultad = $tmp;
        $stmt->close();
    }
  

    require_once __DIR__ . '/../utilidades/mail_helper.php';

    $destinatarios = ['rene.cruz@utec.edu.sv'];
    $asunto = "Nueva reserva creada por $docente – $fecha $hora";

    
    $html = '
    <div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#111">
      <h2 style="margin:0 0 8px">Nueva reserva registrada</h2>
      <p>Se ha creado una nueva reserva con el siguiente detalle:</p>
      <table style="border-collapse:collapse;width:100%;max-width:600px">
        <tbody>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Docente</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($docente).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Correo</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($correo).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Asignatura / Sección</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($asignatura).' - '.htmlspecialchars($seccion).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Tema</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($tema).'</td></tr>

          <tr><td style="padding:6px;border:1px solid #eee"><b>Tipo de video</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($nombre_tipo).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Escenario</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($nombre_escenario).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Escuela</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($nombre_escuela).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Facultad</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($nombre_facultad).'</td></tr>

          <tr><td style="padding:6px;border:1px solid #eee"><b>Fecha</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($fecha).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Hora</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($hora).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Sala</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($nombre_sala).'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Recursos</b></td><td style="padding:6px;border:1px solid #eee">'.htmlspecialchars($recursos_texto ?: 'N/D').'</td></tr>
          <tr><td style="padding:6px;border:1px solid #eee"><b>Estado</b></td><td style="padding:6px;border:1px solid #eee">pendiente</td></tr>
        </tbody>
      </table>
      <p style="margin-top:12px">Este es un mensaje automático del sistema de reservas.</p>
    </div>';

    $alt = "Nueva reserva: $docente, $asignatura-$seccion, Tema: $tema, Tipo: $nombre_tipo, Escenario: $nombre_escenario, Escuela: $nombre_escuela, Facultad: $nombre_facultad, Fecha: $fecha, Hora: $hora, Sala: $nombre_sala, Estado: pendiente.";

    $mailRes = enviar_correo($destinatarios, $asunto, $html, $alt);

    if (!$mailRes['ok']) {
        error_log('[MAIL] Error al notificar: ' . $mailRes['msg']);
        echo "<script>alert('Reserva guardada, pero la notificación por correo falló: " . htmlspecialchars($mailRes['msg'], ENT_QUOTES) . "'); window.location.href='dashboard.php';</script>";
        exit;
    }

    echo "<script>alert('Reserva guardada y notificada por correo.'); window.location.href='dashboard.php';</script>";
    exit;
} else {
    echo "Error al guardar la reserva: " . $insert->error;
}

$insert->close();
$conn->close();
