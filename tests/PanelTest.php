<?php
declare(strict_types=1);

namespace Dengue\Tests;

use Dengue\Panel;
use PHPUnit\Framework\TestCase;

/** Regla de privacidad del panel: ninguna categoría con 1 a K-1 evaluaciones se muestra sola (RF-16, RNF-06). */
final class PanelTest extends TestCase
{
    public function testLasCategoriasPequenasSeFundenEnOtros(): void
    {
        $r = Panel::aplicarUmbral([
            'Valledupar' => [1 => 30, 2 => 10, 3 => 2],
            'Aguachica'  => [1 => 3, 2 => 1, 3 => 0],      // total 4  (< 5)
            'Bosconia'   => [1 => 2, 2 => 2, 3 => 1],      // total 5  (grande, pero celdas pequeñas)
            'Pailitas'   => [1 => 1, 2 => 0, 3 => 0],      // total 1  (< 5)
        ]);
        $etq = array_column($r['filas'], 'etiqueta');
        $this->assertSame(['Valledupar', 'Bosconia', 'Otros (menos de 5 cada uno)'], $etq);
        $otros = $r['filas'][2];
        $this->assertSame(5, $otros['total']);                 // 4 + 1
        $this->assertNotContains('Aguachica', $etq);
        $this->assertNotContains('Pailitas', $etq);
    }

    public function testUnaCeldaPequenaNoSeMuestraPorSeparado(): void
    {
        $r = Panel::aplicarUmbral(['Valledupar' => [1 => 30, 2 => 10, 3 => 2]]);
        $f = $r['filas'][0];
        $this->assertSame([1 => 30, 2 => 10, 3 => 0], $f['seg']);   // la clase 3 (2 casos) no se dibuja sola
        $this->assertSame(2, $f['oculto']);                          // va a un segmento agrupado sin clase
        $this->assertSame(42, $f['total']);
    }

    public function testSiLosOtrosNoLlegaAlUmbralNoSeMuestran(): void
    {
        $r = Panel::aplicarUmbral([
            'Valledupar' => [1 => 40, 2 => 5, 3 => 5],
            'Pailitas'   => [1 => 2, 2 => 0, 3 => 0],
        ]);
        $this->assertSame(['Valledupar'], array_column($r['filas'], 'etiqueta'));
        $this->assertSame(2, $r['ocultas']);
    }

    public function testOrdenFijoYEtiquetas(): void
    {
        $r = Panel::aplicarUmbral(
            ['65+' => [1 => 6, 2 => 0, 3 => 0], '0-4' => [1 => 8, 2 => 0, 3 => 0]],
            ['0-4', '5-14', '15-64', '65+'], 4, ['0-4' => '0 a 4 años', '65+' => '65 años o más']
        );
        $this->assertSame(['0 a 4 años', '65 años o más'], array_column($r['filas'], 'etiqueta'));
    }

    public function testElUmbralEsCinco(): void
    {
        $this->assertSame(5, Panel::K);
    }
}
