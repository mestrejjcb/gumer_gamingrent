<?php
function layout_start(string $title, string $active = ''): void {
  global $APP;
  $u = current_user();
  $flash = flash_get();
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title) ?> · <?= e($APP['name'] ?? 'App') ?></title>

  <!-- Set theme ASAP (evita parpadeo) -->
  <script>
    (function(){
      const saved = localStorage.getItem('theme');
      const preferred = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      const theme = saved || preferred;
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css">
  <link rel="icon" href="data:,">
</head>
<body>
  <div class="app-shell">
    <aside class="app-sidebar">
     <div class="brand">
  <img class="brand-mark"
       src="/gamingrent_1/assets/brand/gamingrent_logo_full.png"
       alt="GamingRent"
       width="48" height="48">

  <div>
    <div class="brand-title">GamingRent</div>
    <div class="brand-sub">Backoffice</div>
  </div>
</div>

      <nav class="nav flex-column gap-1">
        <?php if (can_access('dashboard')): ?>
          <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="index.php">Dashboard</a>
        <?php endif; ?>
        <?php if (can_access('clientes')): ?>
          <a class="nav-link <?= $active==='clientes'?'active':'' ?>" href="clientes.php">Clientes</a>
        <?php endif; ?>
        <?php if (can_access('productos')): ?>
          <a class="nav-link <?= $active==='productos'?'active':'' ?>" href="productos.php">Productos & Stock</a>
        <?php endif; ?>
        <?php if (can_access('alquileres')): ?>
          <a class="nav-link <?= $active==='alquileres'?'active':'' ?>" href="alquileres.php">Alquileres</a>
        <?php endif; ?>
        <?php if (can_access('pagos')): ?>
          <a class="nav-link <?= $active==='pagos'?'active':'' ?>" href="pagos.php">Pagos</a>
        <?php endif; ?>
        <?php if (can_access('empleados')): ?>
          <a class="nav-link <?= $active==='empleados'?'active':'' ?>" href="empleados.php">Empleados</a>
        <?php endif; ?>
        <?php if (can_access('reportes')): ?>
          <a class="nav-link <?= $active==='reportes'?'active':'' ?>" href="reportes.php">Reportes (Vistas)</a>
        <?php endif; ?>
        <?php if (current_role()==='GERENCIA'): ?>
          <a class="nav-link <?= $active==='acciones'?'active':'' ?>" href="acciones.php">Acciones (SPs)</a>
          <a class="nav-link <?= $active==='usuarios'?'active':'' ?>" href="usuarios.php">Usuarios</a>
        <?php endif; ?>
      </nav>

      <div class="sidebar-footer">
        <div class="userbox">
          <div class="user-avatar"><?= e(mb_substr($u['nombre'] ?? 'U',0,1)) ?></div>
          <div class="user-meta">
            <div class="user-name"><?= e($u['nombre'] ?? 'Invitado') ?></div>
            <div class="user-role"><?= e($u['rol'] ?? '') ?></div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-secondary w-100" id="themeToggle" type="button">Modo oscuro</button>
          <a class="btn btn-sm btn-outline-danger" href="logout.php">Salir</a>
        </div>
        <div class="hint mt-2">
          <span class="badge text-bg-light border">XAMPP</span>
          <span class="badge text-bg-light border">PHP</span>
          <span class="badge text-bg-light border">MySQL</span>
        </div>
      </div>
    </aside>

    <main class="app-main">
      <div class="topbar">
        <div>
          <div class="topbar-title"><?= e($title) ?></div>
          <div class="topbar-sub">Interfaz conectada a la BD (boceto profesional)</div>
        </div>
        <div class="topbar-right">
          <div class="small text-secondary">Tema: <span id="themeLabel">auto</span></div>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mt-3"><?= e($flash['msg']) ?></div>
      <?php endif; ?>
<?php
}

function layout_end(): void { ?>
      <div class="pb-5"></div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/app.js"></script>
</body>
</html>
<?php }
