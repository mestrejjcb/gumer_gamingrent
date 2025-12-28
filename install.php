<?php
require_once __DIR__ . '/app/bootstrap.php';

$msg = null; $err = null;

try {
  $pdo = db();
  // Crear tabla usuarios si no existe (por seguridad)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
      id_usuario INT AUTO_INCREMENT PRIMARY KEY,
      usuario VARCHAR(60) NOT NULL UNIQUE,
      nombre VARCHAR(120) NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      rol ENUM('GERENCIA','MOSTRADOR','TECNICO') NOT NULL DEFAULT 'MOSTRADOR',
      activo TINYINT(1) NOT NULL DEFAULT 1,
      fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
  ");

  // Si hay POST, crear admin
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? 'admin');
    $nombre = trim($_POST['nombre'] ?? 'Administrador');
    $pass = (string)($_POST['pass'] ?? '');
    $rol = $_POST['rol'] ?? 'GERENCIA';

    if ($pass === '') throw new RuntimeException("La contraseña no puede estar vacía.");

    $st = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE usuario=? LIMIT 1");
    $st->execute([$usuario]);
    if ($st->fetch()) {
      $msg = "El usuario '{$usuario}' ya existe. Puedes ir al login.";
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $ins = $pdo->prepare("INSERT INTO usuarios(usuario,nombre,password_hash,rol,activo) VALUES(?,?,?,?,1)");
      $ins->execute([$usuario,$nombre,$hash,$rol]);
      $msg = "Usuario creado correctamente: {$usuario}. Ya puedes iniciar sesión.";
    }
  }
} catch (Throwable $e) {
  $err = $e->getMessage();
}

?><!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Instalación · GamingRent</title>
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
    .shell{min-height:100vh;display:grid;place-items:center;padding:18px;}
    .cardx{max-width:720px;width:100%;}
  </style>
</head>
<body>
  <div class="shell">
    <div class="card-soft p-4 cardx">
      <div class="d-flex align-items-center gap-3">
        <div class="brand-mark"></div>
        <div>
          <div class="fw-bold" style="font-size:18px;">Instalación V3</div>
          <div class="text-secondary small">Crea el usuario administrador en la tabla <code>usuarios</code></div>
        </div>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-warning mt-3"><b>Error:</b> <?= e($err) ?></div>
      <?php endif; ?>
      <?php if ($msg): ?>
        <div class="alert alert-success mt-3"><?= e($msg) ?></div>
        <div class="d-grid gap-2">
          <a class="btn btn-primary" href="login.php">Ir al login</a>
          <a class="btn btn-outline-secondary" href="index.php">Ir al dashboard</a>
        </div>
      <?php else: ?>
        <div class="alert alert-light border mt-3">
          <b>Requisito:</b> debe existir <code>app/config.php</code> con los datos de tu MySQL (XAMPP).
          Si no conecta, usa primero <a href="setup.php">setup.php</a>.
        </div>

        <form method="post" class="mt-3">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Usuario</label>
              <input class="form-control" name="usuario" value="admin" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Nombre</label>
              <input class="form-control" name="nombre" value="Administrador" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Rol</label>
              <select class="form-select" name="rol">
                <option value="GERENCIA">GERENCIA</option>
                <option value="MOSTRADOR">MOSTRADOR</option>
                <option value="TECNICO">TECNICO</option>
              </select>
            </div>
          </div>
          <div class="mt-2">
            <label class="form-label">Contraseña</label>
            <input class="form-control" type="password" name="pass" placeholder="Ej: admin123" required>
          </div>
          <button class="btn btn-primary w-100 mt-3">Crear usuario</button>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <button class="btn btn-sm btn-outline-secondary" id="themeToggle" type="button">Modo oscuro</button>
            <a class="btn btn-sm btn-outline-secondary" href="login.php">Volver</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
