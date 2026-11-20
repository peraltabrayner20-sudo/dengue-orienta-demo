<?php
declare(strict_types=1);

namespace Dengue;

use PDO;

final class RuleRepository
{
    public static function cargar(PDO $pdo): Classifier
    {
        $v = $pdo->query('SELECT id_version_reglas FROM version_reglas WHERE activa = 1 ORDER BY id_version_reglas DESC LIMIT 1')->fetch();
        if (!$v) {
            throw new \RuntimeException('No hay una versión de reglas activa.');
        }
        $id = (int) $v['id_version_reglas'];

        $st = $pdo->prepare('SELECT id_regla, prioridad, condicion, id_clase_resultado FROM regla WHERE id_version_reglas = ?');
        $st->execute([$id]);
        $reglas = $st->fetchAll();

        $clases = [];
        foreach ($pdo->query('SELECT * FROM clase_clasificacion')->fetchAll() as $c) {
            $clases[(int) $c['id_clase']] = $c;
        }
        $signos = [];
        foreach ($pdo->query('SELECT * FROM signo ORDER BY id_signo')->fetchAll() as $s) {
            $signos[$s['codigo']] = $s;
        }
        return new Classifier($reglas, $clases, $signos, $id);
    }

    /** @return array<int,array<string,mixed>> señales en orden de formulario */
    public static function signos(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM signo ORDER BY id_signo')->fetchAll();
    }
}
