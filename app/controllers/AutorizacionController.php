<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class AutorizacionController {
    private PDO $db;

    private const INSTRUCCIONES = [
        'Tócate la oreja derecha con la mano',
        'Levanta dos dedos de tu mano derecha',
        'Levanta tres dedos de tu mano izquierda',
        'Haz una señal de "pulgar arriba"',
        'Tócate la nariz con un dedo',
        'Haz un saludo con la mano levantada',
        'Tócate el mentón con la mano',
        'Levanta la mano derecha completamente abierta',
        'Haz una "V" de la victoria con los dedos',
        'Tócate la oreja izquierda con la mano',
    ];

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'padre');
        $activo = (bool)$this->db->query(
            'SELECT permite_autorizaciones FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    private function esGestor(): bool {
        return in_array(Auth::rol(), ['admin', 'soporte'], true);
    }

    // ── Listado ─────────────────────────────────────────────────
    public function index(): void {
        $this->verificarAcceso();

        if ($this->esGestor()) {
            $st = $this->db->prepare(
                "SELECT a.*,
                        (SELECT COUNT(*) FROM autorizaciones_participantes p WHERE p.autorizacion_id = a.id) AS total,
                        (SELECT COUNT(*) FROM autorizaciones_participantes p WHERE p.autorizacion_id = a.id AND p.firmado = 1) AS total_firmados
                 FROM autorizaciones a WHERE a.colegio_id = ? ORDER BY a.created_at DESC"
            );
            $st->execute([Tenant::id()]);
            $autorizaciones = $st->fetchAll();

            render('comunicaciones/autorizaciones_lista', [
                'autorizaciones' => $autorizaciones,
                'esGestor' => true,
            ]);
            return;
        }

        // Padre: una fila por hijo × autorización pendiente o firmada
        $st = $this->db->prepare(
            "SELECT a.id AS autorizacion_id, a.tipo, a.titulo, a.fecha_evento, a.activa,
                    e.id AS estudiante_id, u.nombre AS hijo_nombre, u.apellido AS hijo_apellido,
                    p.firmado, f.created_at AS firmado_at
             FROM autorizaciones_participantes p
             JOIN autorizaciones a ON a.id = p.autorizacion_id
             JOIN estudiantes e ON e.id = p.estudiante_id
             JOIN usuarios u ON u.id = e.usuario_id
             JOIN acudientes ac ON ac.estudiante_id = e.id
             LEFT JOIN autorizaciones_firmas f ON f.autorizacion_id = a.id AND f.estudiante_id = e.id
             WHERE ac.usuario_id = ?
             ORDER BY a.created_at DESC"
        );
        $st->execute([Auth::id()]);
        $filas = $st->fetchAll();

        render('padre/autorizaciones_lista', ['filas' => $filas]);
    }

    // ── Crear (solo admin/soporte) ─────────────────────────────
    public function nueva(): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        $grados = $this->db->query('SELECT id, nombre FROM grados WHERE activo = 1 ORDER BY nivel')->fetchAll();
        render('comunicaciones/autorizacion_form', ['grados' => $grados]);
    }

    public function guardar(): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $tipo = $_POST['tipo'] ?? 'otro';
        if (!in_array($tipo, ['salida_pedagogica', 'uso_imagen', 'otro'], true)) $tipo = 'otro';
        $titulo = trim($_POST['titulo'] ?? '');
        $texto = trim($_POST['texto_consentimiento'] ?? '');
        $fechaEvento = trim($_POST['fecha_evento'] ?? '') ?: null;
        $audiencia = $_POST['audiencia'] ?? 'todos';
        if (!in_array($audiencia, ['todos', 'grado'], true)) $audiencia = 'todos';
        $gradoId = (int)($_POST['grado_id'] ?? 0) ?: null;
        if ($audiencia !== 'grado') $gradoId = null;

        if (!$titulo || !$texto || ($audiencia === 'grado' && !$gradoId)) {
            flashError('Completa el título, el texto del consentimiento y, si aplica, el grado.');
            redirect('/autorizaciones/nueva');
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO autorizaciones (colegio_id, tipo, titulo, texto_consentimiento, fecha_evento, audiencia, grado_id, creado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([Tenant::id(), $tipo, $titulo, $texto, $fechaEvento, $audiencia, $gradoId, Auth::id()]);
            $autorizacionId = (int)$this->db->lastInsertId();

            $sqlEst = "SELECT e.id FROM estudiantes e
                       JOIN usuarios u ON u.id = e.usuario_id
                       JOIN matriculas mt ON mt.estudiante_id = e.id
                       JOIN grupos g ON g.id = mt.grupo_id
                       JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
                       WHERE u.colegio_id = ? AND al.activo = 1 AND mt.estado = 'activo'";
            $params = [Tenant::id()];
            if ($audiencia === 'grado') {
                $sqlEst .= ' AND g.grado_id = ?';
                $params[] = $gradoId;
            }
            $stEst = $this->db->prepare($sqlEst);
            $stEst->execute($params);
            $estudianteIds = $stEst->fetchAll(PDO::FETCH_COLUMN);

            $ins = $this->db->prepare(
                'INSERT IGNORE INTO autorizaciones_participantes (autorizacion_id, estudiante_id) VALUES (?, ?)'
            );
            foreach ($estudianteIds as $eid) {
                $ins->execute([$autorizacionId, $eid]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudo crear la autorización. Intenta de nuevo.');
            redirect('/autorizaciones/nueva');
            return;
        }

        flashSuccess('Autorización creada para ' . count($estudianteIds) . ' estudiante(s).');
        redirect('/autorizaciones');
    }

    /** Edición solo permitida mientras NADIE haya firmado (para no reescribir evidencia legal ya aceptada) */
    private function autorizacionEditable(int $id): ?array {
        $q = $this->db->prepare('SELECT * FROM autorizaciones WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, Tenant::id()]);
        $a = $q->fetch();
        if (!$a) return null;

        $chk = $this->db->prepare('SELECT COUNT(*) FROM autorizaciones_firmas WHERE autorizacion_id = ?');
        $chk->execute([$id]);
        $a['total_firmas'] = (int)$chk->fetchColumn();
        return $a;
    }

    public function editarForm(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');

        $a = $this->autorizacionEditable($id);
        if (!$a) { abort(404); }

        $grados = $this->db->query('SELECT id, nombre FROM grados WHERE activo = 1 ORDER BY nivel')->fetchAll();
        render('comunicaciones/autorizacion_editar', ['autorizacion' => $a, 'grados' => $grados]);
    }

    public function actualizar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $a = $this->autorizacionEditable($id);
        if (!$a) { abort(404); }
        if ($a['total_firmas'] > 0) {
            flashError('Esta autorización ya tiene firmas registradas; no se puede modificar el texto (protege la validez legal de lo ya firmado). Puedes cerrarla y crear una nueva.');
            redirect('/autorizaciones');
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $texto = trim($_POST['texto_consentimiento'] ?? '');
        $fechaEvento = trim($_POST['fecha_evento'] ?? '') ?: null;

        if (!$titulo || !$texto) {
            flashError('Completa el título y el texto del consentimiento.');
            redirect('/autorizaciones/' . $id . '/editar');
            return;
        }

        $this->db->prepare(
            'UPDATE autorizaciones SET titulo = ?, texto_consentimiento = ?, fecha_evento = ? WHERE id = ?'
        )->execute([$titulo, $texto, $fechaEvento, $id]);

        flashSuccess('Autorización actualizada.');
        redirect('/autorizaciones');
    }

    public function eliminar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $a = $this->autorizacionEditable($id);
        if (!$a) { abort(404); }
        if ($a['total_firmas'] > 0) {
            flashError('Esta autorización ya tiene firmas registradas y no se puede eliminar (son evidencia legal). Puedes cerrarla en su lugar.');
            redirect('/autorizaciones');
            return;
        }

        $this->db->prepare('DELETE FROM autorizaciones_participantes WHERE autorizacion_id = ?')->execute([$id]);
        $this->db->prepare('DELETE FROM autorizaciones WHERE id = ?')->execute([$id]);

        flashSuccess('Autorización eliminada (no tenía firmas registradas).');
        redirect('/autorizaciones');
    }

    public function cerrar(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $this->db->prepare('UPDATE autorizaciones SET activa = 0 WHERE id = ? AND colegio_id = ?')
                  ->execute([$id, Tenant::id()]);

        flashSuccess('Autorización cerrada.');
        redirect('/autorizaciones');
    }

    // ── Panel de seguimiento (admin/soporte) ────────────────────
    public function seguimiento(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('admin', 'soporte');

        $qa = $this->db->prepare('SELECT * FROM autorizaciones WHERE id = ? AND colegio_id = ? LIMIT 1');
        $qa->execute([$id, Tenant::id()]);
        $autorizacion = $qa->fetch();
        if (!$autorizacion) { abort(404); }

        $qp = $this->db->prepare(
            "SELECT e.id AS estudiante_id, u.nombre, u.apellido, p.firmado,
                    f.id AS firma_id, f.created_at AS firmado_at,
                    fu.nombre AS padre_nombre, fu.apellido AS padre_apellido
             FROM autorizaciones_participantes p
             JOIN estudiantes e ON e.id = p.estudiante_id
             JOIN usuarios u ON u.id = e.usuario_id
             LEFT JOIN autorizaciones_firmas f ON f.autorizacion_id = p.autorizacion_id AND f.estudiante_id = p.estudiante_id
             LEFT JOIN usuarios fu ON fu.id = f.padre_id
             WHERE p.autorizacion_id = ?
             ORDER BY p.firmado ASC, u.apellido"
        );
        $qp->execute([$id]);
        $participantes = $qp->fetchAll();

        render('comunicaciones/autorizacion_seguimiento', [
            'autorizacion' => $autorizacion,
            'participantes' => $participantes,
        ]);
    }

    /** Ver el detalle/evidencia de una firma puntual (admin/soporte, solo auditoría) */
    public function verFirma(int $firmaId): void {
        Auth::requireRole('admin', 'soporte');

        $q = $this->db->prepare(
            "SELECT f.*, a.titulo, u.nombre AS padre_nombre, u.apellido AS padre_apellido,
                    eu.nombre AS est_nombre, eu.apellido AS est_apellido
             FROM autorizaciones_firmas f
             JOIN autorizaciones a ON a.id = f.autorizacion_id
             JOIN usuarios u ON u.id = f.padre_id
             JOIN estudiantes e ON e.id = f.estudiante_id
             JOIN usuarios eu ON eu.id = e.usuario_id
             WHERE f.id = ? AND a.colegio_id = ? LIMIT 1"
        );
        $q->execute([$firmaId, Tenant::id()]);
        $firma = $q->fetch();
        if (!$firma) { abort(404); }

        render('comunicaciones/autorizacion_firma_detalle', ['firma' => $firma]);
    }

    public function exportarFirmaPdf(int $firmaId): void {
        Auth::requireRole('admin', 'soporte');

        $q = $this->db->prepare(
            "SELECT f.*, a.titulo, a.tipo, u.nombre AS padre_nombre, u.apellido AS padre_apellido,
                    eu.nombre AS est_nombre, eu.apellido AS est_apellido
             FROM autorizaciones_firmas f
             JOIN autorizaciones a ON a.id = f.autorizacion_id
             JOIN usuarios u ON u.id = f.padre_id
             JOIN estudiantes e ON e.id = f.estudiante_id
             JOIN usuarios eu ON eu.id = e.usuario_id
             WHERE f.id = ? AND a.colegio_id = ? LIMIT 1"
        );
        $q->execute([$firmaId, Tenant::id()]);
        $firma = $q->fetch();
        if (!$firma) { abort(404); }

        $fotoPath = ROOT . '/storage/firmas_autorizaciones/' . basename($firma['foto_nombre_disk']);
        $fotoBase64 = file_exists($fotoPath) ? base64_encode(file_get_contents($fotoPath)) : null;

        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();

        ob_start();
        extract([
            'firma' => $firma,
            'fotoBase64' => $fotoBase64,
            'colegio' => $colegio,
            'fecha_emision' => date('d/m/Y H:i'),
        ], EXTR_SKIP);
        include ROOT . '/app/views/comunicaciones/evidencia_firma_pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'Evidencia_autorizacion_' . $firmaId . '.pdf';
        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream($nombreArchivo, ['Attachment' => false]);
    }

    /** Sirve la foto de evidencia desde almacenamiento protegido (fuera de public/) */
    public function fotoFirma(int $firmaId): void {
        Auth::requireRole('admin', 'soporte');

        $q = $this->db->prepare(
            'SELECT f.foto_nombre_disk FROM autorizaciones_firmas f
             JOIN autorizaciones a ON a.id = f.autorizacion_id
             WHERE f.id = ? AND a.colegio_id = ? LIMIT 1'
        );
        $q->execute([$firmaId, Tenant::id()]);
        $nombreDisk = $q->fetchColumn();
        if (!$nombreDisk) { abort(404); }

        $path = ROOT . '/storage/firmas_autorizaciones/' . basename($nombreDisk);
        if (!file_exists($path)) { abort(404); }

        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="firma.jpg"');
        header('Cache-Control: private, no-store');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // ── PADRE: firmar ────────────────────────────────────────────
    private function hijoYAutorizacion(int $estudianteId, int $autorizacionId): ?array {
        $q = $this->db->prepare(
            "SELECT a.*, e.id AS estudiante_id, u.nombre AS hijo_nombre, u.apellido AS hijo_apellido, p.firmado
             FROM autorizaciones_participantes p
             JOIN autorizaciones a ON a.id = p.autorizacion_id
             JOIN estudiantes e ON e.id = p.estudiante_id
             JOIN usuarios u ON u.id = e.usuario_id
             JOIN acudientes ac ON ac.estudiante_id = e.id
             WHERE p.autorizacion_id = ? AND p.estudiante_id = ? AND ac.usuario_id = ?
             LIMIT 1"
        );
        $q->execute([$autorizacionId, $estudianteId, Auth::id()]);
        $fila = $q->fetch();
        return $fila ?: null;
    }

    public function firmarForm(int $estudianteId, int $autorizacionId): void {
        $this->verificarAcceso();
        Auth::requireRole('padre');

        $datos = $this->hijoYAutorizacion($estudianteId, $autorizacionId);
        if (!$datos) { abort(403); }
        if ($datos['firmado']) {
            flashError('Esta autorización ya fue firmada.');
            redirect('/autorizaciones');
            return;
        }
        if (!$datos['activa']) {
            flashError('Esta autorización ya no está activa.');
            redirect('/autorizaciones');
            return;
        }

        $padre = $this->db->query('SELECT nombre, apellido FROM usuarios WHERE id = ' . Auth::id())->fetch();
        $perfil = $this->db->prepare('SELECT documento FROM perfiles WHERE usuario_id = ?');
        $perfil->execute([Auth::id()]);
        $documentoPadre = $perfil->fetchColumn() ?: null;

        $instruccion = self::INSTRUCCIONES[array_rand(self::INSTRUCCIONES)];

        $textoFinal = str_replace(
            ['[NOMBRE_HIJO]', '[NOMBRE_PADRE]'],
            [$datos['hijo_nombre'] . ' ' . $datos['hijo_apellido'], $padre['nombre'] . ' ' . $padre['apellido']],
            $datos['texto_consentimiento']
        );

        render('padre/autorizacion_firmar', [
            'autorizacion' => $datos,
            'estudianteId' => $estudianteId,
            'textoFinal' => $textoFinal,
            'instruccion' => $instruccion,
            'documentoPadre' => $documentoPadre,
        ]);
    }

    public function firmarGuardar(int $estudianteId, int $autorizacionId): void {
        $this->verificarAcceso();
        Auth::requireRole('padre');
        csrfVerify();

        $datos = $this->hijoYAutorizacion($estudianteId, $autorizacionId);
        if (!$datos) { abort(403); }
        if ($datos['firmado'] || !$datos['activa']) {
            flashError('Esta autorización ya no admite firma.');
            redirect('/autorizaciones');
            return;
        }

        $documento = trim($_POST['documento_confirmado'] ?? '');
        $instruccion = trim($_POST['instruccion'] ?? '');
        $fotoBase64 = $_POST['foto_base64'] ?? '';

        if (!$documento || !$instruccion || !$fotoBase64) {
            flashError('Debes escribir tu documento y tomar la fotografía antes de firmar.');
            redirect('/autorizaciones/firmar/' . $estudianteId . '/' . $autorizacionId);
            return;
        }

        // El texto exacto que se acepta se reconstruye SIEMPRE en el servidor (nunca se confía
        // en lo que el navegador envíe de vuelta), para que no se pueda alterar antes de enviar.
        $padre = $this->db->query('SELECT nombre, apellido FROM usuarios WHERE id = ' . Auth::id())->fetch();
        $textoFinal = str_replace(
            ['[NOMBRE_HIJO]', '[NOMBRE_PADRE]'],
            [$datos['hijo_nombre'] . ' ' . $datos['hijo_apellido'], $padre['nombre'] . ' ' . $padre['apellido']],
            $datos['texto_consentimiento']
        );

        // Si el acudiente tiene documento registrado en su perfil, debe coincidir exactamente
        // con lo que escribió al firmar (defensa adicional contra suplantación).
        $qDoc = $this->db->prepare('SELECT documento FROM perfiles WHERE usuario_id = ?');
        $qDoc->execute([Auth::id()]);
        $documentoReal = $qDoc->fetchColumn();
        if ($documentoReal && trim($documentoReal) !== $documento) {
            flashError('El documento ingresado no coincide con el registrado en tu perfil.');
            redirect('/autorizaciones/firmar/' . $estudianteId . '/' . $autorizacionId);
            return;
        }

        // La instrucción del reto también debe ser una de las conocidas por el sistema
        // (no un valor arbitrario inyectado desde el cliente).
        if (!in_array($instruccion, self::INSTRUCCIONES, true)) {
            flashError('Solicitud inválida. Intenta de nuevo.');
            redirect('/autorizaciones/firmar/' . $estudianteId . '/' . $autorizacionId);
            return;
        }

        // Decodificar y validar la imagen (debe venir como data URL base64 de un <canvas>, nunca un archivo subido)
        if (!preg_match('/^data:image\/jpeg;base64,(.+)$/', $fotoBase64, $m)) {
            flashError('No se pudo procesar la fotografía. Intenta de nuevo.');
            redirect('/autorizaciones/firmar/' . $estudianteId . '/' . $autorizacionId);
            return;
        }
        $binario = base64_decode($m[1], true);
        if ($binario === false || strlen($binario) < 1000 || strlen($binario) > 4 * 1024 * 1024) {
            flashError('La fotografía no es válida. Intenta de nuevo.');
            redirect('/autorizaciones/firmar/' . $estudianteId . '/' . $autorizacionId);
            return;
        }

        $destDir = ROOT . '/storage/firmas_autorizaciones/';
        if (!is_dir($destDir)) mkdir($destDir, 0775, true);
        $nombreDisk = 'firma_' . $autorizacionId . '_' . $estudianteId . '_' . bin2hex(random_bytes(8)) . '.jpg';
        file_put_contents($destDir . $nombreDisk, $binario);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO autorizaciones_firmas
                    (autorizacion_id, estudiante_id, padre_id, texto_aceptado, documento_confirmado, instruccion_reto, foto_nombre_disk, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$autorizacionId, $estudianteId, Auth::id(), $textoFinal, $documento, $instruccion, $nombreDisk, $ip, $ua]);

            $this->db->prepare(
                'UPDATE autorizaciones_participantes SET firmado = 1 WHERE autorizacion_id = ? AND estudiante_id = ?'
            )->execute([$autorizacionId, $estudianteId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            @unlink($destDir . $nombreDisk);
            flashError('No se pudo registrar la firma. Intenta de nuevo.');
            redirect('/autorizaciones/firmar/' . $estudianteId . '/' . $autorizacionId);
            return;
        }

        flashSuccess('Autorización firmada correctamente.');
        redirect('/autorizaciones');
    }
}
