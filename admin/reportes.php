<!-- reportes.php -->
<div class="rounded-2xl border border-gray-200 bg-white shadow p-5 md:p-6 lg:p-8 max-w-3xl mx-auto">
  <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-3">Generar Reporte de Reservas</h2>

  <p class="text-sm md:text-base text-gray-600 mb-5">
    Genera un reporte de las reservas de las salas p.
  </p>

  <form action="exportar_reporte.php" method="post" class="flex items-center gap-3">
    <button type="submit"
      class="inline-flex items-center gap-2 h-10 px-4 md:h-11 md:px-5 rounded-lg bg-indigo-600 text-white text-sm md:text-base font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 transition">
      <i class="fa-solid fa-file-excel text-white/90"></i>
      Generar Reporte Excel
    </button>
  </form>
</div>
