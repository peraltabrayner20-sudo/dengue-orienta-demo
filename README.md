# Demostración en línea · Orientación temprana de casos febriles compatibles con dengue (Valledupar)

**Probar la demostración en línea:** https://peraltabrayner20-sudo.github.io/dengue-orienta-demo/

Versión estática del prototipo del proyecto de grado (Ingeniería de Sistemas, UNAD). El motor de reglas del prototipo PHP
(`src/Classifier.php`) está portado a JavaScript y corre completamente en el navegador: **no guarda ningún dato**.
La orientación no es un diagnóstico y no reemplaza la valoración médica.

- Reglas: `assets/reglas.js` (mismo contenido que `database/seed.sql`; INS 2024 y OPS 2022).
- Motor: `assets/motor.js`. Interfaz: `index.html` y `assets/app.js`.
- Regresión: `node pruebas/regresion.js` compara los 256 escenarios con la salida del motor PHP.

El prototipo completo (PHP, MySQL, panel de administrador y pruebas PHPUnit) está en el repositorio principal.
