# Orientación temprana de casos febriles compatibles con dengue (Valledupar)

Prototipo web del proyecto de grado (Ingeniería de Sistemas, UNAD). Recibe respuestas a preguntas
cerradas sobre síntomas, aplica reglas parametrizadas derivadas del protocolo del INS (2024) y entrega una
clasificación (sin signos de alarma / con signos de alarma / dengue grave), las señales que la activaron y una
recomendación informativa. **No diagnostica ni sustituye la valoración médica.**

Metodología: CDIO + Scrum · Nivel de madurez objetivo: TRL 5 (validación en entorno relevante simulado).

## Tecnologías
HTML5, CSS3 y JavaScript (cliente) · PHP 8 con PDO (lógica y acceso a datos) · MySQL/MariaDB ·
Apache (XAMPP) · PHPUnit · Git y GitHub.

## Arquitectura (tres capas)
```
public/            Capa de presentación: index.php (inicio), evaluar.php (asistente de 5 pasos), resultado.php, panel.php (panel de situación), reiniciar.php, admin/
src/               Lógica de negocio: Classifier (motor de reglas), Entrada (validación), Ubicaciones, Panel, Evaluacion, Auth, Csrf, Db
database/          schema.sql (modelo entidad-relación) y seed.sql (signos, clases y reglas)
tools/             Herramientas de validación (universo, kappa, muestra, pruebas HTTP, seguridad, rendimiento, SUS)
tests/             Pruebas unitarias y de integración (PHPUnit)
docs/              Guía de pruebas, matriz de trazabilidad y plantillas de usabilidad
```
Las reglas están en la tabla `regla` (no en el código): cambiar una regla no exige modificar el programa.

## Instalación en Windows con XAMPP
1. Instale XAMPP e inicie **Apache** y **MySQL** desde el panel de control.
2. Copie esta carpeta a `C:\xampp\htdocs\dengue-orienta` (o clone allí el repositorio).
3. Abra PowerShell en esa carpeta y cree la base de datos:
   ```
   C:\xampp\php\php.exe tools\instalar_bd.php
   ```
4. Cree el usuario administrador (la contraseña se guarda con hash):
   ```
   C:\xampp\php\php.exe tools\crear_admin.php admin
   ```
5. Abra en el navegador: `http://localhost/dengue-orienta/public/`
   Panel de situación (público, solo conteos agrupados): `http://localhost/dengue-orienta/public/panel.php`
   Panel del administrador: `http://localhost/dengue-orienta/public/admin/login.php`

Si su MySQL tiene contraseña, copie `config/config.example.php` como `config/config.php` y ajústela
(ese archivo no se sube a GitHub).

### Alternativa sin XAMPP (PHP instalado)
```
php tools/instalar_bd.php
php tools/crear_admin.php admin
php -S 127.0.0.1:8080 -t public
```

### Si ya había instalado la versión 1
No hace falta recrear nada: ejecute `database\migracion_v2.sql` (agrega a `evaluacion` las columnas de edad, ubicación y entorno sin borrar datos).

### Datos de demostración para el panel
`php tools/generar_datos_demo.php` inserta evaluaciones **sintéticas** (origen `demo`) para ver el panel con `panel.php?demo=1`.
No son personas ni casos reales y no se mezclan con las evaluaciones reales. Las herramientas de prueba también marcan como `demo`
lo que envían, para no contaminar el panel.

## Privacidad
No se pide ni se guarda nombre, documento, dirección, barrio, teléfono, correo ni IP. De la edad solo se guarda el grupo
(0-4, 5-14, 15-64, 65+). El panel público muestra conteos agrupados y oculta o agrupa las categorías con menos de 5 evaluaciones.
Los datos del panel son evaluaciones voluntarias y anónimas, no casos confirmados.

## Pruebas
Las pruebas automáticas y el procedimiento completo de validación están en **docs/GUIA_PRUEBAS.md**.
Pruebas unitarias: `composer install` y luego `vendor\bin\phpunit` (Windows: `C:\xampp\php\php.exe vendor\bin\phpunit`).

## Fuentes normativas
Reglas derivadas del protocolo de vigilancia en salud pública del dengue del INS (versión 7, 2024) y de las recomendaciones
de la OPS (2022). Las recomendaciones del prototipo son informativas; no incluyen dosis de medicamentos.
