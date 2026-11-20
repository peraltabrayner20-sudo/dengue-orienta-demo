<?php
// Uso:  php tools/sus.php docs/plantillas/sus_respuestas.csv
// Calcula el puntaje SUS (Brooke, 1996): ítems impares (valor - 1), pares (5 - valor), suma x 2,5.
// El CSV debe tener las columnas participante,q1,...,q10 con valores de 1 a 5. Meta: media >= 68.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$ruta = $argv[1] ?? __DIR__ . '/../docs/plantillas/sus_respuestas.csv';
$filas = leerCsv($ruta);
if (!$filas) {
    fwrite(STDERR, "El archivo no tiene respuestas todavía.\n");
    exit(1);
}
$punt = [];
foreach ($filas as $f) {
    $s = 0;
    for ($i = 1; $i <= 10; $i++) {
        $v = (int) ($f["q$i"] ?? 0);
        if ($v < 1 || $v > 5) {
            fwrite(STDERR, "Participante {$f['participante']}: q$i debe ser un valor de 1 a 5.\n");
            exit(1);
        }
        $s += $i % 2 === 1 ? $v - 1 : 5 - $v;
    }
    $punt[$f['participante']] = $s * 2.5;
    printf("%-6s SUS = %.1f\n", $f['participante'], $s * 2.5);
}
$n = count($punt);
$media = array_sum($punt) / $n;
$sd = $n > 1 ? sqrt(array_sum(array_map(static fn($x) => ($x - $media) ** 2, $punt)) / ($n - 1)) : 0.0;
printf("\nParticipantes: %d · SUS medio: %.1f · desviación estándar: %.1f · meta >= 68: %s\n", $n, $media, $sd, $media >= 68 ? 'CUMPLE' : 'NO CUMPLE');
