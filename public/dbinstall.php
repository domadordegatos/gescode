<?php
define('DB_HOST', 'sql210.infinityfree.com');
define('DB_NAME', 'if0_41993912_plataforma');
define('DB_USER', 'if0_41993912');
define('DB_PASS', 'tg8BqvwpVNo');

$resultado = null;
$errores   = [];
$ok        = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
    $sql = file_get_contents(__DIR__ . '/dbdata.sql');
    if (!$sql) {
        $errores[] = 'No se encontró dbdata.sql en el servidor.';
    } else {
        // Drops de todas las tablas antes de recrearlas
        $drops = "SET FOREIGN_KEY_CHECKS=0;\n"
               . "DROP TABLE IF EXISTS `notas`;\n"
               . "DROP TABLE IF EXISTS `calificaciones`;\n"
               . "DROP TABLE IF EXISTS `evaluaciones`;\n"
               . "DROP TABLE IF EXISTS `indicadores_eval`;\n"
               . "DROP TABLE IF EXISTS `indicadores`;\n"
               . "DROP TABLE IF EXISTS `tipos_evaluacion`;\n"
               . "DROP TABLE IF EXISTS `comunidad_archivos`;\n"
               . "DROP TABLE IF EXISTS `comunidad_categorias`;\n"
               . "DROP TABLE IF EXISTS `mensajes_adjuntos`;\n"
               . "DROP TABLE IF EXISTS `mensajes_destinatarios`;\n"
               . "DROP TABLE IF EXISTS `mensajes`;\n"
               . "DROP TABLE IF EXISTS `asistencia`;\n"
               . "DROP TABLE IF EXISTS `observaciones`;\n"
               . "DROP TABLE IF EXISTS `perfiles`;\n"
               . "DROP TABLE IF EXISTS `acudientes`;\n"
               . "DROP TABLE IF EXISTS `matriculas`;\n"
               . "DROP TABLE IF EXISTS `estudiantes`;\n"
               . "DROP TABLE IF EXISTS `asignaciones`;\n"
               . "DROP TABLE IF EXISTS `materias`;\n"
               . "DROP TABLE IF EXISTS `grupos`;\n"
               . "DROP TABLE IF EXISTS `grados`;\n"
               . "DROP TABLE IF EXISTS `periodos`;\n"
               . "DROP TABLE IF EXISTS `anos_lectivos`;\n"
               . "DROP TABLE IF EXISTS `sesiones`;\n"
               . "DROP TABLE IF EXISTS `usuario_roles`;\n"
               . "DROP TABLE IF EXISTS `familias`;\n"
               . "DROP TABLE IF EXISTS `desempenios`;\n"
               . "DROP TABLE IF EXISTS `usuarios`;\n"
               . "SET FOREIGN_KEY_CHECKS=1;\n\n";

        $fullSql = $drops . $sql;

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_error) {
            $errores[] = 'Error de conexión: ' . $mysqli->connect_error;
        } else {
            $mysqli->set_charset('utf8mb4');
            $mysqli->query('SET FOREIGN_KEY_CHECKS=0');

            if ($mysqli->multi_query($fullSql)) {
                $count = 0;
                do {
                    $count++;
                    if ($res = $mysqli->store_result()) $res->free();
                } while ($mysqli->more_results() && $mysqli->next_result());

                if ($mysqli->error) {
                    $errores[] = 'Error en consulta #' . $count . ': ' . $mysqli->error;
                } else {
                    $ok = true;
                    $resultado = $count;
                }
            } else {
                $errores[] = 'Error multi_query: ' . $mysqli->error;
            }

            $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
            $mysqli->close();
        }
    }
}

if (isset($_POST['borrar'])) {
    @unlink(__FILE__);
    @unlink(__DIR__ . '/dbdata.sql');
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f0fdf4}</style></head><body><div style="text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1)"><h2 style="color:#166534">✅ Instalador eliminado</h2><p style="color:#6b7280;margin:12px 0">Los archivos han sido borrados del servidor.</p><a href="/" style="color:#0E0EB3;font-weight:700">→ Ir a la plataforma</a></div></body></html>');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalador BD — CSMA COMS</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f5f6fa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1);padding:40px;max-width:580px;width:100%}
.logo{font-size:1.6rem;font-weight:800;color:#0E0EB3;margin-bottom:4px}
.sub{color:#6b7280;font-size:.88rem;margin-bottom:28px}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:13px 24px;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;border:none;width:100%;font-family:inherit}
.btn-install{background:#0E0EB3;color:#fff}.btn-install:hover{background:#1a1a6e}
.btn-delete{background:#DC2626;color:#fff;margin-top:10px}.btn-delete:hover{background:#991b1b}
.alert{padding:14px 16px;border-radius:8px;margin-bottom:16px;font-size:.88rem}
.ok{background:#f0fdf4;color:#166534;border:1px solid #86efac;font-weight:700}
.err{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0}
.stat{background:#f5f6fa;border-radius:8px;padding:14px;text-align:center}
.stat-n{font-size:2rem;font-weight:800;color:#0E0EB3}
.stat-l{font-size:.75rem;color:#6b7280;margin-top:2px}
.warn{background:#fefce8;border:1px solid #fde047;color:#713f12;padding:12px 14px;border-radius:8px;font-size:.83rem;margin-bottom:20px}
.creds{width:100%;border-collapse:collapse;font-size:.85rem;margin-top:8px}
.creds td{padding:8px 10px;border-bottom:1px solid #f0f0f0}
.creds tr:last-child td{border:none}
.creds tr:nth-child(odd){background:#f9f9f9}
hr{border:none;border-top:1px solid #e5e7eb;margin:20px 0}
</style>
</head>
<body>
<div class="box">
  <div class="logo">CSMA COMS</div>
  <div class="sub">Instalador de base de datos — Plataforma Académica</div>

  <?php foreach ($errores as $e): ?>
    <div class="alert err">⚠️ <?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <?php if ($ok): ?>
    <div class="alert ok">✅ Base de datos instalada correctamente</div>
    <div class="stats">
      <div class="stat"><div class="stat-n"><?= $resultado ?></div><div class="stat-l">Sentencias ejecutadas</div></div>
      <div class="stat"><div class="stat-n" style="color:#16a34a">0</div><div class="stat-l">Errores críticos</div></div>
    </div>
    <hr>
    <p style="font-size:.85rem;color:#6b7280;margin-bottom:10px"><strong>Credenciales de acceso:</strong></p>
    <table class="creds">
      <tr><td><strong>Admin</strong></td><td>admin</td><td><strong>Admin123*</strong></td></tr>
      <tr><td><strong>Docente prueba</strong></td><td>docente1</td><td><strong>Docente123*</strong></td></tr>
    </table>
    <hr>
    <div class="warn">⚠️ <strong>Elimina el instalador</strong> del servidor antes de seguir usando la plataforma.</div>
    <form method="POST">
      <button class="btn btn-delete" name="borrar">🗑️ Eliminar instalador del servidor</button>
    </form>
    <a href="/" style="display:block;text-align:center;margin-top:16px;color:#0E0EB3;font-weight:700;text-decoration:none">→ Ir a la plataforma</a>

  <?php elseif (empty($errores)): ?>
    <div class="warn">Este script eliminará y recreará todas las tablas de la base de datos. Los datos existentes se perderán.</div>
    <form method="POST">
      <button class="btn btn-install" name="instalar">⚡ Instalar base de datos</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
