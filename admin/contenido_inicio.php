<?php
// admin/inicio_moodboard.php
session_start();
require_once("../conexion.php"); // Debe crear $conn = new mysqli(...)

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
  exit('<div class="p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">Acceso denegado.</div>');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ------------- Helpers -------------
function cols(mysqli $conn, $table) {
  $c=[]; $res=$conn->query("SHOW COLUMNS FROM `$table`");
  while($r=$res->fetch_assoc()) $c[]=$r['Field'];
  return $c;
}
function pickCol(array $candidates, array $cols) {
  foreach ($candidates as $x) if (in_array($x, $cols)) return $x;
  return null;
}
function safeInt($v){ return is_null($v)?0:(int)$v; }
function fmtPct($num, $den){
  if ($den<=0) return "0%";
  $p = round(($num/$den)*100);
  return $p . "%";
}
function firstOr($arr,$def){ return $arr ? $arr[0] : $def; }

// ------------- Descubrir columnas en reservations -------------
$cols = cols($conn, "reservations");
$colFecha   = pickCol(['fecha','fecha_reserva','f_reserva','created_at'], $cols);
$colSala    = pickCol(['sala_id','sala','escenario_id','escenario'], $cols);
$colHora    = pickCol(['hora_inicio','hora','bloque_sala','hora_reserva'], $cols);
$colEstado  = pickCol(['estado','status','aprobacion','confirmado'], $cols);
$colDocente = pickCol(['docente','profesor','nombre_docente','teacher','teacher_name','usuario','nombre_usuario'], $cols);

// ------------- Fechas clave -------------
date_default_timezone_set('America/El_Salvador');
$hoy     = date('Y-m-d');
$iniSem  = date('Y-m-d', strtotime('monday this week'));
$finSem  = date('Y-m-d', strtotime('sunday this week'));
$hace30  = date('Y-m-d', strtotime('-30 days'));

// ------------- Métricas -------------
$tiles = [];

// A) “🎬 Hoy se graban X videos en Sala Y”
if ($colFecha && $colSala) {
  $sql = "
    SELECT `$colSala` sala, COUNT(*) n
    FROM reservations
    WHERE DATE(`$colFecha`) = ?
    GROUP BY `$colSala`
    ORDER BY n DESC
    LIMIT 1";
  $st = $conn->prepare($sql);
  $st->bind_param('s', $hoy);
  $st->execute();
  $top = $st->get_result()->fetch_assoc();
  if ($top) {
    $sala = is_numeric($top['sala']) ? ("Sala ".(int)$top['sala']) : ("Sala ".$top['sala']);
    $n    = (int)$top['n'];
    $tiles[] = [
      'emoji'=>'🎬',
      'title'=>"Hoy se graban $n video".($n===1?'':'s'),
      'desc'=>"$sala tiene la mayor actividad hoy.",
      'tone'=>'primary'
    ];
  }
}

// B) “✅ % confirmadas esta semana”
if ($colFecha && $colEstado) {
  // Consideramos confirmadas si estado contiene: aprobado/aceptado/confirmado
  $likeOk = ['aprob','acept','confirm'];
  $totalSem = 0; $okSem = 0;

  // total semana
  $sqlT = "SELECT COUNT(*) c FROM reservations WHERE DATE(`$colFecha`) BETWEEN ? AND ?";
  $stT  = $conn->prepare($sqlT); $stT->bind_param('ss', $iniSem, $finSem); $stT->execute();
  $totalSem = safeInt($stT->get_result()->fetch_assoc()['c']);

  // confirmadas semana (usando LIKEs)
  $okCond = [];
  foreach ($likeOk as $k) $okCond[] = "`$colEstado` LIKE '%$k%'";
  $okWhere = implode(' OR ', $okCond);
  $sqlOk = "
    SELECT COUNT(*) c
    FROM reservations
    WHERE DATE(`$colFecha`) BETWEEN ? AND ?
      AND ($okWhere)";
  $stOk = $conn->prepare($sqlOk); $stOk->bind_param('ss', $iniSem, $finSem); $stOk->execute();
  $okSem = safeInt($stOk->get_result()->fetch_assoc()['c']);

  $tiles[] = [
    'emoji'=>'✅',
    'title'=> fmtPct($okSem, max($totalSem,1))." confirmadas esta semana",
    'desc'=> "$okSem de $totalSem reservas entre $iniSem y $finSem.",
    'tone'=>'success'
  ];
}

// C) “📊 Docente más activo”
if ($colDocente && $colFecha) {
  $sqlTopDoc = "
    SELECT `$colDocente` docente, COUNT(*) n
    FROM reservations
    WHERE DATE(`$colFecha`) >= ?
    GROUP BY `$colDocente`
    HAVING docente IS NOT NULL AND docente <> ''
    ORDER BY n DESC
    LIMIT 1";
  $stD = $conn->prepare($sqlTopDoc);
  $stD->bind_param('s', $hace30);
  $stD->execute();
  $doc = $stD->get_result()->fetch_assoc();
  if ($doc) {
    $tiles[] = [
      'emoji'=>'📊',
      'title'=>"Profesor más activo: ".htmlspecialchars($doc['docente']),
      'desc'=> $doc['n']." reservas en los últimos 30 días.",
      'tone'=>'neutral'
    ];
  }
}

// D) “🕒 Próximo bloque comienza a las HH:MM” (si tienes hora)
if ($colHora && $colFecha) {
  // Busca la próxima reserva de HOY en el futuro (si hora es TEXTO se extrae HH:MM; si es TIME, usamos TIME())
  $proxima = null;
  // Caso TIME real
  if (in_array($colHora, ['hora_inicio','hora','hora_reserva'])) {
    $sqlNext = "
      SELECT TIME(`$colHora`) h
      FROM reservations
      WHERE DATE(`$colFecha`) = ?
        AND TIME(`$colHora`) > TIME(NOW())
      ORDER BY TIME(`$colHora`) ASC
      LIMIT 1";
    $stN = $conn->prepare($sqlNext);
    $stN->bind_param('s', $hoy);
    $stN->execute();
    $rN = $stN->get_result()->fetch_assoc();
    if ($rN && $rN['h']) $proxima = substr($rN['h'],0,5);
  } else {
    // TEXTO: intenta extraer HH:MM con regex en PHP
    $sqlTxt = "SELECT `$colHora` h FROM reservations WHERE DATE(`$colFecha`) = ?";
    $stTx = $conn->prepare($sqlTxt); $stTx->bind_param('s',$hoy); $stTx->execute();
    $res = $stTx->get_result();
    $candidatas = [];
    while($row=$res->fetch_assoc()){
      if (preg_match('/\b([01]\d|2[0-3]):[0-5]\d\b/', (string)$row['h'], $m)){
        $candidatas[]=$m[0];
      }
    }
    sort($candidatas);
    foreach($candidatas as $hhmm){
      if ($hhmm > date('H:i')) { $proxima=$hhmm; break; }
    }
  }
  if ($proxima){
    $tiles[] = [
      'emoji'=>'🕒',
      'title'=>"Próximo bloque: $proxima",
      'desc'=>"Prepárate para la siguiente grabación de hoy.",
      'tone'=>'info'
    ];
  }
}

// E) “🏆 Sala más usada del mes”
if ($colFecha && $colSala) {
  $inicioMes = date('Y-m-01');
  $sqlSalaMes = "
    SELECT `$colSala` sala, COUNT(*) n
    FROM reservations
    WHERE DATE(`$colFecha`) >= ?
    GROUP BY `$colSala`
    ORDER BY n DESC
    LIMIT 1";
  $stSM = $conn->prepare($sqlSalaMes);
  $stSM->bind_param('s', $inicioMes);
  $stSM->execute();
  $rSM = $stSM->get_result()->fetch_assoc();
  if ($rSM) {
    $salaTxt = is_numeric($rSM['sala']) ? ("Sala ".(int)$rSM['sala']) : ("Sala ".$rSM['sala']);
    $tiles[] = [
      'emoji'=>'🏆',
      'title'=>"$salaTxt es la más usada del mes",
      'desc'=>$rSM['n']." reservas desde ".date('d/m', strtotime($inicioMes)).".",
      'tone'=>'warning'
    ];
  }
}

// F) “⚠️ Aviso de mantenimiento” (si tienes tabla avisos)
$haveAviso = false;
try {
  // Si existe una tabla 'avisos' con (titulo, cuerpo, tipo, fecha_evento)
  $colsAvis = cols($conn, "avisos"); // si no existe lanzará excepción
  $cTipo = pickCol(['tipo','categoria'], $colsAvis) ?: 'tipo';
  $cTit  = pickCol(['titulo','title'], $colsAvis) ?: 'titulo';
  $cCuer = pickCol(['cuerpo','descripcion','detalle','body'], $colsAvis) ?: 'cuerpo';
  $cF    = pickCol(['fecha_evento','fecha','f_evento','starts_at'], $colsAvis) ?: 'fecha_evento';

  $stAv = $conn->prepare("
    SELECT $cTit t, $cCuer d, $cF f
    FROM avisos
    WHERE $cTipo LIKE '%manten%'
      AND ($cF IS NULL OR DATE($cF) >= ?)
    ORDER BY COALESCE($cF, NOW()) ASC
    LIMIT 1
  ");
  $stAv->bind_param('s',$hoy);
  $stAv->execute();
  $av = $stAv->get_result()->fetch_assoc();
  if ($av) {
    $haveAviso = true;
    $fechaTxt = $av['f'] ? date('d/m', strtotime($av['f'])) : 'próximos días';
    $tiles[] = [
      'emoji'=>'⚠️',
      'title'=> $av['t'],
      'desc'=> "Programado para $fechaTxt. ".$av['d'],
      'tone'=>'danger'
    ];
  }
} catch (Throwable $e) {
  // No hay tabla avisos o no accesible -> seguimos sin romper
}

// G) Si el tablero queda muy vacío, añade “tips” o datos suaves
if (count($tiles) < 5) {
  $fallbacks = [
    ['emoji'=>'💡','title'=>'Tip rápido','desc'=>'Recuerda verificar los recursos antes de confirmar la reserva.','tone'=>'neutral'],
    ['emoji'=>'🧹','title'=>'Buenas prácticas','desc'=>'Deja el set limpio para el siguiente docente.','tone'=>'neutral'],
    ['emoji'=>'🔐','title'=>'Seguridad','desc'=>'No compartas tu usuario/contraseña con terceros.','tone'=>'neutral'],
  ];
  $need = 5 - count($tiles);
  for ($i=0; $i<$need && $i<count($fallbacks); $i++) $tiles[]=$fallbacks[$i];
}

// ------------- Barajar y recortar -------------
shuffle($tiles);
$tiles = array_slice($tiles, 0, min(8, max(5, count($tiles))));

// ------------- Mapeo de tonos a estilos -------------
function toneClass($tone){
  switch($tone){
    case 'primary': return ['bg'=>'bg-rose-50','border'=>'border-rose-200','text'=>'text-rose-900','accent'=>'text-rose-700'];
    case 'success': return ['bg'=>'bg-green-50','border'=>'border-green-200','text'=>'text-green-900','accent'=>'text-green-700'];
    case 'info':    return ['bg'=>'bg-blue-50','border'=>'border-blue-200','text'=>'text-blue-900','accent'=>'text-blue-700'];
    case 'warning': return ['bg'=>'bg-amber-50','border'=>'border-amber-200','text'=>'text-amber-900','accent'=>'text-amber-700'];
    case 'danger':  return ['bg'=>'bg-red-50','border'=>'border-red-200','text'=>'text-red-900','accent'=>'text-red-700'];
    default:        return ['bg'=>'bg-gray-50','border'=>'border-gray-200','text'=>'text-gray-900','accent'=>'text-gray-600'];
  }
}

?>
<!-- Mood Board de Actividad -->
<section class="mt-6">
  <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
    <span class="inline-flex w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
    Mood Board de Actividad
  </h2>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mt-4">
    <?php foreach($tiles as $t):
      $cls = toneClass($t['tone']);
    ?>
    <article class="group relative overflow-hidden rounded-2xl border <?= $cls['border'] ?> <?= $cls['bg'] ?> p-5 shadow transition hover:shadow-lg hover:-translate-y-0.5">
      <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full opacity-20 blur-2xl bg-white"></div>
      <div class="flex items-start gap-3">
        <div class="text-2xl <?= $cls['accent'] ?>"><?= $t['emoji'] ?></div>
        <div>
          <h3 class="text-sm font-bold <?= $cls['text'] ?> leading-snug">
            <?= htmlspecialchars($t['title']) ?>
          </h3>
          <p class="text-xs mt-1 <?= $cls['accent'] ?>"><?= htmlspecialchars($t['desc']) ?></p>
        </div>
      </div>
      <div class="mt-4 flex items-center justify-between text-[11px] text-gray-500">
        <span class="inline-flex items-center gap-1 opacity-80">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a4 4 0 100 8 4 4 0 000-8zm8-2h-3.17l-1.41-1.41A2 2 0 0014.17 4h-4.34a2 2 0 00-1.41.59L7 6H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2z"/></svg>
          Actualiza para ver más
        </span>
        <span class="opacity-60"><?= date('d/m H:i') ?></span>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- (Opcional) Sutileza de animación para que aparezcan con efecto -->
<style>
  .grid > article { animation: pop .25s ease-out both; }
  .grid > article:nth-child(2){animation-delay:.05s}
  .grid > article:nth-child(3){animation-delay:.1s}
  .grid > article:nth-child(4){animation-delay:.15s}
  @keyframes pop { from { transform: translateY(6px); opacity: 0 } to { transform: translateY(0); opacity: 1 } }
</style>
