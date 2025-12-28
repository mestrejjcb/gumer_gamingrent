<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
require_access('clientes');
require_once __DIR__ . '/app/layout.php';

layout_start('Nuevo cliente', 'clientes');

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');

  if ($nombre === '') $err = "El nombre es obligatorio.";

  if (!$err) {
    try {
      $st = db()->prepare("INSERT INTO clientes(nombre,email,telefono,fecha_alta,puntos_fidelidad) VALUES(?,?,?,CURDATE(),0)");
      $st->execute([$nombre,$email,$telefono]);
      flash_set('success', 'Cliente creado correctamente.');
      redirect('clientes.php');
    } catch (Throwable $e) {
      $err = $e->getMessage();
    }
  }
}
?>
<div class="card-soft p-3 mt-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-bold">Alta de cliente</div>
      <div class="text-secondary small">Operativa: registrar cliente para poder alquilar</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="clientes.php">Volver</a>
  </div>

  <?php if ($err): ?><div class="alert alert-warning mt-3"><?= e($err) ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <div class="mb-2">
      <label class="form-label">Nombre *</label>
      <input class="form-control" name="nombre" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Email</label>
      <input class="form-control" name="email" type="email">
    </div>
    <div class="mb-2">
      <label class="form-label">Teléfono</label>
      <input class="form-control" name="telefono">
    </div>
    <button class="btn btn-primary w-100">Guardar</button>
  </form>
</div>
<?php layout_end(); ?>
