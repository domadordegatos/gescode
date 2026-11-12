<?php
class DocenteController {
    private Grupo      $grupoM;
    private Evaluacion $evalM;
    private Asistencia $asistM;
    private Tarea      $tareaM;

    public function __construct() {
        $this->grupoM = new Grupo();
        $this->evalM  = new Evaluacion();
        $this->asistM = new Asistencia();
        $this->tareaM = new Tarea();
    }

    // ── MIS GRUPOS ───────────────────────────────────────────

    public function misGrupos(): void {
        Auth::requireRole('docente');
        $grupos = $this->grupoM->porDocente(Auth::id());
        render('docente/mis_grupos', compact('grupos'));
    }

    // ── DETALLE GRUPO ─────────────────────────────────────────

    public function grupo(string $grupo_id): void {
        Auth::requireRole('docente');
        redirect('/grupos/' . (int)$grupo_id . '/estudiantes');
    }

    // ── ESTUDIANTES ───────────────────────────────────────────

    public function estudiantes(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid   = (int) $grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $estudiantes = $this->grupoM->estudiantes($gid);
        $periodo     = $this->grupoM->periodoActivo();
        render('docente/estudiantes', compact('grupo', 'estudiantes', 'periodo'));
    }

    // ── EVALUACIONES ──────────────────────────────────────────

    public function evaluaciones(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid = (int) $grupo_id;

        $todas_asig = $this->grupoM->porDocente(Auth::id());
        if (empty($todas_asig)) {
            flashError('No tienes grupos asignados.');
            redirect('/mis-grupos');
        }

        // Determinar asignación activa
        $asig_id = (int)($_GET['asig'] ?? 0);
        if (!$asig_id) {
            foreach ($todas_asig as $a) {
                if ((int)$a['grupo_id'] === $gid) { $asig_id = $a['asignacion_id']; break; }
            }
            if (!$asig_id) $asig_id = $todas_asig[0]['asignacion_id'];
        }

        $grupo = null;
        foreach ($todas_asig as $a) {
            if ((int)$a['asignacion_id'] === $asig_id) { $grupo = $a; break; }
        }
        if (!$grupo) { abort(403); }

        // Materias del mismo grupo (para el select de materia)
        $materias_grupo = array_values(array_filter(
            $todas_asig, fn($a) => (int)$a['grupo_id'] === (int)$grupo['grupo_id']
        ));

        // Grupos del mismo grado donde el docente tiene asignaciones (para el select de grupo)
        $grupos_grado = [];
        $vistos = [];
        foreach ($todas_asig as $a) {
            $key = $a['grupo_id']; // grupo_id
            if ($a['grado'] === $grupo['grado'] && !in_array($key, $vistos)) {
                $grupos_grado[] = ['grupo_id' => $a['grupo_id'], 'grupo' => $a['grupo'],
                                   'asignacion_id' => $a['asignacion_id']];
                $vistos[] = $key;
            }
        }

        $periodos   = $this->grupoM->periodos();
        $periodo_id = (int)($_GET['periodo'] ?? 0);
        if (!$periodo_id) {
            foreach ($periodos as $p) { if ($p['activo']) { $periodo_id = $p['id']; break; } }
            if (!$periodo_id && !empty($periodos)) $periodo_id = $periodos[0]['id'];
        }

        $indicadores = $this->evalM->indicadores($asig_id, $periodo_id);
        $fechas_imp  = $this->evalM->fechasImportantes($periodo_id);
        $periodo_obj = null;
        foreach ($periodos as $p) { if ((int)$p['id'] === $periodo_id) { $periodo_obj = $p; break; } }

        // Indicadores de períodos anteriores para el select de recuperación
        $ind_recuperables = [];
        $db = Database::get();
        $st = $db->prepare("
            SELECT ie.id, ie.codigo, ie.descripcion, p.nombre AS periodo_nombre, p.numero
            FROM indicadores_eval ie
            JOIN periodos p ON ie.periodo_id = p.id
            WHERE ie.asignacion_id = ? AND ie.periodo_id != ?
            ORDER BY p.numero, ie.orden
        ");
        $st->execute([$asig_id, $periodo_id]);
        foreach ($st->fetchAll() as $r) {
            $ind_recuperables[$r['periodo_nombre']][] = $r;
        }

        render('docente/evaluaciones', compact(
            'grupo', 'materias_grupo', 'grupos_grado', 'asig_id',
            'periodos', 'periodo_id', 'periodo_obj',
            'indicadores', 'fechas_imp', 'ind_recuperables'
        ));
    }

    // ── CALIFICAR por INDICADOR (matriz todas las evaluaciones) ──

    public function calificarIndicadorForm(string $ind_id): void {
        Auth::requireRole('docente');
        $db  = Database::get();

        // Cargar indicador + verificar propiedad
        $st = $db->prepare(
            'SELECT ie.*, a.docente_id, a.id AS asignacion_id,
                    g.id AS grupo_id, gr.nombre AS grado, g.nombre AS grupo,
                    m.nombre AS materia, p.nombre AS periodo_nombre, p.id AS periodo_id
             FROM indicadores_eval ie
             JOIN asignaciones a ON ie.asignacion_id=a.id
             JOIN grupos g       ON a.grupo_id=g.id
             JOIN grados gr      ON g.grado_id=gr.id
             JOIN materias m     ON a.materia_id=m.id
             JOIN periodos p     ON ie.periodo_id=p.id
             WHERE ie.id=? LIMIT 1'
        );
        $st->execute([(int)$ind_id]);
        $ind = $st->fetch();

        if (!$ind || $ind['docente_id'] != Auth::id()) {
            abort(403);
        }

        // Evaluaciones del indicador (columnas)
        $eval_st = $db->prepare(
            'SELECT id, nombre, fecha, porcentaje FROM evaluaciones
             WHERE indicador_id=? ORDER BY fecha, id'
        );
        $eval_st->execute([(int)$ind_id]);
        $evaluaciones = $eval_st->fetchAll();

        // Estudiantes del grupo (filas)
        $est_st = $db->prepare(
            'SELECT mt.id AS matricula_id, u.nombre, u.apellido
             FROM matriculas mt
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u    ON e.usuario_id=u.id
             WHERE mt.grupo_id=? AND mt.estado="activo"
             ORDER BY u.apellido, u.nombre'
        );
        $est_st->execute([$ind['grupo_id']]);
        $estudiantes = $est_st->fetchAll();

        // Notas existentes indexadas [eval_id][matricula_id]
        $notas = [];
        if (!empty($evaluaciones)) {
            $eids   = implode(',', array_column($evaluaciones, 'id'));
            $nota_st= $db->query(
                "SELECT evaluacion_id, matricula_id, valor FROM calificaciones
                 WHERE evaluacion_id IN ($eids)"
            );
            foreach ($nota_st->fetchAll() as $n) {
                $notas[$n['evaluacion_id']][$n['matricula_id']] = $n['valor'];
            }
        }

        // Calcular definitiva por estudiante (promedio ponderado)
        foreach ($estudiantes as &$est) {
            $ponderado = 0; $pct_calificado = 0;
            foreach ($evaluaciones as $ev) {
                $val = $notas[$ev['id']][$est['matricula_id']] ?? null;
                if ($val !== null) {
                    $ponderado      += (float)$val * ($ev['porcentaje'] / 100);
                    $pct_calificado += $ev['porcentaje'];
                }
            }
            $est['definitiva'] = $pct_calificado > 0
                ? round($ponderado / ($pct_calificado / 100) * ($pct_calificado / 100), 1)
                : null;
            // Simplificado: suma ponderada directa
            $est['definitiva'] = $pct_calificado > 0 ? round($ponderado, 1) : null;
        }

        render('docente/calificar_indicador',
               compact('ind', 'evaluaciones', 'estudiantes', 'notas'));
    }

    public function calificarIndicadorGuardar(string $ind_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $db  = Database::get();

        $st = $db->prepare(
            'SELECT ie.*, a.docente_id, g.id AS grupo_id
             FROM indicadores_eval ie
             JOIN asignaciones a ON ie.asignacion_id=a.id
             JOIN grupos g ON a.grupo_id=g.id
             WHERE ie.id=? LIMIT 1'
        );
        $st->execute([(int)$ind_id]);
        $ind = $st->fetch();
        if (!$ind || $ind['docente_id'] != Auth::id()) {
            abort(403);
        }

        // notas[eval_id][matricula_id] = valor
        $notas = $_POST['nota'] ?? [];
        foreach ($notas as $eval_id => $matriculas) {
            foreach ($matriculas as $mat_id => $valor) {
                $v = trim($valor) === '' ? null : max(notaMin(), min(notaMax(), (float)$valor));
                $this->evalM->guardarCalificacion((int)$eval_id, (int)$mat_id, $v);
            }
        }

        flashSuccess('Calificaciones guardadas correctamente.');
        if (($_POST['accion'] ?? 'continuar') === 'salir') {
            redirect('/grupos/' . $ind['grupo_id'] . '/evaluaciones?asig=' . $ind['asignacion_id'] . '&periodo=' . $ind['periodo_id']);
        }
        redirect('/indicador/' . (int)$ind_id . '/calificar');
    }

    // ── CALIFICAR evaluación individual (mantenido para compatibilidad) ──

    public function calificarForm(string $eval_id): void {
        Auth::requireRole('docente');
        $eid  = (int) $eval_id;
        $ev   = $this->evalM->getEvaluacion($eid);
        if (!$ev) { abort(404); }

        // Verificar que el docente sea propietario
        $asig = $this->grupoM->asignacionPorId($ev['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }

        $grupo_id    = $asig['grupo_id'];
        $estudiantes = $this->evalM->estudiantesParaCalificar($eid, $grupo_id);

        render('docente/calificar', compact('ev', 'asig', 'estudiantes', 'grupo_id'));
    }

    public function calificarGuardar(string $eval_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $eid  = (int) $eval_id;
        $ev   = $this->evalM->getEvaluacion($eid);
        if (!$ev) { abort(404); }

        $asig = $this->grupoM->asignacionPorId($ev['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }

        $notas = $_POST['nota'] ?? [];
        foreach ($notas as $mat_id => $valor) {
            $v = trim($valor) === '' ? null : (float)$valor;
            if ($v !== null) $v = max(notaMin(), min(notaMax(), $v));
            $this->evalM->guardarCalificacion($eid, (int)$mat_id, $v);
        }

        flashSuccess('Calificaciones guardadas.');
        redirect('/grupos/' . $asig['grupo_id'] . '/evaluaciones?asig=' . $ev['asignacion_id'] . '&periodo=' . $ev['periodo_id']);
    }

    // ── CRUD INDICADORES ─────────────────────────────────────

    public function crearIndicador(string $grupo_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $asig_id    = (int)($_POST['asig_id'] ?? 0);
        $periodo_id = (int)($_POST['periodo_id'] ?? 0);
        $desc       = trim($_POST['descripcion'] ?? '');
        $peso       = (float)($_POST['peso'] ?? 1.00);

        $asig = $this->grupoM->asignacionPorId($asig_id, Auth::id());
        if (!$asig || !$desc) { flashError('Datos incompletos.'); redirect('/grupos/'.(int)$grupo_id.'/evaluaciones'); return; }

        $this->evalM->crearIndicador($asig_id, $periodo_id, $desc, $peso);
        flashSuccess('Indicador creado correctamente.');
        redirect('/grupos/' . $asig['grupo_id'] . '/evaluaciones?asig=' . $asig_id . '&periodo=' . $periodo_id);
    }

    public function editarIndicadorForm(string $ind_id): void {
        Auth::requireRole('docente');
        $db  = Database::get();
        $st  = $db->prepare(
            'SELECT ie.*, a.docente_id, g.id AS grupo_id,
                    gr.nombre AS grado, g.nombre AS grupo,
                    m.nombre AS materia, al.nombre AS ano,
                    p.nombre AS periodo_nombre
             FROM indicadores_eval ie
             JOIN asignaciones a   ON ie.asignacion_id = a.id
             JOIN grupos g         ON a.grupo_id = g.id
             JOIN grados gr        ON g.grado_id = gr.id
             JOIN materias m       ON a.materia_id = m.id
             JOIN periodos p       ON ie.periodo_id = p.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE ie.id = ? LIMIT 1'
        );
        $st->execute([(int)$ind_id]);
        $ind = $st->fetch();
        if (!$ind || $ind['docente_id'] != Auth::id()) { abort(403); }

        $desempenios = $db->query('SELECT * FROM desempenios ORDER BY orden DESC')->fetchAll();
        render('docente/editar_indicador', compact('ind', 'desempenios'));
    }

    public function editarIndicadorGuardar(string $ind_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $db  = Database::get();
        $st  = $db->prepare('SELECT ie.*, a.docente_id, g.id AS grupo_id FROM indicadores_eval ie JOIN asignaciones a ON ie.asignacion_id=a.id JOIN grupos g ON a.grupo_id=g.id WHERE ie.id=? LIMIT 1');
        $st->execute([(int)$ind_id]);
        $ind = $st->fetch();
        if (!$ind || $ind['docente_id'] != Auth::id()) { abort(403); }

        $desc          = trim($_POST['descripcion']   ?? '');
        $desc2         = trim($_POST['descripcion_2'] ?? '') ?: null;
        $peso          = max(0.1, (float)($_POST['peso'] ?? 1));
        $privado       = empty($_POST['privado'])       ? 0 : 1;
        $autoevaluacion= empty($_POST['autoevaluacion'])? 0 : 1;
        $red_tipo      = ($_POST['redaccion_tipo'] ?? '') === 'personalizado' ? 'personalizado' : 'prefijo';
        $desc_superior = trim($_POST['desc_superior'] ?? '') ?: null;
        $desc_alto     = trim($_POST['desc_alto']     ?? '') ?: null;
        $desc_basico   = trim($_POST['desc_basico']   ?? '') ?: null;
        $desc_inferior = trim($_POST['desc_inferior'] ?? '') ?: null;

        $db->prepare(
            'UPDATE indicadores_eval
             SET descripcion=?, descripcion_2=?, peso=?, privado=?, autoevaluacion=?,
                 redaccion_tipo=?, desc_superior=?, desc_alto=?, desc_basico=?, desc_inferior=?
             WHERE id=?'
        )->execute([$desc, $desc2, $peso, $privado, $autoevaluacion, $red_tipo,
                    $desc_superior, $desc_alto, $desc_basico, $desc_inferior,
                    (int)$ind_id]);

        flashSuccess('Indicador actualizado.');
        redirect('/grupos/' . $ind['grupo_id'] . '/evaluaciones?asig=' . $ind['asignacion_id'] . '&periodo=' . $ind['periodo_id']);
    }

    public function eliminarIndicador(string $ind_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $db = Database::get();
        $st = $db->prepare('SELECT ie.*, a.docente_id, g.id AS grupo_id FROM indicadores_eval ie JOIN asignaciones a ON ie.asignacion_id=a.id JOIN grupos g ON a.grupo_id=g.id WHERE ie.id=?');
        $st->execute([(int)$ind_id]);
        $ind = $st->fetch();
        if (!$ind || $ind['docente_id'] != Auth::id()) { abort(403); }
        $this->evalM->eliminarIndicador((int)$ind_id, Auth::id());
        flashSuccess('Indicador eliminado.');
        redirect('/grupos/' . $ind['grupo_id'] . '/evaluaciones?asig=' . $ind['asignacion_id'] . '&periodo=' . $ind['periodo_id']);
    }

    // ── CRUD TIPOS DE EVALUACIÓN ─────────────────────────────

    public function crearTipoEval(): void {
        Auth::requireRole('docente');
        csrfVerify();
        $ind_id   = (int)($_POST['indicador_id'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $pct      = max(0.01, min(100, (float)($_POST['porcentaje'] ?? 100)));
        $privado  = empty($_POST['privado'])  ? 0 : 1;
        $opcional = empty($_POST['opcional']) ? 0 : 1;
        $gid      = (int)($_POST['grupo_id'] ?? 0);
        $asig_id  = (int)($_POST['asig_id'] ?? 0);
        $per_id   = (int)($_POST['periodo_id'] ?? 0);

        $db = Database::get();
        $st = $db->prepare('SELECT ie.*, a.docente_id FROM indicadores_eval ie JOIN asignaciones a ON ie.asignacion_id=a.id WHERE ie.id=? LIMIT 1');
        $st->execute([$ind_id]);
        $ind = $st->fetch();
        if (!$ind || $ind['docente_id'] != Auth::id() || !$nombre) {
            flashError('Datos incompletos.'); redirect('/grupos/'.$gid.'/evaluaciones'); return;
        }

        $tipo_id = $this->evalM->crearTipoEval($ind_id, $nombre, $pct, $privado, $opcional);

        // Crear evaluaciones inline si se especificó cantidad
        $cant_eval  = max(0, min(20, (int)($_POST['eval_cantidad'] ?? 0)));
        $eval_nom   = trim($_POST['eval_nombre'] ?? '');
        $eval_pct   = (float)($_POST['eval_porcentaje'] ?? 100);
        $eval_priv  = empty($_POST['eval_privado'])  ? 0 : 1;
        $eval_opc   = empty($_POST['eval_opcional']) ? 0 : 1;
        if ($cant_eval > 0 && $eval_nom !== '') {
            $pct_unit = $cant_eval > 1 ? round($eval_pct / $cant_eval, 2) : $eval_pct;
            $ins = $db->prepare(
                'INSERT INTO evaluaciones (indicador_id, tipo_eval_id, nombre, fecha, porcentaje, privado, opcional)
                 VALUES (?,?,?,?,?,?,?)'
            );
            for ($i = 1; $i <= $cant_eval; $i++) {
                $nom = $cant_eval > 1 ? "$eval_nom $i" : $eval_nom;
                $ins->execute([$ind_id, $tipo_id, $nom, date('Y-m-d'), $pct_unit, $eval_priv, $eval_opc]);
            }
        }

        flashSuccess("Tipo «$nombre» creado." . ($cant_eval > 0 ? " $cant_eval evaluación(es) agregadas." : ''));
        redirect('/grupos/' . $gid . '/evaluaciones?asig=' . $asig_id . '&periodo=' . $per_id);
    }

    public function eliminarTipoEval(string $tipo_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $tipo = $this->evalM->getTipoEval((int)$tipo_id);
        if (!$tipo || $tipo['docente_id'] != Auth::id()) { abort(403); }
        $this->evalM->eliminarTipoEval((int)$tipo_id);
        flashSuccess('Tipo eliminado.');
        redirect('/grupos/' . $tipo['grupo_id'] . '/evaluaciones?asig=' . $tipo['asignacion_id'] . '&periodo=' . $tipo['periodo_id']);
    }

    // ── CRUD EVALUACIONES ────────────────────────────────────

    public function crearEvaluacion(): void {
        Auth::requireRole('docente');
        csrfVerify();
        $ind_id        = (int)($_POST['indicador_id'] ?? 0);
        $tipo_eval_id  = (int)($_POST['tipo_eval_id'] ?? 0) ?: null;
        $nombre        = trim($_POST['nombre'] ?? '');
        $fecha         = $_POST['fecha'] ?? date('Y-m-d');
        $pct           = (float)($_POST['porcentaje'] ?? 100);
        $gid           = (int)($_POST['grupo_id'] ?? 0);
        $asig_id       = (int)($_POST['asig_id'] ?? 0);
        $per_id        = (int)($_POST['periodo_id'] ?? 0);
        $privado       = empty($_POST['privado'])       ? 0 : 1;
        $opcional      = empty($_POST['opcional'])      ? 0 : 1;
        $cantidad      = max(1, min(20, (int)($_POST['cantidad'] ?? 1)));
        $recup_de      = (int)($_POST['recuperacion_de'] ?? 0) ?: null;

        $db = Database::get();
        $st = $db->prepare('SELECT ie.*, a.docente_id FROM indicadores_eval ie JOIN asignaciones a ON ie.asignacion_id=a.id WHERE ie.id=? LIMIT 1');
        $st->execute([$ind_id]);
        $ind = $st->fetch();
        if (!$ind || $ind['docente_id'] != Auth::id() || !$nombre) {
            flashError('Datos incompletos.'); redirect('/grupos/'.$gid.'/evaluaciones'); return;
        }

        // Verificar que el tipo pertenezca al indicador
        if ($tipo_eval_id) {
            $st2 = $db->prepare('SELECT id FROM tipos_eval WHERE id=? AND indicador_id=? LIMIT 1');
            $st2->execute([$tipo_eval_id, $ind_id]);
            if (!$st2->fetch()) { $tipo_eval_id = null; }
        }

        // Validar que la evaluación a recuperar pertenezca al mismo indicador
        if ($recup_de) {
            $st3 = $db->prepare('SELECT id FROM evaluaciones WHERE id=? AND indicador_id=? LIMIT 1');
            $st3->execute([$recup_de, $ind_id]);
            if (!$st3->fetch()) { $recup_de = null; }
        }

        $pct_unit = $cantidad > 1 ? round($pct / $cantidad, 2) : $pct;
        $ins = $db->prepare(
            'INSERT INTO evaluaciones (indicador_id, tipo_eval_id, nombre, fecha, porcentaje, privado, opcional, recuperacion_de)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        for ($i = 1; $i <= $cantidad; $i++) {
            $nom = $cantidad > 1 ? "$nombre $i" : $nombre;
            $ins->execute([$ind_id, $tipo_eval_id, $nom, $fecha, $pct_unit, $privado, $opcional, $recup_de]);
        }

        flashSuccess($cantidad > 1 ? "$cantidad evaluaciones agregadas." : 'Evaluación agregada.');
        redirect('/grupos/' . $gid . '/evaluaciones?asig=' . $asig_id . '&periodo=' . $per_id);
    }

    public function eliminarEvaluacion(string $eval_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $ev = $this->evalM->getEvaluacion((int)$eval_id);
        if (!$ev) { abort(404); }
        $asig = $this->grupoM->asignacionPorId($ev['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }
        $this->evalM->eliminarEvaluacion((int)$eval_id, Auth::id());
        flashSuccess('Evaluación eliminada.');
        redirect('/grupos/' . $asig['grupo_id'] . '/evaluaciones?asig=' . $ev['asignacion_id'] . '&periodo=' . $ev['periodo_id']);
    }

    // ── CONSOLIDADO POR INDICADORES ───────────────────────────

    public function consolidadoIndicadores(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid     = (int) $grupo_id;
        $asig_id = (int)($_GET['asig'] ?? 0);
        $per_id  = (int)($_GET['periodo'] ?? 0);

        $asig = $this->grupoM->asignacionPorId($asig_id, Auth::id());
        if (!$asig) { abort(403); }

        $periodos = $this->grupoM->periodos();
        if (!$per_id && !empty($periodos)) {
            foreach ($periodos as $p) { if ($p['activo']) { $per_id = $p['id']; break; } }
        }

        $data  = $this->evalM->consolidadoPorIndicadores($asig_id, $per_id);
        $grupo = $asig;

        render('docente/consolidado_indicadores',
            compact('grupo', 'asig_id', 'per_id', 'periodos') + $data);
    }

    // ── CONSOLIDADO POR PERÍODO ───────────────────────────────

    public function consolidadoPeriodo(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid     = (int) $grupo_id;
        $asig_id = (int)($_GET['asig'] ?? 0);

        $asig = $this->grupoM->asignacionPorId($asig_id, Auth::id());
        if (!$asig) { abort(403); }

        $todos_periodos = $this->grupoM->periodos();
        $periodo_fil    = (int)($_GET['periodo_fil'] ?? 0);
        $estado_fil     = $_GET['estado']    ?? 'activo';
        $modo_nota      = $_GET['modo_nota'] ?? 'valor';

        // Acumulativo: mostrar desde el primer período hasta el seleccionado.
        // Si periodo_fil=0 → hasta el período activo (inclusive).
        if ($periodo_fil > 0) {
            $periodos = [];
            foreach ($todos_periodos as $p) {
                $periodos[] = $p;
                if ((int)$p['id'] === $periodo_fil) break;
            }
        } else {
            // Cortar en el período activo; si ninguno está activo, mostrar solo el primero
            $periodos = [];
            foreach ($todos_periodos as $p) {
                $periodos[] = $p;
                if ((int)$p['activo'] === 1) break;
            }
            if (empty($periodos)) $periodos = array_slice($todos_periodos, 0, 1);
        }

        $db = Database::get();

        // Desempeños para convertir a texto
        $desempenios = $db->query('SELECT * FROM desempenios ORDER BY orden')->fetchAll();

        // Estudiantes según filtro de estado
        $estados_ok = ['activo','retirado','reprobado','promovido','pendiente','suspendido','egresado','inscrito'];
        $estado_sql = in_array($estado_fil, $estados_ok) ? $estado_fil : 'activo';

        $st = $db->prepare("
            SELECT u.id AS usuario_id, u.nombre, u.apellido,
                   e.id AS estudiante_id, e.genero,
                   mt.id AS matricula_id, mt.estado
            FROM matriculas mt
            JOIN estudiantes e ON e.id  = mt.estudiante_id
            JOIN usuarios u    ON u.id  = e.usuario_id
            WHERE mt.grupo_id = ? AND mt.estado = ?
            ORDER BY u.apellido, u.nombre
        ");
        $st->execute([$asig['grupo_id'], $estado_sql]);
        $estudiantes = $st->fetchAll();

        // Para cada estudiante, calcular nota por período
        foreach ($estudiantes as &$est) {
            $est['notas_periodo'] = [];
            $suma_pond  = 0;
            $pct_total  = 0; // suma de porcentaje_ano de períodos con nota

            foreach ($periodos as $p) {
                $nota_p = $this->notaPeriodo($asig_id, (int)$p['id'], $est['matricula_id'], $db);
                $est['notas_periodo'][$p['id']] = $nota_p;

                $pct = (float)($p['porcentaje_ano'] ?? 25);
                if ($nota_p !== null) {
                    $suma_pond += $nota_p * $pct;
                    $pct_total += $pct;
                }
            }

            // Promedio ponderado normalizado sobre el total de porcentaje calificado
            $est['promedio']   = $pct_total > 0 ? round($suma_pond / $pct_total, 1) : null;
            $est['definitiva'] = $est['promedio'];
        }
        unset($est);

        $grupo = $asig;
        render('docente/consolidado_periodo',
            compact('grupo', 'asig_id', 'periodos', 'todos_periodos', 'estudiantes',
                    'estado_fil', 'modo_nota', 'periodo_fil', 'desempenios'));
    }

    private function notaPeriodo(int $asig_id, int $periodo_id, int $matricula_id, PDO $db): ?float {
        // Paso 1: obtener indicadores del período con su peso
        $st = $db->prepare(
            'SELECT id, peso FROM indicadores_eval
             WHERE asignacion_id = ? AND periodo_id = ?'
        );
        $st->execute([$asig_id, $periodo_id]);
        $indicadores = $st->fetchAll();
        if (empty($indicadores)) return null;

        $suma_pond  = 0.0;
        $peso_total = 0.0;
        $con_nota   = false;

        foreach ($indicadores as $ind) {
            // Nota del indicador = promedio ponderado de sus evaluaciones
            $st2 = $db->prepare("
                SELECT SUM(c.valor * ev.porcentaje / 100)                              AS ponderado,
                       SUM(CASE WHEN c.valor IS NOT NULL THEN ev.porcentaje ELSE 0 END) AS pct_calif
                FROM evaluaciones ev
                LEFT JOIN calificaciones c ON c.evaluacion_id = ev.id AND c.matricula_id = ?
                WHERE ev.indicador_id = ?
            ");
            $st2->execute([$matricula_id, $ind['id']]);
            $r = $st2->fetch();

            if (!$r || (float)$r['pct_calif'] == 0) continue;

            // Normalizar al porcentaje calificado: nota = ponderado * 100 / pct_calif
            $nota_ind    = (float)$r['ponderado'] * 100 / (float)$r['pct_calif'];
            $peso        = (float)$ind['peso'];
            $suma_pond  += $nota_ind * $peso;
            $peso_total += $peso;
            $con_nota    = true;
        }

        if (!$con_nota || $peso_total == 0) return null;
        return round($suma_pond / $peso_total, 1);
    }

    // ── ASISTENCIA ────────────────────────────────────────────

    public function asistencia(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid   = (int) $grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $fecha       = $_GET['fecha'] ?? date('Y-m-d');
        $estudiantes = $this->asistM->porFecha($gid, $fecha);
        $mes         = substr($fecha, 0, 7);
        $registradas = $this->asistM->fechasRegistradas($gid, $mes);

        render('docente/asistencia', compact('grupo', 'fecha', 'estudiantes', 'registradas'));
    }

    public function guardarAsistencia(string $grupo_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $gid   = (int) $grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $fecha     = $_POST['fecha'] ?? date('Y-m-d');
        $registros = $_POST['asistencia'] ?? [];
        $estados_v = ['presente','ausente','tarde','excusa','incapacidad'];
        $db        = Database::get();
        $alertar   = []; // mat_id => estado para notificar

        foreach ($registros as $mat_id => $estado) {
            if (in_array($estado, $estados_v)) {
                $obs = trim($_POST['obs'][$mat_id] ?? '');
                $reportadoPor = trim($_POST['reportado_por'][$mat_id] ?? '');
                $this->asistM->guardar((int)$mat_id, $fecha, $estado, $obs, Auth::id(), $reportadoPor);
                if (in_array($estado, ['ausente','tarde','excusa','incapacidad'])) {
                    $alertar[(int)$mat_id] = $estado;
                }
            }
        }

        // Notificar padres/estudiante cuando hay inasistencias
        if ($alertar) {
            $estadoLabel = ['ausente'=>'Ausente','tarde'=>'Tardanza','excusa'=>'Excusa justificada','incapacidad'=>'Incapacidad médica'];
            foreach ($alertar as $mat_id => $estado) {
                $stDest = $db->prepare("
                    SELECT DISTINCT u.id
                    FROM matriculas mt
                    JOIN estudiantes e ON e.id=mt.estudiante_id
                    JOIN usuarios eu   ON eu.id=e.usuario_id AND eu.activo=1
                    LEFT JOIN acudientes a ON a.estudiante_id=e.id
                    LEFT JOIN usuarios u   ON u.id=a.usuario_id AND u.activo=1
                    WHERE mt.id=?
                    UNION
                    SELECT eu2.id FROM matriculas mt2
                    JOIN estudiantes e2 ON e2.id=mt2.estudiante_id
                    JOIN usuarios eu2   ON eu2.id=e2.usuario_id AND eu2.activo=1
                    WHERE mt2.id=?
                ");
                $stDest->execute([$mat_id, $mat_id]);
                $dests = $stDest->fetchAll(PDO::FETCH_COLUMN);

                $stEst = $db->prepare(
                    'SELECT u.nombre, u.apellido FROM matriculas mt
                     JOIN estudiantes e ON e.id=mt.estudiante_id
                     JOIN usuarios u ON u.id=e.usuario_id WHERE mt.id=? LIMIT 1'
                );
                $stEst->execute([$mat_id]);
                $est = $stEst->fetch();
                $estNombre = $est ? $est['nombre'] . ' ' . $est['apellido'] : 'el/la estudiante';

                if ($dests) {
                    $etiqueta = $estadoLabel[$estado] ?? $estado;
                    Notificador::notificar(
                        'asistencia',
                        $dests,
                        'Registro de asistencia — ' . APP_NAME,
                        'Registro de asistencia',
                        '<p>Se ha registrado el estado <strong>' . htmlspecialchars($etiqueta) . '</strong> '
                        . 'para <strong>' . htmlspecialchars($estNombre) . '</strong> el día <strong>' . htmlspecialchars($fecha) . '</strong>.</p>',
                        APP_URL . '/dashboard',
                        'Ir al inicio'
                    );
                }
            }
        }

        flashSuccess("Asistencia del $fecha guardada.");
        redirect("/grupos/$gid/asistencia?fecha=$fecha");
    }

    // ── OBSERVACIONES ─────────────────────────────────────────

    public function observaciones(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid   = (int) $grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $db = Database::get();
        $st = $db->prepare(
            'SELECT ob.*, u.nombre, u.apellido,
                    du.nombre AS doc_nombre, du.apellido AS doc_apellido
             FROM observaciones ob
             JOIN matriculas mt ON ob.matricula_id=mt.id
             JOIN estudiantes e ON mt.estudiante_id=e.id
             JOIN usuarios u ON e.usuario_id=u.id
             JOIN usuarios du ON ob.docente_id=du.id
             WHERE mt.grupo_id=?
             ORDER BY ob.fecha DESC, ob.id DESC'
        );
        $st->execute([$gid]);
        $observaciones = $st->fetchAll();
        $estudiantes   = $this->grupoM->estudiantes($gid);

        // Lecturas por acudiente: observacion_id => [ ['nombre'=>..., 'visto_at'=>...], ... ]
        $qv = $db->prepare(
            'SELECT ov.observacion_id,
                    u.nombre AS pad_nombre, u.apellido AS pad_apellido,
                    ov.visto_at
             FROM observaciones_vistas ov
             JOIN usuarios u ON ov.usuario_id = u.id
             JOIN observaciones ob ON ob.id = ov.observacion_id
             JOIN matriculas mt ON ob.matricula_id = mt.id
             WHERE mt.grupo_id = ?
             ORDER BY ov.visto_at ASC'
        );
        $qv->execute([$gid]);
        $lecturas_raw = $qv->fetchAll();
        $lecturas = [];
        foreach ($lecturas_raw as $lr) {
            $lecturas[$lr['observacion_id']][] = [
                'nombre'   => $lr['pad_nombre'] . ' ' . $lr['pad_apellido'],
                'visto_at' => $lr['visto_at'],
            ];
        }

        render('docente/observaciones', compact('grupo', 'observaciones', 'estudiantes', 'lecturas'));
    }

    public function guardarObservacion(string $grupo_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $gid   = (int) $grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $mat_id = (int)($_POST['matricula_id'] ?? 0);
        $tipo   = $_POST['tipo'] ?? 'academica';
        $desc   = trim($_POST['descripcion'] ?? '');
        $fecha  = date('Y-m-d');
        $tipos_ok = ['academica','convivencia','logro','otro'];

        if (!$mat_id || !$desc) {
            flashError('Selecciona un estudiante y escribe la observación.');
        } else {
            $db = Database::get();
            $db->prepare(
                'INSERT INTO observaciones (matricula_id, docente_id, tipo, descripcion, fecha)
                 VALUES (?,?,?,?,?)'
            )->execute([$mat_id, Auth::id(), in_array($tipo,$tipos_ok)?$tipo:'academica', $desc, $fecha]);

            // Notificar a padres/acudientes y al estudiante
            $stDest = $db->prepare("
                SELECT DISTINCT u.id
                FROM matriculas mt
                JOIN estudiantes e  ON e.id = mt.estudiante_id
                JOIN usuarios eu    ON eu.id = e.usuario_id
                LEFT JOIN acudientes a ON a.estudiante_id = e.id
                LEFT JOIN usuarios u   ON u.id = a.usuario_id AND u.activo=1
                WHERE mt.id = ?
                UNION
                SELECT eu2.id FROM matriculas mt2
                JOIN estudiantes e2 ON e2.id=mt2.estudiante_id
                JOIN usuarios eu2   ON eu2.id=e2.usuario_id AND eu2.activo=1
                WHERE mt2.id = ?
            ");
            $stDest->execute([$mat_id, $mat_id]);
            $dests = $stDest->fetchAll(PDO::FETCH_COLUMN);

            // Nombre del estudiante para el cuerpo
            $stEst = $db->prepare(
                'SELECT u.nombre, u.apellido FROM matriculas mt
                 JOIN estudiantes e ON e.id=mt.estudiante_id
                 JOIN usuarios u ON u.id=e.usuario_id WHERE mt.id=? LIMIT 1'
            );
            $stEst->execute([$mat_id]);
            $est = $stEst->fetch();
            $estNombre = $est ? $est['nombre'] . ' ' . $est['apellido'] : 'el/la estudiante';
            $tipoLabel = ['academica'=>'Académica','convivencia'=>'Convivencia','logro'=>'Logro','otro'=>'Otra'][$tipo] ?? $tipo;

            if ($dests) {
                Notificador::notificar(
                    'observacion',
                    $dests,
                    'Nueva observación registrada — ' . APP_NAME,
                    'Nueva observación',
                    '<p>Se ha registrado una observación de tipo <strong>' . htmlspecialchars($tipoLabel) . '</strong> para <strong>' . htmlspecialchars($estNombre) . '</strong>:</p>'
                    . '<blockquote style="border-left:3px solid #0E0EB3;margin:12px 0;padding:8px 16px;background:#f0f2f5;border-radius:4px;font-style:italic">'
                    . htmlspecialchars($desc) . '</blockquote>',
                    APP_URL . '/grupos/' . $gid . '/observaciones',
                    'Ver observaciones'
                );
            }

            flashSuccess('Observación registrada.');
        }
        redirect("/grupos/$gid/observaciones");
    }

    public function editarObservacion(string $grupo_id, string $obs_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $gid  = (int)$grupo_id;
        $oid  = (int)$obs_id;
        $db   = Database::get();

        $ob = $db->prepare('SELECT * FROM observaciones WHERE id=? AND docente_id=?');
        $ob->execute([$oid, Auth::id()]);
        $ob = $ob->fetch();
        if (!$ob) { abort(403); }

        $tipos_ok = ['academica','convivencia','logro','otro'];
        $tipo = in_array($_POST['tipo'] ?? '', $tipos_ok) ? $_POST['tipo'] : $ob['tipo'];
        $desc = trim($_POST['descripcion'] ?? '');

        if ($desc) {
            $db->prepare('UPDATE observaciones SET tipo=?, descripcion=? WHERE id=?')
               ->execute([$tipo, $desc, $oid]);
            flashSuccess('Observacion actualizada.');
        }
        redirect("/grupos/$gid/observaciones");
    }

    public function borrarObservacion(string $grupo_id, string $obs_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $gid = (int)$grupo_id;
        $oid = (int)$obs_id;
        $db  = Database::get();

        $ob = $db->prepare('SELECT id FROM observaciones WHERE id=? AND docente_id=?');
        $ob->execute([$oid, Auth::id()]);
        if (!$ob->fetch()) { abort(403); }

        $db->prepare('DELETE FROM observaciones WHERE id=?')->execute([$oid]);
        flashSuccess('Observacion eliminada.');
        redirect("/grupos/$gid/observaciones");
    }

    // ── TAREAS ────────────────────────────────────────────────

    private const TAREA_MIME = [
        'application/pdf','application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg','image/png','image/gif','image/webp',
        'text/plain','text/csv','application/zip',
    ];

    // Lista de tareas del grupo
    public function tareas(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid   = (int)$grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $asig_id = (int)($_GET['asig'] ?? $grupo['asignacion_id']);
        $asig    = $this->grupoM->asignacionPorId($asig_id, Auth::id());
        if (!$asig) $asig = $grupo;

        // Periodo activo (las tareas solo se gestionan en el periodo activo)
        $periodo = $this->grupoM->periodoActivo();
        if (!$periodo) { flashError('No hay un periodo activo configurado.'); redirect('/grupos/'.$gid.'/estudiantes'); return; }

        $tareas = $this->tareaM->listar($asig['asignacion_id'], (int)$periodo['id']);

        render('docente/tareas', compact('grupo', 'asig', 'periodo', 'tareas'));
    }

    // Formulario nueva tarea
    public function nuevaTareaForm(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid   = (int)$grupo_id;
        $grupo = $this->grupoM->detalle($gid, Auth::id());
        if (!$grupo) { abort(403); }

        $asig_id = (int)($_GET['asig'] ?? $grupo['asignacion_id']);
        $asig    = $this->grupoM->asignacionPorId($asig_id, Auth::id());
        if (!$asig) $asig = $grupo;

        $periodo = $this->grupoM->periodoActivo();
        if (!$periodo) { flashError('No hay un periodo activo configurado.'); redirect('/grupos/'.$gid.'/tareas'); return; }

        // Indicadores disponibles para asociar evaluación
        $indicadores = $this->tareaM->indicadoresDisponibles($asig['asignacion_id'], (int)$periodo['id']);

        // Otros grupos del docente con la MISMA materia y el MISMO grado (para "crear copia en")
        $todas = $this->grupoM->porDocente(Auth::id());
        $otros_grupos = array_values(array_filter($todas, fn($a) =>
            (int)$a['materia_id'] === (int)$asig['materia_id'] &&
            $a['grado']           === $grupo['grado'] &&
            (int)$a['asignacion_id'] !== (int)$asig['asignacion_id']
        ));

        render('docente/tarea_form', compact('grupo', 'asig', 'periodo', 'indicadores', 'otros_grupos'));
    }

    // Guardar nueva tarea (con posible réplica a otros grupos)
    public function guardarTarea(string $grupo_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $gid   = (int)$grupo_id;

        $asig_id = (int)($_POST['asignacion_id'] ?? 0);
        $asig    = $this->grupoM->asignacionPorId($asig_id, Auth::id());
        if (!$asig) { abort(403); }

        $periodo = $this->grupoM->periodoActivo();
        if (!$periodo) { flashError('No hay periodo activo.'); redirect("/grupos/$gid/tareas"); return; }
        $periodo_id = (int)$periodo['id'];

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            flashError('El nombre de la tarea es obligatorio.');
            redirect("/grupos/$gid/tareas/nueva?asig=$asig_id");
            return;
        }

        $descripcion = safeHtml(trim($_POST['descripcion'] ?? ''));
        $foro        = isset($_POST['foro']) ? 1 : 0;

        // Procesar adjuntos una sola vez (compartidos entre grupos)
        $archivos = $this->procesarAdjuntosTarea();

        // Grupo principal + grupos copia
        $objetivos = [[
            'asignacion_id' => $asig['asignacion_id'],
            'grupo_id'      => $asig['grupo_id'],
            'tipo'          => $this->normalizarTipoEntrega($_POST['tipo_entrega'] ?? 'ninguna'),
            'eval_ind'      => (int)($_POST['indicador_id'] ?? 0),
            'fecha_limite'  => $_POST['fecha_limite'] ?? '',
        ]];

        // Copias a otros grupos
        $copias = $_POST['copia'] ?? []; // copia[asignacion_id] = ['tipo'=>..., 'indicador_id'=>..., 'fecha_limite'=>...]
        foreach ($copias as $copia_asig_id => $cfg) {
            $ca = $this->grupoM->asignacionPorId((int)$copia_asig_id, Auth::id());
            if (!$ca) continue;
            // Solo misma materia
            if ((int)$ca['materia_id'] !== (int)$asig['materia_id']) continue;
            $objetivos[] = [
                'asignacion_id' => $ca['asignacion_id'],
                'grupo_id'      => $ca['grupo_id'],
                'tipo'          => $this->normalizarTipoEntrega($cfg['tipo'] ?? 'ninguna'),
                'eval_ind'      => (int)($cfg['indicador_id'] ?? 0),
                'fecha_limite'  => $cfg['fecha_limite'] ?? '',
            ];
        }

        $fecha_pub = $this->parseFechaHora($_POST['fecha_publicacion'] ?? '') ?: date('Y-m-d H:i:s');
        $creadas = 0;

        foreach ($objetivos as $obj) {
            // Validar fecha límite si requiere entrega
            $fecha_limite = null;
            if ($obj['tipo'] !== 'ninguna') {
                $fecha_limite = $this->parseFechaHora($obj['fecha_limite']);
                if (!$fecha_limite) {
                    // Si pide entrega pero no hay fecha límite, usar fin de periodo o +7 días
                    $fecha_limite = date('Y-m-d H:i:s', strtotime('+7 days'));
                }
            }

            // Crear evaluación asociada si se eligió un indicador
            $evaluacion_id = null;
            if ($obj['eval_ind'] > 0) {
                // Verificar que el indicador pertenezca a esta asignación+periodo
                $chk = Database::get()->prepare(
                    'SELECT id FROM indicadores_eval WHERE id=? AND asignacion_id=? AND periodo_id=? LIMIT 1'
                );
                $chk->execute([$obj['eval_ind'], $obj['asignacion_id'], $periodo_id]);
                if ($chk->fetch()) {
                    $evaluacion_id = $this->evalM->crearEvaluacion(
                        $obj['eval_ind'], $nombre, date('Y-m-d', strtotime($fecha_pub)), 100.00
                    );
                }
            }

            // Notificar a estudiantes + acudientes del grupo
            $mensaje_id = $this->notificarTarea($obj['grupo_id'], $nombre, $descripcion, $asig['materia']);

            // Crear la tarea
            $tarea_id = $this->tareaM->crear([
                'asignacion_id'     => $obj['asignacion_id'],
                'periodo_id'        => $periodo_id,
                'nombre'            => $nombre,
                'descripcion'       => $descripcion,
                'tipo_entrega'      => $obj['tipo'],
                'fecha_publicacion' => $fecha_pub,
                'fecha_limite'      => $fecha_limite,
                'evaluacion_id'     => $evaluacion_id,
                'foro_habilitado'   => $foro,
                'mensaje_id'        => $mensaje_id,
                'creado_por'        => Auth::id(),
            ]);

            // Guardar adjuntos (copia el registro para cada tarea)
            foreach ($archivos as $a) {
                $this->tareaM->agregarAdjunto($tarea_id, $a);
            }
            $creadas++;
        }

        flashSuccess($creadas > 1
            ? "Tarea publicada en $creadas grupos. Se notificó a estudiantes y familias."
            : 'Tarea publicada. Se notificó a estudiantes y familias.');
        redirect("/grupos/$gid/tareas?asig=$asig_id");
    }

    // ── VER TAREA (vista previa como estudiante/padre) ────────

    public function verTarea(string $tarea_id): void {
        Auth::requireRole('docente');
        $tid   = (int)$tarea_id;
        $tarea = $this->tareaM->porId($tid);
        if (!$tarea) { abort(404); }

        $asig = $this->grupoM->asignacionPorId((int)$tarea['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }

        $grupo    = $this->grupoM->detalle((int)$asig['grupo_id'], Auth::id());
        $adjuntos = $this->tareaM->adjuntos($tid);

        render('docente/tarea_ver', compact('tarea', 'asig', 'grupo', 'adjuntos'));
    }

    // ── EDITAR TAREA ──────────────────────────────────────────

    public function editarTareaForm(string $tarea_id): void {
        Auth::requireRole('docente');
        $tid   = (int)$tarea_id;
        $tarea = $this->tareaM->porId($tid);
        if (!$tarea) { abort(404); }

        $asig = $this->grupoM->asignacionPorId((int)$tarea['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }

        $grupo = $this->grupoM->detalle((int)$asig['grupo_id'], Auth::id());
        $adjuntos = $this->tareaM->adjuntos($tid);
        $ahora    = date('Y-m-d\TH:i');

        render('docente/tarea_editar', compact('tarea', 'asig', 'grupo', 'adjuntos', 'ahora'));
    }

    public function editarTareaGuardar(string $tarea_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $tid   = (int)$tarea_id;
        $tarea = $this->tareaM->porId($tid);
        if (!$tarea) { abort(404); }

        $asig = $this->grupoM->asignacionPorId((int)$tarea['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }

        $gid = (int)$asig['grupo_id'];

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            flashError('El nombre de la tarea es obligatorio.');
            redirect("/tarea/$tid/editar");
            return;
        }

        $descripcion  = safeHtml(trim($_POST['descripcion'] ?? ''));
        $tipo_entrega = $this->normalizarTipoEntrega($_POST['tipo_entrega'] ?? 'ninguna');
        $foro         = isset($_POST['foro']) ? 1 : 0;

        // Fecha límite — debe ser >= ahora si se requiere entrega
        $fecha_limite = null;
        if ($tipo_entrega !== 'ninguna') {
            $fecha_limite = $this->parseFechaHora($_POST['fecha_limite'] ?? '');
            if ($fecha_limite && strtotime($fecha_limite) < time()) {
                flashError('La fecha límite no puede ser anterior a la fecha actual.');
                redirect("/tarea/$tid/editar");
                return;
            }
            if (!$fecha_limite) {
                flashError('Debes indicar una fecha límite de entrega.');
                redirect("/tarea/$tid/editar");
                return;
            }
        }

        // Adjuntos a eliminar
        foreach ((array)($_POST['eliminar_adjunto'] ?? []) as $aid) {
            $adj = $this->tareaM->adjuntoPorId((int)$aid);
            if ($adj && (int)$this->tareaM->porId($tid)['id'] === $tid) {
                @unlink(ROOT . '/storage/tareas/' . $adj['nombre_disk']);
                $this->tareaM->eliminarAdjunto((int)$aid);
            }
        }

        // Nuevos adjuntos
        $nuevos = $this->procesarAdjuntosTarea();
        foreach ($nuevos as $a) {
            $this->tareaM->agregarAdjunto($tid, $a);
        }

        // Actualizar — fecha_publicacion = ahora (sin notificar)
        $this->tareaM->actualizar($tid, [
            'nombre'            => $nombre,
            'descripcion'       => $descripcion,
            'tipo_entrega'      => $tipo_entrega,
            'fecha_publicacion' => date('Y-m-d H:i:s'),
            'fecha_limite'      => $fecha_limite,
            'foro_habilitado'   => $foro,
        ]);

        flashSuccess('Tarea actualizada.');
        redirect("/grupos/$gid/tareas?asig={$asig['asignacion_id']}");
    }

    public function eliminarTarea(string $tarea_id): void {
        Auth::requireRole('docente');
        csrfVerify();
        $tid   = (int)$tarea_id;
        $tarea = $this->tareaM->porId($tid);
        if (!$tarea) { abort(404); }

        $asig = $this->grupoM->asignacionPorId((int)$tarea['asignacion_id'], Auth::id());
        if (!$asig) { abort(403); }

        // Borrar adjuntos en disco
        foreach ($this->tareaM->adjuntos($tid) as $a) {
            @unlink(ROOT . '/storage/tareas/' . $a['nombre_disk']);
        }
        $this->tareaM->eliminar($tid);

        flashSuccess('Tarea eliminada.');
        redirect('/grupos/' . $asig['grupo_id'] . '/tareas?asig=' . $asig['asignacion_id']);
    }

    public function tareaAdjunto(string $id): void {
        Auth::require();
        $adj = $this->tareaM->adjuntoPorId((int)$id);
        if (!$adj) { abort(404); }

        $path = ROOT . '/storage/tareas/' . basename($adj['nombre_disk']);
        if (!file_exists($path)) { abort(404); }

        $inline = in_array($adj['mime'], ['application/pdf','image/jpeg','image/png','image/gif','image/webp']);
        header('Content-Type: ' . $adj['mime']);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($adj['nombre_orig']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // ── Helpers Tareas ────────────────────────────────────────

    private function normalizarTipoEntrega(string $t): string {
        return in_array($t, ['ninguna','clase','linea'], true) ? $t : 'ninguna';
    }

    // Convierte "dd/mm/yyyy HH:mm" o "yyyy-mm-ddTHH:mm" a "Y-m-d H:i:s"
    private function parseFechaHora(string $raw): ?string {
        $raw = trim($raw);
        if ($raw === '') return null;
        // input datetime-local: 2026-06-02T19:18
        $raw = str_replace('T', ' ', $raw);
        $ts  = strtotime($raw);
        if ($ts === false) {
            // intentar formato dd/mm/yyyy HH:mm
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})\s+(\d{2}):(\d{2})#', $raw, $m)) {
                $ts = strtotime("$m[3]-$m[2]-$m[1] $m[4]:$m[5]");
            }
        }
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function procesarAdjuntosTarea(): array {
        $archivos = [];
        if (empty($_FILES['adjuntos']['name'][0])) return $archivos;

        $storage = ROOT . '/storage/tareas';
        if (!is_dir($storage)) mkdir($storage, 0755, true);

        foreach ($_FILES['adjuntos']['name'] as $i => $orig) {
            if ($_FILES['adjuntos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['adjuntos']['size'][$i] > 10 * 1024 * 1024) continue;
            $mime = mime_content_type($_FILES['adjuntos']['tmp_name'][$i]);
            if (!in_array($mime, self::TAREA_MIME)) continue;
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $disk = bin2hex(random_bytes(16)) . ($ext ? ".$ext" : '');
            if (move_uploaded_file($_FILES['adjuntos']['tmp_name'][$i], "$storage/$disk")) {
                $archivos[] = ['orig'=>$orig, 'disk'=>$disk, 'mime'=>$mime, 'size'=>$_FILES['adjuntos']['size'][$i]];
            }
        }
        return $archivos;
    }

    // Envía mensaje-notificación a estudiantes y acudientes del grupo
    private function notificarTarea(int $grupo_id, string $nombre, string $descripcion, string $materia): ?int {
        $destinatarios = $this->tareaM->destinatariosGrupo($grupo_id);
        if (empty($destinatarios)) return null;

        $db = Database::get();
        $asunto = "Nueva tarea: $nombre ($materia)";
        $cuerpo = '<p>Se ha publicado una nueva tarea de <strong>' . e($materia) . '</strong>: <strong>'
                . e($nombre) . '</strong>.</p>' . ($descripcion ?: '');

        $db->prepare('INSERT INTO mensajes (remitente_id, asunto, cuerpo) VALUES (?,?,?)')
           ->execute([Auth::id(), mb_substr($asunto, 0, 200), $cuerpo]);
        $mensaje_id = (int)$db->lastInsertId();

        $ins = $db->prepare('INSERT IGNORE INTO mensajes_destinatarios (mensaje_id, usuario_id) VALUES (?,?)');
        foreach ($destinatarios as $uid) {
            if ($uid > 0) $ins->execute([$mensaje_id, $uid]);
        }

        // Notificación por correo (respeta preferencias del usuario)
        Notificador::notificar(
            'tarea',
            $destinatarios,
            "Nueva tarea: $nombre — " . APP_NAME,
            'Nueva tarea publicada',
            '<p>El docente ha publicado una nueva tarea de <strong>' . htmlspecialchars($materia) . '</strong>:</p>'
            . '<p style="font-size:1.05em;font-weight:600">' . htmlspecialchars($nombre) . '</p>'
            . ($descripcion ? '<p style="color:#6b7280">' . strip_tags($descripcion, '<br><p><b><strong>') . '</p>' : ''),
            APP_URL . '/dashboard',
            'Ir a la plataforma'
        );

        return $mensaje_id;
    }

    // ── NOTAS GRUPALES (varios estudiantes) ──────────────────

    public function notasGrupo(string $grupo_id): void {
        Auth::requireRole('docente');
        $gid     = (int)$grupo_id;
        $docente = Auth::id();

        $uids = array_unique(array_map('intval', (array)($_GET['uid'] ?? [])));
        if (empty($uids)) { redirect('/grupos/'.$gid.'/estudiantes'); return; }

        $periodos   = $this->grupoM->periodos();
        $periodo_id = (int)($_GET['periodo'] ?? 0);
        if (!$periodo_id) {
            foreach ($periodos as $p) {
                if ($p['activo']) { $periodo_id = (int)$p['id']; break; }
            }
            if (!$periodo_id && !empty($periodos)) $periodo_id = (int)$periodos[0]['id'];
        }

        $periodo_sel = null;
        foreach ($periodos as $p) {
            if ((int)$p['id'] === $periodo_id) { $periodo_sel = $p; break; }
        }

        // Verificar grupo y cargar todos los estudiantes del grupo
        $todos = $this->grupoM->estudiantes($gid);
        $grupo = $this->grupoM->detalle($gid, $docente);
        if (!$grupo) { abort(403); }

        // Filtrar solo los seleccionados
        $estudiantes = array_filter($todos, fn($e) => in_array((int)$e['usuario_id'], $uids));
        $estudiantes = array_values($estudiantes);

        // Cargar notas para cada estudiante
        foreach ($estudiantes as &$est) {
            $est['materias'] = $this->grupoM->notasEstudiante(
                $gid, $est['matricula_id'], $periodo_id
            );
        }
        unset($est);

        render('docente/notas_grupo', compact(
            'grupo', 'gid', 'estudiantes', 'periodos', 'periodo_id', 'periodo_sel'
        ));
    }

    // ── NOTAS DE UN ESTUDIANTE ────────────────────────────────

    public function notasEstudiante(string $grupo_id, string $uid): void {
        Auth::requireRole('docente');
        $gid      = (int)$grupo_id;
        $uid      = (int)$uid;
        $docente  = Auth::id();

        $est = $this->grupoM->estudianteEnGrupo($gid, $uid, $docente);
        if (!$est) { abort(403); }

        $periodos   = $this->grupoM->periodos();
        $periodo_id = (int)($_GET['periodo'] ?? 0);
        if (!$periodo_id) {
            foreach ($periodos as $p) {
                if ($p['activo']) { $periodo_id = (int)$p['id']; break; }
            }
            if (!$periodo_id && !empty($periodos)) $periodo_id = (int)$periodos[0]['id'];
        }

        $periodo_sel = null;
        foreach ($periodos as $p) {
            if ((int)$p['id'] === $periodo_id) { $periodo_sel = $p; break; }
        }

        $materias = $this->grupoM->notasEstudiante($gid, $est['matricula_id'], $periodo_id);
        $padres   = $this->grupoM->padresDeEstudiante($est['estudiante_id']);

        render('docente/estudiante_notas', compact(
            'est', 'periodos', 'periodo_id', 'periodo_sel',
            'materias', 'padres', 'gid'
        ));
    }

    // ── AJAX: guardar una nota individual ─────────────────────────

    public function guardarNotaApi(): void {
        header('Content-Type: application/json');
        Auth::requireRole('docente');
        csrfVerify();

        $eval_id   = (int)($_POST['eval_id'] ?? 0);
        $mat_id    = (int)($_POST['mat_id']  ?? 0);
        $valor_raw = trim($_POST['valor'] ?? '');
        $valor     = $valor_raw === '' ? null
                   : max(notaMin(), min(notaMax(), (float)$valor_raw));

        if (!$eval_id || !$mat_id) {
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $db = Database::get();

        // Verificar propiedad docente
        $st = $db->prepare(
            'SELECT ie.id AS ind_id, ie.asignacion_id, ie.periodo_id,
                    a.docente_id, a.materia_id
             FROM evaluaciones ev
             JOIN indicadores_eval ie ON ie.id = ev.indicador_id
             JOIN asignaciones a      ON a.id  = ie.asignacion_id
             WHERE ev.id = ? LIMIT 1'
        );
        $st->execute([$eval_id]);
        $ev = $st->fetch();
        if (!$ev || (int)$ev['docente_id'] !== Auth::id()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permisos']);
            return;
        }

        $this->evalM->guardarCalificacion($eval_id, $mat_id, $valor);

        // Recalcular definitiva del indicador
        $st2 = $db->prepare(
            'SELECT ev2.porcentaje, c.valor
             FROM evaluaciones ev2
             LEFT JOIN calificaciones c ON c.evaluacion_id = ev2.id AND c.matricula_id = ?
             WHERE ev2.indicador_id = ?'
        );
        $st2->execute([$mat_id, $ev['ind_id']]);
        $pond = 0; $pct = 0;
        foreach ($st2->fetchAll() as $e) {
            if ($e['valor'] !== null) {
                $pond += (float)$e['valor'] * ($e['porcentaje'] / 100);
                $pct  += $e['porcentaje'];
            }
        }
        $definitiva = $pct > 0 ? round($pond, 1) : null;

        // Recalcular promedio de la materia (todos los indicadores del asig + periodo)
        $st3 = $db->prepare(
            'SELECT ie2.id AS ind_id, ie2.peso
             FROM indicadores_eval ie2
             WHERE ie2.asignacion_id = ? AND ie2.periodo_id = ?'
        );
        $st3->execute([$ev['asignacion_id'], $ev['periodo_id']]);
        $nota_pond = 0; $suma_peso = 0;
        foreach ($st3->fetchAll() as $ind2) {
            $st4 = $db->prepare(
                'SELECT ev3.porcentaje, c3.valor
                 FROM evaluaciones ev3
                 LEFT JOIN calificaciones c3 ON c3.evaluacion_id = ev3.id AND c3.matricula_id = ?
                 WHERE ev3.indicador_id = ?'
            );
            $st4->execute([$mat_id, $ind2['ind_id']]);
            $p2 = 0; $pt2 = 0;
            foreach ($st4->fetchAll() as $e2) {
                if ($e2['valor'] !== null) {
                    $p2  += (float)$e2['valor'] * ($e2['porcentaje'] / 100);
                    $pt2 += $e2['porcentaje'];
                }
            }
            $def2 = $pt2 > 0 ? round($p2, 1) : null;
            if ($def2 !== null) {
                $nota_pond += $def2 * (float)$ind2['peso'];
                $suma_peso += (float)$ind2['peso'];
            }
        }
        $promedio = $suma_peso > 0 ? round($nota_pond / $suma_peso, 1) : null;

        echo json_encode([
            'ok'         => true,
            'nota'       => $valor !== null ? round($valor, 1) : null,
            'ind_id'     => $ev['ind_id'],
            'definitiva' => $definitiva,
            'materia_id' => $ev['materia_id'],
            'promedio'   => $promedio,
            'csrf'       => csrfToken(),
        ]);
    }
}


