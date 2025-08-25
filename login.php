<?php
session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST["nombre_usuario"];
    $clave = $_POST["clave"];

    $sql = "SELECT u.id, u.nombre, u.apellido, u.nombre_usuario, r.nombre AS rol
            FROM users u
            JOIN role r ON u.role_id = r.id
            WHERE u.nombre_usuario = ? AND u.psw = SHA2(?, 256)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nombre_usuario, $clave);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {
        // Seguridad extra recomendada:
        session_regenerate_id(true);

        $usuario = $resultado->fetch_assoc();
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"] . " " . $usuario["apellido"];
        $_SESSION["nombre_usuario"] = $usuario["nombre_usuario"];
        $_SESSION["rol"] = $usuario["rol"];

        if ($usuario["rol"] === "admin") {
            header("Location: admin/dashboard.php");
        } elseif ($usuario["rol"] === "docente") {
            header("Location: docente/dashboard.php");
        } else {
            die("Rol no reconocido.");
        }
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Ingreso - Sistema de Grabaciones</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Tailwind CSS (Play CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Opcional: personalizar colores
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              600: '#6A0026',
              700: '#43001A'
            }
          }
        }
      }
    }
  </script>

  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="shortcut icon" href="1.png" type="image/x-icon" />
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-600 to-brand-700 text-white flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <!-- Card -->
    <div class="relative bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl shadow-xl p-8">
      <div class="w-12 h-12 mx-auto mb-4 grid place-items-center rounded-xl bg-white/15">
        <i class="fas fa-video text-xl"></i>
      </div>
      <h2 class="text-center text-2xl font-semibold mb-6">Ingreso al Sistema de Grabaciones</h2>

      <!-- Mensaje de error -->
      <?php if (isset($error)): ?>
        <div class="mb-5 rounded-lg border border-red-400/40 bg-red-500/10 text-red-100 px-4 py-3 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Formulario -->
      <form method="POST" class="space-y-4">
        <div>
          <label for="nombre_usuario" class="block text-sm font-medium mb-1">
            <i class="fas fa-user mr-1"></i> Usuario
          </label>
          <input
            type="text"
            id="nombre_usuario"
            name="nombre_usuario"
            required
            class="block w-full rounded-xl border-0 bg-white/90 text-gray-900 placeholder-gray-500 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-white/60 focus:ring-offset-2 focus:ring-offset-brand-700"
            placeholder="nombre.apellido"
          />
        </div>

        <div>
          <label for="clave" class="block text-sm font-medium mb-1">
            <i class="fas fa-lock mr-1"></i> Contraseña
          </label>
          <input
            type="password"
            id="clave"
            name="clave"
            required
            class="block w-full rounded-xl border-0 bg-white/90 text-gray-900 placeholder-gray-500 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-white/60 focus:ring-offset-2 focus:ring-offset-brand-700"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-white text-brand-700 font-semibold py-3 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-white/60 focus:ring-offset-2 focus:ring-offset-brand-700"
        >
          <i class="fas fa-sign-in-alt mr-2"></i> Ingresar
        </button>
      </form>

      
      <p class="mt-6 text-center text-xs text-white/70">
        © <?= date('Y'); ?> Sistema de Grabaciones · UTEC
      </p>
    </div>


    
  </div>

</body>
</html>
