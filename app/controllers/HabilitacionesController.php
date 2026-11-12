<?php
class HabilitacionesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    // Lista las asignaciones del año activo donde el usuario (docente: las suyas; admin/soporte: todas)
    public function index(): void {
        Auth::requireRole('admin', 'soporte', 'docente');
        $cid = Tenant::id();

        $anoActivo = $this->db->prepare('SELECT id, nombre FROM anos_lectivos WHERE colegio_id = ? AND activo = 1 LIMIT 1');
        $anoActivo->execute([$cid]);
        $ano = $anoActivo->fetch();
        if (!$ano) { flashError('No hay un año lectivo activo.'); redirect('/dashboard'); return; }

        $esDocente = Auth::rol() === 'docente';
        $sql = "SELECT a.id AS asignacion_id, gr.nombre AS grado, g.nombre AS grupo, m.nombre AS materia,
                       (SELECT COUNT(*) FROM habilitaciones_anuales h
                        JOIN matriculas mt ON mt.id = h.matricula_id
                        WHERE h.asignacion_id = a.id AND mt.grupo_id = g.id) AS total_habilitados
                FROM asignaciones a
                JOIN grupos g ON g.id = a.grupo_id AND g.ano_lectivo_id = ?
                JOIN grados gr ON gr.id = g.grado_id
                JOIN materias m ON m.id = a.materia_id";
        $params = [$ano['id']];
        if ($esDocente) { $sql .= ' WHERE a.docente_id = ?'; $params[] = Auth::id(); }
        $sql .= ' ORDER BY gr.nivel, g.nombre, m.nombre';

        $st = $this->db->prepare($sql);
        $st->execute($params);

        render('admin/habilitaciones_lista', ['asignaciones' => $st->fetchAll(), 'ano' => $ano]);
    }

    // Estudiantes de una asignación cuya nota definitiva del año está por debajo del umbral de aprobación
    public function ver(int $asignacionId): void {
        Auth::requireRole('admin', 'soporte', 'docente');
        $cid = Tenant::id();

        $asig = $this->db->prepare(
            'SELECT a.*, g.nombre AS grupo, g.ano_lectivo_id, gr.nombre AS grado, m.nombre AS materia
             FROM asignaciones a JOIN grupos g ON g.id = a.grupo_id
             JOIN grados gr ON gr.id = g.grado_id JOIN materias m ON m.id = a.materia_id
             WHERE a.id = ? LIMIT 1'
        );
        $asig->execute([$asignacionId]);
        $asig = $asig->fetch();
        if (!$asig) { abort(404); }
        if (Auth::rol() === 'docente' && (int)$asig['docente_id'] !== Auth::id()) { abort(403); }

        $umbral = (float)$this->db->query("SELECT nota_aprobacion FROM colegios WHERE id = $cid")->fetchColumn();

        // Definitiva del año por estudiante: promedio de cada período ponderado por periodos.porcentaje_ano
        $sql = "SELECT mt.id AS matricula_id, ANY_VALUE(u.nombre) AS nombre, ANY_VALUE(u.apellido) AS apellido,
                       SUM(prom.promedio * p.porcentaje_ano) / NULLIF(SUM(p.porcentaje_ano), 0) AS definitiva_ano,
                       ANY_VALUE(h.nota_habilitacion) AS nota_habilitacion, ANY_VALUE(h.observacion) AS observacion
                FROM matriculas mt
                JOIN estudiantes e ON e.id = mt.estudiante_id
                JOIN usuarios u ON u.id = e.usuario_id
                JOIN (
                    SELECT c.matricula_id, ie.periodo_id, AVG(c.valor) AS promedio
                    FROM calificaciones c
                    JOIN evaluaciones ev ON ev.id = c.evaluacion_id
                    JOIN indicadores_eval ie ON ie.id = ev.indicador_id
                    WHERE ie.asignacion_id = ?
                    GROUP BY c.matricula_id, ie.periodo_id
                ) prom ON prom.matricula_id = mt.id
                JOIN periodos p ON p.id = prom.periodo_id
                LEFT JOIN habilitaciones_anuales h ON h.matricula_id = mt.id AND h.asignacion_id = ?
                WHERE mt.grupo_id = ? AND mt.estado = 'activo'
                GROUP BY mt.id
                HAVING definitiva_ano < ?
                ORDER BY u.apellido, u.nombre";
        $st = $this->db->prepare($sql);
        $st->execute([$asignacionId, $asignacionId, $asig['grupo_id'], $umbral]);

        render('admin/habilitaciones_detalle', [
            'asig' => $asig,
            'estudiantes' => $st->fetchAll(),
            'umbral' => $umbral,
        ]);
    }

    public function guardar(int $asignacionId): void {
        Auth::requireRole('admin', 'soporte', 'docente');
        csrfVerify();

        $asig = $this->db->prepare('SELECT docente_id, grupo_id FROM asignaciones WHERE id = ? LIMIT 1');
        $asig->execute([$asignacionId]);
        $asig = $asig->fetch();
        if (!$asig) { abort(404); }
        if (Auth::rol() === 'docente' && (int)$asig['docente_id'] !== Auth::id()) { abort(403); }

        $notas = $_POST['nota_habilitacion'] ?? [];
        $obs = $_POST['observacion'] ?? [];
        $guardadas = 0;

        foreach ($notas as $matriculaId => $valor) {
            $valor = trim((string)$valor);
            if ($valor === '') continue;
            $valor = (float)$valor;

            $chkMat = $this->db->prepare('SELECT 1 FROM matriculas WHERE id = ? AND grupo_id = ?');
            $chkMat->execute([$matriculaId, $asig['grupo_id']]);
            if (!$chkMat->fetch()) continue;

            $this->db->prepare(
                'INSERT INTO habilitaciones_anuales (matricula_id, asignacion_id, nota_habilitacion, observacion, registrado_por)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE nota_habilitacion = VALUES(nota_habilitacion), observacion = VALUES(observacion), registrado_por = VALUES(registrado_por)'
            )->execute([$matriculaId, $asignacionId, $valor, trim($obs[$matriculaId] ?? '') ?: null, Auth::id()]);
            $guardadas++;
        }

        if (class_exists('Auditoria')) {
            Auditoria::registrar('habilitacion.registrar', "Registró $guardadas nota(s) de habilitación anual en la asignación #$asignacionId.");
        }
        flashSuccess($guardadas ? "$guardadas nota(s) de habilitación guardada(s)." : 'No se guardó ninguna nota.');
        redirect("/habilitaciones/$asignacionId");
    }
}
