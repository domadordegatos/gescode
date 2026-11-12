<?php
class CitaController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('docente', 'padre');
        $activo = (bool)$this->db->query(
            'SELECT permite_citas FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    /** Divide un bloque de disponibilidad en slots de duracion_minutos, marcando los ya reservados */
    private function slotsDeBloque(array $bloque): array {
        $slots = [];
        $actual = strtotime($bloque['fecha'] . ' ' . $bloque['hora_inicio']);
        $fin = strtotime($bloque['fecha'] . ' ' . $bloque['hora_fin']);
        $dur = (int)$bloque['duracion_minutos'] * 60;

        $qr = $this->db->prepare(
            "SELECT r.id, r.hora_inicio, r.padre_id, r.motivo,
                    u.nombre AS padre_nombre, u.apellido AS padre_apellido,
                    eu.nombre AS est_nombre, eu.apellido AS est_apellido
             FROM citas_reservas r
             JOIN usuarios u ON u.id = r.padre_id
             LEFT JOIN estudiantes e ON e.id = r.estudiante_id
             LEFT JOIN usuarios eu ON eu.id = e.usuario_id
             WHERE r.disponibilidad_id = ? AND r.estado = 'confirmada'"
        );
        $qr->execute([$bloque['id']]);
        $reservados = [];
        foreach ($qr->fetchAll() as $r) { $reservados[$r['hora_inicio']] = $r; }

        while ($actual + $dur <= $fin) {
            $horaIni = date('H:i:s', $actual);
            $res = $reservados[$horaIni] ?? null;
            $slots[] = [
                'hora_inicio' => $horaIni,
                'hora_fin'    => date('H:i:s', $actual + $dur),
                'reservado'   => $res !== null,
                'mio'         => $res !== null && (int)$res['padre_id'] === Auth::id(),
                'reserva'     => $res,
            ];
            $actual += $dur;
        }
        return $slots;
    }

    // ── DOCENTE: gestionar disponibilidad ───────────────────────
    public function index(): void {
        $this->verificarAcceso();

        if (Auth::rol() === 'docente') {
            $qb = $this->db->prepare(
                'SELECT * FROM citas_disponibilidad WHERE docente_id = ? AND fecha >= CURDATE() ORDER BY fecha, hora_inicio'
            );
            $qb->execute([Auth::id()]);
            $bloques = $qb->fetchAll();
            foreach ($bloques as &$b) { $b['slots'] = $this->slotsDeBloque($b); }
            unset($b);

            render('docente/citas_index', [
                'bloques' => $bloques,
                'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
            ]);
            return;
        }

        // Padre: listar docentes de sus hijos
        $qd = $this->db->prepare(
            "SELECT DISTINCT u.id, u.nombre, u.apellido
             FROM acudientes ac
             JOIN estudiantes e ON ac.estudiante_id = e.id
             JOIN matriculas mt ON mt.estudiante_id = e.id
             JOIN asignaciones a ON a.grupo_id = mt.grupo_id
             JOIN usuarios u ON u.id = a.docente_id
             WHERE ac.usuario_id = ?
             ORDER BY u.apellido"
        );
        $qd->execute([Auth::id()]);
        $docentes = $qd->fetchAll();

        $qm = $this->db->prepare(
            "SELECT r.id, r.hora_inicio, r.hora_fin, r.motivo, r.estado,
                    d.fecha, u.nombre AS doc_nombre, u.apellido AS doc_apellido, d.lugar,
                    es.nombre AS est_nombre
             FROM citas_reservas r
             JOIN citas_disponibilidad d ON d.id = r.disponibilidad_id
             JOIN usuarios u ON u.id = d.docente_id
             LEFT JOIN usuarios es ON es.id = (SELECT usuario_id FROM estudiantes WHERE id = r.estudiante_id)
             WHERE r.padre_id = ? AND r.estado = 'confirmada' AND d.fecha >= CURDATE()
             ORDER BY d.fecha, r.hora_inicio"
        );
        $qm->execute([Auth::id()]);
        $misCitas = $qm->fetchAll();

        render('padre/citas_index', [
            'docentes' => $docentes,
            'misCitas' => $misCitas,
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function crearDisponibilidad(): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        csrfVerify();

        $fecha = trim($_POST['fecha'] ?? '');
        $horaInicio = trim($_POST['hora_inicio'] ?? '');
        $horaFin = trim($_POST['hora_fin'] ?? '');
        $duracion = max(5, (int)($_POST['duracion_minutos'] ?? 20));
        $lugar = trim($_POST['lugar'] ?? '') ?: null;

        if (!$fecha || !$horaInicio || !$horaFin || $horaFin <= $horaInicio || strtotime($fecha) < strtotime(date('Y-m-d'))) {
            flashError('Verifica la fecha y el rango de horas (la fecha no puede ser pasada).');
            redirect('/citas');
            return;
        }

        $this->db->prepare(
            'INSERT INTO citas_disponibilidad (docente_id, fecha, hora_inicio, hora_fin, duracion_minutos, lugar)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([Auth::id(), $fecha, $horaInicio, $horaFin, $duracion, $lugar]);

        flashSuccess('Disponibilidad publicada.');
        redirect('/citas');
    }

    public function eliminarDisponibilidad(int $id): void {
        $this->verificarAcceso();
        Auth::requireRole('docente');
        csrfVerify();

        $qd = $this->db->prepare('SELECT * FROM citas_disponibilidad WHERE id = ? AND docente_id = ? LIMIT 1');
        $qd->execute([$id, Auth::id()]);
        $disponibilidad = $qd->fetch();
        if (!$disponibilidad) { abort(404); }

        $qr = $this->db->prepare('SELECT * FROM citas_reservas WHERE disponibilidad_id = ?');
        $qr->execute([$id]);
        $reservas = $qr->fetchAll();

        $this->db->prepare(
            'INSERT INTO papelera_general (colegio_id, tipo, descripcion, snapshot_json, eliminado_por)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            Tenant::id(), 'disponibilidad_cita',
            'Disponibilidad del ' . date('d/m/Y', strtotime($disponibilidad['fecha'])) . ' (' . count($reservas) . ' cita(s) reservada(s))',
            json_encode(['disponibilidad' => $disponibilidad, 'reservas' => $reservas]),
            Auth::id(),
        ]);

        $this->db->prepare('DELETE FROM citas_disponibilidad WHERE id = ? AND docente_id = ?')
                  ->execute([$id, Auth::id()]);

        flashSuccess('Disponibilidad eliminada. Si tenía citas reservadas, puedes restaurarla desde la Papelera.');
        redirect('/citas');
    }

    // ── PADRE: ver horarios de un docente y reservar ────────────
    public function verDocente(int $docenteId): void {
        $this->verificarAcceso();
        Auth::requireRole('padre');

        // Verificar que el docente da clase a alguno de sus hijos
        $chk = $this->db->prepare(
            "SELECT u.nombre, u.apellido FROM usuarios u
             WHERE u.id = ? AND u.rol = 'docente' AND u.colegio_id = ?
               AND EXISTS (
                   SELECT 1 FROM asignaciones a
                   JOIN matriculas mt ON mt.grupo_id = a.grupo_id
                   JOIN estudiantes e ON e.id = mt.estudiante_id
                   JOIN acudientes ac ON ac.estudiante_id = e.id
                   WHERE a.docente_id = u.id AND ac.usuario_id = ?
               )
             LIMIT 1"
        );
        $chk->execute([$docenteId, Tenant::id(), Auth::id()]);
        $docente = $chk->fetch();
        if (!$docente) { abort(403); }

        $qb = $this->db->prepare(
            'SELECT * FROM citas_disponibilidad WHERE docente_id = ? AND fecha >= CURDATE() ORDER BY fecha, hora_inicio'
        );
        $qb->execute([$docenteId]);
        $bloques = $qb->fetchAll();
        foreach ($bloques as &$b) { $b['slots'] = $this->slotsDeBloque($b); }
        unset($b);

        $qh = $this->db->prepare(
            'SELECT e.id, u.nombre, u.apellido FROM acudientes ac
             JOIN estudiantes e ON ac.estudiante_id = e.id
             JOIN usuarios u ON u.id = e.usuario_id
             WHERE ac.usuario_id = ?'
        );
        $qh->execute([Auth::id()]);
        $hijos = $qh->fetchAll();

        render('padre/citas_docente', [
            'docente' => $docente,
            'docenteId' => $docenteId,
            'bloques' => $bloques,
            'hijos' => $hijos,
        ]);
    }

    public function reservar(): void {
        $this->verificarAcceso();
        Auth::requireRole('padre');
        csrfVerify();

        $dispId = (int)($_POST['disponibilidad_id'] ?? 0);
        $horaInicio = trim($_POST['hora_inicio'] ?? '');
        $estudianteId = (int)($_POST['estudiante_id'] ?? 0) ?: null;
        $motivo = trim($_POST['motivo'] ?? '') ?: null;

        $qb = $this->db->prepare('SELECT * FROM citas_disponibilidad WHERE id = ?');
        $qb->execute([$dispId]);
        $bloque = $qb->fetch();
        if (!$bloque) { abort(404); }

        // Validar que el slot existe realmente dentro del bloque
        $slots = $this->slotsDeBloque($bloque);
        $slot = null;
        foreach ($slots as $s) { if ($s['hora_inicio'] === $horaInicio) { $slot = $s; break; } }
        if (!$slot) {
            flashError('Ese horario no es válido.');
            redirect('/citas/docente/' . $bloque['docente_id']);
            return;
        }
        if ($slot['reservado']) {
            flashError('Ese horario ya fue reservado por otra persona. Elige otro.');
            redirect('/citas/docente/' . $bloque['docente_id']);
            return;
        }

        // Validar que el estudiante, si se indicó, es hijo de este padre
        if ($estudianteId) {
            $chkH = $this->db->prepare('SELECT 1 FROM acudientes WHERE estudiante_id = ? AND usuario_id = ? LIMIT 1');
            $chkH->execute([$estudianteId, Auth::id()]);
            if (!$chkH->fetch()) { $estudianteId = null; }
        }

        try {
            $this->db->prepare(
                'INSERT INTO citas_reservas (disponibilidad_id, hora_inicio, hora_fin, padre_id, estudiante_id, motivo)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$dispId, $slot['hora_inicio'], $slot['hora_fin'], Auth::id(), $estudianteId, $motivo]);
        } catch (PDOException $e) {
            flashError('Ese horario ya fue reservado por otra persona. Elige otro.');
            redirect('/citas/docente/' . $bloque['docente_id']);
            return;
        }

        flashSuccess('Cita reservada para el ' . date('d/m/Y', strtotime($bloque['fecha'])) . ' a las ' . substr($slot['hora_inicio'], 0, 5) . '.');
        redirect('/citas');
    }

    public function cancelarReserva(int $id): void {
        $this->verificarAcceso();
        csrfVerify();

        $q = $this->db->prepare(
            'SELECT r.*, d.docente_id FROM citas_reservas r
             JOIN citas_disponibilidad d ON d.id = r.disponibilidad_id
             WHERE r.id = ? LIMIT 1'
        );
        $q->execute([$id]);
        $reserva = $q->fetch();
        if (!$reserva) { abort(404); }

        $esPropietario = (int)$reserva['padre_id'] === Auth::id();
        $esDocente = Auth::rol() === 'docente' && (int)$reserva['docente_id'] === Auth::id();
        if (!$esPropietario && !$esDocente) { abort(403); }

        $this->db->prepare("UPDATE citas_reservas SET estado = 'cancelada' WHERE id = ?")->execute([$id]);

        flashSuccess('Cita cancelada.');
        redirect('/citas');
    }
}
