<?php
// ------------------- PHP BOOTSTRAP -------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "docente") {
  header("Location: ../login.php");
  exit;
}

$id_usuario = (int)($_SESSION['usuario_id'] ?? 0);

// Detectar si se está cargando como parcial (AJAX/fetch) o página completa
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

// ------------------- DATA FROM DB -------------------
// Docente (nombre y correo)
$nombre_completo = $correo_usuario = '';
$sql_usuario = "SELECT nombre, apellido, correo FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($u = $result->fetch_assoc()) {
  $nombre_completo = $u['nombre']." ".$u['apellido'];
  $correo_usuario  = $u['correo'];
}
$stmt->close();

// Tipos de video (detailsvideo)
$tipos_video = [];
$qTipos = $conn->query("SELECT valor FROM detailsvideo WHERE tipo='tipo_video'");
if ($qTipos) {
  while ($r = $qTipos->fetch_assoc()) $tipos_video[] = $r['valor'];
}

// Escenarios + sala
$escenarios = [];
$qEsc = $conn->query("SELECT e.id, e.nombre, s.nombre AS sala FROM escenarios e JOIN salas s ON e.sala_id = s.id ORDER BY e.nombre");
if ($qEsc) {
  while ($r = $qEsc->fetch_assoc()) $escenarios[] = $r;
}

// Escuelas
$escuelas = [];
$qEscuelas = $conn->query("SELECT id, nombre FROM escuelas ORDER BY nombre");
if ($qEscuelas) {
  while ($r = $qEscuelas->fetch_assoc()) $escuelas[] = $r;
}

// Facultades
$facultades = [];
$qFacs = $conn->query("SELECT id, nombre FROM facultades ORDER BY nombre");
if ($qFacs) {
  while ($r = $qFacs->fetch_assoc()) $facultades[] = $r;
}

// Prefill desde POST (fallback si entras directo)
$materia        = $_POST["materia"] ?? '';
$seccion        = $_POST["seccion"] ?? '';
$codigo_materia = $_POST["codigo_materia"] ?? '';

// Opciones de recursos (puedes mover esto a BD si luego lo necesitas dinámico)
$recursosOpciones = [
  "Documentos PDF",
  "Documento de Word",
  "Libro de Excel",
  "Videos",
  "Presentación de PowerPoint",
  "Presentación de programa",
];

// ------------------- HTML WRAPPER (solo si NO es Ajax) -------------------
if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reservar Video</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tailwind CDN solo en modo página completa -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: { 600:'#61122F', 700:'#4D0E26' } },
          boxShadow: { soft: '0 12px 32px rgba(16,24,40,.07)' }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
  <main class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">
<?php endif; ?>

  <!-- --------------- FORMULARIO --------------- -->
  <div class="rounded-2xl bg-white border border-gray-200 shadow p-5 sm:p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">
        <i class="fa-solid fa-video mr-2 text-indigo-600"></i> Reservar Grabación
      </h3>
      <div class="text-xs text-gray-500">Los campos marcados con * son obligatorios</div>
    </div>

    <form method="POST" action="guardar_reserva.php" class="space-y-8" id="formReserva">
      <!-- Datos del Docente -->
      <section>
        <h4 class="text-sm font-semibold text-gray-600 mb-3">Datos del Docente</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre completo</label>
            <input type="text" value="<?= htmlspecialchars($nombre_completo) ?>" disabled
              class="mt-1 w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 px-3 py-2">
            <input type="hidden" name="docente" value="<?= htmlspecialchars($nombre_completo) ?>">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Correo</label>
            <input type="email" value="<?= htmlspecialchars($correo_usuario) ?>" disabled
              class="mt-1 w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 px-3 py-2">
            <input type="hidden" name="correo_docente" value="<?= htmlspecialchars($correo_usuario) ?>">
          </div>
        </div>
      </section>

      <!-- Datos de la grabación -->
      <section>
        <h4 class="text-sm font-semibold text-gray-600 mb-3">Datos de la Grabación</h4>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Asignatura</label>
            <input type="text" name="asignatura" value="<?= htmlspecialchars($materia) ?>" readonly
              class="mt-1 w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Sección</label>
            <input type="text" name="seccion" value="<?= htmlspecialchars($seccion) ?>" readonly
              class="mt-1 w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Código</label>
            <input type="text" value="<?= htmlspecialchars($codigo_materia) ?>" disabled
              class="mt-1 w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 px-3 py-2">
            <input type="hidden" name="codigo_materia" value="<?= htmlspecialchars($codigo_materia) ?>">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Tema del video *</label>
            <input type="text" name="tema_video" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de video *</label>
            <select name="tipo_video" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
              <?php if ($tipos_video): foreach ($tipos_video as $tv): ?>
                <option value="<?= htmlspecialchars($tv) ?>"><?= htmlspecialchars($tv) ?></option>
              <?php endforeach; else: ?>
                <option value="Bienvenida">Bienvenida</option>
                <option value="Introductorio">Introductorio</option>
                <option value="Instrucciones">Instrucciones</option>
                <option value="Clase">Clase</option>
                <option value="Desarrollo de ejercicio">Desarrollo de ejercicio</option>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Escenario *</label>
            <select name="escenario" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
              <?php foreach ($escenarios as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($e['sala']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Escuela *</label>
            <select name="escuela" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
              <option value="">Seleccione una escuela</option>
              <?php foreach ($escuelas as $es): ?>
                <option value="<?= (int)$es['id'] ?>"><?= htmlspecialchars($es['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Facultad *</label>
            <select name="facultad" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
              <option value="">Seleccione una facultad</option>
              <?php foreach ($facultades as $fa): ?>
                <option value="<?= (int)$fa['id'] ?>"><?= htmlspecialchars($fa['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Recursos: Dropdown multi-select con checkboxes -->
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Recursos a utilizar *</label>

          <div class="relative" id="recursosDropdown">
            <!-- Botón/trigger -->
            <button type="button" id="recursosBtn"
              class="w-full inline-flex items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm hover:bg-gray-50">
              <span id="recursosLabel" class="truncate text-gray-700">Selecciona uno o más recursos…</span>
              <i class="fa-solid fa-chevron-down text-gray-500"></i>
            </button>

            <!-- Panel -->
            <div id="recursosPanel"
                 class="absolute z-10 mt-1 w-full rounded-xl border border-gray-200 bg-white shadow-soft p-2 hidden max-h-56 overflow-auto">
              <?php foreach ($recursosOpciones as $i => $opt): ?>
                <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer">
                  <input type="checkbox" name="recursos[]"
                         value="<?= htmlspecialchars($opt) ?>"
                         class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                  <span class="text-sm text-gray-700"><?= htmlspecialchars($opt) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Chips seleccionados -->
          <div id="recursosChips" class="mt-2 flex flex-wrap gap-1"></div>

          <p class="text-xs text-gray-500 mt-1">
            Puedes seleccionar múltiples opciones.
          </p>
        </div>
      </section>

      <!-- Fecha y hora -->
      <section>
        <h4 class="text-sm font-semibold text-gray-600 mb-3">Fecha y hora de grabación</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Fecha *</label>
            <input type="date" name="fecha_reserva" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Hora y Sala *</label>
            <select name="bloque_sala" id="bloque_sala" required
              class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
              <option value="">Selecciona una fecha primero</option>
            </select>
            <p class="text-xs text-red-600 mt-1">* Tiempo máximo de cada video: 5 minutos</p>
          </div>
        </div>
      </section>

      <div class="pt-2">
        <button type="submit"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
          <i class="fa-solid fa-paper-plane"></i> Enviar solicitud
        </button>
      </div>

      <!-- Hidden código materia (si viene por POST o prefill JS) -->
      <input type="hidden" name="codigo_materia" value="<?= htmlspecialchars($codigo_materia) ?>">
    </form>
  </div>

  <!-- --------------- SCRIPTS --------------- -->
  <script>
    // Prefill desde openReserva(...) si se cargó dentro del dashboard
    (function prefill(){
      if (window.__prefillReserva) {
        const f = document.getElementById('formReserva');
        if (!f) return;
        const {codigo_materia, materia, seccion} = window.__prefillReserva;
        if (materia)  f.querySelector('input[name="asignatura"]').value = materia;
        if (seccion)  f.querySelector('input[name="seccion"]').value = seccion;
        if (codigo_materia) {
          const hidden = f.querySelector('input[name="codigo_materia"]') || document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'codigo_materia';
          hidden.value = codigo_materia;
          if (!hidden.parentNode) f.appendChild(hidden);
        }
      }
    })();

    // Cargar horarios disponibles al elegir fecha
    (function horarios(){
      const fecha  = document.querySelector('input[name="fecha_reserva"]');
      const select = document.getElementById('bloque_sala');

      fecha?.addEventListener('change', async () => {
        const value = fecha.value;
        select.innerHTML = `<option value="">Cargando...</option>`;
        if (!value) {
          select.innerHTML = `<option value="">Selecciona una fecha primero</option>`;
          return;
        }
        try {
          const res = await fetch(`horarios_disponibles.php?fecha=${encodeURIComponent(value)}`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
          });
          if (!res.ok) throw new Error('HTTP '+res.status);
          const data = await res.json(); // esperado: [{value,text},...]
          if (Array.isArray(data) && data.length) {
            select.innerHTML = `<option value=''>Selecciona una sala y hora</option>` +
              data.map(item => `<option value="${item.value}">${item.text}</option>`).join('');
          } else {
            select.innerHTML = `<option value=''>No hay horarios disponibles para esta fecha</option>`;
          }
        } catch (err) {
          console.error(err);
          select.innerHTML = `<option value="">Error al cargar horarios</option>`;
        }
      });
    })();

    // Dropdown multi-select de Recursos (sin librerías)
    (function recursosDropdown(){
      const root   = document.getElementById('recursosDropdown');
      const btn    = document.getElementById('recursosBtn');
      const panel  = document.getElementById('recursosPanel');
      const chipsC = document.getElementById('recursosChips');
      const label  = document.getElementById('recursosLabel');

      function selectedValues() {
        return Array.from(panel.querySelectorAll('input[type="checkbox"]:checked')).map(i => i.value);
      }
      function renderChips() {
        const vals = selectedValues();
        chipsC.innerHTML = vals.map(v => `
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs">
            ${v}
            <button type="button" class="ml-1" data-remove="${v}" aria-label="Quitar ${v}">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </span>
        `).join('');
        label.textContent = vals.length ? `${vals.length} seleccionado(s)` : 'Selecciona uno o más recursos…';
      }
      function toggle(open) {
        panel.classList.toggle('hidden', open === false ? true : panel.classList.contains('hidden') ? false : true);
      }

      // Abrir/cerrar
      btn?.addEventListener('click', (e) => {
        e.preventDefault();
        panel.classList.toggle('hidden');
      });

      // Cerrar al hacer click fuera
      document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) panel.classList.add('hidden');
      });

      // Al cambiar checks, re‑pintar chips/label
      panel?.addEventListener('change', renderChips);

      // Quitar con chip
      chipsC?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-remove]');
        if (!btn) return;
        const val = btn.getAttribute('data-remove');
        const input = panel.querySelector(`input[value="${CSS.escape(val)}"]`);
        if (input) { input.checked = false; renderChips(); }
      });

      // Inicial
      renderChips();
    })();
  </script>

<?php if (!$isAjax): ?>
  </main>
</body>
</html>
<?php endif; ?>
