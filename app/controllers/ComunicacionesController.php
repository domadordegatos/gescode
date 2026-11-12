<?php
class ComunicacionesController {
    private PDO $db;

    private const ALLOWED_MIME = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'text/plain', 'application/zip',
    ];

    public function __construct() {
        $this->db = Database::get();
    }

    // Quita prefijos "Re:", "Re2:", "RE:"... (recursivo) para detectar el mismo hilo
    private function normalizarAsunto(string $s): string {
        $s = trim($s);
        while (preg_match('/^re\d*\s*:\s*/i', $s, $mm)) {
            $s = trim(substr($s, strlen($mm[0])));
        }
        return $s;
    }

    /**
     * Agrupa una lista de mensajes (ya ordenada por created_at DESC) en hilos de
     * conversación según su hilo_id real (columna propagada en cada respuesta),
     * devolviendo un mensaje "resumen" por hilo (el más reciente) con
     * 'num_mensajes' y 'no_leidos_hilo' agregados. Si algún mensaje antiguo no
     * tiene hilo_id (dato previo a esta columna), se usa su propio id como
     * respaldo para no romper el agrupamiento.
     */
    private function agruparPorHilo(array $mensajes): array {
        $hilos = [];
        foreach ($mensajes as $m) {
            $clave = (int)($m['hilo_id'] ?? $m['id']);
            if (!isset($hilos[$clave])) {
                // Como $mensajes viene ordenado DESC, el primero que aparece
                // con esta clave es el más reciente del hilo.
                $hilos[$clave] = $m;
                $hilos[$clave]['asunto']         = $this->normalizarAsunto($m['asunto']);
                $hilos[$clave]['num_mensajes']   = 0;
                $hilos[$clave]['no_leidos_hilo'] = 0;
            }
            $hilos[$clave]['num_mensajes']++;
            if (!$m['leido']) $hilos[$clave]['no_leidos_hilo']++;
        }
        return array_values($hilos);
    }

    // ── BANDEJA ──────────────────────────────────────────────────
    public function bandeja(): void {
        Auth::require();
        $uid    = Auth::id();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $offset = ($pagina - 1) * 20;

        $q          = trim($_GET['q']          ?? '');
        $de         = trim($_GET['de']         ?? '');
        $asunto     = trim($_GET['asunto']     ?? '');
        $fecha_desde= trim($_GET['fecha_desde']?? '');
        $fecha_hasta= trim($_GET['fecha_hasta']?? '');
        $estado     = trim($_GET['estado']     ?? ''); // '' | 'leidos' | 'no_leidos'

        $where  = 'md.usuario_id = ? AND md.eliminado = 0 AND md.archivado = 0';
        $params = [$uid];

        if ($q)          { $where .= ' AND (m.asunto LIKE ? OR m.cuerpo LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        if ($de)         { $where .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ?)'; $params[] = "%$de%"; $params[] = "%$de%"; }
        if ($asunto)     { $where .= ' AND m.asunto LIKE ?'; $params[] = "%$asunto%"; }
        if ($fecha_desde){ $where .= ' AND DATE(m.created_at) >= ?'; $params[] = $fecha_desde; }
        if ($fecha_hasta){ $where .= ' AND DATE(m.created_at) <= ?'; $params[] = $fecha_hasta; }
        if ($estado === 'leidos')    { $where .= ' AND md.leido = 1'; }
        elseif ($estado === 'no_leidos') { $where .= ' AND md.leido = 0'; }

        $sql = "SELECT m.id, m.asunto, m.created_at, m.remitente_id, m.cuerpo, m.hilo_id,
                       u.nombre, u.apellido, u.foto, u.rol, md.leido,
                       (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.mensaje_id = m.id) AS num_adjuntos
                FROM mensajes_destinatarios md
                JOIN mensajes m ON md.mensaje_id = m.id
                JOIN usuarios u ON m.remitente_id = u.id
                WHERE $where ORDER BY m.created_at DESC LIMIT 20 OFFSET ?";
        $params[] = $offset;
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $mensajes = $this->agruparPorHilo($st->fetchAll());

        $countParams = array_slice($params, 0, -1); // sin offset
        $stCount = $this->db->prepare("SELECT COUNT(*) FROM mensajes_destinatarios md JOIN mensajes m ON md.mensaje_id=m.id JOIN usuarios u ON m.remitente_id=u.id WHERE $where");
        $stCount->execute($countParams);
        $total = (int)$stCount->fetchColumn();

        $filtros = compact('q','de','asunto','fecha_desde','fecha_hasta','estado');

        if (!empty($_GET['ajax'])) {
            header('Content-Type: text/html; charset=utf-8');
            extract(compact('mensajes'));
            include ROOT . '/app/views/comunicaciones/_bandeja_lista.php';
            exit;
        }

        render('comunicaciones/bandeja', compact('mensajes','pagina','total','filtros'));
    }

    // ── VER ──────────────────────────────────────────────────────
    public function ver(int $id): void {
        Auth::require();
        $uid = Auth::id();

        $msg = $this->db->prepare(
            'SELECT m.*, u.nombre, u.apellido, u.rol, u.foto
             FROM mensajes m JOIN usuarios u ON m.remitente_id = u.id
             WHERE m.id = ?'
        );
        $msg->execute([$id]);
        $clicado = $msg->fetch();
        if (!$clicado) { http_response_code(404); echo '<h1>404 — Mensaje no encontrado</h1>'; return; }

        // Acceso a este mensaje puntual (igual que antes)
        $dest = $this->db->prepare('SELECT leido FROM mensajes_destinatarios WHERE mensaje_id = ? AND usuario_id = ? LIMIT 1');
        $dest->execute([$id, $uid]);
        if (!$dest->fetch() && (int)$clicado['remitente_id'] !== $uid) {
            http_response_code(403);
            echo '<h1>403 — Sin acceso</h1>';
            return;
        }

        // ── Reconstruir TODO el hilo (conversación completa), no solo el mensaje clicado ──
        $hiloId = (int)($clicado['hilo_id'] ?? $clicado['id']);
        $th = $this->db->prepare(
            'SELECT m.*, u.nombre, u.apellido, u.rol, u.foto
             FROM mensajes m JOIN usuarios u ON m.remitente_id = u.id
             WHERE m.hilo_id = ? OR (m.hilo_id IS NULL AND m.id = ?)
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $th->execute([$hiloId, $hiloId]);
        $todosHilo = $th->fetchAll();

        $idsHilo = array_column($todosHilo, 'id');
        // Destinatarios de cada mensaje del hilo, en una sola consulta
        $destPorMensaje = [];
        if ($idsHilo) {
            $in = implode(',', array_fill(0, count($idsHilo), '?'));
            $dAll = $this->db->prepare(
                "SELECT md.mensaje_id, u.id, u.nombre, u.apellido, u.rol, md.leido, md.leido_at
                 FROM mensajes_destinatarios md JOIN usuarios u ON u.id = md.usuario_id
                 WHERE md.mensaje_id IN ($in) AND md.eliminado = 0
                 ORDER BY u.apellido, u.nombre"
            );
            $dAll->execute($idsHilo);
            foreach ($dAll->fetchAll() as $row) {
                $destPorMensaje[$row['mensaje_id']][] = $row;
            }
        }

        $esAdminVista = in_array(Auth::rol(), ['admin', 'soporte']);
        $mensajesHilo = [];
        foreach ($todosHilo as $m) {
            $destinatarios = $destPorMensaje[$m['id']] ?? [];
            $esRemitenteM  = (int)$m['remitente_id'] === $uid;
            $esDestM       = (bool) array_filter($destinatarios, fn($d) => (int)$d['id'] === $uid);

            // Solo se incluyen en la conversación los mensajes a los que el usuario tiene acceso
            // (fue remitente, destinatario, o la visibilidad del mensaje lo permite).
            if (!$esRemitenteM && !$esDestM) continue;

            $puedeVerTodos = $esRemitenteM || $m['visibilidad'] === 'normal'
                || ($m['visibilidad'] === 'solo_admin' && $esAdminVista);

            // Marcar como leído al visualizar toda la conversación
            if ($esDestM) {
                $leidoActual = array_values(array_filter($destinatarios, fn($d) => (int)$d['id'] === $uid))[0]['leido'] ?? 0;
                if (!$leidoActual) {
                    $this->db->prepare('UPDATE mensajes_destinatarios SET leido=1, leido_at=NOW() WHERE mensaje_id=? AND usuario_id=?')
                              ->execute([$m['id'], $uid]);
                }
            }

            $adj_st = $this->db->prepare('SELECT id, nombre_orig, mime, tamano FROM mensajes_adjuntos WHERE mensaje_id=?');
            $adj_st->execute([$m['id']]);

            $mensajesHilo[] = $m + [
                'es_propio'          => $esRemitenteM,
                'puede_ver_todos'    => $puedeVerTodos,
                'destinatarios_vista'=> $puedeVerTodos ? $destinatarios : [],
                'total_destinatarios'=> count($destinatarios),
                'adjuntos'           => $adj_st->fetchAll(),
            ];
        }

        if (!$mensajesHilo) { http_response_code(403); echo '<h1>403 — Sin acceso</h1>'; return; }

        $mensaje = end($mensajesHilo); // el más reciente visible, para "Responder"
        reset($mensajesHilo);

        render('comunicaciones/ver', compact('mensaje', 'mensajesHilo', 'hiloId'));
    }

    // ── INFO DE UN PARTICIPANTE (panel lateral) ───────────────────
    public function destinatarioInfo(int $mensajeId, int $usuarioId): void {
        Auth::require();
        header('Content-Type: application/json');
        $uid = Auth::id();

        // Verificar que quien pide la info tiene acceso a este mensaje
        $msg = $this->db->prepare('SELECT remitente_id FROM mensajes WHERE id = ?');
        $msg->execute([$mensajeId]);
        $m = $msg->fetch();
        if (!$m) { http_response_code(404); echo json_encode(['error' => 'No encontrado']); return; }

        $esRemitente = (int)$m['remitente_id'] === $uid;
        $dest = $this->db->prepare('SELECT 1 FROM mensajes_destinatarios WHERE mensaje_id = ? AND usuario_id = ? LIMIT 1');
        $dest->execute([$mensajeId, $uid]);
        if (!$esRemitente && !$dest->fetch()) { http_response_code(403); echo json_encode(['error' => 'Sin acceso']); return; }

        // Verificar que el usuario consultado realmente participa en este mensaje
        $participa = ((int)$m['remitente_id'] === $usuarioId);
        if (!$participa) {
            $chk = $this->db->prepare('SELECT 1 FROM mensajes_destinatarios WHERE mensaje_id = ? AND usuario_id = ? LIMIT 1');
            $chk->execute([$mensajeId, $usuarioId]);
            $participa = (bool) $chk->fetch();
        }
        if (!$participa) { http_response_code(403); echo json_encode(['error' => 'Ese usuario no participa en este mensaje']); return; }

        $u = $this->db->prepare('SELECT id, nombre, apellido, rol, cargo, foto FROM usuarios WHERE id = ?');
        $u->execute([$usuarioId]);
        $usuario = $u->fetch();
        if (!$usuario) { http_response_code(404); echo json_encode(['error' => 'Usuario no encontrado']); return; }

        $data = [
            'id' => (int)$usuario['id'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'rol' => $usuario['rol'],
            'cargo' => $usuario['cargo'],
        ];

        if ($usuario['rol'] === 'padre') {
            $hijos = $this->db->prepare(
                "SELECT u2.nombre, u2.apellido, ac.parentesco,
                        gr.nombre AS grado, g.nombre AS grupo
                 FROM acudientes ac
                 JOIN estudiantes e ON e.id = ac.estudiante_id
                 JOIN usuarios u2 ON u2.id = e.usuario_id
                 LEFT JOIN matriculas mt ON mt.estudiante_id = e.id AND mt.estado = 'activo'
                 LEFT JOIN grupos g ON g.id = mt.grupo_id
                 LEFT JOIN grados gr ON gr.id = g.grado_id
                 WHERE ac.usuario_id = ?
                 ORDER BY u2.nombre"
            );
            $hijos->execute([$usuarioId]);
            $data['hijos'] = $hijos->fetchAll();
        } elseif ($usuario['rol'] === 'estudiante') {
            $g = $this->db->prepare(
                "SELECT gr.nombre AS grado, g.nombre AS grupo
                 FROM estudiantes e
                 JOIN usuarios u2 ON u2.id = e.usuario_id
                 LEFT JOIN matriculas mt ON mt.estudiante_id = e.id AND mt.estado = 'activo'
                 LEFT JOIN grupos g ON g.id = mt.grupo_id
                 LEFT JOIN grados gr ON gr.id = g.grado_id
                 WHERE u2.id = ? LIMIT 1"
            );
            $g->execute([$usuarioId]);
            $data['grado'] = $g->fetch() ?: null;
        } elseif ($usuario['rol'] === 'docente') {
            $gr = $this->db->prepare(
                "SELECT gr.nombre AS grado, g.nombre AS grupo
                 FROM grupos g JOIN grados gr ON gr.id = g.grado_id
                 JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
                 WHERE g.director_id = ? AND al.activo = 1"
            );
            $gr->execute([$usuarioId]);
            $data['grupos_dirige'] = $gr->fetchAll();
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    // ── REDACTAR ─────────────────────────────────────────────────
    public function redactar(): void {
        Auth::require();
        $uid    = Auth::id();
        $rol    = Auth::rol();

        // Padres y estudiantes: solo pueden escribir a los docentes que les corresponden
        if (in_array($rol, ['padre', 'estudiante'])) {
            $this->redactarRestringido($uid, $rol);
            return;
        }

        $anoSub = '(SELECT id FROM anos_lectivos WHERE activo=1 LIMIT 1)';

        // Estudiantes con sus acudientes, agrupados por grado
        $st = $this->db->prepare("
            SELECT
                u.id  AS est_id,  u.nombre  AS est_nombre,  u.apellido  AS est_apellido,
                g.nombre AS grado, g.nivel,
                gr.director_id,
                pu.id AS pad_id, pu.nombre AS pad_nombre, pu.apellido AS pad_apellido,
                a.parentesco, a.principal
            FROM usuarios u
            JOIN estudiantes e   ON e.usuario_id  = u.id
            JOIN matriculas mat  ON mat.estudiante_id = e.id AND mat.estado = 'activo'
            JOIN grupos gr       ON gr.id = mat.grupo_id AND gr.ano_lectivo_id = $anoSub
            JOIN grados g        ON g.id  = gr.grado_id
            LEFT JOIN acudientes a ON a.estudiante_id = e.id
            LEFT JOIN usuarios pu  ON pu.id = a.usuario_id AND pu.activo = 1 AND pu.id != ?
            WHERE u.activo = 1 AND u.id != ?
            ORDER BY g.nivel, u.apellido, u.nombre, a.principal DESC
        ");
        $st->execute([$uid, $uid]);
        $rows = $st->fetchAll();

        // Construir mapa de grados → estudiantes → padres
        $gradesMap = [];
        $directorIds = [];

        foreach ($rows as $r) {
            $gk = $r['grado'];
            if (!isset($gradesMap[$gk])) {
                $gradesMap[$gk] = [
                    'grado'       => $r['grado'],
                    'nivel'       => (int)$r['nivel'],
                    'director_id' => $r['director_id'] ? (int)$r['director_id'] : null,
                    'director'    => null,
                    'estudiantes' => [],
                ];
                if ($r['director_id']) {
                    $directorIds[(int)$r['director_id']] = $gk;
                }
            }
            $ek = (int)$r['est_id'];
            if (!isset($gradesMap[$gk]['estudiantes'][$ek])) {
                $gradesMap[$gk]['estudiantes'][$ek] = [
                    'id'       => $ek,
                    'nombre'   => $r['est_nombre'],
                    'apellido' => $r['est_apellido'],
                    'padres'   => [],
                ];
            }
            if ($r['pad_id']) {
                $padId = (int)$r['pad_id'];
                $already = false;
                foreach ($gradesMap[$gk]['estudiantes'][$ek]['padres'] as $p) {
                    if ($p['id'] === $padId) { $already = true; break; }
                }
                if (!$already) {
                    $gradesMap[$gk]['estudiantes'][$ek]['padres'][] = [
                        'id'         => $padId,
                        'nombre'     => $r['pad_nombre'],
                        'apellido'   => $r['pad_apellido'],
                        'parentesco' => $r['parentesco'] ?? '',
                    ];
                }
            }
        }

        // Obtener datos de directores
        if ($directorIds) {
            $inList = implode(',', array_map('intval', array_keys($directorIds)));
            $dRows  = $this->db->query(
                "SELECT id, nombre, apellido FROM usuarios WHERE id IN ($inList) AND activo=1"
            )->fetchAll();
            foreach ($dRows as $d) {
                $gk = $directorIds[(int)$d['id']];
                $gradesMap[$gk]['director'] = [
                    'id'       => (int)$d['id'],
                    'nombre'   => $d['nombre'],
                    'apellido' => $d['apellido'],
                ];
            }
        }

        // Convertir a array indexado y ordenar por nivel
        $grades = array_values($gradesMap);
        usort($grades, fn($a, $b) => $a['nivel'] - $b['nivel']);
        foreach ($grades as &$g) {
            $g['estudiantes'] = array_values($g['estudiantes']);
            unset($g['director_id']);
        }
        unset($g);

        // Docentes, admin, soporte (sin grado)
        $st2 = $this->db->prepare("
            SELECT id, nombre, apellido, rol
            FROM usuarios
            WHERE activo=1 AND id != ? AND rol IN ('docente','admin','soporte')
            ORDER BY rol, apellido, nombre
        ");
        $st2->execute([$uid]);
        $otros = array_map(fn($r) => [
            'id'       => (int)$r['id'],
            'nombre'   => $r['nombre'],
            'apellido' => $r['apellido'],
            'rol'      => $r['rol'],
        ], $st2->fetchAll());

        $rd = $this->datosRespuesta();

        // Pre-selección desde lista de estudiantes (?est=estudiante_id)
        $presel = $this->preselDesdeEstudiante();

        $permiteMsjOcultos = (bool)$this->db->query(
            'SELECT permite_mensajes_ocultos FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();

        render('comunicaciones/redactar', [
            'contactos_json' => json_encode(
                ['grades' => $grades, 'otros' => $otros],
                JSON_UNESCAPED_UNICODE
            ),
            'permite_mensajes_ocultos' => $permiteMsjOcultos,
            'reply_id'    => $rd['reply_id'],
            'reply_asunto'=> $rd['reply_asunto'],
            'reply_cuerpo'=> $rd['reply_cuerpo'],
            'reply_lock'  => $rd['reply_lock'],
            'reply_dest'  => $rd['reply_dest'],
            'restringido' => false,
            'presel'      => $presel,
            'estudiante_relacionado_id' => $rd['estudiante_relacionado_id'] ?? null,
            'hilo_id' => $rd['hilo_id'] ?? null,
        ]);
    }

    // Pre-selección de destinatarios desde ?est=id o ?est[]=id1&est[]=id2
    private function preselDesdeEstudiante(): array {
        // Soporta un solo est= o múltiples est[]=
        $raw = $_GET['est'] ?? null;
        if (is_array($raw)) {
            $est_ids = array_map('intval', $raw);
        } elseif ($raw) {
            $est_ids = [(int)$raw];
        } else {
            $est_ids = [];
        }
        if (empty($est_ids)) return [];

        $db  = $this->db;
        $ids = [];
        $seen = [];

        foreach ($est_ids as $est_id) {
            if (!$est_id) continue;

            // Usuario del estudiante
            $st = $db->prepare(
                'SELECT e.usuario_id, u.nombre, u.apellido
                 FROM estudiantes e JOIN usuarios u ON e.usuario_id=u.id
                 WHERE e.id=? AND u.activo=1 LIMIT 1'
            );
            $st->execute([$est_id]);
            $row = $st->fetch();
            if ($row && !in_array($row['usuario_id'], $seen)) {
                $seen[] = $row['usuario_id'];
                $ids[]  = ['id'    => (int)$row['usuario_id'],
                           'label' => e($row['nombre'].' '.$row['apellido']).' (Estudiante)'];
            }

            // Padres/acudientes
            $st2 = $db->prepare(
                'SELECT a.usuario_id, u.nombre, u.apellido, a.parentesco
                 FROM acudientes a JOIN usuarios u ON a.usuario_id=u.id
                 WHERE a.estudiante_id=? AND u.activo=1'
            );
            $st2->execute([$est_id]);
            foreach ($st2->fetchAll() as $p) {
                if (!in_array($p['usuario_id'], $seen)) {
                    $seen[] = $p['usuario_id'];
                    $ids[]  = ['id'    => (int)$p['usuario_id'],
                               'label' => e($p['nombre'].' '.$p['apellido']).' ('.ucfirst($p['parentesco'] ?: 'Familiar').')'];
                }
            }
        }

        return $ids;
    }

    // Datos de respuesta (asunto RE incremental + cita del historial)
    private function datosRespuesta(): array {
        if (isset($_GET['responder'])) {
            $r = $this->prepararRespuesta((int)$_GET['responder']);
            if ($r) return $r;
        }
        return [
            'reply_id'     => isset($_GET['reply']) ? (int)$_GET['reply'] : null,
            'reply_asunto' => $_GET['asunto'] ?? '',
            'reply_cuerpo' => '',
            'reply_lock'   => false,
            'reply_dest'   => null,
            'estudiante_relacionado_id' => null,
            'hilo_id' => null,
        ];
    }

    private function prepararRespuesta(int $mensaje_id): ?array {
        $uid = Auth::id();
        $st  = $this->db->prepare(
            'SELECT m.*, u.nombre, u.apellido
             FROM mensajes m JOIN usuarios u ON m.remitente_id=u.id
             WHERE m.id=? LIMIT 1'
        );
        $st->execute([$mensaje_id]);
        $msg = $st->fetch();
        if (!$msg) return null;

        // Verificar acceso: destinatario o remitente
        $acc = $this->db->prepare(
            'SELECT 1 FROM mensajes_destinatarios WHERE mensaje_id=? AND usuario_id=? LIMIT 1'
        );
        $acc->execute([$mensaje_id, $uid]);
        if (!$acc->fetch() && (int)$msg['remitente_id'] !== $uid) return null;

        $fecha = date('d/m/Y H:i', strtotime($msg['created_at']));
        $autor = e($msg['nombre'] . ' ' . $msg['apellido']);
        $cita  = '<blockquote class="msg-quote">'
               . '<p class="msg-quote-head"><em>El ' . $fecha . ', ' . $autor . ' escribió:</em></p>'
               . safeHtml($msg['cuerpo'])
               . '</blockquote>';

        return [
            'reply_id'     => (int)$msg['remitente_id'],
            'reply_asunto' => $this->siguienteRe($msg['asunto']),
            'reply_cuerpo' => $cita,
            'reply_lock'   => true,
            'reply_dest'   => [
                'id'       => (int)$msg['remitente_id'],
                'nombre'   => $msg['nombre'],
                'apellido' => $msg['apellido'],
            ],
            // Si el mensaje original era parte de la conversación sobre un estudiante
            // (p.ej. respondido por un acudiente en su nombre), propagar la marca para
            // que la respuesta también aparezca en esa conversación.
            'estudiante_relacionado_id' => $msg['estudiante_relacionado_id'] !== null
                ? (int)$msg['estudiante_relacionado_id'] : null,
            // Propagar el hilo real (en vez de inferirlo por el texto del asunto).
            'hilo_id' => (int)($msg['hilo_id'] ?? $msg['id']),
        ];
    }

    // "X" → "Re: X" → "Re2: X" → "Re3: X" …
    private function siguienteRe(string $asunto): string {
        $asunto = trim($asunto);
        if (preg_match('/^Re\s*(\d*)\s*:\s*(.*)$/i', $asunto, $m)) {
            $n = ($m[1] === '') ? 2 : ((int)$m[1] + 1);
            return 'Re' . $n . ': ' . $m[2];
        }
        return 'Re: ' . $asunto;
    }

    // ── REDACTAR para padres/estudiantes (solo docentes relevantes) ──
    private function redactarRestringido(int $uid, string $rol): void {
        if ($rol === 'padre') {
            $sql = "SELECT DISTINCT doc.id, doc.nombre, doc.apellido,
                           mat.nombre AS materia, gr.nombre AS grado, g.nombre AS grupo, gr.nivel
                    FROM acudientes ac
                    JOIN estudiantes e   ON ac.estudiante_id = e.id
                    JOIN matriculas mt   ON mt.estudiante_id = e.id AND mt.estado='activo'
                    JOIN asignaciones a  ON a.grupo_id = mt.grupo_id
                    JOIN usuarios doc    ON a.docente_id = doc.id AND doc.activo=1
                    JOIN materias mat    ON a.materia_id = mat.id
                    JOIN grupos g        ON mt.grupo_id = g.id
                    JOIN grados gr       ON g.grado_id = gr.id
                    WHERE ac.usuario_id = ?
                    ORDER BY gr.nivel, doc.apellido, doc.nombre";
        } else { // estudiante
            $sql = "SELECT DISTINCT doc.id, doc.nombre, doc.apellido,
                           mat.nombre AS materia, gr.nombre AS grado, g.nombre AS grupo, gr.nivel
                    FROM estudiantes e
                    JOIN matriculas mt   ON mt.estudiante_id = e.id AND mt.estado='activo'
                    JOIN asignaciones a  ON a.grupo_id = mt.grupo_id
                    JOIN usuarios doc    ON a.docente_id = doc.id AND doc.activo=1
                    JOIN materias mat    ON a.materia_id = mat.id
                    JOIN grupos g        ON mt.grupo_id = g.id
                    JOIN grados gr       ON g.grado_id = gr.id
                    WHERE e.usuario_id = ?
                    ORDER BY doc.apellido, doc.nombre";
        }
        $st = $this->db->prepare($sql);
        $st->execute([$uid]);
        $rows = $st->fetchAll();

        // Agrupar docentes únicos con la lista de materias que dictan a sus hijos
        $map = [];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            if (!isset($map[$id])) {
                $map[$id] = [
                    'id'       => $id,
                    'nombre'   => $r['nombre'],
                    'apellido' => $r['apellido'],
                    'rol'      => 'docente',
                    'materias' => [],
                ];
            }
            $label = $r['materia'] . ' · ' . $r['grado'] . ' ' . $r['grupo'];
            if (!in_array($label, $map[$id]['materias'])) {
                $map[$id]['materias'][] = $label;
            }
        }
        $otros = array_values($map);

        $rd = $this->datosRespuesta();

        render('comunicaciones/redactar', [
            'contactos_json' => json_encode(['grades' => [], 'otros' => $otros], JSON_UNESCAPED_UNICODE),
            'reply_id'    => $rd['reply_id'],
            'reply_asunto'=> $rd['reply_asunto'],
            'reply_cuerpo'=> $rd['reply_cuerpo'],
            'reply_lock'  => $rd['reply_lock'],
            'reply_dest'  => $rd['reply_dest'],
            'restringido' => true,
            'presel'      => [],
            'estudiante_relacionado_id' => $rd['estudiante_relacionado_id'] ?? null,
            'hilo_id' => $rd['hilo_id'] ?? null,
        ]);
    }

    // IDs de usuarios a los que un padre/estudiante puede escribir (validación servidor)
    private function destinatariosPermitidos(int $uid, string $rol): array {
        if ($rol === 'padre') {
            $sql = "SELECT DISTINCT a.docente_id
                    FROM acudientes ac
                    JOIN estudiantes e  ON ac.estudiante_id = e.id
                    JOIN matriculas mt  ON mt.estudiante_id = e.id AND mt.estado='activo'
                    JOIN asignaciones a ON a.grupo_id = mt.grupo_id
                    WHERE ac.usuario_id = ?";
        } else {
            $sql = "SELECT DISTINCT a.docente_id
                    FROM estudiantes e
                    JOIN matriculas mt  ON mt.estudiante_id = e.id AND mt.estado='activo'
                    JOIN asignaciones a ON a.grupo_id = mt.grupo_id
                    WHERE e.usuario_id = ?";
        }
        $st = $this->db->prepare($sql);
        $st->execute([$uid]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    // ── ENVIAR ───────────────────────────────────────────────────
    public function enviar(): void {
        Auth::require();
        csrfVerify();

        $asunto        = trim($_POST['asunto'] ?? '');
        $cuerpo        = trim($_POST['cuerpo'] ?? '');
        $destinatarios = array_unique(array_map('intval', (array)($_POST['destinatarios'] ?? [])));

        $cuerpo = safeHtml($cuerpo);

        $visibilidad = $_POST['visibilidad'] ?? 'normal';
        if (!in_array($visibilidad, ['normal', 'oculto', 'solo_admin'])) $visibilidad = 'normal';
        // No confiar en el selector del cliente: si el colegio no tiene la función
        // habilitada, forzar visibilidad normal sin importar lo que llegue en el POST.
        if ($visibilidad !== 'normal') {
            $permiteOcultos = (bool)$this->db->query(
                'SELECT permite_mensajes_ocultos FROM colegios WHERE id = ' . Tenant::id()
            )->fetchColumn();
            if (!$permiteOcultos) $visibilidad = 'normal';
        }

        // Si se está respondiendo dentro de una conversación ligada a un estudiante,
        // propagar la marca para que la respuesta aparezca en esa misma conversación.
        $estudianteRelId = (int)($_POST['estudiante_relacionado_id'] ?? 0);
        if ($estudianteRelId > 0) {
            $chkEst = $this->db->prepare('SELECT 1 FROM estudiantes WHERE id = ? LIMIT 1');
            $chkEst->execute([$estudianteRelId]);
            if (!$chkEst->fetch()) $estudianteRelId = 0;
        }

        // Si se está respondiendo un mensaje existente, propagar el hilo real
        // (en vez de inferirlo luego por el texto del asunto).
        $hiloId = (int)($_POST['hilo_id'] ?? 0);
        if ($hiloId > 0) {
            $chkHilo = $this->db->prepare('SELECT 1 FROM mensajes WHERE id = ? LIMIT 1');
            $chkHilo->execute([$hiloId]);
            if (!$chkHilo->fetch()) $hiloId = 0;
        }

        if (!$asunto || !$cuerpo || !$destinatarios) {
            flashError('Completa todos los campos y selecciona al menos un destinatario.');
            redirect('/comunicaciones/redactar');
            return;
        }

        // Padres y estudiantes: solo pueden escribir a sus docentes (seguridad servidor)
        $rol = Auth::rol();
        if (in_array($rol, ['padre', 'estudiante'])) {
            $permitidos = $this->destinatariosPermitidos(Auth::id(), $rol);
            $destinatarios = array_values(array_intersect($destinatarios, $permitidos));
            if (!$destinatarios) {
                flashError('Solo puedes enviar mensajes a los docentes de tus materias.');
                redirect('/comunicaciones/redactar');
                return;
            }
        }

        // Procesar archivos adjuntos
        $archivos = [];
        if (!empty($_FILES['adjuntos']['name'][0])) {
            $storage = ROOT . '/storage/adjuntos';
            if (!is_dir($storage)) {
                mkdir($storage, 0755, true);
            }
            foreach ($_FILES['adjuntos']['name'] as $i => $orig) {
                if ($_FILES['adjuntos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['adjuntos']['size'][$i] > 10 * 1024 * 1024)  continue;
                $mime = mime_content_type($_FILES['adjuntos']['tmp_name'][$i]);
                if (!in_array($mime, self::ALLOWED_MIME)) continue;
                $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $disk = bin2hex(random_bytes(16)) . ($ext ? ".$ext" : '');
                if (move_uploaded_file($_FILES['adjuntos']['tmp_name'][$i], "$storage/$disk")) {
                    $archivos[] = [
                        'orig' => $orig,
                        'disk' => $disk,
                        'mime' => $mime,
                        'size' => $_FILES['adjuntos']['size'][$i],
                    ];
                }
            }
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO mensajes (remitente_id, asunto, cuerpo, estudiante_relacionado_id, hilo_id, visibilidad) VALUES (?,?,?,?,?,?)'
            )->execute([Auth::id(), $asunto, $cuerpo, $estudianteRelId ?: null, $hiloId ?: null, $visibilidad]);

            $msg_id = (int)$this->db->lastInsertId();

            if (!$hiloId) {
                // Mensaje nuevo (no es respuesta): es la raíz de su propio hilo.
                $this->db->prepare('UPDATE mensajes SET hilo_id = ? WHERE id = ?')
                         ->execute([$msg_id, $msg_id]);
            }

            $ins = $this->db->prepare(
                'INSERT IGNORE INTO mensajes_destinatarios (mensaje_id, usuario_id) VALUES (?,?)'
            );
            foreach ($destinatarios as $did) {
                if ($did > 0) $ins->execute([$msg_id, $did]);
            }

            if ($archivos) {
                $insAdj = $this->db->prepare(
                    'INSERT INTO mensajes_adjuntos (mensaje_id, nombre_orig, nombre_disk, mime, tamano)
                     VALUES (?,?,?,?,?)'
                );
                foreach ($archivos as $a) {
                    $insAdj->execute([$msg_id, $a['orig'], $a['disk'], $a['mime'], $a['size']]);
                }
            }

            $this->db->commit();

            // Notificación por correo a destinatarios (respeta preferencias)
            $remNombre  = Auth::user()['nombre'] . ' ' . Auth::user()['apellido'];
            $remRol     = Auth::rol();
            $cuerpoHtml = '<p>Has recibido un mensaje de <strong>' . htmlspecialchars($remNombre) . '</strong>.</p>'
                        . '<p><strong>Asunto:</strong> ' . htmlspecialchars($asunto) . '</p>';

            // Si el remitente es padre o estudiante, los docentes reciben la notificación
            // como 'tarea_asignada' (actividad en tareas/mensajes de familias)
            if (in_array($remRol, ['padre', 'estudiante'])) {
                // Separar destinatarios docentes de los demás
                $ids_str = implode(',', array_map('intval', $destinatarios));
                $stRoles = $this->db->query(
                    "SELECT id, rol FROM usuarios WHERE id IN ($ids_str)"
                );
                $docentes  = [];
                $restantes = [];
                foreach ($stRoles->fetchAll() as $r) {
                    if ($r['rol'] === 'docente') $docentes[]  = (int)$r['id'];
                    else                          $restantes[] = (int)$r['id'];
                }
                if ($docentes) {
                    Notificador::notificar(
                        'tarea_asignada', $docentes,
                        "Nuevo mensaje de $remNombre — " . APP_NAME,
                        'Tienes un nuevo mensaje',
                        $cuerpoHtml,
                        APP_URL . '/comunicaciones/' . $msg_id,
                        'Leer mensaje'
                    );
                }
                if ($restantes) {
                    Notificador::notificar(
                        'mensaje', $restantes,
                        "Nuevo mensaje de $remNombre — " . APP_NAME,
                        'Tienes un nuevo mensaje',
                        $cuerpoHtml,
                        APP_URL . '/comunicaciones/' . $msg_id,
                        'Leer mensaje'
                    );
                }
            } else {
                Notificador::notificar(
                    'mensaje',
                    $destinatarios,
                    "Nuevo mensaje de $remNombre — " . APP_NAME,
                    'Tienes un nuevo mensaje',
                    $cuerpoHtml,
                    APP_URL . '/comunicaciones/' . $msg_id,
                    'Leer mensaje'
                );
            }

            flashSuccess('Mensaje enviado correctamente.');
            redirect('/comunicaciones');
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('Error al enviar el mensaje. Intenta de nuevo.');
            redirect('/comunicaciones/redactar');
        }
    }

    // ── ADJUNTO (descarga) ───────────────────────────────────────
    public function adjunto(int $id): void {
        Auth::require();
        $uid = Auth::id();

        $st = $this->db->prepare("
            SELECT ma.nombre_orig, ma.nombre_disk, ma.mime
            FROM mensajes_adjuntos ma
            JOIN mensajes m ON m.id = ma.mensaje_id
            LEFT JOIN mensajes_destinatarios md ON md.mensaje_id = m.id AND md.usuario_id = ?
            WHERE ma.id = ? AND (m.remitente_id = ? OR md.usuario_id IS NOT NULL)
            LIMIT 1
        ");
        $st->execute([$uid, $id, $uid]);
        $adj = $st->fetch();

        if (!$adj) {
            http_response_code(403);
            exit;
        }

        $path = ROOT . '/storage/adjuntos/' . basename($adj['nombre_disk']);
        if (!file_exists($path)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $adj['mime']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($adj['nombre_orig']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // ── ACCIÓN MASIVA (bandeja) ─────────────────────────────────────
    // action: archivar | eliminar | marcar_leido | marcar_no_leido
    // ids[]: ids de mensaje (el "resumen" de hilo que se marcó en la bandeja) —
    // se aplica a TODOS los mensajes del mismo hilo que el usuario pueda ver.
    public function accionMasiva(): void {
        Auth::require();
        csrfVerify();
        header('Content-Type: application/json');
        $uid = Auth::id();

        $accion = $_POST['accion'] ?? '';
        $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));
        if (!$ids || !in_array($accion, ['archivar', 'eliminar', 'marcar_leido', 'marcar_no_leido'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Solicitud inválida.']);
            return;
        }

        // Resolver hilo_id de cada mensaje seleccionado (puede ser el id del mensaje si no tiene hilo)
        $in = implode(',', array_fill(0, count($ids), '?'));
        $hilosSt = $this->db->prepare("SELECT id, COALESCE(hilo_id, id) AS hilo FROM mensajes WHERE id IN ($in)");
        $hilosSt->execute($ids);
        $hilos = array_unique(array_column($hilosSt->fetchAll(), 'hilo'));
        if (!$hilos) { echo json_encode(['ok' => true, 'afectados' => 0]); return; }

        $inHilos = implode(',', array_fill(0, count($hilos), '?'));

        $campo = match ($accion) {
            'archivar'        => 'archivado = 1',
            'eliminar'        => 'eliminado = 1',
            'marcar_leido'    => 'leido = 1, leido_at = IFNULL(leido_at, NOW())',
            'marcar_no_leido' => 'leido = 0',
        };

        $sql = "UPDATE mensajes_destinatarios md
                JOIN mensajes m ON m.id = md.mensaje_id
                SET md.$campo
                WHERE md.usuario_id = ? AND COALESCE(m.hilo_id, m.id) IN ($inHilos)";
        $st = $this->db->prepare($sql);
        $st->execute([$uid, ...$hilos]);

        echo json_encode(['ok' => true, 'afectados' => $st->rowCount(), 'csrf' => csrfToken()]);
    }

    // ── ENVIADOS ─────────────────────────────────────────────────
    public function enviados(): void {
        Auth::require();
        $uid = Auth::id();

        $q          = trim($_GET['q']          ?? '');
        $de         = trim($_GET['de']         ?? '');
        $asunto     = trim($_GET['asunto']     ?? '');
        $fecha_desde= trim($_GET['fecha_desde']?? '');
        $fecha_hasta= trim($_GET['fecha_hasta']?? '');

        $where  = 'm.remitente_id = ?';
        $params = [$uid];

        if ($q)          { $where .= ' AND (m.asunto LIKE ? OR m.cuerpo LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        if ($asunto)     { $where .= ' AND m.asunto LIKE ?'; $params[] = "%$asunto%"; }
        if ($fecha_desde){ $where .= ' AND DATE(m.created_at) >= ?'; $params[] = $fecha_desde; }
        if ($fecha_hasta){ $where .= ' AND DATE(m.created_at) <= ?'; $params[] = $fecha_hasta; }

        $sql = "SELECT m.id, m.asunto, m.created_at, m.hilo_id, m.remitente_id, m.cuerpo,
                       COUNT(md.id) AS total_dest, SUM(md.leido) AS leidos, 1 AS leido
                FROM mensajes m
                LEFT JOIN mensajes_destinatarios md ON md.mensaje_id = m.id
                WHERE $where GROUP BY m.id ORDER BY m.created_at DESC LIMIT 50";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        // Mismo agrupamiento por hilo_id que la bandeja de entrada, para no repetir
        // una fila por cada respuesta de la misma conversación.
        $mensajes = $this->agruparPorHilo($st->fetchAll());

        $filtros = compact('q','de','asunto','fecha_desde','fecha_hasta');

        if (!empty($_GET['ajax'])) {
            header('Content-Type: text/html; charset=utf-8');
            extract(compact('mensajes'));
            include ROOT . '/app/views/comunicaciones/_enviados_lista.php';
            exit;
        }

        render('comunicaciones/enviados', compact('mensajes','filtros'));
    }
}
