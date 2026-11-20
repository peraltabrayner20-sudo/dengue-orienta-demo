<?php
// Uso:  php tools/verificar_seguridad.php [--base=http://localhost/dengue-orienta/public]
// Lista de comprobación basada en OWASP Top 10 (2021) y los casos CP-07 y CP-08 de la Fase 3.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$base = rtrim((string) opcion($argv, 'base', 'http://127.0.0.1:8080'), '/');
$resultados = [];
$desde = ahoraBd();
function chequeo(string $id, string $desc, bool $ok, string $detalle = ''): void
{
    global $resultados;
    $resultados[] = [$id, $desc, $ok, $detalle];
}

// --- Sesión nueva: aviso de privacidad ---
$ch = nuevoCliente();
$r  = http($base . '/evaluar.php', null, $ch);
$t  = extraerCsrf($r['cuerpo']);
$h  = strtolower($r['cabeceras']);
chequeo('RF-01/CP-07', 'Sin aceptar el aviso no se muestra el formulario', !str_contains($r['cuerpo'], 'id="form-evaluacion"') && $t !== null);
chequeo('A05', 'Cabecera Content-Security-Policy', str_contains($h, 'content-security-policy:'));
chequeo('A05', 'Cabecera X-Content-Type-Options: nosniff', str_contains($h, 'x-content-type-options: nosniff'));
chequeo('A05', 'Cabecera X-Frame-Options: DENY', str_contains($h, 'x-frame-options: deny'));
chequeo('A05', 'Cabecera Referrer-Policy', str_contains($h, 'referrer-policy:'));
chequeo('A05', 'Cookie de sesión con HttpOnly y SameSite', (bool) preg_match('/set-cookie: denguesess=[^\r\n]*httponly/i', $r['cabeceras']) && (bool) preg_match('/set-cookie:[^\r\n]*samesite=/i', $r['cabeceras']));

// --- POST sin consentimiento ---
$r2 = http($base . '/evaluar.php', ['csrf' => (string) $t, 'accion' => 'evaluar'] + camposDesdeBits('00000000', '1-2'), $ch);
chequeo('RF-01', 'Evaluar sin haber aceptado el aviso es rechazado (403)', $r2['codigo'] === 403, 'HTTP ' . $r2['codigo']);

// --- POST sin CSRF ---
$ch2 = nuevoCliente();
http($base . '/evaluar.php', null, $ch2);
$r3 = http($base . '/evaluar.php', ['accion' => 'aceptar', 'acepto' => '1'], $ch2);
chequeo('A01/CSRF', 'POST sin token CSRF es rechazado (403)', $r3['codigo'] === 403, 'HTTP ' . $r3['codigo']);

// --- CP-08: entrada maliciosa ---
$ch3 = nuevoCliente();
$p = http($base . '/evaluar.php', null, $ch3);
$tk = extraerCsrf($p['cuerpo']);
$p = http($base . '/evaluar.php', ['csrf' => (string) $tk, 'accion' => 'aceptar', 'acepto' => '1'], $ch3, true);
$tk = extraerCsrf($p['cuerpo']);
$malo = camposDesdeBits('00000000', '1-2');
$malo['dias_fiebre'] = "1-2'; DROP TABLE evaluacion;-- ";
$malo['A1'] = '<script>alert(1)</script>';
$p = http($base . '/evaluar.php', ['csrf' => (string) $tk, 'accion' => 'evaluar'] + $malo, $ch3);
chequeo('CP-08/A03', 'Entrada con SQL/XSS es rechazada (422) sin error de base de datos', $p['codigo'] === 422 && !stripos($p['cuerpo'], 'SQLSTATE') && !str_contains($p['cuerpo'], '<script>alert(1)'), 'HTTP ' . $p['codigo']);
$existe = (int) Dengue\Db::pdo()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'evaluacion'")->fetchColumn();
chequeo('CP-08/A03', 'La tabla evaluacion sigue existiendo tras el intento de inyección', $existe === 1);

// --- Campos de la versión 2: edad, embarazo, ubicación ---
$ch5 = nuevoCliente();
$p = http($base . '/evaluar.php', null, $ch5);
$tk = extraerCsrf($p['cuerpo']);
$p = http($base . '/evaluar.php', ['csrf' => (string) $tk, 'accion' => 'aceptar', 'acepto' => '1'], $ch5, true);
$tk = extraerCsrf($p['cuerpo']);
$m1 = camposDesdeBits('00000000', '1-2');
$m1['edad'] = '150';
$p = http($base . '/evaluar.php', ['csrf' => (string) $tk, 'accion' => 'evaluar'] + $m1, $ch5);
chequeo('RF-13/CP-09', 'Edad fuera de rango (150) es rechazada (422)', $p['codigo'] === 422, 'HTTP ' . $p['codigo']);
$m2 = camposDesdeBits('00000000', '1-2');
$m2['municipio'] = "Valledupar'; DROP TABLE evaluacion;--";
$p = http($base . '/evaluar.php', ['csrf' => (string) $tk, 'accion' => 'evaluar'] + $m2, $ch5);
chequeo('CP-09/A03', 'Municipio fuera del catálogo (con SQL) es rechazado (422)', $p['codigo'] === 422 && !stripos($p['cuerpo'], 'SQLSTATE'), 'HTTP ' . $p['codigo']);
$m3 = camposDesdeBits('00000000', '1-2');
$m3['edad'] = '4';
$m3['embarazo'] = 'si';
$p = http($base . '/evaluar.php', ['csrf' => (string) $tk, 'accion' => 'evaluar'] + $m3, $ch5);
chequeo('CP-09', 'Embarazo con una edad incompatible (4 años) es rechazado (422)', $p['codigo'] === 422, 'HTTP ' . $p['codigo']);

// --- Panel público ---
$pn = http($base . '/panel.php?demo=1', null, nuevoCliente());
chequeo('RF-16/A05', 'El panel público responde 200 con la política CSP y sin estilos en línea', $pn['codigo'] === 200 && str_contains(strtolower($pn['cabeceras']), 'content-security-policy:') && !preg_match('/\sstyle="/', $pn['cuerpo']), 'HTTP ' . $pn['codigo']);
$csv = http($base . '/panel.php?csv=1&demo=1', null, nuevoCliente());
chequeo('RF-17/RNF-06', 'La exportación del panel no contiene identificadores (solo conteos agrupados)', $csv['codigo'] === 200 && !preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-/', $csv['cuerpo']) && str_starts_with(ltrim($csv['cuerpo'], "\xEF\xBB\xBF"), 'dimension,categoria,clase,evaluaciones'), 'HTTP ' . $csv['codigo']);

// --- Administración ---
$ch4 = nuevoCliente();
$a = http($base . '/admin/index.php', null, $ch4);
chequeo('A01', 'Panel de administración sin sesión redirige al login (302)', $a['codigo'] === 302 && str_contains(strtolower($a['cabeceras']), 'location: login.php'), 'HTTP ' . $a['codigo']);
$a = http($base . '/admin/export.php?tipo=evaluaciones', null, $ch4);
chequeo('A01', 'Exportación CSV sin sesión es rechazada (302)', $a['codigo'] === 302, 'HTTP ' . $a['codigo']);

// --- Datos personales (RNF-06) ---
$cols = Dengue\Db::pdo()->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('evaluacion','evaluacion_signo')")->fetchAll(PDO::FETCH_COLUMN);
$prohibidas = array_filter($cols, static fn($c) => (bool) preg_match('/nombre|documento|cedula|direccion|telefono|correo|email|(^|_)ip($|_)/i', (string) $c));
chequeo('RNF-06', 'Las tablas de evaluación no tienen columnas de identificación personal', !$prohibidas, $prohibidas ? implode(', ', $prohibidas) : '');
$hash = (string) Dengue\Db::pdo()->query('SELECT hash_clave FROM usuario_admin LIMIT 1')->fetchColumn();
chequeo('A02', 'Las contraseñas del administrador están almacenadas con hash bcrypt', $hash === '' || str_starts_with($hash, '$2y$'));

marcarPruebas($desde);   // las evaluaciones enviadas por las pruebas no entran al panel
$fallos = 0;
echo "Verificación de seguridad básica (OWASP Top 10 · 2021)\n";
foreach ($resultados as [$id, $desc, $ok, $det]) {
    printf("[%s] %-14s %s%s\n", $ok ? 'OK ' : 'FALLA', $id, $desc, $det ? " ($det)" : '');
    $fallos += $ok ? 0 : 1;
}
printf("\n%d comprobaciones, %d con falla.\n", count($resultados), $fallos);
exit($fallos ? 2 : 0);
