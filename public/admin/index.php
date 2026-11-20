<?php
declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use Dengue\{Auth, Csrf, Db, View};

Auth::exigir();
$pdo = Db::pdo();

$total   = (int) $pdo->query('SELECT COUNT(*) FROM evaluacion')->fetchColumn();
$porClase = $pdo->query('SELECT c.nombre, COUNT(e.id_evaluacion) AS n FROM clase_clasificacion c LEFT JOIN evaluacion e ON e.id_clase = c.id_clase GROUP BY c.id_clase, c.nombre ORDER BY c.id_clase')->fetchAll();
$tiempos = array_map('floatval', $pdo->query('SELECT tiempo_respuesta_ms FROM evaluacion ORDER BY tiempo_respuesta_ms')->fetchAll(PDO::FETCH_COLUMN));
$p95 = $tiempos ? $tiempos[(int) ceil(0.95 * count($tiempos)) - 1] : null;
$casos = (int) $pdo->query('SELECT COUNT(*) FROM caso_prueba')->fetchColumn();
$ejec  = (int) $pdo->query('SELECT COUNT(*) FROM ejecucion_prueba')->fetchColumn();
$ultimas = $pdo->query('SELECT e.fecha_hora, e.dias_fiebre, e.grupo_edad, e.municipio, e.condicion_riesgo, c.nombre, e.tiempo_respuesta_ms FROM evaluacion e JOIN clase_clasificacion c ON c.id_clase = e.id_clase ORDER BY e.fecha_hora DESC LIMIT 15')->fetchAll();

View::cabecera('Panel', '../');
?>
<section class="tarjeta">
  <h1>Panel del administrador</h1>
  <p>Evaluaciones registradas (anónimas): <strong><?= $total ?></strong>
     · Tiempo de respuesta p95: <strong><?= $p95 === null ? 'n/d' : number_format($p95, 2) . ' ms' ?></strong>
     · Casos de prueba: <strong><?= $casos ?></strong> · Ejecuciones: <strong><?= $ejec ?></strong></p>
  <table>
    <caption>Evaluaciones por clase</caption>
    <thead><tr><th scope="col">Clase</th><th scope="col">Evaluaciones</th></tr></thead>
    <tbody><?php foreach ($porClase as $r): ?><tr><td><?= View::e($r['nombre']) ?></td><td><?= (int) $r['n'] ?></td></tr><?php endforeach; ?></tbody>
  </table>
  <h2>Exportar (CSV)</h2>
  <p>
    <a class="boton" href="export.php?tipo=evaluaciones">Evaluaciones (registro anónimo)</a>
    <a class="boton" href="../panel.php">Panel público</a>
    <a class="boton" href="export.php?tipo=matriz">Matriz de casos y resultados</a>
  </p>
  <h2>Últimas evaluaciones</h2>
  <table>
    <thead><tr><th scope="col">Fecha</th><th scope="col">Días</th><th scope="col">Edad</th><th scope="col">Municipio</th><th scope="col">Riesgo</th><th scope="col">Clase</th><th scope="col">ms</th></tr></thead>
    <tbody><?php foreach ($ultimas as $r): ?>
      <tr><td><?= View::e($r['fecha_hora']) ?></td><td><?= View::e($r['dias_fiebre']) ?></td><td><?= View::e((string) $r['grupo_edad']) ?></td><td><?= View::e((string) $r['municipio']) ?></td><td><?= $r['condicion_riesgo'] ? 'Sí' : 'No' ?></td><td><?= View::e($r['nombre']) ?></td><td><?= View::e((string) $r['tiempo_respuesta_ms']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table>
  <form method="post" action="logout.php">
    <input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>">
    <button class="boton sec" type="submit">Cerrar sesión</button>
  </form>
</section>
<?php
View::pie('../', false);
