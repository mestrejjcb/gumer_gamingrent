<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('alquileres');
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/rentals.php';

layout_start('Detalle de alquiler', 'alquileres');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo '<div class="alert alert-warning mt-3">ID no válido.</div>'; layout_end(); exit; }

$pdo = db();
$err = null;
$info = null;

function go_back(int $id): void { redirect('alquiler.php?id='.$id); }

try {
  // Procesar acciones
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
      require_role(['GERENCIA','MOSTRADOR']);
      $id_producto = (int)($_POST['id_producto'] ?? 0);
      $cantidad = (int)($_POST['cantidad'] ?? 1);
      $descuento = (float)($_POST['descuento_pct'] ?? 0);

      if ($id_producto<=0 || $cantidad<=0) throw new RuntimeException("Producto/cantidad inválidos.");
      if ($descuento<0 || $descuento>100) throw new RuntimeException("Descuento inválido.");

      $pdo->beginTransaction();

      $h = $pdo->prepare("SELECT estado FROM alquileres WHERE id_alquiler=? FOR UPDATE");
      $h->execute([$id]);
      $hh = $h->fetch();
      if (!$hh) throw new RuntimeException("Alquiler no existe.");
      if ($hh['estado'] !== 'ABIERTO') throw new RuntimeException("El alquiler está cerrado.");

      $p = $pdo->prepare("SELECT precio_dia, stock_disponible FROM productos WHERE id_producto=? FOR UPDATE");
      $p->execute([$id_producto]);
      $pp = $p->fetch();
      if (!$pp) throw new RuntimeException("Producto no existe.");
      if ((int)$pp['stock_disponible'] < $cantidad) throw new RuntimeException("Stock insuficiente.");

      // ¿Existe línea?
      $l = $pdo->prepare("SELECT cantidad, precio_dia_aplicado, descuento_pct FROM alquiler_detalle
                          WHERE id_alquiler=? AND id_producto=? FOR UPDATE");
      $l->execute([$id,$id_producto]);
      $line = $l->fetch();

      $precio = (float)$pp['precio_dia'];

      if ($line) {
        $newQty = (int)$line['cantidad'] + $cantidad;
        $u = $pdo->prepare("UPDATE alquiler_detalle SET cantidad=?, precio_dia_aplicado=?, descuento_pct=? WHERE id_alquiler=? AND id_producto=?");
        $u->execute([$newQty, $precio, $descuento, $id, $id_producto]);
      } else {
        $ins = $pdo->prepare("INSERT INTO alquiler_detalle(id_alquiler,id_producto,cantidad,precio_dia_aplicado,descuento_pct)
                              VALUES(?,?,?,?,?)");
        $ins->execute([$id,$id_producto,$cantidad,$precio,$descuento]);
      }

      $upS = $pdo->prepare("UPDATE productos SET stock_disponible = stock_disponible - ? WHERE id_producto=?");
      $upS->execute([$cantidad,$id_producto]);

      $pdo->commit();
      recalc_rental($pdo, $id, null);
      flash_set('success','Producto añadido.');
      go_back($id);
    }

    if ($action === 'update_line') {
      require_role(['GERENCIA','MOSTRADOR']);
      $id_producto = (int)($_POST['id_producto'] ?? 0);
      $cantidad = (int)($_POST['cantidad'] ?? 1);
      $descuento = (float)($_POST['descuento_pct'] ?? 0);
      if ($id_producto<=0 || $cantidad<=0) throw new RuntimeException("Datos inválidos.");

      $pdo->beginTransaction();

      $h = $pdo->prepare("SELECT estado FROM alquileres WHERE id_alquiler=? FOR UPDATE");
      $h->execute([$id]);
      $hh = $h->fetch();
      if ($hh['estado'] !== 'ABIERTO') throw new RuntimeException("El alquiler está cerrado.");

      $l = $pdo->prepare("SELECT cantidad FROM alquiler_detalle WHERE id_alquiler=? AND id_producto=? FOR UPDATE");
      $l->execute([$id,$id_producto]);
      $line = $l->fetch();
      if (!$line) throw new RuntimeException("Línea no encontrada.");

      $oldQty = (int)$line['cantidad'];
      $delta = $cantidad - $oldQty;

      if ($delta > 0) {
        $p = $pdo->prepare("SELECT stock_disponible, precio_dia FROM productos WHERE id_producto=? FOR UPDATE");
        $p->execute([$id_producto]);
        $pp = $p->fetch();
        if ((int)$pp['stock_disponible'] < $delta) throw new RuntimeException("Stock insuficiente para aumentar cantidad.");
        $pdo->prepare("UPDATE productos SET stock_disponible = stock_disponible - ? WHERE id_producto=?")->execute([$delta,$id_producto]);
        $precio = (float)$pp['precio_dia'];
      } else {
        // devolvemos stock si baja
        $pdo->prepare("UPDATE productos SET stock_disponible = stock_disponible + ? WHERE id_producto=?")->execute([abs($delta),$id_producto]);
        $precio = (float)$pdo->query("SELECT precio_dia FROM productos WHERE id_producto=".(int)$id_producto)->fetchColumn();
      }

      $u = $pdo->prepare("UPDATE alquiler_detalle SET cantidad=?, precio_dia_aplicado=?, descuento_pct=? WHERE id_alquiler=? AND id_producto=?");
      $u->execute([$cantidad, $precio, $descuento, $id, $id_producto]);

      $pdo->commit();
      recalc_rental($pdo, $id, null);
      flash_set('success','Línea actualizada.');
      go_back($id);
    }

    if ($action === 'remove_line') {
      require_role(['GERENCIA','MOSTRADOR']);
      $id_producto = (int)($_POST['id_producto'] ?? 0);

      $pdo->beginTransaction();
      $h = $pdo->prepare("SELECT estado FROM alquileres WHERE id_alquiler=? FOR UPDATE");
      $h->execute([$id]);
      $hh = $h->fetch();
      if ($hh['estado'] !== 'ABIERTO') throw new RuntimeException("El alquiler está cerrado.");

      $l = $pdo->prepare("SELECT cantidad FROM alquiler_detalle WHERE id_alquiler=? AND id_producto=? FOR UPDATE");
      $l->execute([$id,$id_producto]);
      $line = $l->fetch();
      if (!$line) throw new RuntimeException("Línea no encontrada.");

      $qty = (int)$line['cantidad'];
      $pdo->prepare("DELETE FROM alquiler_detalle WHERE id_alquiler=? AND id_producto=?")->execute([$id,$id_producto]);
      $pdo->prepare("UPDATE productos SET stock_disponible = stock_disponible + ? WHERE id_producto=?")->execute([$qty,$id_producto]);

      $pdo->commit();
      recalc_rental($pdo, $id, null);
      flash_set('info','Línea eliminada.');
      go_back($id);
    }

    if ($action === 'close') {
      require_role(['GERENCIA','MOSTRADOR']);
      $fecha_real = $_POST['fecha_fin_real'] ?? date('Y-m-d');

      $pdo->beginTransaction();

      $h = $pdo->prepare("SELECT estado FROM alquileres WHERE id_alquiler=? FOR UPDATE");
      $h->execute([$id]);
      $hh = $h->fetch();
      if ($hh['estado'] !== 'ABIERTO') throw new RuntimeException("Ya está cerrado.");

      // devolver stock de todas las líneas
      $lines = $pdo->prepare("SELECT id_producto, cantidad FROM alquiler_detalle WHERE id_alquiler=? FOR UPDATE");
      $lines->execute([$id]);
      $all = $lines->fetchAll();
      foreach ($all as $r) {
        $pdo->prepare("UPDATE productos SET stock_disponible = LEAST(stock_total, stock_disponible + ?) WHERE id_producto=?")
            ->execute([(int)$r['cantidad'], (int)$r['id_producto']]);
      }

      $pdo->prepare("UPDATE alquileres SET estado='CERRADO', fecha_fin_real=? WHERE id_alquiler=?")->execute([$fecha_real,$id]);

      $pdo->commit();

      recalc_rental($pdo, $id, $fecha_real);
      flash_set('success','Alquiler cerrado. Stock devuelto y recargo calculado si aplica.');
      go_back($id);
    }

    if ($action === 'add_payment') {
      require_role(['GERENCIA','MOSTRADOR']);
      $importe = (float)($_POST['importe'] ?? 0);
      $metodo = $_POST['metodo'] ?? 'TARJETA';
      $concepto = trim($_POST['concepto'] ?? 'Pago alquiler');

      if ($importe <= 0) throw new RuntimeException("Importe inválido.");

      $st = $pdo->prepare("INSERT INTO pagos(id_alquiler, importe, metodo, concepto) VALUES(?,?,?,?)");
      $st->execute([$id, $importe, $metodo, $concepto]);

      flash_set('success','Pago registrado.');
      go_back($id);
    }
  }

  // Cabecera
  $cab = $pdo->prepare("SELECT a.*, c.nombre AS cliente, e.nombre AS empleado
                        FROM alquileres a
                        JOIN clientes c ON c.id_cliente=a.id_cliente
                        JOIN empleados e ON e.id_empleado=a.id_empleado
                        WHERE a.id_alquiler=?");
  $cab->execute([$id]);
  $h = $cab->fetch();
  if (!$h) throw new RuntimeException("No existe ese alquiler.");

  // Líneas
  $det = $pdo->prepare("SELECT d.id_producto, p.nombre, p.plataforma, d.cantidad, d.precio_dia_aplicado, d.descuento_pct
                        FROM alquiler_detalle d
                        JOIN productos p ON p.id_producto=d.id_producto
                        WHERE d.id_alquiler=?
                        ORDER BY p.nombre");
  $det->execute([$id]);
  $lines = $det->fetchAll();

  // Productos para añadir
  $productos = $pdo->query("SELECT id_producto, nombre, plataforma, stock_disponible, precio_dia
                            FROM productos
                            WHERE activo=1 AND stock_disponible>0
                            ORDER BY nombre")->fetchAll();

  // Totales
  $t = recalc_rental($pdo, $id, $h['fecha_fin_real'] ?? null);
  $pagado = sum_pagos($pdo, $id);
  $pendiente = max(0, (float)$t['total_final'] - $pagado);

} catch (Throwable $e) { $err = $e->getMessage(); }
?>

<div class="d-flex justify-content-between align-items-center mt-3">
  <div>
    <div class="h5 mb-0">Alquiler #<?= e((string)$id) ?> · <span class="badge text-bg-light border"><?= e($h['estado'] ?? '') ?></span></div>
    <div class="text-secondary small">Cliente: <b><?= e($h['cliente'] ?? '') ?></b> · Gestiona: <?= e($h['empleado'] ?? '') ?></div>
  </div>
  <a class="btn btn-outline-secondary" href="alquileres.php">Volver</a>
</div>

<?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3 mt-1">
  <div class="col-lg-7">
    <div class="card-soft p-3">
      <div class="fw-bold">Datos</div>
      <div class="row g-2 mt-1 small">
        <div class="col-md-4"><span class="text-secondary">Inicio:</span> <?= e(fmt_date($h['fecha_inicio'] ?? '')) ?></div>
        <div class="col-md-4"><span class="text-secondary">Prevista:</span> <?= e(fmt_date($h['fecha_fin_prevista'] ?? '')) ?></div>
        <div class="col-md-4"><span class="text-secondary">Real:</span> <?= e(fmt_date($h['fecha_fin_real'] ?? '')) ?></div>
        <div class="col-md-4"><span class="text-secondary">Días:</span> <?= e((string)($t['dias'] ?? 1)) ?></div>
        <div class="col-md-4"><span class="text-secondary">Depósito:</span> <?= e(fmt_eur($h['deposito'] ?? 0)) ?></div>
        <div class="col-md-4"><span class="text-secondary">Recargo:</span> <?= e(fmt_eur($t['recargo'] ?? 0)) ?></div>
      </div>
      <?php if (!empty($h['observaciones'])): ?>
        <div class="mt-2 small"><span class="text-secondary">Obs:</span> <?= e($h['observaciones']) ?></div>
      <?php endif; ?>
    </div>

    <div class="card-soft p-3 mt-3">
      <div class="d-flex justify-content-between align-items-center">
        <div class="fw-bold">Productos del alquiler</div>
        <?php if (($h['estado'] ?? '')==='ABIERTO' && in_array(current_role(), ['GERENCIA','MOSTRADOR'], true)): ?>
          <span class="text-secondary small">Stock reservado mientras esté abierto</span>
        <?php endif; ?>
      </div>

      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">€/día</th><th class="text-end">Desc.%</th><th style="width:220px;"></th></tr>
          </thead>
          <tbody>
            <?php foreach($lines as $r): ?>
              <tr>
                <td><?= e($r['nombre'].' · '.$r['plataforma']) ?></td>
                <td class="text-end"><?= e((string)$r['cantidad']) ?></td>
                <td class="text-end"><?= e(fmt_eur($r['precio_dia_aplicado'])) ?></td>
                <td class="text-end"><?= e((string)$r['descuento_pct']) ?></td>
                <td>
                  <?php if (($h['estado'] ?? '')==='ABIERTO' && in_array(current_role(), ['GERENCIA','MOSTRADOR'], true)): ?>
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="action" value="update_line">
                      <input type="hidden" name="id_producto" value="<?= e((string)$r['id_producto']) ?>">
                      <input class="form-control form-control-sm" type="number" name="cantidad" min="1" value="<?= e((string)$r['cantidad']) ?>" style="max-width:80px;">
                      <input class="form-control form-control-sm" type="number" step="0.01" name="descuento_pct" min="0" max="100" value="<?= e((string)$r['descuento_pct']) ?>" style="max-width:90px;">
                      <button class="btn btn-sm btn-outline-primary">Guardar</button>
                    </form>
                    <form method="post" class="mt-2" onsubmit="return confirm('¿Eliminar línea?');">
                      <input type="hidden" name="action" value="remove_line">
                      <input type="hidden" name="id_producto" value="<?= e((string)$r['id_producto']) ?>">
                      <button class="btn btn-sm btn-outline-danger w-100">Eliminar</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!$lines): ?>
          <div class="text-secondary small mt-2">Aún no hay productos añadidos.</div>
        <?php endif; ?>
      </div>

      <?php if (($h['estado'] ?? '')==='ABIERTO' && in_array(current_role(), ['GERENCIA','MOSTRADOR'], true)): ?>
      <hr>
      <div class="fw-bold">Añadir producto</div>
      <form method="post" class="row g-2 align-items-end mt-1">
        <input type="hidden" name="action" value="add_item">
        <div class="col-md-7">
          <label class="form-label">Producto (solo con stock)</label>
          <select class="form-select" name="id_producto" required>
            <option value="">-- Selecciona --</option>
            <?php foreach($productos as $p): ?>
              <option value="<?= e((string)$p['id_producto']) ?>">
                <?= e($p['nombre'].' · '.$p['plataforma'].' · Stock: '.$p['stock_disponible'].' · '.fmt_eur($p['precio_dia']).'/día') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Cantidad</label>
          <input class="form-control" type="number" name="cantidad" min="1" value="1" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Desc.%</label>
          <input class="form-control" type="number" step="0.01" name="descuento_pct" min="0" max="100" value="0">
        </div>
        <div class="col-md-1 d-grid">
          <button class="btn btn-primary">+</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-soft p-3">
      <div class="fw-bold">Resumen económico</div>
      <div class="d-flex justify-content-between mt-2"><span class="text-secondary">Total base</span><b><?= e(fmt_eur($t['total_base'] ?? 0)) ?></b></div>
      <div class="d-flex justify-content-between"><span class="text-secondary">Recargo</span><b><?= e(fmt_eur($t['recargo'] ?? 0)) ?></b></div>
      <div class="d-flex justify-content-between mt-2" style="font-size:18px;">
        <span>Total final</span><b><?= e(fmt_eur($t['total_final'] ?? 0)) ?></b>
      </div>
      <div class="d-flex justify-content-between mt-2"><span class="text-secondary">Pagado</span><b><?= e(fmt_eur($pagado)) ?></b></div>
      <div class="d-flex justify-content-between"><span class="text-secondary">Pendiente</span><b><?= e(fmt_eur($pendiente)) ?></b></div>
    </div>

    <div class="card-soft p-3 mt-3">
      <div class="fw-bold">Pagos</div>
      <?php
        $pags = $pdo->prepare("SELECT fecha_pago, importe, metodo, concepto FROM pagos WHERE id_alquiler=? ORDER BY fecha_pago DESC");
        $pags->execute([$id]);
        $plist = $pags->fetchAll();
      ?>
      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Fecha</th><th>Método</th><th>Concepto</th><th class="text-end">Importe</th></tr></thead>
          <tbody>
            <?php foreach($plist as $p): ?>
              <tr>
                <td><?= e((string)$p['fecha_pago']) ?></td>
                <td><?= e((string)$p['metodo']) ?></td>
                <td><?= e((string)$p['concepto']) ?></td>
                <td class="text-end"><?= e(fmt_eur($p['importe'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!$plist): ?><div class="text-secondary small mt-2">Sin pagos registrados.</div><?php endif; ?>
      </div>

      <?php if (in_array(current_role(), ['GERENCIA','MOSTRADOR'], true)): ?>
      <hr>
      <div class="fw-bold">Registrar pago</div>
      <form method="post" class="row g-2 align-items-end mt-1">
        <input type="hidden" name="action" value="add_payment">
        <div class="col-md-4">
          <label class="form-label">Importe</label>
          <input class="form-control" type="number" step="0.01" name="importe" value="<?= e(number_format($pendiente,2,'.','')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Método</label>
          <select class="form-select" name="metodo">
            <option value="TARJETA">TARJETA</option>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="BIZUM">BIZUM</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Concepto</label>
          <input class="form-control" name="concepto" value="Pago alquiler">
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-outline-primary">Guardar pago</button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <?php if (($h['estado'] ?? '')==='ABIERTO' && in_array(current_role(), ['GERENCIA','MOSTRADOR'], true)): ?>
    <div class="card-soft p-3 mt-3">
      <div class="fw-bold">Cerrar alquiler</div>
      <div class="text-secondary small">Al cerrar: se devuelve stock y se calcula recargo si hay retraso.</div>
      <form method="post" class="mt-2">
        <input type="hidden" name="action" value="close">
        <label class="form-label">Fecha fin real</label>
        <input class="form-control" type="date" name="fecha_fin_real" value="<?= e(date('Y-m-d')) ?>" required>
        <button class="btn btn-danger w-100 mt-2" onclick="return confirm('¿Cerrar alquiler?');">Cerrar alquiler</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php layout_end(); ?>
