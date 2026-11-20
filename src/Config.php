<?php
declare(strict_types=1);

namespace Dengue;

final class Config
{
    private static ?array $cfg = null;

    /** @return array{db: array{host:string,port:int,name:string,user:string,pass:string}} */
    public static function get(): array
    {
        if (self::$cfg === null) {
            $dir  = dirname(__DIR__) . '/config/';
            $file = is_file($dir . 'config.php') ? $dir . 'config.php' : $dir . 'config.example.php';
            $cfg  = require $file;
            // Variables de entorno (útiles para pruebas automatizadas).
            $env = [
                'host' => 'DENGUE_DB_HOST', 'port' => 'DENGUE_DB_PORT', 'name' => 'DENGUE_DB_NAME',
                'user' => 'DENGUE_DB_USER', 'pass' => 'DENGUE_DB_PASS',
            ];
            foreach ($env as $k => $var) {
                $v = getenv($var);
                if ($v !== false) {
                    $cfg['db'][$k] = $k === 'port' ? (int) $v : $v;
                }
            }
            self::$cfg = $cfg;
        }
        return self::$cfg;
    }
}
