<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");

$usuario = $_SESSION["nombre_usuario"] ?? '';

// Consulta preparada
$sql = "SELECT facultad, escuela, codigo_materia, materia, seccion
        FROM cargaacade
        WHERE docente = ?
        ORDER BY facultad, materia, seccion";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="rounded-2xl bg-white border border-gray-200 shadow p-5">
  <div class="flex items-center justify-between gap-3 mb-4">
    <h3 class="text-lg font-semibold text-gray-800">
      <i class="fa-solid fa-book mr-2 text-indigo-600"></i> Mis Materias Asignadas
    </h3>

    <!-- (Opcional) Filtro rápido por texto -->
    <div class="hidden sm:flex items-center h-10 bg-gray-100 rounded-xl px-3">
      <i class="fa-solid fa-magnifying-glass text-gray-500"></i>
      <input id="filtroMaterias" type="search" placeholder="Buscar materia o código…"
             class="bg-transparent outline-none px-2 text-sm w-56" />
    </div>
  </div>

  <?php if ($result->num_rows === 0): ?>
    <div class="p-6 text-center">
      <div class="mx-auto w-12 h-12 rounded-xl bg-gray-100 grid place-items-center text-gray-500 mb-3">
        <i class="fa-regular fa-folder-open"></i>
      </div>
      <p class="text-sm text-gray-500">No tienes materias asignadas por ahora.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-left">
            <th class="px-4 py-3 font-medium text-gray-600">Facultad</th>
            <th class="px-4 py-3 font-medium text-gray-600">Escuela</th>
            <th class="px-4 py-3 font-medium text-gray-600">Código</th>
            <th class="px-4 py-3 font-medium text-gray-600">Materia</th>
            <th class="px-4 py-3 font-medium text-gray-600">Sección</th>
            <th class="px-4 py-3 font-medium text-gray-600 text-center">Acción</th>
          </tr>
        </thead>
        <tbody id="tablaMaterias" class="[&>tr:nth-child(even)]:bg-gray-50">
          <?php while ($fila = $result->fetch_assoc()): ?>
            <tr class="align-top">
              <td class="px-4 py-3"><?= htmlspecialchars($fila["facultad"]) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($fila["escuela"]) ?></td>
              <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($fila["codigo_materia"]) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($fila["materia"]) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($fila["seccion"]) ?></td>
              <td class="px-4 py-3">
                <form action="reserva.php" method="POST" class="m-0 flex justify-center">
                  <input type="hidden" name="codigo_materia" value="<?= htmlspecialchars($fila["codigo_materia"]) ?>">
                  <input type="hidden" name="materia"         value="<?= htmlspecialchars($fila["materia"]) ?>">
                  <input type="hidden" name="seccion"         value="<?= htmlspecialchars($fila["seccion"]) ?>">
                  <button type="submit"
                          class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    <i class="fa-solid fa-plus"></i> Reservar
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php $stmt->close(); ?>

<script>
// Filtro rápido por texto (cliente)
const input = document.getElementById('filtroMaterias');
const tbody = document.getElementById('tablaMaterias');
input?.addEventListener('input', () => {
  const q = input.value.toLowerCase().trim();
  tbody.querySelectorAll('tr').forEach(tr => {
    const text = tr.innerText.toLowerCase();
    tr.style.display = text.includes(q) ? '' : 'none';
  });
});
</script>
