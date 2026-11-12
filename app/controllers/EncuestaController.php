<?php
class EncuestaController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'docente', 'padre');
        $activo = (bool)$this->db->query(
            'SELECT permite_encuestas FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    private function esGestor(): bool {
        return in_array(Auth::rol(), ['admin', 'soporte'], true);
    }

    // ── Listado ─────────────────────────────────────────────────
    public function index(): void {
        $this->verificarAcceso();

        if ($this->esGestor()) {
            $st = $this->db->prepare(
                "SELECT e.*,
                        (SELECT COUNT(*) FROM encuestas_participantes p WHERE p.encuesta_id = e.id) AS total_invitados,
                        (SELECT COUNT(*) FROM encuestas_participantes p WHERE p.encuesta_id = e.id AND p.completado = 1) AS total_completados
                 FROM encuestas e WHERE e.colegio_id = ? ORDER BY e.created_at DESC"
            );
            $st->execute([Tenant::id()]);
        } else {
            $st = $this->db->prepare(
                "SELECT e.*, p.completado
                 FROM encuestas_participantes p
                 JOIN encuestas e ON e.id = p.encuesta_id
                 WHERE p.usuario_id = ? AND e.activa = 1
                 ORDER BY e.created_at DESC"
            );
            $st->execute([Auth::id()]);
        }
        $encuestas = $st->fetchAll();

        render('comunicaciones/encuestas_lista', [
            'encuestas' => $encuestas,
            'esGestor' => $this->esGestor(),
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    // ── Crear (solo admin/soporte) ─────────────────────────────
    public function nueva(): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        render('comunicaciones/encuesta_form');
    }

    public function guardar(): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $audiencia = $_POST['audiencia'] ?? 'todos';
        if (!in_array($audiencia, ['todos', 'padres', 'docentes'], true)) $audiencia = 'todos';
        $anonima = isset($_POST['anonima']) ? 1 : 0;

        $preguntasTexto = $_POST['pregunta_texto'] ?? [];
        $preguntasTipo  = $_POST['pregunta_tipo'] ?? [];
        $preguntasOpciones = $_POST['pregunta_opciones'] ?? [];

        if (!$titulo || !$preguntasTexto) {
            flashError('Agrega un título y al menos una pregunta.');
            redirect('/encuestas/nueva');
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO encuestas (colegio_id, titulo, descripcion, audiencia, anonima, creado_por)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([Tenant::id(), $titulo, $descripcion ?: null, $audiencia, $anonima, Auth::id()]);
            $encuestaId = (int)$this->db->lastInsertId();

            $insPreg = $this->db->prepare(
                'INSERT INTO encuestas_preguntas (encuesta_id, texto, tipo, opciones, orden) VALUES (?, ?, ?, ?, ?)'
            );
            $orden = 1;
            foreach ($preguntasTexto as $i => $texto) {
                $texto = trim($texto);
                if (!$texto) continue;
                $tipo = $preguntasTipo[$i] ?? 'opcion_unica';
                if (!in_array($tipo, ['opcion_unica', 'opcion_multiple', 'escala', 'texto'], true)) $tipo = 'texto';
                $opciones = null;
                if (in_array($tipo, ['opcion_unica', 'opcion_multiple'], true)) {
                    $lista = array_filter(array_map('trim', explode("\n", $preguntasOpciones[$i] ?? '')));
                    $opciones = $lista ? json_encode(array_values($lista)) : null;
                }
                $insPreg->execute([$encuestaId, $texto, $tipo, $opciones, $orden++]);
            }

            $destinatarios = $this->resolverDestinatarios($audiencia);
            $insPart = $this->db->prepare(
                'INSERT IGNORE INTO encuestas_participantes (encuesta_id, usuario_id) VALUES (?, ?)'
            );
            foreach ($destinatarios as $uid) {
                $insPart->execute([$encuestaId, $uid]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudo crear la encuesta. Intenta de nuevo.');
            redirect('/encuestas/nueva');
            return;
        }

        flashSuccess('Encuesta creada y enviada a ' . count($destinatarios) . ' participante(s).');
        redirect('/encuestas');
    }

    private function resolverDestinatarios(string $audiencia): array {
        $colegioId = Tenant::id();
        $rolSql = match ($audiencia) {
            'padres'   => "rol = 'padre'",
            'docentes' => "rol = 'docente'",
            default    => "rol IN ('admin','soporte','docente','padre')",
        };
        $st = $this->db->prepare("SELECT id FROM usuarios WHERE colegio_id = ? AND activo = 1 AND $rolSql");
        $st->execute([$colegioId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    // ── Responder (participante) ────────────────────────────────
    private function participacion(int $encuestaId): ?array {
        $q = $this->db->prepare(
            'SELECT p.*, e.titulo, e.descripcion, e.anonima, e.activa, e.colegio_id
             FROM encuestas_participantes p JOIN encuestas e ON e.id = p.encuesta_id
             WHERE p.encuesta_id = ? AND p.usuario_id = ? LIMIT 1'
        );
        $q->execute([$encuestaId, Auth::id()]);
        $part = $q->fetch();
        if (!$part || (int)$part['colegio_id'] !== Tenant::id()) return null;
        return $part;
    }

    public function responder(int $id): void {
        $this->verificarAcceso();
        $part = $this->participacion($id);
        if (!$part) { abort(403); }

        $qp = $this->db->prepare('SELECT * FROM encuestas_preguntas WHERE encuesta_id = ? ORDER BY orden');
        $qp->execute([$id]);
        $preguntas = $qp->fetchAll();
        foreach ($preguntas as &$p) {
            $p['opciones'] = $p['opciones'] ? json_decode($p['opciones'], true) : [];
        }
        unset($p);

        render('comunicaciones/encuesta_responder', [
            'encuestaId' => $id,
            'part' => $part,
            'preguntas' => $preguntas,
        ]);
    }

    public function guardarRespuestas(int $id): void {
        $this->verificarAcceso();
        csrfVerify();

        $part = $this->participacion($id);
        if (!$part) { abort(403); }
        if ($part['completado']) {
            flashError('Ya respondiste esta encuesta.');
            redirect('/encuestas');
            return;
        }
        if (!$part['activa']) {
            flashError('Esta encuesta ya no está activa.');
            redirect('/encuestas');
            return;
        }

        $qp = $this->db->prepare('SELECT id, tipo FROM encuestas_preguntas WHERE encuesta_id = ?');
        $qp->execute([$id]);
        $preguntas = $qp->fetchAll();

        $this->db->beginTransaction();
        try {
            $ins = $this->db->prepare(
                'INSERT INTO encuestas_respuestas (pregunta_id, usuario_id, valor) VALUES (?, ?, ?)'
            );
            foreach ($preguntas as $p) {
                $pid = $p['id'];
                $raw = $_POST['respuesta'][$pid] ?? null;
                if ($raw === null) continue;
                $valor = is_array($raw) ? implode(', ', array_map('trim', $raw)) : trim($raw);
                if ($valor === '') continue;
                $ins->execute([$pid, Auth::id(), $valor]);
            }

            $this->db->prepare(
                'UPDATE encuestas_participantes SET completado = 1, completado_at = NOW() WHERE encuesta_id = ? AND usuario_id = ?'
            )->execute([$id, Auth::id()]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudieron guardar tus respuestas. Intenta de nuevo.');
            redirect('/encuestas/' . $id . '/responder');
            return;
        }

        flashSuccess('¡Gracias! Tus respuestas fueron registradas.');
        redirect('/encuestas');
    }

    // ── Resultados agregados (solo admin/soporte) ───────────────
    public function resultados(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');

        $qe = $this->db->prepare('SELECT * FROM encuestas WHERE id = ? AND colegio_id = ? LIMIT 1');
        $qe->execute([$id, Tenant::id()]);
        $encuesta = $qe->fetch();
        if (!$encuesta) { abort(404); }

        $qp = $this->db->prepare('SELECT * FROM encuestas_preguntas WHERE encuesta_id = ? ORDER BY orden');
        $qp->execute([$id]);
        $preguntas = $qp->fetchAll();

        foreach ($preguntas as &$p) {
            $qr = $this->db->prepare('SELECT valor FROM encuestas_respuestas WHERE pregunta_id = ?');
            $qr->execute([$p['id']]);
            $valores = $qr->fetchAll(PDO::FETCH_COLUMN);
            $p['total_respuestas'] = count($valores);

            if (in_array($p['tipo'], ['opcion_unica', 'escala'], true)) {
                $conteo = [];
                foreach ($valores as $v) { $conteo[$v] = ($conteo[$v] ?? 0) + 1; }
                $p['conteo'] = $conteo;
            } elseif ($p['tipo'] === 'opcion_multiple') {
                $conteo = [];
                foreach ($valores as $v) {
                    foreach (array_map('trim', explode(',', $v)) as $op) {
                        if ($op === '') continue;
                        $conteo[$op] = ($conteo[$op] ?? 0) + 1;
                    }
                }
                $p['conteo'] = $conteo;
            } else {
                $p['respuestas_texto'] = $valores; // solo texto libre se lista, sin autor si es anónima
            }
        }
        unset($p);

        $totalInvitados = (int)$this->db->query(
            "SELECT COUNT(*) FROM encuestas_participantes WHERE encuesta_id = $id"
        )->fetchColumn();
        $totalCompletados = (int)$this->db->query(
            "SELECT COUNT(*) FROM encuestas_participantes WHERE encuesta_id = $id AND completado = 1"
        )->fetchColumn();

        render('comunicaciones/encuesta_resultados', [
            'encuesta' => $encuesta,
            'preguntas' => $preguntas,
            'totalInvitados' => $totalInvitados,
            'totalCompletados' => $totalCompletados,
        ]);
    }

    public function cerrar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $this->db->prepare('UPDATE encuestas SET activa = 0 WHERE id = ? AND colegio_id = ?')
                  ->execute([$id, Tenant::id()]);

        flashSuccess('Encuesta cerrada. Ya no se podrán registrar más respuestas.');
        redirect('/encuestas/' . $id . '/resultados');
    }
}
