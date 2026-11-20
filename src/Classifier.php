<?php
declare(strict_types=1);

namespace Dengue;

/**
 * Motor de clasificación (capa de lógica de negocio).
 * Las reglas vienen de la base de datos (tabla regla) y se evalúan por prioridad:
 * la primera que se cumple define la clase. Cambiar una regla no exige tocar el código (RNF-07).
 *
 * Gramática de condiciones:  ANY:A1,A2  -> se cumple si al menos una de esas señales está presente
 *                            ALWAYS     -> siempre se cumple (regla por defecto)
 */
final class Classifier
{
    /** @var array<int,array<string,mixed>> */
    private array $reglas;

    /**
     * @param array<int,array<string,mixed>> $reglas  filas de la tabla regla
     * @param array<int,array<string,mixed>> $clases  filas de clase_clasificacion indexadas por id_clase
     * @param array<string,array<string,mixed>> $signos filas de signo indexadas por código
     */
    public function __construct(array $reglas, private array $clases, private array $signos, private int $versionId = 0)
    {
        usort($reglas, static fn(array $a, array $b): int => $a['prioridad'] <=> $b['prioridad']);
        $this->reglas = $reglas;
    }

    public function versionId(): int
    {
        return $this->versionId;
    }

    /** @param array<string,bool> $presentes código de señal => presente */
    public static function cumple(string $condicion, array $presentes): bool
    {
        $c = trim($condicion);
        if ($c === 'ALWAYS') {
            return true;
        }
        if (preg_match('/^ANY:([A-Z0-9,]+)$/', $c, $m) === 1) {
            foreach (explode(',', $m[1]) as $codigo) {
                if (!empty($presentes[$codigo])) {
                    return true;
                }
            }
            return false;
        }
        throw new \InvalidArgumentException('Condición de regla no soportada: ' . $c);
    }

    /** @return string[] */
    public static function codigosDe(string $condicion): array
    {
        return preg_match('/^ANY:([A-Z0-9,]+)$/', trim($condicion), $m) === 1 ? explode(',', $m[1]) : [];
    }

    /**
     * @param array<string,bool> $presentes
     * @return array{id_clase:int,nombre:string,urgencia:string,severidad:int,recomendacion:string,mensaje_riesgo:?string,senales:array<int,array{codigo:string,nombre:string}>,id_regla:int}
     */
    public function clasificar(array $presentes): array
    {
        foreach ($this->reglas as $r) {
            if (!self::cumple((string) $r['condicion'], $presentes)) {
                continue;
            }
            $clase   = $this->clases[(int) $r['id_clase_resultado']];
            $senales = [];
            foreach (self::codigosDe((string) $r['condicion']) as $codigo) {
                if (!empty($presentes[$codigo])) {
                    $senales[] = ['codigo' => $codigo, 'nombre' => (string) ($this->signos[$codigo]['nombre'] ?? $codigo)];
                }
            }
            return [
                'id_clase'       => (int) $clase['id_clase'],
                'nombre'         => (string) $clase['nombre'],
                'urgencia'       => (string) $clase['nivel_urgencia'],
                'severidad'      => (int) $clase['severidad'],
                'recomendacion'  => (string) $clase['recomendacion'],
                'mensaje_riesgo' => $clase['mensaje_riesgo'] !== null ? (string) $clase['mensaje_riesgo'] : null,
                'senales'        => $senales,
                'id_regla'       => (int) $r['id_regla'],
            ];
        }
        throw new \RuntimeException('Ninguna regla aplica: revise la tabla regla.');
    }
}
