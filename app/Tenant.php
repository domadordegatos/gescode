<?php
/**
 * Tenant — detecta el subdominio de la petición actual y carga el colegio correspondiente.
 * Uso: Tenant::detectar() en bootstrap, luego Tenant::colegio() / Tenant::id() en cualquier parte.
 */
class Tenant {

    private static ?array  $colegio     = null;
    private static string  $subdominio  = '';
    private static bool    $detectado   = false;

    /** Llama esto una sola vez en index.php, antes de las rutas */
    public static function detectar(): void {
        if (self::$detectado) return;
        self::$detectado = true;

        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $host = preg_replace('/:\d+$/', '', $host); // quitar puerto

        // csma.localhost → ['csma','localhost']
        // csma.nncoms.com → ['csma','nncoms','com']
        // localhost → ['localhost']
        $partes = explode('.', $host);

        if (count($partes) >= 2 && $partes[0] !== 'www' && $partes[0] !== 'localhost') {
            self::$subdominio = $partes[0];
        }
    }

    public static function subdominio(): string {
        return self::$subdominio;
    }

    /**
     * Devuelve el array del colegio activo para este subdominio.
     * Si el subdominio no existe en BD → muestra error 404 y termina.
     * Si no hay subdominio (acceso directo a localhost sin sub) → carga el primer colegio activo.
     */
    public static function colegio(): array {
        if (self::$colegio !== null) return self::$colegio;

        $db = Database::get();

        if (self::$subdominio === '') {
            // Sin subdominio: acceso directo no permitido
            http_response_code(200);
            include ROOT . '/app/views/errors/no_tenant.php';
            exit;
        }

        $st = $db->prepare('SELECT * FROM colegios WHERE subdominio = ? AND activo = 1 LIMIT 1');
        $st->execute([self::$subdominio]);
        $row = $st->fetch();

        if (!$row) {
            http_response_code(404);
            include ROOT . '/app/views/errors/tenant_404.php';
            exit;
        }

        self::$colegio = $row;

        return self::$colegio;
    }

    public static function id(): int {
        return (int)(self::colegio()['id'] ?? 1);
    }

    /** URL base dinámica construida del request actual */
    public static function appUrl(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        return $proto . '://' . $host;
    }
}
