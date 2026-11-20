<?php
declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use Dengue\Csrf;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::valido($_POST['csrf'] ?? null)) {
    $_SESSION = [];
    session_destroy();
}
header('Location: login.php');
