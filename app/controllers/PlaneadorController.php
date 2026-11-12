<?php
class PlaneadorController {
    private PDO $db;
    private Grupo $grupoM;

    public function __construct() {
        $this->db = Database::get();
        $this->grupoM = new Grupo();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'docente');
        $activo = (bool)$this->db->query(
            'SELECT permite_planeador_clases FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    /** Verifica que la asignación pertenezca al docente autenticado (si rol docente) */
    private function asignacionPermitida(int $asignacionId): ?array {
        $q = $this->db->prepare(
            'SELECT a.id, a.docente_id, a.grupo_id, a.materia_id,
                    m.nombre AS materia, gr.nombre AS grado, g.nombre AS grupo
             FROM asignaciones a
             JOIN materias m ON m.id = a.materia_id
             JOIN grupos g   ON g.id = a.grupo_id
             JOIN grados gr  ON gr.id = g.grado_id
             WHERE a.id = ? LIMIT 1'
        );
        $q->execute([$asignacionId]);
        $asig = $q->fetch();
        if (!$asig) return null;
        if (Auth::rol() === 'docente' && (int)$asig['docente_id'] !== Auth::id()) return null;
        return $asig;
    }

    public function index(): void {
        $this->verificarAcceso();
        $rol = Auth::rol();

        if ($rol === 'docente') {
            $grupos = $this->grupoM->porDocente(Auth::id());
            $periodo = $this->grupoM->periodoActivo();
            render('docente/planeador_index', ['grupos' => $grupos, 'periodo' => $periodo]);
            return;
        }

        // admin/soporte: ver todas las planeaciones del colegio (solo lectura)
        $periodoId = isset($_GET['periodo']) ? (int)$_GET['periodo'] : null;
        $sql = "SELECT p.id, p.tema, p.fecha_inicio, p.fecha_fin, p.created_at,
                       m.nombre AS materia, gr.nombre AS grado, g.nombre AS grupo,
                       per.nombre AS periodo, per.id AS periodo_id,
                       u.nombre AS doc_nombre, u.apellido AS doc_apellido
                FROM planeaciones p
                JOIN asignaciones a ON a.id = p.asignacion_id
                JOIN materias m     ON m.id = a.materia_id
                JOIN grupos g        ON g.id = a.grupo_id
                JOIN grados gr       ON gr.id = g.grado_id
                JOIN usuarios u      ON u.id = a.docente_id
                JOIN periodos per    ON per.id = p.periodo_id
                JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
                WHERE al.activo = 1 AND u.colegio_id = ?";
        $params = [Tenant::id()];
        if ($periodoId) { $sql .= ' AND p.periodo_id = ?'; $params[] = $periodoId; }
        $sql .= ' ORDER BY p.created_at DESC LIMIT 200';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $planeaciones = $st->fetchAll();

        $qp = $this->db->prepare(
            'SELECT per.id, per.nombre FROM periodos per
             JOIN anos_lectivos al ON al.id = per.ano_lectivo_id
             WHERE al.activo = 1 ORDER BY per.numero'
        );
        $qp->execute();
        $periodos = $qp->fetchAll();

        render('admin/planeador_lista', ['planeaciones' => $planeaciones, 'periodos' => $periodos, 'periodoId' => $periodoId]);
    }

    public function porAsignacion(int $asignacionId): void {
        $this->verificarAcceso();
        $asig = $this->asignacionPermitida($asignacionId);
        if (!$asig) { abort(403); }

        $periodos = $this->grupoM->periodos();
        $periodoActivo = $this->grupoM->periodoActivo();

        $q = $this->db->prepare(
            'SELECT p.*, per.nombre AS periodo_nombre
             FROM planeaciones p JOIN periodos per ON per.id = p.periodo_id
             WHERE p.asignacion_id = ? ORDER BY per.numero DESC, p.fecha_inicio DESC'
        );
        $q->execute([$asignacionId]);
        $planeaciones = $q->fetchAll();

        render('docente/planeador_asignacion', [
            'asig' => $asig,
            'periodos' => $periodos,
            'periodoActivo' => $periodoActivo,
            'planeaciones' => $planeaciones,
        ]);
    }

    public function nueva(int $asignacionId): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        $asig = $this->asignacionPermitida($asignacionId);
        if (!$asig) { abort(403); }

        $periodos = $this->grupoM->periodos();
        render('docente/planeador_form', ['asig' => $asig, 'periodos' => $periodos, 'planeacion' => null]);
    }

    public function guardar(): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        csrfVerify();

        $asignacionId = (int)($_POST['asignacion_id'] ?? 0);
        $asig = $this->asignacionPermitida($asignacionId);
        if (!$asig) { abort(403); }

        $datos = $this->extraerCampos();
        if ($datos === null) {
            redirect('/planeador/asignacion/' . $asignacionId . '/nueva');
            return;
        }

        $this->db->prepare(
            'INSERT INTO planeaciones (asignacion_id, periodo_id, tema, objetivo, metodologia, recursos, evaluacion, fecha_inicio, fecha_fin)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $asignacionId, $datos['periodo_id'], $datos['tema'], $datos['objetivo'],
            $datos['metodologia'], $datos['recursos'], $datos['evaluacion'],
            $datos['fecha_inicio'], $datos['fecha_fin'],
        ]);

        flashSuccess('Planeación guardada correctamente.');
        redirect('/planeador/asignacion/' . $asignacionId);
    }

    private function planeacionPermitida(int $id): ?array {
        $q = $this->db->prepare(
            'SELECT p.*, a.docente_id FROM planeaciones p
             JOIN asignaciones a ON a.id = p.asignacion_id
             WHERE p.id = ? LIMIT 1'
        );
        $q->execute([$id]);
        $pl = $q->fetch();
        if (!$pl) return null;
        if ((int)$pl['docente_id'] !== Auth::id()) return null;
        return $pl;
    }

    public function editar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        $planeacion = $this->planeacionPermitida($id);
        if (!$planeacion) { abort(403); }

        $asig = $this->asignacionPermitida((int)$planeacion['asignacion_id']);
        $periodos = $this->grupoM->periodos();
        render('docente/planeador_form', ['asig' => $asig, 'periodos' => $periodos, 'planeacion' => $planeacion]);
    }

    public function actualizar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        csrfVerify();

        $planeacion = $this->planeacionPermitida($id);
        if (!$planeacion) { abort(403); }

        $datos = $this->extraerCampos();
        if ($datos === null) {
            redirect('/planeador/' . $id . '/editar');
            return;
        }

        $this->db->prepare(
            'UPDATE planeaciones SET periodo_id=?, tema=?, objetivo=?, metodologia=?, recursos=?, evaluacion=?, fecha_inicio=?, fecha_fin=?
             WHERE id=?'
        )->execute([
            $datos['periodo_id'], $datos['tema'], $datos['objetivo'],
            $datos['metodologia'], $datos['recursos'], $datos['evaluacion'],
            $datos['fecha_inicio'], $datos['fecha_fin'], $id,
        ]);

        flashSuccess('Planeación actualizada correctamente.');
        redirect('/planeador/asignacion/' . $planeacion['asignacion_id']);
    }

    public function eliminar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        csrfVerify();

        $planeacion = $this->planeacionPermitida($id);
        if (!$planeacion) { abort(403); }

        $this->db->prepare(
            'INSERT INTO papelera_general (colegio_id, tipo, descripcion, snapshot_json, eliminado_por)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            Tenant::id(), 'planeacion',
            'Planeación "' . $planeacion['tema'] . '"',
            json_encode(['planeacion' => $planeacion]),
            Auth::id(),
        ]);

        $this->db->prepare('DELETE FROM planeaciones WHERE id = ?')->execute([$id]);

        flashSuccess('Planeación eliminada. Puedes restaurarla desde la Papelera si fue un error.');
        redirect('/planeador/asignacion/' . $planeacion['asignacion_id']);
    }

    private function extraerCampos(): ?array {
        $tema = trim($_POST['tema'] ?? '');
        $periodoId = (int)($_POST['periodo_id'] ?? 0);
        if (!$tema || !$periodoId) {
            flashError('El tema y el período son obligatorios.');
            return null;
        }
        return [
            'periodo_id'   => $periodoId,
            'tema'         => $tema,
            'objetivo'     => trim($_POST['objetivo'] ?? '') ?: null,
            'metodologia'  => trim($_POST['metodologia'] ?? '') ?: null,
            'recursos'     => trim($_POST['recursos'] ?? '') ?: null,
            'evaluacion'   => trim($_POST['evaluacion'] ?? '') ?: null,
            'fecha_inicio' => trim($_POST['fecha_inicio'] ?? '') ?: null,
            'fecha_fin'    => trim($_POST['fecha_fin'] ?? '') ?: null,
        ];
    }
}
