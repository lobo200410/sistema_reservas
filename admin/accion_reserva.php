<?php
include("../conexion.php");
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

$reserva_id = isset($_POST["reserva_id"]) ? (int)$_POST["reserva_id"] : 0;
$accion     = $_POST["accion"] ?? "";

if (!$reserva_id || ! in_array($accion, ["aceptar", "rechazar"], true)) {
    echo json_encode(["success" => false, "error" => "Datos inválidos"]);
    exit;
}


$nuevo_estado = $accion === "aceptar" ? "aprobada" : "rechazada";

$sql  = "UPDATE reservations SET estado = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nuevo_estado, $reserva_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode([
      "success" => false,
      "error"   => $stmt->error ?: "Error al actualizar"
    ]);
}
exit;
?>
