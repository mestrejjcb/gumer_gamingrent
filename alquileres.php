<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('alquileres');
require_once __DIR__ . '/app/layout.php';

layout_start('Alquileres', 'alquileres');

$estado = trim($_GET['estado'] ?? 'ABIERTO');
$q = trim($_GET['q'] ?? '');

$sql = "SELECT a.id_alquiler, a.estado, a.fecha_inicio, a.fecha_fin_prevista, a.fecha_fin_real,
               c.nombre AS cliente, e.nombre AS empleado,
               a.total_final, a.recargo
        FROM alquileres a
        JOIN clientes c ON c.id_cliente=a.id_cliente
        JOIN empleados e ON e.id_empleado=a.id_empleado
        WHERE 1=1";
$params = [];
if ($estado !== '') { $sql .= " AND a.estado = ?"; $params[] = $estado; }
if ($q !== '') {
  $sql .= " AND (c.nombre LIKE ? OR e.nombre LIKE ? OR a.id_alquiler = ?)";
  $like = "%$q%";
  $params[] = $like; $params[] = $like; $params[] = $q;
}
$sql .= " ORDER BY a.id_alquiler DESC LIMIT 200";

$rows = [];
$err = null;
try {
  $st = db()->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll();
} catch (Throwable $e) { $err = $e->getMessage(); }
?>

<div class="d-flex justify-content-between align-items-center mt-3">
  <div>
    <div class="h5 mb-0">Gestión de alquileres</div>
    <div class="text-secondary small">Negocio: abrir alquiler, reservar stock, devolver y cobrar</div>
  </div>
  <?php if (in_array(current_role(), ['GERENCIA','MOSTRADOR'], true)): ?>
    <a class="btn btn-primary" href="alquiler_new.php">+ Nuevo alquiler</a>
  <?php endif; ?>
</div>

<?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

<div class="card-soft p-3 mt-3">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-md-3">
      <label class="form-label">Estado</label>
      <select class="form-select" name="estado">
        <option value="ABIERTO" <?= $estado==='ABIERTO'?'selected':'' ?>>ABIERTO</option>
        <option value="CERRADO" <?= $estado==='CERRADO'?'selected':'' ?>>CERRADO</option>
        <option value="" <?= $estado===''?'selected':'' ?>>Todos</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Buscar (cliente, empleado, id)</label>
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Ej: Carlos o 12">
    </div>
    <div class="col-md-3 d-grid">
      <button class="btn btn-outline-secondary">Filtrar</button>
    </div>
  </form>

  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th><th>Estado</th><th>Cliente</th><th>Empleado</th>
          <th>Inicio</th><th>Prevista</th><th>Real</th>
          <th class="text-end">Recargo</th><th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="alquiler.php?id=<?= e($r['id_alquiler']) ?>">#<?= e($r['id_alquiler']) ?></a></td>
          <td><span class="badge text-bg-light border"><?= e($r['estado']) ?></span></td>
          <td><?= e($r['cliente']) ?></td>
          <td><?= e($r['empleado']) ?></td>
          <td><?= e(fmt_date($r['fecha_inicio'])) ?></td>
          <td><?= e(fmt_date($r['fecha_fin_prevista'])) ?></td>
          <td><?= e(fmt_date($r['fecha_fin_real'])) ?></td>
          <td class="text-end"><?= e(fmt_eur($r['recargo'])) ?></td>
          <td class="text-end fw-semibold"><?= e(fmt_eur($r['total_final'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$rows): ?>
      <div class="text-secondary small mt-2">Sin resultados.</div>
    <?php endif; ?>
  </div>
</div>

<?php layout_end(); ?>
