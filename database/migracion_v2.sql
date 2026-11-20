-- Migración para quien ya instaló la versión 1 (sin recrear tablas ni perder las evaluaciones guardadas).
-- Uso (XAMPP):  C:\xampp\mysql\bin\mysql.exe -u root < database\migracion_v2.sql
USE dengue_orienta;
ALTER TABLE evaluacion
  ADD COLUMN grupo_edad ENUM('0-4','5-14','15-64','65+') NULL AFTER dias_fiebre,
  ADD COLUMN embarazo ENUM('si','no','na') NULL AFTER grupo_edad,
  ADD COLUMN condiciones_cronicas SET('diabetes','corazon','rinon','defensas','otra') NULL AFTER embarazo,
  ADD COLUMN departamento VARCHAR(30) NULL AFTER condicion_riesgo,
  ADD COLUMN municipio VARCHAR(40) NULL AFTER departamento,
  ADD COLUMN zona ENUM('urbana','rural') NULL AFTER municipio,
  ADD COLUMN agua_estancada ENUM('si','no','nose') NULL AFTER zona,
  ADD COLUMN origen ENUM('real','demo') NOT NULL DEFAULT 'real' AFTER agua_estancada,
  ADD INDEX idx_evaluacion_municipio (municipio),
  ADD INDEX idx_evaluacion_fecha (fecha_hora);
