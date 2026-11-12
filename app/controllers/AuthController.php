<?php
class AuthController {
    private Usuario $usuario;

    public function __construct() {
        $this->usuario = new Usuario();
    }

    public function showLogin(): void {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        render('auth/login');
    }

    public function login(): void {
        csrfVerify();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            flashError('Por favor completa todos los campos.');
            redirect('/');
            return;
        }

        $user = $this->usuario->findByUsername($username);

        if (!$user || !$this->usuario->verificarPassword($password, $user['password'])) {
            // Mismo mensaje para no revelar si existe el usuario
            $this->logAcceso(null, $username, false, 'credenciales_invalidas');
            flashError('Usuario o contraseña incorrectos.');
            redirect('/');
            return;
        }

        if (!$user['activo']) {
            $this->logAcceso($user['id'], $username, false, 'cuenta_desactivada');
            flashError('Tu cuenta está desactivada. Contacta a soporte.');
            redirect('/');
            return;
        }

        // Verificar que el usuario pertenece a este colegio (multi-tenant)
        if ((int)($user['colegio_id'] ?? 1) !== Tenant::id()) {
            $this->logAcceso($user['id'], $username, false, 'colegio_incorrecto');
            flashError('Usuario o contraseña incorrectos.');
            redirect('/');
            return;
        }

        // Cargar todos los roles del usuario desde usuario_roles
        $db = Database::get();
        $stRoles = $db->prepare('SELECT rol FROM usuario_roles WHERE usuario_id=?');
        $stRoles->execute([$user['id']]);
        $roles = $stRoles->fetchAll(PDO::FETCH_COLUMN) ?: [$user['rol']];

        Auth::login($user, $roles);
        $this->usuario->updateUltimoLogin($user['id']);
        $this->logAcceso($user['id'], $username, true, null);
        redirect('/dashboard');
    }

    private function logAcceso(?int $usuarioId, string $username, bool $exitoso, ?string $motivo): void {
        Database::get()->prepare(
            'INSERT INTO accesos_log (usuario_id, username_intentado, colegio_id, exitoso, motivo_fallo, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $usuarioId, $username, Tenant::id(), $exitoso ? 1 : 0, $motivo,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    public function logout(): void {
        Auth::logout();
        redirect('/');
    }

    // ── RECUPERAR CONTRASEÑA ──────────────────────────────────

    public function showRecuperar(): void {
        if (Auth::check()) redirect('/dashboard');
        render('auth/recuperar');
    }

    public function recuperar(): void {
        csrfVerify();
        $input = trim($_POST['identificador'] ?? '');
        if (!$input) {
            flashError('Ingresa tu correo electrónico o nombre de usuario.');
            redirect('/recuperar-password');
            return;
        }

        // Buscar por email o por username
        $user = filter_var($input, FILTER_VALIDATE_EMAIL)
            ? $this->usuario->findByEmail($input)
            : $this->usuario->findByUsername($input);

        // Respuesta idéntica tanto si existe como si no (seguridad)
        if (!$user || !$user['activo'] || empty($user['email'])) {
            $_SESSION['reset_hint'] = null;
            flashSuccess('dummy'); // se maneja en la vista con clave especial
            redirect('/recuperar-password?enviado=1');
            return;
        }

        $token   = $this->usuario->crearResetToken((int)$user['id']);
        $enlace  = APP_URL . '/reset-password?token=' . $token;
        $ttlMin  = (int)(PASSWORD_RESET_TTL / 60);
        $nombre  = $user['nombre'] . ' ' . $user['apellido'];
        $html    = Mailer::htmlRecuperacion($nombre, $enlace, $ttlMin);

        Mailer::send($user['email'], $nombre, 'Restablecer contraseña — ' . APP_NAME, $html);

        // Guardar email parcialmente enmascarado en sesión
        $_SESSION['reset_hint'] = self::maskEmail($user['email']);
        redirect('/recuperar-password?enviado=1');
    }

    public function showReset(): void {
        if (Auth::check()) redirect('/dashboard');
        $token = trim($_GET['token'] ?? '');
        if (!$token) { redirect('/recuperar-password'); return; }

        $reset = $this->usuario->findResetToken($token);
        if (!$reset) {
            flashError('El enlace de recuperación no es válido o ha expirado.');
            redirect('/recuperar-password');
            return;
        }
        render('auth/reset_password', compact('token', 'reset'));
    }

    public function resetPassword(): void {
        csrfVerify();
        $token    = trim($_POST['token'] ?? '');
        $nueva    = $_POST['password_nueva']     ?? '';
        $confirma = $_POST['password_confirmar'] ?? '';

        $reset = $token ? $this->usuario->findResetToken($token) : null;
        if (!$reset) {
            flashError('Enlace inválido o expirado. Solicita uno nuevo.');
            redirect('/recuperar-password');
            return;
        }

        if (strlen($nueva) < 8) {
            flashError('La contraseña debe tener al menos 8 caracteres.');
            redirect('/reset-password?token=' . urlencode($token));
            return;
        }
        if ($nueva !== $confirma) {
            flashError('Las contraseñas no coinciden.');
            redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $this->usuario->updatePassword((int)$reset['usuario_id'], $hash);
        $this->usuario->consumirResetToken($token);

        flashSuccess('¡Contraseña actualizada! Ya puedes iniciar sesión.');
        redirect('/');
    }

    // ── Helper: enmascarar email ──────────────────────────────
    private static function maskEmail(string $email): string {
        [$local, $domain] = explode('@', $email, 2);
        $parts  = explode('.', $domain);
        $tld    = array_pop($parts);
        $dom    = implode('.', $parts);
        $masked = substr($local, 0, 2) . str_repeat('*', max(3, strlen($local) - 2));
        $maskedDom = substr($dom, 0, 1) . str_repeat('*', max(2, strlen($dom) - 1));
        return $masked . '@' . $maskedDom . '.' . $tld;
    }

    public function showCambiarPassword(): void {
        Auth::require();
        render('auth/cambiar_password');
    }

    public function cambiarPassword(): void {
        Auth::require();
        csrfVerify();

        $actual   = $_POST['password_actual']    ?? '';
        $nueva    = $_POST['password_nueva']     ?? '';
        $confirma = $_POST['password_confirmar'] ?? '';

        $user = $this->usuario->findById(Auth::id());

        if (!$this->usuario->verificarPassword($actual, $user['password'])) {
            flashError('La contraseña actual es incorrecta.');
            redirect('/perfil/cambiar-password');
            return;
        }

        if (strlen($nueva) < 8) {
            flashError('La nueva contraseña debe tener al menos 8 caracteres.');
            redirect('/perfil/cambiar-password');
            return;
        }

        if ($nueva !== $confirma) {
            flashError('Las contraseñas no coinciden.');
            redirect('/perfil/cambiar-password');
            return;
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $this->usuario->updatePassword(Auth::id(), $hash);
        flashSuccess('Contraseña actualizada correctamente.');
        redirect('/dashboard');
    }
}
