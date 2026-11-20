# Guía de validación del prototipo (orden de ejecución)

Cada paso genera una evidencia para el documento maestro. Todos los comandos se ejecutan desde la carpeta del
proyecto. En Windows con XAMPP reemplace `php` por `C:\xampp\php\php.exe` y use `--base=http://localhost/dengue-orienta/public`.

| # | Paso | Comando | Evidencia |
|---|------|---------|-----------|
| 1 | Instalar la base de datos (o `database/migracion_v2.sql` si ya tenía la versión 1) y crear el administrador | `php tools/instalar_bd.php` · `php tools/crear_admin.php admin` | Captura de las tablas en phpMyAdmin |
| 2 | Generar el universo de 256 escenarios | `php tools/generar_universo.php` | `docs/universo_256.csv` |
| 3 | **Asignación independiente de la clase esperada**: dos integrantes llenan, cada uno por su cuenta, `clase_inv1` y `clase_inv2` aplicando el protocolo del INS (2022) (valores `sin`, `alarma`, `grave`) | (trabajo manual en Excel) | Archivo con ambas columnas |
| 4 | Índice kappa (meta ≥ 0,80) y lista de discrepancias | `php tools/kappa.php` | Salida del comando |
| 5 | Conciliar las discrepancias contra el texto del protocolo y llenar `clase_esperada` | (manual; documente cada decisión) | Registro de discrepancias |
| 6 | Cargar el universo y seleccionar la muestra estratificada de 154 casos (semilla 2026) | `php tools/seleccionar_muestra.php` | Salida (debe ser 2 / 30 / 122) |
| 7 | Ejecutar las pruebas funcionales sobre la muestra por HTTP | `php tools/ejecutar_pruebas.php --tipo=muestra` | `resultados/muestra_*.md` |
| 8 | Regresión sobre los 256 escenarios | `php tools/ejecutar_pruebas.php --tipo=regresion` | `resultados/regresion_*.md` |
| 9 | Verificación de seguridad (OWASP Top 10, CP-07, CP-08) | `php tools/verificar_seguridad.php` | Salida del comando |
| 10 | Rendimiento: 20 solicitudes consecutivas (CP-09) | `php tools/rendimiento.php` | Salida del comando |
| 11 | Pruebas unitarias | `vendor/bin/phpunit` | Captura de la salida |
| 12 | Prueba de usabilidad con 8 voluntarios (consentimiento firmado, guion del Anexo F, escala SUS del Anexo E) | Llenar `docs/plantillas/*.csv`, luego `php tools/sus.php docs/plantillas/sus_respuestas.csv` | Plantillas llenas + salida SUS |
| 13 | Exportar la matriz de casos y las evaluaciones | Panel del administrador → botones CSV | Archivos CSV |

## Importante sobre la honestidad de los resultados
- Los pasos 3 a 5 son los que dan sentido a la validación: la clase esperada debe salir de **la lectura del protocolo**,
  no del propio programa. Si se copia la salida del prototipo como "esperada", la exactitud será 100 % sin demostrar nada.
- Los datos de usabilidad (paso 12) solo pueden venir de personas reales. No se generan ni se estiman.
- El resultado de la exactitud mide **conformidad con el protocolo**, no exactitud diagnóstica (limitación declarada en el documento).

## Qué prueba cada herramienta
- `ejecutar_pruebas.php` recorre el flujo real del usuario (aviso → aceptación → formulario → evaluación) con token CSRF y sesión,
  guarda cada ejecución en la tabla `ejecucion_prueba` y calcula matriz de confusión, exactitud con IC de Wilson,
  subclasificaciones, tasa de errores y percentil 95 del tiempo.
- Un código de salida distinto de 0 indica que hubo discrepancias o fallas.
