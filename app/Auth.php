<?php
class Auth {

    public static function login(array $user, array $roles = []): void {
        session_regenerate_id(true);

        $token = bin2hex(random_bytes(32));
        Database::get()->prepare('UPDATE usuarios SET session_token = ? WHERE id = ?')->execute([$token, $user['id']]);

        $_SESSION['user'] = [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'nombre'       => $user['nombre'],
            'apellido'     => $user['apellido'],
            'foto'         => $user['foto'] ?? null,
            'rol'          => $user['rol'],
            'roles'        => $roles ?: [$user['rol']],
            'es_principal' => (bool)($user['es_principal'] ?? false),
            'colegio_id'   => (int)($user['colegio_id'] ?? 1),
            'session_token' => $token,
        ];
    }

    /** Invalida cualquier otra sesión activa de este usuario (incluida la actual si no se vuelve a loguear). */
    public static function cerrarSesionesRemotas(int $usuarioId): void {
        Database::get()->prepare('UPDATE usuarios SET session_token = NULL WHERE id = ?')->execute([$usuarioId]);
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool {
        if (empty($_SESSION['user']['id'])) return false;

        // Si un admin forzó el cierre de sesión remoto (session_token = NULL en BD),
        // esta sesión deja de ser válida en la siguiente petición.
        if (!empty($_SESSION['user']['session_token'])) {
            $st = Database::get()->prepare('SELECT session_token FROM usuarios WHERE id = ?');
            $st->execute([$_SESSION['user']['id']]);
            $actual = $st->fetchColumn();
            if ($actual === false || $actual !== $_SESSION['user']['session_token']) {
                self::logout();
                return false;
            }
        }
        return true;
    }

    /** Redirige al login si no hay sesión */
    public static function require(): void {
        if (!self::check()) redirect('/');
    }

    /** Verifica si el usuario tiene AL MENOS uno de los roles indicados */
    public static function hasRole(string ...$roles): bool {
        $userRoles = self::roles();
        foreach ($roles as $r) {
            if (in_array($r, $userRoles, true)) return true;
        }
        return false;
    }

    /** Redirige con 403 si no tiene ninguno de los roles indicados */
    public static function requireRole(string ...$roles): void {
        self::require();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            include ROOT . '/app/views/errors/403.php';
            exit;
        }
    }

    /** Rol PRINCIPAL del usuario (determina el dashboard) */
    public static function rol(): string {
        return $_SESSION['user']['rol'] ?? '';
    }

    /** TODOS los roles del usuario */
    public static function roles(): array {
        return $_SESSION['user']['roles'] ?? [self::rol()];
    }

    public static function id(): int {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    public static function user(): array {
        return $_SESSION['user'] ?? [];
    }

    /** True si el usuario es admin principal (o soporte) */
    public static function isPrincipal(): bool {
        $u = self::user();
        if (($u['rol'] ?? '') === 'soporte') return true;
        return !empty($u['es_principal']);
    }
}
