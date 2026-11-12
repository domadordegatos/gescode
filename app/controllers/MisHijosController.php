<?php
class MisHijosController {

    public function index(): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        if (count($hijos) === 1) {
            redirect('/mis-hijos/' . $hijos[0]['estudiante_id']);
        }
        render('padre/mis_hijos', [
            'hijos'              => $hijos,
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function detalle(int $estudianteId): void {
        Auth::requireRole('padre');

        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { http_response_code(403); die('Acceso no autorizado'); }

        $tab = $_GET['tab'] ?? 'resumen';
        $db  = Database::get();
        $mid = (int)$hijo['matricula_id'];

        // ── Datos personales del estudiante ──────────────────────
        $qp = $db->prepare(
            'SELECT u.nombre, u.apellido, u.email, u.rol,
                    e.documento, e.tipo_documento, e.fecha_nacimiento, e.genero,
                    e.lugar_nacimiento, e.estrato, e.eps, e.direccion, e.barrio,
                    e.contacto_emergencia, e.tel_emergencia,
                    p.telefono, p.celular, p.ciudad
             FROM estudiantes e
             JOIN usuarios u   ON e.usuario_id = u.id
             LEFT JOIN perfiles p ON p.usuario_id = u.id
             WHERE e.id = ? LIMIT 1'
        );
        $qp->execute([$estudianteId]);
        $info = $qp->fetch();

        // ── Familiares (todos los acudientes del estudiante) ─────
        $qf = $db->prepare(
            'SELECT u.nombre, u.apellido, u.email, ac.parentesco
             FROM acudientes ac
             JOIN usuarios u ON ac.usuario_id = u.id
             WHERE ac.estudiante_id = ?
             ORDER BY u.nombre'
        );
        $qf->execute([$estudianteId]);
        $familiares = $qf->fetchAll();

        // ── Notas por período y materia ──────────────────────────
        $qn = $db->prepare(
            'SELECT m.nombre AS materia, m.id AS materia_id,
                    p.id AS periodo_id, p.nombre AS periodo, p.activo,
                    p.numero, p.porcentaje_ano,
                    ROUND(AVG(c.valor), 1) AS promedio,
                    COUNT(c.id) AS total_eval
             FROM calificaciones c
             JOIN evaluaciones ev   ON c.evaluacion_id = ev.id
             JOIN indicadores_eval ie ON ev.indicador_id = ie.id
             JOIN asignaciones a    ON ie.asignacion_id = a.id
             JOIN materias m        ON a.materia_id = m.id
             JOIN periodos p        ON ie.periodo_id = p.id
             WHERE c.matricula_id = ?
             GROUP BY m.id, p.id
             ORDER BY m.nombre, p.numero'
        );
        $qn->execute([$mid]);
        $notas_raw = $qn->fetchAll();

        // Armar estructura: materias → periodos
        $periodos_ids = [];
        $notas_tabla  = []; // [materia_id => ['nombre'=>..., 'periodos'=>[pid=>['promedio'=>..., 'activo'=>...]]]]
        foreach ($notas_raw as $row) {
            $periodos_ids[$row['periodo_id']] = [
                'nombre'     => $row['periodo'],
                'activo'     => $row['activo'],
                'numero'     => (int)$row['numero'],
                'porcentaje' => $row['porcentaje_ano'],
            ];
            $mid_key = $row['materia_id'];
            if (!isset($notas_tabla[$mid_key])) {
                $notas_tabla[$mid_key] = ['nombre' => $row['materia'], 'periodos' => []];
            }
            $notas_tabla[$mid_key]['periodos'][$row['periodo_id']] = [
                'promedio'   => $row['promedio'],
                'total_eval' => $row['total_eval'],
                'activo'     => (bool)$row['activo'],
            ];
        }
        ksort($periodos_ids);
        $periodo_activo_id = null;
        foreach ($periodos_ids as $pid => $per) {
            if ($per['activo']) { $periodo_activo_id = $pid; break; }
        }

        // grupo_id necesario para subconsultas de materia
        $gid = (int)$hijo['grupo_id'];

        // ── Período seleccionado por el padre (selector de períodos) ─
        // Por defecto siempre el período activo; se puede navegar a otros vía ?periodo=
        $periodo_mostrado_id = isset($_GET['periodo']) ? (int)$_GET['periodo'] : null;
        if ($periodo_mostrado_id === null || !isset($periodos_ids[$periodo_mostrado_id])) {
            $periodo_mostrado_id = $periodo_activo_id;
        }

        $uid_p = Auth::id();

        $qeSql = 'SELECT ev.nombre AS eval_nombre,
                          ie.descripcion AS logro,
                          ie.codigo AS logro_cod,
                          m.id AS materia_id,
                          c.valor, c.updated_at
                   FROM calificaciones c
                   JOIN evaluaciones ev      ON c.evaluacion_id = ev.id
                   JOIN indicadores_eval ie  ON ev.indicador_id = ie.id
                   JOIN asignaciones a       ON ie.asignacion_id = a.id
                   JOIN materias m           ON a.materia_id = m.id
                   JOIN periodos p           ON ie.periodo_id = p.id
                   WHERE c.matricula_id = ? AND p.id = ? AND ie.privado = 0 AND ev.privado = 0
                   ORDER BY m.id, ie.id, c.updated_at DESC';

        // 1. Siempre evaluamos el período REALMENTE activo para el badge de alertas,
        //    independiente del período que el padre esté viendo en este momento.
        $notas_alertas = 0;
        $evals_periodo_activo = [];
        if ($periodo_activo_id) {
            $qe = $db->prepare($qeSql);
            $qe->execute([$mid, $periodo_activo_id]);
            foreach ($qe->fetchAll() as $row) {
                $evals_periodo_activo[(int)$row['materia_id']][] = $row;
            }
            foreach ($evals_periodo_activo as $mk => $evals) {
                if (!isset($notas_tabla[$mk])) continue;
                $sess_nota = 'nota_vista_' . $mk . '_' . $uid_p . '_' . $estudianteId;
                $ultima    = $_SESSION[$sess_nota] ?? null;
                $max_upd   = max(array_column($evals, 'updated_at'));
                $es_nueva  = ($ultima === null || $max_upd > $ultima);
                $notas_tabla[$mk]['es_nueva'] = $es_nueva;
                if ($es_nueva) $notas_alertas++;
            }
        }

        // 2. Las evaluaciones que se muestran en el panel corresponden al período
        //    SELECCIONADO (puede ser el activo u otro anterior).
        if ($periodo_mostrado_id === $periodo_activo_id) {
            foreach ($evals_periodo_activo as $mk => $evals) {
                if (isset($notas_tabla[$mk])) $notas_tabla[$mk]['evals'] = $evals;
            }
        } elseif ($periodo_mostrado_id) {
            $qe2 = $db->prepare($qeSql);
            $qe2->execute([$mid, $periodo_mostrado_id]);
            foreach ($qe2->fetchAll() as $row) {
                $mk = (int)$row['materia_id'];
                if (isset($notas_tabla[$mk])) {
                    $notas_tabla[$mk]['evals'][] = $row;
                }
            }
            // Al ver un período distinto al activo no tiene sentido marcar "nueva"
            foreach ($notas_tabla as $mk => &$mat_ref) { $mat_ref['es_nueva'] = false; }
            unset($mat_ref);
        }

        // ── Asistencia ───────────────────────────────────────────
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
             LIMIT 200'
        );
        $qa->execute([$gid, $mid]);
        $asistencia_todas = $qa->fetchAll();

        // Badge asistencia: contar alertas (no presente / no excusa) desde la última visita al tab
        // Siempre sobre el histórico completo, independiente del período que se esté viendo.
        $sess_key = 'asist_vista_' . $estudianteId . '_' . Auth::id();
        $ultima_visita = $_SESSION[$sess_key] ?? null;

        // Si el tab actual es asistencia → registrar visita ahora (vacía el badge)
        if ($tab === 'asistencia') {
            $_SESSION[$sess_key] = date('Y-m-d H:i:s');
        }

        $asist_alertas = 0;
        foreach ($asistencia_todas as $r) {
            if (in_array($r['estado'], ['presente', 'excusa'])) continue;
            if ($ultima_visita === null || $r['created_at'] > $ultima_visita) {
                $asist_alertas++;
            }
        }

        // Rango de fechas del período seleccionado, para filtrar la asistencia mostrada
        $qpr = $db->prepare('SELECT fecha_inicio, fecha_fin FROM periodos WHERE id = ? LIMIT 1');
        $qpr->execute([$periodo_mostrado_id]);
        $rango_periodo = $qpr->fetch();

        if ($rango_periodo) {
            $asistencia = array_values(array_filter($asistencia_todas, function($r) use ($rango_periodo) {
                return $r['fecha'] >= $rango_periodo['fecha_inicio'] && $r['fecha'] <= $rango_periodo['fecha_fin'];
            }));
        } else {
            $asistencia = $asistencia_todas;
        }

        // Resumen asistencia (del período mostrado)
        $resumen_asist = ['presente' => 0, 'tarde' => 0, 'excusa' => 0, 'ausente' => 0, 'incapacidad' => 0];
        foreach ($asistencia as $r) {
            if (isset($resumen_asist[$r['estado']])) $resumen_asist[$r['estado']]++;
        }

        // ── Observaciones del docente ────────────────────────────
        $uid_padre = Auth::id();
        $qo = $db->prepare(
            'SELECT o.id, o.fecha, o.tipo, o.descripcion,
                    u.nombre AS docente_nombre, u.apellido AS docente_apellido,
                    (SELECT m.nombre FROM asignaciones a2
                     JOIN materias m ON a2.materia_id = m.id
                     WHERE a2.docente_id = o.docente_id AND a2.grupo_id = ?
                     ORDER BY m.nombre LIMIT 1) AS materia,
                    (SELECT 1 FROM observaciones_vistas ov
                     WHERE ov.observacion_id = o.id AND ov.usuario_id = ?) AS vista
             FROM observaciones o
             JOIN usuarios u ON o.docente_id = u.id
             WHERE o.matricula_id = ?
             ORDER BY o.fecha DESC
             LIMIT 20'
        );
        $qo->execute([$gid, $uid_padre, $mid]);
        $observaciones = $qo->fetchAll();

        $obs_no_leidas = 0;
        foreach ($observaciones as $o) {
            if (!$o['vista']) $obs_no_leidas++;
        }

        // ── Aula: materias del grupo con docente ─────────────────
        $qa2 = $db->prepare(
            'SELECT a.id AS asignacion_id, a.materia_id,
                    m.nombre AS materia,
                    u.nombre AS doc_nombre, u.apellido AS doc_apellido
             FROM asignaciones a
             JOIN materias m ON a.materia_id = m.id
             JOIN usuarios u ON a.docente_id = u.id
             WHERE a.grupo_id = ?
             ORDER BY m.nombre'
        );
        $qa2->execute([$gid]);
        $aula_materias = $qa2->fetchAll();

        // Enriquecer con nota del período mostrado y conteo de tareas
        foreach ($aula_materias as &$am) {
            $mid_m = $am['materia_id'];
            // nota del período seleccionado
            $nota_act = null;
            if ($periodo_mostrado_id && isset($notas_tabla[$mid_m]['periodos'][$periodo_mostrado_id])) {
                $nota_act = (float)$notas_tabla[$mid_m]['periodos'][$periodo_mostrado_id]['promedio'];
            }
            $am['nota_activa'] = $nota_act;

            // conteo tareas del período seleccionado
            $qt = $db->prepare(
                'SELECT COUNT(*) FROM tareas WHERE asignacion_id = ?' .
                ($periodo_mostrado_id ? ' AND periodo_id = ?' : '')
            );
            $params = $periodo_mostrado_id ? [$am['asignacion_id'], $periodo_mostrado_id] : [$am['asignacion_id']];
            $qt->execute($params);
            $am['total_tareas'] = (int)$qt->fetchColumn();
        }
        unset($am);

        // Mapa materia_id => docente (nombre completo)
        $docente_por_materia = [];
        foreach ($aula_materias as $am) {
            $docente_por_materia[$am['materia_id']] = $am['doc_nombre'] . ' ' . $am['doc_apellido'];
        }

        // ── Tareas del grupo ─────────────────────────────────────
        $qt2 = $db->prepare(
            'SELECT t.id, t.nombre, t.descripcion, t.tipo_entrega, t.periodo_id,
                    t.fecha_publicacion, t.fecha_limite,
                    m.nombre AS materia, a.materia_id,
                    u.nombre AS doc_nombre, u.apellido AS doc_apellido,
                    a.docente_id,
                    (SELECT 1 FROM tareas_vistas tv
                     WHERE tv.tarea_id = t.id AND tv.usuario_id = ?) AS vista
             FROM tareas t
             JOIN asignaciones a ON t.asignacion_id = a.id
             JOIN materias m     ON a.materia_id = m.id
             JOIN usuarios u     ON a.docente_id = u.id
             WHERE a.grupo_id = ?
             ORDER BY t.fecha_publicacion DESC
             LIMIT 80'
        );
        $qt2->execute([$uid_padre, $gid]);
        $tareas = $qt2->fetchAll();

        // Conteo de tareas no leídas total y por materia (todas las tareas, notificación global)
        $total_no_leidas   = 0;
        $no_leidas_por_mat = []; // materia_id => count
        foreach ($tareas as $t) {
            if (!$t['vista']) {
                $total_no_leidas++;
                $no_leidas_por_mat[$t['materia_id']] = ($no_leidas_por_mat[$t['materia_id']] ?? 0) + 1;
            }
        }

        // Tareas a mostrar en el tab "Tareas", filtradas por el período seleccionado
        $tareas_mostradas = array_values(array_filter($tareas, function($t) use ($periodo_mostrado_id) {
            return (int)$t['periodo_id'] === (int)$periodo_mostrado_id;
        }));

        // Añadir badge de no leídas a aula_materias
        foreach ($aula_materias as &$am) {
            $am['no_leidas'] = $no_leidas_por_mat[$am['materia_id']] ?? 0;
        }
        unset($am);

        // ── Mensajes dirigidos al estudiante (cuenta aún no habilitada) ──
        // El padre puede leerlos y responder en su nombre mientras el
        // estudiante no tenga acceso propio a la plataforma. Se incluye también
        // el resto del hilo: las respuestas que el padre envió en su nombre y
        // las réplicas que el docente le haga de vuelta al padre, marcadas con
        // estudiante_relacionado_id para mantener la conversación completa.
        $mensajes_hijo = [];
        $mensajes_hijo_no_leidos = 0;
        $uid_estudiante = (int)($hijo['usuario_id'] ?? 0);
        $uid_padre_actual = Auth::id();
        if ($uid_estudiante) {
            $qm = $db->prepare(
                'SELECT m.id, m.asunto, m.cuerpo, m.created_at, m.remitente_id, m.hilo_id,
                        u.nombre AS rem_nombre, u.apellido AS rem_apellido, u.rol AS rem_rol,
                        (m.remitente_id = ?) AS es_mio,
                        CASE WHEN m.remitente_id = ? THEN 1
                             ELSE COALESCE(md_est.leido, md_me.leido, 1) END AS leido,
                        (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.mensaje_id = m.id) AS num_adjuntos
                 FROM mensajes m
                 JOIN usuarios u ON m.remitente_id = u.id
                 LEFT JOIN mensajes_destinatarios md_est
                        ON md_est.mensaje_id = m.id AND md_est.usuario_id = ?
                 LEFT JOIN mensajes_destinatarios md_me
                        ON md_me.mensaje_id = m.id AND md_me.usuario_id = ?
                 WHERE (md_est.usuario_id IS NOT NULL AND md_est.eliminado = 0)
                    OR m.estudiante_relacionado_id = ?
                 ORDER BY m.created_at ASC
                 LIMIT 100'
            );
            $qm->execute([$uid_padre_actual, $uid_padre_actual, $uid_estudiante, $uid_padre_actual, $estudianteId]);
            $mensajes_hijo = $qm->fetchAll();
            foreach ($mensajes_hijo as $m) {
                if (!$m['es_mio'] && !$m['leido']) $mensajes_hijo_no_leidos++;
            }
        }

        // ── Escala de desempeños y modo de visualización de notas ─
        // El padre puede ver las notas como valor numérico, texto del
        // desempeño (Superior/Alto/Básico/Bajo...) o abreviatura.
        $desempenios = $db->query('SELECT * FROM desempenios ORDER BY orden')->fetchAll();
        $modo_nota   = $_GET['modo'] ?? 'valor';
        if (!in_array($modo_nota, ['valor', 'texto', 'abrev'])) $modo_nota = 'valor';

        header('Cache-Control: no-store, no-cache, must-revalidate');
        render('padre/hijo', [
            'hijos'              => $hijos,
            'hijo'               => $hijo,
            'info'               => $info,
            'familiares'         => $familiares,
            'notas_tabla'        => $notas_tabla,
            'periodos_ids'       => $periodos_ids,
            'desempenios'        => $desempenios,
            'modo_nota'          => $modo_nota,
            'asistencia'         => $asistencia,
            'resumen_asist'      => $resumen_asist,
            'observaciones'      => $observaciones,
            'aula_materias'        => $aula_materias,
            'docente_por_materia'  => $docente_por_materia,
            'tareas'               => $tareas,
            'tareas_mostradas'     => $tareas_mostradas,
            'mensajes_hijo'             => $mensajes_hijo,
            'mensajes_hijo_no_leidos'   => $mensajes_hijo_no_leidos,
            'total_no_leidas'      => $total_no_leidas,
            'no_leidas_por_mat'    => $no_leidas_por_mat,
            'obs_no_leidas'        => $obs_no_leidas,
            'asist_alertas'        => $asist_alertas,
            'notas_alertas'        => $notas_alertas,
            'periodo_mostrado_id'  => $periodo_mostrado_id,
            'tab'                => $tab,
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function editar(int $estudianteId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { http_response_code(403); die('Acceso no autorizado'); }

        $db = Database::get();
        $q  = $db->prepare(
            'SELECT u.email, e.documento, e.tipo_documento, e.fecha_nacimiento,
                    e.genero, e.lugar_nacimiento, e.estrato, e.eps,
                    e.direccion, e.barrio, e.contacto_emergencia, e.tel_emergencia,
                    p.telefono, p.celular, p.ciudad
             FROM estudiantes e
             JOIN usuarios u ON e.usuario_id = u.id
             LEFT JOIN perfiles p ON p.usuario_id = u.id
             WHERE e.id = ? LIMIT 1'
        );
        $q->execute([$estudianteId]);
        $info = $q->fetch();

        render('padre/editar_hijo', [
            'hijos'              => $hijos,
            'hijo'               => $hijo,
            'info'               => $info,
            'tab'                => 'info',
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function guardarEdicion(int $estudianteId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { http_response_code(403); die('Acceso no autorizado'); }

        $db = Database::get();

        // Obtener usuario_id del estudiante
        $qu = $db->prepare('SELECT usuario_id FROM estudiantes WHERE id = ? LIMIT 1');
        $qu->execute([$estudianteId]);
        $uid_est = (int)$qu->fetchColumn();

        $p = $_POST;
        $trim = fn($k) => trim($p[$k] ?? '');

        // Actualizar tabla estudiantes
        $db->prepare(
            'UPDATE estudiantes SET
                documento           = ?,
                tipo_documento      = ?,
                fecha_nacimiento    = ?,
                genero              = ?,
                lugar_nacimiento    = ?,
                estrato             = ?,
                eps                 = ?,
                direccion           = ?,
                barrio              = ?,
                contacto_emergencia = ?,
                tel_emergencia      = ?
             WHERE id = ?'
        )->execute([
            $trim('documento'),
            in_array($trim('tipo_documento'), ['TI','CC','CE','PP']) ? $trim('tipo_documento') : 'TI',
            $trim('fecha_nacimiento') ?: null,
            in_array($trim('genero'), ['M','F','O']) ? $trim('genero') : null,
            $trim('lugar_nacimiento'),
            (int)($p['estrato'] ?? 0) ?: null,
            $trim('eps'),
            $trim('direccion'),
            $trim('barrio'),
            $trim('contacto_emergencia'),
            $trim('tel_emergencia'),
            $estudianteId,
        ]);

        // Upsert perfiles
        $db->prepare(
            'INSERT INTO perfiles (usuario_id, telefono, celular, ciudad)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE telefono=VALUES(telefono), celular=VALUES(celular), ciudad=VALUES(ciudad)'
        )->execute([
            $uid_est,
            $trim('telefono'),
            $trim('celular'),
            $trim('ciudad'),
        ]);

        flash('success', 'Información actualizada correctamente.');
        redirect('/mis-hijos/' . $estudianteId . '?tab=info');
    }

    public function verTarea(int $estudianteId, int $tareaId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { http_response_code(403); die('Acceso no autorizado'); }

        $db  = Database::get();
        $gid = (int)$hijo['grupo_id'];

        // Verificar que la tarea pertenece al grupo del hijo
        $qt = $db->prepare(
            'SELECT t.id, t.nombre, t.descripcion, t.tipo_entrega,
                    t.fecha_publicacion, t.fecha_limite,
                    m.nombre AS materia,
                    u.nombre AS doc_nombre, u.apellido AS doc_apellido
             FROM tareas t
             JOIN asignaciones a ON t.asignacion_id = a.id
             JOIN materias m     ON a.materia_id = m.id
             JOIN usuarios u     ON a.docente_id = u.id
             WHERE t.id = ? AND a.grupo_id = ?
             LIMIT 1'
        );
        $qt->execute([$tareaId, $gid]);
        $tarea = $qt->fetch();
        if (!$tarea) { http_response_code(404); die('Tarea no encontrada'); }

        // Marcar como vista por el padre
        $db->prepare(
            'INSERT IGNORE INTO tareas_vistas (tarea_id, usuario_id) VALUES (?, ?)'
        )->execute([$tareaId, Auth::id()]);

        render('padre/ver_tarea', [
            'hijos'              => $hijos,
            'hijo'               => $hijo,
            'tarea'              => $tarea,
            'tab'                => 'tareas',
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function marcarUnaObservacion(int $estudianteId, int $obsId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $ok = false;
        $mid = 0;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $ok = true; $mid = (int)$h['matricula_id']; break; }
        }
        if (!$ok) { http_response_code(403); echo json_encode(['ok'=>false]); return; }

        $db  = Database::get();
        $uid = Auth::id();

        // Verificar que la observación pertenece al estudiante
        $chk = $db->prepare('SELECT id FROM observaciones WHERE id = ? AND matricula_id = ? LIMIT 1');
        $chk->execute([$obsId, $mid]);
        if (!$chk->fetch()) { http_response_code(404); echo json_encode(['ok'=>false]); return; }

        $db->prepare(
            'INSERT IGNORE INTO observaciones_vistas (observacion_id, usuario_id) VALUES (?, ?)'
        )->execute([$obsId, $uid]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function marcarObservacionesVistas(int $estudianteId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { http_response_code(403); echo json_encode(['ok'=>false]); return; }

        $db  = Database::get();
        $uid = Auth::id();
        $mid = (int)$hijo['matricula_id'];

        $st = $db->prepare('SELECT id FROM observaciones WHERE matricula_id = ?');
        $st->execute([$mid]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);

        $ins = $db->prepare(
            'INSERT IGNORE INTO observaciones_vistas (observacion_id, usuario_id) VALUES (?, ?)'
        );
        foreach ($ids as $oid) {
            $ins->execute([$oid, $uid]);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function marcarNotaVista(int $estudianteId, int $materiaId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $ok = false;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $ok = true; break; }
        }
        if (!$ok) { http_response_code(403); echo json_encode(['ok'=>false]); return; }

        $uid  = Auth::id();
        $key  = 'nota_vista_' . $materiaId . '_' . $uid . '_' . $estudianteId;
        $_SESSION[$key] = date('Y-m-d H:i:s');

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function marcarUnaVista(int $estudianteId, int $tareaId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $ok = false;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $ok = true; break; }
        }
        if (!$ok) { http_response_code(403); echo json_encode(['ok'=>false]); return; }
        Database::get()->prepare(
            'INSERT IGNORE INTO tareas_vistas (tarea_id, usuario_id) VALUES (?, ?)'
        )->execute([$tareaId, Auth::id()]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function marcarTareasVistas(int $estudianteId): void {
        Auth::requireRole('padre');
        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { http_response_code(403); echo json_encode(['ok'=>false]); return; }

        $db  = Database::get();
        $uid = Auth::id();
        $gid = (int)$hijo['grupo_id'];

        // IDs de todas las tareas del grupo
        $st = $db->prepare(
            'SELECT t.id FROM tareas t
             JOIN asignaciones a ON t.asignacion_id = a.id
             WHERE a.grupo_id = ?'
        );
        $st->execute([$gid]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);

        $ins = $db->prepare(
            'INSERT IGNORE INTO tareas_vistas (tarea_id, usuario_id) VALUES (?, ?)'
        );
        foreach ($ids as $tid) {
            $ins->execute([$tid, $uid]);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    // ── Mensajes del estudiante (cuenta aún no habilitada) ────────

    // "X" → "Re: X" → "Re2: X" → "Re3: X" … (mismo criterio que ComunicacionesController)
    private function siguienteRe(string $asunto): string {
        $asunto = trim($asunto);
        if (preg_match('/^Re\s*(\d*)\s*:\s*(.*)$/i', $asunto, $m)) {
            $n = ($m[1] === '') ? 2 : ((int)$m[1] + 1);
            return 'Re' . $n . ': ' . $m[2];
        }
        return 'Re: ' . $asunto;
    }

    /**
     * Valida que el mensaje pertenezca a la conversación del estudiante indicado
     * (ya sea dirigido directamente a él, o parte del hilo marcado con
     * estudiante_relacionado_id — respuestas del padre o del docente de vuelta
     * al padre). Devuelve [hijo, mensaje] o null.
     */
    private function mensajeDelHijo(int $estudianteId, int $mensajeId): ?array {
        $hijos = $this->hijosDelPadre();
        $hijo  = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo || empty($hijo['usuario_id'])) return null;

        $db = Database::get();
        $qm = $db->prepare(
            'SELECT m.id, m.asunto, m.remitente_id, m.cuerpo, m.created_at, m.hilo_id,
                    u.nombre AS rem_nombre, u.apellido AS rem_apellido
             FROM mensajes m
             JOIN usuarios u ON u.id = m.remitente_id
             LEFT JOIN mensajes_destinatarios md
                    ON md.mensaje_id = m.id AND md.usuario_id IN (?, ?)
             WHERE m.id = ?
               AND (md.usuario_id IS NOT NULL OR m.estudiante_relacionado_id = ?)
             LIMIT 1'
        );
        $qm->execute([(int)$hijo['usuario_id'], Auth::id(), $mensajeId, $estudianteId]);
        $mensaje = $qm->fetch();
        if (!$mensaje) return null;

        return ['hijo' => $hijo, 'mensaje' => $mensaje];
    }

    public function marcarMensajeVisto(int $estudianteId, int $mensajeId): void {
        Auth::requireRole('padre');
        header('Content-Type: application/json');

        $ctx = $this->mensajeDelHijo($estudianteId, $mensajeId);
        if (!$ctx) { http_response_code(403); echo json_encode(['ok' => false]); return; }

        // El destinatario real puede ser el estudiante (mensaje original del docente)
        // o el propio padre (réplica del docente a la respuesta del padre).
        Database::get()->prepare(
            'UPDATE mensajes_destinatarios SET leido = 1, leido_at = NOW()
             WHERE mensaje_id = ? AND usuario_id IN (?, ?)'
        )->execute([$mensajeId, (int)$ctx['hijo']['usuario_id'], Auth::id()]);

        echo json_encode(['ok' => true]);
    }

    public function responderMensajeHijo(int $estudianteId, int $mensajeId): void {
        Auth::requireRole('padre');
        csrfVerify();

        $ctx = $this->mensajeDelHijo($estudianteId, $mensajeId);
        if (!$ctx) { http_response_code(403); die('Acceso no autorizado'); }
        $hijo = $ctx['hijo'];

        $cuerpo = trim($_POST['cuerpo'] ?? '');
        if (!$cuerpo) {
            flashError('Escribe un mensaje antes de enviarlo.');
            redirect('/mis-hijos/' . $estudianteId . '?tab=mensajes');
            return;
        }

        $db = Database::get();

        // Marcar el mensaje original como leído al responder (puede estar dirigido
        // al estudiante o al propio padre, según el punto del hilo)
        $db->prepare(
            'UPDATE mensajes_destinatarios SET leido = 1, leido_at = NOW()
             WHERE mensaje_id = ? AND usuario_id IN (?, ?)'
        )->execute([$mensajeId, (int)$hijo['usuario_id'], Auth::id()]);

        // $cuerpo es texto plano (textarea), no HTML: se escapa y se convierten
        // los saltos de línea en <br> para que se vea igual que el resto de mensajes.
        $nombreHijo = $hijo['nombre'] . ' ' . $hijo['apellido'];
        $nuevoTexto = '<p><em>Respuesta enviada por el acudiente en nombre de ' . e($nombreHijo) . ':</em></p>'
                    . '<p>' . nl2br(e($cuerpo)) . '</p>';

        // Citar el mensaje original (mismo patrón que el resto de la app), para que
        // el docente vea el historial de la conversación al abrir la respuesta.
        $fechaOrig = date('d/m/Y H:i', strtotime($ctx['mensaje']['created_at']));
        $autorOrig = e($ctx['mensaje']['rem_nombre'] . ' ' . $ctx['mensaje']['rem_apellido']);
        $cita = '<blockquote class="msg-quote">'
              . '<p class="msg-quote-head"><em>El ' . $fechaOrig . ', ' . $autorOrig . ' escribió:</em></p>'
              . safeHtml($ctx['mensaje']['cuerpo'])
              . '</blockquote>';

        $cuerpoFinal = $nuevoTexto . '<div class="cita-historial">' . $cita . '</div>';
        $asunto = $this->siguienteRe($ctx['mensaje']['asunto']);

        // Propagar el hilo real del mensaje que se está respondiendo (en vez de
        // inferirlo luego por el texto del asunto).
        $hiloId = (int)($ctx['mensaje']['hilo_id'] ?: $ctx['mensaje']['id']);

        $db->beginTransaction();
        try {
            $db->prepare(
                'INSERT INTO mensajes (remitente_id, asunto, cuerpo, estudiante_relacionado_id, hilo_id) VALUES (?, ?, ?, ?, ?)'
            )->execute([Auth::id(), $asunto, $cuerpoFinal, $estudianteId, $hiloId]);

            $msgId = (int)$db->lastInsertId();

            $db->prepare(
                'INSERT IGNORE INTO mensajes_destinatarios (mensaje_id, usuario_id) VALUES (?, ?)'
            )->execute([$msgId, (int)$ctx['mensaje']['remitente_id']]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            flashError('No se pudo enviar la respuesta. Intenta de nuevo.');
            redirect('/mis-hijos/' . $estudianteId . '?tab=mensajes');
            return;
        }

        flashSuccess('Respuesta enviada correctamente.');
        redirect('/mis-hijos/' . $estudianteId . '?tab=mensajes');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function hijosDelPadre(): array {
        $db  = Database::get();
        $uid = Auth::id();
        $st  = $db->prepare(
            'SELECT u.nombre, u.apellido, e.id AS estudiante_id, u.id AS usuario_id,
                    gr.nombre AS grado, g.nombre AS grupo, g.id AS grupo_id,
                    mt.id AS matricula_id, mt.estado AS estado_matricula
             FROM acudientes ac
             JOIN estudiantes e ON ac.estudiante_id = e.id
             JOIN usuarios u    ON e.usuario_id = u.id
             JOIN matriculas mt ON mt.estudiante_id = e.id
             JOIN grupos g      ON mt.grupo_id = g.id
             JOIN grados gr     ON g.grado_id = gr.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE ac.usuario_id = ? AND al.activo = 1
             ORDER BY gr.nivel, u.apellido'
        );
        $st->execute([$uid]);
        return $st->fetchAll();
    }
}
