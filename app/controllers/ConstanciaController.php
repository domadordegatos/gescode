<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class ConstanciaController {
    private PDO $db;

    private const TIPOS = [
        'estudio'         => 'Constancia de estudio',
        'notas_simple'    => 'Certificado de notas (resumen)',
        'buena_conducta'  => 'Certificado de buena conducta',
        'paz_y_salvo'     => 'Paz y salvo',
    ];

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte');
        $activo = (bool)$this->db->query(
            'SELECT permite_constancias FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    public function index(): void {
        $this->verificarAcceso();
        render('admin/constancias_buscar');
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

    private function datosEstudiante(int $estudianteId): ?array {
        $q = $this->db->prepare(
            'SELECT mt.id AS matricula_id, mt.grupo_id, mt.estado AS estado_matricula,
                    e.documento, e.tipo_documento, u.nombre, u.apellido,
                    gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano
             FROM matriculas mt
             JOIN estudiantes e   ON mt.estudiante_id = e.id
             JOIN usuarios u      ON u.id = e.usuario_id
             JOIN grupos g        ON g.id = mt.grupo_id
             JOIN grados gr       ON gr.id = g.grado_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE e.id = ? AND u.colegio_id = ?
             ORDER BY mt.id DESC LIMIT 1'
        );
        $q->execute([$estudianteId, Tenant::id()]);
        $fila = $q->fetch();
        return $fila ?: null;
    }

    public function ficha(int $estudianteId): void {
        $this->verificarAcceso();
        $estudiante = $this->datosEstudiante($estudianteId);
        if (!$estudiante) { abort(404); }

        $qh = $this->db->prepare(
            'SELECT c.id, c.tipo, c.created_at, u.nombre AS gen_nombre, u.apellido AS gen_apellido
             FROM constancias c JOIN usuarios u ON u.id = c.generado_por
             WHERE c.estudiante_id = ? ORDER BY c.created_at DESC LIMIT 10'
        );
        $qh->execute([$estudianteId]);
        $historial = $qh->fetchAll();

        render('admin/constancias_ficha', [
            'estudiante'   => $estudiante,
            'estudianteId' => $estudianteId,
            'tipos'        => self::TIPOS,
            'historial'    => $historial,
        ]);
    }

    public function generar(int $estudianteId): void {
        $this->verificarAcceso();
        csrfVerify();

        $estudiante = $this->datosEstudiante($estudianteId);
        if (!$estudiante) { abort(404); }

        $tipo = $_POST['tipo'] ?? 'estudio';
        if (!isset(self::TIPOS[$tipo])) $tipo = 'estudio';
        $textoExtra = trim($_POST['texto_extra'] ?? '');

        $this->db->prepare(
            'INSERT INTO constancias (estudiante_id, matricula_id, tipo, texto_extra, generado_por)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$estudianteId, (int)$estudiante['matricula_id'], $tipo, $textoExtra ?: null, Auth::id()]);
        $radicado = (int)$this->db->lastInsertId();

        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();

        ob_start();
        extract([
            'estudiante'  => $estudiante,
            'colegio'     => $colegio,
            'tipo'        => $tipo,
            'tituloTipo'  => self::TIPOS[$tipo],
            'textoExtra'  => $textoExtra,
            'fecha_emision' => date('d/m/Y'),
            'radicado'    => $radicado,
        ], EXTR_SKIP);
        include ROOT . '/app/views/admin/constancia_pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = preg_replace('/[^A-Za-z0-9_]/', '_', self::TIPOS[$tipo]) . '_' . preg_replace('/[^A-Za-z0-9_]/', '_', $estudiante['apellido'] . '_' . $estudiante['nombre']) . '.pdf';
        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream($nombreArchivo, ['Attachment' => false]);
    }
}
