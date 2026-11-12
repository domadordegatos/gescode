<?php
class CalendarioController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::require();
        render('calendario/index');
    }

    // ── Exportar / suscribirse (ICS) ────────────────────────────

    public function exportar(): void {
        Auth::require();
        $uid = Auth::id();

        $token = $this->db->prepare('SELECT calendar_token FROM usuarios WHERE id = ?');
        $token->execute([$uid]);
        $token = $token->fetchColumn();

        if (!$token) {
            $token = bin2hex(random_bytes(24));
            $this->db->prepare('UPDATE usuarios SET calendar_token = ? WHERE id = ?')->execute([$token, $uid]);
        }

        $urlIcs = APP_URL . '/calendario/suscripcion/' . $token;
        render('calendario/exportar', ['urlIcs' => $urlIcs]);
    }

    public function regenerarToken(): void {
        Auth::require();
        csrfVerify();
        $token = bin2hex(random_bytes(24));
        $this->db->prepare('UPDATE usuarios SET calendar_token = ? WHERE id = ?')->execute([$token, Auth::id()]);
        flashSuccess('Se generó un nuevo enlace. El anterior ya no funcionará.');
        redirect('/calendario/exportar');
    }

    // Feed público (sin sesión) consumido por Google Calendar / Outlook mediante el token del usuario
    public function suscripcion(string $token): void {
        $token = preg_replace('/\.ics$/', '', $token);
        $u = $this->db->prepare('SELECT id, colegio_id, rol, nombre, apellido FROM usuarios WHERE calendar_token = ? AND activo = 1 LIMIT 1');
        $u->execute([$token]);
        $u = $u->fetch();
        if (!$u) { http_response_code(404); echo 'Enlace de calendario no válido.'; return; }

        $inicio = date('Y-m-d', strtotime('-1 month'));
        $fin    = date('Y-m-d', strtotime('+6 months'));

        $eventos = array_merge(
            $this->getTareas((int)$u['id'], $u['rol'], $inicio, $fin, (int)$u['colegio_id']),
            $this->getEvaluaciones((int)$u['id'], $u['rol'], $inicio, $fin, (int)$u['colegio_id']),
            $this->getFechasImportantes($inicio, $fin, (int)$u['colegio_id'])
        );

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="gescode-' . $u['id'] . '.ics"');

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Gescode//Calendario//ES\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "X-WR-CALNAME:Gescode - " . $this->icsEscape($u['nombre'] . ' ' . $u['apellido']) . "\r\n";
        foreach ($eventos as $ev) {
            if (empty($ev['fecha'])) continue;
            $fechaIcs = str_replace('-', '', $ev['fecha']);
            $uidBase = ($ev['tipo'] ?? 'evento') . '-' . ($ev['id'] ?? md5($ev['nombre'] . $ev['fecha']));
            $resumen = $this->icsEscape(($ev['nombre'] ?? 'Evento') . (!empty($ev['materia']) ? ' — ' . $ev['materia'] : ''));
            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . $uidBase . "@gescode\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART;VALUE=DATE:" . $fechaIcs . "\r\n";
            echo "SUMMARY:" . $resumen . "\r\n";
            if (!empty($ev['grupo'])) echo "DESCRIPTION:" . $this->icsEscape($ev['grupo']) . "\r\n";
            echo "END:VEVENT\r\n";
        }
        echo "END:VCALENDAR\r\n";
    }

    private function icsEscape(string $s): string {
        return str_replace(["\\", ",", ";", "\n"], ["\\\\", "\\,", "\\;", "\\n"], $s);
    }

    /**
     * GET /api/calendario?inicio=YYYY-MM-DD&fin=YYYY-MM-DD
     */
    public function eventos(): void {
        Auth::require();
        header('Content-Type: application/json');

        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin    = $_GET['fin']    ?? date('Y-m-t');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
            echo json_encode([]);
            return;
        }

        $uid       = Auth::id();
        $rol       = Auth::rol();
        $colegioId = Tenant::id();

        try {
            $eventos = array_merge(
                $this->getTareas($uid, $rol, $inicio, $fin, $colegioId),
                $this->getEvaluaciones($uid, $rol, $inicio, $fin, $colegioId),
                $this->getFechasImportantes($inicio, $fin, $colegioId)
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
            return;
        }

        usort($eventos, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
        echo json_encode($eventos);
    }

    // ── Helpers de columnas reutilizables ──────────────────────

    /** Columnas comunes para tareas — incluye grado + grupo */
    private function colsTarea(bool $conDocente): string {
        $doc = $conDocente
            ? "u.nombre AS docente_nombre, u.apellido AS docente_apellido"
            : "NULL AS docente_nombre, NULL AS docente_apellido";
        return "t.id, t.nombre, DATE(t.fecha_limite) AS fecha,
                'tarea' AS tipo,
                CONCAT(gr.nombre, ' ', g.nombre) AS grupo,
                m.nombre AS materia,
                a.grupo_id,
                $doc";
    }

    /** Columnas comunes para evaluaciones — incluye grado + grupo */
    private function colsEval(bool $conDocente): string {
        $doc = $conDocente
            ? "u.nombre AS docente_nombre, u.apellido AS docente_apellido"
            : "NULL AS docente_nombre, NULL AS docente_apellido";
        return "e.id, e.nombre, e.fecha,
                'evaluacion' AS tipo,
                CONCAT(gr.nombre, ' ', g.nombre) AS grupo,
                m.nombre AS materia,
                a.grupo_id,
                $doc";
    }

    /** JOINs para llegar a grado desde asignaciones */
    private function joinGrado(): string {
        return "JOIN grados gr ON gr.id = g.grado_id";
    }

    // ── Tareas ─────────────────────────────────────────────────

    private function getTareas(int $uid, string $rol, string $inicio, string $fin, int $colegioId): array {
        if ($rol === 'admin' || $rol === 'soporte') {
            $sql = "SELECT " . $this->colsTarea(true) . "
                    FROM tareas t
                    JOIN asignaciones a ON a.id = t.asignacion_id
                    JOIN grupos g       ON g.id = a.grupo_id
                    " . $this->joinGrado() . "
                    JOIN materias m     ON m.id = a.materia_id
                    JOIN usuarios u     ON u.id = a.docente_id
                    WHERE t.fecha_limite IS NOT NULL
                      AND DATE(t.fecha_limite) BETWEEN ? AND ?
                      AND u.colegio_id = ?
                    ORDER BY t.fecha_limite";
            $st = $this->db->prepare($sql);
            $st->execute([$inicio, $fin, $colegioId]);

        } elseif ($rol === 'docente') {
            $sql = "SELECT " . $this->colsTarea(false) . "
                    FROM tareas t
                    JOIN asignaciones a ON a.id = t.asignacion_id
                    JOIN grupos g       ON g.id = a.grupo_id
                    " . $this->joinGrado() . "
                    JOIN materias m     ON m.id = a.materia_id
                    WHERE a.docente_id = ?
                      AND t.fecha_limite IS NOT NULL
                      AND DATE(t.fecha_limite) BETWEEN ? AND ?
                    ORDER BY t.fecha_limite";
            $st = $this->db->prepare($sql);
            $st->execute([$uid, $inicio, $fin]);

        } elseif (in_array($rol, ['estudiante', 'padre'])) {
            $estUid = ($rol === 'padre') ? $this->getEstudianteUidPorPadre($uid) : $uid;
            $sql = "SELECT " . $this->colsTarea(false) . "
                    FROM tareas t
                    JOIN asignaciones a  ON a.id = t.asignacion_id
                    JOIN grupos g        ON g.id = a.grupo_id
                    " . $this->joinGrado() . "
                    JOIN materias m      ON m.id = a.materia_id
                    JOIN matriculas mat  ON mat.grupo_id = a.grupo_id AND mat.estado = 'activo'
                    JOIN estudiantes est ON est.id = mat.estudiante_id
                    WHERE est.usuario_id = ?
                      AND t.fecha_limite IS NOT NULL
                      AND DATE(t.fecha_limite) BETWEEN ? AND ?
                    ORDER BY t.fecha_limite";
            $st = $this->db->prepare($sql);
            $st->execute([$estUid, $inicio, $fin]);

        } else {
            return [];
        }

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ── Evaluaciones ───────────────────────────────────────────

    private function getEvaluaciones(int $uid, string $rol, string $inicio, string $fin, int $colegioId): array {
        if ($rol === 'admin' || $rol === 'soporte') {
            $sql = "SELECT " . $this->colsEval(true) . "
                    FROM evaluaciones e
                    JOIN indicadores_eval ie ON ie.id = e.indicador_id
                    JOIN asignaciones a       ON a.id = ie.asignacion_id
                    JOIN grupos g             ON g.id = a.grupo_id
                    " . $this->joinGrado() . "
                    JOIN materias m           ON m.id = a.materia_id
                    JOIN usuarios u           ON u.id = a.docente_id
                    WHERE e.fecha BETWEEN ? AND ?
                      AND u.colegio_id = ?
                    ORDER BY e.fecha";
            $st = $this->db->prepare($sql);
            $st->execute([$inicio, $fin, $colegioId]);

        } elseif ($rol === 'docente') {
            $sql = "SELECT " . $this->colsEval(false) . "
                    FROM evaluaciones e
                    JOIN indicadores_eval ie ON ie.id = e.indicador_id
                    JOIN asignaciones a       ON a.id = ie.asignacion_id
                    JOIN grupos g             ON g.id = a.grupo_id
                    " . $this->joinGrado() . "
                    JOIN materias m           ON m.id = a.materia_id
                    WHERE a.docente_id = ?
                      AND e.fecha BETWEEN ? AND ?
                    ORDER BY e.fecha";
            $st = $this->db->prepare($sql);
            $st->execute([$uid, $inicio, $fin]);

        } elseif (in_array($rol, ['estudiante', 'padre'])) {
            $estUid = ($rol === 'padre') ? $this->getEstudianteUidPorPadre($uid) : $uid;
            $sql = "SELECT " . $this->colsEval(false) . "
                    FROM evaluaciones e
                    JOIN indicadores_eval ie ON ie.id = e.indicador_id
                    JOIN asignaciones a       ON a.id = ie.asignacion_id
                    JOIN grupos g             ON g.id = a.grupo_id
                    " . $this->joinGrado() . "
                    JOIN materias m           ON m.id = a.materia_id
                    JOIN matriculas mat        ON mat.grupo_id = a.grupo_id AND mat.estado = 'activo'
                    JOIN estudiantes est       ON est.id = mat.estudiante_id
                    WHERE est.usuario_id = ?
                      AND e.fecha BETWEEN ? AND ?
                    ORDER BY e.fecha";
            $st = $this->db->prepare($sql);
            $st->execute([$estUid, $inicio, $fin]);

        } else {
            return [];
        }

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ── Fechas institucionales ─────────────────────────────────

    private function getFechasImportantes(string $inicio, string $fin, int $colegioId): array {
        $labels = [
            'notas'          => 'Cierre de notas',
            'observaciones'  => 'Cierre de observaciones',
            'planeaciones'   => 'Planeaciones',
            'recuperaciones' => 'Recuperaciones',
            'habilitaciones' => 'Habilitaciones',
            'receso'         => 'Receso escolar',
            'otro'           => 'Fecha institucional',
            'evento'         => 'Evento institucional',
        ];

        // colegio_id vive directo en fechas_importantes (no depende de tener período
        // asignado) para que los eventos institucionales puedan publicarse sin estar
        // ligados a un período académico específico.
        $sql = "SELECT fi.id, fi.tipo, fi.tipo AS nombre, fi.titulo, fi.descripcion,
                       fi.fecha_inicio AS fecha,
                       'fecha_importante' AS tipo_cal,
                       p.nombre AS periodo,
                       fi.fecha_fin,
                       NULL AS grupo, NULL AS materia,
                       NULL AS grupo_id,
                       NULL AS docente_nombre, NULL AS docente_apellido
                FROM fechas_importantes fi
                LEFT JOIN periodos p      ON p.id = fi.periodo_id
                LEFT JOIN anos_lectivos al ON al.id = p.ano_lectivo_id
                WHERE COALESCE(fi.colegio_id, al.colegio_id) = ?
                  AND fi.fecha_inicio <= ? AND fi.fecha_fin >= ?
                ORDER BY fi.fecha_inicio";
        $st = $this->db->prepare($sql);
        $st->execute([$colegioId, $fin, $inicio]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            // Los eventos institucionales usan su propio título; el resto usa la
            // etiqueta fija según el tipo.
            $r['nombre'] = ($r['tipo'] === 'evento' && !empty($r['titulo']))
                ? $r['titulo']
                : ($labels[$r['nombre']] ?? ucfirst($r['nombre']));
            // El campo que el resto del código espera se llama 'tipo' (categoría visual)
            $r['tipo'] = $r['tipo_cal'];
            unset($r['tipo_cal']);
        }
        return $rows;
    }

    // ── Helper padre → estudiante ──────────────────────────────

    private function getEstudianteUidPorPadre(int $padreId): int {
        $st = $this->db->prepare(
            "SELECT e.usuario_id FROM estudiantes e
             JOIN acudientes a ON a.estudiante_id = e.id
             WHERE a.usuario_id = ? LIMIT 1"
        );
        $st->execute([$padreId]);
        return (int)($st->fetchColumn() ?: $padreId);
    }
}
