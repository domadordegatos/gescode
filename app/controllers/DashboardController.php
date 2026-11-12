<?php
class DashboardController {

    public function index(): void {
        Auth::require();

        $rol  = Auth::rol();
        $data = [];

        switch ($rol) {
            case 'admin':
            case 'soporte':
            case 'secretaria':
                $data = $this->datosAdmin();
                break;
            case 'docente':
                $data = $this->datosDocente();
                break;
            case 'estudiante':
                $data = $this->datosEstudiante();
                break;
            case 'padre':
                $data = $this->datosPadre();
                break;
        }

        $view = ($rol === 'padre') ? 'padre/dashboard' : 'dashboard/index';
        render($view, $data);
    }

    // ── Avisos pendientes de los módulos nuevos (circulares, PQRS, encuestas, autoevaluación) ──
    private function avisosPendientes(string $rol): array {
        $db  = Database::get();
        $uid = Auth::id();
        $cid = Tenant::id();

        $flags = $db->query(
            "SELECT permite_circulares, permite_pqrs, permite_encuestas, permite_autoevaluacion
             FROM colegios WHERE id = $cid"
        )->fetch();

        $avisos = [];
        $esGestor = in_array($rol, ['admin', 'soporte'], true);

        // Circulares sin leer (todos los roles que reciben circulares)
        if (!empty($flags['permite_circulares'])) {
            $st = $db->prepare('SELECT COUNT(*) FROM circulares_destinatarios WHERE usuario_id = ? AND leido = 0');
            $st->execute([$uid]);
            $n = (int)$st->fetchColumn();
            if ($n > 0) {
                $avisos[] = ['icono' => 'mdi-bullhorn-outline', 'color' => '#0369A1', 'texto' => "$n circular" . ($n > 1 ? 'es' : '') . " sin leer", 'url' => '/circulares'];
            }
        }

        // PQRS
        if (!empty($flags['permite_pqrs'])) {
            if ($esGestor) {
                $st = $db->prepare("SELECT COUNT(*) FROM pqrs WHERE colegio_id = ? AND estado IN ('radicado','en_tramite')");
                $st->execute([$cid]);
                $n = (int)$st->fetchColumn();
                if ($n > 0) {
                    $avisos[] = ['icono' => 'mdi-file-document-edit-outline', 'color' => '#BE185D', 'texto' => "$n PQRS por atender", 'url' => '/pqrs'];
                }
            } else {
                $st = $db->prepare("SELECT COUNT(*) FROM pqrs WHERE usuario_id = ? AND estado IN ('radicado','en_tramite')");
                $st->execute([$uid]);
                $n = (int)$st->fetchColumn();
                if ($n > 0) {
                    $avisos[] = ['icono' => 'mdi-file-document-edit-outline', 'color' => '#BE185D', 'texto' => "$n solicitud(es) PQRS en trámite", 'url' => '/pqrs'];
                }
            }
        }

        // Encuestas sin responder
        if (!empty($flags['permite_encuestas']) && !$esGestor) {
            $st = $db->prepare(
                "SELECT COUNT(*) FROM encuestas_participantes p
                 JOIN encuestas e ON e.id = p.encuesta_id
                 WHERE p.usuario_id = ? AND p.completado = 0 AND e.activa = 1"
            );
            $st->execute([$uid]);
            $n = (int)$st->fetchColumn();
            if ($n > 0) {
                $avisos[] = ['icono' => 'mdi-poll', 'color' => '#4D7C0F', 'texto' => "$n encuesta" . ($n > 1 ? 's' : '') . " pendiente" . ($n > 1 ? 's' : ''), 'url' => '/encuestas'];
            }
        }

        // Autoevaluación pendiente (solo padre)
        if (!empty($flags['permite_autoevaluacion']) && $rol === 'padre') {
            $st = $db->prepare(
                "SELECT COUNT(*)
                 FROM evaluaciones ev
                 JOIN indicadores_eval ie ON ev.indicador_id = ie.id
                 JOIN asignaciones a      ON ie.asignacion_id = a.id
                 JOIN grupos g            ON a.grupo_id = g.id
                 JOIN matriculas mt       ON mt.grupo_id = g.id
                 JOIN estudiantes e       ON mt.estudiante_id = e.id
                 JOIN acudientes ac       ON ac.estudiante_id = e.id
                 WHERE ie.autoevaluacion = 1 AND ac.usuario_id = ?
                   AND NOT EXISTS (SELECT 1 FROM calificaciones c WHERE c.evaluacion_id = ev.id AND c.matricula_id = mt.id)"
            );
            $st->execute([$uid]);
            $n = (int)$st->fetchColumn();
            if ($n > 0) {
                $avisos[] = ['icono' => 'mdi-account-check-outline', 'color' => '#7C2D12', 'texto' => "$n autoevaluación" . ($n > 1 ? 'es' : '') . " pendiente" . ($n > 1 ? 's' : ''), 'url' => '/autoevaluacion'];
            }
        }

        return $avisos;
    }

    // ── Por rol ──────────────────────────────────────────────

    private function mensajesRecientes(int $uid, int $limit = 5): array {
        $db = Database::get();
        $st = $db->prepare(
            'SELECT m.id, m.asunto, m.created_at,
                    md.leido,
                    u.nombre AS remitente_nombre, u.apellido AS remitente_apellido,
                    u.rol AS remitente_rol
             FROM mensajes_destinatarios md
             JOIN mensajes m ON m.id = md.mensaje_id
             JOIN usuarios u ON u.id = m.remitente_id
             WHERE md.usuario_id = ? AND md.eliminado = 0
             ORDER BY m.created_at DESC
             LIMIT ?'
        );
        $st->execute([$uid, $limit]);
        return $st->fetchAll();
    }

    private function datosAdmin(): array {
        $db  = Database::get();
        $uid = Auth::id();
        return [
            'total_usuarios'     => (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
            'total_estudiantes'  => (int) $db->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn(),
            'total_docentes'     => (int) $db->query("SELECT COUNT(*) FROM usuarios WHERE rol='docente'")->fetchColumn(),
            'total_grupos'       => (int) $db->query('SELECT COUNT(*) FROM grupos g JOIN anos_lectivos a ON g.ano_lectivo_id=a.id WHERE a.activo=1')->fetchColumn(),
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos($uid),
            'mensajes_recientes' => $this->mensajesRecientes($uid),
            'avisos_pendientes'  => array_merge($this->avisosPendientes('admin'), $this->avisoAccesosSospechosos()),
        ];
    }

    /** Avisa a admin/soporte si hubo varios intentos de acceso fallidos en las últimas 24 horas */
    private function avisoAccesosSospechosos(): array {
        $db = Database::get();
        $st = $db->prepare(
            "SELECT COUNT(*) FROM accesos_log
             WHERE colegio_id = ? AND exitoso = 0 AND created_at >= NOW() - INTERVAL 1 DAY"
        );
        $st->execute([Tenant::id()]);
        $n = (int)$st->fetchColumn();

        if ($n < 5) return [];
        return [[
            'icono' => 'mdi-shield-alert-outline', 'color' => '#DC2626',
            'texto' => "$n intentos de acceso fallidos en las últimas 24h", 'url' => '/accesos',
        ]];
    }

    private function datosDocente(): array {
        $db  = Database::get();
        $uid = Auth::id();

        $grupos = $db->prepare(
            'SELECT a.id AS asignacion_id, g.id AS grupo_id,
                    gr.nombre AS grado, g.nombre AS grupo, m.nombre AS materia,
                    gr.nivel,
                    COUNT(mt.id) AS total_estudiantes
             FROM asignaciones a
             JOIN grupos g   ON a.grupo_id   = g.id
             JOIN grados gr  ON g.grado_id   = gr.id
             JOIN materias m ON a.materia_id = m.id
             LEFT JOIN matriculas mt ON mt.grupo_id = g.id AND mt.estado = "activo"
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE a.docente_id = ? AND al.activo = 1
             GROUP BY a.id
             ORDER BY gr.nivel, g.nombre, m.nombre'
        );
        $grupos->execute([$uid]);

        return [
            'grupos'             => $grupos->fetchAll(),
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos($uid),
            'mensajes_recientes' => $this->mensajesRecientes($uid),
            'avisos_pendientes'  => $this->avisosPendientes('docente'),
        ];
    }

    private function datosEstudiante(): array {
        $db  = Database::get();
        $uid = Auth::id();

        $est = $db->prepare(
            'SELECT e.id FROM estudiantes e WHERE e.usuario_id = ? LIMIT 1'
        );
        $est->execute([$uid]);
        $estudiante = $est->fetch();

        $notas = [];
        if ($estudiante) {
            $st = $db->prepare(
                'SELECT m.nombre AS materia,
                        ROUND(AVG(n.valor), 1) AS promedio
                 FROM notas n
                 JOIN indicadores i ON n.indicador_id = i.id
                 JOIN tipos_evaluacion te ON i.tipo_eval_id = te.id
                 JOIN asignaciones a ON te.asignacion_id = a.id
                 JOIN materias m ON a.materia_id = m.id
                 JOIN matriculas mt ON n.matricula_id = mt.id
                 JOIN periodos p ON te.periodo_id = p.id
                 WHERE mt.estudiante_id = ? AND p.activo = 1
                 GROUP BY a.materia_id'
            );
            $st->execute([$estudiante['id']]);
            $notas = $st->fetchAll();
        }

        return [
            'notas_periodo'      => $notas,
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos($uid),
            'mensajes_recientes' => $this->mensajesRecientes($uid),
            'avisos_pendientes'  => [],
        ];
    }

    private function datosPadre(): array {
        $db  = Database::get();
        $uid = Auth::id();

        $st = $db->prepare(
            'SELECT u.nombre, u.apellido, e.id AS estudiante_id,
                    gr.nombre AS grado, g.nombre AS grupo, g.id AS grupo_id, mt.id AS matricula_id
             FROM acudientes ac
             JOIN estudiantes e ON ac.estudiante_id = e.id
             JOIN usuarios u    ON e.usuario_id = u.id
             JOIN matriculas mt ON mt.estudiante_id = e.id
             JOIN grupos g      ON mt.grupo_id = g.id
             JOIN grados gr     ON g.grado_id = gr.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE ac.usuario_id = ? AND al.activo = 1 AND mt.estado = "activo"
             ORDER BY gr.nivel, u.apellido'
        );
        $st->execute([$uid]);
        $hijos = $st->fetchAll();

        $hijo_id = (int)($_GET['hijo'] ?? ($hijos[0]['estudiante_id'] ?? 0));
        $hijo_sel = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $hijo_id) { $hijo_sel = $h; break; }
        }
        if (!$hijo_sel && !empty($hijos)) {
            $hijo_sel = $hijos[0];
            $hijo_id  = (int)$hijo_sel['estudiante_id'];
        }

        $notas_hijo = [];
        $asistencia_hijo = [];

        if ($hijo_sel) {
            $mid = (int)$hijo_sel['matricula_id'];

            // Notas por materia (período activo)
            $qn = $db->prepare(
                'SELECT m.nombre AS materia,
                        ROUND(AVG(c.valor), 1) AS promedio,
                        COUNT(c.id) AS total_eval
                 FROM calificaciones c
                 JOIN evaluaciones ev ON c.evaluacion_id = ev.id
                 JOIN indicadores_eval ie ON ev.indicador_id = ie.id
                 JOIN asignaciones a ON ie.asignacion_id = a.id
                 JOIN materias m ON a.materia_id = m.id
                 JOIN periodos p ON ie.periodo_id = p.id
                 WHERE c.matricula_id = ? AND p.activo = 1
                 GROUP BY a.materia_id
                 ORDER BY m.nombre'
            );
            $qn->execute([$mid]);
            $notas_hijo = $qn->fetchAll();

            // Últimos 10 registros de asistencia
            $gid = (int)$hijo_sel['grupo_id'];
            $qa = $db->prepare(
                'SELECT a.fecha, a.estado, a.observacion, a.created_at,
                        u.nombre AS reg_nombre, u.apellido AS reg_apellido,
                        (SELECT m.nombre FROM asignaciones a2
                         JOIN materias m ON a2.materia_id = m.id
                         WHERE a2.docente_id = a.registrado_por AND a2.grupo_id = ?
                         ORDER BY m.nombre LIMIT 1) AS materia
                 FROM asistencia a
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 WHERE a.matricula_id = ?
                 ORDER BY a.fecha DESC
                 LIMIT 10'
            );
            $qa->execute([$gid, $mid]);
            $asistencia_hijo = $qa->fetchAll();
        }

        return [
            'hijos'              => $hijos,
            'hijo_sel'           => $hijo_sel,
            'notas_hijo'         => $notas_hijo,
            'asistencia_hijo'    => $asistencia_hijo,
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos($uid),
            'mensajes_recientes' => $this->mensajesRecientes($uid),
            'avisos_pendientes'  => $this->avisosPendientes('padre'),
        ];
    }
}
