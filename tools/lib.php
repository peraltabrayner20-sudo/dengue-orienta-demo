<?php
// Funciones compartidas por las herramientas de prueba (línea de comandos).
declare(strict_types=1);
require_once __DIR__ . '/../src/autoload.php';

const CLASES = [1 => 'Dengue sin signos de alarma', 2 => 'Dengue con signos de alarma', 3 => 'Dengue grave'];

/** Convierte "sin", "Dengue con signos de alarma", "grave", "1"... en 1, 2 o 3 (null si no se entiende). */
function normalizarClase(string $v): ?int
{
    $t = mb_strtolower(trim($v), 'UTF-8');
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    if ($t === '') {
        return null;
    }
    if (in_array($t, ['1', '2', '3'], true)) {
        return (int) $t;
    }
    if (str_contains($t, 'grave')) {
        return 3;
    }
    if (preg_match('/\bsin\b/', $t)) {
        return 1;
    }
    if (str_contains($t, 'alarma') || preg_match('/\bcon\b/', $t)) {
        return 2;
    }
    return null;
}

/** Intervalo de confianza de Wilson (Wilson, 1927) para una proporción k/n. */
function wilson(int $k, int $n, float $z = 1.96): array
{
    if ($n === 0) {
        return [0.0, 0.0];
    }
    $p = $k / $n;
    $den = 1 + $z * $z / $n;
    $centro = $p + $z * $z / (2 * $n);
    $mar = $z * sqrt($p * (1 - $p) / $n + $z * $z / (4 * $n * $n));
    return [($centro - $mar) / $den, ($centro + $mar) / $den];
}

/** Percentil por rango más cercano. */
function percentil(array $valores, float $p): float
{
    if (!$valores) {
        return 0.0;
    }
    sort($valores);
    return (float) $valores[max(0, (int) ceil($p / 100 * count($valores)) - 1)];
}

function mediana(array $v): float
{
    return percentil($v, 50);
}

/** Lee un CSV (separador coma o punto y coma) como lista de filas asociativas. */
function leerCsv(string $ruta): array
{
    if (!is_file($ruta)) {
        fwrite(STDERR, "No existe el archivo: $ruta\n");
        exit(1);
    }
    $f = fopen($ruta, 'r');
    $linea = fgets($f);
    $sep = substr_count((string) $linea, ';') > substr_count((string) $linea, ',') ? ';' : ',';
    rewind($f);
    $cab = fgetcsv($f, 0, $sep);
    $cab[0] = ltrim((string) $cab[0], "\xEF\xBB\xBF");
    $cab = array_map(static fn($c) => trim((string) $c), $cab);
    $filas = [];
    while (($r = fgetcsv($f, 0, $sep)) !== false) {
        if ($r === [null] || count($r) < count($cab)) {
            continue;
        }
        $filas[] = array_combine($cab, array_slice($r, 0, count($cab)));
    }
    fclose($f);
    return $filas;
}

function http(string $url, ?array $post, $ch, bool $seguir = false): array
{
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => $seguir,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_POST           => $post !== null,
        CURLOPT_POSTFIELDS     => $post !== null ? http_build_query($post) : null,
        CURLOPT_HTTPGET        => $post === null,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        return ['codigo' => 0, 'cabeceras' => '', 'cuerpo' => '', 'ms' => 0.0, 'error' => curl_error($ch)];
    }
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    return [
        'codigo'    => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'cabeceras' => substr($resp, 0, $hs),
        'cuerpo'    => substr($resp, $hs),
        'ms'        => curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000,
        'url_final' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
        'error'     => '',
    ];
}

function nuevoCliente()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, '');   // activa el manejo de cookies en memoria
    return $ch;
}

function extraerCsrf(string $html): ?string
{
    return preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $m) === 1 ? $m[1] : null;
}

/**
 * Recorre el flujo real del usuario: aviso -> aceptar -> formulario -> evaluar.
 * @return array{clase:?int,ms:float,codigo:int,error:string}
 */
function evaluarPorHttp(string $base, array $campos): array
{
    $ch = nuevoCliente();
    $r  = http($base . '/evaluar.php', null, $ch);
    $t  = extraerCsrf($r['cuerpo']);
    if ($t === null) {
        return ['clase' => null, 'ms' => 0.0, 'codigo' => $r['codigo'], 'error' => 'sin token CSRF en el aviso'];
    }
    $r = http($base . '/evaluar.php', ['csrf' => $t, 'accion' => 'aceptar', 'acepto' => '1'], $ch, true);
    $t = extraerCsrf($r['cuerpo']);
    if ($t === null || !str_contains($r['cuerpo'], 'id="form-evaluacion"')) {
        return ['clase' => null, 'ms' => 0.0, 'codigo' => $r['codigo'], 'error' => 'formulario no disponible tras aceptar'];
    }
    $r = http($base . '/evaluar.php', ['csrf' => $t, 'accion' => 'evaluar'] + $campos, $ch, true);
    $clase = preg_match('/data-clase-id="(\d)"/', $r['cuerpo'], $m) === 1 ? (int) $m[1] : null;
    curl_close($ch);
    return ['clase' => $clase, 'ms' => $r['ms'], 'codigo' => $r['codigo'], 'error' => $r['error']];
}

/**
 * Arma los campos del formulario v2 para una combinación de 8 bits (A1..G3,R).
 * R=1 se obtiene con una de cuatro condiciones de riesgo (según $variante) y R=0 con ninguna:
 * así las pruebas de regresión también ejercitan la derivación de R a partir de las respuestas separadas.
 */
function camposDesdeBits(string $bits, string $dias, int $variante = 0): array
{
    $c = ['dias_fiebre' => $dias, 'departamento' => 'Cesar', 'municipio' => 'Valledupar', 'zona' => 'urbana', 'agua' => 'no'];
    if ($bits[7] === '1') {
        switch ($variante % 4) {
            case 0: $c += ['edad' => '3',  'embarazo' => 'na', 'cronica' => ['ninguna']]; break;     // menor de 5 años
            case 1: $c += ['edad' => '70', 'embarazo' => 'na', 'cronica' => ['ninguna']]; break;     // 65 años o más
            case 2: $c += ['edad' => '28', 'embarazo' => 'si', 'cronica' => ['ninguna']]; break;     // embarazo
            default: $c += ['edad' => '40', 'embarazo' => 'no', 'cronica' => ['diabetes', 'rinon']]; // enfermedad crónica
        }
    } else {
        $c += ['edad' => (string) (5 + $variante % 60), 'embarazo' => $variante % 2 ? 'no' : 'na', 'cronica' => ['ninguna']];
    }
    foreach (Dengue\Entrada::SIGNOS as $i => $codigo) {
        $c[$codigo] = $bits[$i] === '1' ? 'si' : 'no';
    }
    return $c;
}

function opcion(array $argv, string $nombre, ?string $defecto = null): ?string
{
    foreach ($argv as $a) {
        if (str_starts_with($a, "--$nombre=")) {
            return substr($a, strlen($nombre) + 3);
        }
    }
    return $defecto;
}

/**
 * Las herramientas de prueba envían evaluaciones sintéticas por el formulario real. Para que no contaminen el
 * panel de situación, al terminar se marcan como origen = 'demo' (las que se crearon desde $desde).
 */
function marcarPruebas(string $desde): int
{
    $st = Dengue\Db::pdo()->prepare("UPDATE evaluacion SET origen = 'demo' WHERE origen = 'real' AND fecha_hora >= ?");
    $st->execute([$desde]);
    return $st->rowCount();
}

function ahoraBd(): string
{
    return (string) Dengue\Db::pdo()->query('SELECT NOW()')->fetchColumn();
}
