<div class="max-w-5xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Administrar horarios</h1>
    <label class="inline-flex items-center gap-2 text-sm">
      <input type="checkbox" id="toggleAll">
      <span>Ver también inactivos</span>
    </label>
  </div>


  <form id="formNew" class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
      <div>
        <label class="text-sm">Inicio</label>
        <input type="time" id="nIni" required class="mt-1 w-full border rounded px-2 py-1">
      </div>
      <div>
        <label class="text-sm">Fin</label>
        <input type="time" id="nFin" required class="mt-1 w-full border rounded px-2 py-1">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm">Etiqueta (opcional)</label>
        <input type="text" id="nTag" placeholder="HH:MM-HH:MM" class="mt-1 w-full border rounded px-2 py-1">
      </div>
      <div class="flex items-end">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" id="nAct" checked>
          <span class="text-sm">Activo</span>
        </label>
      </div>
    </div>
    <div class="mt-3">
      <button class="bg-indigo-600 text-white px-4 py-2 rounded">Agregar</button>
    </div>
  </form>

  
  <div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2 text-left">Mover</th>
          <th class="px-3 py-2 text-left">Etiqueta</th>
          <th class="px-3 py-2 text-left">Inicio</th>
          <th class="px-3 py-2 text-left">Fin</th>
          <th class="px-3 py-2 text-left">Activo</th>
          <th class="px-3 py-2 text-left">Acciones</th>
          <th class="px-3 py-2 text-left">Eliminar</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="7" class="p-4 text-center text-gray-500">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
  <p class="text-xs text-gray-500 mt-3">Tip: usar las flechas ↑/↓ para reordenar; el orden se guarda al instante.</p>
</div>
