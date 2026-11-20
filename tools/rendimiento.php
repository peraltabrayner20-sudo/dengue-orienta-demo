<?php
// Uso:  php tools/rendimiento.php [--base=http://localhost/dengue-orienta/public] [--n=20]
// CP-09 / RNF-02: n evaluaciones consecutivas; el percentil 95 debe ser <= 3 s.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$base = rtrim((string) opcion($argv, 'base', 'http://127.0.0.1:8080'), '/');
$n    = (int) opcion($argv, 'n', '20');
$desde = ahoraBd();
$t = [];
for ($i = 0; $i < $n; $i++) {
    $r = evaluarPorHttp($base, camposDesdeBits('01000000', '3-7'));
    if ($r['clase'] === null) {
        fwrite(STDERR, "Falló la solicitud $i (HTTP {$r['codigo']}) {$r['error']}\n");
        exit(1);
    }
    $t[] = $r['ms'];
}
marcarPruebas($desde);
printf("Solicitudes: %d · media %.1f ms · mediana %.1f ms · p95 %.1f ms · máx %.1f ms\n",
    $n, array_sum($t) / $n, mediana($t), percentil($t, 95), max($t));
printf("RNF-02 (p95 <= 3000 ms): %s\n", percentil($t, 95) <= 3000 ? 'CUMPLE' : 'NO CUMPLE');
