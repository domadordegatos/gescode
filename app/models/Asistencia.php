<?php
class Asistencia {
    private PDO $db;
    public function __construct() { $this->db = Database::get(); }

    public function porFecha(int $grupo_id, string $fecha): array {
        $st = $this->db->prepare(
            'SELECT mt.id AS matricula_id, u.nombre, u.apellido, u.foto,
                    COALESCE(a.estado, "presente") AS estado, a.observacion, a.reportado_por
             FROM matriculas mt
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u ON e.usuario_id=u.id
             LEFT JOIN asistencia a ON a.matricula_id=mt.id AND a.fecha=?
             WHERE mt.grupo_id=? AND mt.estado="activo"
             ORDER BY u.apellido, u.nombre'
        );
        $st->execute([$fecha, $grupo_id]);
        return $st->fetchAll();
    }

    public function guardar(int $matricula_id, string $fecha, string $estado, string $obs, int $docente_id, string $reportadoPor = ''): void {
        $this->db->prepare(
            'INSERT INTO asistencia (matricula_id, fecha, estado, observacion, reportado_por, registrado_por)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE estado=VALUES(estado), observacion=VALUES(observacion), reportado_por=VALUES(reportado_por)'
        )->execute([$matricula_id, $fecha, $estado, $obs ?: null, $reportadoPor ?: null, $docente_id]);
    }

    // Resumen de asistencia de un estudiante en el período
    public function resumenEstudiante(int $matricula_id, string $fecha_inicio, string $fecha_fin): array {
        $st = $this->db->prepare(
            'SELECT estado, COUNT(*) AS total
             FROM asistencia
             WHERE matricula_id=? AND fecha BETWEEN ? AND ?
             GROUP BY estado'
        );
        $st->execute([$matricula_id, $fecha_inicio, $fecha_fin]);
        $rows = $st->fetchAll();
        $res = ['presente' => 0, 'ausente' => 0, 'tarde' => 0, 'excusa' => 0, 'incapacidad' => 0];
        foreach ($rows as $r) $res[$r['estado']] = (int)$r['total'];
        return $res;
    }

    // Fechas registradas en el mes actual para el grupo
    public function fechasRegistradas(int $grupo_id, string $mes): array {
        $st = $this->db->prepare(
            'SELECT DISTINCT a.fecha
             FROM asistencia a
             JOIN matriculas mt ON a.matricula_id=mt.id
             WHERE mt.grupo_id=? AND DATE_FORMAT(a.fecha,"%Y-%m")=?
             ORDER BY a.fecha'
        );
        $st->execute([$grupo_id, $mes]);
        return array_column($st->fetchAll(), 'fecha');
    }
}
