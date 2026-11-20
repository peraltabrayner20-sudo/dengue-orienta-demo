<?php
// Uso:  php tools/crear_admin.php <usuario> [<clave>]
// Si no se indica la clave se pide por consola. La clave se guarda con hash bcrypt.
declare(strict_types=1);
require __DIR__ . '/../src/autoload.php';

$usuario = $argv[1] ?? null;
if (!$usuario || !preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $usuario)) {
    fwrite(STDERR, "Uso: php tools/crear_admin.php <usuario> [<clave>]\n");
    exit(1);
}
$clave = $argv[2] ?? null;
if ($clave === null) {
    fwrite(STDOUT, 'Contraseña (mínimo 8 caracteres): ');
    $clave = trim((string) fgets(STDIN));
}
if (strlen($clave) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}
$pdo = Dengue\Db::pdo();
$pdo->prepare('REPLACE INTO usuario_admin (usuario, hash_clave) VALUES (?, ?)')
    ->execute([$usuario, password_hash($clave, PASSWORD_BCRYPT)]);
echo "Administrador '$usuario' creado o actualizado.\n";
