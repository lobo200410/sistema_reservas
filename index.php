<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

echo "<h2>Bienvenido, " . $_SESSION["nombre"] . " (" . $_SESSION["rol"] . ")</h2>";
echo "<p><a href='cerrar_sesion.php'>Cerrar sesión</a></p>";

if ($_SESSION["rol"] === "docente") {
    echo "<a href='reserva.php'>Reservar video</a>";
} elseif ($_SESSION["rol"] === "admin") {
    echo "<a href='admin_dashboard.php'>Panel de administración</a>";
}
?>
