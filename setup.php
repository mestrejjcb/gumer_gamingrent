<?php
require_once __DIR__ . '/app/helpers.php';

$cfgPath = __DIR__ . '/app/config.php';
if (file_exists($cfgPath)) {
  header('Location: login.php');
  exit;
}

$error = null;
$ok = null;

function write_config(array $data): void {
  $tpl = "<?php\n/**\n * Config generado por setup.php\n */\nreturn " . var_export($data, true) . ";\n";
  file_put_contents(__DIR__ . '/app/config.php', $tpl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $host = trim($_POST['host'] ?? 'localhost');
  $name = trim($_POST['name'] ?? 'alquiler_gaming');
  $port = (int)($_POST['port'] ?? 3307);
  if ($port <= 0) { $port = 3307; }
  $user = trim($_POST['user'] ?? 'root');
  $pass = (string)($_POST['pass'] ?? '');
  $charset = 'utf8mb4';

  $data = [
    'db' => [
      'host' => $host,
      'port' => $port,
      'name' => $name,
      'user' => $user,
      'pass' => $pass,
      'charset' => $charset,
    ],
    'fallback_login' => [
      'user' => 'admin',
      'pass' => 'admin123',
      'role' => 'GERENCIA',
    ],
    'app_name' => 'GamingRent · Backoffice',
    'timezone' => 'Europe/Madrid',
  ];

  try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->query("SELECT 1");
    write_config($data);
    $ok = "Conexión OK. Configuración guardada. Ya puedes entrar al login.";
  } catch (Throwable $e) {
    $error = "No se pudo conectar: " . $e->getMessage();
  }
}

?><!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup · GamingRent</title>
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
    .cardx{max-width:640px;width:100%;}
  </style>
</head>
<body>
  <div class="shell">
    <div class="card-soft p-4 cardx">
      <div class="d-flex align-items-center gap-3">
        <div class="brand-mark"></div>
        <div>
          <div class="fw-bold" style="font-size:18px;">Setup de conexión</div>
          <div class="text-secondary small">Crea <code>app/config.php</code> y prueba el acceso a MySQL</div>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-warning mt-3"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($ok): ?>
        <div class="alert alert-success mt-3"><?= e($ok) ?></div>
        <div class="d-grid">
          <a class="btn btn-primary" href="login.php">Ir a login</a>
        </div>
      <?php else: ?>
      <form method="post" class="mt-3">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Host</label>
            <input class="form-control" name="host" value="localhost" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Base de datos</label>
            <input class="form-control" name="name" value="alquiler_gaming" required>
          </div>
        </div>
        <div class="row g-2 mt-1">
          <div class="col-md-4">
            <label class="form-label">Puerto</label>
            <input class="form-control" name="port" value="3307" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Usuario</label>
            <input class="form-control" name="user" value="root" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Contraseña</label>
            <input class="form-control" name="pass" value="">
          </div>
        </div>

        <div class="alert alert-light border mt-3">
          <b>Recuerda:</b> la BD debe existir y estar importada. Si no, ejecuta <code>sql/alquiler_gaming_opcionA_fix.sql</code>.
        </div>

        <button class="btn btn-primary w-100" type="submit">Probar conexión y guardar</button>

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
