<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$APP = ['name' => 'GamingRent · Backoffice'];

try {
  $cfgPath = __DIR__ . '/config.php';
  if (file_exists($cfgPath)) {
    $cfg = require $cfgPath;
    $APP['name'] = $cfg['app_name'] ?? $APP['name'];
  }
} catch (Throwable $e) {
  // se mostrará el error en la UI si procede
}
