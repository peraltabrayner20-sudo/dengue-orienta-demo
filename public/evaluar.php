<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Dengue\{Csrf, Db, Entrada, Evaluacion, RuleRepository, Ubicaciones, View};

$pdo       = Db::pdo();
$errores   = [];
$valores   = [];
$errorAviso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valido($_POST['csrf'] ?? null)) {
        View::error(403, 'La solicitud no es válida o la sesión expiró. Vuelva al inicio e intente de nuevo.');
        exit;
    }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'aceptar') {                       // RF-01
        if (($_POST['acepto'] ?? '') === '1') {
            $_SESSION['consentimiento'] = true;
            header('Location: evaluar.php');
            exit;
        }
        http_response_code(422);
        $errorAviso = 'Para continuar debe aceptar el tratamiento de sus respuestas.';
    } elseif ($accion === 'evaluar') {
        if (empty($_SESSION['consentimiento'])) {
            View::error(403, 'Primero debe aceptar el aviso de privacidad.');
            exit;
        }
        $t0 = hrtime(true);
        $r  = Entrada::validar($_POST);
        if ($r['datos'] === null) {
            http_response_code(422);                    // RF-02 a RF-04: no se avanza sin responder
            $errores = $r['errores'];
            foreach (array_merge(['dias_fiebre', 'edad', 'embarazo', 'departamento', 'municipio', 'zona', 'agua'], Entrada::SIGNOS) as $k) {
                if (isset($_POST[$k]) && is_string($_POST[$k])) {
                    $valores[$k] = $_POST[$k];
                }
            }
            $valores['cronica'] = array_values(array_filter((array) ($_POST['cronica'] ?? []), 'is_string'));
        } else {
            $clf = RuleRepository::cargar($pdo);
            $res = $clf->clasificar($r['datos']['presentes']);
            $ms  = (hrtime(true) - $t0) / 1e6;
            Evaluacion::registrar($pdo, $r['datos'], $res['id_clase'], $clf->versionId(), $ms);   // RF-10
            $_SESSION['resultado'] = [
                'res' => $res, 'dias' => $r['datos']['dias_fiebre'], 'riesgo' => $r['datos']['riesgo'],
                'edad' => $r['datos']['edad'], 'embarazo' => $r['datos']['embarazo'], 'cronicas' => $r['datos']['cronicas'],
            ];
            header('Location: resultado.php');
            exit;
        }
    }
}

$csrf = Csrf::token();

// ---------- Pantalla 1: aviso de privacidad (RF-01) ----------
if (empty($_SESSION['consentimiento'])) {
    View::cabecera('Aviso de privacidad');
    ?>
<section class="tarjeta">
  <h1>Antes de empezar</h1>
  <p><strong>Finalidad.</strong> Esta herramienta le orienta sobre cuándo consultar si usted, o una persona a su cuidado, tiene fiebre compatible con dengue. <strong>No diagnostica.</strong></p>
  <ul>
    <li>No le pedimos nombre, cédula, dirección exacta, barrio, teléfono ni correo.</li>
    <li>Le preguntamos la edad, el departamento y el municipio donde vive y algunos datos del entorno para orientarle mejor y construir estadísticas agrupadas. De la edad solo se guarda el grupo (por ejemplo, 15 a 64 años), no el número.</li>
    <li>Sus respuestas son <strong>anónimas y voluntarias</strong>; no se guarda ningún dato que lo identifique, ni su dirección IP. El panel público solo muestra conteos agrupados.</li>
    <li>No compartimos datos con terceros. Los datos de salud son sensibles (Ley 1581 de 2012): usted puede dejar de responder en cualquier momento.</li>
  </ul>
  <form method="post" action="evaluar.php" novalidate>
    <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
    <input type="hidden" name="accion" value="aceptar">
    <?php if ($errorAviso): ?><p class="error" role="alert"><?= View::e($errorAviso) ?></p><?php endif; ?>
    <p class="check"><input type="checkbox" id="acepto" name="acepto" value="1">
       <label for="acepto">Acepto el tratamiento de mis respuestas con esta finalidad.</label></p>
    <button class="boton" type="submit">Continuar</button>
  </form>
</section>
<?php
    View::pie();
    exit;
}

// ---------- Pantallas 2 a 4: formulario por pasos ----------
$signos = RuleRepository::signos($pdo);
$alarma = array_values(array_filter($signos, static fn($s) => $s['tipo'] === 'alarma'));
$grave  = array_values(array_filter($signos, static fn($s) => $s['tipo'] === 'gravedad'));

/** Grupo de pregunta sí/no accesible (fieldset + legend + radios). */
function preguntaSiNo(string $name, string $texto, array $valores, array $errores): void
{
    $v = $valores[$name] ?? '';
    $e = $errores[$name] ?? '';
    echo '<fieldset class="pregunta' . ($e ? ' con-error' : '') . '" data-grupo="' . View::e($name) . '">';
    echo '<legend>' . View::e($texto) . '</legend><div class="opciones">';
    foreach (['si' => 'Sí', 'no' => 'No'] as $val => $et) {
        $id = $name . '_' . $val;
        echo '<span class="op"><input type="radio" id="' . $id . '" name="' . View::e($name) . '" value="' . $val . '"' . ($v === $val ? ' checked' : '') . '>'
           . '<label for="' . $id . '">' . $et . '</label></span>';
    }
    echo '</div>';
    if ($e) {
        echo '<p class="error" role="alert">' . View::e($e) . '</p>';
    }
    echo '</fieldset>';
}

/** Grupo de opciones (radios) con etiquetas arbitrarias. */
function preguntaOpciones(string $name, string $texto, array $opciones, array $valores, array $errores): void
{
    $v = $valores[$name] ?? '';
    $e = $errores[$name] ?? '';
    echo '<fieldset class="pregunta' . ($e ? ' con-error' : '') . '" data-grupo="' . View::e($name) . '">';
    echo '<legend>' . View::e($texto) . '</legend><div class="opciones">';
    foreach ($opciones as $val => $et) {
        $id = $name . '_' . $val;
        echo '<span class="op"><input type="radio" id="' . View::e($id) . '" name="' . View::e($name) . '" value="' . View::e((string) $val) . '"' . ($v === (string) $val ? ' checked' : '') . '>'
           . '<label for="' . View::e($id) . '">' . View::e($et) . '</label></span>';
    }
    echo '</div>';
    if ($e) {
        echo '<p class="error" role="alert">' . View::e($e) . '</p>';
    }
    echo '</fieldset>';
}

View::cabecera('Evaluación');
$v = static fn(string $k): string => (string) ($valores[$k] ?? '');
$cronSel = (array) ($valores['cronica'] ?? []);
$depSel  = $v('departamento');
?>
<section class="tarjeta">
  <h1>Evaluación de la persona con fiebre</h1>
  <p>Responda por usted o por la persona a su cuidado (por ejemplo, un niño). Son cinco pasos cortos.</p>
  <?php if ($errores): ?>
    <p class="error resumen-error" role="alert">Faltan respuestas o hay datos que corregir. Revise los campos marcados.</p>
  <?php endif; ?>
  <p class="progreso" id="progreso" aria-live="polite" hidden></p>
  <form id="form-evaluacion" method="post" action="evaluar.php" novalidate>
    <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
    <input type="hidden" name="accion" value="evaluar">

    <div class="paso" data-paso="1" data-titulo="La persona">
      <h2>Paso 1 · La persona</h2>
      <fieldset class="pregunta<?= isset($errores['edad']) ? ' con-error' : '' ?>" data-grupo="edad" data-tipo="numero">
        <legend>¿Qué edad tiene la persona? (en años; escriba 0 si es menor de un año)</legend>
        <input class="entrada-num" type="number" inputmode="numeric" id="edad" name="edad" min="0" max="<?= Entrada::EDAD_MAX ?>" step="1" value="<?= View::e($v('edad')) ?>" aria-describedby="ayuda-edad">
        <p class="ayuda" id="ayuda-edad">La edad no se guarda: solo el grupo (0-4, 5-14, 15-64 o 65 años y más).</p>
        <?php if (isset($errores['edad'])): ?><p class="error" role="alert"><?= View::e($errores['edad']) ?></p><?php endif; ?>
      </fieldset>
      <fieldset class="pregunta<?= isset($errores['dias_fiebre']) ? ' con-error' : '' ?>" data-grupo="dias_fiebre">
        <legend>¿Cuántos días lleva con fiebre?</legend>
        <div class="opciones">
        <?php foreach (['1-2' => '1 a 2 días', '3-7' => '3 a 7 días', '8+' => '8 días o más'] as $val => $et): ?>
          <span class="op"><input type="radio" id="dias_<?= View::e($val) ?>" name="dias_fiebre" value="<?= View::e($val) ?>"<?= $v('dias_fiebre') === $val ? ' checked' : '' ?>>
          <label for="dias_<?= View::e($val) ?>"><?= View::e($et) ?></label></span>
        <?php endforeach; ?>
        </div>
        <?php if (isset($errores['dias_fiebre'])): ?><p class="error" role="alert"><?= View::e($errores['dias_fiebre']) ?></p><?php endif; ?>
      </fieldset>
      <div class="nav"><button type="button" class="boton" data-accion="siguiente" hidden>Siguiente</button></div>
    </div>

    <div class="paso" data-paso="2" data-titulo="Condiciones de riesgo">
      <h2>Paso 2 · Condiciones de riesgo</h2>
      <div id="bloque-embarazo" data-rango-min="10" data-rango-max="55">
        <?php
        $e = $errores['embarazo'] ?? '';
        echo '<fieldset class="pregunta' . ($e ? ' con-error' : '') . '" data-grupo="embarazo"><legend>¿La persona está embarazada?</legend><div class="opciones">';
        foreach (['si' => 'Sí', 'no' => 'No', 'na' => 'No aplica'] as $val => $et) {
            echo '<span class="op"><input type="radio" id="embarazo_' . $val . '" name="embarazo" value="' . $val . '"' . ($v('embarazo') === $val ? ' checked' : '') . '>'
               . '<label for="embarazo_' . $val . '">' . $et . '</label></span>';
        }
        echo '</div>' . ($e ? '<p class="error" role="alert">' . View::e($e) . '</p>' : '') . '</fieldset>';
        ?>
      </div>
      <fieldset class="pregunta<?= isset($errores['cronica']) ? ' con-error' : '' ?>" data-grupo="cronica" data-tipo="check">
        <legend>¿Tiene alguna enfermedad crónica? Marque todas las que apliquen.</legend>
        <div class="opciones opciones-col">
        <?php foreach (Entrada::CRONICAS + ['ninguna' => 'Ninguna'] as $val => $et): ?>
          <span class="op"><input type="checkbox" id="cronica_<?= $val ?>" name="cronica[]" value="<?= $val ?>"<?= in_array($val, $cronSel, true) ? ' checked' : '' ?>>
          <label for="cronica_<?= $val ?>"><?= View::e($et) ?></label></span>
        <?php endforeach; ?>
        </div>
        <?php if (isset($errores['cronica'])): ?><p class="error" role="alert"><?= View::e($errores['cronica']) ?></p><?php endif; ?>
      </fieldset>
      <div class="nav">
        <button type="button" class="boton sec" data-accion="atras" hidden>Atrás</button>
        <button type="button" class="boton" data-accion="siguiente" hidden>Siguiente</button>
      </div>
    </div>

    <div class="paso" data-paso="3" data-titulo="Ubicación y entorno">
      <h2>Paso 3 · Ubicación y entorno</h2>
      <p class="ayuda">No pedimos dirección ni barrio. Estos datos sirven para el panel de situación y se guardan agrupados.</p>
      <fieldset class="pregunta<?= isset($errores['departamento']) ? ' con-error' : '' ?>" data-grupo="departamento" data-tipo="lista">
        <legend><label for="departamento">Departamento donde vive la persona</label></legend>
        <select id="departamento" name="departamento">
          <option value="">Seleccione…</option>
          <?php foreach (Ubicaciones::DEPARTAMENTOS as $d): ?>
            <option value="<?= View::e($d) ?>"<?= $depSel === $d ? ' selected' : '' ?>><?= View::e($d) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['departamento'])): ?><p class="error" role="alert"><?= View::e($errores['departamento']) ?></p><?php endif; ?>
      </fieldset>
      <fieldset class="pregunta<?= isset($errores['municipio']) ? ' con-error' : '' ?>" data-grupo="municipio" data-tipo="lista" id="bloque-municipio" data-solo="<?= View::e(Ubicaciones::CESAR) ?>">
        <legend><label for="municipio">Municipio del Cesar</label></legend>
        <select id="municipio" name="municipio">
          <option value="">Seleccione…</option>
          <?php foreach (Ubicaciones::MUNICIPIOS_CESAR as $m): ?>
            <option value="<?= View::e($m) ?>"<?= $v('municipio') === $m ? ' selected' : '' ?>><?= View::e($m) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['municipio'])): ?><p class="error" role="alert"><?= View::e($errores['municipio']) ?></p><?php endif; ?>
      </fieldset>
      <?php
      preguntaOpciones('zona', '¿La vivienda está en zona urbana o rural?', Ubicaciones::ZONAS, $valores, $errores);
      preguntaOpciones('agua', '¿Hay agua estancada cerca de la vivienda (tanques destapados, llantas, charcos, floreros)?', Ubicaciones::AGUA, $valores, $errores);
      ?>
      <div class="nav">
        <button type="button" class="boton sec" data-accion="atras" hidden>Atrás</button>
        <button type="button" class="boton" data-accion="siguiente" hidden>Siguiente</button>
      </div>
    </div>

    <div class="paso" data-paso="4" data-titulo="Señales de alarma">
      <h2>Paso 4 · Señales de alarma</h2>
      <?php foreach ($alarma as $s) { preguntaSiNo($s['codigo'], $s['descripcion_usuario'], $valores, $errores); } ?>
      <div class="nav">
        <button type="button" class="boton sec" data-accion="atras" hidden>Atrás</button>
        <button type="button" class="boton" data-accion="siguiente" hidden>Siguiente</button>
      </div>
    </div>

    <div class="paso" data-paso="5" data-titulo="Señales de gravedad">
      <h2>Paso 5 · Señales de gravedad</h2>
      <?php foreach ($grave as $s) { preguntaSiNo($s['codigo'], $s['descripcion_usuario'], $valores, $errores); } ?>
      <div class="nav">
        <button type="button" class="boton sec" data-accion="atras" hidden>Atrás</button>
        <button type="submit" class="boton">Ver resultado</button>
      </div>
    </div>
    <p class="error mensaje-paso" id="mensaje-paso" role="alert" hidden></p>
  </form>
</section>
<?php
View::pie();
