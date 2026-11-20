USE dengue_orienta;

INSERT INTO version_reglas (id_version_reglas, fecha_vigencia, fuente, activa) VALUES
 (1, '2026-11-12', 'INS (2024) Protocolo de vigilancia en salud pública: Dengue; OPS (2022)', 1);

INSERT INTO clase_clasificacion (id_clase, nombre, recomendacion, mensaje_riesgo, nivel_urgencia, severidad) VALUES
 (1, 'Dengue sin signos de alarma',
     'Hidratación, reposo y vigilancia; vuelva a consultar si aparece alguna señal.',
     'Como hay una condición de riesgo, consulte con un profesional de la salud para que valore su caso aunque no tenga señales de alarma.',
     'Vigilancia', 1),
 (2, 'Dengue con signos de alarma',
     'Acuda hoy a un centro de salud.', NULL, 'El mismo día', 2),
 (3, 'Dengue grave',
     'Acuda ahora a urgencias o llame a la línea de emergencias 123.', NULL, 'Inmediata', 3);

INSERT INTO signo (codigo, nombre, descripcion_usuario, tipo, fuente_normativa) VALUES
 ('A1','Dolor abdominal intenso y continuo','¿Tiene dolor de barriga fuerte que no se le quita?','alarma','INS (2024); OPS (2022)'),
 ('A2','Vómito persistente','¿Ha vomitado varias veces y no logra retener líquidos?','alarma','INS (2024); OPS (2022)'),
 ('A3','Sangrado de mucosas','¿Tiene sangrado por las encías o por la nariz?','alarma','INS (2024); OPS (2022)'),
 ('A4','Letargia o irritabilidad','¿Está muy decaído, con mucho sueño o muy irritable?','alarma','INS (2024); OPS (2022)'),
 ('G1','Choque o dificultad respiratoria','¿Tiene la piel fría, pálida o sudorosa, el pulso débil o dificultad para respirar?','gravedad','INS (2024); OPS (2022)'),
 ('G2','Sangrado grave','¿Tiene sangrado abundante o que no se detiene (vómito o heces con sangre)?','gravedad','INS (2024); OPS (2022)'),
 ('G3','Alteración del estado de conciencia','¿Tiene confusión, desmayo o convulsiones?','gravedad','INS (2024); OPS (2022)');

-- Reglas parametrizadas (RNF-07): se evalúan por prioridad; la primera que cumple define la clase.
INSERT INTO regla (id_version_reglas, prioridad, condicion, id_clase_resultado, apartado_normativo) VALUES
 (1, 1, 'ANY:G1,G2,G3',    3, 'INS (2024) clasificación: dengue grave'),
 (1, 2, 'ANY:A1,A2,A3,A4', 2, 'INS (2024) clasificación: dengue con signos de alarma'),
 (1, 3, 'ALWAYS',          1, 'INS (2024) clasificación: dengue sin signos de alarma');
