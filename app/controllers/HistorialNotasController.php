<?php
class HistorialNotasController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        render('admin/historial_notas_buscar');
    }

    public function buscar(): void {
        Auth::requireRole('admin', 'soporte');
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) { echo json_encode([]); return; }

        $st = $this->db->prepare(
            "SELECT e.id AS estudiante_id, u.nombre, u.apellido, e.documento,
                    (SELECT CONCAT(gr.nombre, ' ', g.nombre)
                     FROM matriculas mt2
                     JOIN grupos g ON g.id = mt2.grupo_id
                     JOIN grados gr ON gr.id = g.grado_id
                     WHERE mt2.estudiante_id = e.id
                     ORDER BY mt2.id DESC LIMIT 1) AS grupo_actual
             FROM estudiantes e
             JOIN usuarios u ON u.id = e.usuario_id
             WHERE u.colegio_id = ? AND (u.nombre LIKE ? OR u.apellido LIKE ? OR e.documento LIKE ?)
             ORDER BY u.apellido, u.nombre
             LIMIT 15"
        );
        $like = "%$q%";
        $st->execute([Tenant::id(), $like, $like, $like]);
        echo json_encode($st->fetchAll());
    }

    public function estudiante(int $estudianteId): void {
        Auth::requireRole('admin', 'soporte');

        $qEst = $this->db->prepare(
            'SELECT e.id, u.nombre, u.apellido, e.documento
             FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $qEst->execute([$estudianteId, Tenant::id()]);
        $estudiante = $qEst->fetch();
        if (!$estudiante) { abort(404); }

        // Historial de TODAS las matrículas del estudiante (multi-año), vía las calificaciones
        // asociadas a cada una de sus matrículas.
        $qh = $this->db->prepare(
            "SELECT h.id, h.valor_anterior, h.valor_nuevo, h.accion, h.created_at,
                    m.nombre AS materia, p.nombre AS periodo, al.nombre AS ano,
                    ev.nombre AS evaluacion, ie.descripcion AS logro,
                    uu.nombre AS mod_nombre, uu.apellido AS mod_apellido, uu.rol AS mod_rol
             FROM calificaciones_historial h
             JOIN evaluaciones ev      ON h.evaluacion_id = ev.id
             JOIN indicadores_eval ie  ON ev.indicador_id = ie.id
             JOIN asignaciones a       ON ie.asignacion_id = a.id
             JOIN materias m           ON a.materia_id = m.id
             JOIN periodos p           ON ie.periodo_id = p.id
             JOIN matriculas mt        ON h.matricula_id = mt.id
             JOIN grupos g             ON mt.grupo_id = g.id
             JOIN anos_lectivos al     ON g.ano_lectivo_id = al.id
             JOIN usuarios uu          ON h.modificado_por = uu.id
             WHERE mt.estudiante_id = ?
             ORDER BY h.created_at DESC
             LIMIT 300"
        );
        $qh->execute([$estudianteId]);
        $historial = $qh->fetchAll();

        render('admin/historial_notas_estudiante', compact('estudiante', 'historial'));
    }
}
