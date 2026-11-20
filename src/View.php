<?php
declare(strict_types=1);

namespace Dengue;

final class View
{
    public static function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param string $p prefijo relativo hacia /public ('' o '../') */
    public static function cabecera(string $titulo, string $p = '', string $claseBody = ''): void
    {
        $t = self::e($titulo);
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t} · Orientación temprana de dengue</title>
<link rel="stylesheet" href="{$p}assets/css/styles.css">
</head>
<body class="{$claseBody}">
<a class="salto" href="#contenido">Saltar al contenido</a>
<header class="encabezado"><div class="contenedor">
  <p class="marca">Orientación temprana de casos febriles compatibles con dengue</p>
  <p class="sub">Valledupar, Cesar · Prototipo académico</p>
  <nav class="menu" aria-label="Principal"><a href="{$p}index.php">Inicio</a><a href="{$p}evaluar.php">Evaluación</a><a href="{$p}panel.php">Panel de situación</a></nav>
</div></header>
<main id="contenido" class="contenedor">

HTML;
    }

    public static function pie(string $p = '', bool $conJs = true): void
    {
        $js = $conJs ? "<script src=\"{$p}assets/js/app.js\"></script>" : '';
        echo <<<HTML

</main>
<footer class="pie"><div class="contenedor">
  <p><strong>Esta herramienta orienta; no diagnostica ni sustituye la valoración médica.</strong>
  Si la persona empeora o tiene dudas, consulte a un profesional de la salud. En una emergencia llame al 123.</p>
  <p class="pequeno">Proyecto de grado · Ingeniería de Sistemas · UNAD. Prototipo en validación.</p>
</div></footer>
{$js}
</body>
</html>
HTML;
    }

    public static function error(int $codigo, string $mensaje): void
    {
        http_response_code($codigo);
        self::cabecera('Error');
        echo '<section class="tarjeta"><h1>No se pudo completar la solicitud</h1><p>' . self::e($mensaje) . '</p>'
           . '<p><a class="boton" href="index.php">Volver al inicio</a></p></section>';
        self::pie();
    }
}
