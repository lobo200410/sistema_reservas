// === util: loader bonito en el panel
function setLoading(el) {
  el.innerHTML = `
    <div class="flex items-center gap-2 text-sm text-gray-500">
      <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor"></path>
      </svg>
      Cargando…
    </div>`;
}

// === util: vista -> archivo php (mismo mapping que ya usas)
function viewToFile(vista) {
  switch (vista) {
    case 'inicio':        return 'contenido_inicio.php';
    case 'mis_materias':  return 'mis_materias.php';
    case 'mis_reservas':  return 'mis_reservas.php';
    case 'soporte':       return 'soporte.php';
    case 'reserva':       return 'reserva.php';
    default:              return 'contenido_inicio.php';
  }
}

// === util: ejecutar <script> que llegan dentro del HTML inyectado
function hydrateScripts(container) {
  const scripts = container.querySelectorAll('script');
  scripts.forEach(old => {
    const s = document.createElement('script');
    // conserva type="module" y otros attrs
    [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
    s.textContent = old.textContent;
    old.replaceWith(s);
  });
}

// === util: cerrar drawer (si existe) cuando se navega en móvil
function closeDrawerIfMobile() {
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (window.innerWidth < 768 && sidebar && backdrop) {
    sidebar.classList.add('-translate-x-full');
    backdrop.classList.add('hidden');
  }
}

// === util: marcar activo en el sidebar
function setActive(vista) {
  const links = document.querySelectorAll('.sidebar a');
  links.forEach(a => {
    const on = a.getAttribute('onclick') || '';
    const m = on.match(/cargarContenido\(['"]([^'"]+)['"]\)/);
    const v = m ? m[1] : null;
    a.classList.remove('bg-gray-100', 'text-gray-900', 'font-medium');
    if (v && v === vista) a.classList.add('bg-gray-100', 'text-gray-900', 'font-medium');
  });
}

// === (opcional) breadcrumb simple si existe #crumb
function updateCrumb(vista) {
  const map = {
    inicio: 'Inicio',
    mis_materias: 'Mis Materias',
    mis_reservas: 'Mis Reservas',
    soporte: 'Soporte',
    reserva: 'Nueva Reserva'
  };
  const crumb = document.getElementById('crumb');
  if (crumb && map[vista]) crumb.textContent = map[vista];
}

// === evita cargas duplicadas si ya está activa
let _vistaActual = null;

// === tu función principal, mejorada
function cargarContenido(vista) {
  const archivo = viewToFile(vista);
  const cont = document.getElementById('contenido-principal');
  if (!cont) return;

  if (_vistaActual === vista) return; // evita doble fetch
  _vistaActual = vista;

  setActive(vista);
  updateCrumb(vista);
  setLoading(cont);

  // (opcional) cache-busting si tus vistas cambian mucho:
  // const url = archivo + (archivo.includes('?') ? '&' : '?') + '_=' + Date.now();
  const url = archivo;

  fetch(url, {
    credentials: 'same-origin',                  // importante para PHP con sesiones
    headers: { 'X-Requested-With': 'fetch' }     // por si decides tratar AJAX en el backend
  })
  .then(res => {
    if (!res.ok) throw new Error('HTTP ' + res.status + ' al cargar ' + archivo);
    return res.text();
  })
  .then(html => {
    cont.innerHTML = html;
    hydrateScripts(cont);     // habilita <script> embebidos en tus vistas
    closeDrawerIfMobile();    // cierra el menú en móvil
  })
  .catch(err => {
    cont.innerHTML = `
      <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        Error al cargar contenido.<br><code>${String(err)}</code>
      </div>`;
    console.error(err);
  });
}

// === helper público para abrir la vista de reserva con datos prellenados
function openReserva(codigo_materia, materia, seccion) {
  window.__prefillReserva = { codigo_materia, materia, seccion };
  cargarContenido('reserva');
}

// === carga inicial
window.onload = () => cargarContenido('inicio');

// === (opcional) controles de drawer del dashboard moderno con Tailwind
(function initDrawer(){
  const sidebar  = document.getElementById('sidebar');
  const openBtn  = document.getElementById('openSidebar');
  const closeBtn = document.getElementById('closeSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  function openSidebar(){ sidebar?.classList.remove('-translate-x-full'); backdrop?.classList.remove('hidden'); }
  function closeSidebar(){ sidebar?.classList.add('-translate-x-full');   backdrop?.classList.add('hidden'); }

  openBtn?.addEventListener('click', (e)=>{ e.preventDefault(); openSidebar(); });
  closeBtn?.addEventListener('click', (e)=>{ e.preventDefault(); closeSidebar(); });
  backdrop?.addEventListener('click', closeSidebar);

  window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) { sidebar?.classList.remove('-translate-x-full'); backdrop?.classList.add('hidden'); }
    else { sidebar?.classList.add('-translate-x-full'); }
  });
})();
