// === Dashboard Docente — AJAX sin cambiar la URL ===

// Limpia cualquier query visible (?fecha, ?desde, ?hasta) del dashboard docente
(() => {
  if (location.pathname.includes('/docente/')
      && /(?:^|\?|&)(fecha|desde|hasta)=/i.test(location.search)) {
    history.replaceState(null, '', location.pathname);
  }
})();

// Recuerda la última fecha usada en Mis Reservas
let __ultimaFechaMisReservas = null;

// Mapa vistas -> archivo
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

// Normaliza a YYYY-MM-DD
function toYMD(val) {
  if (!val) return '';
  const t = String(val).trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(t)) return t;
  const m = t.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/);
  if (m) return `${m[3]}-${m[2]}-${m[1]}`;
  const d = new Date(t);
  if (!isNaN(d)) {
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const dd = String(d.getDate()).padStart(2,'0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }
  return '';
}

// Carga contenido AJAX
function cargarContenido(vista, parametros = '') {
  if (vista === 'mis_reservas') {
    if (parametros) {
      try { const p = new URLSearchParams(parametros); const f = p.get('fecha'); if (f) __ultimaFechaMisReservas = f; } catch {}
    } else if (__ultimaFechaMisReservas) {
      parametros = 'fecha=' + encodeURIComponent(__ultimaFechaMisReservas);
    }
  }

  const archivo = viewToFile(vista) + (parametros ? `?${parametros}` : '');
  const cont = document.getElementById('contenido-principal');
  if (!cont) return;

  cont.innerHTML = `
    <div class="flex items-center gap-2 text-sm text-gray-500">
      <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor"></path>
      </svg>
      Cargando…
    </div>`;

  fetch(archivo, { cache: 'no-store', credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
    .then(html => { cont.innerHTML = html; })
    .catch(err => {
      console.error('Error al cargar:', err);
      cont.innerHTML = `
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
          Error al cargar contenido.<br><code>${String(err)}</code>
        </div>`;
    });
}
window.cargarContenido = cargarContenido;

// Delegación GLOBAL: captura submit/click del filtro aunque el HTML se reemplace
function initDelegation() {
  // Submit del filtro
  document.addEventListener('submit', (e) => {
    if (e.target && e.target.id === 'formFiltroFecha') {
      e.preventDefault();
      const form  = e.target;
      const raw   = form.elements?.fecha?.value || '';
      const fecha = /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : toYMD(raw);
      if (!fecha) { alert('Fecha inválida'); return; }
      __ultimaFechaMisReservas = fecha;
      cargarContenido('mis_reservas', 'fecha=' + encodeURIComponent(fecha));
    }
  }, true); // captura para ganar al submit nativo

  // Click en botón Buscar (por si es type="button")
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('#btnBuscarFecha');
    if (!btn) return;
    e.preventDefault();
    const form  = btn.closest('#formFiltroFecha') || document.getElementById('formFiltroFecha');
    const raw   = form?.elements?.fecha?.value || '';
    const fecha = /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : toYMD(raw);
    if (!fecha) { alert('Fecha inválida'); return; }
    __ultimaFechaMisReservas = fecha;
    cargarContenido('mis_reservas', 'fecha=' + encodeURIComponent(fecha));
  }, true);
}

function boot() {
  initDelegation();
  cargarContenido('inicio');
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
