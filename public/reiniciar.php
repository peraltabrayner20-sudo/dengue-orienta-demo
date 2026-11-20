<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Dengue\{Csrf, View};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::valido($_POST['csrf'] ?? null)) {
    View::error(403, 'Solicitud no válida.');
    exit;
}
// Borra toda la sesión: respuestas, resultado y consentimiento (RF-11).
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
}
session_destroy();
header('Location: index.php');
