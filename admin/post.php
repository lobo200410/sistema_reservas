<?php
session_start();
include("../conexion.php");

// Sólo admins
if ($_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fecha del filtro (GET) o hoy por defecto
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Consulta sólo reservas aprobadas de esa fecha
$sql = "
  SELECT id, asignatura, tema_video, hora_reserva
  FROM reservations
  WHERE estado = 'aprobada'
    AND DATE(fecha_reserva) = ?
  ORDER BY hora_reserva
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fecha);
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="py-4">
  <h3 class="text-lg font-semibold text-gray-800 mb-4">
    Reservas Aprobadas para Post-Grabación
  </h3>

  <!-- Filtro -->
  <div class="rounded-2xl bg-white border border-gray-200 shadow p-5 mb-5">
    <form id="formFiltroPost" method="GET" class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
      <div>
        <label for="filtroFechaPost" class="block text-sm font-medium text-gray-700">
          Selecciona fecha
        </label>
        <input
          type="date"
          name="fecha"
          id="filtroFechaPost"
          value="<?= htmlspecialchars($fecha) ?>"
          class="mt-1 h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
        >
      </div>
      <button
        type="submit"
        class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
        Filtrar
      </button>
    </form>
  </div>

  <?php if ($res->num_rows === 0): ?>
    <div class="rounded-2xl bg-white border border-gray-200 shadow p-6">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center">ℹ️</div>
        <p class="text-sm text-gray-600">
          No hay reservas aprobadas para
          <span class="font-medium text-gray-800"><?= htmlspecialchars($fecha) ?></span>.
        </p>
      </div>
    </div>
  <?php else: ?>
    <div class="rounded-2xl bg-white border border-gray-200 shadow divide-y">
      <?php while ($f = $res->fetch_assoc()): ?>
        <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="min-w-0">
            <div class="text-sm font-semibold text-gray-800 truncate">
              <?= htmlspecialchars($f['asignatura']) ?>
            </div>
            <div class="text-xs text-gray-500 truncate">
              <?= htmlspecialchars($f['tema_video']) ?>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <span class="inline-flex items-center h-7 px-2 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-medium">
              <?= htmlspecialchars($f['hora_reserva']) ?>
            </span>
            <a
              href="post_formulario.php?id=<?= (int)$f['id'] ?>"
              class="inline-flex items-center h-9 px-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs font-medium text-gray-700">
              Procesar
            </a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>
