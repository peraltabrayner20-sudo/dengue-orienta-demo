-- Esquema del prototipo (MariaDB/MySQL). Basado en la Figura 3 (modelo entidad-relación) de la Fase 3.
-- Extensiones respecto a la figura: evaluacion.(grupo_edad, embarazo, condiciones_cronicas, departamento, municipio, zona, agua_estancada, origen), regla.prioridad, clase_clasificacion.severidad,
-- ejecucion_prueba.lote/tipo_ejecucion/detalle y usuario_admin (RF-12).
CREATE DATABASE IF NOT EXISTS dengue_orienta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dengue_orienta;

DROP TABLE IF EXISTS ejecucion_prueba;
DROP TABLE IF EXISTS caso_prueba;
DROP TABLE IF EXISTS evaluacion_signo;
DROP TABLE IF EXISTS evaluacion;
DROP TABLE IF EXISTS regla;
DROP TABLE IF EXISTS signo;
DROP TABLE IF EXISTS clase_clasificacion;
DROP TABLE IF EXISTS version_reglas;
DROP TABLE IF EXISTS usuario_admin;

CREATE TABLE version_reglas (
  id_version_reglas INT AUTO_INCREMENT PRIMARY KEY,
  fecha_vigencia    DATE NOT NULL,
  fuente            VARCHAR(255) NOT NULL,
  activa            TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE clase_clasificacion (
  id_clase       TINYINT PRIMARY KEY,
  nombre         VARCHAR(60) NOT NULL UNIQUE,
  recomendacion  TEXT NOT NULL,
  mensaje_riesgo TEXT NULL,
  nivel_urgencia VARCHAR(20) NOT NULL,
  severidad      TINYINT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE signo (
  id_signo            TINYINT AUTO_INCREMENT PRIMARY KEY,
  codigo              VARCHAR(4) NOT NULL UNIQUE,
  nombre              VARCHAR(100) NOT NULL,
  descripcion_usuario VARCHAR(255) NOT NULL,
  tipo                ENUM('alarma','gravedad') NOT NULL,
  fuente_normativa    VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE regla (
  id_regla           INT AUTO_INCREMENT PRIMARY KEY,
  id_version_reglas  INT NOT NULL,
  prioridad          TINYINT NOT NULL,
  condicion          VARCHAR(120) NOT NULL,
  id_clase_resultado TINYINT NOT NULL,
  apartado_normativo VARCHAR(255) NOT NULL,
  FOREIGN KEY (id_version_reglas)  REFERENCES version_reglas(id_version_reglas),
  FOREIGN KEY (id_clase_resultado) REFERENCES clase_clasificacion(id_clase)
) ENGINE=InnoDB;

-- Registro anonimizado (Ley 1581/2012): sin nombre, documento, dirección, contacto ni IP. RF-10.
-- Se guardan el grupo de edad (no la edad exacta), departamento y municipio (no barrio ni dirección),
-- zona y entorno. condicion_riesgo (R) se deriva de edad, embarazo y enfermedades crónicas.
-- Las columnas nuevas admiten NULL para no invalidar evaluaciones registradas con la versión 1 del formulario.
CREATE TABLE evaluacion (
  id_evaluacion       CHAR(36) PRIMARY KEY,
  fecha_hora          DATETIME NOT NULL,
  dias_fiebre         ENUM('1-2','3-7','8+') NOT NULL,
  grupo_edad          ENUM('0-4','5-14','15-64','65+') NULL,
  embarazo            ENUM('si','no','na') NULL,
  condiciones_cronicas SET('diabetes','corazon','rinon','defensas','otra') NULL,
  condicion_riesgo    TINYINT(1) NOT NULL,
  departamento        VARCHAR(30) NULL,
  municipio           VARCHAR(40) NULL,
  zona                ENUM('urbana','rural') NULL,
  agua_estancada      ENUM('si','no','nose') NULL,
  origen              ENUM('real','demo') NOT NULL DEFAULT 'real',
  id_clase            TINYINT NOT NULL,
  id_version_reglas   INT NOT NULL,
  tiempo_respuesta_ms DECIMAL(8,2) NOT NULL,
  INDEX idx_evaluacion_municipio (municipio),
  INDEX idx_evaluacion_fecha (fecha_hora),
  FOREIGN KEY (id_clase)          REFERENCES clase_clasificacion(id_clase),
  FOREIGN KEY (id_version_reglas) REFERENCES version_reglas(id_version_reglas)
) ENGINE=InnoDB;

CREATE TABLE evaluacion_signo (
  id_evaluacion CHAR(36) NOT NULL,
  id_signo      TINYINT NOT NULL,
  presente      TINYINT(1) NOT NULL,
  PRIMARY KEY (id_evaluacion, id_signo),
  FOREIGN KEY (id_evaluacion) REFERENCES evaluacion(id_evaluacion) ON DELETE CASCADE,
  FOREIGN KEY (id_signo)      REFERENCES signo(id_signo)
) ENGINE=InnoDB;

-- Matriz de casos de prueba y trazabilidad.
-- combinacion_signos: 8 bits en el orden A1 A2 A3 A4 G1 G2 G3 R.
CREATE TABLE caso_prueba (
  id_caso            SMALLINT PRIMARY KEY,
  combinacion_signos CHAR(8) NOT NULL UNIQUE,
  clase_esperada     TINYINT NOT NULL,
  estrato            VARCHAR(30) NOT NULL,
  en_muestra         TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (clase_esperada) REFERENCES clase_clasificacion(id_clase)
) ENGINE=InnoDB;

CREATE TABLE ejecucion_prueba (
  id_ejecucion   INT AUTO_INCREMENT PRIMARY KEY,
  lote           CHAR(14) NOT NULL,
  tipo_ejecucion ENUM('muestra','regresion') NOT NULL,
  id_caso        SMALLINT NOT NULL,
  fecha_hora     DATETIME NOT NULL,
  clase_obtenida TINYINT NULL,
  tiempo_ms      DECIMAL(8,2) NOT NULL,
  resultado      ENUM('ok','error') NOT NULL,
  detalle        VARCHAR(120) NULL,
  FOREIGN KEY (id_caso) REFERENCES caso_prueba(id_caso)
) ENGINE=InnoDB;

CREATE TABLE usuario_admin (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  usuario    VARCHAR(40) NOT NULL UNIQUE,
  hash_clave VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
