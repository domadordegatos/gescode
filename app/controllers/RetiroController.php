<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class RetiroController {
    private PDO $db;

    private const INSTRUCCIONES = [
        'Tócate la oreja derecha con la mano',
        'Levanta dos dedos de tu mano derecha',
        'Levanta tres dedos de tu mano izquierda',
        'Haz una señal de pulgar arriba',
        'Tócate la nariz con un dedo',
        'Haz un saludo con la mano levantada',
        'Tócate el mentón con la mano',
        'Levanta la mano derecha completamente abierta',
        'Haz una V de la victoria con los dedos',
        'Tócate la oreja izquierda con la mano',
    ];

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');
        $activo = (bool)$this->db->query(
            'SELECT permite_retiro_anticipado FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    public function index(): void {
        $this->verificarAcceso();

        $st = $this->db->prepare(
            "SELECT r.id, r.nombre_persona, r.parentesco, r.motivo, r.created_at, r.anulado,
                    eu.nombre AS est_nombre, eu.apellido AS est_apellido,
                    ru.nombre AS reg_nombre, ru.apellido AS reg_apellido
             FROM retiros_anticipados r
             JOIN estudiantes e ON e.id = r.estudiante_id
             JOIN usuarios eu ON eu.id = e.usuario_id
             JOIN usuarios ru ON ru.id = r.registrado_por
             WHERE r.colegio_id = ?
             ORDER BY r.created_at DESC
             LIMIT 100"
        );
        $st->execute([Tenant::id()]);
        render('admin/retiro_lista', ['retiros' => $st->fetchAll()]);
    }

    public function nuevo(): void {
        $this->verificarAcceso();
        render('admin/retiro_buscar');
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

    public function ficha(int $estudianteId): void {
        $this->verificarAcceso();

        $qe = $this->db->prepare(
            'SELECT e.id, u.nombre, u.apellido, e.documento
             FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $qe->execute([$estudianteId, Tenant::id()]);
        $estudiante = $qe->fetch();
        if (!$estudiante) { abort(404); }

        $qa = $this->db->prepare(
            'SELECT u.id, u.nombre, u.apellido, ac.parentesco
             FROM acudientes ac JOIN usuarios u ON u.id = ac.usuario_id
             WHERE ac.estudiante_id = ? ORDER BY u.nombre'
        );
        $qa->execute([$estudianteId]);
        $acudientes = $qa->fetchAll();

        $instruccion = self::INSTRUCCIONES[array_rand(self::INSTRUCCIONES)];

        render('admin/retiro_form', [
            'estudiante' => $estudiante,
            'acudientes' => $acudientes,
            'instruccion' => $instruccion,
        ]);
    }

    public function guardar(int $estudianteId): void {
        $this->verificarAcceso();
        csrfVerify();

        $qe = $this->db->prepare(
            'SELECT e.id FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $qe->execute([$estudianteId, Tenant::id()]);
        if (!$qe->fetch()) { abort(404); }

        $acudienteId = (int)($_POST['acudiente_id'] ?? 0) ?: null;
        $nombrePersona = trim($_POST['nombre_persona'] ?? '');
        $documentoPersona = trim($_POST['documento_persona'] ?? '');
        $parentesco = trim($_POST['parentesco'] ?? '');
        $autorizadoPor = (int)($_POST['autorizado_por_acudiente_id'] ?? 0) ?: null;
        $motivo = trim($_POST['motivo'] ?? '') ?: null;
        $instruccion = trim($_POST['instruccion'] ?? '');
        $fotoBase64 = $_POST['foto_base64'] ?? '';

        if (!$nombrePersona || !$documentoPersona || !$parentesco || !$instruccion || !$fotoBase64) {
            flashError('Completa los datos de quién retira al estudiante y toma la fotografía.');
            redirect('/retiro-anticipado/' . $estudianteId);
            return;
        }

        if (!in_array($instruccion, self::INSTRUCCIONES, true)) {
            flashError('Solicitud inválida. Intenta de nuevo.');
            redirect('/retiro-anticipado/' . $estudianteId);
            return;
        }

        // Si se eligió un acudiente registrado, verificar que realmente sea acudiente de este estudiante
        if ($acudienteId) {
            $chk = $this->db->prepare('SELECT 1 FROM acudientes WHERE estudiante_id = ? AND usuario_id = ? LIMIT 1');
            $chk->execute([$estudianteId, $acudienteId]);
            if (!$chk->fetch()) { $acudienteId = null; }
        }
        if ($autorizadoPor) {
            $chk2 = $this->db->prepare('SELECT 1 FROM acudientes WHERE estudiante_id = ? AND usuario_id = ? LIMIT 1');
            $chk2->execute([$estudianteId, $autorizadoPor]);
            if (!$chk2->fetch()) { $autorizadoPor = null; }
        }

        if (!preg_match('/^data:image\/jpeg;base64,(.+)$/', $fotoBase64, $m)) {
            flashError('No se pudo procesar la fotografía. Intenta de nuevo.');
            redirect('/retiro-anticipado/' . $estudianteId);
            return;
        }
        $binario = base64_decode($m[1], true);
        if ($binario === false || strlen($binario) < 1000 || strlen($binario) > 4 * 1024 * 1024) {
            flashError('La fotografía no es válida. Intenta de nuevo.');
            redirect('/retiro-anticipado/' . $estudianteId);
            return;
        }

        $destDir = ROOT . '/storage/retiros_anticipados/';
        if (!is_dir($destDir)) mkdir($destDir, 0775, true);
        $nombreDisk = 'retiro_' . $estudianteId . '_' . bin2hex(random_bytes(8)) . '.jpg';
        file_put_contents($destDir . $nombreDisk, $binario);

        $this->db->prepare(
            'INSERT INTO retiros_anticipados
                (colegio_id, estudiante_id, acudiente_id, nombre_persona, documento_persona, parentesco,
                 autorizado_por_acudiente_id, motivo, instruccion_reto, foto_nombre_disk, ip, user_agent, registrado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            Tenant::id(), $estudianteId, $acudienteId, $nombrePersona, $documentoPersona, $parentesco,
            $autorizadoPor, $motivo, $instruccion, $nombreDisk,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), Auth::id(),
        ]);

        flashSuccess('Retiro registrado correctamente.');
        redirect('/retiro-anticipado');
    }

    public function verRegistro(int $id): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');

        $q = $this->db->prepare(
            "SELECT r.*, eu.nombre AS est_nombre, eu.apellido AS est_apellido,
                    ru.nombre AS reg_nombre, ru.apellido AS reg_apellido,
                    au.nombre AS autoriza_nombre, au.apellido AS autoriza_apellido
             FROM retiros_anticipados r
             JOIN estudiantes e ON e.id = r.estudiante_id
             JOIN usuarios eu ON eu.id = e.usuario_id
             JOIN usuarios ru ON ru.id = r.registrado_por
             LEFT JOIN usuarios au ON au.id = r.autorizado_por_acudiente_id
             WHERE r.id = ? AND r.colegio_id = ? LIMIT 1"
        );
        $q->execute([$id, Tenant::id()]);
        $retiro = $q->fetch();
        if (!$retiro) { abort(404); }

        render('admin/retiro_detalle', ['retiro' => $retiro]);
    }

    public function exportarPdf(int $id): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');

        $q = $this->db->prepare(
            "SELECT r.*, eu.nombre AS est_nombre, eu.apellido AS est_apellido,
                    ru.nombre AS reg_nombre, ru.apellido AS reg_apellido,
                    au.nombre AS autoriza_nombre, au.apellido AS autoriza_apellido
             FROM retiros_anticipados r
             JOIN estudiantes e ON e.id = r.estudiante_id
             JOIN usuarios eu ON eu.id = e.usuario_id
             JOIN usuarios ru ON ru.id = r.registrado_por
             LEFT JOIN usuarios au ON au.id = r.autorizado_por_acudiente_id
             WHERE r.id = ? AND r.colegio_id = ? LIMIT 1"
        );
        $q->execute([$id, Tenant::id()]);
        $retiro = $q->fetch();
        if (!$retiro) { abort(404); }

        $fotoPath = ROOT . '/storage/retiros_anticipados/' . basename($retiro['foto_nombre_disk']);
        $fotoBase64 = file_exists($fotoPath) ? base64_encode(file_get_contents($fotoPath)) : null;

        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();

        ob_start();
        extract([
            'retiro' => $retiro,
            'fotoBase64' => $fotoBase64,
            'colegio' => $colegio,
            'fecha_emision' => date('d/m/Y H:i'),
        ], EXTR_SKIP);
        include ROOT . '/app/views/admin/retiro_pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream('Retiro_anticipado_' . $id . '.pdf', ['Attachment' => false]);
    }

    public function anularForm(int $id): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');
        $retiro = $this->buscarRetiro($id);
        if (!$retiro) { abort(404); }
        if ($retiro['anulado']) {
            flashError('Este registro ya está anulado.');
            redirect('/retiro-anticipado/registro/' . $id);
            return;
        }
        render('admin/retiro_anular', ['retiro' => $retiro]);
    }

    public function anular(int $id): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');
        csrfVerify();

        $retiro = $this->buscarRetiro($id);
        if (!$retiro) { abort(404); }
        if ($retiro['anulado']) {
            flashError('Este registro ya está anulado.');
            redirect('/retiro-anticipado/registro/' . $id);
            return;
        }

        $motivo = trim($_POST['anulado_motivo'] ?? '');
        if (!$motivo) {
            flashError('Escribe el motivo de la anulación (ej: se registró al estudiante equivocado).');
            redirect('/retiro-anticipado/registro/' . $id . '/anular');
            return;
        }

        $this->db->prepare(
            'UPDATE retiros_anticipados SET anulado = 1, anulado_por = ?, anulado_motivo = ?, anulado_at = NOW() WHERE id = ?'
        )->execute([Auth::id(), $motivo, $id]);

        flashSuccess('Registro anulado. La evidencia original se conserva para trazabilidad.');
        redirect('/retiro-anticipado/registro/' . $id);
    }

    private function buscarRetiro(int $id): ?array {
        $q = $this->db->prepare('SELECT * FROM retiros_anticipados WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, Tenant::id()]);
        return $q->fetch() ?: null;
    }

    public function foto(int $id): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');

        $q = $this->db->prepare('SELECT foto_nombre_disk FROM retiros_anticipados WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, Tenant::id()]);
        $nombreDisk = $q->fetchColumn();
        if (!$nombreDisk) { abort(404); }

        $path = ROOT . '/storage/retiros_anticipados/' . basename($nombreDisk);
        if (!file_exists($path)) { abort(404); }

        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="retiro.jpg"');
        header('Cache-Control: private, no-store');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
