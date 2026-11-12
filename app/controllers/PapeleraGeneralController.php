<?php
class PapeleraGeneralController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private const RETENCION_DIAS_RESTAURADOS = 90;

    public function index(): void {
        Auth::requireRole('admin', 'soporte');

        // Limpieza automática: solo se purgan los elementos YA RESTAURADOS hace más de 90 días
        // (lo pendiente de restaurar nunca se purga solo).
        $this->db->prepare(
            "DELETE FROM papelera_general WHERE colegio_id = ? AND restaurado = 1 AND restaurado_at < NOW() - INTERVAL ? DAY"
        )->execute([Tenant::id(), self::RETENCION_DIAS_RESTAURADOS]);

        $st = $this->db->prepare(
            "SELECT p.id, p.tipo, p.descripcion, p.created_at, p.restaurado, p.restaurado_at,
                    eu.nombre AS elim_nombre, eu.apellido AS elim_apellido,
                    ru.nombre AS rest_nombre, ru.apellido AS rest_apellido
             FROM papelera_general p
             JOIN usuarios eu ON eu.id = p.eliminado_por
             LEFT JOIN usuarios ru ON ru.id = p.restaurado_por
             WHERE p.colegio_id = ?
             ORDER BY p.created_at DESC
             LIMIT 200"
        );
        $st->execute([Tenant::id()]);
        render('admin/papelera_general_lista', ['items' => $st->fetchAll()]);
    }

    private function item(int $id): ?array {
        $q = $this->db->prepare('SELECT * FROM papelera_general WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, Tenant::id()]);
        $row = $q->fetch();
        if (!$row) return null;
        $row['snapshot'] = json_decode($row['snapshot_json'], true);
        return $row;
    }

    public function ver(int $id): void {
        Auth::requireRole('admin', 'soporte');
        $item = $this->item($id);
        if (!$item) { abort(404); }
        render('admin/papelera_general_detalle', ['item' => $item]);
    }

    public function restaurar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $item = $this->item($id);
        if (!$item) { abort(404); }
        if ($item['restaurado']) {
            flashError('Esto ya fue restaurado anteriormente.');
            redirect('/papelera-general');
            return;
        }

        $this->db->beginTransaction();
        try {
            match ($item['tipo']) {
                'usuario'             => $this->restaurarUsuario($item['snapshot']),
                'planeacion'          => $this->restaurarPlaneacion($item['snapshot']),
                'disponibilidad_cita' => $this->restaurarDisponibilidad($item['snapshot']),
                'archivo_comunidad'   => $this->restaurarArchivo($item['snapshot'], $item['archivo_trasladado']),
            };

            $this->db->prepare(
                'UPDATE papelera_general SET restaurado = 1, restaurado_por = ?, restaurado_at = NOW() WHERE id = ?'
            )->execute([Auth::id(), $id]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudo restaurar: ' . $e->getMessage());
            redirect('/papelera-general');
            return;
        }

        flashSuccess('Elemento restaurado correctamente.');
        redirect('/papelera-general');
    }

    private function restaurarUsuario(array $s): void {
        $u = $s['usuario'];
        $this->db->prepare(
            'INSERT INTO usuarios (id, colegio_id, username, email, password, nombre, apellido, foto, rol, activo, es_principal, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $u['id'], $u['colegio_id'], $u['username'], $u['email'], $u['password'], $u['nombre'], $u['apellido'],
            $u['foto'], $u['rol'], $u['activo'], $u['es_principal'] ?? 0, $u['created_at'],
        ]);

        if ($s['perfil']) {
            $p = $s['perfil'];
            $this->db->prepare(
                'INSERT INTO perfiles (usuario_id, documento, tipo_documento, telefono, celular, direccion, ciudad, cargo, bio, fecha_nacimiento, pais, departamento, barrio, nacionalidad)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $p['usuario_id'], $p['documento'], $p['tipo_documento'], $p['telefono'], $p['celular'],
                $p['direccion'], $p['ciudad'], $p['cargo'], $p['bio'], $p['fecha_nacimiento'],
                $p['pais'], $p['departamento'], $p['barrio'], $p['nacionalidad'],
            ]);
        }

        foreach ($s['acudientes'] as $a) {
            $this->db->prepare(
                'INSERT INTO acudientes (id, usuario_id, estudiante_id, parentesco, principal, familia_id, es_responsable_pago)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $a['id'], $a['usuario_id'], $a['estudiante_id'], $a['parentesco'],
                $a['principal'], $a['familia_id'], $a['es_responsable_pago'],
            ]);
        }

        foreach ($s['asignaciones'] as $a) {
            $chk = $this->db->prepare('SELECT 1 FROM asignaciones WHERE id = ?');
            $chk->execute([$a['id']]);
            if (!$chk->fetch()) {
                $this->db->prepare('INSERT INTO asignaciones (id, grupo_id, materia_id, docente_id) VALUES (?, ?, ?, ?)')
                          ->execute([$a['id'], $a['grupo_id'], $a['materia_id'], $a['docente_id']]);
            }
        }
    }

    private function restaurarPlaneacion(array $s): void {
        $p = $s['planeacion'];
        $this->db->prepare(
            'INSERT INTO planeaciones (id, asignacion_id, periodo_id, tema, objetivo, metodologia, recursos, evaluacion, fecha_inicio, fecha_fin, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $p['id'], $p['asignacion_id'], $p['periodo_id'], $p['tema'], $p['objetivo'], $p['metodologia'],
            $p['recursos'], $p['evaluacion'], $p['fecha_inicio'], $p['fecha_fin'], $p['created_at'], $p['updated_at'],
        ]);
    }

    private function restaurarDisponibilidad(array $s): void {
        $d = $s['disponibilidad'];
        $this->db->prepare(
            'INSERT INTO citas_disponibilidad (id, docente_id, fecha, hora_inicio, hora_fin, duracion_minutos, lugar, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $d['id'], $d['docente_id'], $d['fecha'], $d['hora_inicio'], $d['hora_fin'],
            $d['duracion_minutos'], $d['lugar'], $d['created_at'],
        ]);

        foreach ($s['reservas'] as $r) {
            $this->db->prepare(
                'INSERT INTO citas_reservas (id, disponibilidad_id, hora_inicio, hora_fin, padre_id, estudiante_id, motivo, estado, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $r['id'], $r['disponibilidad_id'], $r['hora_inicio'], $r['hora_fin'],
                $r['padre_id'], $r['estudiante_id'], $r['motivo'], $r['estado'], $r['created_at'],
            ]);
        }
    }

    private function restaurarArchivo(array $s, ?string $archivoTrasladado): void {
        $a = $s['archivo'];

        $papeleraDir = ROOT . '/storage/comunidad_papelera/';
        $destinoDir = ROOT . '/storage/comunidad/';
        if ($archivoTrasladado && file_exists($papeleraDir . $archivoTrasladado)) {
            rename($papeleraDir . $archivoTrasladado, $destinoDir . $archivoTrasladado);
        }

        $this->db->prepare(
            'INSERT INTO comunidad_archivos (id, categoria_id, titulo, descripcion, archivo_disk, archivo_orig, mime, tamano, subido_por, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $a['id'], $a['categoria_id'], $a['titulo'], $a['descripcion'], $a['archivo_disk'],
            $a['archivo_orig'], $a['mime'], $a['tamano'], $a['subido_por'], $a['created_at'],
        ]);
    }
}
