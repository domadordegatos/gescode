<?php
class PerfilController {
    private PDO $db;
    private string $avatarDir;

    public function __construct() {
        $this->db        = Database::get();
        $this->avatarDir = dirname(__DIR__, 2) . '/storage/avatars/';
    }

    // ── Ver perfil ──────────────────────────────────────────────
    public function ver(): void {
        Auth::require();
        $user    = (new Usuario())->findById(Auth::id());
        $perfil  = $this->getPerfil(Auth::id());
        $noLeidos = (new Usuario())->totalNoLeidos(Auth::id());
        render('perfil/ver', compact('user', 'perfil', 'noLeidos'));
    }

    // ── Mostrar form edición ────────────────────────────────────
    public function editarForm(): void {
        Auth::require();
        $user    = (new Usuario())->findById(Auth::id());
        $perfil  = $this->getPerfil(Auth::id());
        $noLeidos = (new Usuario())->totalNoLeidos(Auth::id());
        render('perfil/editar', compact('user', 'perfil', 'noLeidos'));
    }

    // ── Guardar edición ─────────────────────────────────────────
    public function guardar(): void {
        Auth::require();
        csrfVerify();

        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flashError('El correo electrónico es obligatorio y debe ser válido.');
            redirect('/perfil/editar');
            return;
        }

        // Verificar que el email no lo use otro usuario
        $st = $this->db->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1');
        $st->execute([$email, Auth::id()]);
        if ($st->fetch()) {
            flashError('Ese correo ya está registrado por otro usuario.');
            redirect('/perfil/editar');
            return;
        }

        // Procesar foto comprimida (base64 del cliente)
        $fotoB64 = $_POST['foto_b64'] ?? '';
        if ($fotoB64 === 'REMOVE') {
            $old = $this->db->prepare('SELECT foto FROM usuarios WHERE id=?');
            $old->execute([Auth::id()]);
            $oldFoto = $old->fetchColumn();
            if ($oldFoto) @unlink($this->avatarDir . $oldFoto);
            $this->db->prepare('UPDATE usuarios SET foto = NULL WHERE id = ?')->execute([Auth::id()]);
        } elseif (str_starts_with($fotoB64, 'data:image/jpeg;base64,')) {
            $data = base64_decode(substr($fotoB64, strlen('data:image/jpeg;base64,')));
            if ($data && strlen($data) < 200000) { // máx ~195 KB en disco
                if (!is_dir($this->avatarDir)) mkdir($this->avatarDir, 0755, true);
                $filename = Auth::id() . '.jpg';
                file_put_contents($this->avatarDir . $filename, $data);
                $this->db->prepare('UPDATE usuarios SET foto = ? WHERE id = ?')
                         ->execute([$filename, Auth::id()]);
            }
        }

        // Actualizar email en usuarios
        $this->db->prepare('UPDATE usuarios SET email = ? WHERE id = ?')
                 ->execute([$email, Auth::id()]);

        // Upsert perfil extendido
        $campos = [
            'documento'        => trim($_POST['documento'] ?? ''),
            'tipo_documento'   => $_POST['tipo_documento'] ?? 'CC',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?: null,
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'celular'          => trim($_POST['celular'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'barrio'           => trim($_POST['barrio'] ?? ''),
            'ciudad'           => trim($_POST['ciudad'] ?? ''),
            'cargo'            => trim($_POST['cargo'] ?? ''),
            'bio'              => trim($_POST['bio'] ?? ''),
        ];

        $this->upsertPerfil(Auth::id(), $campos);

        flashSuccess('Perfil actualizado correctamente.');
        redirect('/perfil');
    }

    // ── Eliminar foto directamente desde ver.php ────────────────
    public function eliminarFoto(): void {
        Auth::require();
        csrfVerify();

        $st = $this->db->prepare('SELECT foto FROM usuarios WHERE id = ? LIMIT 1');
        $st->execute([Auth::id()]);
        $foto = $st->fetchColumn();

        if ($foto && is_file($this->avatarDir . $foto)) {
            unlink($this->avatarDir . $foto);
        }
        $this->db->prepare('UPDATE usuarios SET foto = NULL WHERE id = ?')
                 ->execute([Auth::id()]);

        flashSuccess('Foto de perfil eliminada.');
        redirect('/perfil');
    }

    // ── Mostrar form cambio de contraseña ───────────────────────
    public function passwordForm(): void {
        Auth::require();
        $noLeidos = (new Usuario())->totalNoLeidos(Auth::id());
        render('perfil/password', compact('noLeidos'));
    }

    // ── Guardar nueva contraseña ────────────────────────────────
    public function passwordGuardar(): void {
        Auth::require();
        csrfVerify();

        $actual   = $_POST['password_actual']    ?? '';
        $nueva    = $_POST['password_nueva']     ?? '';
        $confirma = $_POST['password_confirmar'] ?? '';

        $userM = new Usuario();
        $user  = $userM->findById(Auth::id());

        if (!$userM->verificarPassword($actual, $user['password'])) {
            flashError('La contraseña actual es incorrecta.');
            redirect('/perfil/password');
            return;
        }
        if (strlen($nueva) < 8) {
            flashError('La nueva contraseña debe tener al menos 8 caracteres.');
            redirect('/perfil/password');
            return;
        }
        if ($nueva !== $confirma) {
            flashError('Las contraseñas no coinciden.');
            redirect('/perfil/password');
            return;
        }

        $userM->updatePassword(Auth::id(), password_hash($nueva, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]));
        flashSuccess('Contraseña actualizada correctamente.');
        redirect('/perfil');
    }

    // ── Servir avatar (siempre devuelve imagen — JPG real o SVG con iniciales) ──
    public function avatar(string $uid): void {
        // Limpiar cualquier output acumulado para no corromper la respuesta binaria
        while (ob_get_level() > 0) ob_end_clean();

        if (!Auth::check()) {
            $this->servirSvgAvatar('?');
            return;
        }

        $st = $this->db->prepare(
            'SELECT u.foto, u.nombre, u.apellido FROM usuarios u WHERE u.id = ? LIMIT 1'
        );
        $st->execute([(int)$uid]);
        $row = $st->fetch();

        // Servir la foto real si existe en disco
        if ($row && !empty($row['foto'])) {
            $path = $this->avatarDir . $row['foto'];
            if (is_file($path) && is_readable($path)) {
                // Mime por extensión (más fiable que mime_content_type en Windows)
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                         'png' => 'image/png',  'webp'  => 'image/webp',
                         'gif' => 'image/gif'][$ext] ?? 'image/jpeg';
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($path));
                header('Cache-Control: private, max-age=86400');
                readfile($path);
                exit;
            }
        }

        // Fallback: SVG con iniciales
        $iniciales = $row
            ? strtoupper(mb_substr($row['nombre'], 0, 1) . mb_substr($row['apellido'], 0, 1))
            : '?';
        $this->servirSvgAvatar($iniciales);
    }

    private function servirSvgAvatar(string $iniciales): void {
        header('Content-Type: image/svg+xml');
        header('Cache-Control: private, max-age=300');
        // Sin whitespace antes del tag SVG para evitar corrupción
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="280" height="280" viewBox="0 0 280 280">'
             . '<circle cx="140" cy="140" r="140" fill="#0E0EB3"/>'
             . '<text x="140" y="140" dy=".35em" text-anchor="middle"'
             . ' font-family="Nunito,Arial,sans-serif" font-size="100" font-weight="800"'
             . ' fill="#ffffff">' . htmlspecialchars($iniciales, ENT_XML1) . '</text>'
             . '</svg>';
        echo $svg;
        exit;
    }

    // ── Preferencias de notificaciones ─────────────────────────
    public function preferencias(): void {
        Auth::require();
        $uid  = Auth::id();
        $rol  = Auth::rol();
        $user = (new Usuario())->findById($uid);

        $tipos_rol    = Notificador::TIPOS_POR_ROL[$rol] ?? ['mensaje'];
        $tipos        = array_intersect_key(Notificador::TIPOS_LABEL, array_flip($tipos_rol));
        $descripciones = Notificador::TIPOS_DESCRIPCION;

        // Cargar preferencias guardadas
        $st = $this->db->prepare(
            'SELECT tipo, activo FROM preferencias_notificaciones WHERE usuario_id=?'
        );
        $st->execute([$uid]);
        $rows  = $st->fetchAll(PDO::FETCH_KEY_PAIR);
        // Si no hay fila para un tipo, default es activo (true)
        $prefs = [];
        foreach ($tipos_rol as $t) {
            $prefs[$t] = isset($rows[$t]) ? (bool)$rows[$t] : true;
        }

        $email    = $user['email'] ?? '';
        $noLeidos = (new Usuario())->totalNoLeidos($uid);
        render('perfil/preferencias', compact('tipos', 'prefs', 'descripciones', 'email', 'noLeidos'));
    }

    public function guardarPreferencias(): void {
        Auth::require();
        csrfVerify();
        $uid      = Auth::id();
        $rol      = Auth::rol();
        $activos  = (array)($_POST['tipos'] ?? []);
        $validos  = Notificador::TIPOS_POR_ROL[$rol] ?? ['mensaje'];

        foreach ($validos as $tipo) {
            $activo = in_array($tipo, $activos) ? 1 : 0;
            $this->db->prepare(
                'INSERT INTO preferencias_notificaciones (usuario_id, tipo, activo)
                 VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE activo = VALUES(activo)'
            )->execute([$uid, $tipo, $activo]);
        }

        flashSuccess('Preferencias de notificaciones guardadas.');
        redirect('/perfil/notificaciones');
    }

    public function desuscribir(): void {
        $token = trim($_GET['token'] ?? '');
        $ok    = false;
        $tipo_label = '';

        if ($token) {
            $db = $this->db;
            $st = $db->prepare(
                'SELECT id, tipo FROM preferencias_notificaciones WHERE token_unsub=? LIMIT 1'
            );
            $st->execute([$token]);
            $row = $st->fetch();
            if ($row) {
                $db->prepare(
                    'UPDATE preferencias_notificaciones SET activo=0 WHERE id=?'
                )->execute([$row['id']]);
                $ok         = true;
                $tipo_label = Notificador::TIPOS_LABEL[$row['tipo']] ?? $row['tipo'];
            }
        }

        $noLeidos = 0;
        render('perfil/desuscribir', compact('ok', 'tipo_label', 'noLeidos'));
    }

    // ── Helpers privados ────────────────────────────────────────
    private function getPerfil(int $uid): array {
        $st = $this->db->prepare('SELECT * FROM perfiles WHERE usuario_id = ? LIMIT 1');
        $st->execute([$uid]);
        return $st->fetch() ?: [];
    }

    private function upsertPerfil(int $uid, array $data): void {
        $cols = implode(', ', array_keys($data));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        $updates = implode(', ', array_map(fn($k) => "$k = VALUES($k)", array_keys($data)));

        $sql = "INSERT INTO perfiles (usuario_id, $cols)
                VALUES (?, $vals)
                ON DUPLICATE KEY UPDATE $updates";

        $this->db->prepare($sql)->execute([$uid, ...array_values($data)]);
    }
}
