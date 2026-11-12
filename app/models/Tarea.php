<?php
class Tarea {
    private PDO $db;
    public function __construct() { $this->db = Database::get(); }

    // Lista de tareas de una asignación + periodo, con conteos
    public function listar(int $asignacion_id, int $periodo_id): array {
        $st = $this->db->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM tareas_adjuntos ta WHERE ta.tarea_id=t.id) AS num_adjuntos,
                    (SELECT COUNT(*) FROM mensajes_destinatarios md WHERE md.mensaje_id=t.mensaje_id) AS num_notif,
                    ev.nombre AS eval_nombre
             FROM tareas t
             LEFT JOIN evaluaciones ev ON t.evaluacion_id=ev.id
             WHERE t.asignacion_id=? AND t.periodo_id=?
             ORDER BY t.created_at DESC'
        );
        $st->execute([$asignacion_id, $periodo_id]);
        return $st->fetchAll();
    }

    public function porId(int $id): ?array {
        $st = $this->db->prepare('SELECT * FROM tareas WHERE id=? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    // Indicadores (logros) disponibles de una asignación+periodo, para el select de evaluación
    public function indicadoresDisponibles(int $asignacion_id, int $periodo_id): array {
        $st = $this->db->prepare(
            'SELECT id, codigo, descripcion FROM indicadores_eval
             WHERE asignacion_id=? AND periodo_id=?
             ORDER BY orden, id'
        );
        $st->execute([$asignacion_id, $periodo_id]);
        return $st->fetchAll();
    }

    public function crear(array $d): int {
        $st = $this->db->prepare(
            'INSERT INTO tareas
             (asignacion_id, periodo_id, nombre, descripcion, tipo_entrega,
              fecha_publicacion, fecha_limite, evaluacion_id, foro_habilitado, mensaje_id, creado_por)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $d['asignacion_id'], $d['periodo_id'], $d['nombre'], $d['descripcion'],
            $d['tipo_entrega'], $d['fecha_publicacion'], $d['fecha_limite'],
            $d['evaluacion_id'], $d['foro_habilitado'], $d['mensaje_id'], $d['creado_por'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function agregarAdjunto(int $tarea_id, array $a): void {
        $this->db->prepare(
            'INSERT INTO tareas_adjuntos (tarea_id, nombre_orig, nombre_disk, mime, tamano)
             VALUES (?,?,?,?,?)'
        )->execute([$tarea_id, $a['orig'], $a['disk'], $a['mime'], $a['size']]);
    }

    public function adjuntos(int $tarea_id): array {
        $st = $this->db->prepare(
            'SELECT id, nombre_orig, nombre_disk, mime, tamano FROM tareas_adjuntos WHERE tarea_id=?'
        );
        $st->execute([$tarea_id]);
        return $st->fetchAll();
    }

    public function adjuntoPorId(int $id): ?array {
        $st = $this->db->prepare('SELECT * FROM tareas_adjuntos WHERE id=? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function actualizar(int $id, array $d): void {
        $this->db->prepare(
            'UPDATE tareas SET nombre=?, descripcion=?, tipo_entrega=?,
             fecha_publicacion=?, fecha_limite=?, foro_habilitado=?
             WHERE id=?'
        )->execute([
            $d['nombre'], $d['descripcion'], $d['tipo_entrega'],
            $d['fecha_publicacion'], $d['fecha_limite'], $d['foro_habilitado'],
            $id,
        ]);
    }

    public function eliminarAdjunto(int $adjunto_id): void {
        $this->db->prepare('DELETE FROM tareas_adjuntos WHERE id=?')->execute([$adjunto_id]);
    }

    public function eliminar(int $id): void {
        $this->db->prepare('DELETE FROM tareas WHERE id=?')->execute([$id]);
    }

    // Destinatarios (estudiantes + acudientes) de un grupo para notificar
    public function destinatariosGrupo(int $grupo_id): array {
        $st = $this->db->prepare(
            'SELECT DISTINCT u.id
             FROM matriculas mt
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u    ON e.usuario_id=u.id AND u.activo=1
             WHERE mt.grupo_id=? AND mt.estado="activo"
             UNION
             SELECT DISTINCT pu.id
             FROM matriculas mt
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN acudientes ac ON ac.estudiante_id=e.id
             JOIN usuarios pu   ON ac.usuario_id=pu.id AND pu.activo=1
             WHERE mt.grupo_id=? AND mt.estado="activo"'
        );
        $st->execute([$grupo_id, $grupo_id]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
}
