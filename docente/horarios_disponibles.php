<?php
include("../conexion.php");
header('Content-Type: application/json; charset=utf-8');

$fecha = $_GET['fecha'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
  echo json_encode(['error' => 'Fecha no válida'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* 3.1 Leer horarios activos */
$horarios = [];
$q = $conn->query("SELECT etiqueta, inicio, fin FROM horarios WHERE activo=1 ORDER BY orden, inicio");
if ($q && $q->num_rows) {
  while ($r = $q->fetch_assoc()) {
    $etiqueta = $r['etiqueta'];
    if (!$etiqueta || strpos($etiqueta, '-') === false) {
      $etiqueta = substr($r['inicio'],0,5) . '-' . substr($r['fin'],0,5);
    }
    $horarios[] = $etiqueta;
  }
  $q->free();
} else {
  // fallback opcional
  $horarios = ["08:00-08:50","09:00-09:50","10:00-10:50","11:00-11:50","15:00-15:50","16:00-16:50"];
}

/* 3.2 Salas */
$salas = $conn->query("SELECT id, nombre FROM salas");
if (!$salas || !$salas->num_rows) {
  echo json_encode([], JSON_UNESCAPED_UNICODE); exit;
}

/* 3.3 Pre-check global (sin importar sala) */
$horasOcupadas = [];
$chk = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE fecha_reserva=? AND hora_reserva=?");
if (!$chk) { echo json_encode(['error'=>'No se pudo preparar la consulta'], JSON_UNESCAPED_UNICODE); exit; }

foreach ($horarios as $h) {
  $chk->bind_param("ss", $fecha, $h);
  $chk->execute();
  $chk->store_result();
  $chk->bind_result($c);
  $chk->fetch();
  $chk->free_result();
  if ((int)$c > 0) $horasOcupadas[$h] = true;
}
$chk->close();

/* 3.4 Construir opciones solo de horas libres */
$disponibles = [];
$salas->data_seek(0);
while ($s = $salas->fetch_assoc()) {
  foreach ($horarios as $hora) {
    if (isset($horasOcupadas[$hora])) continue;
    $disponibles[] = [
      "value" => "{$hora}|{$s['id']}",
      "text"  => "{$hora} - {$s['nombre']}"
    ];
  }
}
$salas->free();

echo json_encode($disponibles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
