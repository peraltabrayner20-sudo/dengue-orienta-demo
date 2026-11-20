# Demostración en línea · Orientación temprana de casos febriles compatibles con dengue (Valledupar)

Versión estática del prototipo del proyecto de grado (Ingeniería de Sistemas, UNAD). El motor de reglas del prototipo PHP
(`src/Classifier.php`) está portado a JavaScript y corre completamente en el navegador: **no guarda ningún dato**.
La orientación no es un diagnóstico y no reemplaza la valoración médica.

- Formulario de cinco pasos: la persona (edad y días de fiebre), condiciones de riesgo (embarazo y enfermedades crónicas por separado),
  ubicación y entorno (departamento, municipio del Cesar, zona, agua estancada), señales de alarma y señales de gravedad.
  La condición de riesgo R de las reglas se calcula a partir de esas respuestas separadas (`Motor.riesgo`).
- Resultado con paso a paso según la clase (sin signos de alarma, con signos de alarma, dengue grave).
- Reglas: `assets/reglas.js` (mismo contenido que `database/seed.sql`; INS 2024 y OPS 2022).
- Motor: `assets/motor.js`. Interfaz: `index.html` y `assets/app.js`.
- Panel de situación de **ejemplo**: `panel.html` (datos sintéticos generados por computador; no son personas ni casos reales).
  En esta versión estática no se guardan evaluaciones, por eso el panel es solo una muestra de cómo se ve el del prototipo completo.
- Regresión: `node pruebas/regresion.js` compara los 256 escenarios con la salida del motor PHP y revisa la derivación de R.

El prototipo completo (PHP, MySQL, panel público con exportación, panel de administrador y pruebas PHPUnit) está en el repositorio principal.
