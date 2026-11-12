<?php
class ColegioController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::require();
        Auth::requireRole('admin', 'soporte');
        if (!Auth::isPrincipal()) abort(403);

        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();
        render('admin/colegio', compact('colegio'));
    }

    public function guardar(): void {
        Auth::require();
        Auth::requireRole('admin', 'soporte');
        if (!Auth::isPrincipal()) abort(403);
        csrfVerify();

        $id = Tenant::id();

        // Escala de notas
        $nota_min = (float)($_POST['nota_min'] ?? 1.0);
        $nota_max = (float)($_POST['nota_max'] ?? 10.0);

        if ($nota_min >= $nota_max) {
            flashError('La nota mínima debe ser menor que la máxima.');
            redirect('/admin/colegio');
            return;
        }
        if ($nota_min < 0 || $nota_max > 100) {
            flashError('Los valores deben estar entre 0 y 100.');
            redirect('/admin/colegio');
            return;
        }

        // Datos de texto
        $nombre         = trim($_POST['nombre']         ?? '');
        $slogan         = trim($_POST['slogan']         ?? '');
        $nombre_corto   = trim($_POST['nombre_corto']   ?? '');
        $rector         = trim($_POST['rector']         ?? '');
        $rector_cargo   = trim($_POST['rector_cargo']   ?? '') ?: 'Rector(a)';
        $nit            = trim($_POST['nit']            ?? '');
        $dane           = trim($_POST['dane']           ?? '');
        $ciudad         = trim($_POST['ciudad']         ?? '');
        $direccion      = trim($_POST['direccion']      ?? '');
        $telefono       = trim($_POST['telefono']       ?? '');
        $telefono2      = trim($_POST['telefono2']      ?? '');
        $email          = trim($_POST['email']          ?? '');
        $sitio_web      = trim($_POST['sitio_web']      ?? '');
        $color_primario = trim($_POST['color_primario'] ?? '#0E0EB3');
        $color_sec      = trim($_POST['color_secundario'] ?? '#1a1a6e');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_primario)) $color_primario = '#0E0EB3';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_sec))      $color_sec      = '#1a1a6e';

        // Logo upload
        $logo = null; // null = no change
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $mime    = mime_content_type($_FILES['logo']['tmp_name']);
            $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
            if (!in_array($mime, $allowed)) {
                flashError('El logo debe ser una imagen PNG, JPG, WebP o SVG.');
                redirect('/admin/colegio');
                return;
            }
            if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                flashError('El logo no puede superar 2 MB.');
                redirect('/admin/colegio');
                return;
            }

            $destDir = ROOT . '/public/uploads/imagenes/';
            if (!is_dir($destDir)) mkdir($destDir, 0775, true);

            // Eliminar logo anterior (cualquier extensión)
            foreach (glob($destDir . 'logo_colegio_' . $id . '.*') as $old) {
                @unlink($old);
            }

            if ($mime === 'image/svg+xml') {
                // SVG: guardar tal cual (GD no lo procesa; SVG ya es texto compacto)
                $filename = 'logo_colegio_' . $id . '.svg';
                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destDir . $filename)) {
                    flashError('Error al guardar el logo. Intenta de nuevo.');
                    redirect('/admin/colegio');
                    return;
                }
            } else {
                // Raster: comprimir y redimensionar con GD
                $filename = self::procesarLogo($_FILES['logo']['tmp_name'], $mime, $destDir, $id);
                if (!$filename) {
                    flashError('No se pudo procesar la imagen. Verifica que sea un PNG, JPG o WebP válido.');
                    redirect('/admin/colegio');
                    return;
                }
            }
            $logo = $filename;
        }

        // Eliminar logo si se pidió
        if (isset($_POST['eliminar_logo']) && $_POST['eliminar_logo'] === '1') {
            $destDir = ROOT . '/public/uploads/imagenes/';
            foreach (glob($destDir . 'logo_colegio_' . $id . '.*') as $old) {
                @unlink($old);
            }
            $logo = '';
        }

        // Marca de agua upload (boletines/constancias)
        $marcaAgua = null; // null = no change
        if (!empty($_FILES['marca_agua']['tmp_name']) && $_FILES['marca_agua']['error'] === UPLOAD_ERR_OK) {
            $mime    = mime_content_type($_FILES['marca_agua']['tmp_name']);
            $allowed = ['image/png', 'image/jpeg', 'image/webp'];
            if (!in_array($mime, $allowed)) {
                flashError('La marca de agua debe ser una imagen PNG, JPG o WebP.');
                redirect('/admin/colegio');
                return;
            }
            if ($_FILES['marca_agua']['size'] > 2 * 1024 * 1024) {
                flashError('La marca de agua no puede superar 2 MB.');
                redirect('/admin/colegio');
                return;
            }

            $destDir = ROOT . '/public/uploads/imagenes/';
            if (!is_dir($destDir)) mkdir($destDir, 0775, true);

            foreach (glob($destDir . 'marca_agua_' . $id . '.*') as $old) {
                @unlink($old);
            }

            $filename = self::procesarLogo($_FILES['marca_agua']['tmp_name'], $mime, $destDir, $id, 'marca_agua_');
            if (!$filename) {
                flashError('No se pudo procesar la imagen. Verifica que sea un PNG, JPG o WebP válido.');
                redirect('/admin/colegio');
                return;
            }
            $marcaAgua = $filename;
        }

        if (isset($_POST['eliminar_marca_agua']) && $_POST['eliminar_marca_agua'] === '1') {
            $destDir = ROOT . '/public/uploads/imagenes/';
            foreach (glob($destDir . 'marca_agua_' . $id . '.*') as $old) {
                @unlink($old);
            }
            $marcaAgua = '';
        }

        // Notificaciones (solo soporte)
        $notiEmail = (Auth::rol() === 'soporte') ? (isset($_POST['notificaciones_email']) ? 1 : 0) : null;

        // Cuentas de estudiante (función futura/premium) — editable por admin y soporte
        $permiteCuentasEst = isset($_POST['permite_cuentas_estudiantes']) ? 1 : 0;

        // Construir UPDATE dinámico para logo y marca de agua
        $logoSql = '';
        $params  = [];
        if ($logo === '') {
            $logoSql = ', logo = NULL';
        } elseif ($logo !== null) {
            $logoSql = ', logo = ?';
            $params[] = $logo;
        }
        if ($marcaAgua === '') {
            $logoSql .= ', marca_agua_boletin = NULL';
        } elseif ($marcaAgua !== null) {
            $logoSql .= ', marca_agua_boletin = ?';
            $params[] = $marcaAgua;
        }

        if ($notiEmail !== null) {
            $sql = "UPDATE colegios SET
                nombre=?, slogan=?, nombre_corto=?, rector=?, rector_cargo=?,
                nit=?, dane=?, ciudad=?, direccion=?,
                telefono=?, telefono2=?, email=?, sitio_web=?,
                color_primario=?, color_secundario=?,
                nota_min=?, nota_max=?,
                notificaciones_email=?, permite_cuentas_estudiantes=?
                $logoSql
                WHERE id=?";
            $base = [$nombre ?: 'Mi Institución', $slogan, $nombre_corto, $rector, $rector_cargo,
                     $nit, $dane, $ciudad, $direccion,
                     $telefono, $telefono2, $email, $sitio_web,
                     $color_primario, $color_sec,
                     $nota_min, $nota_max,
                     $notiEmail, $permiteCuentasEst];
        } else {
            $sql = "UPDATE colegios SET
                nombre=?, slogan=?, nombre_corto=?, rector=?, rector_cargo=?,
                nit=?, dane=?, ciudad=?, direccion=?,
                telefono=?, telefono2=?, email=?, sitio_web=?,
                color_primario=?, color_secundario=?,
                nota_min=?, nota_max=?,
                permite_cuentas_estudiantes=?
                $logoSql
                WHERE id=?";
            $base = [$nombre ?: 'Mi Institución', $slogan, $nombre_corto, $rector, $rector_cargo,
                     $nit, $dane, $ciudad, $direccion,
                     $telefono, $telefono2, $email, $sitio_web,
                     $color_primario, $color_sec,
                     $nota_min, $nota_max,
                     $permiteCuentasEst];
        }

        $allParams = array_merge($base, $params, [$id]);
        $this->db->prepare($sql)->execute($allParams);

        Auditoria::registrar('colegio.actualizar', 'Actualizó la configuración institucional del colegio.');
        flashSuccess('Configuración guardada correctamente.');
        redirect('/admin/colegio');
    }

    /**
     * Carga, redimensiona (máx. 400×400) y guarda el logo como PNG (transparencia) o JPEG.
     * Devuelve el nombre del archivo guardado, o false si GD falla.
     */
    private static function procesarLogo(string $tmpPath, string $mime, string $destDir, int $id, string $prefix = 'logo_colegio_'): string|false {
        $info = @getimagesize($tmpPath);
        if (!$info) return false;

        [$origW, $origH, $type] = [$info[0], $info[1], $info[2]];

        $src = match ($type) {
            IMAGETYPE_PNG  => @imagecreatefrompng($tmpPath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmpPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($tmpPath),
            default        => false,
        };
        if (!$src) return false;

        // Redimensionar a máx. 400px manteniendo proporción
        $maxDim = 400;
        $ratio  = min($maxDim / $origW, $maxDim / $origH, 1.0);
        $newW   = max(1, (int)round($origW * $ratio));
        $newH   = max(1, (int)round($origH * $ratio));

        $dst = imagecreatetruecolor($newW, $newH);

        $savePng = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP]);

        if ($savePng) {
            // Preservar canal alfa
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
            imagealphablending($dst, true);
        } else {
            // Fondo blanco para JPEG
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        if ($savePng) {
            $filename = $prefix . $id . '.png';
            $ok = imagepng($dst, $destDir . $filename, 9);
        } else {
            $filename = $prefix . $id . '.jpg';
            $ok = imagejpeg($dst, $destDir . $filename, 85);
        }
        imagedestroy($dst);

        return $ok ? $filename : false;
    }
}
