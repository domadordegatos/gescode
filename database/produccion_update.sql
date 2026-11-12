-- ============================================================
-- ACTUALIZACIÓN PRODUCCIÓN — Ejecutar en phpMyAdmin
-- InfinityFree: if0_41993912
-- ============================================================
SET NAMES utf8mb4;

-- ── 1. TABLA PERFILES (ampliar si ya existe) ──────────────────
CREATE TABLE IF NOT EXISTS perfiles (
  usuario_id      INT UNSIGNED PRIMARY KEY,
  documento       VARCHAR(20)  DEFAULT NULL,
  tipo_documento  ENUM('CC','TI','CE','PP','NIT') DEFAULT 'CC',
  fecha_nacimiento DATE        DEFAULT NULL,
  telefono        VARCHAR(20)  DEFAULT NULL,
  celular         VARCHAR(20)  DEFAULT NULL,
  direccion       VARCHAR(200) DEFAULT NULL,
  barrio          VARCHAR(80)  DEFAULT NULL,
  ciudad          VARCHAR(80)  DEFAULT NULL,
  cargo           VARCHAR(100) DEFAULT NULL,
  bio             TEXT         DEFAULT NULL,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columnas faltantes si la tabla ya existía
ALTER TABLE perfiles
  ADD COLUMN IF NOT EXISTS documento       VARCHAR(20)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS tipo_documento  ENUM('CC','TI','CE','PP','NIT') DEFAULT 'CC',
  ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE        DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS barrio          VARCHAR(80)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS cargo           VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS bio             TEXT         DEFAULT NULL;

-- ── 2. COMUNIDAD ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comunidad_categorias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) DEFAULT NULL,
  orden       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  creado_por  INT UNSIGNED NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comunidad_archivos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  titulo       VARCHAR(200) NOT NULL,
  descripcion  VARCHAR(255) DEFAULT NULL,
  archivo_disk VARCHAR(255) NOT NULL,
  archivo_orig VARCHAR(255) NOT NULL,
  mime         VARCHAR(100) NOT NULL,
  tamano       INT UNSIGNED NOT NULL,
  subido_por   INT UNSIGNED NOT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES comunidad_categorias(id) ON DELETE CASCADE,
  FOREIGN KEY (subido_por)   REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. REPARAR ENCODING (mojibake â€º → ›) ─────────────────────
-- Solo afecta registros que tengan los caracteres corruptos
UPDATE periodos       SET nombre      = CONVERT(CONVERT(nombre      USING latin1) USING utf8mb4) WHERE nombre      REGEXP 'Ã|â€|Â';
UPDATE grados         SET nombre      = CONVERT(CONVERT(nombre      USING latin1) USING utf8mb4) WHERE nombre      REGEXP 'Ã|â€|Â';
UPDATE materias       SET nombre      = CONVERT(CONVERT(nombre      USING latin1) USING utf8mb4) WHERE nombre      REGEXP 'Ã|â€|Â';
UPDATE usuarios       SET nombre      = CONVERT(CONVERT(nombre      USING latin1) USING utf8mb4) WHERE nombre      REGEXP 'Ã|â€|Â';
UPDATE usuarios       SET apellido    = CONVERT(CONVERT(apellido    USING latin1) USING utf8mb4) WHERE apellido    REGEXP 'Ã|â€|Â';
UPDATE desempenios    SET nombre      = CONVERT(CONVERT(nombre      USING latin1) USING utf8mb4) WHERE nombre      REGEXP 'Ã|â€|Â';
UPDATE indicadores    SET descripcion = CONVERT(CONVERT(descripcion USING latin1) USING utf8mb4) WHERE descripcion REGEXP 'Ã|â€|Â';
UPDATE tipos_evaluacion SET nombre   = CONVERT(CONVERT(nombre      USING latin1) USING utf8mb4) WHERE nombre      REGEXP 'Ã|â€|Â';
UPDATE mensajes       SET asunto      = CONVERT(CONVERT(asunto      USING latin1) USING utf8mb4) WHERE asunto      REGEXP 'Ã|â€|Â';
UPDATE observaciones  SET descripcion = CONVERT(CONVERT(descripcion USING latin1) USING utf8mb4) WHERE descripcion REGEXP 'Ã|â€|Â';

SELECT 'Actualización completada' AS resultado;
