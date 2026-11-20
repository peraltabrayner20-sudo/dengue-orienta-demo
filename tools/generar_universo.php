<?php
// Uso:  php tools/generar_universo.php
// Genera docs/universo_256.csv: las 2^8 = 256 combinaciones de A1..A4, G1..G3 y R.
// Las columnas clase_inv1 y clase_inv2 deben llenarlas DOS integrantes de forma INDEPENDIENTE,
// aplicando el protocolo del INS (2024) a cada fila. Luego se concilian en clase_esperada.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$ruta = __DIR__ . '/../docs/universo_256.csv';
$f = fopen($ruta, 'w');
fwrite($f, "\xEF\xBB\xBF");
fputcsv($f, array_merge(['id_caso'], Dengue\Entrada::BITS, ['clase_inv1', 'clase_inv2', 'clase_esperada']));
for ($i = 0; $i < 256; $i++) {
    $bits = str_pad(decbin($i), 8, '0', STR_PAD_LEFT);
    fputcsv($f, array_merge([$i + 1], str_split($bits), ['', '', '']));
}
fclose($f);
echo "Generado $ruta (256 escenarios).\n";
echo "Valores aceptados en las clases: sin / alarma / grave (o 1 / 2 / 3).\n";
