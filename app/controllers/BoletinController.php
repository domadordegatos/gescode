<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class BoletinController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function verificarAcceso(): void {
        Auth::requireRole('admin', 'soporte', 'docente');
        $activo = (bool)$this->db->query(
            'SELECT permite_boletines_pdf FROM colegios WHERE id = ' . Tenant::id()
        )->fetchColumn();
        if (!$activo) {
            flashError('Esta función no está habilitada para tu institución. Pídele a soporte que la active.');
            redirect('/dashboard');
        }
    }

    public function index(): void {
        $this->verificarAcceso();
        render('admin/boletin_buscar');
    }

    public function grupos(): void {
        $this->verificarAcceso();
        $rol = Auth::rol();

        $sql = "SELECT g.id, gr.nombre AS grado, g.nombre AS grupo, gr.nivel,
                       (SELECT COUNT(*) FROM matriculas mt WHERE mt.grupo_id = g.id AND mt.estado = 'activo') AS total_est
                FROM grupos g
                JOIN grados gr ON gr.id = g.grado_id
                JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
                WHERE al.activo = 1 AND al.colegio_id = ?";
        $params = [Tenant::id()];
        if ($rol === 'docente') {
            $sql .= ' AND g.id IN (SELECT grupo_id FROM asignaciones WHERE docente_id = ?)';
            $params[] = Auth::id();
        }
        $sql .= ' ORDER BY gr.nivel, g.nombre';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        render('admin/boletin_grupos', ['grupos' => $st->fetchAll()]);
    }

    public function buscar(): void {
        $this->verificarAcceso();
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) { echo json_encode([]); return; }

        $rol = Auth::rol();
        $sql = "SELECT e.id AS estudiante_id, u.nombre, u.apellido, e.documento, mt.grupo_id,
                       (SELECT CONCAT(gr.nombre, ' ', g.nombre)
                        FROM grupos g JOIN grados gr ON gr.id = g.grado_id
                        WHERE g.id = mt.grupo_id) AS grupo_actual
                FROM estudiantes e
                JOIN usuarios u   ON u.id = e.usuario_id
                JOIN matriculas mt ON mt.estudiante_id = e.id
                JOIN grupos g2     ON g2.id = mt.grupo_id
                JOIN anos_lectivos al ON al.id = g2.ano_lectivo_id
                WHERE u.colegio_id = ? AND al.activo = 1
                  AND (u.nombre LIKE ? OR u.apellido LIKE ? OR e.documento LIKE ?)";
        $params = [Tenant::id(), "%$q%", "%$q%", "%$q%"];

        if ($rol === 'docente') {
            $sql .= ' AND mt.grupo_id IN (SELECT grupo_id FROM asignaciones WHERE docente_id = ?)';
            $params[] = Auth::id();
        }
        $sql .= ' ORDER BY u.apellido, u.nombre LIMIT 15';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        echo json_encode($st->fetchAll());
    }

    /** Devuelve [matricula_id, grupo_id, estudiante, familiares_ok] validando acceso del rol actual */
    private function matriculaDeEstudiante(int $estudianteId): ?array {
        $q = $this->db->prepare(
            'SELECT mt.id AS matricula_id, mt.grupo_id, e.documento, e.tipo_documento,
                    u.nombre, u.apellido,
                    gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano
             FROM matriculas mt
             JOIN estudiantes e   ON mt.estudiante_id = e.id
             JOIN usuarios u      ON u.id = e.usuario_id
             JOIN grupos g        ON g.id = mt.grupo_id
             JOIN grados gr       ON gr.id = g.grado_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE e.id = ? AND u.colegio_id = ? AND al.activo = 1
             ORDER BY mt.id DESC LIMIT 1'
        );
        $q->execute([$estudianteId, Tenant::id()]);
        $fila = $q->fetch();
        if (!$fila) return null;

        if (Auth::rol() === 'docente') {
            $chk = $this->db->prepare('SELECT 1 FROM asignaciones WHERE grupo_id = ? AND docente_id = ? LIMIT 1');
            $chk->execute([(int)$fila['grupo_id'], Auth::id()]);
            if (!$chk->fetch()) return null;
        }
        return $fila;
    }

    /** Valida acceso del rol actual a un grupo y devuelve sus datos básicos */
    private function grupoPermitido(int $grupoId): ?array {
        $q = $this->db->prepare(
            'SELECT g.id, gr.nombre AS grado, g.nombre AS grupo, al.nombre AS ano, al.id AS ano_id
             FROM grupos g
             JOIN grados gr ON gr.id = g.grado_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             JOIN colegios c ON c.id = ?
             WHERE g.id = ? AND al.activo = 1 LIMIT 1'
        );
        $q->execute([Tenant::id(), $grupoId]);
        $grupo = $q->fetch();
        if (!$grupo) return null;

        if (Auth::rol() === 'docente') {
            $chk = $this->db->prepare('SELECT 1 FROM asignaciones WHERE grupo_id = ? AND docente_id = ? LIMIT 1');
            $chk->execute([$grupoId, Auth::id()]);
            if (!$chk->fetch()) return null;
        }
        return $grupo;
    }

    public function fichaGrupo(int $grupoId): void {
        $this->verificarAcceso();
        $grupo = $this->grupoPermitido($grupoId);
        if (!$grupo) { abort(404); }

        $qp = $this->db->prepare('SELECT id, nombre, numero FROM periodos WHERE ano_lectivo_id = ? ORDER BY numero');
        $qp->execute([$grupo['ano_id']]);
        $periodos = $qp->fetchAll();

        $stCount = $this->db->prepare("SELECT COUNT(*) FROM matriculas WHERE grupo_id = ? AND estado = 'activo'");
        $stCount->execute([$grupoId]);
        $totalEst = (int)$stCount->fetchColumn();

        render('admin/boletin_grupo_ficha', ['grupo' => $grupo, 'periodos' => $periodos, 'totalEst' => $totalEst]);
    }

    public function generarGrupo(int $grupoId): void {
        $this->verificarAcceso();
        $grupo = $this->grupoPermitido($grupoId);
        if (!$grupo) { abort(404); }

        $qe = $this->db->prepare(
            "SELECT e.id FROM estudiantes e
             JOIN matriculas mt ON mt.estudiante_id = e.id
             JOIN usuarios u ON u.id = e.usuario_id
             WHERE mt.grupo_id = ? AND mt.estado = 'activo'
             ORDER BY u.apellido, u.nombre"
        );
        $qe->execute([$grupoId]);
        $estudianteIds = $qe->fetchAll(PDO::FETCH_COLUMN);

        if (!$estudianteIds) {
            flashError('Este grupo no tiene estudiantes activos.');
            redirect('/boletines/grupo/' . $grupoId);
            return;
        }

        $datosEstudiantes = [];
        foreach ($estudianteIds as $eid) {
            $datos = $this->prepararDatos((int)$eid);
            if ($datos) $datosEstudiantes[] = $datos;
        }

        ob_start();
        extract(['estudiantesData' => $datosEstudiantes, 'grupo' => $grupo], EXTR_SKIP);
        include ROOT . '/app/views/admin/boletin_pdf_lote.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'Boletines_' . preg_replace('/[^A-Za-z0-9_]/', '_', $grupo['grado'] . '_' . $grupo['grupo']) . '.pdf';
        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream($nombreArchivo, ['Attachment' => false]);
    }

    public function ficha(int $estudianteId): void {
        $this->verificarAcceso();
        $estudiante = $this->matriculaDeEstudiante($estudianteId);
        if (!$estudiante) { abort(404); }

        $qp = $this->db->prepare(
            'SELECT p.id, p.nombre, p.numero FROM periodos p
             JOIN grupos g ON g.ano_lectivo_id = p.ano_lectivo_id
             WHERE g.id = ? ORDER BY p.numero'
        );
        $qp->execute([(int)$estudiante['grupo_id']]);
        $periodos = $qp->fetchAll();

        render('admin/boletin_ficha', ['estudiante' => $estudiante, 'periodos' => $periodos, 'estudianteId' => $estudianteId]);
    }

    public function generar(int $estudianteId): void {
        $this->verificarAcceso();
        $datos = $this->prepararDatos($estudianteId);
        if (!$datos) { abort(404); }

        ob_start();
        extract($datos, EXTR_SKIP);
        include ROOT . '/app/views/admin/boletin_pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'Boletin_' . preg_replace('/[^A-Za-z0-9_]/', '_', $datos['estudiante']['apellido'] . '_' . $datos['estudiante']['nombre']) . '.pdf';
        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream($nombreArchivo, ['Attachment' => false]);
    }

    private function prepararDatos(int $estudianteId): ?array {
        $estudiante = $this->matriculaDeEstudiante($estudianteId);
        if (!$estudiante) return null;
        $mid = (int)$estudiante['matricula_id'];

        $periodoId = isset($_GET['periodo']) ? (int)$_GET['periodo'] : null;

        $qn = $this->db->prepare(
            'SELECT m.nombre AS materia, m.id AS materia_id,
                    p.id AS periodo_id, p.nombre AS periodo, p.numero, p.porcentaje_ano,
                    ROUND(AVG(c.valor), 1) AS promedio
             FROM calificaciones c
             JOIN evaluaciones ev     ON c.evaluacion_id = ev.id
             JOIN indicadores_eval ie ON ev.indicador_id = ie.id
             JOIN asignaciones a      ON ie.asignacion_id = a.id
             JOIN materias m          ON a.materia_id = m.id
             JOIN periodos p          ON ie.periodo_id = p.id
             WHERE c.matricula_id = ?
             GROUP BY m.id, p.id
             ORDER BY m.nombre, p.numero'
        );
        $qn->execute([$mid]);
        $notas_raw = $qn->fetchAll();

        $periodos = [];
        $materias = [];
        foreach ($notas_raw as $row) {
            $pid = (int)$row['periodo_id'];
            $periodos[$pid] = ['nombre' => $row['periodo'], 'numero' => (int)$row['numero'], 'porcentaje' => (float)$row['porcentaje_ano']];
            $mkid = (int)$row['materia_id'];
            if (!isset($materias[$mkid])) $materias[$mkid] = ['nombre' => $row['materia'], 'periodos' => [], 'promedio_final' => null];
            $materias[$mkid]['periodos'][$pid] = (float)$row['promedio'];
        }
        ksort($periodos);

        foreach ($materias as &$mat) {
            $suma = 0; $sumaPct = 0;
            foreach ($mat['periodos'] as $pid => $val) {
                $pct = $periodos[$pid]['porcentaje'] ?: (100 / max(count($periodos), 1));
                $suma += $val * $pct;
                $sumaPct += $pct;
            }
            $mat['promedio_final'] = $sumaPct > 0 ? round($suma / $sumaPct, 1) : null;
        }
        unset($mat);

        if ($periodoId !== null && !isset($periodos[$periodoId])) $periodoId = null;

        $desempenios = $this->db->query('SELECT * FROM desempenios ORDER BY orden')->fetchAll();
        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();

        // Asistencia resumida del año
        $qa = $this->db->prepare(
            "SELECT estado, COUNT(*) AS total FROM asistencia WHERE matricula_id = ? GROUP BY estado"
        );
        $qa->execute([$mid]);
        $asistencia = ['presente' => 0, 'tarde' => 0, 'excusa' => 0, 'ausente' => 0, 'incapacidad' => 0];
        foreach ($qa->fetchAll() as $r) {
            if (isset($asistencia[$r['estado']])) $asistencia[$r['estado']] = (int)$r['total'];
        }

        return [
            'estudiante'   => $estudiante,
            'colegio'      => $colegio,
            'materias'     => $materias,
            'periodos'     => $periodos,
            'periodo_id'   => $periodoId,
            'desempenios'  => $desempenios,
            'asistencia'   => $asistencia,
            'fecha_emision'=> date('d/m/Y'),
        ];
    }
}
