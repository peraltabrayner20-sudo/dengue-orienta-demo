<?php
declare(strict_types=1);

namespace Dengue\Tests\Support;

use Dengue\Classifier;

/** Reglas y clases iguales a database/seed.sql (SeedConsistencyTest verifica que no se desalineen). */
final class Fixtures
{
    public static function reglas(): array
    {
        return [
            ['id_regla' => 1, 'prioridad' => 1, 'condicion' => 'ANY:G1,G2,G3',    'id_clase_resultado' => 3],
            ['id_regla' => 2, 'prioridad' => 2, 'condicion' => 'ANY:A1,A2,A3,A4', 'id_clase_resultado' => 2],
            ['id_regla' => 3, 'prioridad' => 3, 'condicion' => 'ALWAYS',          'id_clase_resultado' => 1],
        ];
    }

    public static function clases(): array
    {
        return [
            1 => ['id_clase' => 1, 'nombre' => 'Dengue sin signos de alarma', 'nivel_urgencia' => 'Vigilancia',   'severidad' => 1, 'recomendacion' => 'r1', 'mensaje_riesgo' => 'm'],
            2 => ['id_clase' => 2, 'nombre' => 'Dengue con signos de alarma', 'nivel_urgencia' => 'El mismo día', 'severidad' => 2, 'recomendacion' => 'r2', 'mensaje_riesgo' => null],
            3 => ['id_clase' => 3, 'nombre' => 'Dengue grave',                'nivel_urgencia' => 'Inmediata',    'severidad' => 3, 'recomendacion' => 'r3', 'mensaje_riesgo' => null],
        ];
    }

    public static function signos(): array
    {
        $n = ['A1' => 'Dolor abdominal intenso y continuo', 'A2' => 'Vómito persistente', 'A3' => 'Sangrado de mucosas', 'A4' => 'Letargia o irritabilidad',
              'G1' => 'Choque o dificultad respiratoria', 'G2' => 'Sangrado grave', 'G3' => 'Alteración del estado de conciencia'];
        $s = [];
        foreach ($n as $c => $nombre) {
            $s[$c] = ['codigo' => $c, 'nombre' => $nombre];
        }
        return $s;
    }

    public static function clasificador(): Classifier
    {
        // Se pasan en desorden a propósito: el motor debe ordenarlas por prioridad.
        return new Classifier(array_reverse(self::reglas()), self::clases(), self::signos(), 1);
    }

    /** Clase esperada según las definiciones del protocolo, implementada aparte del motor. */
    public static function claseEsperada(string $bits): int
    {
        $a = substr($bits, 0, 4);
        $g = substr($bits, 4, 3);
        if (str_contains($g, '1')) {
            return 3;
        }
        return str_contains($a, '1') ? 2 : 1;
    }
}
