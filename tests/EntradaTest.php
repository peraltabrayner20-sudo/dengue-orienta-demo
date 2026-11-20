<?php
declare(strict_types=1);

namespace Dengue\Tests;

use Dengue\Entrada;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EntradaTest extends TestCase
{
    private function valida(): array
    {
        $p = ['dias_fiebre' => '3-7', 'edad' => '30', 'embarazo' => 'no', 'cronica' => ['ninguna'],
              'departamento' => 'Cesar', 'municipio' => 'Valledupar', 'zona' => 'urbana', 'agua' => 'no'];
        foreach (Entrada::SIGNOS as $s) {
            $p[$s] = 'no';
        }
        return $p;
    }

    public function testEntradaValidaSeAcepta(): void
    {
        $r = Entrada::validar($this->valida());
        $this->assertSame([], $r['errores']);
        $this->assertFalse($r['datos']['riesgo']);
    }

    public function testNoSeAvanzaSinResponder(): void   // RF-02, RF-03, RF-04
    {
        foreach (array_merge(['dias_fiebre', 'edad', 'embarazo', 'cronica', 'departamento', 'zona', 'agua'], Entrada::SIGNOS) as $campo) {
            $p = $this->valida();
            unset($p[$campo]);
            $r = Entrada::validar($p);
            $this->assertArrayHasKey($campo, $r['errores'], "Debe exigir $campo");
            $this->assertNull($r['datos']);
        }
    }

    public function testCP08EntradaMaliciosaSeRechaza(): void
    {
        $p = $this->valida();
        $p['dias_fiebre'] = "1-2'; DROP TABLE evaluacion;--";
        $p['A1'] = '<script>alert(1)</script>';
        $r = Entrada::validar($p);
        $this->assertArrayHasKey('dias_fiebre', $r['errores']);
        $this->assertArrayHasKey('A1', $r['errores']);
    }

    public function testValoresEnFormaDeArregloSeRechazan(): void
    {
        $p = $this->valida();
        $p['A2'] = ['si'];
        $this->assertArrayHasKey('A2', Entrada::validar($p)['errores']);
    }

    public function testDesdeBits(): void
    {
        $d = Entrada::desdeBits('10000011');
        $this->assertTrue($d['presentes']['A1']);
        $this->assertTrue($d['presentes']['G3']);
        $this->assertTrue($d['riesgo']);
        $this->assertFalse($d['presentes']['A2']);
    }

    /** @return array<string,array{0:array,1:bool}> */
    public static function casosDeRiesgo(): array
    {
        return [
            'sin ninguna condición'      => [['edad' => '30', 'embarazo' => 'no', 'cronica' => ['ninguna']], false],
            'menor de 5 años (4)'        => [['edad' => '4', 'embarazo' => 'na', 'cronica' => ['ninguna']], true],
            'justo 5 años'               => [['edad' => '5', 'embarazo' => 'na', 'cronica' => ['ninguna']], false],
            'menor de un año (0)'        => [['edad' => '0', 'embarazo' => 'na', 'cronica' => ['ninguna']], true],
            '64 años'                    => [['edad' => '64', 'embarazo' => 'na', 'cronica' => ['ninguna']], false],
            '65 años'                    => [['edad' => '65', 'embarazo' => 'na', 'cronica' => ['ninguna']], true],
            'embarazo'                   => [['edad' => '27', 'embarazo' => 'si', 'cronica' => ['ninguna']], true],
            'una enfermedad crónica'     => [['edad' => '40', 'embarazo' => 'no', 'cronica' => ['diabetes']], true],
            'varias enfermedades'        => [['edad' => '40', 'embarazo' => 'no', 'cronica' => ['corazon', 'rinon', 'otra']], true],
        ];
    }

    #[DataProvider('casosDeRiesgo')]
    public function testRiesgoSeDerivaDeLasRespuestasSeparadas(array $cambios, bool $esperado): void   // RF-13, RF-14
    {
        $r = Entrada::validar($cambios + $this->valida());
        $this->assertSame([], $r['errores']);
        $this->assertSame($esperado, $r['datos']['riesgo']);
    }

    public function testSoloSeGuardaElGrupoDeEdad(): void   // RNF-06
    {
        $r = Entrada::validar(['edad' => '70'] + $this->valida());
        $this->assertSame('65+', $r['datos']['grupo_edad']);
        $this->assertSame('0-4', Entrada::grupoEdad(0));
        $this->assertSame('5-14', Entrada::grupoEdad(14));
        $this->assertSame('15-64', Entrada::grupoEdad(15));
    }

    public function testEdadInvalidaSeRechaza(): void
    {
        foreach (['-1', '111', 'abc', '3.5', '', '１２'] as $edad) {
            $r = Entrada::validar(['edad' => $edad] + $this->valida());
            $this->assertArrayHasKey('edad', $r['errores'], "Edad '$edad' debe rechazarse");
        }
    }

    public function testEmbarazoIncoherenteConLaEdadSeRechaza(): void
    {
        foreach (['9', '56', '4', '80'] as $edad) {
            $r = Entrada::validar(['edad' => $edad, 'embarazo' => 'si'] + $this->valida());
            $this->assertArrayHasKey('embarazo', $r['errores'], "Embarazo con $edad años");
        }
        $this->assertSame([], Entrada::validar(['edad' => '10', 'embarazo' => 'si'] + $this->valida())['errores']);
    }

    public function testNingunaNoSeCombinaYLasOpcionesSonDeListaBlanca(): void
    {
        $this->assertArrayHasKey('cronica', Entrada::validar(['cronica' => ['ninguna', 'diabetes']] + $this->valida())['errores']);
        $this->assertArrayHasKey('cronica', Entrada::validar(['cronica' => ['cancer']] + $this->valida())['errores']);
        $this->assertArrayHasKey('cronica', Entrada::validar(['cronica' => 'diabetes'] + $this->valida())['errores']);
        $this->assertArrayHasKey('cronica', Entrada::validar(['cronica' => []] + $this->valida())['errores']);
    }

    public function testUbicacionSoloDeCatalogo(): void   // RF-15
    {
        $this->assertArrayHasKey('departamento', Entrada::validar(['departamento' => 'Narnia'] + $this->valida())['errores']);
        $this->assertArrayHasKey('municipio', Entrada::validar(['municipio' => 'Bogotá'] + $this->valida())['errores']);
        $this->assertArrayHasKey('municipio', Entrada::validar(['municipio' => ''] + $this->valida())['errores']);
        // Fuera del Cesar el municipio no se guarda: queda «Fuera del Cesar».
        $r = Entrada::validar(['departamento' => 'Magdalena', 'municipio' => "x'; DROP"] + $this->valida());
        $this->assertSame([], $r['errores']);
        $this->assertSame('Fuera del Cesar', $r['datos']['municipio']);
    }

    public function testEntornoSoloDeListaBlanca(): void
    {
        $this->assertArrayHasKey('zona', Entrada::validar(['zona' => 'otra'] + $this->valida())['errores']);
        $this->assertArrayHasKey('agua', Entrada::validar(['agua' => '1'] + $this->valida())['errores']);
    }

    public function testElCatalogoDelCesarTiene25Municipios(): void
    {
        $this->assertCount(25, \Dengue\Ubicaciones::MUNICIPIOS_CESAR);
        $this->assertContains('Valledupar', \Dengue\Ubicaciones::MUNICIPIOS_CESAR);
        $this->assertCount(33, \Dengue\Ubicaciones::DEPARTAMENTOS);
    }
}
