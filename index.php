<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('dashboard');
require_once __DIR__ . '/app/layout.php';
?>
<?php
layout_start('Dashboard', 'dashboard');

try {
  $pdo = db();

  $alquileres_abiertos = (int)$pdo->query("SELECT COUNT(*) FROM alquileres WHERE estado='ABIERTO'")->fetchColumn();
  $clientes_total = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
  $productos_bajo_stock = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE activo=1 AND stock_disponible <= 1")->fetchColumn();
  $ingresos_mes = $pdo->query("SELECT COALESCE(SUM(importe),0) FROM pagos WHERE YEAR(fecha_pago)=YEAR(CURDATE()) AND MONTH(fecha_pago)=MONTH(CURDATE())")->fetchColumn();

  $ultimos_alq = $pdo->query("SELECT a.id_alquiler, c.nombre AS cliente, a.estado, a.fecha_inicio, a.fecha_fin_prevista, a.total_final
                             FROM alquileres a
                             JOIN clientes c ON c.id_cliente=a.id_cliente
                             ORDER BY a.id_alquiler DESC
                             LIMIT 8")->fetchAll();

  $top_prod = [];
  if (table_exists('vw_productos_ranking')) {
    $top_prod = $pdo->query("
        SELECT producto, plataforma, unidades_alquiladas
        FROM vw_productos_ranking
        ORDER BY unidades_alquiladas DESC, ingresos_estimados DESC
        LIMIT 8
    ")->fetchAll();
}


} catch (Throwable $e) {
  echo '<div class="alert alert-danger mt-3"><b>Error de conexión:</b> ' . e($e->getMessage()) . '</div>';
  echo '<div class="card-soft p-3 mt-3"><div class="text-secondary">Revisa <code>app/config.php</code> y que la BD <code>alquiler_gaming</code> exista. Ejecuta el SQL de <code>sql/alquiler_gaming_opcionA_fix.sql</code>.</div></div>';
  layout_end();
  exit;
}
?>

<div class="mt-3 kpi">
  <div class="kpi-box">
    <div class="label">Alquileres abiertos</div>
    <div class="value"><?= e((string)$alquileres_abiertos) ?></div>
    <div class="tag">Operativa mostrador</div>
  </div>
  <div class="kpi-box">
    <div class="label">Clientes</div>
    <div class="value"><?= e((string)$clientes_total) ?></div>
    <div class="tag">Base de clientes</div>
  </div>
  <div class="kpi-box">
    <div class="label">Productos en bajo stock (≤ 1)</div>
    <div class="value"><?= e((string)$productos_bajo_stock) ?></div>
    <div class="tag">Decisión gerencia</div>
  </div>
  <div class="kpi-box">
    <div class="label">Ingresos del mes</div>
    <div class="value"><?= e(fmt_eur($ingresos_mes)) ?></div>
    <div class="tag">Caja</div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-lg-7">
    <div class="card-soft p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-bold">Últimos alquileres</div>
          <div class="text-secondary small">Vista rápida para control y seguimiento</div>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="alquileres.php">Ver todos</a>
      </div>

      <div class="table-responsive mt-3">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>ID</th><th>Cliente</th><th>Estado</th><th>Inicio</th><th>Fin previsto</th><th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($ultimos_alq as $r): ?>
              <tr>
                <td><a href="alquiler.php?id=<?= e($r['id_alquiler']) ?>"><?= e($r['id_alquiler']) ?></a></td>
                <td><?= e($r['cliente']) ?></td>
                <td><span class="badge text-bg-<?= $r['estado']==='ABIERTO'?'warning':'success' ?>"><?= e($r['estado']) ?></span></td>
                <td><?= e(fmt_date($r['fecha_inicio'])) ?></td>
                <td><?= e(fmt_date($r['fecha_fin_prevista'])) ?></td>
                <td class="text-end"><?= e(fmt_eur($r['total_final'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-soft p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-bold">Top productos (demanda)</div>
          <div class="text-secondary small">Basado en vw_productos_ranking</div>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="reportes.php">Reportes</a>
      </div>

      <?php if (!$top_prod): ?>
        <div class="alert alert-light border mt-3 mb-0">
          No hay datos del ranking o la vista no existe.
        </div>
      <?php else: ?>
        <div class="table-responsive mt-3">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Producto</th><th>Plataforma</th><th class="text-end">Alquilado</th></tr></thead>
            <tbody>
              <?php foreach($top_prod as $p): ?>
                <tr>
                  <td><?= e($p['producto']) ?></td>
                  <td><?= e($p['plataforma']) ?></td>
                  <td class="text-end"><b><?= e((string)$p['unidades_alquiladas']) ?></b></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card-soft p-3 mt-3">
      <div class="fw-bold">Acciones rápidas</div>
      <div class="text-secondary small">Atajos para practicar procedimientos</div>
      <div class="d-grid gap-2 mt-3">
        <a class="btn btn-outline-primary" href="acciones.php">Abrir alquiler / Añadir producto / Devolver / Pago</a>
        <a class="btn btn-outline-secondary" href="productos.php">Revisar stock</a>
      </div>
    </div>
  </div>
</div>

<?php layout_end(); ?>
