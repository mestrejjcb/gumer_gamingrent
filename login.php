<?php
require_once __DIR__ . '/app/bootstrap.php';
if (is_logged_in()) redirect('index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = $_POST['user'] ?? '';
  $pass = $_POST['pass'] ?? '';
  try{
    if (login_attempt($user, $pass)) {
      flash_set('success', 'Bienvenido/a. Sesión iniciada correctamente.');
      redirect('index.php');
    } else {
      $error = "Usuario o contraseña incorrectos.";
    }
  } catch (Throwable $e) {
    $error = "Error: " . $e->getMessage();
  }
}

$APP_NAME = 'GamingRent · Backoffice';
try {
  $cfgPath = __DIR__ . '/app/config.php';
  if (file_exists($cfgPath)) {
    $cfg = require $cfgPath;
    $APP_NAME = $cfg['app_name'] ?? $APP_NAME;
  } else {
    $error = $error ?: "Falta app/config.php. Copia app/config.example.php a app/config.php y edita la conexión.";
  }
} catch (Throwable $e) {
  $error = $error ?: $e->getMessage();
}

?><!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login · <?= e($APP_NAME) ?></title>
  <script>
    (function(){
      const saved = localStorage.getItem('theme');
      const preferred = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-bs-theme', saved || preferred);
    })();
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css">
  <style>
    .login-shell{min-height:100vh; display:grid; place-items:center; padding:18px;}
    .login-card{max-width:440px; width:100%;}
    .login-title{font-weight:900;}
  </style>
</head>
<body>
  <div class="login-shell">
    <div class="card-soft p-4 login-card">
      <div class="d-flex align-items-center gap-3 mb-2">
        <!--  <div class="brand-mark"></div>
      <div>
          <div class="login-title">GamingRent</div>
          <div class="text-secondary small">Acceso al backoffice (V3 · funcional)</div>
        </div>-->
      </div>

      <?php if ($error): ?>
        <div class="alert alert-warning mt-3"><?= e($error) ?></div>
      <?php endif; ?>


<div class="text-center mt-2 mb-3">
  <img src="assets/brand/gamingrent_logo_full.png"
       alt="GamingRent"
       style="max-width:240px;height:auto;">
</div>


      <form method="post" class="mt-3">
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input class="form-control" name="user" autocomplete="username" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input class="form-control" type="password" name="pass" autocomplete="current-password" required>
        </div>

        <button class="btn btn-primary w-100" type="submit">Entrar</button>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <button class="btn btn-sm btn-outline-secondary" id="themeToggle" type="button">Modo oscuro</button>
          <div class="text-secondary small">Local (XAMPP)</div>
        </div>

        <div class="mt-3 small text-secondary">
          <b>Primera vez:</b> crea el usuario admin en <a href="install.php">install.php</a>.
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
