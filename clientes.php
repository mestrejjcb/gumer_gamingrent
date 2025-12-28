<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('clientes');
require_once __DIR__ . '/app/layout.php';

// Acciones rápidas desde el listado (actualizar / eliminar por ID)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $idCliente = (int)($_POST['id_cliente'] ?? 0);

  if ($idCliente <= 0) {
    flash_set('warning', 'ID de cliente inválido.');
    redirect('clientes.php');
  }

  if ($action === 'edit') {
    redirect('cliente_edit.php?id=' . $idCliente);
  }

  if ($action === 'delete') {
    try {
      $pdo = db();
      $st = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE id_cliente=?");
      $st->execute([$idCliente]);
      $exists = (int)$st->fetchColumn();

      $stAlq = $pdo->prepare("SELECT COUNT(*) FROM alquileres WHERE id_cliente=?");
      $stAlq->execute([$idCliente]);
      $alqs = (int)$stAlq->fetchColumn();

      if ($exists === 0) {
        flash_set('warning', 'Cliente no encontrado.');
      } elseif ($alqs > 0) {
        flash_set('warning', 'No se puede eliminar: tiene alquileres asociados.');
      } else {
        $del = $pdo->prepare("DELETE FROM clientes WHERE id_cliente=?");
        $del->execute([$idCliente]);
        flash_set('success', 'Cliente eliminado correctamente.');
      }
    } catch (Throwable $e) {
      flash_set('danger', 'Error eliminando cliente: ' . $e->getMessage());
    }
    redirect('clientes.php');
  }

  // Acción no soportada
  flash_set('warning', 'Acción no soportada.');
  redirect('clientes.php');
}

layout_start('Clientes', 'clientes');

$q = trim($_GET['q'] ?? '');
$sql = "SELECT id_cliente, nombre, email, telefono, fecha_alta, puntos_fidelidad
        FROM clientes
        WHERE 1=1";
$params = [];
if ($q !== '') {
  $sql .= " AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
  $like = "%$q%";
  $params = [$like,$like,$like];
}
$sql .= " ORDER BY id_cliente DESC LIMIT 200";

try {
  $st = db()->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll();
} catch (Throwable $e) {
  echo '<div class="alert alert-danger mt-3">' . e($e->getMessage()) . '</div>';
  layout_end(); exit;
}
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end">
    <div>
      <div class="fw-bold">Listado de clientes</div>
      <div class="text-secondary small">Operativa: buscar, actualizar datos, fidelización</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-primary" href="cliente_new.php">+ Nuevo</a>
      <form class="d-flex gap-1 align-items-center" method="post">
        <input type="hidden" name="action" value="edit">
        <input class="form-control form-control-sm" name="id_cliente" type="number" min="1" placeholder="ID" style="width:100px">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Actualizar</button>
      </form>
      <form class="d-flex gap-1 align-items-center" method="post" onsubmit="return confirm('¿Eliminar cliente? Esta acción no se puede deshacer.');">
        <input type="hidden" name="action" value="delete">
        <input class="form-control form-control-sm" name="id_cliente" type="number" min="1" placeholder="ID" style="width:100px">
        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
      </form>
    </div>
    <form class="d-flex gap-2" method="get">
      <input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Buscar: nombre, email, teléfono">
      <button class="btn btn-sm btn-outline-primary">Buscar</button>
    </form>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle mb-0">
      <thead><tr>
        <th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Alta</th><th class="text-end">Puntos</th>
      </tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= e($r['id_cliente']) ?></td>
          <td><a href="cliente_edit.php?id=<?= e($r['id_cliente']) ?>"><?= e($r['nombre']) ?></a></td>
          <td><?= e($r['email']) ?></td>
          <td><?= e($r['telefono']) ?></td>
          <td><?= e(fmt_date($r['fecha_alta'])) ?></td>
          <td class="text-end"><?= e($r['puntos_fidelidad']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end(); ?>
