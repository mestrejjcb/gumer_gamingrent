<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('clientes');
require_once __DIR__ . '/app/layout.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('clientes.php');

$err = null;

try {
  $st = db()->prepare("SELECT * FROM clientes WHERE id_cliente=?");
  $st->execute([$id]);
  $c = $st->fetch();
  if (!$c) { flash_set('warning','Cliente no encontrado.'); redirect('clientes.php'); }
} catch (Throwable $e) {
  $err = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
  $nombre = trim($_POST['nombre'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $puntos = (int)($_POST['puntos_fidelidad'] ?? 0);

  if ($nombre === '') $err = "El nombre es obligatorio.";

  if (!$err) {
    try {
      $up = db()->prepare("UPDATE clientes SET nombre=?, email=?, telefono=?, puntos_fidelidad=? WHERE id_cliente=?");
      $up->execute([$nombre,$email,$telefono,$puntos,$id]);
      flash_set('success','Cliente actualizado.');
      redirect('clientes.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
  }
}
layout_start('Editar cliente', 'clientes');
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Editar cliente #<?= e((string)$id) ?></div>
      <div class="text-secondary small">Mantener datos correctos evita incidencias en alquileres</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="clientes.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <div class="mb-2">
      <label class="form-label">Nombre *</label>
      <input class="form-control" name="nombre" value="<?= e($c['nombre']) ?>" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Email</label>
      <input class="form-control" name="email" type="email" value="<?= e($c['email'] ?? '') ?>">
    </div>
    <div class="mb-2">
      <label class="form-label">Teléfono</label>
      <input class="form-control" name="telefono" value="<?= e($c['telefono'] ?? '') ?>">
    </div>
    <div class="mb-2">
      <label class="form-label">Puntos fidelidad</label>
      <input class="form-control" name="puntos_fidelidad" type="number" value="<?= e((string)$c['puntos_fidelidad']) ?>">
    </div>
    <button class="btn btn-primary w-100">Guardar cambios</button>
  </form>
</div>
<?php layout_end(); ?>
