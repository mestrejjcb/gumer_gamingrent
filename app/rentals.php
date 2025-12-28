<?php
/**
 * Helpers de alquileres (V4) — sin SPs.
 * Mantiene el sentido de negocio: stock, devoluciones, recargos.
 */

function rental_days(string $inicio, string $fin_prev): int {
  try {
    $a = new DateTime($inicio);
    $b = new DateTime($fin_prev);
    $d = (int)$a->diff($b)->format('%r%a');
    return max(1, $d + 1); // incluye el día de inicio
  } catch (Throwable $e) {
    return 1;
  }
}

function recalc_rental(PDO $pdo, int $id_alquiler, ?string $fecha_fin_real = null): array {
  // Cabecera
  $cab = $pdo->prepare("SELECT id_alquiler, fecha_inicio, fecha_fin_prevista, fecha_fin_real, estado
                        FROM alquileres WHERE id_alquiler=?");
  $cab->execute([$id_alquiler]);
  $h = $cab->fetch();
  if (!$h) throw new RuntimeException("Alquiler no encontrado.");

  $inicio = $h['fecha_inicio'];
  $fin_prev = $h['fecha_fin_prevista'];
  $dias = rental_days($inicio, $fin_prev);

  // Líneas
  $st = $pdo->prepare("SELECT cantidad, precio_dia_aplicado, descuento_pct
                       FROM alquiler_detalle WHERE id_alquiler=?");
  $st->execute([$id_alquiler]);
  $lines = $st->fetchAll();

  $total_base = 0.0;
  $daily_total = 0.0; // para recargo por día de retraso
  foreach ($lines as $r) {
    $qty = (int)$r['cantidad'];
    $price = (float)$r['precio_dia_aplicado'];
    $disc = (float)$r['descuento_pct'];
    $factor = (100.0 - $disc) / 100.0;
    $daily_total += ($qty * $price * $factor);
    $total_base += ($qty * $price * $dias * $factor);
  }

  $recargo = 0.0;
  $late_days = 0;

  $real = $fecha_fin_real ?? ($h['fecha_fin_real'] ?? null);
  if ($real) {
    try {
      $dPrev = new DateTime($fin_prev);
      $dReal = new DateTime($real);
      $late_days = (int)$dPrev->diff($dReal)->format('%r%a');
      if ($late_days > 0) {
        $recargo = $late_days * $daily_total;
      }
    } catch (Throwable $e) {}
  }

  $total_final = $total_base + $recargo;

  $up = $pdo->prepare("UPDATE alquileres SET total_base=?, recargo=?, total_final=? WHERE id_alquiler=?");
  $up->execute([$total_base, $recargo, $total_final, $id_alquiler]);

  return [
    'dias' => $dias,
    'total_base' => $total_base,
    'recargo' => $recargo,
    'total_final' => $total_final,
    'late_days' => $late_days,
  ];
}

function sum_pagos(PDO $pdo, int $id_alquiler): float {
  $st = $pdo->prepare("SELECT COALESCE(SUM(importe),0) AS s FROM pagos WHERE id_alquiler=?");
  $st->execute([$id_alquiler]);
  return (float)($st->fetchColumn() ?? 0);
}
