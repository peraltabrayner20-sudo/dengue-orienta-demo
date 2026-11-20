<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Dengue\View;

View::cabecera('Inicio');
?>
<section class="tarjeta portada">
  <p class="etiqueta-sec">Valledupar, Cesar</p>
  <h1>¿Fiebre en casa? Sepa cuándo consultar por posible dengue</h1>
  <p class="lead">Conteste unas preguntas cortas y reciba, en menos de dos minutos, una orientación según los signos de alarma y de gravedad del protocolo del Instituto Nacional de Salud, con un paso a paso de qué hacer.</p>
  <p><a class="boton grande" href="evaluar.php">Comenzar la evaluación</a></p>
  <p class="pequeno"><strong>Esto no es un diagnóstico.</strong> La herramienta orienta y no reemplaza la valoración médica. Si hay una emergencia, llame al 123.</p>
</section>

<section class="tarjeta" aria-labelledby="como">
  <h2 id="como">Cómo funciona</h2>
  <ol class="pasos-inicio">
    <li><strong>Datos de la persona.</strong> Edad, días de fiebre, embarazo y enfermedades crónicas, que cambian la urgencia de consultar.</li>
    <li><strong>Ubicación y entorno.</strong> Departamento, municipio y si hay agua estancada cerca. Nunca pedimos nombre, dirección ni teléfono.</li>
    <li><strong>Señales de alarma y de gravedad.</strong> Preguntas de sí o no sobre dolor de barriga, vómito, sangrados, decaimiento y señales de choque.</li>
    <li><strong>Resultado y paso a paso.</strong> Una de tres orientaciones, las señales que la activaron y qué hacer ahora.</li>
  </ol>
</section>

<section class="tarjeta" aria-labelledby="panel">
  <h2 id="panel">Panel de situación</h2>
  <p>Las evaluaciones anónimas se resumen por municipio, grupo de edad y entorno, solo con conteos agrupados. <a href="panel.php">Ver el panel</a></p>
</section>
<?php
View::pie('', false);
