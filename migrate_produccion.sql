-- ============================================================
-- MIGRACIÓN A PRODUCCIÓN — Gescode
-- Tipos de evaluación + campos privado/opcional
-- Seguro de ejecutar en una BD limpia o con datos previos.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. TABLA tipos_eval (nueva)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipos_eval` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `indicador_id` INT UNSIGNED    NOT NULL,
  `nombre`       VARCHAR(100)    NOT NULL COLLATE utf8mb4_unicode_ci,
  `porcentaje`   DECIMAL(5,2)    NOT NULL DEFAULT 100.00,
  `privado`      TINYINT(1)      NOT NULL DEFAULT 0,
  `opcional`     TINYINT(1)      NOT NULL DEFAULT 0,
  `orden`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tipo_ind`
    FOREIGN KEY (`indicador_id`) REFERENCES `indicadores_eval` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. evaluaciones — agregar columnas si no existen
-- ------------------------------------------------------------

-- tipo_eval_id
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND COLUMN_NAME = 'tipo_eval_id');
SET @sql = IF(@col = 0,
  'ALTER TABLE evaluaciones ADD COLUMN tipo_eval_id INT UNSIGNED NULL AFTER indicador_id',
  'SELECT "tipo_eval_id ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- privado
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND COLUMN_NAME = 'privado');
SET @sql = IF(@col = 0,
  'ALTER TABLE evaluaciones ADD COLUMN privado TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT "privado ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- opcional
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND COLUMN_NAME = 'opcional');
SET @sql = IF(@col = 0,
  'ALTER TABLE evaluaciones ADD COLUMN opcional TINYINT(1) NOT NULL DEFAULT 0 AFTER privado',
  'SELECT "opcional ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- recuperacion_de
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND COLUMN_NAME = 'recuperacion_de');
SET @sql = IF(@col = 0,
  'ALTER TABLE evaluaciones ADD COLUMN recuperacion_de INT UNSIGNED NULL',
  'SELECT "recuperacion_de ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 3. evaluaciones — FK tipo_eval_id
-- ------------------------------------------------------------
SET @fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND CONSTRAINT_NAME = 'fk_eval_tipo');
SET @sql = IF(@fk = 0,
  'ALTER TABLE evaluaciones ADD CONSTRAINT fk_eval_tipo
     FOREIGN KEY (tipo_eval_id) REFERENCES tipos_eval(id) ON DELETE SET NULL',
  'SELECT "fk_eval_tipo ya existe" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 4. evaluaciones — FK recuperacion_de → evaluaciones(id)
--    Corrección: si apunta a indicadores_eval la borramos y recreamos
-- ------------------------------------------------------------
SET @fk_bad = (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME    = 'evaluaciones'
    AND COLUMN_NAME   = 'recuperacion_de'
    AND REFERENCED_TABLE_NAME = 'indicadores_eval'
);
SET @sql = IF(@fk_bad > 0,
  'ALTER TABLE evaluaciones DROP FOREIGN KEY fk_eval_recuperacion',
  'SELECT "fk_eval_recuperacion no apunta a indicadores_eval" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Recrear apuntando a evaluaciones(id) si no existe
SET @fk_ok = (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME    = 'evaluaciones'
    AND COLUMN_NAME   = 'recuperacion_de'
    AND REFERENCED_TABLE_NAME = 'evaluaciones'
);
SET @sql = IF(@fk_ok = 0,
  'ALTER TABLE evaluaciones ADD CONSTRAINT fk_eval_recuperacion
     FOREIGN KEY (recuperacion_de) REFERENCES evaluaciones(id) ON DELETE SET NULL',
  'SELECT "fk_eval_recuperacion OK" AS info');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 5. Datos iniciales — crear tipo "Nota 100%" para cada
--    indicador que todavía no tenga ningún tipo
-- ------------------------------------------------------------
INSERT INTO tipos_eval (indicador_id, nombre, porcentaje, orden)
SELECT ie.id, 'Nota', 100.00, 1
FROM indicadores_eval ie
WHERE NOT EXISTS (
  SELECT 1 FROM tipos_eval t WHERE t.indicador_id = ie.id
);

-- ------------------------------------------------------------
-- 6. Asignar evaluaciones sin tipo al tipo de su indicador
-- ------------------------------------------------------------
UPDATE evaluaciones e
  JOIN tipos_eval t ON t.indicador_id = e.indicador_id
SET e.tipo_eval_id = t.id
WHERE e.tipo_eval_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Verificación final
-- ------------------------------------------------------------
SELECT
  (SELECT COUNT(*) FROM tipos_eval)                              AS tipos_creados,
  (SELECT COUNT(*) FROM evaluaciones WHERE tipo_eval_id IS NULL) AS evals_sin_tipo,
  'OK — migración completa'                                      AS estado;
