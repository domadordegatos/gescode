<?php
class PqrsController {
    private PDO $db;

    private const TIPOS = [
        'peticion'     => 'Petición',
        'queja'        => 'Queja',
        'reclamo'      => 'Reclamo',
        'sugerencia'   => 'Sugerencia',
        'felicitacion' => 'Felicitación',
    ];

    private const ESTADOS = [
        'radicado'    => 'Radicado',
        'en_tramite'  => 'En trámite',
        'resuelto'    => 'Resuelto',
        'cerrado'     => 'Cerrado',
    ];

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'docente', 'padre');
        $activo = (bool)$this->db->query(
            'SELECT permite_pqrs FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    private function esGestor(): bool {
        return in_array(Auth::rol(), ['admin', 'soporte'], true);
    }

    public function index(): void {
        $this->verificarAcceso();

        if ($this->esGestor()) {
            $estado = $_GET['estado'] ?? '';
            $sql = "SELECT p.*, u.nombre AS u_nombre, u.apellido AS u_apellido
                    FROM pqrs p JOIN usuarios u ON u.id = p.usuario_id
                    WHERE p.colegio_id = ?";
            $params = [Tenant::id()];
            if (isset(self::ESTADOS[$estado])) { $sql .= ' AND p.estado = ?'; $params[] = $estado; }
            $sql .= ' ORDER BY p.created_at DESC LIMIT 200';
            $st = $this->db->prepare($sql);
            $st->execute($params);
        } else {
            $st = $this->db->prepare(
                'SELECT p.* FROM pqrs p WHERE p.usuario_id = ? ORDER BY p.created_at DESC'
            );
            $st->execute([Auth::id()]);
        }
        $items = $st->fetchAll();

        render('comunicaciones/pqrs_lista', [
            'items' => $items,
            'esGestor' => $this->esGestor(),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
            'estadoFiltro' => $_GET['estado'] ?? '',
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function nueva(): void {
        $this->verificarAcceso();
        $hijos = [];
        if (Auth::rol() === 'padre') {
            $st = $this->db->prepare(
                'SELECT e.id, u.nombre, u.apellido FROM acudientes ac
                 JOIN estudiantes e ON ac.estudiante_id = e.id
                 JOIN usuarios u ON u.id = e.usuario_id
                 WHERE ac.usuario_id = ?'
            );
            $st->execute([Auth::id()]);
            $hijos = $st->fetchAll();
        }
        render('comunicaciones/pqrs_form', ['tipos' => self::TIPOS, 'hijos' => $hijos]);
    }

    public function guardar(): void {
        $this->verificarAcceso();
        csrfVerify();

        $tipo = $_POST['tipo'] ?? 'peticion';
        if (!isset(self::TIPOS[$tipo])) $tipo = 'peticion';
        $asunto = trim($_POST['asunto'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estudianteId = (int)($_POST['estudiante_id'] ?? 0) ?: null;

        if (!$asunto || !$descripcion) {
            flashError('Completa el asunto y la descripción.');
            redirect('/pqrs/nueva');
            return;
        }

        $radicado = $this->generarRadicado();

        $this->db->prepare(
            'INSERT INTO pqrs (colegio_id, radicado, tipo, asunto, descripcion, usuario_id, estudiante_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([Tenant::id(), $radicado, $tipo, $asunto, $descripcion, Auth::id(), $estudianteId]);

        flashSuccess("Tu solicitud fue radicada con el número $radicado. Podrás ver su estado en esta sección.");
        redirect('/pqrs');
    }

    private function generarRadicado(): string {
        $anio = date('Y');
        $count = (int)$this->db->query(
            "SELECT COUNT(*) FROM pqrs WHERE colegio_id = " . Tenant::id() . " AND radicado LIKE 'PQRS-$anio-%'"
        )->fetchColumn();
        do {
            $count++;
            $radicado = sprintf('PQRS-%s-%06d', $anio, $count);
            $chk = $this->db->prepare('SELECT 1 FROM pqrs WHERE radicado = ? LIMIT 1');
            $chk->execute([$radicado]);
        } while ($chk->fetch());
        return $radicado;
    }

    private function pqrsVisible(int $id): ?array {
        $q = $this->db->prepare('SELECT * FROM pqrs WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, Tenant::id()]);
        $pqrs = $q->fetch();
        if (!$pqrs) return null;
        if (!$this->esGestor() && (int)$pqrs['usuario_id'] !== Auth::id()) return null;
        return $pqrs;
    }

    public function ver(int $id): void {
        $this->verificarAcceso();
        $pqrs = $this->pqrsVisible($id);
        if (!$pqrs) { abort(404); }

        $qu = $this->db->prepare('SELECT nombre, apellido, email FROM usuarios WHERE id = ?');
        $qu->execute([$pqrs['usuario_id']]);
        $solicitante = $qu->fetch();

        render('comunicaciones/pqrs_detalle', [
            'pqrs' => $pqrs,
            'solicitante' => $solicitante,
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
            'esGestor' => $this->esGestor(),
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function responder(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $pqrs = $this->pqrsVisible($id);
        if (!$pqrs) { abort(404); }

        $respuesta = trim($_POST['respuesta'] ?? '');
        $estado = $_POST['estado'] ?? 'en_tramite';
        if (!isset(self::ESTADOS[$estado])) $estado = 'en_tramite';

        if (!$respuesta) {
            flashError('Escribe una respuesta antes de guardar.');
            redirect('/pqrs/' . $id);
            return;
        }

        $this->db->prepare(
            'UPDATE pqrs SET respuesta = ?, estado = ?, respondido_por = ?, respondido_at = NOW() WHERE id = ?'
        )->execute([$respuesta, $estado, Auth::id(), $id]);

        flashSuccess('Respuesta guardada.');
        redirect('/pqrs/' . $id);
    }

    public function cambiarEstado(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $pqrs = $this->pqrsVisible($id);
        if (!$pqrs) { abort(404); }

        $estado = $_POST['estado'] ?? '';
        if (!isset(self::ESTADOS[$estado])) {
            flashError('Estado inválido.');
            redirect('/pqrs/' . $id);
            return;
        }

        $this->db->prepare('UPDATE pqrs SET estado = ? WHERE id = ?')->execute([$estado, $id]);
        flashSuccess('Estado actualizado.');
        redirect('/pqrs/' . $id);
    }
}
