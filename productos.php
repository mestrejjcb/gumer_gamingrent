<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('productos');
require_once __DIR__ . '/app/layout.php';
?>
<?php
// Acciones rápidas desde el listado (actualizar / desactivar por ID)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $idProducto = (int)($_POST['id_producto'] ?? 0);

  if ($idProducto <= 0) {
    flash_set('warning', 'ID de producto inválido.');
    redirect('productos.php');
  }

  if ($action === 'edit') {
    redirect('producto_edit.php?id=' . $idProducto);
  }

  if ($action === 'delete') {
    try {
      $pdo = db();
      $st = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE id_producto=?");
      $st->execute([$idProducto]);
      $exists = (int)$st->fetchColumn();

      if ($exists === 0) {
        flash_set('warning', 'Producto no encontrado.');
      } else {
        $up = $pdo->prepare("UPDATE productos SET activo=0 WHERE id_producto=?");
        $up->execute([$idProducto]);
        flash_set('info', 'Producto desactivado (baja lógica).');
      }
    } catch (Throwable $e) {
      flash_set('danger', 'Error al desactivar producto: ' . $e->getMessage());
    }
    redirect('productos.php');
  }

  flash_set('warning', 'Acción no soportada.');
  redirect('productos.php');
}

layout_start('Productos & Stock', 'productos');

$q = trim($_GET['q'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$sql = "SELECT id_producto, tipo, nombre, plataforma, genero, pegi, precio_dia, stock_total, stock_disponible, activo
        FROM productos
        WHERE 1=1";
$params = [];
if ($q !== '') {
  $sql .= " AND (nombre LIKE ? OR plataforma LIKE ? OR genero LIKE ?)";
  $like = "%$q%";
  $params = array_merge($params, [$like,$like,$like]);
}
if ($tipo !== '') {
  $sql .= " AND tipo = ?";
  $params[] = $tipo;
}
$sql .= " ORDER BY activo DESC, stock_disponible ASC, id_producto DESC LIMIT 300";

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
      <div class="fw-bold">Inventario</div>
      <div class="text-secondary small">Negocio: evitar alquileres sin stock y decidir reposición</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-primary" href="producto_new.php">+ Nuevo</a>
      <form class="d-flex gap-1 align-items-center" method="post">
        <input type="hidden" name="action" value="edit">
        <input class="form-control form-control-sm" name="id_producto" type="number" min="1" placeholder="ID" style="width:100px">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Actualizar</button>
      </form>
      <form class="d-flex gap-1 align-items-center" method="post" onsubmit="return confirm('¿Desactivar producto? (baja lógica)');">
        <input type="hidden" name="action" value="delete">
        <input class="form-control form-control-sm" name="id_producto" type="number" min="1" placeholder="ID" style="width:100px">
        <button class="btn btn-sm btn-outline-danger" type="submit">Desactivar</button>
      </form>
    </div>
    <form class="d-flex gap-2" method="get">
      <select class="form-select form-select-sm" name="tipo">
        <option value="">Tipo (todos)</option>
        <option value="JUEGO" <?= $tipo==='JUEGO'?'selected':'' ?>>JUEGO</option>
        <option value="CONSOLA" <?= $tipo==='CONSOLA'?'selected':'' ?>>CONSOLA</option>
      </select>
      <input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Buscar: nombre, plataforma, género">
      <button class="btn btn-sm btn-outline-primary">Filtrar</button>
    </form>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle mb-0">
      <thead><tr>
        <th>ID</th><th>Tipo</th><th>Producto</th><th>Plataforma</th><th>Género</th><th class="text-end">€/día</th>
        <th class="text-end">Total</th><th class="text-end">Disp.</th><th>Estado</th>
      </tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= e($r['id_producto']) ?></td>
          <td><span class="badge text-bg-<?= $r['tipo']==='CONSOLA'?'primary':'secondary' ?>"><?= e($r['tipo']) ?></span></td>
          <td><a href="producto_edit.php?id=<?= e($r['id_producto']) ?>"><?= e($r['nombre']) ?></a></td>
          <td><?= e($r['plataforma']) ?></td>
          <td><?= e($r['genero'] ?? '-') ?></td>
          <td class="text-end"><?= e(number_format((float)$r['precio_dia'],2,',','.')) ?></td>
          <td class="text-end"><?= e($r['stock_total']) ?></td>
          <td class="text-end">
            <span class="badge text-bg-<?= ((int)$r['stock_disponible']<=1)?'warning':'success' ?>">
              <?= e($r['stock_disponible']) ?>
            </span>
          </td>
          <td><?= (int)$r['activo']===1 ? 'Activo' : 'Inactivo' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end(); ?>
