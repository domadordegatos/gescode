<?php
class AsignacionesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        $cid = Tenant::id();

        $grupoId = (int)($_GET['grupo_id'] ?? 0);

        $grupos = $this->db->prepare(
            "SELECT g.id, gr.nombre AS grado, g.nombre
             FROM grupos g JOIN grados gr ON gr.id = g.grado_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE al.colegio_id = ? AND al.activo = 1
             ORDER BY gr.nivel, g.nombre"
        );
        $grupos->execute([$cid]);
        $grupos = $grupos->fetchAll();

        $sql = "SELECT a.id, a.grupo_id, a.materia_id, a.docente_id,
                       gr.nombre AS grado, g.nombre AS grupo, m.nombre AS materia,
                       u.nombre AS doc_nombre, u.apellido AS doc_apellido
                FROM asignaciones a
                JOIN grupos g ON g.id = a.grupo_id
                JOIN grados gr ON gr.id = g.grado_id
                JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
                JOIN materias m ON m.id = a.materia_id
                JOIN usuarios u ON u.id = a.docente_id
                WHERE al.colegio_id = ? AND al.activo = 1";
        $params = [$cid];
        if ($grupoId) { $sql .= ' AND a.grupo_id = ?'; $params[] = $grupoId; }
        $sql .= ' ORDER BY gr.nivel, g.nombre, m.nombre';

        $st = $this->db->prepare($sql);
        $st->execute($params);

        $materias = $this->db->query('SELECT id, nombre FROM materias ORDER BY nombre')->fetchAll();
        $docentes = $this->db->prepare("SELECT id, nombre, apellido FROM usuarios WHERE colegio_id = ? AND rol = 'docente' AND activo = 1 ORDER BY nombre");
        $docentes->execute([$cid]);

        render('admin/asignaciones_lista', [
            'asignaciones' => $st->fetchAll(),
            'grupos' => $grupos,
            'materias' => $materias,
            'docentes' => $docentes->fetchAll(),
            'grupoFiltro' => $grupoId,
        ]);
    }

    public function guardar(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $grupoId = (int)($_POST['grupo_id'] ?? 0);
        $materiaId = (int)($_POST['materia_id'] ?? 0);
        $docenteId = (int)($_POST['docente_id'] ?? 0);

        if (!$grupoId || !$materiaId || !$docenteId) {
            flashError('Selecciona grupo, materia y docente.');
            redirect('/asignaciones');
            return;
        }

        $chk = $this->db->prepare('SELECT 1 FROM asignaciones WHERE grupo_id = ? AND materia_id = ?');
        $chk->execute([$grupoId, $materiaId]);
        if ($chk->fetch()) {
            flashError('Ese grupo ya tiene un docente asignado para esa materia. Edítala en vez de crear otra.');
            redirect('/asignaciones?grupo_id=' . $grupoId);
            return;
        }

        $this->db->prepare('INSERT INTO asignaciones (grupo_id, materia_id, docente_id) VALUES (?, ?, ?)')
                  ->execute([$grupoId, $materiaId, $docenteId]);

        Auditoria::registrar('asignacion.crear', "Asignó al docente #$docenteId la materia #$materiaId en el grupo #$grupoId.");
        flashSuccess('Asignación creada.');
        redirect('/asignaciones?grupo_id=' . $grupoId);
    }

    public function actualizar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $docenteId = (int)($_POST['docente_id'] ?? 0);
        if (!$docenteId) {
            flashError('Selecciona el docente.');
            redirect('/asignaciones');
            return;
        }

        $q = $this->db->prepare('SELECT grupo_id FROM asignaciones WHERE id = ? LIMIT 1');
        $q->execute([$id]);
        $grupoId = $q->fetchColumn();
        if (!$grupoId) { abort(404); }

        $this->db->prepare('UPDATE asignaciones SET docente_id = ? WHERE id = ?')->execute([$docenteId, $id]);

        Auditoria::registrar('asignacion.actualizar', "Cambió el docente de la asignación #$id a #$docenteId.");
        flashSuccess('Docente actualizado para esta materia.');
        redirect('/asignaciones?grupo_id=' . $grupoId);
    }

    public function eliminar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $q = $this->db->prepare('SELECT grupo_id FROM asignaciones WHERE id = ? LIMIT 1');
        $q->execute([$id]);
        $grupoId = $q->fetchColumn();
        if (!$grupoId) { abort(404); }

        $chkNotas = $this->db->prepare('SELECT COUNT(*) FROM indicadores_eval WHERE asignacion_id = ?');
        $chkNotas->execute([$id]);
        if ((int)$chkNotas->fetchColumn() > 0) {
            flashError('No se puede eliminar: esta asignación ya tiene notas/indicadores registrados. Usa la papelera de notas si necesitas limpiar eso primero.');
            redirect('/asignaciones?grupo_id=' . $grupoId);
            return;
        }

        $this->db->prepare('DELETE FROM asignaciones WHERE id = ?')->execute([$id]);
        Auditoria::registrar('asignacion.eliminar', "Eliminó la asignación #$id.");
        flashSuccess('Asignación eliminada.');
        redirect('/asignaciones?grupo_id=' . $grupoId);
    }
}
