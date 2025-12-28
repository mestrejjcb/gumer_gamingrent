<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('pagos');
require_once __DIR__ . '/app/layout.php';
?>
<?php
layout_start('Pagos', 'pagos');

$q = trim($_GET['q'] ?? '');
$sql = "SELECT p.id_pago, p.fecha_pago, p.importe, p.metodo, p.concepto, p.id_alquiler,
               c.nombre AS cliente
        FROM pagos p
        JOIN alquileres a ON a.id_alquiler=p.id_alquiler
        JOIN clientes c ON c.id_cliente=a.id_cliente
        WHERE 1=1";
$params = [];
if ($q !== '') {
  $sql .= " AND (c.nombre LIKE ? OR p.metodo LIKE ? OR p.concepto LIKE ? OR p.id_alquiler = ?)";
  $like = "%$q%";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $params[] = ctype_digit($q) ? (int)$q : -1;
}
$sql .= " ORDER BY p.id_pago DESC LIMIT 250";

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
      <div class="fw-bold">Pagos</div>
      <div class="text-secondary small">Negocio: auditoría, métodos de pago y caja</div>
    </div>
    <form class="d-flex gap-2" method="get">
      <input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Buscar: cliente, método, alquiler...">
      <button class="btn btn-sm btn-outline-primary">Buscar</button>
    </form>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-sm align-middle mb-0">
      <thead><tr>
        <th>ID</th><th>Fecha</th><th>Cliente</th><th>Método</th><th>Concepto</th><th class="text-end">Importe</th><th class="text-end">Alquiler</th>
      </tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= e($r['id_pago']) ?></td>
          <td><?= e($r['fecha_pago']) ?></td>
          <td><?= e($r['cliente']) ?></td>
          <td><?= e($r['metodo']) ?></td>
          <td><?= e($r['concepto']) ?></td>
          <td class="text-end"><b><?= e(fmt_eur($r['importe'])) ?></b></td>
          <td class="text-end"><a href="alquiler.php?id=<?= e($r['id_alquiler']) ?>"><?= e($r['id_alquiler']) ?></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="d-grid mt-3">
    <a class="btn btn-outline-primary" href="acciones.php#pago">Registrar pago (SP)</a>
  </div>
</div>
<?php layout_end(); ?>
