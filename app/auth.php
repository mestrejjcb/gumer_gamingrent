<?php
require_once __DIR__ . '/db.php';

function is_logged_in(): bool { return isset($_SESSION['user']); }

function require_login(): void {
  if (!is_logged_in()) redirect('login.php');
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function current_role(): string {
  $u = current_user();
  return $u['rol'] ?? 'MOSTRADOR';
}

function require_role(array $roles): void {
  $r = current_role();
  if (!in_array($r, $roles, true)) {
    flash_set('warning', 'No tienes permisos para acceder a esa sección.');
    redirect('index.php');
  }
}

/**
 * Mapa simple de permisos por sección (pensado para FP / aula).
 */
function can_access(string $section): bool {
  $r = current_role();
  if ($r === 'GERENCIA') return true;

  $map = [
    'MOSTRADOR' => ['dashboard','clientes','productos','alquileres','pagos','empleados','reportes'],
    'TECNICO'   => ['dashboard','productos','reportes','empleados'],
  ];

  return in_array($section, $map[$r] ?? [], true);
}

function require_access(string $section): void {
  if (!can_access($section)) {
    flash_set('warning', 'Acceso restringido por tu rol.');
    redirect('index.php');
  }
}

function logout(): void { unset($_SESSION['user']); }

function login_attempt(string $user, string $pass): bool {
  $user = trim($user);
  if ($user === '' || $pass === '') return false;

  $pdo = db();
  if (!table_exists('usuarios')) {
    redirect('install.php');
  }

  $st = $pdo->prepare("SELECT id_usuario, usuario, nombre, password_hash, rol, activo
                       FROM usuarios
                       WHERE usuario=? LIMIT 1");
  $st->execute([$user]);
  $row = $st->fetch();

  if (!$row) return false;
  if ((int)$row['activo'] !== 1) return false;
  if (!password_verify($pass, $row['password_hash'])) return false;

  $_SESSION['user'] = [
    'id' => (int)$row['id_usuario'],
    'usuario' => $row['usuario'],
    'nombre' => $row['nombre'],
    'rol' => $row['rol'],
    'source' => 'db',
  ];
  return true;
}
