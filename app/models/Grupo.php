<?php
class Grupo {
    private PDO $db;
    public function __construct() { $this->db = Database::get(); }

    public function porDocente(int $docente_id): array {
        $st = $this->db->prepare(
            'SELECT a.id AS asignacion_id, g.id AS grupo_id, gr.nombre AS grado, gr.nivel,
                    g.nombre AS grupo, m.nombre AS materia, m.id AS materia_id,
                    al.nombre AS ano,
                    (SELECT COUNT(*) FROM matriculas mt WHERE mt.grupo_id=g.id AND mt.estado="activo") AS total_est
             FROM asignaciones a
             JOIN grupos g ON a.grupo_id=g.id
             JOIN grados gr ON g.grado_id=gr.id
             JOIN materias m ON a.materia_id=m.id
             JOIN anos_lectivos al ON g.ano_lectivo_id=al.id
             WHERE a.docente_id=? AND al.activo=1
             ORDER BY gr.nivel, g.nombre'
        );
        $st->execute([$docente_id]);
        return $st->fetchAll();
    }

    public function detalle(int $grupo_id, int $docente_id): ?array {
        $st = $this->db->prepare(
            'SELECT a.id AS asignacion_id, g.id AS grupo_id, gr.nombre AS grado, gr.nivel,
                    g.nombre AS grupo, m.nombre AS materia, m.id AS materia_id,
                    al.nombre AS ano, al.id AS ano_id
             FROM asignaciones a
             JOIN grupos g ON a.grupo_id=g.id
             JOIN grados gr ON g.grado_id=gr.id
             JOIN materias m ON a.materia_id=m.id
             JOIN anos_lectivos al ON g.ano_lectivo_id=al.id
             WHERE a.docente_id=? AND g.id=? AND al.activo=1 LIMIT 1'
        );
        $st->execute([$docente_id, $grupo_id]);
        return $st->fetch() ?: null;
    }

    public function estudiantes(int $grupo_id): array {
        $st = $this->db->prepare(
            'SELECT mt.id AS matricula_id, mt.estado,
                    u.id AS usuario_id, u.nombre, u.apellido, u.foto,
                    e.id AS estudiante_id, e.documento, e.genero, e.fecha_nacimiento
             FROM matriculas mt
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u ON e.usuario_id=u.id
             WHERE mt.grupo_id=?
             ORDER BY u.apellido, u.nombre'
        );
        $st->execute([$grupo_id]);
        return $st->fetchAll();
    }

    /** Datos de un estudiante en un grupo (para vista de notas) */
    public function estudianteEnGrupo(int $grupo_id, int $usuario_id, int $docente_id): ?array {
        // Verificar que el docente tenga asignación en el grupo
        $st = $this->db->prepare(
            'SELECT mt.id AS matricula_id, mt.estado,
                    u.id AS usuario_id, u.nombre, u.apellido,
                    e.id AS estudiante_id, e.documento, e.genero,
                    g.id AS grupo_id, gr.nombre AS grado, g.nombre AS grupo,
                    al.nombre AS ano
             FROM matriculas mt
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u    ON e.usuario_id=u.id
             JOIN grupos g      ON mt.grupo_id=g.id
             JOIN grados gr     ON g.grado_id=gr.id
             JOIN anos_lectivos al ON g.ano_lectivo_id=al.id
             WHERE mt.grupo_id=? AND u.id=? AND al.activo=1
               AND EXISTS (SELECT 1 FROM asignaciones a
                           WHERE a.grupo_id=g.id AND a.docente_id=?)
             LIMIT 1'
        );
        $st->execute([$grupo_id, $usuario_id, $docente_id]);
        return $st->fetch() ?: null;
    }

    /** Padres/acudientes de un estudiante */
    public function padresDeEstudiante(int $estudiante_id): array {
        $st = $this->db->prepare(
            'SELECT u.id AS usuario_id, u.nombre, u.apellido, a.parentesco
             FROM acudientes a
             JOIN usuarios u ON a.usuario_id=u.id
             WHERE a.estudiante_id=? AND u.activo=1'
        );
        $st->execute([$estudiante_id]);
        return $st->fetchAll();
    }

    /** Notas del estudiante por periodo — agrupadas por materia → indicador → evaluaciones */
    public function notasEstudiante(int $grupo_id, int $matricula_id, int $periodo_id): array {
        $db = $this->db;

        // Asignaciones del grupo con sus indicadores y evaluaciones del periodo
        $st = $db->prepare("
            SELECT
                m.id   AS materia_id,   m.nombre AS materia,
                ie.id  AS ind_id,       ie.codigo, ie.descripcion AS indicador, ie.peso,
                ev.id  AS eval_id,      ev.nombre AS eval_nombre,
                ev.fecha AS eval_fecha, ev.porcentaje AS eval_pct,
                c.valor AS nota
            FROM asignaciones a
            JOIN materias m          ON a.materia_id = m.id
            JOIN indicadores_eval ie ON ie.asignacion_id = a.id AND ie.periodo_id = ?
            JOIN evaluaciones ev     ON ev.indicador_id = ie.id
            LEFT JOIN calificaciones c ON c.evaluacion_id = ev.id AND c.matricula_id = ?
            WHERE a.grupo_id = ?
            ORDER BY m.nombre, ie.orden, ie.id, ev.fecha, ev.id
        ");
        $st->execute([$periodo_id, $matricula_id, $grupo_id]);
        $rows = $st->fetchAll();

        // Agrupar: materia → indicadores → evaluaciones
        $materias = [];
        foreach ($rows as $r) {
            $mid = $r['materia_id'];
            $iid = $r['ind_id'];
            $eid = $r['eval_id'];

            if (!isset($materias[$mid])) {
                $materias[$mid] = [
                    'materia_id'  => $mid,
                    'nombre'      => $r['materia'],
                    'indicadores' => [],
                    'promedio'    => null,
                ];
            }
            if (!isset($materias[$mid]['indicadores'][$iid])) {
                $materias[$mid]['indicadores'][$iid] = [
                    'ind_id'       => $iid,
                    'codigo'       => $r['codigo'],
                    'descripcion'  => $r['indicador'],
                    'peso'         => (float)$r['peso'],
                    'evaluaciones' => [],
                    'definitiva'   => null,
                ];
            }
            $materias[$mid]['indicadores'][$iid]['evaluaciones'][$eid] = [
                'nombre'     => $r['eval_nombre'],
                'fecha'      => $r['eval_fecha'],
                'porcentaje' => (float)$r['eval_pct'],
                'nota'       => $r['nota'] !== null ? (float)$r['nota'] : null,
            ];
        }

        // Calcular definitivas por indicador y por materia
        foreach ($materias as &$mat) {
            $suma_peso = 0; $nota_pond = 0;
            foreach ($mat['indicadores'] as &$ind) {
                $pct_total = 0; $ponderado = 0;
                foreach ($ind['evaluaciones'] as $ev) {
                    if ($ev['nota'] !== null) {
                        $ponderado += $ev['nota'] * ($ev['porcentaje'] / 100);
                        $pct_total += $ev['porcentaje'];
                    }
                }
                $ind['definitiva'] = $pct_total > 0 ? round($ponderado / ($pct_total / 100) * ($pct_total / 100), 1) : null;
                if ($ind['definitiva'] !== null) {
                    $nota_pond += $ind['definitiva'] * $ind['peso'];
                    $suma_peso += $ind['peso'];
                }
            }
            unset($ind);
            $mat['promedio'] = $suma_peso > 0 ? round($nota_pond / $suma_peso, 1) : null;
            $mat['indicadores'] = array_values($mat['indicadores']);
        }
        unset($mat);

        return array_values($materias);
    }

    // Verificar que una asignación pertenezca al docente y devolver sus datos de grupo
    public function asignacionPorId(int $asig_id, int $docente_id): ?array {
        $st = $this->db->prepare(
            'SELECT a.id AS asignacion_id, g.id AS grupo_id, gr.nombre AS grado, gr.nivel,
                    g.nombre AS grupo, m.nombre AS materia, m.id AS materia_id,
                    al.nombre AS ano, al.id AS ano_id
             FROM asignaciones a
             JOIN grupos g ON a.grupo_id=g.id
             JOIN grados gr ON g.grado_id=gr.id
             JOIN materias m ON a.materia_id=m.id
             JOIN anos_lectivos al ON g.ano_lectivo_id=al.id
             WHERE a.id=? AND a.docente_id=? LIMIT 1'
        );
        $st->execute([$asig_id, $docente_id]);
        return $st->fetch() ?: null;
    }

    public function periodoActivo(): ?array {
        $st = $this->db->query(
            'SELECT p.* FROM periodos p
             JOIN anos_lectivos al ON p.ano_lectivo_id=al.id
             WHERE al.activo=1 AND p.activo=1 LIMIT 1'
        );
        return $st->fetch() ?: null;
    }

    public function periodos(): array {
        $st = $this->db->query(
            'SELECT p.* FROM periodos p
             JOIN anos_lectivos al ON p.ano_lectivo_id=al.id
             WHERE al.activo=1 ORDER BY p.numero'
        );
        return $st->fetchAll();
    }
}
