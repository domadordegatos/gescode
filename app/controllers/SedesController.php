<?php
class SedesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        $st = $this->db->prepare('SELECT * FROM sedes WHERE colegio_id = ? ORDER BY nombre');
        $st->execute([Tenant::id()]);
        render('admin/sedes_lista', ['sedes' => $st->fetchAll()]);
    }

    public function guardar(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $nombre = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (!$nombre) { flashError('Escribe el nombre de la sede.'); redirect('/sedes'); return; }

        $this->db->prepare('INSERT INTO sedes (colegio_id, nombre, direccion, telefono, activa) VALUES (?, ?, ?, ?, 1)')
                  ->execute([Tenant::id(), $nombre, $direccion ?: null, $telefono ?: null]);
        Auditoria::registrar('sede.crear', "Creó la sede \"$nombre\".");
        flashSuccess('Sede creada.');
        redirect('/sedes');
    }

    public function actualizar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $nombre = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;

        if (!$nombre) { flashError('Escribe el nombre de la sede.'); redirect('/sedes'); return; }

        $this->db->prepare('UPDATE sedes SET nombre = ?, direccion = ?, telefono = ?, activa = ? WHERE id = ? AND colegio_id = ?')
                  ->execute([$nombre, $direccion ?: null, $telefono ?: null, $activa, $id, Tenant::id()]);
        Auditoria::registrar('sede.actualizar', "Actualizó la sede #$id.");
        flashSuccess('Sede actualizada.');
        redirect('/sedes');
    }

    public function eliminar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $chk = $this->db->prepare('SELECT COUNT(*) FROM grupos WHERE sede_id = ?');
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            flashError('No se puede eliminar: hay grupos asignados a esta sede.');
            redirect('/sedes');
            return;
        }

        $this->db->prepare('DELETE FROM sedes WHERE id = ? AND colegio_id = ?')->execute([$id, Tenant::id()]);
        Auditoria::registrar('sede.eliminar', "Eliminó la sede #$id.");
        flashSuccess('Sede eliminada.');
        redirect('/sedes');
    }
}
