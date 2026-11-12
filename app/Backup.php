<?php
/**
 * Respaldo completo de la base de datos en SQL plano (comprimido en .gz), sin depender
 * del binario mysqldump (no siempre disponible en hosting compartido). Se usa tanto para
 * el botón "Generar ahora" como para el respaldo automático diario.
 */
class Backup {
    private const RETENCION_DIAS = 30;
    private const LOTE = 500;

    public static function directorio(): string {
        return ROOT . '/storage/backups/';
    }

    public static function yaHayRespaldoDeHoy(): bool {
        $dir = self::directorio();
        if (!is_dir($dir)) return false;
        $hoy = date('Y-m-d');
        foreach (glob($dir . 'respaldo_*.sql.gz') as $f) {
            if (str_contains(basename($f), $hoy)) return true;
        }
        return false;
    }

    /** Ejecuta el respaldo y devuelve el nombre del archivo generado */
    public static function ejecutar(): string {
        $db = Database::get();
        $dir = self::directorio();
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $nombre = 'respaldo_' . date('Y-m-d_His') . '.sql.gz';
        $gz = gzopen($dir . $nombre, 'w9');

        gzwrite($gz, "-- Respaldo generado el " . date('Y-m-d H:i:s') . "\n");
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tablas = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tablas as $tabla) {
            $create = $db->query("SHOW CREATE TABLE `$tabla`")->fetch();
            gzwrite($gz, "DROP TABLE IF EXISTS `$tabla`;\n");
            gzwrite($gz, $create['Create Table'] . ";\n\n");

            $total = (int)$db->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
            $cols = $db->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
            $colsEscapadas = '`' . implode('`,`', $cols) . '`';

            for ($offset = 0; $offset < $total; $offset += self::LOTE) {
                $filas = $db->query("SELECT * FROM `$tabla` LIMIT " . self::LOTE . " OFFSET $offset")->fetchAll(PDO::FETCH_NUM);
                if (!$filas) break;

                $valores = [];
                foreach ($filas as $fila) {
                    $escapados = array_map(function ($v) use ($db) {
                        return $v === null ? 'NULL' : $db->quote((string)$v);
                    }, $fila);
                    $valores[] = '(' . implode(',', $escapados) . ')';
                }
                gzwrite($gz, "INSERT INTO `$tabla` ($colsEscapadas) VALUES\n" . implode(",\n", $valores) . ";\n");
            }
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);

        self::limpiarAntiguos();

        return $nombre;
    }

    private static function limpiarAntiguos(): void {
        $limite = time() - self::RETENCION_DIAS * 86400;
        foreach (glob(self::directorio() . 'respaldo_*.sql.gz') as $f) {
            if (filemtime($f) < $limite) @unlink($f);
        }
    }

    public static function listar(): array {
        $dir = self::directorio();
        if (!is_dir($dir)) return [];
        $archivos = glob($dir . 'respaldo_*.sql.gz');
        usort($archivos, fn($a, $b) => filemtime($b) - filemtime($a));
        return array_map(fn($f) => [
            'nombre' => basename($f),
            'tamano' => filesize($f),
            'fecha'  => filemtime($f),
        ], $archivos);
    }

    /** Dispara un respaldo automático en segundo plano (no bloquea la respuesta actual) */
    public static function programarAutomaticoSiFalta(): void {
        if (self::yaHayRespaldoDeHoy()) return;

        // Marcador para evitar que varias peticiones simultáneas disparen el respaldo a la vez.
        $dir = self::directorio();
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $lock = $dir . '.lock_' . date('Y-m-d');
        if (file_exists($lock)) return;
        if (!@touch($lock)) return;

        register_shutdown_function(function () use ($lock) {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            try { self::ejecutar(); } catch (Exception $e) { /* se reintenta el próximo request */ }
            @unlink($lock);
        });
    }
}
