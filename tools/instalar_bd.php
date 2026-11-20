<?php
// Uso:  php tools/instalar_bd.php
// Crea la base de datos, las tablas y los datos iniciales (signos, clases y reglas).
// ATENCIÓN: borra y recrea las tablas.
declare(strict_types=1);
require __DIR__ . '/../src/autoload.php';

$c = Dengue\Config::get()['db'];
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], $c['port']), $c['user'], $c['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
foreach (['schema.sql', 'seed.sql'] as $f) {
    $sql = file_get_contents(__DIR__ . '/../database/' . $f);
    $pdo->exec($sql);
    echo "Ejecutado database/$f\n";
}
echo "Base de datos lista. Siguiente paso: php tools/crear_admin.php <usuario>\n";
