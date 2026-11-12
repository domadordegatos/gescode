<?php
class AccesosController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        $cid = Tenant::id();

        $filtro = $_GET['filtro'] ?? 'todos'; // todos | exitosos | fallidos
        $q = trim($_GET['q'] ?? '');

        $sql = "SELECT a.*, u.nombre, u.apellido
                FROM accesos_log a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.colegio_id = ?";
        $params = [$cid];

        if ($filtro === 'exitosos') { $sql .= ' AND a.exitoso = 1'; }
        elseif ($filtro === 'fallidos') { $sql .= ' AND a.exitoso = 0'; }

        if ($q !== '') {
            $sql .= ' AND (a.username_intentado LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ?)';
            $like = "%$q%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT 300';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $accesos = $st->fetchAll();

        $stFallidos = $this->db->prepare(
            "SELECT COUNT(*) FROM accesos_log WHERE colegio_id = ? AND exitoso = 0 AND created_at >= CURDATE()"
        );
        $stFallidos->execute([$cid]);
        $totalFallidosHoy = (int)$stFallidos->fetchColumn();

        render('admin/accesos_lista', [
            'accesos' => $accesos,
            'filtro' => $filtro,
            'q' => $q,
            'totalFallidosHoy' => $totalFallidosHoy,
        ]);
    }
}
