<?php
class AutoevaluacionController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('padre');
        $activo = (bool)$this->db->query(
            'SELECT permite_autoevaluacion FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    private function hijosDelPadre(): array {
        $st = $this->db->prepare(
            'SELECT u.nombre, u.apellido, e.id AS estudiante_id, mt.id AS matricula_id, mt.grupo_id
             FROM acudientes ac
             JOIN estudiantes e ON ac.estudiante_id = e.id
             JOIN usuarios u    ON e.usuario_id = u.id
             JOIN matriculas mt ON mt.estudiante_id = e.id
             JOIN grupos g      ON mt.grupo_id = g.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE ac.usuario_id = ? AND al.activo = 1
             ORDER BY u.nombre'
        );
        $st->execute([Auth::id()]);
        return $st->fetchAll();
    }

    public function index(): void {
        $this->verificarAcceso();
        $hijos = $this->hijosDelPadre();

        $colegio = $this->db->query(
            'SELECT nota_min, nota_max FROM colegios WHERE id = ' . Tenant::id()
        )->fetch();

        $pendientes = [];
        $respondidas = [];

        $qPend = $this->db->prepare(
            "SELECT ev.id AS evaluacion_id, ev.nombre AS eval_nombre, ie.descripcion AS logro,
                    m.nombre AS materia, p.nombre AS periodo, e.estudiante_id, e.matricula_id
             FROM evaluaciones ev
             JOIN indicadores_eval ie ON ev.indicador_id = ie.id
             JOIN asignaciones a      ON ie.asignacion_id = a.id
             JOIN materias m          ON a.materia_id = m.id
             JOIN periodos p          ON ie.periodo_id = p.id
             CROSS JOIN (SELECT ? AS estudiante_id, ? AS matricula_id, ? AS grupo_id) e
             WHERE ie.autoevaluacion = 1 AND a.grupo_id = e.grupo_id
               AND NOT EXISTS (SELECT 1 FROM calificaciones c WHERE c.evaluacion_id = ev.id AND c.matricula_id = e.matricula_id)
             ORDER BY p.numero, m.nombre"
        );
        $qResp = $this->db->prepare(
            "SELECT ev.nombre AS eval_nombre, ie.descripcion AS logro, m.nombre AS materia,
                    p.nombre AS periodo, c.valor, c.updated_at, e.estudiante_id
             FROM calificaciones c
             JOIN evaluaciones ev     ON c.evaluacion_id = ev.id
             JOIN indicadores_eval ie ON ev.indicador_id = ie.id
             JOIN asignaciones a      ON ie.asignacion_id = a.id
             JOIN materias m          ON a.materia_id = m.id
             JOIN periodos p          ON ie.periodo_id = p.id
             CROSS JOIN (SELECT ? AS estudiante_id, ? AS matricula_id) e
             WHERE ie.autoevaluacion = 1 AND c.matricula_id = e.matricula_id
             ORDER BY p.numero, m.nombre"
        );

        foreach ($hijos as $h) {
            $qPend->execute([$h['estudiante_id'], $h['matricula_id'], $h['grupo_id']]);
            foreach ($qPend->fetchAll() as $row) {
                $row['hijo_nombre'] = $h['nombre'] . ' ' . $h['apellido'];
                $pendientes[] = $row;
            }
            $qResp->execute([$h['estudiante_id'], $h['matricula_id']]);
            foreach ($qResp->fetchAll() as $row) {
                $row['hijo_nombre'] = $h['nombre'] . ' ' . $h['apellido'];
                $respondidas[] = $row;
            }
        }

        render('padre/autoevaluacion', [
            'pendientes' => $pendientes,
            'respondidas' => $respondidas,
            'notaMin' => (float)($colegio['nota_min'] ?? 1.0),
            'notaMax' => (float)($colegio['nota_max'] ?? 10.0),
            'mensajes_no_leidos' => (new Usuario())->totalNoLeidos(Auth::id()),
        ]);
    }

    public function guardar(int $estudianteId, int $evaluacionId): void {
        $this->verificarAcceso();
        csrfVerify();

        $hijos = $this->hijosDelPadre();
        $hijo = null;
        foreach ($hijos as $h) {
            if ((int)$h['estudiante_id'] === $estudianteId) { $hijo = $h; break; }
        }
        if (!$hijo) { abort(403); }

        // Verificar que la evaluación es realmente de autoevaluación y pertenece al grupo del estudiante
        $q = $this->db->prepare(
            'SELECT ev.id FROM evaluaciones ev
             JOIN indicadores_eval ie ON ev.indicador_id = ie.id
             JOIN asignaciones a      ON ie.asignacion_id = a.id
             WHERE ev.id = ? AND ie.autoevaluacion = 1 AND a.grupo_id = ? LIMIT 1'
        );
        $q->execute([$evaluacionId, $hijo['grupo_id']]);
        if (!$q->fetch()) { abort(403); }

        // No permitir sobrescribir una autoevaluación ya registrada
        $chk = $this->db->prepare('SELECT 1 FROM calificaciones WHERE evaluacion_id = ? AND matricula_id = ? LIMIT 1');
        $chk->execute([$evaluacionId, $hijo['matricula_id']]);
        if ($chk->fetch()) {
            flashError('Esta autoevaluación ya fue registrada.');
            redirect('/autoevaluacion');
            return;
        }

        $colegio = $this->db->query('SELECT nota_min, nota_max FROM colegios WHERE id = ' . Tenant::id())->fetch();
        $valor = (float)($_POST['valor'] ?? -1);
        if ($valor < (float)$colegio['nota_min'] || $valor > (float)$colegio['nota_max']) {
            flashError('La nota debe estar entre ' . $colegio['nota_min'] . ' y ' . $colegio['nota_max'] . '.');
            redirect('/autoevaluacion');
            return;
        }

        (new Evaluacion())->guardarCalificacion($evaluacionId, (int)$hijo['matricula_id'], $valor, Auth::id());

        flashSuccess('Autoevaluación registrada correctamente.');
        redirect('/autoevaluacion');
    }
}
