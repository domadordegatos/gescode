-- ============================================================
-- SEED CALENDARIO — Gescode (Colegio San Mateo Apóstol)
-- Inserta año lectivo 2026, periodos y fechas institucionales
-- Seguro de ejecutar: usa INSERT IGNORE / IF NOT EXISTS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Año lectivo 2026
-- ------------------------------------------------------------
INSERT IGNORE INTO anos_lectivos (id, colegio_id, nombre, activo, estado)
VALUES (1, 1, '2026', 1, 'activo');

-- ------------------------------------------------------------
-- 2. Periodos
-- ------------------------------------------------------------
INSERT IGNORE INTO periodos
  (id, ano_lectivo_id, nombre, numero, fecha_inicio, fecha_fin,
   activo, permite_notas, permite_observaciones, permite_planeaciones,
   permite_habilitaciones, porcentaje_ano, peso)
VALUES
  (1, 1, 'I PERIODO',   1, '2026-02-03', '2026-04-25', 0, 0, 1, 1, 1, 20.00, 1.00),
  (2, 1, 'II PERIODO',  2, '2026-04-28', '2026-06-27', 1, 1, 1, 1, 1, 30.00, 1.00),
  (3, 1, 'III PERIODO', 3, '2026-07-14', '2026-09-26', 0, 0, 1, 1, 1, 20.00, 1.00),
  (4, 1, 'IV PERIODO',  4, '2026-09-29', '2026-11-28', 0, 0, 1, 1, 1, 30.00, 1.00);

-- ------------------------------------------------------------
-- 3. Fechas institucionales / calendario
-- ------------------------------------------------------------
INSERT IGNORE INTO fechas_importantes (id, periodo_id, tipo, fecha_inicio, fecha_fin)
VALUES
  (1, 2, 'notas',         '2026-04-13', '2026-07-28'),
  (2, 2, 'observaciones', '2026-04-09', '2026-11-28'),
  (3, 2, 'planeaciones',  '2026-04-09', '2026-11-28'),
  (4, 2, 'habilitaciones','2026-04-09', '2026-11-28'),
  (5, 2, 'receso',        '2026-06-13', '2026-06-28');

SET FOREIGN_KEY_CHECKS = 1;

-- Verificación
SELECT fi.id, fi.tipo, fi.fecha_inicio, fi.fecha_fin, p.nombre AS periodo, al.nombre AS ano
FROM fechas_importantes fi
JOIN periodos p ON p.id = fi.periodo_id
JOIN anos_lectivos al ON al.id = p.ano_lectivo_id
ORDER BY fi.fecha_inicio;
