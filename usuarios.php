<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('dashboard');
require_role(['GERENCIA']);
require_once __DIR__ . '/app/layout.php';

layout_start('Usuarios', 'usuarios');

$err = null;
$ok = null;

try {
  $pdo = db();
  if (!table_exists('usuarios')) redirect('install.php');

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      $usuario = trim($_POST['usuario'] ?? '');
      $nombre = trim($_POST['nombre'] ?? '');
      $rol = $_POST['rol'] ?? 'MOSTRADOR';
      $pass = (string)($_POST['pass'] ?? '');

      if ($usuario==='' || $nombre==='' || $pass==='') throw new RuntimeException("Usuario, nombre y contraseña son obligatorios.");

      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $st = $pdo->prepare("INSERT INTO usuarios(usuario,nombre,password_hash,rol,activo) VALUES(?,?,?,?,1)");
      $st->execute([$usuario,$nombre,$hash,$rol]);
      $ok = "Usuario creado correctamente.";
    }

    if ($action === 'toggle') {
      $id = (int)($_POST['id'] ?? 0);
      $activo = (int)($_POST['activo'] ?? 0);
      if ($id<=0) throw new RuntimeException("ID inválido.");
      $st = $pdo->prepare("UPDATE usuarios SET activo=? WHERE id_usuario=?");
      $st->execute([$activo, $id]);
      $ok = "Estado actualizado.";
    }

    if ($action === 'reset') {
      $id = (int)($_POST['id'] ?? 0);
      $pass = (string)($_POST['pass'] ?? '');
      if ($id<=0 || $pass==='') throw new RuntimeException("ID y contraseña son obligatorios.");
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $st = $pdo->prepare("UPDATE usuarios SET password_hash=? WHERE id_usuario=?");
      $st->execute([$hash, $id]);
      $ok = "Contraseña actualizada.";
    }
  }

  $rows = $pdo->query("SELECT id_usuario, usuario, nombre, rol, activo, fecha_alta FROM usuarios ORDER BY activo DESC, rol, usuario")->fetchAll();

} catch (Throwable $e) { $err = $e->getMessage(); }
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Usuarios del backoffice</div>
      <div class="text-secondary small">Negocio: controlar quién puede cobrar, gestionar stock o ver reportes</div>
    </div>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert alert-success mt-3"><?= e($ok) ?></div><?php endif; ?>

  <div class="mt-3 p-3 border rounded-3">
    <div class="fw-bold mb-2">Crear usuario</div>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="create">
      <div class="col-md-3">
        <input class="form-control" name="usuario" placeholder="usuario" required>
      </div>
      <div class="col-md-4">
        <input class="form-control" name="nombre" placeholder="Nombre y apellidos" required>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="rol">
          <option value="MOSTRADOR">MOSTRADOR</option>
          <option value="TECNICO">TECNICO</option>
          <option value="GERENCIA">GERENCIA</option>
        </select>
      </div>
      <div class="col-md-3">
        <input class="form-control" type="password" name="pass" placeholder="Contraseña" required>
      </div>
      <div class="col-12">
        <button class="btn btn-primary w-100">Crear</button>
      </div>
    </form>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Activo</th><th>Alta</th><th style="width:320px;">Acciones</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= e((string)$r['id_usuario']) ?></td>
          <td><?= e($r['usuario']) ?></td>
          <td><?= e($r['nombre']) ?></td>
          <td><span class="badge text-bg-light border"><?= e($r['rol']) ?></span></td>
          <td><?= (int)$r['activo']===1 ? 'Sí' : 'No' ?></td>
          <td><?= e((string)$r['fecha_alta']) ?></td>
          <td>
            <form method="post" class="d-flex gap-2" style="min-width:300px;">
              <input type="hidden" name="id" value="<?= e((string)$r['id_usuario']) ?>">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="activo" value="<?= (int)$r['activo']===1 ? 0 : 1 ?>">
              <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('¿Cambiar activo?')">
                <?= (int)$r['activo']===1 ? 'Desactivar' : 'Activar' ?>
              </button>
            </form>

            <form method="post" class="d-flex gap-2 mt-2">
              <input type="hidden" name="action" value="reset">
              <input type="hidden" name="id" value="<?= e((string)$r['id_usuario']) ?>">
              <input class="form-control form-control-sm" type="password" name="pass" placeholder="Nueva contraseña" required>
              <button class="btn btn-sm btn-outline-primary" onclick="return confirm('¿Resetear contraseña?')">Reset</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end(); ?>
