<?php
class ComunidadController {
    private PDO $db;
    private string $storageDir;

    private const MIME_ICONS = [
        'application/pdf'                                                  => 'mdi-file-pdf-box',
        'application/msword'                                               => 'mdi-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'mdi-file-word',
        'application/vnd.ms-excel'                                         => 'mdi-file-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'mdi-file-excel',
        'application/vnd.ms-powerpoint'                                    => 'mdi-file-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'mdi-file-powerpoint',
        'image/jpeg'  => 'mdi-file-image',
        'image/png'   => 'mdi-file-image',
        'image/gif'   => 'mdi-file-image',
        'image/webp'  => 'mdi-file-image',
        'text/plain'  => 'mdi-file-document-outline',
        'text/csv'    => 'mdi-file-delimited',
        'application/zip'  => 'mdi-folder-zip',
        'application/x-rar-compressed' => 'mdi-folder-zip',
    ];

    private const MIME_COLORS = [
        'mdi-file-pdf-box'       => '#DC2626',
        'mdi-file-word'          => '#2563EB',
        'mdi-file-excel'         => '#16A34A',
        'mdi-file-powerpoint'    => '#EA580C',
        'mdi-file-image'         => '#7C3AED',
        'mdi-file-document-outline' => '#6B7280',
        'mdi-file-delimited'     => '#0891B2',
        'mdi-folder-zip'         => '#B45309',
    ];

    public function __construct() {
        $this->db         = Database::get();
        $this->storageDir = dirname(__DIR__, 2) . '/storage/comunidad/';
    }

    // ── Vista pública ────────────────────────────────────────────
    public function index(): void {
        Auth::require();
        $noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
        $categorias = $this->getCategorias(soloActivas: true);
        render('comunidad/index', compact('categorias', 'noLeidos'));
    }

    // ── Panel de gestión (soporte/admin) ─────────────────────────
    public function admin(): void {
        Auth::requireRole('soporte','admin');
        $noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
        $categorias = $this->getCategorias(soloActivas: false);
        render('comunidad/admin', compact('categorias', 'noLeidos'));
    }

    // ── Crear categoría ──────────────────────────────────────────
    public function crearCategoria(): void {
        Auth::requireRole('soporte','admin');
        csrfVerify();
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { flashError('El nombre es obligatorio.'); redirect('/comunidad/admin'); return; }

        $this->db->prepare(
            'INSERT INTO comunidad_categorias (nombre, descripcion, orden, creado_por) VALUES (?,?,?,?)'
        )->execute([$nombre, trim($_POST['descripcion'] ?? ''), (int)($_POST['orden'] ?? 0), Auth::id()]);

        flashSuccess('Categoría creada.');
        redirect('/comunidad/admin');
    }

    // ── Editar categoría ─────────────────────────────────────────
    public function editarCategoria(string $id): void {
        Auth::requireRole('soporte','admin');
        csrfVerify();
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { flashError('El nombre es obligatorio.'); redirect('/comunidad/admin'); return; }

        $this->db->prepare(
            'UPDATE comunidad_categorias SET nombre=?, descripcion=?, orden=?, activo=? WHERE id=?'
        )->execute([
            $nombre,
            trim($_POST['descripcion'] ?? ''),
            (int)($_POST['orden'] ?? 0),
            isset($_POST['activo']) ? 1 : 0,
            (int)$id,
        ]);

        flashSuccess('Categoría actualizada.');
        redirect('/comunidad/admin');
    }

    // ── Eliminar categoría ───────────────────────────────────────
    public function eliminarCategoria(string $id): void {
        Auth::requireRole('soporte','admin');
        csrfVerify();

        // Borrar archivos en disco
        $archivos = $this->db->prepare('SELECT archivo_disk FROM comunidad_archivos WHERE categoria_id=?');
        $archivos->execute([(int)$id]);
        foreach ($archivos->fetchAll() as $a) {
            @unlink($this->storageDir . $a['archivo_disk']);
        }
        $this->db->prepare('DELETE FROM comunidad_categorias WHERE id=?')->execute([(int)$id]);
        flashSuccess('Categoría eliminada.');
        redirect('/comunidad/admin');
    }

    // ── Subir archivo ────────────────────────────────────────────
    public function subirArchivo(): void {
        Auth::requireRole('soporte','admin');
        csrfVerify();

        $catId = (int)($_POST['categoria_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');

        if (!$catId || !$titulo) {
            flashError('Faltan datos obligatorios.'); redirect('/comunidad/admin'); return;
        }
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            flashError('Error al subir el archivo.'); redirect('/comunidad/admin'); return;
        }

        $file   = $_FILES['archivo'];
        $mime   = mime_content_type($file['tmp_name']);
        $ext    = pathinfo($file['name'], PATHINFO_EXTENSION);
        $disk   = bin2hex(random_bytes(16)) . ($ext ? '.' . strtolower($ext) : '');

        if (!is_dir($this->storageDir)) mkdir($this->storageDir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $this->storageDir . $disk)) {
            flashError('No se pudo guardar el archivo.'); redirect('/comunidad/admin'); return;
        }

        $this->db->prepare(
            'INSERT INTO comunidad_archivos
             (categoria_id, titulo, descripcion, archivo_disk, archivo_orig, mime, tamano, subido_por)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            $catId,
            $titulo,
            trim($_POST['descripcion'] ?? ''),
            $disk,
            $file['name'],
            $mime,
            $file['size'],
            Auth::id(),
        ]);

        flashSuccess('Archivo subido correctamente.');
        redirect('/comunidad/admin');
    }

    // ── Eliminar archivo ─────────────────────────────────────────
    public function eliminarArchivo(string $id): void {
        Auth::requireRole('soporte','admin');
        csrfVerify();

        $st = $this->db->prepare('SELECT * FROM comunidad_archivos WHERE id=?');
        $st->execute([(int)$id]);
        $row = $st->fetch();
        if ($row) {
            $papeleraDir = dirname(__DIR__, 2) . '/storage/comunidad_papelera/';
            if (!is_dir($papeleraDir)) mkdir($papeleraDir, 0775, true);

            $origen = $this->storageDir . $row['archivo_disk'];
            if (file_exists($origen)) {
                rename($origen, $papeleraDir . $row['archivo_disk']);
            }

            $this->db->prepare(
                'INSERT INTO papelera_general (colegio_id, tipo, descripcion, snapshot_json, archivo_trasladado, eliminado_por)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                Tenant::id(), 'archivo_comunidad',
                'Archivo "' . $row['titulo'] . '" (' . $row['archivo_orig'] . ')',
                json_encode(['archivo' => $row]),
                $row['archivo_disk'],
                Auth::id(),
            ]);

            $this->db->prepare('DELETE FROM comunidad_archivos WHERE id=?')->execute([(int)$id]);
        }
        flashSuccess('Archivo eliminado. Puedes restaurarlo desde la Papelera si fue un error.');
        redirect('/comunidad/admin');
    }

    // ── Descargar / visualizar archivo ───────────────────────────
    public function descargar(string $id): void {
        Auth::require();

        $st = $this->db->prepare('SELECT * FROM comunidad_archivos WHERE id=?');
        $st->execute([(int)$id]);
        $arch = $st->fetch();
        if (!$arch) { abort(404); }

        $path = $this->storageDir . $arch['archivo_disk'];
        if (!file_exists($path)) { abort(404); }

        // Mostrar en navegador si es PDF o imagen, sino forzar descarga
        $inline = in_array($arch['mime'], ['application/pdf','image/jpeg','image/png','image/gif','image/webp']);
        header('Content-Type: ' . $arch['mime']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($arch['archivo_orig']) . '"');
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    // ── Helpers privados ─────────────────────────────────────────
    private function getCategorias(bool $soloActivas): array {
        $where = $soloActivas ? 'WHERE c.activo = 1' : '';
        $cats  = $this->db->query(
            "SELECT c.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido
             FROM comunidad_categorias c
             JOIN usuarios u ON u.id = c.creado_por
             $where ORDER BY c.orden ASC, c.nombre ASC"
        )->fetchAll();

        foreach ($cats as &$cat) {
            $st = $this->db->prepare(
                'SELECT a.*, u.nombre AS autor_nombre, u.apellido AS autor_apellido
                 FROM comunidad_archivos a
                 JOIN usuarios u ON u.id = a.subido_por
                 WHERE a.categoria_id = ?
                 ORDER BY a.created_at DESC'
            );
            $st->execute([$cat['id']]);
            $archivos = $st->fetchAll();
            foreach ($archivos as &$a) {
                $icon = self::MIME_ICONS[$a['mime']] ?? 'mdi-file-outline';
                $a['icon']  = $icon;
                $a['color'] = self::MIME_COLORS[$icon] ?? '#6B7280';
                $a['tamano_fmt'] = $this->fmtSize($a['tamano']);
            }
            $cat['archivos'] = $archivos;
        }
        return $cats;
    }

    private function fmtSize(int $bytes): string {
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
