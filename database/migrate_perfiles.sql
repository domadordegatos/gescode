-- ============================================================
-- MIGRACIÓN: Tabla perfiles extendida
-- Ejecutar una sola vez sobre la BD existente
-- ============================================================
SET NAMES utf8mb4;

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

-- Si la tabla ya existía con menos columnas, agregar las que falten:
ALTER TABLE perfiles
  ADD COLUMN IF NOT EXISTS documento        VARCHAR(20)  DEFAULT NULL AFTER usuario_id,
  ADD COLUMN IF NOT EXISTS tipo_documento   ENUM('CC','TI','CE','PP','NIT') DEFAULT 'CC' AFTER documento,
  ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE         DEFAULT NULL AFTER tipo_documento,
  ADD COLUMN IF NOT EXISTS barrio           VARCHAR(80)  DEFAULT NULL AFTER direccion,
  ADD COLUMN IF NOT EXISTS cargo            VARCHAR(100) DEFAULT NULL AFTER ciudad,
  ADD COLUMN IF NOT EXISTS bio              TEXT         DEFAULT NULL AFTER cargo;

SELECT 'Migración perfiles completada' AS resultado;
