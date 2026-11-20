# Matriz de trazabilidad: requisito → implementación → prueba

| Requisito | Descripción resumida | Implementación | Prueba |
|---|---|---|---|
| RF-01 | Aviso de privacidad con aceptación expresa antes de iniciar | `public/evaluar.php` (consentimiento en sesión) | CP-07 · `verificar_seguridad.php` |
| RF-02 | Capturar días de fiebre (obligatorio) | `Entrada::validar`, paso 1 | `EntradaTest::testNoSeAvanzaSinResponder` |
| RF-03 | Señales de alarma A1–A4 con preguntas cerradas | Paso 4; tabla `signo` | `EntradaTest`; prueba en navegador |
| RF-04 | Señales de gravedad G1–G3 con preguntas cerradas | Paso 5; tabla `signo` | `EntradaTest`; prueba en navegador |
| RF-05 | Clasificar en tres categorías con reglas parametrizadas | `Classifier`, tabla `regla` | CP-01 a CP-06 · regresión de 256 · muestra de 154 |
| RF-06 | Mostrar categoría, señales que activaron y acción | `public/resultado.php` | `ClassifierTest::testCasosRepresentativos` |
| RF-07 | Urgencia inmediata primero y destacada en grave | Bloque `.urgencia` en `resultado.php` | Prueba en navegador (caso grave) |
| RF-08 | Autocuidado y señales de retorno en "sin signos de alarma" | `resultado.php` (**contrastar con OPS/INS**) | Revisión manual |
| RF-09 | Aviso "no diagnostica" en todo resultado | `resultado.php` y pie de página | Revisión manual |
| RF-10 | Registro anonimizado con versión de reglas y tiempo | `Evaluacion::registrar` | `SeedConsistencyTest` |
| RF-11 | Reiniciar la evaluación y borrar la sesión | `public/reiniciar.php` | Prueba en navegador |
| RF-12 | Administrador autenticado exporta CSV | `public/admin/*` | `verificar_seguridad.php` |
| RF-13 | Pedir la edad en años (0 a 110) y guardar solo el grupo de edad | `Entrada::validar`, `Entrada::grupoEdad`, paso 1 | `EntradaTest::testEdadInvalidaSeRechaza`, `testSoloSeGuardaElGrupoDeEdad` · CP-09 |
| RF-14 | Embarazo y enfermedades crónicas por separado; R se deriva de edad, embarazo y enfermedades | `Entrada::riesgo`, paso 2 | `EntradaTest::testRiesgoSeDerivaDeLasRespuestasSeparadas` (9 casos), `testEmbarazoIncoherenteConLaEdadSeRechaza` |
| RF-15 | Ubicación (departamento y municipio del Cesar) y entorno (zona, agua estancada) con lista cerrada | `Ubicaciones`, paso 3 | `EntradaTest::testUbicacionSoloDeCatalogo`, `testEntornoSoloDeListaBlanca` · CP-09 |
| RF-16 | Panel de situación agregado con umbral de privacidad (K = 5) | `Panel`, `public/panel.php` | `PanelTest` · `verificar_seguridad.php` |
| RF-17 | Exportar los conteos agregados del panel (CSV) | `public/panel.php?csv=1` | `verificar_seguridad.php` |
| RF-18 | Paso a paso de qué hacer según el resultado | `public/resultado.php` | Prueba en navegador (las tres clases) |
| RF-19 | Pantalla de inicio con explicación y accesos | `public/index.php` | Prueba en navegador |
| RNF-01 | Idoneidad funcional: exactitud ≥ 90 %, subclasificación 0 % | `Classifier` | `ejecutar_pruebas.php` |
| RNF-02 | Percentil 95 del tiempo ≤ 3 s | — | `rendimiento.php` (CP-09) |
| RNF-03 | Usabilidad: SUS ≥ 68, éxito en tareas ≥ 90 % | Asistente por pasos | `sus.php` + hoja de observación |
| RNF-04 | Chrome, Firefox, Edge y celular | CSS responsivo | Probado en Chrome, Firefox, Edge y Android (formulario de la versión 1; repetir con la versión 2) |
| RNF-05 | Seguridad (consultas preparadas, escape, CSRF, hash, cabeceras) | PDO, `View::e`, `Csrf`, `Auth`, `bootstrap.php` | CP-08 · `verificar_seguridad.php` |
| RNF-06 | Sin datos de identificación personal (ni nombre ni dirección; edad y municipio solo agrupados) | Esquema de `evaluacion`, `Panel::aplicarUmbral` | `SeedConsistencyTest`, `PanelTest` |
| RNF-07 | Reglas en tablas, no en el código | Tabla `regla` | `ClassifierTest::testCambiarUnaReglaNoRequiereCambiarElCodigo` |
| RNF-08 | Explicabilidad: señales que activaron el resultado | `resultado.php` | `ClassifierTest` |
| RNF-09 | Accesibilidad básica (etiquetas, contraste, lenguaje claro) | `fieldset/legend`, CSS | **Pendiente**: revisión con una herramienta (p. ej. Lighthouse) |

TLS (RNF-05): el prototipo funciona sobre HTTP en el entorno local; la cookie de sesión se marca `Secure`
y se envía HSTS automáticamente cuando se sirve por HTTPS. Para activarlo en XAMPP, habilite el SSL de Apache.
