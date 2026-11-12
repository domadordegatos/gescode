<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class ObservadorController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte');
        $activo = (bool)$this->db->query(
            'SELECT permite_observador_consolidado FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    // ── Buscador de estudiantes ──────────────────────────────────
    public function index(): void {
        $this->verificarAcceso();
        render('admin/observador_buscar');
    }

    public function buscar(): void {
        $this->verificarAcceso();
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

    // ── Línea de tiempo consolidada de un estudiante ─────────────
    public function estudiante(int $estudianteId): void {
        $this->verificarAcceso();

        $est = $this->db->prepare(
            'SELECT e.id, u.nombre, u.apellido, u.foto, e.documento, e.tipo_documento
             FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $est->execute([$estudianteId, Tenant::id()]);
        $estudiante = $est->fetch();
        if (!$estudiante) { abort(404); }

        // Todas las observaciones del estudiante, a través de TODAS sus matrículas
        // (todos los años), con el año lectivo y grupo de cada una para contexto.
        $obs = $this->db->prepare(
            'SELECT ob.id, ob.tipo, ob.descripcion, ob.fecha, ob.created_at,
                    du.nombre AS doc_nombre, du.apellido AS doc_apellido, du.rol AS doc_rol,
                    gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano
             FROM observaciones ob
             JOIN matriculas mt ON ob.matricula_id = mt.id
             JOIN grupos g      ON mt.grupo_id = g.id
             JOIN grados gr     ON g.grado_id = gr.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             JOIN usuarios du   ON ob.docente_id = du.id
             WHERE mt.estudiante_id = ?
             ORDER BY ob.fecha DESC, ob.id DESC'
        );
        $obs->execute([$estudianteId]);
        $timeline = $obs->fetchAll();

        // Matrícula vigente (la más reciente), para registrar nuevas anotaciones
        $matAct = $this->db->prepare(
            'SELECT id FROM matriculas WHERE estudiante_id = ? ORDER BY id DESC LIMIT 1'
        );
        $matAct->execute([$estudianteId]);
        $matriculaActual = (int)$matAct->fetchColumn();

        render('admin/observador_timeline', compact('estudiante', 'timeline', 'matriculaActual'));
    }

    // ── Registrar anotación disciplinaria (solo admin/soporte) ──
    public function guardarAnotacion(int $estudianteId): void {
        $this->verificarAcceso();
        csrfVerify();

        $matId = (int)($_POST['matricula_id'] ?? 0);
        $desc  = trim($_POST['descripcion'] ?? '');

        // Verificar que la matrícula pertenece realmente a este estudiante
        $chk = $this->db->prepare('SELECT id FROM matriculas WHERE id = ? AND estudiante_id = ? LIMIT 1');
        $chk->execute([$matId, $estudianteId]);
        if (!$chk->fetch() || !$desc) {
            flashError('Completa la anotación antes de guardar.');
            redirect("/observador/$estudianteId");
            return;
        }

        $this->db->prepare(
            "INSERT INTO observaciones (matricula_id, docente_id, tipo, descripcion, fecha)
             VALUES (?, ?, 'disciplinaria', ?, CURDATE())"
        )->execute([$matId, Auth::id(), $desc]);

        flashSuccess('Anotación registrada en el observador del estudiante.');
        redirect("/observador/$estudianteId");
    }

    // ── Hoja de vida del estudiante en PDF (consolidado multi-año) ──
    public function exportarPdf(int $estudianteId): void {
        $this->verificarAcceso();

        $est = $this->db->prepare(
            'SELECT e.id, u.nombre, u.apellido, e.documento, e.tipo_documento, e.fecha_nacimiento, e.genero
             FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $est->execute([$estudianteId, Tenant::id()]);
        $estudiante = $est->fetch();
        if (!$estudiante) { abort(404); }

        $hist = $this->db->prepare(
            'SELECT mt.estado, gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano
             FROM matriculas mt
             JOIN grupos g ON mt.grupo_id = g.id
             JOIN grados gr ON g.grado_id = gr.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE mt.estudiante_id = ?
             ORDER BY al.nombre DESC'
        );
        $hist->execute([$estudianteId]);
        $historialMatriculas = $hist->fetchAll();

        $obs = $this->db->prepare(
            'SELECT ob.id, ob.tipo, ob.descripcion, ob.fecha,
                    du.nombre AS doc_nombre, du.apellido AS doc_apellido,
                    gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano
             FROM observaciones ob
             JOIN matriculas mt ON ob.matricula_id = mt.id
             JOIN grupos g      ON mt.grupo_id = g.id
             JOIN grados gr     ON g.grado_id = gr.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             JOIN usuarios du   ON ob.docente_id = du.id
             WHERE mt.estudiante_id = ?
             ORDER BY ob.fecha DESC, ob.id DESC'
        );
        $obs->execute([$estudianteId]);
        $timeline = $obs->fetchAll();

        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();

        ob_start();
        extract([
            'estudiante'  => $estudiante,
            'colegio'     => $colegio,
            'historialMatriculas' => $historialMatriculas,
            'timeline'    => $timeline,
            'fecha_emision' => date('d/m/Y'),
        ], EXTR_SKIP);
        include ROOT . '/app/views/admin/observador_pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'Hoja_de_vida_' . preg_replace('/[^A-Za-z0-9_]/', '_', $estudiante['apellido'] . '_' . $estudiante['nombre']) . '.pdf';
        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream($nombreArchivo, ['Attachment' => false]);
    }
}
