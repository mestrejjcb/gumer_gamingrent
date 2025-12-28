<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('productos');
require_once __DIR__ . '/app/layout.php';

layout_start('Nuevo producto', 'productos');
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tipo = $_POST['tipo'] ?? 'JUEGO';
  $nombre = trim($_POST['nombre'] ?? '');
  $plataforma = trim($_POST['plataforma'] ?? '');
  $genero = trim($_POST['genero'] ?? '');
  $pegi = (int)($_POST['pegi'] ?? 0);
  $precio = (float)($_POST['precio_dia'] ?? 0);
  $stock = (int)($_POST['stock_total'] ?? 0);
  $activo = (int)($_POST['activo'] ?? 1);

  if ($nombre === '' || $plataforma === '') $err = "Nombre y plataforma son obligatorios.";

  if (!$err) {
    try {
      $st = db()->prepare("INSERT INTO productos(tipo,nombre,plataforma,genero,pegi,precio_dia,stock_total,stock_disponible,activo)
                           VALUES(?,?,?,?,?,?,?,?,?)");
      $st->execute([$tipo,$nombre,$plataforma,$genero,$pegi,$precio,$stock,$stock,$activo]);
      flash_set('success','Producto creado correctamente.');
      redirect('productos.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
  }
}
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Alta de producto</div>
      <div class="text-secondary small">Negocio: ampliar catálogo y controlar stock</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="productos.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select class="form-select" name="tipo">
          <option value="JUEGO">JUEGO</option>
          <option value="CONSOLA">CONSOLA</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Nombre *</label>
        <input class="form-control" name="nombre" required>
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
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Género</label>
        <input class="form-control" name="genero">
      </div>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-md-4">
        <label class="form-label">PEGI</label>
        <input class="form-control" name="pegi" type="number" min="0" max="21" value="0">
      </div>
      <div class="col-md-4">
        <label class="form-label">Precio €/día</label>
        <input class="form-control" name="precio_dia" type="number" step="0.01" value="2.50">
      </div>
      <div class="col-md-4">
        <label class="form-label">Stock total</label>
        <input class="form-control" name="stock_total" type="number" min="0" value="1">
      </div>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-md-6">
        <label class="form-label">Activo</label>
        <select class="form-select" name="activo">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <button class="btn btn-primary w-100">Guardar</button>
      </div>
    </div>
  </form>
</div>
<?php layout_end(); ?>
