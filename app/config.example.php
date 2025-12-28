<?php
/**
 * Copia este archivo como: app/config.php
 * y rellena tus datos de conexión.
 */
return [
  'db' => [
    'host' => 'localhost',
    'port' => 3307,
    'name' => 'alquiler_gaming',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],

  // Login de respaldo (solo para entorno local / aula)
  // (Siguiente paso: login con tabla propia de usuarios si quieres.)
  'fallback_login' => [
    'user' => 'admin',
    'pass' => 'admin123',
    'role' => 'GERENCIA',
  ],

  'app_name' => 'GamingRent · Backoffice',
  'timezone' => 'Europe/Madrid',
];
