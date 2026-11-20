<?php
declare(strict_types=1);

namespace Dengue;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function valido(mixed $recibido): bool
    {
        return is_string($recibido) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $recibido);
    }
}
