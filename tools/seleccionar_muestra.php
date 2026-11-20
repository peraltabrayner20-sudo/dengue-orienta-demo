<?php
// Uso:  php tools/seleccionar_muestra.php [--semilla=2026] [--csv=docs/universo_256.csv]
// 1) Lee la clase_esperada conciliada del universo y carga los 256 casos en caso_prueba.
// 2) Selecciona la muestra estratificada de 154 casos (Tabla 5 de la Fase 3):
//    censo de los estratos pequeños + casos límite forzados + relleno aleatorio con semilla fija.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$semilla = (int) opcion($argv, 'semilla', '2026');
$ruta    = opcion($argv, 'csv', __DIR__ . '/../docs/universo_256.csv');
const N_MUESTRA = 154;

$filas = leerCsv($ruta);
if (count($filas) !== 256) {
    fwrite(STDERR, 'El universo debe tener 256 filas; tiene ' . count($filas) . ".\n");
    exit(1);
}
$casos = [];
foreach ($filas as $r) {
    $bits = '';
    foreach (Dengue\Entrada::BITS as $c) {
        $bits .= $r[$c] === '1' ? '1' : '0';
    }
    $esp = normalizarClase($r['clase_esperada'] ?? '');
    if ($esp === null) {
        fwrite(STDERR, "Fila id_caso={$r['id_caso']}: clase_esperada vacía o no válida. Concilie primero las dos asignaciones.\n");
        exit(1);
    }
    $casos[(int) $r['id_caso']] = ['bits' => $bits, 'clase' => $esp];
}

// Casos límite: un solo signo de gravedad y ningún signo de alarma (6 escenarios).
$limite = [];
foreach ($casos as $id => $c) {
    $a = substr($c['bits'], 0, 4);
    $g = substr($c['bits'], 4, 3);
    if ($a === '0000' && substr_count($g, '1') === 1) {
        $limite[] = $id;
    }
}

mt_srand($semilla);
$sel = [];
$estrato = [1 => 'sin_alarma', 2 => 'con_alarma', 3 => 'grave'];
$porClase = [1 => [], 2 => [], 3 => []];
foreach ($casos as $id => $c) {
    $porClase[$c['clase']][] = $id;
}
$pequenos = count($porClase[1]) + count($porClase[2]);      // censo de estratos pequeños
foreach ([1, 2] as $k) {
    foreach ($porClase[$k] as $id) {
        $sel[$id] = true;
    }
}
foreach ($limite as $id) {
    $sel[$id] = true;                                         // forzados
}
$faltan = N_MUESTRA - count($sel);
$resto  = array_values(array_filter($porClase[3], static fn($id) => !isset($sel[$id])));
for ($i = count($resto) - 1; $i > 0; $i--) {                  // Fisher-Yates con semilla
    $j = mt_rand(0, $i);
    [$resto[$i], $resto[$j]] = [$resto[$j], $resto[$i]];
}
foreach (array_slice($resto, 0, $faltan) as $id) {
    $sel[$id] = true;
}

$pdo = Dengue\Db::pdo();
$pdo->exec('DELETE FROM ejecucion_prueba');
$pdo->exec('DELETE FROM caso_prueba');
$ins = $pdo->prepare('INSERT INTO caso_prueba (id_caso, combinacion_signos, clase_esperada, estrato, en_muestra) VALUES (?, ?, ?, ?, ?)');
foreach ($casos as $id => $c) {
    $ins->execute([$id, $c['bits'], $c['clase'], $estrato[$c['clase']], isset($sel[$id]) ? 1 : 0]);
}

$cuenta = [1 => 0, 2 => 0, 3 => 0];
foreach ($sel as $id => $_) {
    $cuenta[$casos[$id]['clase']]++;
}
printf("Universo: %d casos cargados (semilla %d).\n", count($casos), $semilla);
printf("Estratos del universo : sin alarma=%d, con alarma=%d, grave=%d\n", count($porClase[1]), count($porClase[2]), count($porClase[3]));
printf("Muestra (n=%d)        : sin alarma=%d, con alarma=%d, grave=%d  | casos límite forzados=%d\n",
    count($sel), $cuenta[1], $cuenta[2], $cuenta[3], count($limite));
echo "Meta de la Tabla 5: 2 / 30 / 122 = 154.\n";
