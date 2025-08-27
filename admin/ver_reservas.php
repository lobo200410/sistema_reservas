<?php
session_start();
include("../conexion.php");

// Establecer zona horaria del servidor para evitar desfases de fecha
date_default_timezone_set('America/El_Salvador');

$rol = strtolower(trim($_SESSION['rol'] ?? '')); // normaliza

if ($rol !== 'admin' && $rol !== 'multimedia') {
    header("Location: ../login.php");
    exit;
}


// Determinar la fecha a filtrar (GET o fecha actual)
$fecha_filtrada = isset($_GET['fecha']) && $_GET['fecha'] !== '' ? $_GET['fecha'] : date('Y-m-d');

// Consulta (sin cambios)
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
    e.nombre AS nombre_escenario,
    s.nombre AS nombre_sala,
    es.nombre AS nombre_escuela,
    f.nombre AS nombre_facultad,
    r.fecha_reserva,
    r.hora_reserva,
    r.estado,
    r.recursos
FROM reservations r
JOIN escenarios e ON r.escenario_id = e.id
JOIN salas s ON e.sala_id = s.id
JOIN escuelas es ON r.escuela_id = es.id
JOIN facultades f ON r.facultad_id = f.id
WHERE r.fecha_reserva = ?
ORDER BY r.hora_reserva ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fecha_filtrada);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Filtro de fecha -->
<div class="rounded-2xl bg-white border border-gray-200 shadow p-5 mb-5">
  <form id="formFiltroFecha" method="GET" class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
    <div>
      <label for="filtroFecha" class="block text-sm font-medium text-gray-700">Filtrar por fecha</label>
      <input
        type="date"
        name="fecha"
        id="filtroFecha"
        value="<?php echo htmlspecialchars($fecha_filtrada); ?>"
        class="mt-1 h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
    </div>
    <button
      type="button" id="btnBuscarFecha"
      class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
      Buscar
    </button>
  </form>
</div>

<h3 class="text-lg font-semibold text-gray-800 mb-3">
  Reservas para <?php echo htmlspecialchars($fecha_filtrada); ?>
</h3>

<div class="overflow-x-auto rounded-2xl bg-white border border-gray-200 shadow">
  <table class="min-w-[1000px] w-full text-sm">
    <thead>
      <tr class="bg-gray-50 text-left text-gray-600">
        <th class="px-4 py-3 font-medium">Docente</th>
        <th class="px-4 py-3 font-medium">Correo</th>
        <th class="px-4 py-3 font-medium">Asignatura</th>
        <th class="px-4 py-3 font-medium">Sección</th>
        <th class="px-4 py-3 font-medium">Tema del video</th>
        <th class="px-4 py-3 font-medium">Tipo de video</th>
        <th class="px-4 py-3 font-medium">Escenario</th>
        <th class="px-4 py-3 font-medium">Sala</th>
        <th class="px-4 py-3 font-medium">Escuela</th>
        <th class="px-4 py-3 font-medium">Facultad</th>
        <th class="px-4 py-3 font-medium">Fecha</th>
        <th class="px-4 py-3 font-medium">Hora</th>
        <th class="px-4 py-3 font-medium">Estado</th>
        <th class="px-4 py-3 font-medium">Recursos</th>
        <th class="px-4 py-3 font-medium text-center">Acción</th>
      </tr>
    </thead>
    <tbody class="[&>tr:nth-child(even)]:bg-gray-50">
      <?php while ($fila = $resultado->fetch_assoc()) {
        $estado = strtolower($fila['estado']);
        $badge = 'bg-gray-100 text-gray-700';
        if ($estado === 'pendiente') $badge = 'bg-amber-100 text-amber-700';
        if ($estado === 'aprobado' || $estado === 'aprobada') $badge = 'bg-emerald-100 text-emerald-700';
        if ($estado === 'rechazado' || $estado === 'rechazada') $badge = 'bg-rose-100 text-rose-700';

        $chips = array_filter(array_map('trim', explode(',', $fila['recursos'] ?? '')));
      ?>
        <tr class="text-gray-800">
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["docente_nombre"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["docente_correo"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["asignatura"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["seccion"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["tema_video"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["tipo_video_id"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["nombre_escenario"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["nombre_sala"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["nombre_escuela"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["nombre_facultad"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["fecha_reserva"]); ?></td>
          <td class="px-4 py-3"><?php echo htmlspecialchars($fila["hora_reserva"]); ?></td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded-lg text-xs capitalize <?php echo $badge; ?>">
              <?php echo htmlspecialchars($fila["estado"]); ?>
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1">
              <?php foreach ($chips as $c): ?>
                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs"><?php echo htmlspecialchars($c); ?></span>
              <?php endforeach; ?>
            </div>
          </td>
          <td class="px-4 py-3">
            <?php if ($fila['estado'] === 'pendiente') { ?>
              <div class="flex items-center justify-center gap-2">
                <button
                  class="btn-accion inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs"
                  data-id="<?php echo (int)$fila['id']; ?>" data-accion="aceptar">
                  Aceptar
                </button>
                <button
                  class="btn-accion inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rose-600 text-white hover:bg-rose-700 text-xs"
                  data-id="<?php echo (int)$fila['id']; ?>" data-accion="rechazar">
                  Rechazar
                </button>
              </div>
            <?php } else { ?>
              <div class="flex items-center justify-center gap-2">
                <span class="px-2 py-1 rounded-lg text-xs <?php echo ($estado === 'aprobado' || $estado === 'aprobada') ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'; ?>">
                  <?php echo ucfirst(htmlspecialchars($fila['estado'])); ?>
                </span>
                <a href="editar_reserva.php?id=<?php echo (int)$fila['id']; ?>&back=dashboard&vista=reservas&fecha=<?php echo urlencode($fecha_filtrada); ?>"
                  class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs">
                  Editar
                </a>

              </div>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>

      <?php if ($resultado->num_rows === 0): ?>
        <tr>
          <td colspan="15" class="px-4 py-6 text-center text-sm text-gray-500">
            No hay reservas para esta fecha.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$stmt->close();
$conn->close();
?>