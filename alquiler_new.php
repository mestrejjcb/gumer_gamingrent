<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('alquileres');
require_role(['GERENCIA','MOSTRADOR']);
require_once __DIR__ . '/app/layout.php';

layout_start('Nuevo alquiler', 'alquileres');

$err = null;

try {
  $pdo = db();
  $clientes = $pdo->query("SELECT id_cliente, nombre FROM clientes ORDER BY nombre")->fetchAll();
  $empleados = $pdo->query("SELECT id_empleado, nombre, rol FROM empleados WHERE activo=1 ORDER BY rol, nombre")->fetchAll();
} catch (Throwable $e) { $err = $e->getMessage(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_cliente = (int)($_POST['id_cliente'] ?? 0);
  $id_empleado = (int)($_POST['id_empleado'] ?? 0);
  $inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
  $fin = $_POST['fecha_fin_prevista'] ?? date('Y-m-d', strtotime('+3 day'));
  $deposito = (float)($_POST['deposito'] ?? 0);
  $obs = trim($_POST['observaciones'] ?? '');

  if ($id_cliente<=0 || $id_empleado<=0) $err = "Selecciona cliente y empleado.";

  if (!$err) {
    try{
      $st = db()->prepare("INSERT INTO alquileres(id_cliente,id_empleado,fecha_inicio,fecha_fin_prevista,deposito,observaciones)
                           VALUES(?,?,?,?,?,?)");
      $st->execute([$id_cliente,$id_empleado,$inicio,$fin,$deposito,$obs]);
      $id = (int)db()->lastInsertId();
      flash_set('success','Alquiler abierto. Ahora añade productos.');
      redirect('alquiler.php?id='.$id);
    } catch (Throwable $e) { $err = $e->getMessage(); }
  }
}
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Abrir alquiler</div>
      <div class="text-secondary small">Paso 1: elegir cliente y fechas. Paso 2: añadir productos.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="alquileres.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <div class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Cliente</label>
        <select class="form-select" name="id_cliente" required>
          <option value="">-- Selecciona --</option>
          <?php foreach($clientes as $c): ?>
            <option value="<?= e((string)$c['id_cliente']) ?>"><?= e($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Empleado (quién gestiona)</label>
        <select class="form-select" name="id_empleado" required>
          <option value="">-- Selecciona --</option>
          <?php foreach($empleados as $e): ?>
            <option value="<?= e((string)$e['id_empleado']) ?>"><?= e($e['nombre'].' · '.$e['rol']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-md-4">
        <label class="form-label">Fecha inicio</label>
        <input class="form-control" type="date" name="fecha_inicio" value="<?= e(date('Y-m-d')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Fecha fin prevista</label>
        <input class="form-control" type="date" name="fecha_fin_prevista" value="<?= e(date('Y-m-d', strtotime('+3 day'))) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Depósito (€)</label>
        <input class="form-control" type="number" step="0.01" name="deposito" value="0">
      </div>
    </div>

    <div class="mt-2">
      <label class="form-label">Observaciones</label>
      <input class="form-control" name="observaciones" placeholder="Ej: mando extra, funda, etc.">
    </div>

    <button class="btn btn-primary w-100 mt-3">Crear alquiler</button>
  </form>
</div>
<?php layout_end(); ?>
