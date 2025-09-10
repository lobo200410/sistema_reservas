// app.js
console.log("APP JS v8 - " + new Date().toISOString());

function loadScriptOnce(src) {
  return new Promise((resolve, reject) => {
    const exists = Array.from(document.scripts).some(s => s.src && s.src.includes(src));
    if (exists) { resolve(); return; }
    const el = document.createElement('script');
    el.src = src + (src.includes('?') ? '&' : '?') + '_ts=' + Date.now(); // cache-bust
    el.onload = () => resolve();
    el.onerror = () => reject(new Error('No se pudo cargar ' + src));
    document.head.appendChild(el);
  });
}

let __ultimaFechaReservas = null;
let __ultimaFechaPost = null;

function cargarContenido(vista, parametros = "") {
  if (vista === 'reservas') {
    if (parametros) {
      try {
        const p = new URLSearchParams(parametros);
        const f = p.get("fecha");
        if (f) __ultimaFechaReservas = f;
      } catch (e) {}
    } else if (__ultimaFechaReservas) {
      parametros = "fecha=" + encodeURIComponent(__ultimaFechaReservas);
    }
  } else if (vista === 'post') {
    if (parametros) {
      try {
        const p = new URLSearchParams(parametros);
        const f = p.get("fecha");
        if (f) __ultimaFechaPost = f;
      } catch (e) {}
    } else if (__ultimaFechaPost) {
      parametros = "fecha=" + encodeURIComponent(__ultimaFechaPost);
    }
  }

  let archivo = "";
  switch (vista) {
    case 'inicio':           archivo = 'contenido_inicio.php'; break;
    case 'reservas':         archivo = 'ver_reservas.php'; break;
    case 'post':             archivo = 'post.php'; break;
    case 'reporte_mensual':  archivo = 'reporte_mensual.php'; break;
    case 'reporte_rango':    archivo = 'reporte_rango.php'; break;
    case 'reporte_anual':    archivo = 'reporte_anual.php'; break;
    case 'gestion':          archivo = 'gestion_usuarios.php'; break;
    case 'horas':            archivo = 'horarios.php'; break;
    case 'misreservas':      archivo = 'mis_reservas.php'; break;
    default:                 archivo = 'contenido_inicio.php';
  }
  if (parametros) archivo += "?" + parametros;

  console.log("[fetch]", archivo);

  fetch(archivo, { cache: "no-store" })
    .then(r => { if (!r.ok) throw new Error("Error en la respuesta"); return r.text(); })
    .then(async (html) => {
      const cont = document.getElementById("contenido-principal");
      if (!cont) { console.error("No existe #contenido-principal"); return; }
      cont.innerHTML = html;

      if (vista === 'horas') {
        try {
          // jQuery YA se cargó en dashboard.php (local). Solo carga la vista:
          await loadScriptOnce('js/horarios.view.js');
          if (typeof window.initHorarios === 'function') {
            window.initHorarios();
          } else {
            throw new Error('initHorarios no encontrado en horarios.view.js');
          }
        } catch (e) {
          console.error('Error iniciando Horarios:', e);
          cont.querySelector('#tbody')?.insertAdjacentHTML(
            'beforeend',
            `<tr><td colspan="7" class="p-4 text-center text-red-600">
               Error iniciando Horarios: ${e.message}
             </td></tr>`
          );
        }
      }
    })
    .catch(err => {
      console.error("Error al cargar:", err);
      const target = document.getElementById("contenido-principal");
      if (target) target.innerHTML = "<p>Error al cargar contenido.</p>";
    });
}

document.addEventListener("DOMContentLoaded", () => {
  const root = document.getElementById("contenido-principal");

  document.addEventListener("submit", (e) => {
    if (e.target && e.target.id === "formFiltroFecha") {
      e.preventDefault();
      const input = e.target.querySelector("#filtroFecha");
      const fecha = (input?.value || "").trim();
      if (!fecha) { alert("Selecciona una fecha."); return; }
      __ultimaFechaReservas = fecha;
      cargarContenido("reservas", "fecha=" + encodeURIComponent(fecha));
    }
    if (e.target && e.target.id === "formFiltroPost") {
      e.preventDefault();
      const input = e.target.querySelector("#filtroFechaPost");
      const fecha = (input?.value || "").trim();
      if (!fecha) { alert("Selecciona una fecha."); return; }
      __ultimaFechaPost = fecha;
      cargarContenido("post", "fecha=" + encodeURIComponent(fecha));
    }
  }, true);

  document.addEventListener("click", (e) => {
    const btnBuscarReservas = e.target.closest("#btnBuscarFecha");
    if (btnBuscarReservas) {
      e.preventDefault();
      const input = document.getElementById("filtroFecha");
      const fecha = (input?.value || "").trim();
      if (!fecha) { alert("Selecciona una fecha."); return; }
      __ultimaFechaReservas = fecha;
      cargarContenido("reservas", "fecha=" + encodeURIComponent(fecha));
      return;
    }

    const btnSubmitPost = e.target.closest("#formFiltroPost button[type='submit']");
    if (btnSubmitPost) { /* vacío a propósito */ }

    const btnAccion = e.target.closest(".btn-accion");
    if (btnAccion) {
      e.preventDefault();
      const id = btnAccion.dataset.id;
      const accion = btnAccion.dataset.accion;
      const fecha = (root?.querySelector("#filtroFecha")?.value || __ultimaFechaReservas || "").trim();

      fetch("accion_reserva.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `reserva_id=${encodeURIComponent(id)}&accion=${encodeURIComponent(accion)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const qs = fecha ? "fecha=" + encodeURIComponent(fecha) : "";
          cargarContenido("reservas", qs);
        } else {
          alert("Error: " + (data.error || "No se pudo cambiar el estado"));
        }
      })
      .catch(err => {
        console.error("Error AJAX:", err);
        alert("Ocurrió un error.");
      });
    }
  }, true);

  cargarContenido('inicio');
});
