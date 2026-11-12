<?php
class UploadController {

    // Ancho máximo al que se redimensiona (si es mayor). Alto se calcula proporcional.
    private const MAX_WIDTH  = 1200;
    private const MAX_HEIGHT = 1200;
    private const JPEG_QUALITY = 82; // 0-100

    public function imagen(): void {
        Auth::require();
        header('Content-Type: application/json');

        if (empty($_FILES['imagen'])) {
            echo json_encode(['error' => 'No se recibió ninguna imagen.']);
            return;
        }

        $file     = $_FILES['imagen'];
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxBytes = 5 * 1024 * 1024; // 5 MB antes de comprimir

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Error en la subida.']);
            return;
        }
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['error' => 'Formato no permitido. Usa JPG, PNG, GIF o WebP.']);
            return;
        }
        if ($file['size'] > $maxBytes) {
            echo json_encode(['error' => 'La imagen supera el límite de 5 MB.']);
            return;
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info) {
            echo json_encode(['error' => 'El archivo no es una imagen válida.']);
            return;
        }

        $destDir = ROOT . '/public/uploads/imagenes/';
        $nombre  = bin2hex(random_bytes(12)) . '.jpg'; // siempre guardamos como JPEG
        $destPath = $destDir . $nombre;

        // ── Comprimir y redimensionar con GD ─────────────────────
        $src = self::loadImage($file['tmp_name'], $info[2]);
        if (!$src) {
            // GD no disponible o formato no soportado — guardar tal cual
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['error' => 'No se pudo guardar la imagen.']);
                return;
            }
            echo json_encode(['url' => APP_URL . '/uploads/imagenes/' . $nombre]);
            return;
        }

        [$origW, $origH] = [$info[0], $info[1]];
        [$newW, $newH]   = self::calcSize($origW, $origH, self::MAX_WIDTH, self::MAX_HEIGHT);

        $canvas = imagecreatetruecolor($newW, $newH);

        // Fondo blanco para imágenes con transparencia (PNG)
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        if (!imagejpeg($canvas, $destPath, self::JPEG_QUALITY)) {
            imagedestroy($canvas);
            echo json_encode(['error' => 'No se pudo guardar la imagen comprimida.']);
            return;
        }
        imagedestroy($canvas);

        echo json_encode(['url' => APP_URL . '/uploads/imagenes/' . $nombre]);
    }

    private static function loadImage(string $path, int $type) {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => false,
        };
    }

    public function menciones(): void {
        Auth::require();
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) { echo json_encode([]); return; }
        $model = new Usuario();
        $rows  = $model->buscarMenciones($q);
        $result = array_map(fn($u) => [
            'id'    => $u['id'],
            'value' => $u['nombre'] . ' ' . $u['apellido'],
            'link'  => $u['username'],
            'rol'   => $u['rol'],
        ], $rows);
        echo json_encode($result);
    }

    private static function calcSize(int $w, int $h, int $maxW, int $maxH): array {
        if ($w <= $maxW && $h <= $maxH) return [$w, $h];
        $ratio = min($maxW / $w, $maxH / $h);
        return [max(1, (int)round($w * $ratio)), max(1, (int)round($h * $ratio))];
    }
}
