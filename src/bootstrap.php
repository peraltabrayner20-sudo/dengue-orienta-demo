<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

// Cookies de sesión seguras: HttpOnly, SameSite y Secure cuando hay HTTPS.
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('DENGUESESS');
session_start();

// Cabeceras de seguridad (OWASP A05).
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
if ($https) {
    header('Strict-Transport-Security: max-age=31536000');
}
