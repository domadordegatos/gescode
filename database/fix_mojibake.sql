-- ============================================================
-- FIX MOJIBAKE — Reparación de datos mal codificados
-- Patrón: datos UTF-8 guardados como latin1 (doble codificación)
-- Síntomas: â€º â€¦ Ã± Ã© Ã¡ etc. en lugar de › … ñ é á
--
-- INSTRUCCIONES:
--   1. Haz un respaldo ANTES: mysqldump -u root plataforma_academica > backup_pre_fix.sql
--   2. Ejecuta este script: mysql -u root plataforma_academica < fix_mojibake.sql
--   3. Verifica con las queries de la sección DIAGNÓSTICO al final
-- ============================================================

SET NAMES utf8mb4;
SET @regex_mojibake = 'Ã|â€|Â¿|Â¡|Ã±|Ã©|Ã¡|Ã³|Ãº|Ã­';

-- ── DIAGNÓSTICO PREVIO ────────────────────────────────────────
-- (Puedes ejecutar solo este bloque primero para ver cuántos registros están afectados)

SELECT '=== DIAGNÓSTICO ANTES DE REPARAR ===' AS info;

SELECT 'periodos.nombre' AS campo, COUNT(*) AS afectados
FROM periodos WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'grados.nombre', COUNT(*) FROM grados WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'materias.nombre', COUNT(*) FROM materias WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'usuarios.nombre', COUNT(*) FROM usuarios WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'usuarios.apellido', COUNT(*) FROM usuarios WHERE apellido REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'tipos_evaluacion.nombre', COUNT(*) FROM tipos_evaluacion WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'indicadores.descripcion', COUNT(*) FROM indicadores WHERE descripcion REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'mensajes.asunto', COUNT(*) FROM mensajes WHERE asunto REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'mensajes.cuerpo', COUNT(*) FROM mensajes WHERE cuerpo REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'observaciones.descripcion', COUNT(*) FROM observaciones WHERE descripcion REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'notas.observacion', COUNT(*) FROM notas WHERE observacion REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'desempenios.nombre', COUNT(*) FROM desempenios WHERE nombre REGEXP 'Ã|â€|Â';

-- ── TABLAS OPCIONALES (solo si existen en tu BD) ──────────────

SELECT '--- Tablas opcionales (evaluaciones/indicadores_eval/calificaciones) ---' AS info;

SELECT IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='evaluaciones'),
  'evaluaciones: existe',
  'evaluaciones: NO existe'
) AS estado_tabla;

SELECT IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='indicadores_eval'),
  'indicadores_eval: existe',
  'indicadores_eval: NO existe'
) AS estado_tabla;

-- ── REPARACIÓN ────────────────────────────────────────────────

SELECT '=== INICIANDO REPARACIÓN ===' AS info;

-- periodos
UPDATE periodos
SET nombre = CONVERT(CONVERT(nombre USING latin1) USING utf8mb4)
WHERE nombre REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'periodos.nombre reparados';

-- grados
UPDATE grados
SET nombre = CONVERT(CONVERT(nombre USING latin1) USING utf8mb4)
WHERE nombre REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'grados.nombre reparados';

-- materias
UPDATE materias
SET nombre = CONVERT(CONVERT(nombre USING latin1) USING utf8mb4)
WHERE nombre REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'materias.nombre reparados';

-- usuarios — nombre y apellido en una sola pasada
UPDATE usuarios
SET nombre   = IF(nombre   REGEXP 'Ã|â€|Â', CONVERT(CONVERT(nombre   USING latin1) USING utf8mb4), nombre),
    apellido = IF(apellido REGEXP 'Ã|â€|Â', CONVERT(CONVERT(apellido USING latin1) USING utf8mb4), apellido)
WHERE nombre REGEXP 'Ã|â€|Â' OR apellido REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'usuarios (nombre/apellido) reparados';

-- tipos_evaluacion
UPDATE tipos_evaluacion
SET nombre = CONVERT(CONVERT(nombre USING latin1) USING utf8mb4)
WHERE nombre REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'tipos_evaluacion.nombre reparados';

-- indicadores
UPDATE indicadores
SET descripcion = CONVERT(CONVERT(descripcion USING latin1) USING utf8mb4)
WHERE descripcion REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'indicadores.descripcion reparados';

-- mensajes
UPDATE mensajes
SET asunto = IF(asunto REGEXP 'Ã|â€|Â', CONVERT(CONVERT(asunto USING latin1) USING utf8mb4), asunto),
    cuerpo = IF(cuerpo REGEXP 'Ã|â€|Â', CONVERT(CONVERT(cuerpo USING latin1) USING utf8mb4), cuerpo)
WHERE asunto REGEXP 'Ã|â€|Â' OR cuerpo REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'mensajes (asunto/cuerpo) reparados';

-- observaciones
UPDATE observaciones
SET descripcion = CONVERT(CONVERT(descripcion USING latin1) USING utf8mb4)
WHERE descripcion REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'observaciones.descripcion reparados';

-- notas
UPDATE notas
SET observacion = CONVERT(CONVERT(observacion USING latin1) USING utf8mb4)
WHERE observacion REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'notas.observacion reparados';

-- desempenios
UPDATE desempenios
SET nombre = CONVERT(CONVERT(nombre USING latin1) USING utf8mb4)
WHERE nombre REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'desempenios.nombre reparados';

-- matriculas
UPDATE matriculas
SET observacion = CONVERT(CONVERT(observacion USING latin1) USING utf8mb4)
WHERE observacion IS NOT NULL AND observacion REGEXP 'Ã|â€|Â';
SELECT ROW_COUNT() AS 'matriculas.observacion reparados';

-- ── TABLAS OPCIONALES (evaluaciones/indicadores_eval) ─────────

SET @tbl_eval = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='evaluaciones');

SET @tbl_ind_eval = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='indicadores_eval');

-- evaluaciones.nombre (si la tabla existe)
SET @sql = IF(@tbl_eval > 0,
  "UPDATE evaluaciones SET nombre = CONVERT(CONVERT(nombre USING latin1) USING utf8mb4) WHERE nombre REGEXP 'Ã|â€|Â'",
  "SELECT 'evaluaciones: tabla no existe, omitida' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- indicadores_eval.descripcion (si la tabla existe)
SET @sql = IF(@tbl_ind_eval > 0,
  "UPDATE indicadores_eval SET descripcion = CONVERT(CONVERT(descripcion USING latin1) USING utf8mb4) WHERE descripcion REGEXP 'Ã|â€|Â'",
  "SELECT 'indicadores_eval: tabla no existe, omitida' AS info");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── DIAGNÓSTICO POSTERIOR ─────────────────────────────────────

SELECT '=== VERIFICACIÓN DESPUÉS DE REPARAR ===' AS info;
SELECT '(todos deben mostrar 0 afectados)' AS nota;

SELECT 'periodos.nombre' AS campo, COUNT(*) AS afectados
FROM periodos WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'grados.nombre', COUNT(*) FROM grados WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'materias.nombre', COUNT(*) FROM materias WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'usuarios.nombre', COUNT(*) FROM usuarios WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'usuarios.apellido', COUNT(*) FROM usuarios WHERE apellido REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'tipos_evaluacion.nombre', COUNT(*) FROM tipos_evaluacion WHERE nombre REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'indicadores.descripcion', COUNT(*) FROM indicadores WHERE descripcion REGEXP 'Ã|â€|Â'
UNION ALL
SELECT 'desempenios.nombre', COUNT(*) FROM desempenios WHERE nombre REGEXP 'Ã|â€|Â';

-- ── MUESTRA DE VERIFICACIÓN ───────────────────────────────────
SELECT '--- Periodos después de fix ---' AS info;
SELECT id, nombre FROM periodos ORDER BY id;

SELECT '--- Grados después de fix ---' AS info;
SELECT id, nombre, nivel FROM grados ORDER BY nivel;

SELECT '--- Desempeños después de fix ---' AS info;
SELECT id, nombre, abreviatura FROM desempenios ORDER BY orden;

SELECT '=== Fix completado ===' AS info;
