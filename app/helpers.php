<?php
// ── Funciones auxiliares globales ──────────────────────────

function redirect(string $path): never {
    header('Location: ' . APP_URL . $path);
    exit;
}

function abort(int $code = 404): never {
    http_response_code($code);
    $view = ROOT . '/app/views/errors/' . $code . '.php';
    if (file_exists($view)) include $view;
    else echo "<h1>$code</h1>";
    exit;
}

function render(string $view, array $data = []): void {
    extract($data, EXTR_SKIP);
    $file = __DIR__ . '/views/' . str_replace('.', '/', $view) . '.php';
    if (!file_exists($file)) {
        http_response_code(404);
        die("Vista no encontrada: $view");
    }
    include $file;
}

// ── Escala de desempeños (nota numérica → texto/abreviatura) ──
// $desempenios: resultado de `SELECT * FROM desempenios ORDER BY orden`.
// $modo: 'valor' (número con color ok/fail), 'texto' (nombre completo),
//        'abrev' (abreviatura).
function desempenioDeNota(?float $nota, array $desempenios, string $modo): string {
    if ($nota === null) return '<span class="text-muted">N.A.</span>';
    if ($modo === 'valor') {
        $ok = $nota >= notaMinAprobacion($desempenios);
        return '<strong class="' . ($ok ? 'nota-ok' : 'nota-fail') . '">' . number_format($nota, 1) . '</strong>';
    }

    foreach ($desempenios as $d) {
        if ($nota >= (float)$d['rango_min'] && $nota <= (float)$d['rango_max']) {
            $color = $d['es_perdido'] ? 'var(--danger)' : 'var(--success)';
            if ($modo === 'texto') return '<span style="color:' . $color . ';font-weight:700">' . e($d['nombre']) . '</span>';
            if ($modo === 'abrev') return '<span style="color:' . $color . ';font-weight:700">' . e($d['abreviatura']) . '</span>';
        }
    }
    return '<span class="text-muted">—</span>';
}

function notaMinAprobacion(array $desempenios): float {
    foreach ($desempenios as $d) {
        if ($d['es_perdido']) return (float)$d['rango_max'] + 0.1;
    }
    return 6.0;
}

// ── CSRF ────────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrfVerify(): void {
    $token   = $_POST[CSRF_TOKEN_NAME] ?? '';
    $session = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    if (!$token || !$session || !hash_equals($session, $token)) {
        http_response_code(403);
        die('Solicitud no válida (CSRF).');
    }
    // Rotar token después de verificar
    unset($_SESSION[CSRF_TOKEN_NAME]);
}

// ── Flash messages ───────────────────────────────────────────

function flashSuccess(string $msg): void {
    $_SESSION['flash_success'] = $msg;
}

function flashError(string $msg): void {
    $_SESSION['flash_error'] = $msg;
}

function flash(string $type): ?string {
    $key = 'flash_' . $type;
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

// ── Utilidades de vista ──────────────────────────────────────

function activePage(string $segment): string {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return str_contains($uri, '/' . $segment) ? 'active' : '';
}

function formatDate(string $datetime): string {
    $ts     = strtotime($datetime);
    $diff   = time() - $ts;
    $hoy    = date('Y-m-d');
    $ayer   = date('Y-m-d', strtotime('-1 day'));
    $msgDay = date('Y-m-d', $ts);
    if ($diff < 60)        return 'Hace un momento';
    if ($diff < 3600)      return 'Hace ' . floor($diff / 60) . ' min';
    if ($msgDay === $hoy)  return 'Hoy ' . date('H:i', $ts);
    if ($msgDay === $ayer) return 'Ayer ' . date('H:i', $ts);
    $dias = ['Sun'=>'Dom','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb'];
    $dow  = $dias[date('D', $ts)] ?? date('D', $ts);
    return $dow . ' ' . date('j/m', $ts);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function assetUrl(string $path): string {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

function colegio(): array {
    $c = Tenant::colegio();
    if (empty($c)) {
        $c = [
            'nombre'           => APP_NAME,
            'nombre_corto'     => APP_NAME,
            'logo'             => null,
            'color_primario'   => '#0E0EB3',
            'color_secundario' => '#1a1a6e',
            'notificaciones_email' => 0,
        ];
    }
    return $c;
}

/**
 * Filtra la lista de periodos para mostrar solo hasta el periodo activo.
 * Si no hay ninguno activo, devuelve todos.
 */
function periodosVisibles(array $periodos): array {
    $maxNumero = 0;
    foreach ($periodos as $p) {
        if ($p['activo']) $maxNumero = max($maxNumero, (int)$p['numero']);
    }
    if (!$maxNumero) return $periodos;
    return array_values(array_filter($periodos, fn($p) => (int)$p['numero'] <= $maxNumero));
}

function notaMin(): float {
    static $v = null;
    if ($v === null) {
        $r = Database::get()->query('SELECT nota_min FROM colegios LIMIT 1')->fetch();
        $v = $r ? (float)$r['nota_min'] : 1.0;
    }
    return $v;
}

function notaMax(): float {
    static $v = null;
    if ($v === null) {
        $r = Database::get()->query('SELECT nota_max FROM colegios LIMIT 1')->fetch();
        $v = $r ? (float)$r['nota_max'] : 10.0;
    }
    return $v;
}

function strftime_es(string $fecha): string {
    $ts   = strtotime($fecha);
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses= ['','enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return $dias[date('w',$ts)] . ' ' . date('d',$ts) . ' ' . $meses[(int)date('n',$ts)];
}

function safeHtml(string $html): string {
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\b(href|src|action)\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '$1="#"', $html);
    return $html;
}
