<?php
// Uso:  php tools/generar_datos_demo.php [--n=240]
// Inserta evaluaciones SINTÉTICAS (origen = 'demo') para poder ver el panel de situación antes de tener usuarios reales.
// Son números inventados por computador (semilla fija): NO representan personas ni casos reales y no se mezclan
// con las evaluaciones reales (el panel solo las muestra con ?demo=1). Es repetible: borra las sintéticas anteriores.
declare(strict_types=1);
require __DIR__ . '/lib.php';

use Dengue\{Db, Entrada, Evaluacion, RuleRepository, Ubicaciones};

$n   = (int) opcion($argv, 'n', '240');
$pdo = Db::pdo();
mt_srand(2026);

$clf = RuleRepository::cargar($pdo);
$pdo->exec("DELETE FROM evaluacion WHERE origen = 'demo'");

// Pesos relativos inventados (solo para que los gráficos tengan forma).
$pesosMun = ['Valledupar' => 90, 'Aguachica' => 18, 'Agustín Codazzi' => 14, 'La Jagua de Ibirico' => 10, 'Bosconia' => 10,
             'Chimichagua' => 7, 'El Copey' => 6, 'Curumaní' => 6, 'San Diego' => 4, 'Pailitas' => 3, 'Gamarra' => 2];
$pesoFuera = 8;
$total = array_sum($pesosMun) + $pesoFuera;
$edades = [[0, 4, 12], [5, 14, 20], [15, 64, 55], [65, 95, 13]];

function elegirPeso(array $pesos): string
{
    $r = mt_rand(1, array_sum($pesos));
    foreach ($pesos as $k => $p) {
        if (($r -= $p) <= 0) {
            return (string) $k;
        }
    }
    return (string) array_key_last($pesos);
}

$ins = $pdo->prepare('INSERT INTO evaluacion (id_evaluacion, fecha_hora, dias_fiebre, grupo_edad, embarazo, condiciones_cronicas, condicion_riesgo,
                      departamento, municipio, zona, agua_estancada, origen, id_clase, id_version_reglas, tiempo_respuesta_ms)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'demo\', ?, ?, ?)');
$cont = [1 => 0, 2 => 0, 3 => 0];
for ($i = 0; $i < $n; $i++) {
    $mun = mt_rand(1, $total) <= $pesoFuera ? Ubicaciones::FUERA : elegirPeso($pesosMun);
    $dep = $mun === Ubicaciones::FUERA ? ['Magdalena', 'La Guajira', 'Atlántico', 'Norte de Santander'][mt_rand(0, 3)] : 'Cesar';
    $rango = null;
    $r = mt_rand(1, 100);
    foreach ($edades as $e) {
        if (($r -= $e[2]) <= 0) { $rango = $e; break; }
    }
    $rango ??= $edades[2];
    $edad = mt_rand($rango[0], $rango[1]);
    $emb = ($edad >= 15 && $edad <= 45) ? (mt_rand(1, 100) <= 6 ? 'si' : 'no') : 'na';
    $cron = [];
    foreach (array_keys(Entrada::CRONICAS) as $c) {
        if (mt_rand(1, 100) <= ($edad >= 50 ? 14 : 4)) { $cron[] = $c; }
    }
    $agua = ['si', 'no', 'nose'][mt_rand(1, 100) <= 45 ? 0 : (mt_rand(1, 100) <= 80 ? 1 : 2)];
    $zona = mt_rand(1, 100) <= ($mun === 'Valledupar' ? 12 : 35) ? 'rural' : 'urbana';
    // Señales: más probables con más días de fiebre.
    $dias = Entrada::DIAS[mt_rand(0, 2)];
    $p = $dias === '1-2' ? 4 : ($dias === '3-7' ? 14 : 12);
    $pres = [];
    foreach (Entrada::SIGNOS as $s) {
        $pres[$s] = mt_rand(1, 100) <= ($s[0] === 'G' ? max(1, intdiv($p, 4)) : $p);
    }
    $res = $clf->clasificar($pres);
    $cont[$res['id_clase']]++;
    $fecha = date('Y-m-d H:i:s', time() - mt_rand(0, 55 * 86400) - mt_rand(0, 86399));
    $riesgo = Entrada::riesgo($edad, $emb, $cron);
    $ins->execute([Evaluacion::uuid(), $fecha, $dias, Entrada::grupoEdad($edad), $emb, $cron ? implode(',', $cron) : null, $riesgo ? 1 : 0,
                   $dep, $mun, $zona, $agua, $res['id_clase'], $clf->versionId(), round(mt_rand(300, 900) / 10, 2)]);
}
printf("Insertadas %d evaluaciones sintéticas (origen=demo): clase 1 = %d, clase 2 = %d, clase 3 = %d\n", $n, $cont[1], $cont[2], $cont[3]);
