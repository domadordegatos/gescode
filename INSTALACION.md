# Plataforma Académica — Guía de Instalación

## Requisitos del servidor
- PHP 8.1 o superior
- MySQL 8.0 / MariaDB 10.6 o superior
- Apache con mod_rewrite habilitado (o Nginx con configuración equivalente)
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`

## Pasos de instalación

### 1. Subir archivos al servidor
Sube la carpeta `plataforma/` a tu hosting. El **document root** del dominio o subdominio debe apuntar a `plataforma/public/`.

Ejemplo en cPanel: si el dominio es `colegio.edu.co`, sube los archivos a `public_html/` y configura el document root a `public_html/public/`.

### 2. Crear la base de datos
En phpMyAdmin (o tu cliente MySQL):
1. Crea una base de datos, ej: `plataforma_academica`
2. Crea un usuario con todos los permisos sobre esa base de datos
3. Importa el archivo `database/schema.sql`

### 3. Configurar la aplicación
Edita el archivo `config/config.php`:

```php
define('APP_URL', 'https://tudominio.edu.co');  // Sin / al final

define('DB_HOST', 'localhost');
define('DB_NAME', 'plataforma_academica');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');

define('SESSION_SECURE', true);  // true cuando tengas HTTPS
```

### 4. Permisos de archivos
```bash
chmod 755 public/
chmod 644 public/.htaccess
chmod 600 config/config.php
```

### 5. Primer acceso
- URL: `https://tudominio.edu.co`
- Usuario: `admin`
- Contraseña: `Admin123*`

**⚠️ Cambia la contraseña inmediatamente después del primer ingreso.**

---

## Estructura de directorios

```
plataforma/
├── config/
│   ├── config.php          ← Configuración principal
│   └── database.php        ← Singleton PDO
├── database/
│   └── schema.sql          ← Esquema + datos iniciales
├── app/
│   ├── Auth.php            ← Gestión de sesiones
│   ├── Router.php          ← Enrutador HTTP
│   ├── helpers.php         ← Funciones globales
│   ├── controllers/        ← Lógica de cada módulo
│   ├── models/             ← Acceso a datos
│   └── views/              ← Plantillas PHP
└── public/                 ← Document root (único acceso web)
    ├── index.php           ← Punto de entrada
    ├── .htaccess
    └── assets/
        ├── css/app.css
        └── js/app.js
```

---

## Roles del sistema

| Rol        | Descripción                                              |
|------------|----------------------------------------------------------|
| `admin`    | Acceso total: usuarios, grados, períodos, reportes       |
| `docente`  | Sus grupos, notas, observaciones, asistencia             |
| `estudiante` | Sus propias notas y mensajes                           |
| `padre`    | Notas y comunicados de sus acudidos                     |
| `soporte`  | Acceso de lectura + gestión técnica                     |

---

## Roadmap de fases

| Fase | Módulos                                                  | Estado    |
|------|----------------------------------------------------------|-----------|
| 1    | Auth, Dashboard por rol, Mensajería                     | ✅ Listo  |
| 2    | Gestión de grados/grupos, Matrícula de estudiantes      | Siguiente |
| 3    | Notas por porcentajes, indicadores de logro             | Pendiente |
| 4    | Asistencia, Observaciones                               | Pendiente |
| 5    | Reportes PDF, estado de estudiantes, promoción          | Pendiente |
| 6    | Calendario, archivos comunitarios                       | Pendiente |

---

## Seguridad implementada

- Contraseñas con bcrypt (costo 12)
- Protección CSRF en todos los formularios POST
- Sesiones HTTP-only, SameSite=Lax
- PDO con prepared statements (sin SQL injection)
- Cabeceras de seguridad HTTP en .htaccess
- Sin listado de directorios
- Acceso directo a PHP bloqueado (solo index.php)
