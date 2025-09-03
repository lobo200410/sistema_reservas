<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");

if (strtolower($_SESSION["rol"] ?? '') !== "docente") { exit("No autorizado."); }

date_default_timezone_set('America/El_Salvador');
$usuario = $_SESSION["nombre_usuario"] ?? '';

// Normaliza a YYYY-MM-DD desde 'YYYY-MM-DD', 'DD/MM/YYYY' o 'DD-MM-YYYY'
function normaliza_fecha($v, $fallback) {
  $v = trim((string)$v);
  if ($v === '') return $fallback;
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
  if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $v, $m)) {
    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  }
  $ts = strtotime($v);
  return $ts !== false ? date('Y-m-d', $ts) : $fallback;
}

$hoy = date('Y-m-d');
$fecha_filtrada = normaliza_fecha($_GET['fecha'] ?? '', $hoy);

// Consulta: reservas del usuario en esa fecha
$sql = "
  SELECT r.id, r.fecha_reserva, r.hora_reserva, r.estado, r.tema_video,
         r.recursos, r.docente_nombre,
         f.nombre AS facultad_nombre, e.nombre AS escuela_nombre
  FROM reservations r
  LEFT JOIN facultades f ON r.facultad_id = f.id
  LEFT JOIN escuelas   e ON r.escuela_id = e.id
  WHERE r.nombre_usuario = ?
    AND r.fecha_reserva = ?
  ORDER BY r.hora_reserva ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $usuario, $fecha_filtrada);
$stmt->execute();
$res = $stmt->get_result();
$hay = $res->num_rows;
?>

<!-- Filtro -->
<div class="rounded-2xl bg-white border border-gray-200 shadow p-5 mb-5">
  <form id="formFiltroFecha" class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
    <div>
      <label for="filtroFecha" class="block text-sm font-medium text-gray-700">Filtrar por fecha</label>
      <input
        type="date"
        name="fecha"
        id="filtroFecha"
        value="<?= htmlspecialchars($fecha_filtrada) ?>"
        class="mt-1 h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
    </div>
    <button type="submit" id="btnBuscarFecha"
      class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
      Buscar
    </button>
  </form>
</div>

<h3 class="text-lg font-semibold text-gray-800 mb-3">
  Mis reservas para <?= htmlspecialchars($fecha_filtrada) ?>
</h3>

<div class="rounded-2xl bg-white border border-gray-200 shadow overflow-x-auto">
  <?php if ($hay === 0): ?>
    <div class="p-6 text-center">
      <div class="mx-auto w-12 h-12 rounded-xl bg-gray-100 grid place-items-center text-gray-500 mb-3">
        <i class="fa-regular fa-folder-open"></i>
      </div>
      <p class="text-sm text-gray-500">No hay reservas para esta fecha.</p>
    </div>
  <?php else: ?>
  <table class="min-w-[900px] w-full text-sm">
    <thead>
      <tr class="bg-gray-50 text-left text-gray-600">
        <th class="px-4 py-3 font-medium">Hora</th>
        <th class="px-4 py-3 font-medium">Estado</th>
        <th class="px-4 py-3 font-medium">Tema</th>
        <th class="px-4 py-3 font-medium">Facultad</th>
        <th class="px-4 py-3 font-medium">Escuela</th>
        <th class="px-4 py-3 font-medium">Recursos</th>
        <th class="px-4 py-3 font-medium">Acciones</th>
      </tr>
    </thead>
    <tbody class="[&>tr:nth-child(even)]:bg-gray-50">
      <?php while ($fila = $res->fetch_assoc()):
        $estado = strtolower($fila['estado'] ?? '');
        $badge = [
          'pendiente'  => 'bg-amber-100 text-amber-700',
          'aprobada'   => 'bg-emerald-100 text-emerald-700',
          'rechazada'  => 'bg-rose-100 text-rose-700',
          'completada' => 'bg-indigo-100 text-indigo-700'
        ][$estado] ?? 'bg-gray-100 text-gray-700';
        $chips = array_filter(array_map('trim', explode(',', $fila['recursos'] ?? '')));
      ?>
      <tr data-id="<?= (int)$fila['id'] ?>" data-estado="<?= htmlspecialchars($estado) ?>">
        <td class="px-4 py-3"><?= htmlspecialchars($fila['hora_reserva']) ?></td>
        <td class="px-4 py-3"><span class="px-2 py-1 rounded-lg text-xs capitalize <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
        <td class="px-4 py-3"><?= htmlspecialchars($fila['tema_video']) ?></td>
        <td class="px-4 py-3"><?= htmlspecialchars($fila['facultad_nombre']) ?></td>
        <td class="px-4 py-3"><?= htmlspecialchars($fila['escuela_nombre']) ?></td>
        <td class="px-4 py-3">
          <div class="flex flex-wrap gap-1">
            <?php foreach ($chips as $c): ?>
              <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs"><?= htmlspecialchars($c) ?></span>
            <?php endforeach; ?>
          </div>
        </td>
        <td class="px-4 py-3">
          <?php if ($estado === 'pendiente'): ?>
            <a href="editar_reserva.php?id=<?= (int)$fila['id'] ?>&back=docente&vista=mis_reservas&fecha=<?= urlencode($fecha_filtrada) ?>"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs">
              Editar
            </a>
          <?php else: ?>
            <span class="px-2 py-1 rounded-lg text-xs bg-gray-100 text-gray-700">Sin acciones</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
?>
