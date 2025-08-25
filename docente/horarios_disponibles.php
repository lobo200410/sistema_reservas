<?php
include("../conexion.php");
header('Content-Type: application/json; charset=utf-8');

$fecha = $_GET['fecha'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
  echo json_encode(['error' => 'Fecha no válida']);
  exit;
}

$horarios = [
  "08:00-08:50",
  "09:00-09:50",
  "10:00-10:50",
  "11:00-11:50",
  "15:00-15:50",
  "16:00-16:50"
];

// Salas (las seguimos usando para armar el texto, pero OJO: por hora sólo se listarán si nadie reservó esa hora)
$salas = $conn->query("SELECT id, nombre FROM salas");

// PRE-CHECK: ¿qué horas ya tienen alguna reserva ese día (independiente de sala)?
$horasOcupadas = [];
$chkHora = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE fecha_reserva=? AND hora_reserva=?");

foreach ($horarios as $h) {
  $chkHora->bind_param("ss", $fecha, $h);
  $chkHora->execute();
  $chkHora->bind_result($c);
  $chkHora->fetch();
  if ((int)$c > 0) $horasOcupadas[$h] = true;
}
$chkHora->close();

// Construir opciones SOLO para horas libres globalmente
$disponibles = [];
$salas->data_seek(0);
while ($sala = $salas->fetch_assoc()) {
  foreach ($horarios as $hora) {
    if (isset($horasOcupadas[$hora])) {
      // Esta hora ya está tomada por alguna sala -> NO ofrecerla en ninguna sala
      continue;
    }
    // Hora libre globalmente -> se puede usar (mostramos todas las salas para que elijan)
    $disponibles[] = [
      "value" => "{$hora}|{$sala['id']}",
      "text"  => "{$hora} - {$sala['nombre']}"
    ];
  }
}

echo json_encode($disponibles);
