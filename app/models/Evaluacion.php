<?php
class Evaluacion {
    private PDO $db;
    public function __construct() { $this->db = Database::get(); }

    // ── INDICADORES ──────────────────────────────────────────

    public function indicadores(int $asignacion_id, int $periodo_id): array {
        $st = $this->db->prepare(
            'SELECT ie.* FROM indicadores_eval ie
             WHERE ie.asignacion_id=? AND ie.periodo_id=?
             ORDER BY ie.orden, ie.id'
        );
        $st->execute([$asignacion_id, $periodo_id]);
        $indicadores = $st->fetchAll();

        $total_mat = $this->totalMatriculados($asignacion_id);

        foreach ($indicadores as &$ind) {
            $ind['tipos']        = $this->tiposConEvaluaciones($ind['id'], $total_mat);
            // Compatibilidad: lista plana de evaluaciones para el calificador
            $ind['evaluaciones'] = [];
            foreach ($ind['tipos'] as $t) {
                foreach ($t['evaluaciones'] as $e) {
                    $ind['evaluaciones'][] = $e;
                }
            }
        }
        return $indicadores;
    }

    private function totalMatriculados(int $asignacion_id): int {
        $st = $this->db->prepare(
            'SELECT COUNT(*) FROM matriculas mt
             JOIN grupos g ON mt.grupo_id=g.id
             JOIN asignaciones a ON a.grupo_id=g.id
             WHERE a.id=? AND mt.estado="activo"'
        );
        $st->execute([$asignacion_id]);
        return (int) $st->fetchColumn();
    }

    // Devuelve tipos con sus evaluaciones para un indicador
    public function tiposConEvaluaciones(int $indicador_id, int $total): array {
        // Cargar tipos ordenados
        $st = $this->db->prepare(
            'SELECT t.id, t.nombre, t.porcentaje, t.privado, t.opcional, t.orden
             FROM tipos_eval t
             WHERE t.indicador_id = ?
             ORDER BY t.orden, t.id'
        );
        $st->execute([$indicador_id]);
        $tipos = $st->fetchAll();

        // Cargar evaluaciones con stats
        $st2 = $this->db->prepare(
            'SELECT e.id, e.nombre, e.fecha, e.porcentaje, e.tipo_eval_id,
                    e.privado, e.opcional, e.recuperacion_de,
                    r.nombre AS recuperacion_nombre,
                    COUNT(c.id)              AS evaluados,
                    SUM(c.valor < 6)         AS perdidos,
                    ROUND(AVG(c.valor), 1)   AS promedio,
                    ROUND(STDDEV_POP(c.valor),1) AS desviacion
             FROM evaluaciones e
             LEFT JOIN evaluaciones r ON r.id = e.recuperacion_de
             LEFT JOIN calificaciones c ON c.evaluacion_id = e.id
             WHERE e.indicador_id = ?
             GROUP BY e.id
             ORDER BY e.fecha, e.id'
        );
        $st2->execute([$indicador_id]);
        $evals = $st2->fetchAll();

        // Indexar evaluaciones por tipo
        $byTipo = [];
        foreach ($evals as $e) {
            $tid = $e['tipo_eval_id'] ?? 0;
            $byTipo[$tid][] = $e;
        }

        // Ensamblar tipos con sus evaluaciones y totales
        foreach ($tipos as &$t) {
            $evs = $byTipo[$t['id']] ?? [];
            foreach ($evs as &$r) {
                $r['total']         = $total;
                $r['evaluados']     = (int)$r['evaluados'];
                $r['perdidos']      = (int)$r['perdidos'];
                $r['pct_perdidos']  = $r['evaluados'] > 0 ? round($r['perdidos'] / $r['evaluados'] * 100) : 0;
                $r['sin_calificar'] = $r['evaluados'] === 0;
            }
            unset($r);
            $t['evaluaciones']  = array_values($evs);
            $t['total']         = $total;

            // Totales del tipo
            $t['sum_eval']   = array_sum(array_column($evs, 'evaluados'));
            $t['sum_perd']   = array_sum(array_column($evs, 'perdidos'));
            $conProm = array_filter($evs, fn($e) => !$e['sin_calificar']);
            $t['promedio']   = count($conProm)
                ? round(array_sum(array_column($conProm, 'promedio')) / count($conProm), 1)
                : null;
        }
        unset($t);

        // Evaluaciones sin tipo (huérfanas) — agrupar en un tipo virtual
        if (!empty($byTipo[0])) {
            $evs = $byTipo[0];
            foreach ($evs as &$r) {
                $r['total']         = $total;
                $r['evaluados']     = (int)$r['evaluados'];
                $r['perdidos']      = (int)$r['perdidos'];
                $r['pct_perdidos']  = $r['evaluados'] > 0 ? round($r['perdidos'] / $r['evaluados'] * 100) : 0;
                $r['sin_calificar'] = $r['evaluados'] === 0;
            }
            unset($r);
            $conProm = array_filter($evs, fn($e) => !$e['sin_calificar']);
            $tipos[] = [
                'id'           => null,
                'nombre'       => 'Sin tipo',
                'porcentaje'   => 0,
                'orden'        => 99,
                'evaluaciones' => array_values($evs),
                'total'        => $total,
                'sum_eval'     => array_sum(array_column($evs, 'evaluados')),
                'sum_perd'     => array_sum(array_column($evs, 'perdidos')),
                'promedio'     => count($conProm) ? round(array_sum(array_column($conProm, 'promedio')) / count($conProm), 1) : null,
            ];
        }

        return $tipos;
    }

    private function evaluacionesDeIndicador(int $indicador_id, int $total): array {
        $st = $this->db->prepare(
            'SELECT e.id, e.nombre, e.fecha, e.porcentaje,
                    COUNT(c.id)                          AS evaluados,
                    SUM(c.valor < 6)                     AS perdidos,
                    ROUND(AVG(c.valor), 1)               AS promedio,
                    ROUND(STDDEV_POP(c.valor), 1)        AS desviacion
             FROM evaluaciones e
             LEFT JOIN calificaciones c ON c.evaluacion_id=e.id
             WHERE e.indicador_id=?
             GROUP BY e.id
             ORDER BY e.fecha, e.id'
        );
        $st->execute([$indicador_id]);
        $rows = $st->fetchAll();

        foreach ($rows as &$r) {
            $r['total']         = $total;
            $r['evaluados']     = (int)$r['evaluados'];
            $r['perdidos']      = (int)$r['perdidos'];
            $r['pct_perdidos']  = $r['evaluados'] > 0
                                  ? round($r['perdidos'] / $r['evaluados'] * 100) : 0;
            $r['sin_calificar'] = $r['evaluados'] === 0;
        }
        return $rows;
    }

    // Totales de un indicador (fila de pie)
    public function totalesIndicador(int $indicador_id, int $total_mat): array {
        $st = $this->db->prepare(
            'SELECT SUM(ev.evaluados) AS ev_total,
                    SUM(ev.perdidos)  AS perd_total,
                    ROUND(AVG(ev.promedio), 1) AS promedio_global
             FROM (
               SELECT COUNT(c.id) AS evaluados, SUM(c.valor < 6) AS perdidos, AVG(c.valor) AS promedio
               FROM evaluaciones e
               LEFT JOIN calificaciones c ON c.evaluacion_id=e.id
               WHERE e.indicador_id=?
               GROUP BY e.id
             ) ev'
        );
        $st->execute([$indicador_id]);
        return $st->fetch() ?: ['ev_total'=>0,'perd_total'=>0,'promedio_global'=>null];
    }

    // ── CREAR / EDITAR / ELIMINAR INDICADOR ──────────────────

    public function crearIndicador(int $asig_id, int $periodo_id, string $desc, float $peso): int {
        $orden = $this->proximoOrden($asig_id, $periodo_id);
        $codigo = 'L' . $orden;
        $this->db->prepare(
            'INSERT INTO indicadores_eval (asignacion_id, periodo_id, codigo, descripcion, peso, orden)
             VALUES (?,?,?,?,?,?)'
        )->execute([$asig_id, $periodo_id, $codigo, $desc, $peso, $orden]);
        return (int) $this->db->lastInsertId();
    }

    public function editarIndicador(int $id, string $desc, float $peso): void {
        $this->db->prepare(
            'UPDATE indicadores_eval SET descripcion=?, peso=? WHERE id=?'
        )->execute([$desc, $peso, $id]);
    }

    public function eliminarIndicador(int $id, int $eliminadoPor): void {
        $qi = $this->db->prepare(
            'SELECT ie.*, m.nombre AS materia, g.nombre AS grupo_nombre, gr.nombre AS grado, g.id AS grupo_id, p.nombre AS periodo
             FROM indicadores_eval ie
             JOIN asignaciones a ON a.id = ie.asignacion_id
             JOIN materias m ON m.id = a.materia_id
             JOIN grupos g ON g.id = a.grupo_id
             JOIN grados gr ON gr.id = g.grado_id
             JOIN periodos p ON p.id = ie.periodo_id
             WHERE ie.id = ? LIMIT 1'
        );
        $qi->execute([$id]);
        $indicador = $qi->fetch();
        if (!$indicador) return;

        $qe = $this->db->prepare('SELECT * FROM evaluaciones WHERE indicador_id = ?');
        $qe->execute([$id]);
        $evaluaciones = $qe->fetchAll();

        $totalNotas = 0;
        foreach ($evaluaciones as &$ev) {
            $qc = $this->db->prepare('SELECT * FROM calificaciones WHERE evaluacion_id = ?');
            $qc->execute([$ev['id']]);
            $ev['calificaciones'] = $qc->fetchAll();
            $totalNotas += count($ev['calificaciones']);
        }
        unset($ev);

        $descripcion = sprintf(
            'Indicador "%s" — %s, %s %s, %s (%d evaluación(es), %d nota(s))',
            $indicador['codigo'], $indicador['materia'], $indicador['grado'], $indicador['grupo_nombre'],
            $indicador['periodo'], count($evaluaciones), $totalNotas
        );

        $this->db->prepare(
            'INSERT INTO papelera_notas (tipo, descripcion, grupo_id, snapshot_json, eliminado_por)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            'indicador', $descripcion, $indicador['grupo_id'],
            json_encode(['indicador' => $indicador, 'evaluaciones' => $evaluaciones]),
            $eliminadoPor,
        ]);

        $this->db->prepare('DELETE FROM indicadores_eval WHERE id=?')->execute([$id]);
    }

    private function proximoOrden(int $asig_id, int $periodo_id): int {
        $st = $this->db->prepare(
            'SELECT COALESCE(MAX(orden),0)+1 FROM indicadores_eval
             WHERE asignacion_id=? AND periodo_id=?'
        );
        $st->execute([$asig_id, $periodo_id]);
        return (int) $st->fetchColumn();
    }

    // ── TIPOS DE EVALUACIÓN ───────────────────────────────────

    public function crearTipoEval(int $indicador_id, string $nombre, float $pct, int $privado = 0, int $opcional = 0): int {
        $st = $this->db->prepare('SELECT COALESCE(MAX(orden),0)+1 FROM tipos_eval WHERE indicador_id=?');
        $st->execute([$indicador_id]);
        $orden = (int)$st->fetchColumn() ?: 1;

        $this->db->prepare(
            'INSERT INTO tipos_eval (indicador_id, nombre, porcentaje, privado, opcional, orden) VALUES (?,?,?,?,?,?)'
        )->execute([$indicador_id, $nombre, $pct, $privado, $opcional, $orden]);
        return (int) $this->db->lastInsertId();
    }

    public function eliminarTipoEval(int $id): void {
        // Desasociar evaluaciones antes de eliminar
        $this->db->prepare('UPDATE evaluaciones SET tipo_eval_id=NULL WHERE tipo_eval_id=?')->execute([$id]);
        $this->db->prepare('DELETE FROM tipos_eval WHERE id=?')->execute([$id]);
    }

    public function getTipoEval(int $id): ?array {
        $st = $this->db->prepare(
            'SELECT t.*, ie.asignacion_id,
                    (SELECT a.docente_id FROM asignaciones a WHERE a.id=ie.asignacion_id) AS docente_id,
                    ie.periodo_id,
                    (SELECT a2.grupo_id FROM asignaciones a2 WHERE a2.id=ie.asignacion_id) AS grupo_id
             FROM tipos_eval t
             JOIN indicadores_eval ie ON ie.id=t.indicador_id
             WHERE t.id=? LIMIT 1'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    // ── CREAR / ELIMINAR EVALUACIÓN ───────────────────────────

    public function crearEvaluacion(int $indicador_id, string $nombre, string $fecha, float $pct, int $privado = 0, ?int $tipo_eval_id = null, int $opcional = 0, ?int $recuperacion_de = null): int {
        $this->db->prepare(
            'INSERT INTO evaluaciones (indicador_id, tipo_eval_id, nombre, fecha, porcentaje, privado, opcional, recuperacion_de)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$indicador_id, $tipo_eval_id, $nombre, $fecha, $pct, $privado, $opcional, $recuperacion_de]);
        return (int) $this->db->lastInsertId();
    }

    public function eliminarEvaluacion(int $id, int $eliminadoPor): void {
        $qe = $this->db->prepare(
            'SELECT ev.*, ie.codigo, m.nombre AS materia, g.nombre AS grupo_nombre, gr.nombre AS grado, g.id AS grupo_id, p.nombre AS periodo
             FROM evaluaciones ev
             JOIN indicadores_eval ie ON ie.id = ev.indicador_id
             JOIN asignaciones a ON a.id = ie.asignacion_id
             JOIN materias m ON m.id = a.materia_id
             JOIN grupos g ON g.id = a.grupo_id
             JOIN grados gr ON gr.id = g.grado_id
             JOIN periodos p ON p.id = ie.periodo_id
             WHERE ev.id = ? LIMIT 1'
        );
        $qe->execute([$id]);
        $evaluacion = $qe->fetch();
        if (!$evaluacion) return;

        $qc = $this->db->prepare('SELECT * FROM calificaciones WHERE evaluacion_id = ?');
        $qc->execute([$id]);
        $calificaciones = $qc->fetchAll();

        $descripcion = sprintf(
            'Evaluación "%s" (indicador %s) — %s, %s %s, %s (%d nota(s))',
            $evaluacion['nombre'], $evaluacion['codigo'], $evaluacion['materia'],
            $evaluacion['grado'], $evaluacion['grupo_nombre'], $evaluacion['periodo'], count($calificaciones)
        );

        $this->db->prepare(
            'INSERT INTO papelera_notas (tipo, descripcion, grupo_id, snapshot_json, eliminado_por)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            'evaluacion', $descripcion, $evaluacion['grupo_id'],
            json_encode(['evaluacion' => $evaluacion, 'calificaciones' => $calificaciones]),
            $eliminadoPor,
        ]);

        $this->db->prepare('DELETE FROM evaluaciones WHERE id=?')->execute([$id]);
    }

    // ── CALIFICAR ─────────────────────────────────────────────

    // Devuelve todos los estudiantes del grupo con su nota para una evaluación
    public function estudiantesParaCalificar(int $evaluacion_id, int $grupo_id): array {
        $st = $this->db->prepare(
            'SELECT mt.id AS matricula_id, u.nombre, u.apellido,
                    c.valor, c.updated_at AS fecha_cal
             FROM matriculas mt
             JOIN estudiantes e  ON mt.estudiante_id=e.id
             JOIN usuarios u     ON e.usuario_id=u.id
             LEFT JOIN calificaciones c ON c.evaluacion_id=? AND c.matricula_id=mt.id
             WHERE mt.grupo_id=? AND mt.estado="activo"
             ORDER BY u.apellido, u.nombre'
        );
        $st->execute([$evaluacion_id, $grupo_id]);
        return $st->fetchAll();
    }

    public function guardarCalificacion(int $eval_id, int $mat_id, ?float $valor, ?int $modificadoPor = null): void {
        $modificadoPor = $modificadoPor ?? Auth::id();

        $qPrev = $this->db->prepare('SELECT id, valor FROM calificaciones WHERE evaluacion_id=? AND matricula_id=? LIMIT 1');
        $qPrev->execute([$eval_id, $mat_id]);
        $prev = $qPrev->fetch();

        if ($valor === null) {
            if ($prev) {
                $this->db->prepare('DELETE FROM calificaciones WHERE evaluacion_id=? AND matricula_id=?')
                          ->execute([$eval_id, $mat_id]);
                $this->registrarHistorial((int)$prev['id'], $eval_id, $mat_id, (float)$prev['valor'], null, 'eliminacion', $modificadoPor);
            }
            return;
        }

        $this->db->prepare(
            'INSERT INTO calificaciones (evaluacion_id, matricula_id, valor)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE valor=VALUES(valor), updated_at=NOW()'
        )->execute([$eval_id, $mat_id, $valor]);

        if ($prev) {
            if ((float)$prev['valor'] !== $valor) {
                $this->registrarHistorial((int)$prev['id'], $eval_id, $mat_id, (float)$prev['valor'], $valor, 'edicion', $modificadoPor);
            }
        } else {
            $qNew = $this->db->prepare('SELECT id FROM calificaciones WHERE evaluacion_id=? AND matricula_id=? LIMIT 1');
            $qNew->execute([$eval_id, $mat_id]);
            $nuevoId = (int)$qNew->fetchColumn();
            $this->registrarHistorial($nuevoId, $eval_id, $mat_id, null, $valor, 'creacion', $modificadoPor);
        }
    }

    private function registrarHistorial(int $calificacionId, int $evalId, int $matId, ?float $valorAnterior, ?float $valorNuevo, string $accion, int $modificadoPor): void {
        $this->db->prepare(
            'INSERT INTO calificaciones_historial (calificacion_id, evaluacion_id, matricula_id, valor_anterior, valor_nuevo, accion, modificado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$calificacionId, $evalId, $matId, $valorAnterior, $valorNuevo, $accion, $modificadoPor]);
    }

    // Obtener evaluación por id (para verificar pertenencia)
    public function getEvaluacion(int $id): ?array {
        $st = $this->db->prepare(
            'SELECT ev.*, ie.asignacion_id, ie.periodo_id, ie.codigo, ie.descripcion AS ind_desc
             FROM evaluaciones ev
             JOIN indicadores_eval ie ON ev.indicador_id=ie.id
             WHERE ev.id=? LIMIT 1'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    // ── CONSOLIDADOS ──────────────────────────────────────────

    // Por indicadores: resumen de cada indicador con promedio ponderado por estudiante
    public function consolidadoPorIndicadores(int $asig_id, int $periodo_id): array {
        $inds = $this->db->prepare(
            'SELECT ie.id, ie.codigo, ie.descripcion, ie.peso
             FROM indicadores_eval ie
             WHERE ie.asignacion_id=? AND ie.periodo_id=?
             ORDER BY ie.orden'
        );
        $inds->execute([$asig_id, $periodo_id]);
        $indicadores = $inds->fetchAll();

        // Estudiantes del grupo
        $ests = $this->db->prepare(
            'SELECT mt.id AS matricula_id, u.nombre, u.apellido
             FROM matriculas mt
             JOIN asignaciones a ON a.grupo_id=mt.grupo_id AND a.id=?
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u ON e.usuario_id=u.id
             WHERE mt.estado="activo"
             ORDER BY u.apellido, u.nombre'
        );
        $ests->execute([$asig_id]);
        $estudiantes = $ests->fetchAll();

        foreach ($estudiantes as &$est) {
            $est['indicadores'] = [];
            $nota_final = 0;
            $peso_total = 0;

            foreach ($indicadores as $ind) {
                // Promedio ponderado de evaluaciones dentro del indicador
                $st = $this->db->prepare(
                    'SELECT SUM(c.valor * ev.porcentaje/100) AS ponderado,
                            SUM(CASE WHEN c.valor IS NOT NULL THEN ev.porcentaje ELSE 0 END) AS pct_calificado
                     FROM evaluaciones ev
                     LEFT JOIN calificaciones c ON c.evaluacion_id=ev.id AND c.matricula_id=?
                     WHERE ev.indicador_id=?'
                );
                $st->execute([$est['matricula_id'], $ind['id']]);
                $res = $st->fetch();

                $nota_ind = null;
                if ($res['pct_calificado'] > 0) {
                    // Ajustar al 100% de lo calificado
                    $nota_ind = round($res['ponderado'] / ($res['pct_calificado']/100) * ($res['pct_calificado']/100), 2);
                    // Simplificado: promedio ponderado directo
                    $nota_ind = round($res['ponderado'], 2);
                }

                $est['indicadores'][$ind['id']] = $nota_ind;
                if ($nota_ind !== null) {
                    $nota_final += $nota_ind * $ind['peso'];
                    $peso_total += $ind['peso'];
                }
            }

            $est['nota_final'] = $peso_total > 0
                ? round($nota_final / $peso_total, 1)
                : null;
        }

        return compact('indicadores', 'estudiantes');
    }

    // ── FECHAS IMPORTANTES ────────────────────────────────────

    public function fechasImportantes(int $periodo_id): array {
        $st = $this->db->prepare(
            'SELECT tipo, fecha_inicio, fecha_fin FROM fechas_importantes
             WHERE periodo_id=? ORDER BY tipo'
        );
        $st->execute([$periodo_id]);
        $rows = $st->fetchAll();
        $mapa = [];
        foreach ($rows as $r) $mapa[$r['tipo']] = $r;
        return $mapa;
    }

    public static function estadoFecha(?array $fi): string {
        if (!$fi) return 'no_definida';
        $hoy = date('Y-m-d');
        if ($hoy < $fi['fecha_inicio']) return 'pendiente';
        if ($hoy > $fi['fecha_fin'])    return 'cerrada';
        return 'activa';
    }
}
