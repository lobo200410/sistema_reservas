<?php
require_once __DIR__ . '/utilidades/mail_helper.php';

// Cambia esta dirección por la que quieras probar
$destino = 'renelobos12@gmail.com';

$res = enviar_correo(
    $destino,
    'Prueba SMTP PHPMailer (Envio directo)',
    '<h2>Prueba de envío a renelobos12</h2>
     <p>Hola, este correo fue enviado desde <strong>PHPMailer</strong> usando Gmail SMTP.</p>
     <p>Si ves este mensaje, tu configuración está funcionando correctamente </p>',
    'Prueba de envío a renelobos12 - versión texto plano.'
);

// Salida simple
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba de Envío SMTP</title>
  <style>
    body { font-family: system-ui, Arial, sans-serif; margin: 2rem; }
    .ok { color: #0a7f2e; }
    .err { color: #b00020; white-space: pre-wrap; }
  </style>
</head>
<body>
  <h1>Resultado de la prueba</h1>
  <?php if ($res['ok']): ?>
    <p class="ok">✅ <strong>Éxito:</strong> <?= htmlspecialchars($res['msg'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Revisa el buzón de <code><?= htmlspecialchars($destino, ENT_QUOTES, 'UTF-8') ?></code>.</p>
  <?php else: ?>
    <p class="err">❌ <strong>Error:</strong> <?= htmlspecialchars($res['msg'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Si no funciona, activa <code>$mail->SMTPDebug = 2;</code> en <code>mail_helper.php</code> para ver el log.</p>
  <?php endif; ?>
</body>
</html>
