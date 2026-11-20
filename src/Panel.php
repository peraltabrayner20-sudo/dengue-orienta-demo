<?php
declare(strict_types=1);

namespace Dengue;

use PDO;

/**
 * Datos agregados del panel de situación (RF-16, RF-17).
 * Solo devuelve conteos agrupados. Regla de privacidad: toda celda con 1 a K-1 evaluaciones se oculta
 * ("<5") o se agrupa en «Otros», para que un conteo pequeño no señale a una persona o a una familia.
 * Las evaluaciones del panel son orientaciones anónimas y voluntarias: NO son casos confirmados.
 */
final class Panel
{
    public const K = 5;

    public function __construct(private PDO $pdo, private string $origen = 'real')
    {
    }

    public function origen(): string
    {
        return $this->origen;
    }

    /** @return array<int,array<string,mixed>> */
    private function filas(string $sql, array $par = []): array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute(array_merge([$this->origen], $par));
        return $st->fetchAll();
    }

    public function total(): int
    {
        return (int) $this->filas('SELECT COUNT(*) AS n FROM evaluacion WHERE origen = ?')[0]['n'];
    }

    /** @return array{1:int,2:int,3:int} */
    public function porClase(): array
    {
        $r = [1 => 0, 2 => 0, 3 => 0];
        foreach ($this->filas('SELECT id_clase, COUNT(*) AS n FROM evaluacion WHERE origen = ? GROUP BY id_clase') as $f) {
            $r[(int) $f['id_clase']] = (int) $f['n'];
        }
        return $r;
    }

    /** Evaluaciones sin los campos de la versión 2 (registradas con el formulario anterior). */
    public function sinUbicacion(): int
    {
        return (int) $this->filas('SELECT COUNT(*) AS n FROM evaluacion WHERE origen = ? AND municipio IS NULL')[0]['n'];
    }

    /**
     * Agrupa por una columna y por clase. @return array<string,array{1:int,2:int,3:int}>
     * @param string $col columna de la lista blanca (nunca viene del usuario)
     */
    private function cruce(string $col): array
    {
        $permitidas = ['municipio', 'grupo_edad', 'zona', 'agua_estancada'];
        if (!in_array($col, $permitidas, true)) {
            throw new \InvalidArgumentException('Columna no permitida');
        }
        $r = [];
        foreach ($this->filas("SELECT $col AS k, id_clase, COUNT(*) AS n FROM evaluacion WHERE origen = ? AND $col IS NOT NULL GROUP BY $col, id_clase") as $f) {
            $r[$f['k']] ??= [1 => 0, 2 => 0, 3 => 0];
            $r[$f['k']][(int) $f['id_clase']] = (int) $f['n'];
        }
        return $r;
    }

    /**
     * Aplica el umbral K. Cada fila resultante: etiqueta, total y segmentos por clase; un segmento con 1..K-1
     * se pasa a 'oculto' (se muestra agrupado sin decir de qué clase es).
     * Las filas con total < K se funden en «Otros (menos de 5 evaluaciones)».
     * @param array<string,array{1:int,2:int,3:int}> $cruce
     * @param string[]|null $orden orden fijo de categorías; null = por total descendente
     * @return array{filas:array<int,array<string,mixed>>,ocultas:int}
     */
    public static function aplicarUmbral(array $cruce, ?array $orden = null, int $max = 10, array $etiquetas = []): array
    {
        $grandes = [];
        $otros = [1 => 0, 2 => 0, 3 => 0];
        $hayOtros = false;
        foreach ($cruce as $k => $c) {
            $t = array_sum($c);
            if ($t >= self::K) {
                $grandes[(string) $k] = $c;
            } elseif ($t > 0) {
                foreach ($c as $i => $n) {
                    $otros[$i] += $n;
                }
                $hayOtros = true;
            }
        }
        if ($orden !== null) {
            $tmp = [];
            foreach ($orden as $o) {
                if (isset($grandes[$o])) {
                    $tmp[$o] = $grandes[$o];
                }
            }
            $grandes = $tmp;
        } else {
            uasort($grandes, static fn($a, $b) => array_sum($b) <=> array_sum($a));
            if (count($grandes) > $max) {
                foreach (array_slice($grandes, $max, null, true) as $c) {
                    foreach ($c as $i => $n) {
                        $otros[$i] += $n;
                    }
                    $hayOtros = true;
                }
                $grandes = array_slice($grandes, 0, $max, true);
            }
        }
        $filas = [];
        foreach ($grandes as $k => $c) {
            $filas[] = self::fila($etiquetas[$k] ?? $k, $c);
        }
        $ocultas = 0;
        if ($hayOtros) {
            if (array_sum($otros) >= self::K) {
                $filas[] = self::fila('Otros (menos de ' . self::K . ' cada uno)', $otros);
            } else {
                $ocultas = array_sum($otros);   // ni siquiera juntas llegan a K: no se muestran
            }
        }
        return ['filas' => $filas, 'ocultas' => $ocultas];
    }

    /** @param array{1:int,2:int,3:int} $c */
    private static function fila(string $etiqueta, array $c): array
    {
        $seg = [1 => 0, 2 => 0, 3 => 0];
        $oculto = 0;
        foreach ($c as $i => $n) {
            if ($n >= self::K || $n === 0) {
                $seg[$i] = $n;
            } else {
                $oculto += $n;
            }
        }
        return ['etiqueta' => $etiqueta, 'total' => array_sum($c), 'seg' => $seg, 'oculto' => $oculto, 'clases' => $c];
    }

    public function municipios(): array
    {
        $etq = [Ubicaciones::FUERA => 'Fuera del Cesar'];
        return self::aplicarUmbral($this->cruce('municipio'), null, 10, $etq);
    }

    public function edades(): array
    {
        return self::aplicarUmbral($this->cruce('grupo_edad'), ['0-4', '5-14', '15-64', '65+'], 4, [
            '0-4' => '0 a 4 años', '5-14' => '5 a 14 años', '15-64' => '15 a 64 años', '65+' => '65 años o más',
        ]);
    }

    public function zonas(): array
    {
        return self::aplicarUmbral($this->cruce('zona'), ['urbana', 'rural'], 2, ['urbana' => 'Zona urbana', 'rural' => 'Zona rural']);
    }

    public function agua(): array
    {
        return self::aplicarUmbral($this->cruce('agua_estancada'), ['si', 'no', 'nose'], 3, [
            'si' => 'Con agua estancada cerca', 'no' => 'Sin agua estancada', 'nose' => 'No sabe',
        ]);
    }

    /** Evaluaciones por semana (lunes de inicio) de las últimas 8 semanas. @return array<int,array{semana:string,n:?int}> */
    public function semanas(int $n = 8): array
    {
        $r = [];
        foreach ($this->filas("SELECT DATE_SUB(DATE(fecha_hora), INTERVAL WEEKDAY(fecha_hora) DAY) AS lunes, COUNT(*) AS n
                               FROM evaluacion WHERE origen = ? GROUP BY lunes ORDER BY lunes DESC LIMIT " . (int) $n) as $f) {
            $r[(string) $f['lunes']] = (int) $f['n'];
        }
        ksort($r);
        $out = [];
        foreach ($r as $lunes => $c) {
            $out[] = ['semana' => $lunes, 'n' => ($c > 0 && $c < self::K) ? null : $c, 'real' => $c];
        }
        return $out;
    }

    /**
     * Tabla plana para exportar (CSV). Las celdas con 1 a K-1 evaluaciones salen como «<5».
     * @return array<int,array{dimension:string,categoria:string,clase:string,evaluaciones:string}>
     */
    public function exportar(): array
    {
        $nombres = [1 => 'Sin signos de alarma', 2 => 'Con signos de alarma', 3 => 'Dengue grave'];
        $f = fn(int $n): string => ($n > 0 && $n < self::K) ? '<' . self::K : (string) $n;
        $out = [];
        $c = $this->porClase();
        foreach ($c as $i => $n) {
            $out[] = ['dimension' => 'total', 'categoria' => 'Todas', 'clase' => $nombres[$i], 'evaluaciones' => $f($n)];
        }
        foreach (['municipio' => 'municipio', 'grupo_edad' => 'grupo de edad', 'zona' => 'zona', 'agua_estancada' => 'agua estancada'] as $col => $dim) {
            $cruce = $this->cruce($col);
            ksort($cruce);
            foreach ($cruce as $cat => $cl) {
                foreach ($cl as $i => $n) {
                    $out[] = ['dimension' => $dim, 'categoria' => (string) $cat, 'clase' => $nombres[$i], 'evaluaciones' => $f($n)];
                }
            }
        }
        return $out;
    }
}
