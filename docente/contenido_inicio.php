<?php
session_start();
include("../conexion.php");


$usuario    = $_SESSION["nombre_usuario"] ?? "";
$mesActual  = (int)date("n"); 
$anioActual = (int)date("Y");


$totalMes   = 0;
$pendientes = 0;
$aprobadas  = 0;


if ($stmt = $conn->prepare("
  SELECT COUNT(*) AS total
  FROM reservations
  WHERE MONTH(fecha_reserva) = ? AND YEAR(fecha_reserva) = ? AND nombre_usuario = ?
")) {
  $stmt->bind_param("iis", $mesActual, $anioActual, $usuario);
  if ($stmt->execute()) {
    $stmt->bind_result($totalMes);
    $stmt->fetch();
  }
  $stmt->close();
}


if ($stmt = $conn->prepare("
  SELECT COUNT(*) AS total
  FROM reservations
  WHERE estado = 'pendiente' AND nombre_usuario = ?
")) {
  $stmt->bind_param("s", $usuario);
  if ($stmt->execute()) {
    $stmt->bind_result($pendientes);
    $stmt->fetch();
  }
  $stmt->close();
}


if ($stmt = $conn->prepare("
  SELECT COUNT(*) AS total
  FROM reservations
  WHERE estado = 'aprobada' AND nombre_usuario = ?
")) {
  $stmt->bind_param("s", $usuario);
  if ($stmt->execute()) {
    $stmt->bind_result($aprobadas);
    $stmt->fetch();
  }
  $stmt->close();
}
?>


<div class="space-y-8">
  <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <!-- Reservas este mes -->
    <article class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
      <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 grid place-content-center">
        <i class="fa-solid fa-calendar-days"></i>
      </div>
      <div class="flex-1">
        <h3 class="text-slate-600 text-sm font-medium">Reservas este mes</h3>
        <p class="text-3xl font-bold text-blue-600 leading-tight">
          <?php echo (int)$totalMes; ?>
        </p>
      </div>
    </article>

    
    <article class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
      <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 grid place-content-center">
        <i class="fa-regular fa-clock"></i>
      </div>
      <div class="flex-1">
        <h3 class="text-slate-600 text-sm font-medium">Pendientes</h3>
        <p class="text-3xl font-bold text-amber-600 leading-tight">
          <?php echo (int)$pendientes; ?>
        </p>
      </div>
    </article>


    <article class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
      <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 grid place-content-center">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div class="flex-1">
        <h3 class="text-slate-600 text-sm font-medium">Aprobadas</h3>
        <p class="text-3xl font-bold text-emerald-600 leading-tight">
          <?php echo (int)$aprobadas; ?>
        </p>
      </div>
    </article>

  </section>

  
  <h3 class="text-lg md:text-xl font-semibold text-slate-800">Conoce nuestras salas de grabación</h3>


  <section class="relative bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <!-- Pista -->
    <div id="salasTrack" class="flex transition-transform duration-700 ease-out">
      <img class="w-full flex-shrink-0 h-48 sm:h-64 lg:h-80 object-cover"
           src="img/2.jpeg" alt="Sala 1 - Pantalla interactiva">
      <img clas
