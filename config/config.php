<?php
// ── Configuración central ──────────────────────────────────
// Copiar config.example.php → config.php y ajustar valores

define('APP_NAME',    'Gescode');
// APP_URL se construye dinámicamente del request para soportar subdominios multi-tenant
$_proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host  = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
define('APP_URL', $_proto . '://' . $_host);
define('APP_VERSION', '1.0.0');
define('APP_LANG',    'es');
define('APP_TZ',      'America/Bogota');

// Base de datos
define('DB_HOST',     getenv('DB_HOST') ?: 'localhost');
define('DB_PORT',     getenv('DB_PORT') ?: '3306');
define('DB_NAME',     getenv('DB_NAME') ?: 'plataforma_academica');
define('DB_USER',     getenv('DB_USER') ?: 'root');
define('DB_PASS',     getenv('DB_PASS') ?: '');
define('DB_CHARSET',  'utf8mb4');

// Sesiones
define('SESSION_NAME',     'ACADSES');
define('SESSION_LIFETIME', 28800);   // 8 horas en segundos
define('SESSION_SECURE',   false);   // true en producción con HTTPS
define('SESSION_HTTPONLY',  true);
define('SESSION_SAMESITE',  'Lax');

// Seguridad
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_NAME', '_csrf');

// Correo — configurar con datos SMTP del servidor de producción
define('MAIL_FROM',     getenv('MAIL_FROM')     ?: 'gescodecoms@gmail.com');
define('MAIL_FROM_NAME',getenv('MAIL_FROM_NAME')?: APP_NAME);
define('MAIL_SMTP_HOST',getenv('MAIL_SMTP_HOST')?: 'smtp.gmail.com');
define('MAIL_SMTP_PORT',getenv('MAIL_SMTP_PORT')?: '587');
define('MAIL_SMTP_USER',getenv('MAIL_SMTP_USER')?: 'gescodecoms@gmail.com');
define('MAIL_SMTP_PASS',getenv('MAIL_SMTP_PASS')?: '');
define('MAIL_SMTP_ENC', getenv('MAIL_SMTP_ENC') ?: 'tls');    // tls | ssl | ''
define('PASSWORD_RESET_TTL', 3600); // segundos que dura el enlace (1 hora)

// Colores de la institución (personalizable)
define('UI_PRIMARY',   '#0E0EB3');
define('UI_SECONDARY', '#1a1a6e');

date_default_timezone_set(APP_TZ);
