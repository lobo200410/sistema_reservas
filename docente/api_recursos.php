<?php

if (session_status() === PHP_SESSION_NONE) session_start();
include("../conexion.php");
header('Content-Type: application/json; charset=utf-8');


$rol = strtolower($_SESSION['rol'] ?? '');
if (!$rol) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

try {
  $sql = "SELECT id, nombre FROM recursos ORDER BY nombre ASC";
  $rs  = $conn->query($sql);
  $out = [];
  while ($r = $rs->fetch_assoc()) {
    $out[] = ['id'=>(int)$r['id'], 'nombre'=>$r['nombre']];
  }
  echo json_encode($out);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Error al consultar recursos']);
}
