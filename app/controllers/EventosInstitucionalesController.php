<?php
class EventosInstitucionalesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private const TIPOS_VALIDOS = ['evento', 'receso', 'otro'];
    public const TIPOS_LABEL = ['evento' => 'Evento', 'receso' => 'Receso escolar', 'otro' => 'Otra fecha institucional'];

    // ── INDEX ─────────────────────────────────────────────────────
    public function index(): void {
        Auth::requireRole('admin', 'soporte');

        $st = $this->db->prepare(
            "SELECT fi.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido
             FROM fechas_importantes fi
             LEFT JOIN usuarios u ON u.id = fi.creado_por
             WHERE fi.tipo IN ('evento','receso','otro') AND fi.colegio_id = ?
             ORDER BY fi.fecha_inicio DESC"
        );
        $st->execute([Tenant::id()]);
        $eventos = $st->fetchAll();

        // Períodos del año activo, para mostrar el calendario académico completo
        $periodos = $this->db->query(
            "SELECT p.* FROM periodos p JOIN anos_lectivos al ON al.id = p.ano_lectivo_id
             WHERE al.activo = 1 ORDER BY p.numero"
        )->fetchAll();

        render('admin/eventos', compact('eventos', 'periodos'));
    }

    // ── GUARDAR (crear o editar) ────────────────────────────────────
    public function guardar(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $id          = (int)($_POST['id'] ?? 0);
        $tipo        = $_POST['tipo'] ?? 'evento';
        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin    = trim($_POST['fecha_fin'] ?? '');

        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) { $tipo = 'evento'; }

        if (!$titulo || !$fecha_inicio) {
            flashError('El título y la fecha de inicio son obligatorios.');
            redirect('/admin/eventos');
            return;
        }
        if (!$fecha_fin) $fecha_fin = $fecha_inicio;
        if ($fecha_fin < $fecha_inicio) {
            flashError('La fecha de fin no puede ser anterior a la fecha de inicio.');
            redirect('/admin/eventos');
            return;
        }

        if ($id > 0) {
            // Verificar que el evento pertenece a este colegio antes de editar
            $chk = $this->db->prepare("SELECT id FROM fechas_importantes WHERE id=? AND tipo IN ('evento','receso','otro') AND colegio_id=?");
            $chk->execute([$id, Tenant::id()]);
            if (!$chk->fetch()) { flashError('Evento no encontrado.'); redirect('/admin/eventos'); return; }

            $this->db->prepare(
                'UPDATE fechas_importantes SET tipo=?, titulo=?, descripcion=?, fecha_inicio=?, fecha_fin=? WHERE id=?'
            )->execute([$tipo, $titulo, $descripcion ?: null, $fecha_inicio, $fecha_fin, $id]);
            flashSuccess('Fecha institucional actualizada.');
        } else {
            $this->db->prepare(
                "INSERT INTO fechas_importantes (tipo, titulo, descripcion, fecha_inicio, fecha_fin, creado_por, colegio_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$tipo, $titulo, $descripcion ?: null, $fecha_inicio, $fecha_fin, Auth::id(), Tenant::id()]);
            Auditoria::registrar('calendario.crear', "Publicó la fecha institucional \"$titulo\" (" . self::TIPOS_LABEL[$tipo] . ").");
            flashSuccess('Fecha institucional publicada. Ya es visible para todos en la Agenda.');
        }

        redirect('/admin/eventos');
    }

    // ── ELIMINAR ──────────────────────────────────────────────────
    public function eliminar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $this->db->prepare("DELETE FROM fechas_importantes WHERE id=? AND tipo IN ('evento','receso','otro') AND colegio_id=?")
                  ->execute([$id, Tenant::id()]);

        flashSuccess('Fecha institucional eliminada.');
        redirect('/admin/eventos');
    }
}
