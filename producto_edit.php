<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('productos');
require_once __DIR__ . '/app/layout.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('productos.php');

$err = null;

try {
  $st = db()->prepare("SELECT * FROM productos WHERE id_producto=?");
  $st->execute([$id]);
  $p = $st->fetch();
  if (!$p) { flash_set('warning','Producto no encontrado.'); redirect('productos.php'); }
} catch (Throwable $e) { $err = $e->getMessage(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
  $action = $_POST['action'] ?? 'save';

  if ($action === 'delete') {
    try{
      // Baja lógica (activo=0), no borra por FK
      $up = db()->prepare("UPDATE productos SET activo=0 WHERE id_producto=?");
      $up->execute([$id]);
      flash_set('info','Producto desactivado (baja lógica).');
      redirect('productos.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
  } else {
    $tipo = $_POST['tipo'] ?? 'JUEGO';
    $nombre = trim($_POST['nombre'] ?? '');
    $plataforma = trim($_POST['plataforma'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $pegi = (int)($_POST['pegi'] ?? 0);
    $precio = (float)($_POST['precio_dia'] ?? 0);
    $stock_total = (int)($_POST['stock_total'] ?? 0);
    $stock_disp = (int)($_POST['stock_disponible'] ?? 0);
    $activo = (int)($_POST['activo'] ?? 1);

    if ($nombre === '' || $plataforma === '') $err = "Nombre y plataforma son obligatorios.";
    if ($stock_disp > $stock_total) $err = "Stock disponible no puede ser mayor que stock total.";

    if (!$err) {
      try {
        $up = db()->prepare("UPDATE productos
                              SET tipo=?, nombre=?, plataforma=?, genero=?, pegi=?, precio_dia=?, stock_total=?, stock_disponible=?, activo=?
                              WHERE id_producto=?");
        $up->execute([$tipo,$nombre,$plataforma,$genero,$pegi,$precio,$stock_total,$stock_disp,$activo,$id]);
        flash_set('success','Producto actualizado.');
        redirect('productos.php');
      } catch (Throwable $e) { $err = $e->getMessage(); }
    }
  }
}

layout_start('Editar producto', 'productos');
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Editar producto #<?= e((string)$id) ?></div>
      <div class="text-secondary small">Control de catálogo y stock (no borrar físico por seguridad)</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="productos.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <input type="hidden" name="action" value="save">

    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select class="form-select" name="tipo">
          <option value="JUEGO" <?= $p['tipo']==='JUEGO'?'selected':'' ?>>JUEGO</option>
          <option value="CONSOLA" <?= $p['tipo']==='CONSOLA'?'selected':'' ?>>CONSOLA</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Nombre *</label>
        <input class="form-control" name="nombre" value="<?= e($p['nombre']) ?>" required>
      </div>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-md-6">
        <label class="form-label">Plataforma *</label>
        <select class="form-select" name="plataforma" required>
          <option value="PS5">PS5</option>
          <option value="PS4">PS4</option>
          <option value="XBOX_SERIES">XBOX_SERIES</option>
          <option value="XBOX_ONE">XBOX_ONE</option>
          <option value="SWITCH">SWITCH</option>
          <option value="PC">PC</option>
        </select>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Género</label>
        <input class="form-control" name="genero" value="<?= e($p['genero'] ?? '') ?>">
      </div>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-md-3">
        <label class="form-label">PEGI</label>
        <input class="form-control" name="pegi" type="number" min="0" max="21" value="<?= e((string)$p['pegi']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Precio €/día</label>
        <input class="form-control" name="precio_dia" type="number" step="0.01" value="<?= e((string)$p['precio_dia']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Stock total</label>
        <input class="form-control" name="stock_total" type="number" min="0" value="<?= e((string)$p['stock_total']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Stock disponible</label>
        <input class="form-control" name="stock_disponible" type="number" min="0" value="<?= e((string)$p['stock_disponible']) ?>">
      </div>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-md-6">
        <label class="form-label">Activo</label>
        <select class="form-select" name="activo">
          <option value="1" <?= ((int)$p['activo']===1)?'selected':'' ?>>Sí</option>
          <option value="0" <?= ((int)$p['activo']===0)?'selected':'' ?>>No</option>
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-primary w-100">Guardar</button>
      </div>
    </div>
  </form>

  <form method="post" class="mt-3" onsubmit="return confirm('¿Desactivar este producto? (baja lógica)');">
    <input type="hidden" name="action" value="delete">
    <button class="btn btn-outline-danger w-100">Desactivar producto</button>
  </form>

</div>
<?php layout_end(); ?>
