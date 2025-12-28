<?php
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $to): never {
  header("Location: $to");
  exit;
}

function flash_set(string $type, string $msg): void {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
  if (!isset($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $f;
}

function fmt_eur($n): string {
  if ($n === null) return "0,00 €";
  return number_format((float)$n, 2, ',', '.') . " €";
}

function fmt_date($s): string {
  if (!$s) return "";
  try {
    $dt = new DateTime($s);
    return $dt->format('d/m/Y');
  } catch (Throwable $e) {
    return (string)$s;
  }
}
