<?php
// Uso:  php tools/kappa.php [docs/universo_256.csv]
// Calcula el índice kappa de Cohen entre clase_inv1 y clase_inv2 y lista las discrepancias (Tabla 7: meta >= 0,80).
declare(strict_types=1);
require __DIR__ . '/lib.php';

$filas = leerCsv($argv[1] ?? __DIR__ . '/../docs/universo_256.csv');
$a = $b = [];
$disc = [];
foreach ($filas as $r) {
    $x = normalizarClase($r['clase_inv1'] ?? '');
    $y = normalizarClase($r['clase_inv2'] ?? '');
    if ($x === null || $y === null) {
        fwrite(STDERR, "Fila id_caso={$r['id_caso']}: falta o no se entiende una de las dos asignaciones.\n");
        exit(1);
    }
    $a[] = $x;
    $b[] = $y;
    if ($x !== $y) {
        $disc[] = $r['id_caso'];
    }
}
$n = count($a);
$acuerdo = $n - count($disc);
$po = $acuerdo / $n;
$pe = 0.0;
foreach ([1, 2, 3] as $k) {
    $pe += (count(array_keys($a, $k, true)) / $n) * (count(array_keys($b, $k, true)) / $n);
}
$kappa = $pe >= 1.0 ? 1.0 : ($po - $pe) / (1 - $pe);

printf("Casos comparados: %d\nAcuerdo observado: %d (%.1f %%)\nÍndice kappa de Cohen: %.3f  (meta >= 0,80: %s)\n",
    $n, $acuerdo, $po * 100, $kappa, $kappa >= 0.80 ? 'CUMPLE' : 'NO CUMPLE');
if ($disc) {
    echo 'Discrepancias (id_caso) para conciliar con el texto del protocolo: ' . implode(', ', $disc) . "\n";
}
