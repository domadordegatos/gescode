<?php
class FichaEstudianteController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');
        render('admin/ficha_estudiante_buscar');
    }

    public function buscar(): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) { echo json_encode([]); return; }

        $st = $this->db->prepare(
            "SELECT e.id AS estudiante_id, u.nombre, u.apellido, e.documento,
                    (SELECT CONCAT(gr.nombre, ' ', g.nombre)
                     FROM matriculas mt2 JOIN grupos g ON g.id = mt2.grupo_id JOIN grados gr ON gr.id = g.grado_id
                     WHERE mt2.estudiante_id = e.id ORDER BY mt2.id DESC LIMIT 1) AS grupo_actual
             FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id
             WHERE u.colegio_id = ? AND (u.nombre LIKE ? OR u.apellido LIKE ? OR e.documento LIKE ?)
             ORDER BY u.apellido, u.nombre LIMIT 15"
        );
        $like = "%$q%";
        $st->execute([Tenant::id(), $like, $like, $like]);
        echo json_encode($st->fetchAll());
    }

    public function ver(int $estudianteId): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');
        $cid = Tenant::id();

        $qEst = $this->db->prepare(
            'SELECT e.*, u.nombre, u.apellido, u.email
             FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $qEst->execute([$estudianteId, $cid]);
        $estudiante = $qEst->fetch();
        if (!$estudiante) { abort(404); }

        // Matrícula actual (año activo)
        $qMat = $this->db->prepare(
            'SELECT mt.id AS matricula_id, mt.estado, gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano
             FROM matriculas mt JOIN grupos g ON g.id = mt.grupo_id
             JOIN grados gr ON gr.id = g.grado_id JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE mt.estudiante_id = ? AND al.activo = 1 LIMIT 1'
        );
        $qMat->execute([$estudianteId]);
        $matriculaActual = $qMat->fetch();

        // Acudientes
        $qAc = $this->db->prepare(
            'SELECT ac.id AS acudiente_id, ac.parentesco, u.id AS usuario_id, u.nombre, u.apellido, u.email
             FROM acudientes ac JOIN usuarios u ON u.id = ac.usuario_id
             WHERE ac.estudiante_id = ? ORDER BY u.nombre'
        );
        $qAc->execute([$estudianteId]);
        $acudientes = $qAc->fetchAll();
        $acudienteIds = array_column($acudientes, 'usuario_id');

        // Resumen de notas (promedio por materia, período activo)
        $notas = [];
        if ($matriculaActual) {
            $qNotas = $this->db->prepare(
                "SELECT m.nombre AS materia, ROUND(AVG(c.valor), 1) AS promedio
                 FROM calificaciones c
                 JOIN evaluaciones ev ON ev.id = c.evaluacion_id
                 JOIN indicadores_eval ie ON ie.id = ev.indicador_id
                 JOIN asignaciones a ON a.id = ie.asignacion_id
                 JOIN materias m ON m.id = a.materia_id
                 JOIN periodos p ON p.id = ie.periodo_id
                 WHERE c.matricula_id = ? AND p.activo = 1
                 GROUP BY m.id ORDER BY m.nombre"
            );
            $qNotas->execute([$matriculaActual['matricula_id']]);
            $notas = $qNotas->fetchAll();
        }

        // Asistencia resumida (año activo)
        $asistencia = ['presente' => 0, 'tarde' => 0, 'excusa' => 0, 'ausente' => 0, 'incapacidad' => 0];
        if ($matriculaActual) {
            $qAsist = $this->db->prepare("SELECT estado, COUNT(*) AS n FROM asistencia WHERE matricula_id = ? GROUP BY estado");
            $qAsist->execute([$matriculaActual['matricula_id']]);
            foreach ($qAsist->fetchAll() as $r) { if (isset($asistencia[$r['estado']])) $asistencia[$r['estado']] = (int)$r['n']; }
        }

        // Observaciones (todas las matrículas, histórico)
        $qObs = $this->db->prepare(
            "SELECT ob.tipo, ob.descripcion, ob.fecha, u.nombre AS doc_nombre, u.apellido AS doc_apellido, al.nombre AS ano
             FROM observaciones ob
             JOIN matriculas mt ON mt.id = ob.matricula_id
             JOIN grupos g ON g.id = mt.grupo_id JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             JOIN usuarios u ON u.id = ob.docente_id
             WHERE mt.estudiante_id = ? ORDER BY ob.fecha DESC LIMIT 20"
        );
        $qObs->execute([$estudianteId]);
        $observaciones = $qObs->fetchAll();

        // Autorizaciones (participación de este estudiante)
        $autorizaciones = [];
        if ($this->db->query("SELECT permite_autorizaciones FROM colegios WHERE id = $cid")->fetchColumn()) {
            $qAut = $this->db->prepare(
                "SELECT a.titulo, a.tipo, p.firmado, f.created_at AS firmado_at
                 FROM autorizaciones_participantes p
                 JOIN autorizaciones a ON a.id = p.autorizacion_id
                 LEFT JOIN autorizaciones_firmas f ON f.autorizacion_id = a.id AND f.estudiante_id = p.estudiante_id
                 WHERE p.estudiante_id = ? ORDER BY a.created_at DESC"
            );
            $qAut->execute([$estudianteId]);
            $autorizaciones = $qAut->fetchAll();
        }

        // Retiros anticipados de este estudiante
        $qRet = $this->db->prepare(
            "SELECT nombre_persona, parentesco, motivo, anulado, created_at
             FROM retiros_anticipados WHERE estudiante_id = ? ORDER BY created_at DESC"
        );
        $qRet->execute([$estudianteId]);
        $retiros = $qRet->fetchAll();

        // PQRS radicados por cualquiera de sus acudientes
        $pqrs = [];
        if ($acudienteIds) {
            $in = implode(',', array_fill(0, count($acudienteIds), '?'));
            $qPqrs = $this->db->prepare(
                "SELECT radicado, tipo, asunto, estado, created_at FROM pqrs WHERE usuario_id IN ($in) ORDER BY created_at DESC"
            );
            $qPqrs->execute($acudienteIds);
            $pqrs = $qPqrs->fetchAll();
        }

        render('admin/ficha_estudiante', compact(
            'estudiante', 'matriculaActual', 'acudientes', 'notas', 'asistencia',
            'observaciones', 'autorizaciones', 'retiros', 'pqrs'
        ));
    }
}
