<?php
// editar_reserva.php
if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");
date_default_timezone_set('America/El_Salvador');

$rol      = strtolower($_SESSION['rol'] ?? '');
$usuario  = $_SESSION['nombre_usuario'] ?? '';
$id       = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$back     = $_GET['back']  ?? ($_POST['back']  ?? 'docente');            // 'docente' | 'dashboard'
$vista    = $_GET['vista'] ?? ($_POST['vista'] ?? 'mis_reservas');       // para volver al lugar correcto
$fechaRet = $_GET['fecha'] ?? ($_POST['fecha'] ?? '');

// --- helpers ---
function json_out($arr){ header('Content-Type: application/json'); echo json_encode($arr); exit; }
function badge($estado){
  $estado = strtolower($estado ?? '');
  $map = [
    'pendiente'  => 'bg-amber-100 text-amber-700',
    'aprobada'   => 'bg-emerald-100 text-emerald-700',
    'aprobado'   => 'bg-emerald-100 text-emerald-700',
    'rechazada'  => 'bg-rose-100 text-rose-700',
    'rechazado'  => 'bg-rose-100 text-rose-700',
    'completada' => 'bg-indigo-100 text-indigo-700',
  ];
  return $map[$estado] ?? 'bg-gray-100 text-gray-700';
}
function norm_fecha($v){ // acepta YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY
  $v = trim((string)$v);
  if (!$v) return '';
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)) return $v;
  if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/',$v,$m)) return sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]);
  $ts = strtotime($v); return $ts?date('Y-m-d',$ts):'';
}
function norm_hora($v){
  $v = trim((string)$v);
  if (preg_match('/^(\d{1,2}):(\d{2})$/',$v,$m)) return sprintf('%02d:%02d',$m[1],$m[2]);
  $ts = strtotime($v); return $ts?date('H:i',$ts):'';
}

// --- seguridad básica ---
if (!$id) { http_response_code(400); exit("Falta ID."); }
if (!$rol) { header("Location: ../login.php"); exit; }

// --- obtener reserva (incluye escenario/sala para filtrar horarios) ---
$sql = "SELECT r.*,
               f.nombre AS facultad_nombre,
               e.nombre AS escuela_nombre,
               r.escenario_id,
               (SELECT s.id FROM escenarios ee JOIN salas s ON ee.sala_id=s.id WHERE ee.id=r.escenario_id LIMIT 1) AS sala_id
        FROM reservations r
        LEFT JOIN facultades f ON r.facultad_id=f.id
        LEFT JOIN escuelas   e ON r.escuela_id=e.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$rv  = $res->fetch_assoc();
if (!$rv) { http_response_code(404); exit("Reserva no encontrada."); }

// --- autorización ---
$esPropia   = (strtolower($rv['nombre_usuario'] ?? '') === strtolower($usuario));
$estadoLow  = strtolower($rv['estado'] ?? '');
$puedeEditarDocente = ($rol === 'docente' && $esPropia && $estadoLow === 'pendiente');
$puedeEditarAdmin   = ($rol === 'admin' || $rol === 'multimedia');
$puedeEditar        = $puedeEditarAdmin || $puedeEditarDocente;

// --- guardar (POST) ---
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

if ($isPost) {
  if (!$puedeEditar) {
    if ($isAjax) json_out(['ok'=>false,'error'=>'No autorizado para editar esta reserva']);
    exit("No autorizado.");
  }

  $fecha  = norm_fecha($_POST['fecha_reserva'] ?? '');
  $hora   = norm_hora($_POST['hora_reserva'] ?? '');
  $tema   = trim((string)($_POST['tema_video'] ?? ''));
  // Si vienen recursos por IDs, convertirlos a nombres (cadena separada por coma)
  $recurIds = $_POST['recursos_ids'] ?? null; // array
  $recurTxt = trim((string)($_POST['recursos'] ?? '')); // fallback

  if (!$fecha || !$hora || $tema === '') {
    $msg = 'Campos inválidos. Revisa fecha, hora y tema.';
    if ($isAjax) json_out(['ok'=>false,'error'=>$msg]);
    $_SESSION['flash_error'] = $msg;
    header("Location: editar_reserva.php?id=$id&back=$back&vista=$vista&fecha=".urlencode($fechaRet));
    exit;
  }

  // Si vienen recursos_ids, traducirlos a nombres
  if (is_array($recurIds) && count($recurIds) > 0) {
    $ids = array_map('intval', $recurIds);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $q = $conn->prepare("SELECT nombre FROM recursos WHERE id IN ($in) ORDER BY nombre");
    $q->bind_param($types, ...$ids);
    $q->execute();
    $rs = $q->get_result();
    $nombres = [];
    while ($r = $rs->fetch_assoc()) $nombres[] = trim($r['nombre']);
    $recur = implode(', ', array_filter($nombres, fn($x)=>$x!==''));
  } else {
    // fallback: texto libre (por compatibilidad con versión anterior)
    $recur = implode(', ', array_filter(array_map('trim', explode(',', $recurTxt)), fn($x)=>$x!==''));
  }

  // Los docentes sólo pueden editar si está 'pendiente'
  if ($puedeEditarDocente) {
    $upd = $conn->prepare("UPDATE reservations
                           SET fecha_reserva=?, hora_reserva=?, tema_video=?, recursos=?
                           WHERE id=? AND estado='pendiente' AND nombre_usuario=?");
    $upd->bind_param("ssssis", $fecha, $hora, $tema, $recur, $id, $usuario);
  } else { // admin / multimedia
    $upd = $conn->prepare("UPDATE reservations
                           SET fecha_reserva=?, hora_reserva=?, tema_video=?, recursos=?
                           WHERE id=?");
    $upd->bind_param("ssssi", $fecha, $hora, $tema, $recur, $id);
  }

  if (!$upd->execute()) {
    $err = 'No se pudo guardar (db).';
    if ($isAjax) json_out(['ok'=>false,'error'=>$err]);
    $_SESSION['flash_error'] = $err;
    header("Location: editar_reserva.php?id=$id&back=$back&vista=$vista&fecha=".urlencode($fechaRet));
    exit;
  }

  // devolver fila actualizada (para AJAX y para volver a pintar)
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $nres = $stmt->get_result();
  $fila = $nres->fetch_assoc();

  if ($isAjax) {
    json_out([
      'ok'   => true,
      'fila' => [
        'id'            => (int)$fila['id'],
        'fecha_reserva' => $fila['fecha_reserva'],
        'hora_reserva'  => substr($fila['hora_reserva'],0,5),
        'tema_video'    => $fila['tema_video'],
        'recursos'      => $fila['recursos'] ?? '',
        'estado'        => $fila['estado'],
      ]
    ]);
  }

  $_SESSION['flash_ok'] = 'Cambios guardados correctamente.';
  $dest = ($back === 'dashboard') ? '../admin/dashboard.php' : 'dashboard.php';
  header("Location: $dest");
  exit;
}

// -------------------- HTML (GET) --------------------
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar reserva</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <div class="max-w-4xl mx-auto p-4 md:p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-800">
        <span class="text-indigo-600">✎</span> Editar Reserva
      </h1>
      <a href="<?= ($back==='dashboard' ? '../admin/dashboard.php' : 'dashboard.php') ?>"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-sm">
        ← Volver
      </a>
    </div>

    <?php if (!empty($_SESSION['flash_ok'])): ?>
      <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">
        <?= htmlspecialchars($_SESSION['flash_ok']); unset($_SESSION['flash_ok']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
        <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
      </div>
    <?php endif; ?>

    <div class="rounded-2xl bg-white border border-gray-200 shadow">
      <div class="p-5 border-b border-gray-100">
        <div class="flex flex-wrap items-center gap-3 text-sm">
          <span class="inline-flex items-center gap-2">
            <span class="text-gray-500">Docente:</span>
            <span class="font-medium"><?= htmlspecialchars($rv['docente_nombre']) ?></span>
          </span>
          <span class="hidden md:inline text-gray-300">•</span>
          <span class="inline-flex items-center gap-2">
            <span class="text-gray-500">Facultad:</span>
            <span class="font-medium"><?= htmlspecialchars($rv['facultad_nombre']) ?></span>
          </span>
          <span class="hidden md:inline text-gray-300">•</span>
          <span class="inline-flex items-center gap-2">
            <span class="text-gray-500">Escuela:</span>
            <span class="font-medium"><?= htmlspecialchars($rv['escuela_nombre']) ?></span>
          </span>
          <span class="hidden md:inline text-gray-300">•</span>
          <span class="inline-flex items-center gap-2">
            <span class="text-gray-500">Estado:</span>
            <span class="px-2 py-1 rounded-lg text-xs capitalize <?= badge($rv['estado']) ?>">
              <?= htmlspecialchars(strtolower($rv['estado'])) ?>
            </span>
          </span>
        </div>
      </div>

      <form id="formEditar" method="POST" class="p-5 space-y-5">
        <input type="hidden" name="id"    value="<?= (int)$rv['id'] ?>">
        <input type="hidden" name="back"  value="<?= htmlspecialchars($back) ?>">
        <input type="hidden" name="vista" value="<?= htmlspecialchars($vista) ?>">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaRet) ?>">

        <!-- Para la carga de horarios -->
        <input type="hidden" id="escenario_id" value="<?= (int)($rv['escenario_id'] ?? 0) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Fecha</label>
            <input type="date" name="fecha_reserva" id="fecha_reserva"
                   class="w-full h-11 rounded-lg border-gray-300 px-3"
                   value="<?= htmlspecialchars($rv['fecha_reserva']) ?>"
                   <?= $puedeEditar ? '' : 'disabled' ?> required>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Sala / Hora</label>
            <!-- Select controlado por AJAX (carga de horarios_disponibles.php) -->
            <select name="hora_reserva" id="bloque_sala"
                    class="w-full h-11 rounded-lg border-gray-300 px-3 bg-white"
                    <?= $puedeEditar ? '' : 'disabled' ?> required>
              <option value="<?= htmlspecialchars(substr($rv['hora_reserva'],0,5)) ?>">
                <?= "Actual · ".htmlspecialchars(substr($rv['hora_reserva'],0,5)) ?>
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">* Se cargan opciones disponibles según la fecha elegida.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Tema del video</label>
            <input type="text" name="tema_video" maxlength="200"
                   class="w-full h-11 rounded-lg border-gray-300 px-3"
                   value="<?= htmlspecialchars($rv['tema_video']) ?>"
                   <?= $puedeEditar ? '' : 'disabled' ?> required>
          </div>

          <!-- Recursos desde BD (AJAX) -->
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-2">Recursos</label>
            <div id="recursosWrap" class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
              <!-- Aquí se llenan los checkboxes vía AJAX -->
            </div>
            <!-- Fallback oculto por compatibilidad si no hay JS -->
            <textarea name="recursos" id="recursos_textarea" class="hidden"><?= htmlspecialchars($rv['recursos'] ?? '') ?></textarea>

            <!-- Lista actual para preseleccionar (separado por coma) -->
            <input type="hidden" id="recursos_actuales" value="<?= htmlspecialchars($rv['recursos'] ?? '') ?>">
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2">
          <a href="<?= ($back==='dashboard' ? '../admin/dashboard.php' : 'dashboard.php') ?>"
             class="h-11 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">Cancelar</a>
          <?php if ($puedeEditar): ?>
            <button type="submit" class="h-11 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
              Guardar cambios
            </button>
          <?php else: ?>
            <button type="button" disabled
              class="h-11 px-4 rounded-lg bg-gray-300 text-white cursor-not-allowed">No editable</button>
          <?php endif; ?>
        </div>

        <?php if ($puedeEditar): ?>
        <p id="ed_msg" class="mt-2 text-sm text-gray-600 hidden"></p>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <script>
  // === Cargar horarios disponibles al elegir fecha (AJAX)
  (function horarios(){
    const fecha   = document.getElementById('fecha_reserva');
    const select  = document.getElementById('bloque_sala');
    const escId   = document.getElementById('escenario_id')?.value || '';

    fecha?.addEventListener('change', async () => {
      const value = fecha.value;
      select.innerHTML = `<option value="">Cargando...</option>`;
      if (!value) {
        select.innerHTML = `<option value="">Selecciona una fecha primero</option>`;
        return;
      }
      try {
        const url = `horarios_disponibles.php?fecha=${encodeURIComponent(value)}${escId?`&escenario_id=${encodeURIComponent(escId)}`:''}`;
        const res = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' }});
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

  // === Cargar recursos desde la BD (AJAX) y preseleccionar los actuales ===
  (function recursos(){
    const wrap   = document.getElementById('recursosWrap');
    const actuales = (document.getElementById('recursos_actuales')?.value || '')
                      .split(',').map(x=>x.trim().toLowerCase()).filter(Boolean);

    async function load() {
      wrap.innerHTML = `
        <div class="col-span-full flex items-center gap-2 text-sm text-gray-500">
          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor"></path>
          </svg>
          Cargando recursos…
        </div>`;
      try {
        const res = await fetch('api_recursos.php', { credentials:'same-origin', headers:{'X-Requested-With':'fetch'} });
        if (!res.ok) throw new Error('HTTP '+res.status);
        const data = await res.json(); // esperado: [{id,nombre}]
        if (!Array.isArray(data) || !data.length) {
          wrap.innerHTML = `<p class="text-sm text-gray-500">No hay recursos configurados.</p>`;
          return;
        }
        wrap.innerHTML = data.map(r => {
          const checked = actuales.includes(String(r.nombre).toLowerCase()) ? 'checked' : '';
          return `
          <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 hover:bg-gray-50">
            <input type="checkbox" name="recursos_ids[]" value="${r.id}" ${checked}
                   class="h-4 w-4 rounded border-gray-300">
            <span class="text-sm text-gray-700">${r.nombre}</span>
          </label>`;
        }).join('');
      } catch (err) {
        console.error(err);
        wrap.innerHTML = `<p class="text-sm text-rose-600">Error al cargar recursos.</p>`;
      }
    }
    load();
  })();

  // === Envío normal (no AJAX) también funciona; si quieres AJAX, envía con fetch:
  (function handleSubmit(){
    const form = document.getElementById('formEditar');
    const msg  = document.getElementById('ed_msg');

    // Si prefieres usar AJAX puro, descomenta esto:
    /*
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      msg?.classList.add('hidden'); if (msg) msg.textContent = '';

      const fd = new FormData(form);
      try {
        const resp = await fetch('editar_reserva.php', {
          method: 'POST',
          headers: { 'X-Requested-With': 'fetch' },
          body: fd
        });
        const data = await resp.json();
        if (!resp.ok || !data?.ok) throw new Error(data?.error || 'No se pudo guardar');
        // Aquí podrías cerrar modal y refrescar la lista en vivo si vienes desde un modal
        msg.textContent = 'Guardado correctamente.';
        msg.classList.remove('hidden');
      } catch (err) {
        msg.textContent = err.message || 'Error inesperado';
        msg.classList.remove('hidden');
      }
    });
    */
  })();
  </script>
</body>
</html>
