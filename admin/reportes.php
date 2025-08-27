<!-- ===== Reporte Anual por Facultades ===== -->
<div class="rounded-2xl border border-gray-200 bg-white shadow p-5 md:p-6 lg:p-8 max-w-3xl mx-auto mt-8">
  <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-3">Reporte anual por facultades</h2>
  <p class="text-sm md:text-base text-gray-600 mb-5">
    Genera un reporte anual (enero–diciembre) con el total de videos por facultad y totales por mes.
  </p>

  <form action="exportar_reporte.php" method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
    <!-- Año -->
    <div>
      <label for="anio" class="block text-sm font-medium text-gray-700 mb-1">Año</label>
      <input type="number" id="anio" name="anio" required min="2000" max="2100"
             class="w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
    </div>

    <!-- (Opcional) Estado -->
    <div>
      <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado (opcional)</label>
      <select id="estado" name="estado"
              class="w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
        <option value="">Todos</option>
        <option value="aprobada">Aprobada</option>
        <option value="pendiente">Pendiente</option>
        <option value="rechazada">Rechazada</option>
      </select>
    </div>

    <div>
      <button type="submit"
        class="w-full inline-flex justify-center items-center gap-2 h-10 md:h-11 rounded-lg bg-emerald-600 text-white text-sm md:text-base font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 transition">
        <i class="fa-solid fa-file-excel text-white/90"></i>
        Generar reporte anual
      </button>
    </div>
  </form>
</div>

<script>
  // Pre-cargar año actual
  document.getElementById('anio').value = new Date().getFullYear();
</script>
