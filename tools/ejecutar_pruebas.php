<?php
// Uso:  php tools/ejecutar_pruebas.php --base=http://localhost/dengue-orienta/public --tipo=muestra|regresion
// Envía cada caso al servidor por HTTP recorriendo el flujo real (aviso, aceptación, formulario, evaluación),
// compara la clase obtenida con la esperada, guarda la ejecución y calcula los indicadores de la Tabla 7.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$base = rtrim((string) opcion($argv, 'base', 'http://127.0.0.1:8080'), '/');
$tipo = (string) opcion($argv, 'tipo', 'muestra');
if (!in_array($tipo, ['muestra', 'regresion'], true)) {
    fwrite(STDERR, "--tipo debe ser muestra o regresion\n");
    exit(1);
}
$pdo = Dengue\Db::pdo();
$where = $tipo === 'muestra' ? 'WHERE en_muestra = 1' : '';
$casos = $pdo->query("SELECT id_caso, combinacion_signos, clase_esperada FROM caso_prueba $where ORDER BY id_caso")->fetchAll();
if (!$casos) {
    fwrite(STDERR, "No hay casos cargados. Ejecute antes tools/seleccionar_muestra.php\n");
    exit(1);
}

$desde = ahoraBd();
$lote = date('YmdHis');
$ins = $pdo->prepare('INSERT INTO ejecucion_prueba (lote, tipo_ejecucion, id_caso, fecha_hora, clase_obtenida, tiempo_ms, resultado, detalle) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)');
mt_srand(2026);   // los días de fiebre no alteran la clase: se asignan al azar con semilla fija
$conf = array_fill(1, 3, array_fill(1, 3, 0));       // [esperada][obtenida]
$tiempos = [];
$errores = 0;
$sub = 0;
$expSev = 0;
$ok = 0;

foreach ($casos as $c) {
    $esp  = (int) $c['clase_esperada'];
    $dias = Dengue\Entrada::DIAS[mt_rand(0, 2)];
    $r    = evaluarPorHttp($base, camposDesdeBits($c['combinacion_signos'], $dias, mt_rand(0, 255)));
    $obt  = $r['clase'];
    $tiempos[] = $r['ms'];

    if ($obt === null) {
        $errores++;
        $ins->execute([$lote, $tipo, $c['id_caso'], null, $r['ms'], 'error', 'falla HTTP ' . $r['codigo'] . ' ' . $r['error']]);
        continue;
    }
    $conf[$esp][$obt]++;
    if ($esp >= 2) {
        $expSev++;
        if ($obt < $esp) {
            $sub++;
        }
    }
    if ($obt === $esp) {
        $ok++;
        $ins->execute([$lote, $tipo, $c['id_caso'], $obt, $r['ms'], 'ok', null]);
    } else {
        $errores++;
        $ins->execute([$lote, $tipo, $c['id_caso'], $obt, $r['ms'], 'error', "discrepancia: esperada $esp, obtenida $obt"]);
    }
}

marcarPruebas($desde);   // no contaminar el panel con evaluaciones de prueba
$n = count($casos);
[$lo, $hi] = wilson($ok, $n);
$p95 = percentil($tiempos, 95);
$sal = [];
$sal[] = "# Resultados de la ejecución ($tipo) · lote $lote";
$sal[] = '';
$sal[] = "Casos ejecutados: $n · Servidor: $base";
$sal[] = '';
$sal[] = '## Matriz de confusión (filas = clase esperada, columnas = clase obtenida)';
$sal[] = '| Esperada \\ Obtenida | Sin alarma | Con alarma | Grave | Sensibilidad |';
$sal[] = '|---|---|---|---|---|';
foreach ([1, 2, 3] as $e) {
    $tot = array_sum($conf[$e]);
    $sal[] = sprintf('| %s | %d | %d | %d | %s |', CLASES[$e], $conf[$e][1], $conf[$e][2], $conf[$e][3], $tot ? sprintf('%.1f %%', 100 * $conf[$e][$e] / $tot) : 'n/d');
}
$sal[] = '';
$sal[] = '## Indicadores (Tabla 7)';
$sal[] = '| Indicador | Resultado | Meta | Estado |';
$sal[] = '|---|---|---|---|';
$exac = 100 * $ok / $n;
$subp = $expSev ? 100 * $sub / $expSev : 0.0;
$tasa = 100 * $errores / $n;
$sal[] = sprintf('| Exactitud de clasificación | %.2f %% (IC 95 %% Wilson: %.1f %% a %.1f %%) | ≥ 90 %% | %s |', $exac, 100 * $lo, 100 * $hi, $exac >= 90 ? 'CUMPLE' : 'NO CUMPLE');
$sal[] = sprintf('| Subclasificación de riesgo | %.2f %% (%d de %d) | 0 %% | %s |', $subp, $sub, $expSev, $sub === 0 ? 'CUMPLE' : 'NO CUMPLE');
$sal[] = sprintf('| Cumplimiento de casos de prueba | %.2f %% | ≥ 95 %% | %s |', $exac, $exac >= 95 ? 'CUMPLE' : 'NO CUMPLE');
$sal[] = sprintf('| Tasa de errores | %.2f %% (%d de %d) | ≤ 5 %% | %s |', $tasa, $errores, $n, $tasa <= 5 ? 'CUMPLE' : 'NO CUMPLE');
$sal[] = sprintf('| Tiempo de respuesta p95 | %.1f ms | ≤ 3000 ms | %s |', $p95, $p95 <= 3000 ? 'CUMPLE' : 'NO CUMPLE');
$sal[] = sprintf('| Tiempo de respuesta media / mediana | %.1f ms / %.1f ms | — | — |', array_sum($tiempos) / $n, mediana($tiempos));
$sal[] = '';
$sal[] = 'Nota: el tiempo medido es el de la solicitud HTTP completa de la evaluación (incluye la redirección al resultado).';
$texto = implode("\n", $sal) . "\n";
echo $texto;

$dir = __DIR__ . '/../resultados';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
file_put_contents("$dir/{$tipo}_$lote.md", $texto);
echo "\nGuardado en resultados/{$tipo}_$lote.md y en la tabla ejecucion_prueba (lote $lote).\n";
exit($errores === 0 ? 0 : 2);
