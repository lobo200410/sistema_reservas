<div class="max-w-3xl mx-auto mt-8 px-4">
  <header class="mb-6 text-center">
    <h1 class="text-2xl md:text-3xl font-semibold text-gray-900">Reporte Mensual</h1>
    <p class="text-sm md:text-base text-gray-500 mt-2">Genera el reporte de un mes específico. Incluye solo reservas aprobadas.</p>
  </header>

  <form action="exportar_reporte.php" method="post" 
        class="bg-white rounded-xl border border-gray-200 shadow-subtle p-6 space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="mes" class="block text-sm font-medium text-gray-700">Mes <span class="text-red-500">*</span></label>
        <select id="mes" name="mes" required
                class="mt-1 w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500">
          <option value="">Selecciona…</option>
          <option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option>
          <option value="04">Abril</option><option value="05">Mayo</option><option value="06">Junio</option>
          <option value="07">Julio</option><option value="08">Agosto</option><option value="09">Septiembre</option>
          <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
        </select>
      </div>
      <div>
        <label for="anio" class="block text-sm font-medium text-gray-700">Año <span class="text-red-500">*</span></label>
        <input type="number" id="anio" name="anio" min="2000" max="2100" required
               class="mt-1 w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500">
      </div>
    </div>

    <input type="hidden" name="estado" value="aprobada">
    <input type="hidden" name="tipo_reporte" value="mensual">

    <button type="submit" 
            class="btn w-full h-11 rounded-lg bg-indigo-600 text-white font-medium hover:opacity-90">
      <i class="fa-solid fa-file-excel mr-2"></i> Generar reporte mensual
    </button>
  </form>
</div>
