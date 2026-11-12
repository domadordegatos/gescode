<?php
/** Log general de acciones administrativas (quién cambió qué, fuera de lo que ya cubren
 *  la Papelera y el Registro de accesos). */
class Auditoria {
    public static function registrar(string $accion, string $detalle): void {
        try {
            Database::get()->prepare(
                'INSERT INTO auditoria_admin (colegio_id, usuario_id, accion, detalle, ip) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                Tenant::id(), Auth::id(), $accion, $detalle, $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Exception $e) {
            // No bloquear la acción principal si el log falla.
        }
    }
}
