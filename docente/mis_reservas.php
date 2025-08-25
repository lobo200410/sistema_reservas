<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");

if (($_SESSION["rol"] ?? '') !== "docente") { exit("No autorizado."); }

$usuario = $_SESSION["nombre_usuario"] ?? '';

$sql = "
  SELECT r.id, r.fecha_reserva, r.hora_reserva, r.estado, r.tema_video, 
         r.docente_nombre, r.recursos,
         f.nombre AS facultad_nombre, e.nombre AS escuela_nombre
  FROM reservations r
  LEFT JOIN facultades f ON r.facultad_id = f.id
  LEFT JOIN escuelas   e ON r.escuela_id = e.id
  WHERE r.nombre_usuario = ?
  ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$reservas = $stmt->get_result();
?>

<div class="rounded-2xl bg-white border border-gray-200 shadow p-5">
  <div class="flex items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-semibold text-gray-800">
      <i class="fa-solid fa-calendar-check mr-2 text-indigo-600"></i> Mis Reservas de Grabación
    </h2>
    <div class="flex items-center gap-2">
      <select id="filtroEstado" class="h-10 rounded-lg border-gray-300 bg-white text-sm px-2">
        <option value="">Todos</option>
        <option value="pendiente">Pendiente</option>
        <option value="aprobada">Aprobada</option>
        <option value="rechazada">Rechazada</option>
        <option value="completada">Completada</option>
      </select>
    </div>
  </div>

  <?php if ($reservas->num_rows === 0): ?>
    <div class="p-6 text-center">
      <div class="mx-auto w-12 h-12 rounded-xl bg-gray-100 grid place-items-center text-gray-500 mb-3">
        <i class="fa-regular fa-folder-open"></i>
      </div>
      <p class="text-sm text-gray-500">Aún no tienes reservas registradas.</p>
    </div>
  <?php else: ?>

  <!-- Tabla en pantallas medianas y grandes -->
  <div class="hidden md:block overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-left">
          <th class="px-4 py-3 font-medium text-gray-600">Fecha</th>
          <th class="px-4 py-3 font-medium text-gray-600">Hora</th>
          <th class="px-4 py-3 font-medium text-gray-600">Estado</th>
          <th class="px-4 py-3 font-medium text-gray-600">Tema</th>
          <th class="px-4 py-3 font-medium text-gray-600">Docente</th>
          <th class="px-4 py-3 font-medium text-gray-600">Facultad</th>
          <th class="px-4 py-3 font-medium text-gray-600">Escuela</th>
          <th class="px-4 py-3 font-medium text-gray-600">Recursos</th>
        </tr>
      </thead>
      <tbody class="[&>tr:nth-child(even)]:bg-gray-50">
        <?php while ($fila = $reservas->fetch_assoc()): 
          $estado = strtolower($fila['estado'] ?? '');
          $badge = [
            'pendiente'  => 'bg-amber-100 text-amber-700',
            'aprobada'   => 'bg-emerald-100 text-emerald-700',
            'rechazada'  => 'bg-rose-100 text-rose-700',
            'completada' => 'bg-indigo-100 text-indigo-700'
          ][$estado] ?? 'bg-gray-100 text-gray-700';

          $chips = array_filter(array_map('trim', explode(',', $fila['recursos'] ?? '')));
        ?>
        <tr data-estado="<?= htmlspecialchars($estado) ?>">
          <td class="px-4 py-3"><?= htmlspecialchars($fila['fecha_reserva']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($fila['hora_reserva']) ?></td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded-lg text-xs capitalize <?= $badge ?>">
              <?= htmlspecialchars($estado) ?>
            </span>
          </td>
          <td class="px-4 py-3"><?= htmlspecialchars($fila['tema_video']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($fila['docente_nombre']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($fila['facultad_nombre']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($fila['escuela_nombre']) ?></td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1">
              <?php foreach ($chips as $c): ?>
                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs"><?= htmlspecialchars($c) ?></span>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Tarjetas para móviles -->
  <div class="md:hidden space-y-3">
    <?php $reservas->data_seek(0); while ($fila = $reservas->fetch_assoc()):
      $estado = strtolower($fila['estado'] ?? '');
      $badge = [
        'pendiente'  => 'bg-amber-100 text-amber-700',
        'aprobada'   => 'bg-emerald-100 text-emerald-700',
        'rechazada'  => 'bg-rose-100 text-rose-700',
        'completada' => 'bg-indigo-100 text-indigo-700'
      ][$estado] ?? 'bg-gray-100 text-gray-700';
      $chips = array_filter(array_map('trim', explode(',', $fila['recursos'] ?? '')));
    ?>
    <div class="border border-gray-200 rounded-2xl p-4" data-estado="<?= htmlspecialchars($estado) ?>">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium"><?= htmlspecialchars($fila['tema_video']) ?></p>
          <p class="text-xs text-gray-500"><?= htmlspecialchars($fila['fecha_reserva']) ?> · <?= htmlspecialchars($fila['hora_reserva']) ?></p>
        </div>
        <span class="px-2 py-1 rounded-lg text-xs capitalize <?= $badge ?>"><?= htmlspecialchars($estado) ?></span>
      </div>
      <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
        <p><span class="text-gray-500">Docente:</span> <?= htmlspecialchars($fila['docente_nombre']) ?></p>
        <p><span class="text-gray-500">Facultad:</span> <?= htmlspecialchars($fila['facultad_nombre']) ?></p>
        <p><span class="text-gray-500">Escuela:</span> <?= htmlspecialchars($fila['escuela_nombre']) ?></p>
      </div>
      <?php if ($chips): ?>
      <div class="mt-2 flex flex-wrap gap-1">
        <?php foreach ($chips as $c): ?>
          <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs"><?= htmlspecialchars($c) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
  </div>

  <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
?>

<script>
// Filtro por estado
const sel = document.getElementById('filtroEstado');
sel?.addEventListener('change', () => {
  const v = sel.value;
  document.querySelectorAll('[data-estado]').forEach(row => {
    row.style.display = (!v || row.getAttribute('data-estado') === v) ? '' : 'none';
  });
});
</script>
