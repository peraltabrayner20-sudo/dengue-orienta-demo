<?php
declare(strict_types=1);

namespace Dengue;

/**
 * Catálogo cerrado de ubicaciones (lista blanca). Solo se pide departamento y, si es el Cesar, municipio:
 * nunca dirección ni barrio, para no volver identificable a la persona (Ley 1581 de 2012).
 */
final class Ubicaciones
{
    public const CESAR = 'Cesar';
    /** Valor que se guarda cuando la persona vive fuera del Cesar (el prototipo se enfoca en el Cesar). */
    public const FUERA = 'Fuera del Cesar';

    public const DEPARTAMENTOS = [
        'Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá, D. C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá',
        'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira',
        'Magdalena', 'Meta', 'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda',
        'San Andrés y Providencia', 'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada',
    ];

    /** Los 25 municipios del departamento del Cesar. */
    public const MUNICIPIOS_CESAR = [
        'Aguachica', 'Agustín Codazzi', 'Astrea', 'Becerril', 'Bosconia', 'Chimichagua', 'Chiriguaná', 'Curumaní',
        'El Copey', 'El Paso', 'Gamarra', 'González', 'La Gloria', 'La Jagua de Ibirico', 'La Paz',
        'Manaure Balcón del Cesar', 'Pailitas', 'Pelaya', 'Pueblo Bello', 'Río de Oro', 'San Alberto', 'San Diego',
        'San Martín', 'Tamalameque', 'Valledupar',
    ];

    public const ZONAS = ['urbana' => 'Zona urbana (cabecera)', 'rural' => 'Zona rural o corregimiento'];
    public const AGUA  = ['si' => 'Sí', 'no' => 'No', 'nose' => 'No sé'];

    /**
     * @return array{departamento:string,municipio:string}|null  null si la combinación no está en el catálogo
     */
    public static function normalizar(mixed $departamento, mixed $municipio): ?array
    {
        if (!is_string($departamento) || !in_array($departamento, self::DEPARTAMENTOS, true)) {
            return null;
        }
        if ($departamento !== self::CESAR) {
            return ['departamento' => $departamento, 'municipio' => self::FUERA];
        }
        if (!is_string($municipio) || !in_array($municipio, self::MUNICIPIOS_CESAR, true)) {
            return null;
        }
        return ['departamento' => $departamento, 'municipio' => $municipio];
    }
}
