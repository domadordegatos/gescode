SET NAMES utf8mb4;
DELETE FROM desempenios;
INSERT INTO desempenios (id, nombre, abreviatura, rango_min, rango_max, es_perdido, orden) VALUES
(1, 'Desempeño Inferior', 'DI', 0.0, 6.9,  1, 1),
(2, 'Desempeño Básico',   'DB', 7.0, 7.9,  0, 2),
(3, 'Desempeño Alto',     'DA', 8.0, 8.9,  0, 3),
(4, 'Desempeño Superior', 'DS', 9.0, 10.0, 0, 4);
