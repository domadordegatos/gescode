<?php
class BackupController {

    public function index(): void {
        Auth::requireRole('soporte');
        render('soporte/backups', ['archivos' => Backup::listar()]);
    }

    public function generar(): void {
        Auth::requireRole('soporte');
        csrfVerify();

        try {
            Backup::ejecutar();
            flashSuccess('Respaldo generado correctamente.');
        } catch (Exception $e) {
            flashError('No se pudo generar el respaldo: ' . $e->getMessage());
        }
        redirect('/soporte/backups');
    }

    public function descargar(string $nombre): void {
        Auth::requireRole('soporte');

        $nombre = basename($nombre); // evita path traversal
        if (!preg_match('/^respaldo_[\d_\-]+\.sql\.gz$/', $nombre)) { abort(404); }

        $path = Backup::directorio() . $nombre;
        if (!file_exists($path)) { abort(404); }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
