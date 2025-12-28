<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $configPath = __DIR__ . '/config.php';
  if (!file_exists($configPath)) {
    header("Location: ../setup.php"); exit;
  }
  $cfg = require $configPath;

  date_default_timezone_set($cfg['timezone'] ?? 'Europe/Madrid');

  $port = $cfg['db']['port'] ?? 3306;
  $dsn = "mysql:host={$cfg['db']['host']};port={$port};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}";
  $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function table_exists(string $table): bool {
  $stmt = db()->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
}
