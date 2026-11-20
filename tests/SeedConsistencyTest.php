<?php
declare(strict_types=1);

namespace Dengue\Tests;

use Dengue\{Db, RuleRepository};
use Dengue\Tests\Support\Fixtures;
use Dengue\Entrada;
use PHPUnit\Framework\TestCase;

/** Pruebas de integración con la base de datos (se omiten si no hay conexión). */
final class SeedConsistencyTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        try {
            $this->pdo = Db::pdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Sin conexión a la base de datos: ' . $e->getMessage());
        }
    }

    public function testLasReglasDeLaBaseDeDatosClasificanComoLaFixture(): void
    {
        $clf = RuleRepository::cargar($this->pdo);
        for ($i = 0; $i < 256; $i++) {
            $bits = str_pad(decbin($i), 8, '0', STR_PAD_LEFT);
            $r = $clf->clasificar(\Dengue\Entrada::desdeBits($bits)['presentes']);
            $this->assertSame(Fixtures::claseEsperada($bits), $r['id_clase'], "Desalineación en $bits");
        }
    }

    public function testElRegistroDeEvaluacionNoTieneDatosPersonales(): void   // RNF-06
    {
        $cols = $this->pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('evaluacion','evaluacion_signo')")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertNotEmpty($cols);
        foreach ($cols as $c) {
            $this->assertDoesNotMatchRegularExpression('/nombre|documento|cedula|direccion|telefono|correo|email|(^|_)ip($|_)/i', (string) $c);
        }
    }

    public function testRegistrarEvaluacionGuardaLosSieteSignosYLaVersion(): void   // RF-10
    {
        $clf = RuleRepository::cargar($this->pdo);
        $datos = Entrada::validar(['dias_fiebre' => '3-7', 'edad' => '70', 'embarazo' => 'na', 'cronica' => ['diabetes'],
            'departamento' => 'Cesar', 'municipio' => 'Valledupar', 'zona' => 'urbana', 'agua' => 'si', 'A2' => 'si', 'A1' => 'no', 'A3' => 'no', 'A4' => 'no', 'G1' => 'no', 'G2' => 'no', 'G3' => 'no'])['datos'];
        $res = $clf->clasificar($datos['presentes']);
        $id = \Dengue\Evaluacion::registrar($this->pdo, $datos, $res['id_clase'], $clf->versionId(), 1.23);
        try {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
            $n = $this->pdo->prepare('SELECT COUNT(*) FROM evaluacion_signo WHERE id_evaluacion = ?');
            $n->execute([$id]);
            $this->assertSame(7, (int) $n->fetchColumn());
            $f = $this->pdo->prepare('SELECT grupo_edad, embarazo, condiciones_cronicas, condicion_riesgo, departamento, municipio, zona, agua_estancada, origen FROM evaluacion WHERE id_evaluacion = ?');
            $f->execute([$id]);
            $this->assertSame(['grupo_edad' => '65+', 'embarazo' => 'na', 'condiciones_cronicas' => 'diabetes', 'condicion_riesgo' => 1,
                               'departamento' => 'Cesar', 'municipio' => 'Valledupar', 'zona' => 'urbana', 'agua_estancada' => 'si', 'origen' => 'real'], $f->fetch());
        } finally {
            // Limpieza: borra la evaluación de prueba (evaluacion_signo se borra en cascada).
            $this->pdo->prepare('DELETE FROM evaluacion WHERE id_evaluacion = ?')->execute([$id]);
        }
    }
}

