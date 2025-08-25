// Recordar la última fecha usada en cada vista
let __ultimaFechaReservas = null;
let __ultimaFechaPost = null;

function cargarContenido(vista, parametros = "") {
  // Si llaman sin parámetros, inyectar la última fecha conocida
  if (vista === 'reservas') {
    if (parametros) {
      try {
        const p = new URLSearchParams(parametros);
        const f = p.get("fecha");
        if (f) __ultimaFechaReservas = f;
      } catch (_) {}
    } else if (__ultimaFechaReservas) {
      parametros = "fecha=" + encodeURIComponent(__ultimaFechaReservas);
    }
  } else if (vista === 'post') {
    if (parametros) {
      try {
        const p = new URLSearchParams(parametros);
        const f = p.get("fecha");
        if (f) __ultimaFechaPost = f;
      } catch (_) {}
    } else if (__ultimaFechaPost) {
      parametros = "fecha=" + encodeURIComponent(__ultimaFechaPost);
    }
  }

  let archivo = "";
  switch (vista) {
    case 'inicio':      archivo = 'contenido_inicio.php'; break;
    case 'reservas':    archivo = 'ver_reservas.php'; break;
    case 'post':        archivo = 'post.php'; break;
    case 'reportes':    archivo = 'reportes.php'; break;
    case 'gestion':     archivo = 'gestion_usuarios.php'; break;
    case 'horas':       archivo = 'horarios.php'; break;
    case 'misreservas': archivo = 'mis_reservas.php'; break;
    default:            archivo = 'contenido_inicio.php';
  }
  if (parametros) archivo += "?" + parametros;

  console.log("[fetch]", archivo);

  fetch(archivo, { cache: "no-store" })
    .then(r => { if (!r.ok) throw new Error("Error en la respuesta"); return r.text(); })
    .then(html => { document.getElementById("contenido-principal").innerHTML = html; })
    .catch(err => {
      console.error("Error al cargar:", err);
      document.getElementById("contenido-principal").innerHTML = "<p>Error al cargar contenido.</p>";
    });
}

document.addEventListener("DOMContentLoaded", () => {
  const root = document.getElementById("contenido-principal");

  // SUBMIT (captura) — Filtro de Reservas
  document.addEventListener("submit", (e) => {
    if (e.target && e.target.id === "formFiltroFecha") {
      e.preventDefault();
      const input = e.target.querySelector("#filtroFecha");
      const fecha = (input?.value || "").trim();
      if (!fecha) { alert("Selecciona una fecha."); return; }
      __ultimaFechaReservas = fecha;
      cargarContenido("reservas", "fecha=" + encodeURIComponent(fecha));
    }
    // SUBMIT (captura) — Filtro de Post
    if (e.target && e.target.id === "formFiltroPost") {
      e.preventDefault();
      const input = e.target.querySelector("#filtroFechaPost");
      const fecha = (input?.value || "").trim();
      if (!fecha) { alert("Selecciona una fecha."); return; }
      __ultimaFechaPost = fecha;
      cargarContenido("post", "fecha=" + encodeURIComponent(fecha));
    }
  }, true); // capture

  // CLICK (captura) — Botón Buscar de Reservas (por si es type="button")
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

    // CLICK (captura) — Botón submit dentro de formFiltroPost (por si lo necesitas)
    const btnSubmitPost = e.target.closest("#formFiltroPost button[type='submit']");
    if (btnSubmitPost) {
      // Permitimos que el handler de submit haga el trabajo; no hacemos nada aquí.
      // Solo está por si algún otro script intenta cortar la propagación.
    }

    // Botones Aprobar/Rechazar
    const btnAccion = e.target.closest(".btn-accion");
    if (btnAccion) {
      e.preventDefault();
      const id = btnAccion.dataset.id;
      const accion = btnAccion.dataset.accion;
      const fecha = root.querySelector("#filtroFecha")?.value
                 || __ultimaFechaReservas
                 || "";

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
});
