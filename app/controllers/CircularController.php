<?php
class CircularController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'docente', 'padre');
        $activo = (bool)$this->db->query(
            'SELECT permite_circulares FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    private function puedePublicar(): bool {
        return in_array(Auth::rol(), ['admin', 'soporte'], true);
    }

    // ── Bandeja: lo que el usuario autenticado ha recibido ────────
    public function index(): void {
        $this->verificarAcceso();

        $st = $this->db->prepare(
            'SELECT c.id, c.titulo, c.cuerpo, c.created_at, c.requiere_acuse,
                    u.nombre AS pub_nombre, u.apellido AS pub_apellido,
                    cd.leido, cd.leido_at
             FROM circulares_destinatarios cd
             JOIN circulares c ON c.id = cd.circular_id
             JOIN usuarios u   ON u.id = c.publicado_por
             WHERE cd.usuario_id = ?
             ORDER BY c.created_at DESC'
        );
        $st->execute([Auth::id()]);
        $circulares = $st->fetchAll();

        render('comunicaciones/circulares_bandeja', [
            'circulares' => $circulares,
            'puedePublicar' => $this->puedePublicar(),
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function marcarLeida(int $id): void {
        $this->verificarAcceso();
        $this->db->prepare(
            'UPDATE circulares_destinatarios SET leido = 1, leido_at = NOW() WHERE circular_id = ? AND usuario_id = ?'
        )->execute([$id, Auth::id()]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    // ── Publicar (solo admin/soporte) ──────────────────────────────
    public function nueva(): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        $grados = $this->db->query('SELECT id, nombre FROM grados WHERE activo = 1 ORDER BY nivel')->fetchAll();
        render('comunicaciones/circular_form', ['grados' => $grados]);
    }

    public function publicar(): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $titulo = trim($_POST['titulo'] ?? '');
        $cuerpo = trim($_POST['cuerpo'] ?? '');
        $audiencia = $_POST['audiencia'] ?? 'todos';
        $gradoId = (int)($_POST['grado_id'] ?? 0) ?: null;
        $requiereAcuse = isset($_POST['requiere_acuse']) ? 1 : 0;

        if (!in_array($audiencia, ['todos', 'padres', 'docentes', 'grado'], true)) $audiencia = 'todos';
        if ($audiencia !== 'grado') $gradoId = null;

        if (!$titulo || !$cuerpo || ($audiencia === 'grado' && !$gradoId)) {
            flashError('Completa el título, el cuerpo y, si aplica, el grado destinatario.');
            redirect('/circulares/nueva');
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO circulares (colegio_id, titulo, cuerpo, publicado_por, audiencia, grado_id, requiere_acuse)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([Tenant::id(), $titulo, $cuerpo, Auth::id(), $audiencia, $gradoId, $requiereAcuse]);
            $circularId = (int)$this->db->lastInsertId();

            $destinatarios = $this->resolverDestinatarios($audiencia, $gradoId);
            $ins = $this->db->prepare(
                'INSERT IGNORE INTO circulares_destinatarios (circular_id, usuario_id) VALUES (?, ?)'
            );
            foreach ($destinatarios as $uid) {
                $ins->execute([$circularId, $uid]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudo publicar la circular. Intenta de nuevo.');
            redirect('/circulares/nueva');
            return;
        }

        flashSuccess('Circular publicada a ' . count($destinatarios) . ' destinatario(s).');
        redirect('/circulares');
    }

    /** Devuelve los IDs de usuario destinatarios según la audiencia elegida */
    private function resolverDestinatarios(string $audiencia, ?int $gradoId): array {
        $colegioId = Tenant::id();

        if ($audiencia === 'docentes') {
            $st = $this->db->prepare("SELECT id FROM usuarios WHERE colegio_id = ? AND rol = 'docente' AND activo = 1");
            $st->execute([$colegioId]);
            return $st->fetchAll(PDO::FETCH_COLUMN);
        }

        if ($audiencia === 'padres') {
            $st = $this->db->prepare("SELECT id FROM usuarios WHERE colegio_id = ? AND rol = 'padre' AND activo = 1");
            $st->execute([$colegioId]);
            return $st->fetchAll(PDO::FETCH_COLUMN);
        }

        if ($audiencia === 'grado') {
            $st = $this->db->prepare(
                "SELECT DISTINCT ac.usuario_id
                 FROM acudientes ac
                 JOIN estudiantes e   ON ac.estudiante_id = e.id
                 JOIN matriculas mt   ON mt.estudiante_id = e.id
                 JOIN grupos g        ON g.id = mt.grupo_id
                 JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
                 JOIN usuarios u      ON u.id = ac.usuario_id
                 WHERE g.grado_id = ? AND al.activo = 1 AND u.colegio_id = ? AND u.activo = 1"
            );
            $st->execute([$gradoId, $colegioId]);
            return $st->fetchAll(PDO::FETCH_COLUMN);
        }

        // 'todos': admin, soporte, docentes y padres (los estudiantes no tienen cuenta propia)
        $st = $this->db->prepare(
            "SELECT id FROM usuarios WHERE colegio_id = ? AND rol IN ('admin','soporte','docente','padre') AND activo = 1"
        );
        $st->execute([$colegioId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    // ── Panel de seguimiento (solo admin/soporte): quién ha leído ──
    public function seguimiento(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');

        $qc = $this->db->prepare('SELECT * FROM circulares WHERE id = ? AND colegio_id = ? LIMIT 1');
        $qc->execute([$id, Tenant::id()]);
        $circular = $qc->fetch();
        if (!$circular) { abort(404); }

        $qd = $this->db->prepare(
            'SELECT u.nombre, u.apellido, u.rol, cd.leido, cd.leido_at
             FROM circulares_destinatarios cd
             JOIN usuarios u ON u.id = cd.usuario_id
             WHERE cd.circular_id = ?
             ORDER BY cd.leido ASC, u.apellido'
        );
        $qd->execute([$id]);
        $destinatarios = $qd->fetchAll();

        $totalLeidos = count(array_filter($destinatarios, fn($d) => $d['leido']));

        render('comunicaciones/circular_seguimiento', [
            'circular' => $circular,
            'destinatarios' => $destinatarios,
            'totalLeidos' => $totalLeidos,
        ]);
    }
}
