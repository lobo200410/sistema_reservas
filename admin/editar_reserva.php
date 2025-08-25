<?php
session_start();
include("../conexion.php");

// Solo admin
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
  exit('<div class="p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">Acceso denegado.</div>');
}

// --- Helpers ---
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --- Obtener ID ---
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  exit('<div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-700">ID de reserva inválido.</div>');
}

// --- Cargar catálogos básicos (salas, escenarios) ---
$salas = [];
$escenarios = [];

// Salas
$rs = $conn->query("SELECT id, nombre FROM salas ORDER BY nombre");
if ($rs) {
  while ($row = $rs->fetch_assoc()) $salas[] = $row;
  $rs->close();
}

// Escenarios
$rs = $conn->query("SELECT id, nombre FROM escenarios ORDER BY nombre");
if ($rs) {
  while ($row = $rs->fetch_assoc()) $escenarios[] = $row;
  $rs->close();
}

// --- Cargar reserva ---
$sql = "
SELECT
  r.id,
  r.docente_nombre,
  r.docente_correo,
  r.nombre_usuario,
  r.asignatura,
  r.seccion,
  r.tema_video,
  r.tipo_video_id,
  r.escenario_id,
  r.escuela_id,
  r.facultad_id,
  r.fecha_reserva,
  r.hora_reserva,
  r.estado,
  r.sala_id,
  r.recursos
FROM reservations r
WHERE r.id = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reserva) {
  exit('<div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-700">Reserva no encontrada.</div>');
}

// Fecha inicial para volver a la lista al guardar
$fecha_para_volver = $reserva['fecha_reserva'];

// Parámetros opcionales para volver al dashboard con filtro
$back  = $_GET['back']  ?? '';
$vista = $_GET['vista'] ?? 'reservas';
$qsBack = "vista=" . urlencode($vista) . "&fecha=" . urlencode($fecha_para_volver);

// --- Procesar envío ---
$mensaje_ok = "";
$mensaje_err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Campos editables del formulario
  $tema_video    = $_POST['tema_video']    ?? $reserva['tema_video'];
  $tipo_video_id = $_POST['tipo_video_id'] ?? $reserva['tipo_video_id'];
  $escenario_id  = (int)($_POST['escenario_id'] ?? $reserva['escenario_id']);
  $sala_id       = (int)($_POST['sala_id'] ?? $reserva['sala_id']);
  $fecha_reserva = $_POST['fecha_reserva'] ?? $reserva['fecha_reserva'];   // YYYY-MM-DD
  $hora_reserva  = $_POST['hora_reserva']  ?? $reserva['hora_reserva'];    // HH:MM[:SS]
  $estado        = $_POST['estado']        ?? $reserva['estado'];
  $recursos      = $_POST['recursos']      ?? $reserva['recursos'];

  // Normaliza hora (HH:MM)
  if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora_reserva)) {
    $hora_reserva = substr($hora_reserva, 0, 5);
  } else {
    $mensaje_err = "Hora inválida.";
  }

  // Validación de colisión: misma sala + misma fecha + misma hora (excluye esta reserva)
  if (!$mensaje_err) {
    $ver = $conn->prepare("SELECT id FROM reservations WHERE fecha_reserva = ? AND hora_reserva = ? AND sala_id = ? AND id <> ? LIMIT 1");
    $ver->bind_param("ssii", $fecha_reserva, $hora_reserva, $sala_id, $id);
    $ver->execute();
    $ver->store_result();
    if ($ver->num_rows > 0) {
      $mensaje_err = "Ya existe otra reserva en esa sala a esa fecha y hora.";
    }
    $ver->close();
  }

  if (!$mensaje_err) {
    // Update principal
    $upd = $conn->prepare("
      UPDATE reservations
      SET tema_video = ?,
          tipo_video_id = ?,
          escenario_id = ?,
          sala_id = ?,
          fecha_reserva = ?,
          hora_reserva = ?,
          estado = ?,
          recursos = ?
      WHERE id = ?
      LIMIT 1
    ");
    $upd->bind_param(
      "ssisssssi",
      $tema_video,
      $tipo_video_id,
      $escenario_id,
      $sala_id,
      $fecha_reserva,
      $hora_reserva,
      $estado,
      $recursos,
      $id
    );

    if ($upd->execute()) {
      // Éxito: redirigir
      $mensaje_ok = "Reserva actualizada correctamente.";
      $fecha_para_volver = $fecha_reserva; // por si cambió
      // Recalcular QS de retorno con la nueva fecha
      $qsBack = "vista=" . urlencode($vista) . "&fecha=" . urlencode($fecha_para_volver);

      if ($back === 'dashboard') {
        header("Location: dashboard.php?" . $qsBack);
      } else {
        header("Location: ver_reservas.php?fecha=" . urlencode($fecha_para_volver));
      }
      $upd->close();
      exit;
    } else {
      $mensaje_err = "Error al actualizar: " . h($upd->error);
      $upd->close();
    }
  }

  // Si hubo error, refresca $reserva para repintar formulario con valores posteados
  if ($mensaje_err) {
    $reserva['tema_video']    = $tema_video;
    $reserva['tipo_video_id'] = $tipo_video_id;
    $reserva['escenario_id']  = $escenario_id;
    $reserva['sala_id']       = $sala_id;
    $reserva['fecha_reserva'] = $fecha_reserva;
    $reserva['hora_reserva']  = $hora_reserva;
    $reserva['estado']        = $estado;
    $reserva['recursos']      = $recursos;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar reserva</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tailwind para probar; en producción usa tu build compilado -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-slate-800">
  <div class="max-w-4xl mx-auto p-4 md:p-6">
    <div class="mb-5">
      <a href="<?php
        echo ($back === 'dashboard')
          ? 'dashboard.php?' . $qsBack
          : 'ver_reservas.php?fecha=' . urlencode($fecha_para_volver);
      ?>" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-800">
        ← Volver a reservas (<?php echo h($fecha_para_volver); ?>)
      </a>
    </div>

    <div class="rounded-2xl bg-white border border-gray-200 shadow p-5">
      <div class="mb-4">
        <h1 class="text-lg font-semibold">Editar reserva #<?php echo h($reserva['id']); ?></h1>
        <p class="text-xs text-gray-500">
          Docente: <span class="font-medium text-gray-700"><?php echo h($reserva['docente_nombre']); ?></span> ·
          Correo: <span class="font-mono"><?php echo h($reserva['docente_correo']); ?></span>
        </p>
      </div>

      <?php if ($mensaje_ok): ?>
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-700">
          <?php echo h($mensaje_ok); ?>
        </div>
      <?php endif; ?>

      <?php if ($mensaje_err): ?>
        <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-700">
          <?php echo h($mensaje_err); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Tema -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Tema del video</label>
          <input type="text" name="tema_video" value="<?php echo h($reserva['tema_video']); ?>"
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        <!-- Tipo de video -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Tipo de video</label>
          <input type="text" name="tipo_video_id" value="<?php echo h($reserva['tipo_video_id']); ?>"
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        <!-- Escenario -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Escenario</label>
          <select name="escenario_id"
                  class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
            <?php foreach ($escenarios as $e): ?>
              <option value="<?php echo (int)$e['id']; ?>"
                <?php echo ($reserva['escenario_id'] == $e['id']) ? 'selected' : ''; ?>>
                <?php echo h($e['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Sala -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Sala</label>
          <select name="sala_id"
                  class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
            <?php foreach ($salas as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>"
                <?php echo ($reserva['sala_id'] == $s['id']) ? 'selected' : ''; ?>>
                <?php echo h($s['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Fecha -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Fecha de reserva</label>
          <input type="date" name="fecha_reserva" value="<?php echo h($reserva['fecha_reserva']); ?>"
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        <!-- Hora -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Hora de reserva</label>
          <input type="time" name="hora_reserva" value="<?php echo h(substr($reserva['hora_reserva'], 0, 5)); ?>"
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        <!-- Estado -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Estado</label>
          <select name="estado"
                  class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
            <?php
              $estados = ['pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'completada' => 'Completada'];
              foreach ($estados as $val => $label):
            ?>
              <option value="<?php echo h($val); ?>"
                <?php echo ($reserva['estado'] === $val) ? 'selected' : ''; ?>>
                <?php echo h($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Recursos -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Recursos (separados por coma)</label>
          <input type="text" name="recursos" value="<?php echo h($reserva['recursos']); ?>"
                 placeholder="Proyector, Micrófono, Iluminación"
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2">
          <a href="<?php
            echo ($back === 'dashboard')
              ? 'dashboard.php?' . $qsBack
              : 'ver_reservas.php?fecha=' . urlencode($fecha_para_volver);
          ?>" class="inline-flex items-center h-10 px-4 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm">
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center h-10 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
            Guardar cambios
          </button>
        </div>

      </form>
    </div>
  </div>
</body>
</html>
