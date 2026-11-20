<?php
declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use Dengue\{Auth, Db};

Auth::exigir();
$pdo  = Db::pdo();
$tipo = $_GET['tipo'] ?? '';

if ($tipo === 'evaluaciones') {
    $sql = "SELECT e.id_evaluacion, e.fecha_hora, e.dias_fiebre, e.grupo_edad, e.embarazo, e.condiciones_cronicas, e.departamento, e.municipio, e.zona, e.agua_estancada, e.origen, e.condicion_riesgo AS R,
              MAX(CASE WHEN s.codigo='A1' THEN es.presente END) AS A1, MAX(CASE WHEN s.codigo='A2' THEN es.presente END) AS A2,
              MAX(CASE WHEN s.codigo='A3' THEN es.presente END) AS A3, MAX(CASE WHEN s.codigo='A4' THEN es.presente END) AS A4,
              MAX(CASE WHEN s.codigo='G1' THEN es.presente END) AS G1, MAX(CASE WHEN s.codigo='G2' THEN es.presente END) AS G2,
              MAX(CASE WHEN s.codigo='G3' THEN es.presente END) AS G3,
              c.nombre AS clase, e.id_version_reglas, e.tiempo_respuesta_ms
            FROM evaluacion e
            JOIN clase_clasificacion c ON c.id_clase = e.id_clase
            JOIN evaluacion_signo es ON es.id_evaluacion = e.id_evaluacion
            JOIN signo s ON s.id_signo = es.id_signo
            GROUP BY e.id_evaluacion ORDER BY e.fecha_hora";
    $archivo = 'evaluaciones.csv';
} elseif ($tipo === 'matriz') {
    $sql = "SELECT cp.id_caso, cp.combinacion_signos, cp.estrato, cp.en_muestra, ce.nombre AS clase_esperada,
              x.lote, x.tipo_ejecucion, co.nombre AS clase_obtenida, x.tiempo_ms, x.resultado, x.detalle
            FROM caso_prueba cp
            JOIN clase_clasificacion ce ON ce.id_clase = cp.clase_esperada
            LEFT JOIN ejecucion_prueba x ON x.id_caso = cp.id_caso
            LEFT JOIN clase_clasificacion co ON co.id_clase = x.clase_obtenida
            ORDER BY cp.id_caso, x.id_ejecucion";
    $archivo = 'matriz_casos_resultados.csv';
} else {
    http_response_code(400);
    exit('Tipo de exportación no válido.');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $archivo . '"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // BOM para que Excel respete las tildes
$primera = true;
foreach ($pdo->query($sql) as $fila) {
    if ($primera) {
        fputcsv($out, array_keys($fila));
        $primera = false;
    }
    fputcsv($out, $fila);
}
fclose($out);
