<?php
declare(strict_types=1);

namespace Dengue;

/** Autenticación del administrador (RF-12). Contraseñas con hash (password_hash / bcrypt). */
final class Auth
{
    public static function login(string $usuario, string $clave): bool
    {
        $st = Db::pdo()->prepare('SELECT id_usuario, hash_clave FROM usuario_admin WHERE usuario = ?');
        $st->execute([$usuario]);
        $u = $st->fetch();
        // Se verifica siempre contra un hash para no revelar si el usuario existe.
        $hash = $u['hash_clave'] ?? '$2y$10$PAUC9CTRCKuJVKaXyF.deuJgWuycIcGBBPW4BWlBc5EjT3TvtxGhO';
        $ok   = password_verify($clave, $hash) && $u;
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $u['id_usuario'];
        }
        return (bool) $ok;
    }

    public static function activo(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function exigir(): void
    {
        if (!self::activo()) {
            header('Location: login.php');
            exit;
        }
    }
}
