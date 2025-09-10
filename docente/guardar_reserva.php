<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "docente") {
  header("Location: ../login.php"); exit;
}

$usuario = $_SESSION["nombre_usuario"] ?? '';
// Validación mínima de campos esperados
function need($k,$arr){ if(!isset($arr[$k]) || $arr[$k]===''){ echo "<script>alert('Falta $k'); history.back();</script>"; exit; } }

need('docente', $_POST);
need('correo_docente', $_POST);
need('asignatura', $_POST);
need('seccion', $_POST);
need('tema_video', $_POST);
need('tipo_video', $_POST);
need('escenario', $_POST);
need('escuela', $_POST);
need('facultad', $_POST);
need('fecha_reserva', $_POST);
need('bloque_sala', $_POST);

$docente      = trim($_POST['docente']);
$correo       = trim($_POST['correo_docente']);
$asignatura   = trim($_POST['asignatura']);
$seccion      = trim($_POST['seccion']);
$tema         = trim($_POST['tema_video']);
$tipo_video   = trim($_POST['tipo_video']);   // puede ser id o texto, según tu modelo
$escenario_id = (int)$_POST['escenario'];
$escuela      = (int)$_POST['escuela'];
$facultad     = (int)$_POST['facultad'];
$fecha        = $_POST['fecha_reserva'];


if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
  echo "<script>alert('Fecha inválida'); history.back();</script>"; exit;
}


if (!preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}\|\d+$/', $_POST['bloque_sala'])) {
  echo "<script>alert('Bloque de hora inválido'); history.back();</script>"; exit;
}
list($hora, $sala_id) = explode('|', $_POST['bloque_sala']);
$sala_id = (int)$sala_id;

// Recursos
$recursos       = isset($_POST['recursos']) && is_array($_POST['recursos']) ? $_POST['recursos'] : [];
$recursos_texto = implode(", ", array_map('trim', $recursos));

/* 4.1 Pre-check GLOBAL por bloque (sin sala) */
$verificar = $conn->prepare("SELECT id FROM reservations WHERE fecha_reserva=? AND hora_reserva=?");
$verificar->bind_param("ss", $fecha, $hora);
$verificar->execute(); $verificar->store_result();
if ($verificar->num_rows > 0) {
  echo "<script>alert('Ya existe una reserva en ese bloque horario. Seleccione otro.'); history.back();</script>";
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
  $docente, $correo, $usuario, $asignatura, $seccion, $tema,
  $tipo_video, $escenario_id, $escuela, $facultad, $fecha, $hora, $sala_id, $recursos_texto
);

if ($insert->execute()) {

  $nombre_tipo = (string)$tipo_video;
  $nombre_escenario = (string)$escenario_id;
  $nombre_sala = (string)$sala_id;
  $nombre_escuela = (string)$escuela;
  $nombre_facultad = (string)$facultad;

  if ($stmt = $conn->prepare("SELECT e.nombre, s.nombre FROM escenarios e JOIN salas s ON s.id=e.sala_id WHERE e.id=?")) {
    $stmt->bind_param("i",$escenario_id); $stmt->execute(); $stmt->bind_result($esc,$sal);
    if ($stmt->fetch()) { $nombre_escenario = $esc ?: $nombre_escenario; $nombre_sala = $sal ?: $nombre_sala; }
    $stmt->close();
  }
  if ($stmt = $conn->prepare("SELECT nombre FROM escuelas WHERE id=?")) {
    $stmt->bind_param("i",$escuela); $stmt->execute(); $stmt->bind_result($tmp);
    if ($stmt->fetch()) $nombre_escuela = $tmp ?: $nombre_escuela; $stmt->close();
  }
  if ($stmt = $conn->prepare("SELECT nombre FROM facultades WHERE id=?")) {
    $stmt->bind_param("i",$facultad); $stmt->execute(); $stmt->bind_result($tmp);
    if ($stmt->fetch()) $nombre_facultad = $tmp ?: $nombre_facultad; $stmt->close();
  }


  require_once __DIR__ . '/../utilidades/mail_helper.php';
  $destinatarios = ['rene.cruz@utec.edu.sv']; // agrega más si quieres
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
    echo "<script>alert('Reserva guardada, pero la notificación por correo falló: ".htmlspecialchars($mailRes['msg'],ENT_QUOTES)."'); window.location.href='dashboard.php';</script>"; exit;
  }

  echo "<script>alert('Reserva guardada y notificada por correo.'); window.location.href='dashboard.php';</script>"; exit;

} else {
  // Choque por índice único u otro error
  if ($conn->errno == 1062) {
    echo "<script>alert('Ese bloque ya fue tomado por otra solicitud. Intenta otro horario.'); history.back();</script>";
  } else {
    echo "Error al guardar la reserva: " . htmlspecialchars($insert->error);
  }
  exit;
}

$insert->close();
$conn->close();
