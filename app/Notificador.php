<?php
class Notificador {

    const TIPOS_LABEL = [
        'mensaje'        => 'Mensajes recibidos',
        'observacion'    => 'Observaciones registradas',
        'asistencia'     => 'Registro de asistencia',
        'tarea'          => 'Nuevas tareas publicadas',
        'nota'           => 'Publicación de calificaciones',
        'tarea_asignada' => 'Actividad en mis tareas',
    ];

    // Descripción extra por tipo (usada en la vista de preferencias)
    const TIPOS_DESCRIPCION = [
        'mensaje'        => 'Cuando alguien te envíe un mensaje interno en la plataforma.',
        'observacion'    => 'Cuando un docente registre una observación sobre tu hijo/a o sobre ti.',
        'asistencia'     => 'Cuando se registre una inasistencia, tardanza o excusa.',
        'tarea'          => 'Cuando se publique una nueva tarea o actividad.',
        'nota'           => 'Cuando se publiquen calificaciones.',
        'tarea_asignada' => 'Cuando un estudiante o padre envíe un mensaje relacionado con tus tareas.',
    ];

    const TIPOS_POR_ROL = [
        'padre'      => ['mensaje', 'observacion', 'asistencia', 'tarea', 'nota'],
        'estudiante' => ['mensaje', 'observacion', 'asistencia', 'tarea', 'nota'],
        'docente'    => ['mensaje', 'tarea_asignada'],
        'admin'      => ['mensaje'],
        'soporte'    => ['mensaje'],
    ];

    /**
     * Envía notificación por correo a los destinatarios que tengan ese tipo activo.
     *
     * @param string $tipo          Tipo de notificación (enum)
     * @param int[]  $destinatarios Array de usuario_id
     * @param string $asunto        Asunto del correo
     * @param string $titulo        Título visible en el email
     * @param string $cuerpo        Cuerpo HTML del mensaje
     * @param string $boton_url     URL del botón de acción (vacío = sin botón)
     * @param string $boton_texto   Texto del botón
     */
    public static function notificar(
        string $tipo,
        array  $destinatarios,
        string $asunto,
        string $titulo,
        string $cuerpo,
        string $boton_url   = '',
        string $boton_texto = 'Ver en la plataforma'
    ): void {
        if (!$destinatarios) return;

        $db = Database::get();

        // Si el colegio no tiene notificaciones activadas, no enviar nada
        $notiActiva = (bool)$db->query(
            'SELECT notificaciones_email FROM colegios LIMIT 1'
        )->fetchColumn();
        if (!$notiActiva) return;

        $ids   = implode(',', array_map('intval', $destinatarios));
        $tipoQ = $db->quote($tipo);

        // Default 0: el usuario debe activar explícitamente sus preferencias
        $st = $db->query("
            SELECT u.id, u.nombre, u.apellido, u.email,
                   COALESCE(pn.activo, 0)       AS pref_activo,
                   COALESCE(pn.token_unsub, '') AS token_unsub
            FROM usuarios u
            LEFT JOIN preferencias_notificaciones pn
                   ON pn.usuario_id = u.id AND pn.tipo = $tipoQ
            WHERE u.id IN ($ids) AND u.activo = 1 AND u.email != ''
        ");

        foreach ($st->fetchAll() as $row) {
            if (!(int)$row['pref_activo']) continue; // preferencia desactivada por el usuario

            $token = $row['token_unsub'];
            if (!$token) {
                $token = bin2hex(random_bytes(24));
                $db->prepare(
                    'INSERT INTO preferencias_notificaciones (usuario_id, tipo, activo, token_unsub)
                     VALUES (?,?,1,?)
                     ON DUPLICATE KEY UPDATE token_unsub = VALUES(token_unsub)'
                )->execute([$row['id'], $tipo, $token]);
            }

            $unsub = APP_URL . '/notificaciones/desuscribir?token=' . $token;
            $html  = self::htmlNotificacion(
                $row['nombre'], $titulo, $cuerpo, $boton_url, $boton_texto, $unsub
            );

            Mailer::send(
                $row['email'],
                $row['nombre'] . ' ' . $row['apellido'],
                $asunto,
                $html
            );
        }
    }

    /** Genera el HTML del correo de notificación */
    public static function htmlNotificacion(
        string $nombre,
        string $titulo,
        string $cuerpo,
        string $boton_url,
        string $boton_texto,
        string $unsub_url
    ): string {
        $appName = APP_NAME;
        $primary = defined('UI_PRIMARY') ? UI_PRIMARY : '#0E0EB3';
        $year    = date('Y');

        $botonHtml = '';
        if ($boton_url) {
            $botonHtml = '
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" style="padding:4px 0 28px">
                  <a href="' . htmlspecialchars($boton_url) . '"
                     style="display:inline-block;background:' . $primary . ';color:#ffffff;
                            text-decoration:none;font-size:15px;font-weight:700;
                            padding:14px 40px;border-radius:10px;letter-spacing:.3px">
                    ' . htmlspecialchars($boton_texto) . '
                  </a>
                </td>
              </tr>
            </table>';
        }

        return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($titulo) . ' — ' . htmlspecialchars($appName) . '</title>
</head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:\'Segoe UI\',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:32px 0">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0"
           style="background:#ffffff;border-radius:16px;overflow:hidden;
                  box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:560px;width:100%">

      <tr>
        <td style="background:linear-gradient(135deg,' . $primary . ' 0%,#1a1a6e 100%);
                   padding:36px 40px;text-align:center">
          <p style="color:rgba(255,255,255,0.7);font-size:11px;letter-spacing:3px;
                    text-transform:uppercase;margin:0 0 8px">' . htmlspecialchars($appName) . '</p>
          <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0">
            ' . htmlspecialchars($titulo) . '
          </h1>
        </td>
      </tr>

      <tr>
        <td style="padding:36px 40px">
          <p style="color:#374151;font-size:15px;margin:0 0 16px">
            Hola, <strong>' . htmlspecialchars($nombre) . '</strong>
          </p>
          <div style="color:#6b7280;font-size:14px;line-height:1.7;margin:0 0 28px">
            ' . $cuerpo . '
          </div>
          ' . $botonHtml . '
        </td>
      </tr>

      <tr>
        <td style="background:#f8f9fb;border-top:1px solid #e5e7eb;padding:20px 40px;text-align:center">
          <p style="color:#9ca3af;font-size:12px;margin:0 0 6px">
            &copy; ' . $year . ' ' . htmlspecialchars($appName) . ' &middot; Mensaje automático, no respondas a este correo.
          </p>
          <p style="margin:0">
            <a href="' . htmlspecialchars($unsub_url) . '"
               style="color:#9ca3af;font-size:11px;text-decoration:underline">
              Cancelar este tipo de notificación
            </a>
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
    }
}
