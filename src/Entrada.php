<?php
declare(strict_types=1);

namespace Dengue;

/** Validación de la entrada del formulario por lista blanca (RNF-05, RF-02 a RF-04, RF-13 a RF-15). */
final class Entrada
{
    public const DIAS    = ['1-2', '3-7', '8+'];
    public const SIGNOS  = ['A1', 'A2', 'A3', 'A4', 'G1', 'G2', 'G3'];
    /** Orden de los 8 bits de la matriz de casos. R = condición de riesgo (derivada, ver riesgo()). */
    public const BITS    = ['A1', 'A2', 'A3', 'A4', 'G1', 'G2', 'G3', 'R'];

    /** Enfermedades crónicas que se preguntan por separado. 'ninguna' es excluyente. */
    public const CRONICAS = [
        'diabetes' => 'Diabetes',
        'corazon'  => 'Problemas del corazón o presión alta',
        'rinon'    => 'Enfermedad del riñón',
        'defensas' => 'Defensas bajas (por ejemplo VIH, cáncer o medicinas que bajan las defensas)',
        'otra'     => 'Otra enfermedad crónica',
    ];
    public const EDAD_MAX = 110;

    /** Grupo de edad que se guarda (la edad exacta no se almacena: menos identificable). */
    public static function grupoEdad(int $edad): string
    {
        return match (true) {
            $edad < 5   => '0-4',
            $edad < 15  => '5-14',
            $edad < 65  => '15-64',
            default     => '65+',
        };
    }

    /**
     * Condición de riesgo R de las reglas, calculada a partir de las respuestas separadas:
     * menor de 5 años, 65 años o más, embarazo o al menos una enfermedad crónica.
     * @param string[] $cronicas
     */
    public static function riesgo(int $edad, string $embarazo, array $cronicas): bool
    {
        return $edad < 5 || $edad >= 65 || $embarazo === 'si' || $cronicas !== [];
    }

    /**
     * @param array<string,mixed> $post
     * @return array{errores:array<string,string>,datos:?array<string,mixed>}
     */
    public static function validar(array $post): array
    {
        $errores = [];

        $dias = $post['dias_fiebre'] ?? null;
        if (!is_string($dias) || !in_array($dias, self::DIAS, true)) {
            $errores['dias_fiebre'] = 'Indique cuántos días lleva con fiebre.';
        }

        // Edad: entero de 0 a 110 (0 = menor de un año).
        $edad = null;
        $e = $post['edad'] ?? null;
        if (is_string($e) && preg_match('/^\d{1,3}$/', trim($e)) === 1 && (int) trim($e) <= self::EDAD_MAX) {
            $edad = (int) trim($e);
        } else {
            $errores['edad'] = 'Escriba la edad en años, entre 0 y ' . self::EDAD_MAX . ' (0 si es menor de un año).';
        }

        // Embarazo: sí / no / no aplica. "Sí" solo es coherente entre 10 y 55 años.
        $emb = $post['embarazo'] ?? null;
        if (!is_string($emb) || !in_array($emb, ['si', 'no', 'na'], true)) {
            $errores['embarazo'] = 'Responda si la persona está embarazada (o elija «No aplica»).';
        } elseif ($emb === 'si' && $edad !== null && ($edad < 10 || $edad > 55)) {
            $errores['embarazo'] = 'La edad indicada no es compatible con un embarazo. Revise la edad o la respuesta.';
        }

        // Enfermedades crónicas: arreglo de la lista blanca; «ninguna» no se combina con otras.
        $cron = $post['cronica'] ?? null;
        $cronicas = [];
        if (!is_array($cron) || $cron === []) {
            $errores['cronica'] = 'Marque las enfermedades crónicas que tenga la persona o «Ninguna».';
        } else {
            $validas = array_merge(array_keys(self::CRONICAS), ['ninguna']);
            foreach ($cron as $v) {
                if (!is_string($v) || !in_array($v, $validas, true)) {
                    $errores['cronica'] = 'Opción de enfermedad crónica no válida.';
                    break;
                }
            }
            if (!isset($errores['cronica'])) {
                $cron = array_values(array_unique($cron));
                if (in_array('ninguna', $cron, true) && count($cron) > 1) {
                    $errores['cronica'] = '«Ninguna» no se puede combinar con otras opciones.';
                } else {
                    $cronicas = in_array('ninguna', $cron, true) ? [] : $cron;
                }
            }
        }

        $ub = Ubicaciones::normalizar($post['departamento'] ?? null, $post['municipio'] ?? null);
        if ($ub === null) {
            $dep = $post['departamento'] ?? null;
            if (is_string($dep) && $dep === Ubicaciones::CESAR) {
                $errores['municipio'] = 'Elija el municipio del Cesar donde vive la persona.';
            } else {
                $errores['departamento'] = 'Elija el departamento donde vive la persona.';
            }
        }

        $zona = $post['zona'] ?? null;
        if (!is_string($zona) || !isset(Ubicaciones::ZONAS[$zona])) {
            $errores['zona'] = 'Indique si vive en zona urbana o rural.';
        }
        $agua = $post['agua'] ?? null;
        if (!is_string($agua) || !isset(Ubicaciones::AGUA[$agua])) {
            $errores['agua'] = 'Indique si hay agua estancada cerca de la vivienda.';
        }

        $presentes = [];
        foreach (self::SIGNOS as $codigo) {
            $v = $post[$codigo] ?? null;
            if (!is_string($v) || !in_array($v, ['si', 'no'], true)) {
                $errores[$codigo] = 'Responda sí o no.';
            } else {
                $presentes[$codigo] = $v === 'si';
            }
        }

        if ($errores) {
            return ['errores' => $errores, 'datos' => null];
        }
        return ['errores' => [], 'datos' => [
            'dias_fiebre'  => $dias,
            'edad'         => $edad,
            'grupo_edad'   => self::grupoEdad($edad),
            'embarazo'     => $emb,
            'cronicas'     => $cronicas,
            'riesgo'       => self::riesgo($edad, $emb, $cronicas),
            'departamento' => $ub['departamento'],
            'municipio'    => $ub['municipio'],
            'zona'         => $zona,
            'agua'         => $agua,
            'presentes'    => $presentes,
        ]];
    }

    /** Convierte una cadena de 8 bits (A1..G3,R) en datos de entrada. */
    public static function desdeBits(string $bits): array
    {
        $presentes = [];
        foreach (self::SIGNOS as $i => $codigo) {
            $presentes[$codigo] = $bits[$i] === '1';
        }
        return ['presentes' => $presentes, 'riesgo' => $bits[7] === '1'];
    }
}
