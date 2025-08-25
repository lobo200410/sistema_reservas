<?php
session_start();
include("../conexion.php");
date_default_timezone_set('America/El_Salvador');

// Solo admin
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
  exit('<div class="p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">Acceso denegado.</div>');
}

// Contexto opcional (si lo llamas desde una reserva y/o con fecha ya filtrada)
$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : null;
$fecha_ctx      = $_GET['fecha'] ?? ''; // fecha de contexto para volver si no cambias la fecha en el form

$err = "";

// Guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reservation_id    = ($_POST['reservation_id'] ?? '') !== '' ? (int)$_POST['reservation_id'] : null;
  $numero_videos     = (int)($_POST['numero_videos'] ?? 0);
  $fecha_publicacion = trim($_POST['fecha_publicacion'] ?? date('Y-m-d'));
  $filmado_por       = trim($_POST['filmado_por'] ?? "");
  $editado_por       = trim($_POST['editado_por'] ?? "");
  $comentario        = trim($_POST['comentario'] ?? "");
  $firma_data        = $_POST['firma_data'] ?? "";

  // Validaciones
  if ($numero_videos <= 0)                         $err = "Ingresa el número de videos (mayor a cero).";
  elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_publicacion)) $err = "Fecha de publicación inválida.";
  elseif ($filmado_por === "")                     $err = "Ingresa el nombre de quien filmó.";
  elseif ($editado_por === "")                     $err = "Ingresa el nombre de quien editó.";
  elseif (strpos($firma_data, "data:image/") !== 0)$err = "Debes firmar en el recuadro.";

  if (!$err) {
    // Inserta según tu tabla real
    $sql = "INSERT INTO postfilms
            (reservation_id, numero_videos, fecha_publicacion, filmado_por, editado_por, comentario, firma)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssss",
      $reservation_id,
      $numero_videos,
      $fecha_publicacion,
      $filmado_por,
      $editado_por,
      $comentario,
      $firma_data
    );

    if ($stmt->execute()) {
      // Redirige al dashboard → Post con fecha_publicacion como filtro
      $redir_fecha = $fecha_publicacion ?: $fecha_ctx;
      $qs = "vista=post";
      if ($redir_fecha) $qs .= "&fecha=" . urlencode($redir_fecha);
      header("Location: dashboard.php?$qs");
      exit;
    } else {
      $err = "No se pudo guardar: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Registro de Post‑Grabación</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">
  <main class="max-w-4xl mx-auto px-4 py-6">
    <div class="mb-5">
      <h1 class="text-xl font-semibold">Registro de Post‑Grabación</h1>
      <p class="text-sm text-gray-500">Completa los campos y firma para guardar.</p>
    </div>

    <?php if ($err): ?>
      <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-700"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="rounded-2xl bg-white border border-gray-200 shadow p-5">
      <form id="formPost" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Contexto -->
        <input type="hidden" name="reservation_id" value="<?= $reservation_id !== null ? (int)$reservation_id : '' ?>">
        <input type="hidden" name="firma_data" id="firma_data">
        <input type="hidden" name="fecha_ctx" value="<?= htmlspecialchars($fecha_ctx) ?>">

        <!-- Número de videos -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Número de videos filmados</label>
          <input type="number" name="numero_videos" min="1" step="1" required
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                 placeholder="Ej. 3">
        </div>

        <!-- Fecha publicación -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Fecha de publicación</label>
          <input type="date" name="fecha_publicacion" value="<?= htmlspecialchars($fecha_ctx ?: date('Y-m-d')) ?>" required
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
        </div>

        <!-- Filmado por -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Filmado por</label>
          <input type="text" name="filmado_por" required
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                 placeholder="Nombre de la persona que filmó">
        </div>

        <!-- Editado por -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Editado por</label>
          <input type="text" name="editado_por" required
                 class="mt-1 w-full h-10 rounded-lg border-gray-300 bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                 placeholder="Nombre de la persona que editó">
        </div>

        <!-- Comentario -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Comentarios del video</label>
          <textarea name="comentario" rows="3"
                    class="mt-1 w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                    placeholder="Observaciones, incidencias, entregables, etc."></textarea>
        </div>

        <!-- Firma -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Firma del responsable</label>
          <div class="rounded-xl border border-gray-300 bg-gray-50 p-3">
            <div class="relative">
              <canvas id="lienzoFirma" class="w-full h-48 bg-white rounded-lg border border-gray-200 touch-none"></canvas>
            </div>
            <div class="mt-3 flex items-center gap-2">
              <button type="button" id="btnLimpiar"
                      class="inline-flex items-center h-10 px-4 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm">
                Limpiar firma
              </button>
              <span class="text-xs text-gray-500">Firma obligatoria.</span>
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2">
          <a href="<?php
            // Volver al dashboard → Post (si hay fecha en contexto, úsala)
            $qs = 'vista=post'; $f = $_GET['fecha'] ?? $fecha_ctx;
            if ($f) $qs .= '&fecha=' . urlencode($f);
            echo 'dashboard.php?' . $qs;
          ?>" class="inline-flex items-center h-10 px-4 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm">
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center h-10 px-4 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
            Guardar
          </button>
        </div>
      </form>
    </div>
  </main>

  <script>
  // ===== Firma en canvas =====
  (function() {
    const canvas = document.getElementById('lienzoFirma');
    const inputFirma = document.getElementById('firma_data');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const form = document.getElementById('formPost');

    const ctx = canvas.getContext('2d');
    let dibujando = false;
    let hayTrazos = false;

    function resizeCanvas() {
      const ratio = window.devicePixelRatio || 1;
      const rect = canvas.getBoundingClientRect();
      canvas.width = Math.floor(rect.width * ratio);
      canvas.height = Math.floor(rect.height * ratio);
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      dibujarGuia();
    }

    function dibujarGuia() {
      const rect = canvas.getBoundingClientRect();
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, rect.width, rect.height);
      ctx.strokeStyle = '#e5e7eb';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(12, rect.height - 20);
      ctx.lineTo(rect.width - 12, rect.height - 20);
      ctx.stroke();
    }

    function pos(e) {
      const r = canvas.getBoundingClientRect();
      const t = e.touches && e.touches[0];
      const x = (t ? t.clientX : e.clientX) - r.left;
      const y = (t ? t.clientY : e.clientY) - r.top;
      return {x, y};
    }

    function start(e) { e.preventDefault(); dibujando = true; hayTrazos = true;
      const {x,y} = pos(e); ctx.beginPath(); ctx.moveTo(x,y); }
    function move(e) { if(!dibujando) return; e.preventDefault();
      const {x,y} = pos(e); ctx.lineTo(x,y); ctx.lineCap='round'; ctx.lineJoin='round'; ctx.lineWidth=2; ctx.strokeStyle='#111827'; ctx.stroke(); }
    function end() { dibujando = false; }
    function limpiar() {
      const rect = canvas.getBoundingClientRect();
      ctx.clearRect(0,0,rect.width,rect.height); dibujarGuia(); hayTrazos=false; inputFirma.value='';
    }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseleave', end);
    canvas.addEventListener('touchstart', start, {passive:false});
    canvas.addEventListener('touchmove', move, {passive:false});
    canvas.addEventListener('touchend', end);
    btnLimpiar.addEventListener('click', (e)=>{ e.preventDefault(); limpiar(); });

    new ResizeObserver(resizeCanvas).observe(canvas);

    form.addEventListener('submit', (e) => {
      if (!hayTrazos) { e.preventDefault(); alert("Debes firmar en el recuadro."); return; }
      // Exportar firma a PNG a tamaño visual
      const rect = canvas.getBoundingClientRect();
      const tmp = document.createElement('canvas'); tmp.width = rect.width; tmp.height = rect.height;
      const tctx = tmp.getContext('2d'); tctx.drawImage(canvas, 0, 0, rect.width, rect.height);
      inputFirma.value = tmp.toDataURL('image/png');
    });

    resizeCanvas();
  })();
  </script>
</body>
</html>
