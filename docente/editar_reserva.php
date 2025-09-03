<?php
// editar_reserva.php (adaptado a horarios value: "HH:MM-HH:MM|salaId")
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");
date_default_timezone_set('America/El_Salvador');

// --- Auth / contexto ---
$rol      = strtolower($_SESSION['rol'] ?? '');
$usuario  = $_SESSION['nombre_usuario'] ?? '';
$id       = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$back     = $_GET['back']  ?? ($_POST['back']  ?? 'docente');   // 'docente' | 'dashboard'
$vista    = $_GET['vista'] ?? ($_POST['vista'] ?? 'mis_reservas');
$fechaRet = $_GET['fecha'] ?? ($_POST['fecha'] ?? '');

if (!$rol) { header("Location: ../login.php"); exit; }
if (!$id)  { http_response_code(400); exit("Falta ID."); }

// --- Helpers ---
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
function norm_fecha($v){
  $v = trim((string)$v);
  if (!$v) return '';
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)) return $v;
  if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/',$v,$m)) return sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]);
  $ts = strtotime($v); return $ts?date('Y-m-d',$ts):'';
}
// Acepta "HH:MM" o "HH:MM-HH:MM"
function norm_slot($v){
  $v = trim((string)$v);
  if ($v === '') return '';
  if (preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $v)) return $v;
  if (preg_match('/^(\d{1,2}):(\d{2})$/', $v, $m)) return sprintf('%02d:%02d', $m[1], $m[2]);
  $ts = strtotime($v);
  return $ts ? date('H:i', $ts) : '';
}
function label_slot($v){
  $v = trim((string)$v);
  if (preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $v)) return $v;         // muestra el rango
  if (preg_match('/^(\d{1,2}):(\d{2})$/', $v, $m)) return sprintf('%02d:%02d', $m[1], $m[2]);
  return $v;
}

// --- Cargar reserva ---
$sql = "SELECT r.*,
               f.nombre AS facultad_nombre,
               e.nombre AS escuela_nombre,
               r.escenario_id
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

// --- Autorización ---
$esPropia   = (strtolower($rv['nombre_usuario'] ?? '') === strtolower($usuario));
$estadoLow  = strtolower($rv['estado'] ?? '');
$puedeEditarDocente = ($rol === 'docente' && $esPropia && $estadoLow === 'pendiente');
$puedeEditarAdmin   = ($rol === 'admin' || $rol === 'multimedia');
$puedeEditar        = $puedeEditarAdmin || $puedeEditarDocente;

// --- Recursos hardcode (igual que crear) ---
$recursosOpciones = [
  "Documentos PDF",
  "Documento de Word",
  "Libro de Excel",
  "Videos",
  "Presentación de PowerPoint",
  "Presentación de programa",
];

// Normalización de recursos actuales
$recursosActualesRaw = array_filter(array_map('trim', explode(',', (string)($rv['recursos'] ?? ''))));
$recursosActualesL   = array_map('mb_strtolower', $recursosActualesRaw);
$opcionesL           = array_map('mb_strtolower', $recursosOpciones);
$recursosExtras      = array_values(array_diff($recursosActualesL, $opcionesL)); // preserva no listados

// --- Guardar (POST) ---
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

if ($isPost) {
  if (!$puedeEditar) {
    if ($isAjax) json_out(['ok'=>false,'error'=>'No autorizado para editar esta reserva']);
    exit("No autorizado.");
  }

  // Acepta ambos nombres y también "rango|salaId"
  $fechaInput = $_POST['fecha_reserva'] ?? $_POST['fecha'] ?? '';
  $horaInput  = $_POST['hora_reserva']  ?? $_POST['bloque_sala'] ?? '';
  // si viene "HH:MM-HH:MM|123", nos quedamos con la parte izquierda
  if (strpos($horaInput, '|') !== false) $horaInput = explode('|', $horaInput, 2)[0];

  $fecha = norm_fecha($fechaInput);
  $hora  = norm_slot($horaInput);
  $tema  = trim((string)($_POST['tema_video'] ?? $_POST['tema'] ?? ''));

  if (!$fecha || !$hora || $tema === '') {
    $msg = 'Campos inválidos. Revisa fecha, hora y tema.';
    if ($isAjax) json_out(['ok'=>false,'error'=>$msg]);
    $_SESSION['flash_error'] = $msg;
    header("Location: editar_reserva.php?id=$id&back=$back&vista=$vista&fecha=".urlencode($fechaRet));
    exit;
  }

  // Recursos seleccionados + extras preservados
  $seleccionados = isset($_POST['recursos']) && is_array($_POST['recursos'])
    ? array_filter(array_map('trim', $_POST['recursos']))
    : [];
  $extrasTxt = trim((string)($_POST['recursos_extras'] ?? ''));
  $extrasArr = $extrasTxt !== '' ? array_filter(array_map('trim', explode(',', $extrasTxt))) : [];

  $all  = [];
  $seen = [];
  foreach (array_merge($seleccionados, $extrasArr) as $r) {
    $k = mb_strtolower($r);
    if (!isset($seen[$k])) { $seen[$k] = true; $all[] = $r; }
  }
  $recur = implode(', ', $all);

  // Update (docente sólo si pendiente y propia)
  if ($puedeEditarDocente) {
    $upd = $conn->prepare("UPDATE reservations
                           SET fecha_reserva=?, hora_reserva=?, tema_video=?, recursos=?
                           WHERE id=? AND estado='pendiente' AND nombre_usuario=?");
    $upd->bind_param("ssssis", $fecha, $hora, $tema, $recur, $id, $usuario);
  } else {
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

  // Respuesta
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
        'hora_reserva'  => $fila['hora_reserva'],
        'tema_video'    => $fila['tema_video'],
        'recursos'      => $fila['recursos'] ?? '',
        'estado'        => $fila['estado'],
      ]
    ]);
  }

  $_SESSION['flash_ok'] = 'Cambios guardados correctamente.';
  header("Location: ".($back==='dashboard' ? '../admin/dashboard.php' : 'dashboard.php'));
  exit;
}
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
        <input type="hidden" id="escenario_id" value="<?= (int)($rv['escenario_id'] ?? 0) ?>">
        <input type="hidden" id="reserva_id"   value="<?= (int)$rv['id'] ?>">

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

            <!-- Select que recibe value "HH:MM-HH:MM|salaId" -->
            <select name="bloque_sala" id="bloque_sala"
                    class="w-full h-11 rounded-lg border-gray-300 px-3 bg-white"
                    <?= $puedeEditar ? '' : 'disabled' ?> required>
              <?php
                $valorHoraActual = trim((string)$rv['hora_reserva']);    
                $labelHoraActual = label_slot($valorHoraActual);
              ?>
              <option value="<?= htmlspecialchars($valorHoraActual) ?>">
                <?= "Actual · ".htmlspecialchars($labelHoraActual) ?>
              </option>
            </select>

           
           <input type="hidden" name="hora_reserva" id="hora_hidden"
                   value="<?= htmlspecialchars($valorHoraActual) ?>">

            <p class="text-xs text-gray-500 mt-1">* Se cargan opciones disponibles según la fecha elegida.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Tema del video</label>
            <input type="text" name="tema_video" maxlength="200"
                   class="w-full h-11 rounded-lg border-gray-300 px-3"
                   value="<?= htmlspecialchars($rv['tema_video']) ?>"
                   <?= $puedeEditar ? '' : 'disabled' ?> required>
          </div>

       
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 mb-2">Recursos</label>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
              <?php foreach ($recursosOpciones as $opt):
                $checked = in_array(mb_strtolower($opt), $recursosActualesL, true) ? 'checked' : '';
              ?>
                <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 hover:bg-gray-50">
                  <input type="checkbox" name="recursos[]" value="<?= htmlspecialchars($opt) ?>"
                         class="h-4 w-4 rounded border-gray-300"
                         <?= $puedeEditar ? $checked : 'disabled' ?>>
                  <span class="text-sm text-gray-700"><?= htmlspecialchars($opt) ?></span>
                </label>
              <?php endforeach; ?>
            </div>

            <?php if (!empty($recursosExtras)): ?>
              <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1 inline-block">
                * Esta reserva incluye otros recursos no listados:
                <strong><?= htmlspecialchars(implode(', ', $recursosExtras)) ?></strong>. Se conservarán.
              </p>
            <?php endif; ?>

            <input type="hidden" name="recursos_extras" value="<?= htmlspecialchars(implode(', ', $recursosExtras)) ?>">
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

(function syncHora(){
  const sel  = document.getElementById('bloque_sala');
  const hid  = document.getElementById('hora_hidden');
  const form = document.getElementById('formEditar');
  if (!sel || !hid || !form) return;
  const onlyRange = (v) => String(v||'').split('|',2)[0];
  const update = () => { hid.value = onlyRange(sel.value); };
  update();
  sel.addEventListener('change', update);
  form.addEventListener('submit', update);
})();

(function horarios(){
  const fecha   = document.getElementById('fecha_reserva');
  const select  = document.getElementById('bloque_sala');
  if (!fecha || !select) return;

  const horaActualVal   = select.querySelector('option')?.value || '';
  const fechaOriginal   = fecha.value;

  async function cargar(fechaYMD){
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fechaYMD)) {
      select.innerHTML = `<option value="">Selecciona una fecha válida</option>`;
      return;
    }
    select.innerHTML = `<option value="">Cargando...</option>`;
    try {
      const url = `horarios_disponibles.php?fecha=${encodeURIComponent(fechaYMD)}`;
      const res = await fetch(url, { credentials:'same-origin', headers:{'X-Requested-With':'fetch'} });
      if (!res.ok) throw new Error('HTTP '+res.status);
      const data = await res.json(); 

      let html = '';
      
      if (fechaYMD === fechaOriginal && horaActualVal) {
        const label = horaActualVal; 
        html += `<option value="${horaActualVal}">Actual · ${label}</option>`;
      } else {
        html += `<option value="">Selecciona una sala y hora</option>`;
      }

      if (Array.isArray(data) && data.length) {
        html += data.map(o => `<option value="${o.value}">${o.text}</option>`).join('');
      } else if (data && data.error) {
        html += `<option value="">${data.error}</option>`;
      } else {
        html += `<option value="">No hay horarios disponibles para esta fecha</option>`;
      }

      select.innerHTML = html;

      
      const firstVal = select.value || '';
      document.getElementById('hora_hidden').value = String(firstVal).split('|',2)[0] || '';
    } catch (err) {
      console.error('Error horarios:', err);
      select.innerHTML = `<option value="">Error al cargar horarios</option>`;
    }
  }

  cargar(fecha.value);
 
  fecha.addEventListener('change', () => cargar(fecha.value));
})();
  </script>
</body>
</html>
