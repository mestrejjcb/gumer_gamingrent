<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('empleados');
require_once __DIR__ . '/app/layout.php';

layout_start('Nuevo empleado', 'empleados');

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $rol = $_POST['rol'] ?? 'MOSTRADOR';
  $activo = (int)($_POST['activo'] ?? 1);

  $rolesValidos = ['GERENCIA','MOSTRADOR','TECNICO'];
  if (!in_array($rol, $rolesValidos, true)) {
    $rol = 'MOSTRADOR';
  }
  $activo = $activo ? 1 : 0;

  if ($nombre === '') {
    $err = 'El nombre es obligatorio.';
  }

  if (!$err) {
    try {
      $st = db()->prepare("INSERT INTO empleados(nombre, rol, activo) VALUES(?,?,?)");
      $st->execute([$nombre,$rol,$activo]);
      flash_set('success', 'Empleado creado correctamente.');
      redirect('empleados.php');
    } catch (Throwable $e) {
      $err = $e->getMessage();
    }
  }
}
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Alta de empleado</div>
      <div class="text-secondary small">Control de roles y estado activo</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="empleados.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <div class="mb-2">
      <label class="form-label">Nombre *</label>
      <input class="form-control" name="nombre" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Rol</label>
      <select class="form-select" name="rol">
        <option value="GERENCIA">GERENCIA</option>
        <option value="MOSTRADOR" selected>MOSTRADOR</option>
        <option value="TECNICO">TECNICO</option>
      </select>
    </div>
    <div class="mb-2">
      <label class="form-label">Activo</label>
      <select class="form-select" name="activo">
        <option value="1" selected>Si</option>
        <option value="0">No</option>
      </select>
    </div>
    <button class="btn btn-primary w-100">Guardar</button>
  </form>
</div>
<?php layout_end(); ?>
