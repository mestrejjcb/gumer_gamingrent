<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('reportes');
require_once __DIR__ . '/app/layout.php';
$pdo = db();
?>
<?php
layout_start('Reportes (Vistas)', 'reportes');

$ranking = [];
$detalle = [];
try {
  if (table_exists('vw_productos_ranking')) {
    $ranking = $pdo->query("
    SELECT producto, plataforma, veces_en_alquiler, unidades_alquiladas, ingresos_estimados
    FROM vw_productos_ranking
    ORDER BY unidades_alquiladas DESC, ingresos_estimados DESC
    LIMIT 25
")->fetchAll();

  }
  if (table_exists('vw_alquileres_detalle')) {
    $detalle = db()->query("SELECT * FROM vw_alquileres_detalle ORDER BY id_alquiler DESC LIMIT 25")->fetchAll();
  }
} catch (Throwable $e) {
  echo '<div class="alert alert-danger mt-3">' . e($e->getMessage()) . '</div>';
}
?>

<div class="row g-3 mt-3">
  <div class="col-lg-6">
    <div class="card-soft p-3">
      <div class="fw-bold">vw_productos_ranking</div>
      <div class="text-secondary small">Gerencia: qué se alquila más (reposiciones / promociones)</div>
      <div class="table-responsive mt-3">
        <table class="table table-sm align-middle mb-0">
          <thead>
  <tr>
    <th>Producto</th>
    <th>Plataforma</th>
    <th class="text-end">Veces</th>
    <th class="text-end">Unidades</th>
    <th class="text-end">Ingresos</th>
  </tr>
</thead>
<tbody>
  <?php if (!$ranking): ?>
    <tr><td colspan="5" class="text-secondary">Sin datos / vista no disponible.</td></tr>
  <?php else: foreach($ranking as $r): ?>
    <tr>
      <td><?= e((string)($r['producto'] ?? '')) ?></td>
      <td><?= e((string)($r['plataforma'] ?? '')) ?></td>
      <td class="text-end"><?= e((string)($r['veces_en_alquiler'] ?? 0)) ?></td>
      <td class="text-end"><b><?= e((string)($r['unidades_alquiladas'] ?? 0)) ?></b></td>
      <td class="text-end"><?= e(fmt_eur($r['ingresos_estimados'] ?? 0)) ?></td>
    </tr>
  <?php endforeach; endif; ?>
</tbody>

        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card-soft p-3">
      <div class="fw-bold">vw_alquileres_detalle</div>
      <div class="text-secondary small">Mostrador: ticket extendido / reclamaciones</div>
      <div class="table-responsive mt-3">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Alquiler</th><th>Cliente</th><th>Producto</th><th class="text-end">Cant.</th></tr></thead>
          <tbody>
            <?php if (!$detalle): ?>
              <tr><td colspan="4" class="text-secondary">Sin datos / vista no disponible.</td></tr>
            <?php else: foreach($detalle as $d): ?>
              <tr>
                <td><a href="alquiler.php?id=<?= e($d['id_alquiler']) ?>"><?= e($d['id_alquiler']) ?></a></td>
                <td><?= e($d['cliente']) ?></td>
                <td><?= e($d['producto']) ?></td>
                <td class="text-end"><?= e($d['cantidad']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php layout_end(); ?>
