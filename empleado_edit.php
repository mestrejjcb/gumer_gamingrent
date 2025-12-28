<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('empleados');
require_once __DIR__ . '/app/layout.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('empleados.php');

$err = null;

try {
  $st = db()->prepare("SELECT * FROM empleados WHERE id_empleado=?");
  $st->execute([$id]);
  $emp = $st->fetch();
  if (!$emp) { flash_set('warning','Empleado no encontrado.'); redirect('empleados.php'); }
} catch (Throwable $e) { $err = $e->getMessage(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
  $action = $_POST['action'] ?? 'save';
  if ($action === 'delete') {
    try {
      $up = db()->prepare("UPDATE empleados SET activo=0 WHERE id_empleado=?");
      $up->execute([$id]);
      flash_set('info','Empleado desactivado (baja logica).');
      redirect('empleados.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
  } else {
    $nombre = trim($_POST['nombre'] ?? '');
    $rol = $_POST['rol'] ?? 'MOSTRADOR';
    $activo = (int)($_POST['activo'] ?? 1);

    $rolesValidos = ['GERENCIA','MOSTRADOR','TECNICO'];
    if (!in_array($rol, $rolesValidos, true)) {
      $rol = 'MOSTRADOR';
    }
    $activo = $activo ? 1 : 0;

    if ($nombre === '') $err = 'El nombre es obligatorio.';

    if (!$err) {
      try {
        $up = db()->prepare("UPDATE empleados SET nombre=?, rol=?, activo=? WHERE id_empleado=?");
        $up->execute([$nombre,$rol,$activo,$id]);
        flash_set('success','Empleado actualizado.');
        redirect('empleados.php');
      } catch (Throwable $e) { $err = $e->getMessage(); }
    }
  }
}

layout_start('Editar empleado', 'empleados');
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Editar empleado #<?= e((string)$id) ?></div>
      <div class="text-secondary small">Actualizar rol o activar/desactivar</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="empleados.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <input type="hidden" name="action" value="save">
    <div class="mb-2">
      <label class="form-label">Nombre *</label>
      <input class="form-control" name="nombre" value="<?= e($emp['nombre']) ?>" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Rol</label>
      <select class="form-select" name="rol">
        <option value="GERENCIA" <?= $emp['rol']==='GERENCIA'?'selected':'' ?>>GERENCIA</option>
        <option value="MOSTRADOR" <?= $emp['rol']==='MOSTRADOR'?'selected':'' ?>>MOSTRADOR</option>
        <option value="TECNICO" <?= $emp['rol']==='TECNICO'?'selected':'' ?>>TECNICO</option>
      </select>
    </div>
    <div class="mb-2">
      <label class="form-label">Activo</label>
      <select class="form-select" name="activo">
        <option value="1" <?= ((int)$emp['activo']===1)?'selected':'' ?>>Si</option>
        <option value="0" <?= ((int)$emp['activo']===0)?'selected':'' ?>>No</option>
      </select>
    </div>
    <button class="btn btn-primary w-100">Guardar cambios</button>
  </form>

  <form method="post" class="mt-3" onsubmit="return confirm('Eliminar (desactivar) este empleado?');">
    <input type="hidden" name="action" value="delete">
    <button class="btn btn-outline-danger w-100">Desactivar empleado</button>
  </form>
</div>
<?php layout_end(); ?>
