<?php
declare(strict_types=1);

namespace Dengue\Tests;

use Dengue\Entrada;
use Dengue\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Casos de prueba representativos de la Tabla 11 (CP-01 a CP-06) y regresión de los 256 escenarios. */
final class ClassifierTest extends TestCase
{
    private function clase(string $bits): array
    {
        return Fixtures::clasificador()->clasificar(Entrada::desdeBits($bits)['presentes']);
    }

    public static function representativos(): array
    {
        //            A1A2A3A4G1G2G3R
        return [
            'CP-01 sin signos, sin riesgo'  => ['00000000', 1, []],
            'CP-02 sin signos, con riesgo'  => ['00000001', 1, []],
            'CP-03 solo A2'                 => ['01000000', 2, ['A2']],
            'CP-04 A1 y A3'                 => ['10100000', 2, ['A1', 'A3']],
            'CP-05 caso límite: solo G1'    => ['00001000', 3, ['G1']],
            'CP-06 G2 y A4'                 => ['00010100', 3, ['G2']],
        ];
    }

    #[DataProvider('representativos')]
    public function testCasosRepresentativos(string $bits, int $claseEsperada, array $senales): void
    {
        $r = $this->clase($bits);
        $this->assertSame($claseEsperada, $r['id_clase']);
        $this->assertSame($senales, array_column($r['senales'], 'codigo'));   // RF-06: señales que lo activaron
    }

    public function testRegresionDeLos256Escenarios(): void
    {
        $errores = [];
        for ($i = 0; $i < 256; $i++) {
            $bits = str_pad(decbin($i), 8, '0', STR_PAD_LEFT);
            if ($this->clase($bits)['id_clase'] !== Fixtures::claseEsperada($bits)) {
                $errores[] = $bits;
            }
        }
        $this->assertSame([], $errores, 'Escenarios mal clasificados');
    }

    public function testNuncaSubclasifica(): void
    {
        for ($i = 0; $i < 256; $i++) {
            $bits = str_pad(decbin($i), 8, '0', STR_PAD_LEFT);
            $this->assertGreaterThanOrEqual(Fixtures::claseEsperada($bits), $this->clase($bits)['id_clase'], "Subclasificación en $bits");
        }
    }

    public function testLaCondicionDeRiesgoNoCambiaLaClase(): void
    {
        for ($i = 0; $i < 128; $i++) {
            $b = str_pad(decbin($i), 7, '0', STR_PAD_LEFT);
            $this->assertSame($this->clase($b . '0')['id_clase'], $this->clase($b . '1')['id_clase']);
        }
    }

    public function testElMotorUsaLaPrioridadYNoElOrdenDeLasFilas(): void
    {
        // Con G1 y A1 presentes debe ganar la regla de mayor prioridad (grave).
        $this->assertSame(3, $this->clase('10001000')['id_clase']);
    }

    public function testCondicionNoSoportadaLanzaExcepcion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \Dengue\Classifier::cumple('DROP TABLE regla', []);
    }

    public function testCambiarUnaReglaNoRequiereCambiarElCodigo(): void   // RNF-07
    {
        $reglas = Fixtures::reglas();
        $reglas[1]['condicion'] = 'ANY:A1,A2';   // A3 y A4 dejan de ser señales de alarma
        $clf = new \Dengue\Classifier($reglas, Fixtures::clases(), Fixtures::signos(), 2);
        $this->assertSame(1, $clf->clasificar(Entrada::desdeBits('00100000')['presentes'])['id_clase']);
        $this->assertSame(2, $clf->clasificar(Entrada::desdeBits('10000000')['presentes'])['id_clase']);
    }
}
