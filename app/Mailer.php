<?php
/**
 * Mailer — envío de correos HTML vía SMTP o mail() nativo.
 * Si MAIL_SMTP_HOST está configurado usa SMTP con stream_socket_client.
 * Si no, usa la función mail() del servidor (válida en hosting compartido).
 */
class Mailer {

    /** Envía un correo HTML profesional */
    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool {
        if (MAIL_SMTP_HOST) {
            return self::sendSmtp($to, $toName, $subject, $htmlBody);
        }
        return self::sendNative($to, $toName, $subject, $htmlBody);
    }

    // ── SMTP manual ───────────────────────────────────────────
    private static function sendSmtp(string $to, string $toName, string $subject, string $html): bool {
        $host     = MAIL_SMTP_HOST;
        $port     = (int)MAIL_SMTP_PORT;
        $enc      = strtolower(MAIL_SMTP_ENC);
        $user     = MAIL_SMTP_USER;
        $pass     = MAIL_SMTP_PASS;
        $from     = MAIL_FROM;
        $fromName = MAIL_FROM_NAME;

        $addr    = ($enc === 'ssl' ? 'ssl://' : '') . $host;
        $timeout = 15;

        $sock = @stream_socket_client("$addr:$port", $errno, $errstr, $timeout);
        if (!$sock) return false;

        stream_set_timeout($sock, $timeout);

        // Lee TODAS las líneas de una respuesta SMTP (puede ser multi-línea, ej. EHLO)
        $readAll = function() use ($sock): string {
            $out = '';
            while (($line = fgets($sock, 512)) !== false) {
                $out .= $line;
                // La última línea tiene espacio en pos 3: "250 OK", las intermedias tienen "-": "250-FEATURE"
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $out;
        };

        $cmd = function(string $c) use ($sock, $readAll): string {
            fwrite($sock, $c . "\r\n");
            return $readAll();
        };

        $readAll(); // saludo inicial (220)
        $cmd("EHLO " . (gethostname() ?: 'localhost'));

        if ($enc === 'tls') {
            $r = $cmd("STARTTLS");
            if (substr($r, 0, 3) !== '220') { fclose($sock); return false; }
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO " . (gethostname() ?: 'localhost'));
        }

        $r = $cmd("AUTH LOGIN");
        if (substr($r, 0, 3) !== '334') { fclose($sock); return false; }
        $r = $cmd(base64_encode($user));
        if (substr($r, 0, 3) !== '334') { fclose($sock); return false; }
        $r = $cmd(base64_encode($pass));
        if (substr($r, 0, 3) !== '235') { fclose($sock); return false; }

        $cmd("MAIL FROM:<$from>");
        $cmd("RCPT TO:<$to>");
        $cmd("DATA");

        $boundary = md5(uniqid('', true));
        $msg  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $msg .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$to>\r\n";
        $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $msg .= "X-Mailer: " . APP_NAME . "\r\n";
        $msg .= "\r\n";
        $msg .= "--$boundary\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $msg .= strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)) . "\r\n";
        $msg .= "--$boundary\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $msg .= $html . "\r\n";
        $msg .= "--$boundary--\r\n";

        fwrite($sock, $msg . "\r\n.\r\n");
        $readAll(); // respuesta al DATA
        $cmd("QUIT");
        fclose($sock);
        return true;
    }

    // ── mail() nativo ─────────────────────────────────────────
    private static function sendNative(string $to, string $toName, string $subject, string $html): bool {
        $from     = MAIL_FROM;
        $fromName = MAIL_FROM_NAME;
        $boundary = md5(uniqid('', true));

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: " . APP_NAME . "\r\n";

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)) . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $html . "\r\n";
        $body .= "--$boundary--";

        $subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        return mail("\"$toName\" <$to>", $subjectEncoded, $body, $headers);
    }

    /** Genera el HTML del correo de recuperación de contraseña */
    public static function htmlRecuperacion(string $nombre, string $enlace, int $ttlMinutos): string {
        $appName = APP_NAME;
        $primary = UI_PRIMARY;
        $year    = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recuperar contraseña — {$appName}</title>
</head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:32px 0">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0"
           style="background:#ffffff;border-radius:16px;overflow:hidden;
                  box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:560px;width:100%">

      <!-- Cabecera -->
      <tr>
        <td style="background:linear-gradient(135deg,{$primary} 0%,#1a1a6e 100%);
                   padding:36px 40px;text-align:center">
          <p style="color:rgba(255,255,255,0.7);font-size:11px;letter-spacing:3px;
                    text-transform:uppercase;margin:0 0 8px">{$appName}</p>
          <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0">
            Recuperación de contraseña
          </h1>
        </td>
      </tr>

      <!-- Cuerpo -->
      <tr>
        <td style="padding:36px 40px">
          <p style="color:#374151;font-size:15px;margin:0 0 12px">
            Hola, <strong>{$nombre}</strong>
          </p>
          <p style="color:#6b7280;font-size:14px;line-height:1.6;margin:0 0 28px">
            Recibimos una solicitud para restablecer la contraseña de tu cuenta en
            <strong>{$appName}</strong>. Si no fuiste tú, puedes ignorar este mensaje
            con total seguridad.
          </p>

          <!-- Botón -->
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center" style="padding:4px 0 28px">
                <a href="{$enlace}"
                   style="display:inline-block;background:{$primary};color:#ffffff;
                          text-decoration:none;font-size:15px;font-weight:700;
                          padding:14px 40px;border-radius:10px;
                          letter-spacing:.3px">
                  Restablecer contraseña
                </a>
              </td>
            </tr>
          </table>

          <!-- Aviso de expiración -->
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;
                         padding:12px 16px">
                <p style="color:#92400e;font-size:13px;margin:0">
                  ⏱ Este enlace expira en <strong>{$ttlMinutos} minutos</strong>.
                  Solo puede usarse una vez.
                </p>
              </td>
            </tr>
          </table>

          <p style="color:#9ca3af;font-size:12px;margin:24px 0 0;line-height:1.6">
            Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
            <a href="{$enlace}" style="color:{$primary};word-break:break-all">{$enlace}</a>
          </p>
        </td>
      </tr>

      <!-- Pie -->
      <tr>
        <td style="background:#f8f9fb;border-top:1px solid #e5e7eb;
                   padding:20px 40px;text-align:center">
          <p style="color:#9ca3af;font-size:12px;margin:0">
            © {$year} {$appName} · Este es un mensaje automático, no respondas a este correo.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
