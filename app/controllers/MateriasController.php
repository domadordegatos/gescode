<?php
class MateriasController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        $materias = $this->db->query(
            "SELECT m.*, (SELECT COUNT(*) FROM asignaciones a WHERE a.materia_id = m.id) AS total_asignaciones
             FROM materias m ORDER BY m.nombre"
        )->fetchAll();
        render('admin/materias_lista', ['materias' => $materias]);
    }

    public function guardar(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '') ?: null;

        if (!$nombre) {
            flashError('Escribe el nombre de la materia.');
            redirect('/materias');
            return;
        }

        $this->db->prepare('INSERT INTO materias (nombre, codigo) VALUES (?, ?)')->execute([$nombre, $codigo]);
        Auditoria::registrar('materia.crear', "Creó la materia \"$nombre\".");
        flashSuccess('Materia creada.');
        redirect('/materias');
    }

    public function actualizar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '') ?: null;
        if (!$nombre) {
            flashError('Escribe el nombre de la materia.');
            redirect('/materias');
            return;
        }

        $this->db->prepare('UPDATE materias SET nombre = ?, codigo = ? WHERE id = ?')->execute([$nombre, $codigo, $id]);
        Auditoria::registrar('materia.actualizar', "Actualizó la materia #$id a \"$nombre\".");
        flashSuccess('Materia actualizada.');
        redirect('/materias');
    }

    public function eliminar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $chk = $this->db->prepare('SELECT COUNT(*) FROM asignaciones WHERE materia_id = ?');
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            flashError('No se puede eliminar: esta materia tiene asignaciones de docentes activas. Elimínalas primero.');
            redirect('/materias');
            return;
        }

        $this->db->prepare('DELETE FROM materias WHERE id = ?')->execute([$id]);
        Auditoria::registrar('materia.eliminar', "Eliminó la materia #$id.");
        flashSuccess('Materia eliminada.');
        redirect('/materias');
    }
}
