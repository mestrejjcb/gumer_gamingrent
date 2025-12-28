<?php
/**
 * Configuración local (XAMPP) — lista para usar.
 * Si tu MySQL tiene contraseña, rellena 'pass'.
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

  // (Opcional) Ya no se usa en V3: login es con tabla 'usuarios'
  'fallback_login' => [
    'user' => 'admin',
    'pass' => 'admin123',
    'role' => 'GERENCIA',
  ],

  'app_name' => 'GamingRent · Backoffice',
  'timezone' => 'Europe/Madrid',
];
