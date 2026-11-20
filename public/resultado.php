<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Dengue\{Csrf, Entrada, View};

if (empty($_SESSION['resultado'])) {
    header('Location: evaluar.php');
    exit;
}
['res' => $res, 'dias' => $dias, 'riesgo' => $riesgo] = $_SESSION['resultado'];
$edad     = (int) ($_SESSION['resultado']['edad'] ?? 30);
$embarazo = (string) ($_SESSION['resultado']['embarazo'] ?? 'na');
$cronicas = (array) ($_SESSION['resultado']['cronicas'] ?? []);

// Condiciones de riesgo indicadas por la persona (para mostrarlas y para el paso a paso).
$condiciones = [];
if ($edad < 5)                 { $condiciones[] = 'es menor de 5 años'; }
if ($edad >= 65)               { $condiciones[] = 'tiene 65 años o más'; }
if ($embarazo === 'si')        { $condiciones[] = 'está embarazada'; }
foreach ($cronicas as $c)      { $condiciones[] = 'tiene ' . mb_strtolower(Entrada::CRONICAS[$c] ?? 'una enfermedad crónica', 'UTF-8'); }
$id    = (int) $res['id_clase'];
$clase = [1 => 'sin', 2 => 'alarma', 3 => 'grave'][$id] ?? 'sin';
$icono = [1 => '●', 2 => '▲', 3 => '■'][$id] ?? '●';

View::cabecera('Resultado');
?>
<section class="tarjeta" data-clase-id="<?= $id ?>">
  <?php if ($id === 3): ?>
    <div class="urgencia" role="alert"><strong>URGENCIA INMEDIATA:</strong> <?= View::e($res['recomendacion']) ?></div>   <!-- RF-07 -->
  <?php endif; ?>

  <div class="resultado r-<?= $clase ?>">
    <p class="etiqueta">Resultado de la orientación</p>
    <h1><span aria-hidden="true"><?= $icono ?></span> <?= View::e($res['nombre']) ?></h1>
    <p class="urg">Nivel de atención: <strong><?= View::e($res['urgencia']) ?></strong></p>
  </div>

  <h2>Qué hacer, paso a paso</h2>
  <p class="accion"><?= View::e($res['recomendacion']) ?></p>
  <?php if ($id === 3): ?>
    <ol class="paso-a-paso u-grave">
      <li><strong>Llame al 123 o vaya ya al servicio de urgencias más cercano.</strong> No espere a que baje la fiebre ni a que amanezca.</li>
      <li><strong>Que alguien lo acompañe.</strong> Si está confuso, muy decaído o con la piel fría, no conduzca.</li>
      <li><strong>Lleve su documento y el carné de la EPS si los tiene a la mano,</strong> pero no se demore buscándolos.</li>
      <li><strong>Cuente al personal de salud</strong> cuántos días lleva con fiebre, las señales de esta pantalla y las condiciones de riesgo que indicó.</li>
      <li><strong>No se automedique.</strong> No tome aspirina, ibuprofeno ni otros antiinflamatorios.</li>
      <li><strong>Mientras llega la ayuda,</strong> mantenga a la persona acostada y vigilada. Si está inconsciente o convulsionando, no le dé de comer ni de beber.</li>
    </ol>
  <?php elseif ($id === 2): ?>
    <ol class="paso-a-paso u-alarma">
      <li><strong>Acuda hoy mismo a un centro de salud</strong> (urgencias o su IPS). No espere a mañana: estas señales pueden empeorar en horas.</li>
      <li><strong>Vaya acompañado,</strong> sobre todo si es un niño, una embarazada o un adulto mayor.</li>
      <li><strong>Lleve su documento y el carné de la EPS</strong> si los tiene a la mano.</li>
      <li><strong>Cuente al médico</strong> los días de fiebre, las señales marcadas y las condiciones de riesgo que indicó.</li>
      <li><strong>Hasta que lo atiendan:</strong> tome líquidos en sorbos pequeños si logra retenerlos y descanse. Para la fiebre use solo acetaminofén según el empaque o la indicación médica. <strong>No use aspirina ni ibuprofeno.</strong></li>
      <li><strong>Si en el camino aparece</strong> piel fría o pálida, dificultad para respirar, sangrado abundante, confusión o convulsiones, <strong>llame al 123</strong>.</li>
    </ol>
  <?php else: ?>
    <ol class="paso-a-paso u-sin">
      <li><strong>Hidrátese:</strong> agua, suero oral, sopas o jugos, varias veces al día aunque no tenga sed.</li>
      <li><strong>Descanse</strong> en casa y no vaya al trabajo ni al colegio mientras tenga fiebre.</li>
      <li><strong>Para la fiebre y el dolor</strong> use acetaminofén (paracetamol) según el empaque o la indicación de su médico. <strong>No use aspirina ni ibuprofeno</strong> u otros antiinflamatorios.</li>
      <li><strong>Evite más picaduras:</strong> toldillo, repelente y ropa larga. Así protege a su familia. Elimine el agua estancada cerca de la vivienda.</li>
      <li><strong>Revise cada día</strong> si aparece alguna de las señales de la lista «Vuelva a consultar de inmediato» (más abajo).</li>
      <li><strong>Si no mejora o tiene dudas,</strong> consulte en su IPS o centro de salud.</li>
    </ol>
  <?php endif; ?>
  <?php if ($id === 1 && $riesgo && $res['mensaje_riesgo']): ?>
    <p class="accion"><?= View::e($res['mensaje_riesgo']) ?></p>
  <?php endif; ?>

  <?php if ($condiciones): ?>
    <div class="bloque-riesgo">
      <p><strong>Condiciones de riesgo que usted indicó:</strong> la persona <?= View::e(implode('; ', $condiciones)) ?>.</p>
      <p>Menciónelas siempre al personal de salud. <?php if ($edad < 5): ?>En un niño pequeño, la deshidratación puede empeorar rápido: si rechaza los líquidos o está muy dormido, consulte sin esperar. <?php endif; ?><?php if ($embarazo === 'si'): ?>Una embarazada con fiebre debe ser valorada por un profesional de la salud. <?php endif; ?></p>
    </div>
  <?php endif; ?>

  <h2>Señales que activaron este resultado</h2>   <!-- RF-06 / RNF-08 -->
  <?php if ($res['senales']): ?>
    <ul>
      <?php foreach ($res['senales'] as $s): ?><li><?= View::e($s['nombre']) ?></li><?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No marcó señales de alarma ni de gravedad.</p>
  <?php endif; ?>

  <?php if ($id === 1): ?>                         <!-- RF-08 -->
    <?php if ($dias === '3-7' || $dias === '8+'): ?>
      <p class="aviso-dias">Cuando la fiebre empieza a bajar (hacia los días 3 a 7) es cuando más aparecen los signos de alarma: vigile con más cuidado.</p>
    <?php endif; ?>
    <h2>Vuelva a consultar de inmediato si aparece</h2>
    <ul>
      <li>Dolor de barriga fuerte que no se quita, o vómito repetido.</li>
      <li>Sangrado por encías o nariz.</li>
      <li>Mucho sueño, decaimiento o irritabilidad.</li>
      <li>Piel fría, pálida o sudorosa, dificultad para respirar, confusión o convulsiones.</li>
    </ul>
  <?php endif; ?>

  <p class="no-diagnostica"><strong>Importante:</strong> esto no es un diagnóstico. La herramienta solo orienta y no reemplaza la valoración médica.</p>   <!-- RF-09 -->

  <p><a href="panel.php">Ver el panel de situación</a></p>

  <form method="post" action="reiniciar.php">     <!-- RF-11 -->
    <input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>">
    <button class="boton" type="submit">Nueva evaluación</button>
  </form>
</section>
<?php
View::pie();
