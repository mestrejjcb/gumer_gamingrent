<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_role(['GERENCIA']);
require_once __DIR__ . '/app/layout.php';
?>
<?php
layout_start('Acciones (Procedimientos)', 'acciones');

$pdo = null;
$clientes = $empleados = $productos = [];
try {
  $pdo = db();
  $clientes = $pdo->query("SELECT id_cliente, nombre FROM clientes ORDER BY nombre LIMIT 200")->fetchAll();
  $empleados = $pdo->query("SELECT id_empleado, nombre FROM empleados WHERE activo=1 ORDER BY nombre")->fetchAll();
  $productos = $pdo->query("SELECT id_producto, CONCAT(nombre,' · ',plataforma,' (disp: ',stock_disponible,')') AS label
                            FROM productos WHERE activo=1 ORDER BY stock_disponible ASC, nombre LIMIT 250")->fetchAll();
} catch (Throwable $e) {
  echo '<div class="alert alert-danger mt-3">' . e($e->getMessage()) . '</div>';
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $pdo) {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'nuevo_alquiler') {
      $id_cliente = (int)($_POST['id_cliente'] ?? 0);
      $id_empleado = (int)($_POST['id_empleado'] ?? 0);
      $fini = $_POST['fecha_inicio'] ?? '';
      $ffin = $_POST['fecha_fin_prevista'] ?? '';
      $dep = (float)($_POST['deposito'] ?? 0);

      $st = $pdo->prepare("CALL sp_nuevo_alquiler(?,?,?,?,?,@id_alq)");
      $st->execute([$id_cliente,$id_empleado,$fini,$ffin,$dep]);
      $created_id = (int)$pdo->query("SELECT @id_alq")->fetchColumn();
      flash_set('success', 'Alquiler creado. ID = ' . $created_id);
      redirect('alquiler.php?id=' . $created_id);
    }

    if ($action === 'add_producto') {
      $id_alquiler = (int)($_POST['id_alquiler'] ?? 0);
      $id_producto = (int)($_POST['id_producto'] ?? 0);
      $cantidad = (int)($_POST['cantidad'] ?? 1);
      $descuento = (int)($_POST['descuento'] ?? 0);

      $st = $pdo->prepare("CALL sp_agregar_producto_alquiler(?,?,?,?)");
      $st->execute([$id_alquiler,$id_producto,$cantidad,$descuento]);
      flash_set('success', 'Producto añadido al alquiler.');
      redirect('alquiler.php?id=' . $id_alquiler);
    }

    if ($action === 'devolucion') {
      $id_alquiler = (int)($_POST['id_alquiler'] ?? 0);
      $fecha_dev = $_POST['fecha_devolucion'] ?? '';

      $st = $pdo->prepare("CALL sp_registrar_devolucion(?,?)");
      $st->execute([$id_alquiler,$fecha_dev]);
      flash_set('success', 'Devolución registrada. Se recalculó el total.');
      redirect('alquiler.php?id=' . $id_alquiler);
    }

    if ($action === 'pago') {
      $id_alquiler = (int)($_POST['id_alquiler'] ?? 0);
      $importe = (float)($_POST['importe'] ?? 0);
      $metodo = $_POST['metodo'] ?? 'TARJETA';
      $concepto = $_POST['concepto'] ?? 'Pago';

      $st = $pdo->prepare("CALL sp_registrar_pago(?,?,?,?)");
      $st->execute([$id_alquiler,$importe,$metodo,$concepto]);
      flash_set('success', 'Pago registrado.');
      redirect('alquiler.php?id=' . $id_alquiler);
    }

  } catch (Throwable $e) {
    flash_set('danger', 'Error: ' . $e->getMessage());
    redirect('acciones.php');
  }
}
?>

<div class="row g-3 mt-3">
  <div class="col-lg-6">
    <div class="card-soft p-3" id="nuevoAlquiler">
      <div class="fw-bold">Abrir alquiler (sp_nuevo_alquiler)</div>
      <div class="text-secondary small">Mostrador: crea el “contrato” y devuelve un ID</div>

      <form method="post" class="mt-3">
        <input type="hidden" name="action" value="nuevo_alquiler">
        <div class="mb-2">
          <label class="form-label">Cliente</label>
          <select class="form-select" name="id_cliente" required>
            <?php foreach($clientes as $c): ?>
              <option value="<?= e($c['id_cliente']) ?>"><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Empleado</label>
          <select class="form-select" name="id_empleado" required>
            <?php foreach($empleados as $e): ?>
              <option value="<?= e($e['id_empleado']) ?>"><?= e($e['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2">
          <div class="col-md-6 mb-2">
            <label class="form-label">Fecha inicio</label>
            <input class="form-control" type="date" name="fecha_inicio" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Fin previsto</label>
            <input class="form-control" type="date" name="fecha_fin_prevista" required>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Depósito (€)</label>
          <input class="form-control" type="number" name="deposito" step="0.01" value="0">
        </div>
        <button class="btn btn-primary w-100">Crear alquiler</button>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card-soft p-3" id="addProducto">
      <div class="fw-bold">Añadir producto (sp_agregar_producto_alquiler)</div>
      <div class="text-secondary small">Baja stock disponible automáticamente</div>

      <form method="post" class="mt-3">
        <input type="hidden" name="action" value="add_producto">
        <div class="mb-2">
          <label class="form-label">ID alquiler</label>
          <input class="form-control" type="number" name="id_alquiler" placeholder="Ej: 1" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Producto</label>
          <select class="form-select" name="id_producto" required>
            <?php foreach($productos as $p): ?>
              <option value="<?= e($p['id_producto']) ?>"><?= e($p['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2">
          <div class="col-md-6 mb-2">
            <label class="form-label">Cantidad</label>
            <input class="form-control" type="number" name="cantidad" value="1" min="1" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Descuento (%)</label>
            <input class="form-control" type="number" name="descuento" value="0" min="0" max="100">
          </div>
        </div>
        <button class="btn btn-outline-primary w-100">Añadir</button>
      </form>
    </div>

    <div class="card-soft p-3 mt-3" id="devolucion">
      <div class="fw-bold">Registrar devolución (sp_registrar_devolucion)</div>
      <div class="text-secondary small">Calcula total + recargo + devuelve stock</div>

      <form method="post" class="mt-3">
        <input type="hidden" name="action" value="devolucion">
        <div class="row g-2">
          <div class="col-md-6 mb-2">
            <label class="form-label">ID alquiler</label>
            <input class="form-control" type="number" name="id_alquiler" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Fecha devolución</label>
            <input class="form-control" type="date" name="fecha_devolucion" required>
          </div>
        </div>
        <button class="btn btn-outline-success w-100">Cerrar alquiler</button>
      </form>
    </div>

    <div class="card-soft p-3 mt-3" id="pago">
      <div class="fw-bold">Registrar pago (sp_registrar_pago)</div>
      <div class="text-secondary small">Caja / auditoría</div>

      <form method="post" class="mt-3">
        <input type="hidden" name="action" value="pago">
        <div class="mb-2">
          <label class="form-label">ID alquiler</label>
          <input class="form-control" type="number" name="id_alquiler" required>
        </div>
        <div class="row g-2">
          <div class="col-md-6 mb-2">
            <label class="form-label">Importe (€)</label>
            <input class="form-control" type="number" name="importe" step="0.01" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Método</label>
            <select class="form-select" name="metodo">
              <option>TARJETA</option>
              <option>EFECTIVO</option>
              <option>BIZUM</option>
            </select>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Concepto</label>
          <input class="form-control" name="concepto" value="Pago">
        </div>
        <button class="btn btn-outline-primary w-100">Registrar pago</button>
      </form>
    </div>
  </div>
</div>

<?php layout_end(); ?>
