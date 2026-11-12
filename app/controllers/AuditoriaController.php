<?php
class AuditoriaController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        $cid = Tenant::id();

        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT a.*, u.nombre, u.apellido
                FROM auditoria_admin a JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.colegio_id = ?";
        $params = [$cid];
        if ($q) {
            $sql .= ' AND (a.accion LIKE ? OR a.detalle LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like, $like);
        }
        $sql .= ' ORDER BY a.created_at DESC LIMIT 300';

        $st = $this->db->prepare($sql);
        $st->execute($params);

        render('admin/auditoria_lista', ['registros' => $st->fetchAll(), 'q' => $q]);
    }
}
