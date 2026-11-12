-- ============================================================
-- SEED DE PRUEBAS: estudiantes, logros y notas por grupo
-- Contraseña de todos los estudiantes: test123
-- ============================================================
SET @pw = '$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C';

-- ── ASIGNACIONES PARA GRUPOS SIN MATERIAS ─────────────────────
INSERT IGNORE INTO asignaciones (grupo_id, materia_id, docente_id) VALUES
(9,  3, 2),   -- Tercero A  → Español y Literatura
(10, 2, 2),   -- Cuarto A   → Matemáticas
(11, 5, 2),   -- Quinto A   → Ciencias Sociales
(4,  2, 2),   -- Séptimo B  → Matemáticas
(5,  7, 2),   -- Octavo A   → Inglés
(6,  4, 2),   -- Noveno A   → Ciencias Naturales
(7,  3, 2),   -- Décimo A   → Español y Literatura
(8,  7, 2);   -- Undécimo A → Inglés

-- ── USUARIOS ESTUDIANTES ──────────────────────────────────────
INSERT INTO usuarios (username, nombre, apellido, email, password, rol, activo) VALUES
-- Tercero A
('carlos.morales.3a',    'Carlos',    'Morales Ríos',      'carlos.morales.3a@test.com',    @pw, 'estudiante', 1),
('isabella.ruiz.3a',     'Isabella',  'Ruiz Cárdenas',     'isabella.ruiz.3a@test.com',     @pw, 'estudiante', 1),
-- Cuarto A
('santiago.vargas.4a',   'Santiago',  'Vargas Peña',       'santiago.vargas.4a@test.com',   @pw, 'estudiante', 1),
('valentina.castro.4a',  'Valentina', 'Castro Herrera',    'valentina.castro.4a@test.com',  @pw, 'estudiante', 1),
-- Quinto A
('sebastian.lopez.5a',   'Sebastián', 'López Díaz',        'sebastian.lopez.5a@test.com',   @pw, 'estudiante', 1),
('mariana.torres.5a',    'Mariana',   'Torres Salcedo',    'mariana.torres.5a@test.com',    @pw, 'estudiante', 1),
-- Sexto B
('andres.bermudez.6b',   'Andrés',    'Bermúdez Pinto',    'andres.bermudez.6b@test.com',   @pw, 'estudiante', 1),
('juliana.pinto.6b',     'Juliana',   'Pinto Rojas',       'juliana.pinto.6b@test.com',     @pw, 'estudiante', 1),
-- Séptimo A
('nicolas.herrera.7a',   'Nicolás',   'Herrera Gómez',     'nicolas.herrera.7a@test.com',   @pw, 'estudiante', 1),
('daniela.sanchez.7a',   'Daniela',   'Sánchez Lozano',    'daniela.sanchez.7a@test.com',   @pw, 'estudiante', 1),
-- Séptimo B
('felipe.cardona.7b',    'Felipe',    'Cardona Ospina',    'felipe.cardona.7b@test.com',    @pw, 'estudiante', 1),
('sara.jimenez.7b',      'Sara',      'Jiménez Arango',    'sara.jimenez.7b@test.com',      @pw, 'estudiante', 1),
-- Octavo A
('camilo.reyes.8a',      'Camilo',    'Reyes Montoya',     'camilo.reyes.8a@test.com',      @pw, 'estudiante', 1),
('natalia.gomez.8a',     'Natalia',   'Gómez Suárez',      'natalia.gomez.8a@test.com',     @pw, 'estudiante', 1),
-- Noveno A
('alejandro.diaz.9a',    'Alejandro', 'Díaz Rincón',       'alejandro.diaz.9a@test.com',    @pw, 'estudiante', 1),
('paula.ramirez.9a',     'Paula',     'Ramírez Castaño',   'paula.ramirez.9a@test.com',     @pw, 'estudiante', 1),
-- Décimo A
('mateo.silva.10a',      'Mateo',     'Silva Pineda',      'mateo.silva.10a@test.com',      @pw, 'estudiante', 1),
('valeria.mendoza.10a',  'Valeria',   'Mendoza Quiroga',   'valeria.mendoza.10a@test.com',  @pw, 'estudiante', 1),
-- Undécimo A
('esteban.cortes.11a',   'Esteban',   'Cortés Navarro',    'esteban.cortes.11a@test.com',   @pw, 'estudiante', 1),
('luisa.pena.11a',       'Luisa',     'Peña Villamizar',   'luisa.pena.11a@test.com',       @pw, 'estudiante', 1);

-- ── PERFILES ESTUDIANTES ──────────────────────────────────────
INSERT INTO estudiantes (usuario_id, documento, tipo_documento, genero) VALUES
((SELECT id FROM usuarios WHERE email='carlos.morales.3a@test.com'),    '1010001001','TI','M'),
((SELECT id FROM usuarios WHERE email='isabella.ruiz.3a@test.com'),     '1010001002','TI','F'),
((SELECT id FROM usuarios WHERE email='santiago.vargas.4a@test.com'),   '1010002001','TI','M'),
((SELECT id FROM usuarios WHERE email='valentina.castro.4a@test.com'),  '1010002002','TI','F'),
((SELECT id FROM usuarios WHERE email='sebastian.lopez.5a@test.com'),   '1010003001','TI','M'),
((SELECT id FROM usuarios WHERE email='mariana.torres.5a@test.com'),    '1010003002','TI','F'),
((SELECT id FROM usuarios WHERE email='andres.bermudez.6b@test.com'),   '1010004001','TI','M'),
((SELECT id FROM usuarios WHERE email='juliana.pinto.6b@test.com'),     '1010004002','TI','F'),
((SELECT id FROM usuarios WHERE email='nicolas.herrera.7a@test.com'),   '1010005001','TI','M'),
((SELECT id FROM usuarios WHERE email='daniela.sanchez.7a@test.com'),   '1010005002','TI','F'),
((SELECT id FROM usuarios WHERE email='felipe.cardona.7b@test.com'),    '1010006001','TI','M'),
((SELECT id FROM usuarios WHERE email='sara.jimenez.7b@test.com'),      '1010006002','TI','F'),
((SELECT id FROM usuarios WHERE email='camilo.reyes.8a@test.com'),      '1010007001','TI','M'),
((SELECT id FROM usuarios WHERE email='natalia.gomez.8a@test.com'),     '1010007002','TI','F'),
((SELECT id FROM usuarios WHERE email='alejandro.diaz.9a@test.com'),    '1010008001','TI','M'),
((SELECT id FROM usuarios WHERE email='paula.ramirez.9a@test.com'),     '1010008002','TI','F'),
((SELECT id FROM usuarios WHERE email='mateo.silva.10a@test.com'),      '1010009001','TI','M'),
((SELECT id FROM usuarios WHERE email='valeria.mendoza.10a@test.com'),  '1010009002','TI','F'),
((SELECT id FROM usuarios WHERE email='esteban.cortes.11a@test.com'),   '1010010001','TI','M'),
((SELECT id FROM usuarios WHERE email='luisa.pena.11a@test.com'),       '1010010002','TI','F');

-- ── MATRÍCULAS ─────────────────────────────────────────────────
INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 9, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('carlos.morales.3a@test.com','isabella.ruiz.3a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 10, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('santiago.vargas.4a@test.com','valentina.castro.4a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 11, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('sebastian.lopez.5a@test.com','mariana.torres.5a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 2, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('andres.bermudez.6b@test.com','juliana.pinto.6b@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 3, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('nicolas.herrera.7a@test.com','daniela.sanchez.7a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 4, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('felipe.cardona.7b@test.com','sara.jimenez.7b@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 5, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('camilo.reyes.8a@test.com','natalia.gomez.8a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 6, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('alejandro.diaz.9a@test.com','paula.ramirez.9a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 7, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('mateo.silva.10a@test.com','valeria.mendoza.10a@test.com');

INSERT INTO matriculas (estudiante_id, grupo_id, estado)
SELECT e.id, 8, 'activo' FROM estudiantes e JOIN usuarios u ON u.id=e.usuario_id
WHERE u.email IN ('esteban.cortes.11a@test.com','luisa.pena.11a@test.com');

-- ── INDICADORES DE LOGRO POR ASIGNACIÓN Y PERÍODO ─────────────
-- (2 logros por asignación × 2 períodos)
INSERT INTO indicadores_eval (asignacion_id, periodo_id, codigo, descripcion, peso, orden) VALUES
-- Tercero A - Español (asig que recién insertamos)
((SELECT id FROM asignaciones WHERE grupo_id=9 AND materia_id=3), 1, 'L1', 'Lee comprensivamente textos narrativos identificando personajes y trama.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=9 AND materia_id=3), 1, 'L2', 'Produce textos escritos cortos con coherencia y cohesión básica.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=9 AND materia_id=3), 2, 'L1', 'Identifica recursos literarios como la metáfora y la comparación.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=9 AND materia_id=3), 2, 'L2', 'Expone oralmente ideas con claridad respetando el turno de la palabra.', 1.00, 2),
-- Cuarto A - Matemáticas
((SELECT id FROM asignaciones WHERE grupo_id=10 AND materia_id=2), 1, 'L1', 'Resuelve multiplicaciones y divisiones exactas con números naturales.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=10 AND materia_id=2), 1, 'L2', 'Identifica y compara fracciones homogéneas y heterogéneas.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=10 AND materia_id=2), 2, 'L1', 'Aplica propiedades de las figuras geométricas planas en ejercicios prácticos.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=10 AND materia_id=2), 2, 'L2', 'Resuelve problemas de medición usando unidades del sistema métrico.', 1.00, 2),
-- Quinto A - Ciencias Sociales
((SELECT id FROM asignaciones WHERE grupo_id=11 AND materia_id=5), 1, 'L1', 'Ubica en el mapa las regiones naturales de Colombia y describe sus características.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=11 AND materia_id=5), 1, 'L2', 'Explica la organización político-administrativa del territorio colombiano.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=11 AND materia_id=5), 2, 'L1', 'Reconoce los hechos más relevantes de la independencia de Colombia.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=11 AND materia_id=5), 2, 'L2', 'Analiza los derechos y deberes del ciudadano colombiano.', 1.00, 2),
-- Sexto A - Ciencias Naturales (asig_id=3)
(3, 1, 'L1', 'Clasifica los seres vivos según sus características morfológicas y funciones vitales.', 1.00, 1),
(3, 1, 'L2', 'Describe los procesos de la célula como unidad básica de la vida.', 1.00, 2),
(3, 2, 'L1', 'Explica los ecosistemas y las relaciones entre los organismos que los componen.', 1.00, 1),
(3, 2, 'L2', 'Reconoce el impacto humano en el medio ambiente y propone soluciones.', 1.00, 2),
-- Sexto B - Informática (asig_id=4)
(4, 1, 'L1', 'Identifica componentes de hardware y software en un equipo de cómputo.', 1.00, 1),
(4, 1, 'L2', 'Aplica herramientas ofimáticas básicas para la creación de documentos digitales.', 1.00, 2),
(4, 2, 'L1', 'Navega de forma segura en internet reconociendo riesgos y buenas prácticas.', 1.00, 1),
(4, 2, 'L2', 'Crea presentaciones digitales con estructura lógica y diseño apropiado.', 1.00, 2),
-- Séptimo A - Matemáticas (asig_id=2)
(2, 1, 'L1', 'Opera con números enteros aplicando las propiedades de la adición y sustracción.', 1.00, 1),
(2, 1, 'L2', 'Resuelve ecuaciones lineales de una incógnita con verificación.', 1.00, 2),
(2, 2, 'L1', 'Interpreta y construye gráficas estadísticas de barras y circulares.', 1.00, 1),
(2, 2, 'L2', 'Calcula porcentajes y razones en contextos cotidianos.', 1.00, 2),
-- Séptimo B - Matemáticas
((SELECT id FROM asignaciones WHERE grupo_id=4 AND materia_id=2), 1, 'L1', 'Trabaja con potencias y raíces cuadradas en ejercicios aplicados.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=4 AND materia_id=2), 1, 'L2', 'Identifica y clasifica triángulos según sus lados y ángulos.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=4 AND materia_id=2), 2, 'L1', 'Aplica el teorema de Pitágoras en situaciones geométricas.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=4 AND materia_id=2), 2, 'L2', 'Resuelve sistemas de ecuaciones lineales de dos variables.', 1.00, 2),
-- Octavo A - Inglés
((SELECT id FROM asignaciones WHERE grupo_id=5 AND materia_id=7), 1, 'L1', 'Uses simple past tense to narrate personal experiences in writing.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=5 AND materia_id=7), 1, 'L2', 'Understands and responds to oral instructions in English.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=5 AND materia_id=7), 2, 'L1', 'Reads short texts and identifies main ideas and supporting details.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=5 AND materia_id=7), 2, 'L2', 'Produces short paragraphs using connectors and appropriate vocabulary.', 1.00, 2),
-- Noveno A - Ciencias Naturales
((SELECT id FROM asignaciones WHERE grupo_id=6 AND materia_id=4), 1, 'L1', 'Comprende las leyes de Newton y las aplica en situaciones de movimiento.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=6 AND materia_id=4), 1, 'L2', 'Describe los tipos de energía y sus transformaciones en sistemas físicos.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=6 AND materia_id=4), 2, 'L1', 'Explica los principios de la genética mendeliana y la herencia biológica.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=6 AND materia_id=4), 2, 'L2', 'Analiza procesos de evolución y adaptación de las especies.', 1.00, 2),
-- Décimo A - Español
((SELECT id FROM asignaciones WHERE grupo_id=7 AND materia_id=3), 1, 'L1', 'Analiza críticamente textos argumentativos identificando tesis y argumentos.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=7 AND materia_id=3), 1, 'L2', 'Produce ensayos con estructura argumentativa coherente y estilo propio.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=7 AND materia_id=3), 2, 'L1', 'Interpreta obras de la literatura latinoamericana en su contexto histórico.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=7 AND materia_id=3), 2, 'L2', 'Emplea el discurso oral formal en exposiciones y debates académicos.', 1.00, 2),
-- Undécimo A - Inglés
((SELECT id FROM asignaciones WHERE grupo_id=8 AND materia_id=7), 1, 'L1', 'Produces formal written texts such as letters, reports and essays.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=8 AND materia_id=7), 1, 'L2', 'Engages in sustained conversations on academic and professional topics.', 1.00, 2),
((SELECT id FROM asignaciones WHERE grupo_id=8 AND materia_id=7), 2, 'L1', 'Reads and critically analyses authentic English texts from various sources.', 1.00, 1),
((SELECT id FROM asignaciones WHERE grupo_id=8 AND materia_id=7), 2, 'L2', 'Demonstrates understanding of grammatical structures in complex sentences.', 1.00, 2);

-- ── EVALUACIONES (1 por indicador) ────────────────────────────
INSERT INTO evaluaciones (indicador_id, nombre, fecha, porcentaje)
SELECT id, CONCAT('Evaluación ', codigo), DATE_ADD('2026-02-15', INTERVAL (orden*14 + periodo_id*60) DAY), 100.00
FROM indicadores_eval
WHERE asignacion_id IN (
  SELECT id FROM asignaciones WHERE docente_id=2
);

-- ── CALIFICACIONES ─────────────────────────────────────────────
-- Para cada evaluación de cada asignación, nota aleatoria entre 6.0 y 10.0 por estudiante
INSERT INTO calificaciones (evaluacion_id, matricula_id, valor)
SELECT ev.id, mt.id,
  ROUND(6.0 + (RAND() * 4.0), 1)
FROM evaluaciones ev
JOIN indicadores_eval ie ON ev.indicador_id = ie.id
JOIN asignaciones a ON ie.asignacion_id = a.id
JOIN matriculas mt ON mt.grupo_id = a.grupo_id AND mt.estado = 'activo'
JOIN estudiantes est ON mt.estudiante_id = est.id
JOIN usuarios u ON est.usuario_id = u.id
WHERE a.docente_id = 2
  AND u.email LIKE '%@test.com'
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- También algunas notas bajas (reprobadas) para realismo
UPDATE calificaciones c
JOIN evaluaciones ev ON c.evaluacion_id = ev.id
JOIN indicadores_eval ie ON ev.indicador_id = ie.id
SET c.valor = ROUND(4.0 + RAND() * 2.5, 1)
WHERE c.matricula_id % 5 = 0 AND ie.periodo_id = 1
  AND RAND() < 0.3;
