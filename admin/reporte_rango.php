<div class="max-w-3xl mx-auto mt-8 px-4">
  <header class="mb-6 text-center">
    <h1 class="text-2xl md:text-3xl font-semibold text-gray-900">Reporte por Rango de Fechas</h1>
    <p class="text-sm md:text-base text-gray-500 mt-2">Genera el reporte de reservas entre dos fechas, con opción de filtrar por estado.</p>
  </header>

  <form action="exportar_reporte.php" method="post" 
        class="bg-white rounded-xl border border-gray-200 shadow-subtle p-6 space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Desde <span class="text-red-500">*</span></label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" required
               class="mt-1 w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500">
      </div>
      <div>
        <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Hasta <span class="text-red-500">*</span></label>
        <input type="date" id="fecha_fin" name="fecha_fin" required
               class="mt-1 w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500">
      </div>
    </div>

    <div>
      <label for="estado" class="block text-sm font-medium text-gray-700">Estado (opcional)</label>
      <select id="estado" name="estado" 
              class="mt-1 w-full h-10 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500">
        <option value="">Todos</option>
        <option value="aprobada">Aprobada</option>
        <option value="pendiente">Pendiente</option>
        <option value="rechazada">Rechazada</option>
      </select>
    </div>

    <input type="hidden" name="tipo_reporte" value="rango">

    <button type="submit" 
            class="btn w-full h-11 rounded-lg bg-indigo-600 text-white font-medium hover:opacity-90">
      <i class="fa-solid fa-file-excel mr-2"></i> Generar reporte por rango
    </button>
  </form>
</div>
