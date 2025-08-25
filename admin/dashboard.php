<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
  header("Location: ../login.php"); exit;
}

$usuario = $_SESSION["nombre_usuario"] ?? '';
$nombreCompleto = $_SESSION["nombre"] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Administrador</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { 50:'#F8F5F6',100:'#F0E9EC',200:'#DAC7CF',300:'#C5A6B3',400:'#9B647A',500:'#71233F',600:'#61122F',700:'#4D0E26' }
          },
          boxShadow: { soft: '0 12px 32px rgba(16,24,40,.07)' }
        }
      }
    }
  </script>

  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" href="img/1.png" type="image/x-icon">
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full md:translate-x-0 transition-transform duration-200 bg-white border-r border-gray-200 shadow-soft">
      <div class="h-full flex flex-col">
        <!-- Brand -->
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white grid place-items-center">
              <i class="fa-solid fa-clapperboard"></i>
            </div>
            <div>
              <p class="font-semibold leading-tight">Sala de Video</p>
              <p class="text-xs text-gray-500">Panel Administrador</p>
            </div>
          </div>
          <button id="closeSidebar" class="md:hidden w-10 h-10 grid place-items-center rounded-lg hover:bg-gray-100" aria-label="Cerrar menú">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 text-sm space-y-1 sidebar">
          <p class="px-3 pb-1 text-[11px] uppercase tracking-wider text-gray-400">Principal</p>

          <a href="#" onclick="event.preventDefault(); cargarContenido('inicio')"
             class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
            <span class="w-9 h-9 rounded-lg bg-gray-100 grid place-items-center text-gray-600 group-hover:bg-white group-hover:shadow">
              <i class="fa-solid fa-house"></i>
            </span>
            <span>Inicio</span>
          </a>

          <a href="#" onclick="event.preventDefault(); cargarContenido('reservas')"
             class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
            <span class="w-9 h-9 rounded-lg bg-gray-100 grid place-items-center text-gray-600 group-hover:bg-white group-hover:shadow">
              <i class="fa-solid fa-calendar-check"></i>
            </span>
            <span>Reservas</span>
          </a>

          <a href="#" onclick="event.preventDefault(); cargarContenido('post')"
             class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
            <span class="w-9 h-9 rounded-lg bg-gray-100 grid place-items-center text-gray-600 group-hover:bg-white group-hover:shadow">
              <i class="fa-solid fa-film"></i>
            </span>
            <span>PostGrabación</span>
          </a>

          <a href="#" onclick="event.preventDefault(); cargarContenido('reportes')"
             class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
            <span class="w-9 h-9 rounded-lg bg-gray-100 grid place-items-center text-gray-600 group-hover:bg-white group-hover:shadow">
              <i class="fa-solid fa-chart-column"></i>
            </span>
            <span>Reportes</span>
          </a>


          <a href="#" onclick="event.preventDefault(); cargarContenido('horas')"
             class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
            <span class="w-9 h-9 rounded-lg bg-gray-100 grid place-items-center text-gray-600 group-hover:bg-white group-hover:shadow">
              <i class="fa-solid fa-clock"></i>
            </span>
            <span>Horarios</span>
          </a>
        </nav>

        <!-- Logout -->
       
      </div>
    </aside>

    <!-- Backdrop móvil -->
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden md:hidden bg-black/40"></div>

    <!-- Main -->
    <main class="flex-1 md:ml-72 min-w-0">

      <!-- Topbar -->
      <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button id="openSidebar" class="md:hidden w-10 h-10 grid place-items-center rounded-lg hover:bg-gray-100" aria-label="Abrir menú">
              <i class="fa-solid fa-bars"></i>
            </button>
            <div class="leading-tight">
              <div class="text-xs text-gray-500">Bienvenido/a</div>
              <h1 class="text-lg font-semibold tracking-wide"><?= htmlspecialchars($nombreCompleto) ?></h1>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Buscador (opcional) -->
            <div class="hidden sm:flex items-center h-10 bg-gray-100 rounded-xl px-3">
              <i class="fa-solid fa-magnifying-glass text-gray-500"></i>
              <input type="search" placeholder="Buscar…" class="bg-transparent outline-none px-2 text-sm w-48" />
            </div>
            <!-- Notificaciones -->
            <button class="relative w-10 h-10 grid place-items-center rounded-lg hover:bg-gray-100">
              <i class="fa-regular fa-bell"></i>
              <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
            </button>
            <!-- Avatar -->
            <div class="relative">
              <button id="userBtn" class="w-10 h-10 rounded-full bg-brand-600 text-white grid place-items-center">
                <span class="text-sm font-semibold"><?= strtoupper(substr($usuario,0,1)) ?></span>
              </button>
              <div id="userMenu" class="hidden absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-soft p-2">
                <div class="px-3 py-2">
                  <p class="text-sm font-semibold"><?= htmlspecialchars($usuario) ?></p>
                  <p class="text-xs text-gray-500">Administrador</p>
                </div>
                <a href="#" onclick="event.preventDefault(); cargarContenido('inicio')" class="block px-3 py-2 rounded-lg hover:bg-gray-100 text-sm">
                  <i class="fa-solid fa-user mr-2"></i> Perfil
                </a>
                <a href="../cerrar_sesion.php" class="block px-3 py-2 rounded-lg hover:bg-gray-100 text-sm text-red-600">
                  <i class="fa-solid fa-right-from-bracket mr-2"></i> Cerrar sesión
                </a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Breadcrumb + acciones rápidas -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <nav class="text-sm text-gray-500">
            <span class="text-gray-600"><i class="fa-solid fa-house mr-1"></i>Inicio</span>
            <span class="mx-2">/</span>
            <span id="crumb" class="text-gray-400">Panel</span>
          </nav>
         
        </div>
      </section>

      <!-- Contenido dinámico -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div id="contenido-principal" class="rounded-2xl bg-white border border-gray-200 shadow-soft p-5 min-h-[260px]">
          <div class="flex items-center gap-2 text-sm text-gray-500">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor"></path>
            </svg>
            Cargando…
          </div>
        </div>

        <div class="py-6 text-center text-xs text-gray-400">
          © <?= date('Y'); ?> Sala de Video — Administración
        </div>
      </section>
    </main>
  </div>

  <!-- Tu JS de navegación (reutiliza el mismo app.js mejorado) -->
  <script src="js/app.js"></script>

  <!-- Interacciones mínimas del layout -->
  <script>
    // Drawer
    const sidebar  = document.getElementById('sidebar');
    const openBtn  = document.getElementById('openSidebar');
    const closeBtn = document.getElementById('closeSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar(){ sidebar.classList.remove('-translate-x-full'); backdrop?.classList.remove('hidden'); }
    function closeSidebar(){ sidebar.classList.add('-translate-x-full');   backdrop?.classList.add('hidden'); }

    openBtn?.addEventListener('click', (e)=>{ e.preventDefault(); openSidebar(); });
    closeBtn?.addEventListener('click', (e)=>{ e.preventDefault(); closeSidebar(); });
    backdrop?.addEventListener('click', closeSidebar);
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) { sidebar.classList.remove('-translate-x-full'); backdrop?.classList.add('hidden'); }
      else { sidebar.classList.add('-translate-x-full'); }
    });

    // Menú usuario
    const userBtn  = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    userBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      userMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
      if (!userMenu?.contains(e.target) && e.target !== userBtn) userMenu?.classList.add('hidden');
    });

    // Breadcrumb simple según vista cargada
    window.cargarContenido = (function(orig){
      return function(v){
        const map = {
          inicio:'Inicio',
          reservas:'Reservas',
          post:'PostGrabación',
          reportes:'Reportes',
          gestion:'Gestión de usuarios',
          horas:'Horarios'
        };
        const crumb = document.getElementById('crumb');
        if (crumb && map[v]) crumb.textContent = map[v];
        (orig || function(){})(v);
      }
    })(window.cargarContenido);
  </script>

 

</body>
</html>
