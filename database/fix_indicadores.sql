SET NAMES utf8mb4;

-- Corregir IDs 4-6 (Sexto A Informática, período 1)
UPDATE indicadores_eval SET descripcion = 'Identifica los componentes básicos de un computador y describe sus funciones principales.' WHERE id = 4;
UPDATE indicadores_eval SET descripcion = 'Reconoce y aplica conceptos básicos de sistemas operativos en tareas cotidianas.' WHERE id = 5;
UPDATE indicadores_eval SET descripcion = 'Crea documentos de texto con formato básico usando procesadores de texto.' WHERE id = 6;

-- Eliminar duplicados corruptos (19-26) que ya tienen versión correcta en 63-70
-- Primero mover calificaciones si las hubiera
UPDATE calificaciones SET evaluacion_id = (
    SELECT ev2.id FROM evaluaciones ev2
    JOIN indicadores_eval ie2 ON ev2.indicador_id = ie2.id
    WHERE ie2.asignacion_id = (SELECT asignacion_id FROM indicadores_eval WHERE id = (
        SELECT indicador_id FROM evaluaciones ev1 WHERE ev1.id = calificaciones.evaluacion_id
    ))
    AND ie2.periodo_id = (SELECT periodo_id FROM indicadores_eval WHERE id = (
        SELECT indicador_id FROM evaluaciones ev1 WHERE ev1.id = calificaciones.evaluacion_id
    ))
    AND ie2.codigo = (SELECT codigo FROM indicadores_eval WHERE id = (
        SELECT indicador_id FROM evaluaciones ev1 WHERE ev1.id = calificaciones.evaluacion_id
    ))
    AND ie2.id >= 63
    LIMIT 1
)
WHERE evaluacion_id IN (SELECT id FROM evaluaciones WHERE indicador_id BETWEEN 19 AND 26);

-- Eliminar evaluaciones de indicadores corruptos
DELETE FROM evaluaciones WHERE indicador_id BETWEEN 19 AND 26;

-- Eliminar indicadores corruptos
DELETE FROM indicadores_eval WHERE id BETWEEN 19 AND 26;
