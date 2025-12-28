<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('empleados');
require_once __DIR__ . '/app/layout.php';
?>
<?php
// Acciones rápidas desde el listado (actualizar / desactivar por ID)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $idEmpleado = (int)($_POST['id_empleado'] ?? 0);

  if ($idEmpleado <= 0) {
    flash_set('warning', 'ID de empleado invalido.');
    redirect('empleados.php');
  }

  if ($action === 'edit') {
    redirect('empleado_edit.php?id=' . $idEmpleado);
  }

  if ($action === 'delete') {
    try {
      $pdo = db();
      $st = $pdo->prepare("SELECT COUNT(*) FROM empleados WHERE id_empleado=?");
      $st->execute([$idEmpleado]);
      $exists = (int)$st->fetchColumn();

      if ($exists === 0) {
        flash_set('warning', 'Empleado no encontrado.');
      } else {
        $up = $pdo->prepare("UPDATE empleados SET activo=0 WHERE id_empleado=?");
        $up->execute([$idEmpleado]);
        flash_set('info', 'Empleado desactivado (baja logica).');
      }
    } catch (Throwable $e) {
      flash_set('danger', 'Error al desactivar empleado: ' . $e->getMessage());
    }
    redirect('empleados.php');
  }

  flash_set('warning', 'Accion no soportada.');
  redirect('empleados.php');
}

layout_start('Empleados', 'empleados');

try {
  $rows = db()->query("SELECT id_empleado, nombre, rol, activo FROM empleados ORDER BY activo DESC, rol, nombre")->fetchAll();
} catch (Throwable $e) {
  echo '<div class="alert alert-danger mt-3">' . e($e->getMessage()) . '</div>';
  layout_end(); exit;
}
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end">
    <div>
      <div class="fw-bold">Empleados</div>
      <div class="text-secondary small">Negocio: trazabilidad (quien atendio el alquiler)</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-primary" href="empleado_new.php">+ Nuevo</a>
      <form class="d-flex gap-1 align-items-center" method="post">
        <input type="hidden" name="action" value="edit">
        <input class="form-control form-control-sm" name="id_empleado" type="number" min="1" placeholder="ID" style="width:100px">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Actualizar</button>
      </form>
      <form class="d-flex gap-1 align-items-center" method="post" onsubmit="return confirm('Eliminar (desactivar) este empleado?');">
        <input type="hidden" name="action" value="delete">
        <input class="form-control form-control-sm" name="id_empleado" type="number" min="1" placeholder="ID" style="width:100px">
        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
      </form>
    </div>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Activo</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= e($r['id_empleado']) ?></td>
          <td><?= e($r['nombre']) ?></td>
          <td><span class="badge text-bg-light border"><?= e($r['rol']) ?></span></td>
          <td><?= (int)$r['activo']===1 ? 'Si' : 'No' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end(); ?>
