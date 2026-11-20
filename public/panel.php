<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Dengue\{Db, Panel, View};

$demo  = ($_GET['demo'] ?? '') === '1';
$panel = new Panel(Db::pdo(), $demo ? 'demo' : 'real');

if (($_GET['csv'] ?? '') === '1') {                      // RF-17: exportación de conteos agregados
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="panel_agregado' . ($demo ? '_demo' : '') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['dimension', 'categoria', 'clase', 'evaluaciones']);
    foreach ($panel->exportar() as $f) {
        fputcsv($out, $f);
    }
    fclose($out);
    exit;
}

$total  = $panel->total();
$clases = $panel->porClase();
$K      = Panel::K;
$nombreClase = [1 => 'Sin signos de alarma', 2 => 'Con signos de alarma', 3 => 'Dengue grave'];

/** Gráfico de barras apiladas (SVG con atributos, compatible con la CSP) más tabla equivalente. */
function grafico(string $titulo, array $datos, string $leyendaExtra = ''): void
{
    global $nombreClase;
    $filas = $datos['filas'];
    echo '<h3>' . View::e($titulo) . '</h3>';
    if (!$filas) {
        echo '<p class="ayuda">Todavía no hay suficientes evaluaciones para mostrar este gráfico (mínimo ' . Panel::K . ' por categoría).</p>';
        return;
    }
    $max = max(array_column($filas, 'total'));
    echo '<ul class="barras" role="list">';
    foreach ($filas as $f) {
        $x = 0.0;
        $rects = '';
        foreach ([1 => 'c1', 2 => 'c2', 3 => 'c3'] as $i => $cl) {
            $w = $f['seg'][$i] / $max * 100;
            if ($w > 0) {
                $rects .= '<rect class="' . $cl . '" x="' . round($x, 2) . '" y="0" width="' . round($w, 2) . '" height="20"/>';
                $x += $w;
            }
        }
        if ($f['oculto'] > 0) {
            $w = $f['oculto'] / $max * 100;
            $rects .= '<rect class="c0" x="' . round($x, 2) . '" y="0" width="' . round($w, 2) . '" height="20"/>';
        }
        $aria = $f['etiqueta'] . ': ' . $f['total'] . ' evaluaciones';
        echo '<li><span class="et">' . View::e($f['etiqueta']) . '</span>'
           . '<svg viewBox="0 0 100 20" preserveAspectRatio="none" role="img" aria-label="' . View::e($aria) . '">' . $rects . '</svg>'
           . '<span class="n">' . (int) $f['total'] . '</span></li>';
    }
    echo '</ul>';
    echo '<p class="leyenda" aria-hidden="true"><span><i class="k k1"></i>Sin signos de alarma</span><span><i class="k k2"></i>Con signos de alarma</span>'
       . '<span><i class="k k3"></i>Dengue grave</span><span><i class="k k0"></i>Menos de ' . Panel::K . ' (agrupado)</span></p>';
    echo '<details class="datos"><summary>Ver los datos en tabla</summary><table><caption>' . View::e($titulo) . '</caption><thead><tr><th scope="col">Categoría</th>';
    foreach ($nombreClase as $n) {
        echo '<th scope="col">' . View::e($n) . '</th>';
    }
    echo '<th scope="col">Total</th></tr></thead><tbody>';
    foreach ($filas as $f) {
        echo '<tr><th scope="row">' . View::e($f['etiqueta']) . '</th>';
        foreach ([1, 2, 3] as $i) {
            $n = $f['clases'][$i];
            echo '<td>' . (($n > 0 && $n < Panel::K) ? '&lt;' . Panel::K : $n) . '</td>';
        }
        echo '<td>' . (int) $f['total'] . '</td></tr>';
    }
    echo '</tbody></table></details>';
    if ($datos['ocultas'] > 0) {
        echo '<p class="ayuda">Hay categorías con muy pocas evaluaciones que no se muestran para proteger la privacidad.</p>';
    }
}

View::cabecera('Panel de situación');
$q = $demo ? '&demo=1' : '';
?>
<section class="tarjeta">
  <h1>Panel de situación</h1>
  <p class="lead">Resumen agrupado de las evaluaciones anónimas hechas en esta herramienta, por municipio, edad y entorno.</p>

  <?php if ($demo): ?>
    <p class="panel-demo" role="note">DATOS SINTÉTICOS DE DEMOSTRACIÓN. Fueron generados por computador (o enviados por las pruebas automáticas) para mostrar cómo se ve el panel; no corresponden a personas ni a casos reales.</p>
  <?php endif; ?>

  <div class="panel-aviso" role="note">
    <p><strong>Cómo leer este panel.</strong> Cada dato es una <em>evaluación voluntaria y anónima</em>, no un caso confirmado de dengue.
    No representa la situación epidemiológica de la ciudad ni sustituye la vigilancia del sistema de salud.</p>
    <p><strong>Privacidad.</strong> Solo se muestran conteos agrupados; las categorías con menos de <?= $K ?> evaluaciones se ocultan o se agrupan.</p>
  </div>

  <?php if ($total === 0): ?>
    <p>Todavía no hay evaluaciones registradas. Cuando las personas usen la herramienta, aquí aparecerán los resultados agrupados.</p>
    <?php if (!$demo): ?><p><a href="panel.php?demo=1">Ver el panel con datos sintéticos de demostración</a></p><?php endif; ?>
  <?php else: ?>
    <div class="tarjetas-num">
      <div class="num"><strong><?= $total ?></strong><span>evaluaciones</span></div>
      <?php foreach ([1, 2, 3] as $i): $n = $clases[$i]; ?>
        <div class="num"><strong><?= ($n > 0 && $n < $K) ? '&lt;' . $K : $n ?></strong><span><?= View::e($nombreClase[$i]) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php
    if ($total < $K * 2) {
        echo '<p class="ayuda">Aún hay pocas evaluaciones: la mayoría de gráficos aparecerán cuando cada categoría llegue a ' . $K . '.</p>';
    }
    grafico('Evaluaciones por municipio y resultado', $panel->municipios());
    grafico('Evaluaciones por grupo de edad y resultado', $panel->edades());
    grafico('Evaluaciones según la zona de la vivienda', $panel->zonas());
    grafico('Evaluaciones según agua estancada cerca de la vivienda', $panel->agua());

    $sem = $panel->semanas();
    if ($sem) {
        echo '<h3>Evaluaciones por semana (últimas 8 semanas)</h3><ul class="barras" role="list">';
        $mx = max(1, max(array_map(static fn($s) => (int) ($s['n'] ?? 0), $sem)));
        foreach ($sem as $s) {
            $w = ($s['n'] ?? 0) / $mx * 100;
            echo '<li><span class="et">Semana del ' . View::e(date('d/m', strtotime($s['semana']))) . '</span><svg viewBox="0 0 100 20" preserveAspectRatio="none" role="img" aria-label="'
               . View::e('Semana del ' . date('d/m', strtotime($s['semana'])) . ': ' . ($s['n'] === null ? 'menos de ' . $K : $s['n']) . ' evaluaciones') . '"><rect class="cT" x="0" y="0" width="' . round($w, 2) . '" height="20"/></svg>'
               . '<span class="n">' . ($s['n'] === null ? '&lt;' . $K : (int) $s['n']) . '</span></li>';
        }
        echo '</ul>';
    }
    $sinUb = $panel->sinUbicacion();
    if ($sinUb > 0) {
        echo '<p class="ayuda">' . $sinUb . ' evaluaciones se hicieron con la versión anterior del formulario (sin ubicación) y no entran en los gráficos por municipio, edad o entorno.</p>';
    }
    ?>
  <?php endif; ?>

  <div class="acciones-panel">
    <a class="boton" href="panel.php?csv=1<?= $q ?>">Exportar conteos (CSV)</a>
    <a class="boton sec" href="evaluar.php">Hacer una evaluación</a>
  </div>
</section>
<?php
View::pie('', false);
