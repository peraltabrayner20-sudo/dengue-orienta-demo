<?php
declare(strict_types=1);

namespace Dengue;

use PDO;

/** Registro anonimizado de cada evaluación (RF-10). No guarda IP, nombre, dirección ni ningún dato que identifique a la persona. */
final class Evaluacion
{
    public static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * @param array<string,mixed> $datos  salida de Entrada::validar()['datos']
     * @param string $origen 'real' (uso normal) o 'demo' (datos sintéticos de demostración)
     */
    public static function registrar(PDO $pdo, array $datos, int $idClase, int $versionId, float $ms, string $origen = 'real'): string
    {
        $id = self::uuid();
        $pdo->beginTransaction();
        try {
            $cron = ($datos['cronicas'] ?? []) === [] ? null : implode(',', $datos['cronicas']);
            $pdo->prepare('INSERT INTO evaluacion (id_evaluacion, fecha_hora, dias_fiebre, grupo_edad, embarazo, condiciones_cronicas, condicion_riesgo,
                                                   departamento, municipio, zona, agua_estancada, origen, id_clase, id_version_reglas, tiempo_respuesta_ms)
                           VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id, $datos['dias_fiebre'], $datos['grupo_edad'] ?? null, $datos['embarazo'] ?? null, $cron,
                           $datos['riesgo'] ? 1 : 0, $datos['departamento'] ?? null, $datos['municipio'] ?? null,
                           $datos['zona'] ?? null, $datos['agua'] ?? null, $origen, $idClase, $versionId, round($ms, 2)]);

            $ids = [];
            foreach ($pdo->query('SELECT id_signo, codigo FROM signo')->fetchAll() as $s) {
                $ids[$s['codigo']] = (int) $s['id_signo'];
            }
            $ins = $pdo->prepare('INSERT INTO evaluacion_signo (id_evaluacion, id_signo, presente) VALUES (?, ?, ?)');
            foreach ($datos['presentes'] as $codigo => $presente) {
                $ins->execute([$id, $ids[$codigo], $presente ? 1 : 0]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $id;
    }
}
