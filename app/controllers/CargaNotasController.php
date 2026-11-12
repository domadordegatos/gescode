<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class CargaNotasController {
    private Grupo      $grupoM;
    private Evaluacion $evalM;
    private PDO        $db;

    private const TMP_DIR = ROOT . '/storage/carga_notas_tmp';

    public function __construct() {
        $this->grupoM = new Grupo();
        $this->evalM  = new Evaluacion();
        $this->db     = Database::get();
        if (!is_dir(self::TMP_DIR)) mkdir(self::TMP_DIR, 0755, true);
    }

    // ── Contexto común: validar que la asignación sea del docente ──
    private function contexto(int $gid): array {
        Auth::requireRole('docente');
        $todas_asig = $this->grupoM->porDocente(Auth::id());

        $asig_id = (int)($_GET['asig'] ?? $_POST['asig'] ?? 0);
        if (!$asig_id) {
            foreach ($todas_asig as $a) {
                if ((int)$a['grupo_id'] === $gid) { $asig_id = $a['asignacion_id']; break; }
            }
        }
        $asig = null;
        foreach ($todas_asig as $a) {
            if ((int)$a['asignacion_id'] === $asig_id) { $asig = $a; break; }
        }
        if (!$asig) { abort(403); }

        $periodos   = $this->grupoM->periodos();
        $periodo_id = (int)($_GET['periodo'] ?? $_POST['periodo'] ?? 0);
        if (!$periodo_id) {
            foreach ($periodos as $p) { if ($p['activo']) { $periodo_id = $p['id']; break; } }
            if (!$periodo_id && $periodos) $periodo_id = $periodos[0]['id'];
        }

        return [$asig, $periodo_id, $periodos, $todas_asig];
    }

    // ── PASO 1: formulario de subida ────────────────────────────
    public function formulario(string $grupo_id): void {
        $gid = (int)$grupo_id;
        [$asig, $periodo_id, $periodos, $todas_asig] = $this->contexto($gid);

        $materias_grupo = array_values(array_filter(
            $todas_asig, fn($a) => (int)$a['grupo_id'] === (int)$asig['grupo_id']
        ));

        render('docente/carga_notas_subir', compact('asig', 'periodo_id', 'periodos', 'materias_grupo', 'gid'));
    }

    // ── PASO 2: procesar archivo subido → mostrar hojas + mapeo ──
    public function subir(string $grupo_id): void {
        $gid = (int)$grupo_id;
        csrfVerify();
        [$asig, $periodo_id, $periodos, $todas_asig] = $this->contexto($gid);

        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            flashError('Selecciona un archivo Excel válido.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            flashError('El archivo debe ser .xlsx o .xls.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }
        if ($_FILES['archivo']['size'] > 8 * 1024 * 1024) {
            flashError('El archivo no puede superar 8 MB.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        $token = bin2hex(random_bytes(12));
        $dest  = self::TMP_DIR . "/$token.$ext";
        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $dest)) {
            flashError('No se pudo guardar el archivo. Intenta de nuevo.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        try {
            $spreadsheet = IOFactory::load($dest);
        } catch (Exception $e) {
            @unlink($dest);
            flashError('No se pudo leer el archivo. Verifica que no esté dañado o protegido con contraseña.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        // Volcar cada hoja a una matriz de celdas (limitado para no saturar memoria/sesión)
        $hojas = [];
        foreach ($spreadsheet->getSheetNames() as $nombreHoja) {
            $ws = $spreadsheet->getSheetByName($nombreHoja);
            $maxRow = min($ws->getHighestDataRow(), 200);
            $maxCol = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ws->getHighestDataColumn()), 30);

            $filas = [];
            for ($r = 1; $r <= $maxRow; $r++) {
                $fila = [];
                for ($c = 1; $c <= $maxCol; $c++) {
                    $colLetra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $cell = $ws->getCell($colLetra . $r);
                    // Si la celda es una fórmula real, intentamos el valor calculado (ej. columnas
                    // "Def"/"Promedio"); si el motor de fórmulas falla (hojas reales a veces traen
                    // texto pegado por error que empieza con "=" y no es una fórmula válida),
                    // caemos al valor crudo en vez de tronar toda la carga.
                    if ($cell->isFormula()) {
                        try { $valor = $cell->getCalculatedValue(); }
                        catch (\Throwable $e) { $valor = $cell->getValue(); }
                    } else {
                        $valor = $cell->getValue();
                    }
                    if ($valor instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                        $valor = $valor->getPlainText();
                    }
                    $fila[$colLetra] = is_string($valor) ? trim($valor) : $valor;
                }
                $filas[] = $fila;
            }
            $hojas[$nombreHoja] = ['filas' => $filas, 'num_col' => $maxCol];
        }

        $_SESSION['carga_notas'] = [
            'token'      => $token,
            'archivo'    => $dest,
            'nombre_orig'=> $_FILES['archivo']['name'],
            'gid'        => $gid,
            'asig_id'    => $asig['asignacion_id'],
            'periodo_id' => $periodo_id,
            'hojas'      => $hojas,
        ];

        // Sugerir hoja por defecto: si el nombre de alguna hoja coincide con "Grado Grupo" del colegio
        $hojaSugerida = array_key_first($hojas);
        $nombreGrupo = preg_replace('/\s+/', '', strtoupper($asig['grado'] . $asig['grupo']));
        foreach (array_keys($hojas) as $h) {
            if (strtoupper(preg_replace('/\s+/', '', $h)) === $nombreGrupo) { $hojaSugerida = $h; break; }
        }

        // Indicadores y evaluaciones existentes de esta asignación+periodo, para el selector destino
        $indicadores = $this->evalM->indicadores($asig['asignacion_id'], $periodo_id);

        render('docente/carga_notas_mapear', compact(
            'asig', 'periodo_id', 'gid', 'hojas', 'hojaSugerida', 'indicadores'
        ));
    }

    // ── PASO 3: recibir mapeo de columnas → emparejar estudiantes ──
    public function emparejar(string $grupo_id): void {
        $gid = (int)$grupo_id;
        csrfVerify();
        [$asig, $periodo_id] = $this->contexto($gid);

        $S = $_SESSION['carga_notas'] ?? null;
        if (!$S || $S['gid'] !== $gid) {
            flashError('La sesión de carga expiró. Vuelve a subir el archivo.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        $hoja        = $_POST['hoja'] ?? '';
        $filaHeader  = max(1, (int)($_POST['fila_header'] ?? 1));
        $colNombre   = strtoupper(trim($_POST['col_nombre'] ?? ''));
        $colNota     = strtoupper(trim($_POST['col_nota'] ?? ''));
        $indicadorId = (int)($_POST['indicador_id'] ?? 0);
        $evalId      = (int)($_POST['eval_id'] ?? 0);
        $evalNuevoNombre = trim($_POST['eval_nuevo_nombre'] ?? '');
        $evalNuevoPct    = (float)($_POST['eval_nuevo_porcentaje'] ?? 100);

        if (!isset($S['hojas'][$hoja]) || !$colNombre || !$colNota || !$indicadorId || (!$evalId && !$evalNuevoNombre)) {
            flashError('Completa todos los campos del mapeo antes de continuar.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        // Verificar que el indicador pertenece a esta asignación+periodo (seguridad)
        $chkInd = $this->db->prepare('SELECT id FROM indicadores_eval WHERE id=? AND asignacion_id=? AND periodo_id=? LIMIT 1');
        $chkInd->execute([$indicadorId, $asig['asignacion_id'], $periodo_id]);
        if (!$chkInd->fetch()) { flashError('Logro/indicador no válido.'); redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id"); return; }

        // Crear evaluación nueva si se pidió, o validar que la existente pertenezca al indicador
        if (!$evalId && $evalNuevoNombre) {
            $this->db->prepare(
                'INSERT INTO evaluaciones (indicador_id, nombre, fecha, porcentaje) VALUES (?,?,?,?)'
            )->execute([$indicadorId, $evalNuevoNombre, date('Y-m-d'), $evalNuevoPct]);
            $evalId = (int)$this->db->lastInsertId();
        } else {
            $chkEv = $this->db->prepare('SELECT id FROM evaluaciones WHERE id=? AND indicador_id=? LIMIT 1');
            $chkEv->execute([$evalId, $indicadorId]);
            if (!$chkEv->fetch()) { flashError('Evaluación no válida para este indicador.'); redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id"); return; }
        }

        // Extraer pares (nombre, nota) desde la fila siguiente al encabezado
        $filasHoja = $S['hojas'][$hoja]['filas'];
        $pares = [];
        for ($i = $filaHeader; $i < count($filasHoja); $i++) { // filas[] es 0-index, fila_header es 1-index de Excel
            $fila = $filasHoja[$i];
            $nombreExcel = trim((string)($fila[$colNombre] ?? ''));
            $notaExcel   = $fila[$colNota] ?? null;
            if ($nombreExcel === '') continue;
            // Normalizar coma decimal
            if (is_string($notaExcel)) $notaExcel = str_replace(',', '.', $notaExcel);
            if ($notaExcel === '' || $notaExcel === null || !is_numeric($notaExcel)) continue;
            $pares[] = ['nombre_excel' => $nombreExcel, 'valor' => (float)$notaExcel];
        }

        if (!$pares) {
            flashError('No se encontraron filas con nombre y nota numérica válida en las columnas elegidas.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        // Emparejar contra estudiantes del grupo
        $estudiantes = $this->grupoM->estudiantes($asig['grupo_id']);
        $candidatos  = [];
        foreach ($estudiantes as $e) {
            $candidatos[] = [
                'matricula_id' => $e['matricula_id'],
                'nombre_completo' => $e['nombre'] . ' ' . $e['apellido'],
                'clave' => $this->normalizar($e['nombre'] . ' ' . $e['apellido']),
            ];
        }

        foreach ($pares as &$p) {
            $claveExcel = $this->normalizar($p['nombre_excel']);
            $mejor = null; $mejorPct = 0;
            foreach ($candidatos as $c) {
                if ($c['clave'] === $claveExcel) { $mejor = $c; $mejorPct = 100; break; }
                similar_text($claveExcel, $c['clave'], $pct);
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejor = $c; }
            }
            $p['matricula_id']    = ($mejor && $mejorPct >= 70) ? $mejor['matricula_id'] : null;
            $p['sugerido_nombre'] = $mejor['nombre_completo'] ?? null;
            $p['confianza']       = round($mejorPct);
        }
        unset($p);

        $_SESSION['carga_notas']['pares']        = $pares;
        $_SESSION['carga_notas']['eval_id']      = $evalId;
        $_SESSION['carga_notas']['indicador_id'] = $indicadorId;

        // Datos para mostrar el nombre de la evaluación/logro elegidos
        $evInfo = $this->db->prepare(
            'SELECT ev.nombre AS eval_nombre, ie.descripcion AS logro, ie.codigo
             FROM evaluaciones ev JOIN indicadores_eval ie ON ev.indicador_id = ie.id
             WHERE ev.id = ? LIMIT 1'
        );
        $evInfo->execute([$evalId]);
        $evDatos = $evInfo->fetch();

        render('docente/carga_notas_confirmar', compact(
            'asig', 'periodo_id', 'gid', 'pares', 'estudiantes', 'evDatos'
        ));
    }

    // ── PASO 4: confirmar y guardar calificaciones ──────────────
    public function confirmar(string $grupo_id): void {
        $gid = (int)$grupo_id;
        csrfVerify();
        [$asig, $periodo_id] = $this->contexto($gid);

        $S = $_SESSION['carga_notas'] ?? null;
        if (!$S || $S['gid'] !== $gid || empty($S['pares']) || empty($S['eval_id'])) {
            flashError('La sesión de carga expiró. Vuelve a subir el archivo.');
            redirect("/grupos/$gid/carga-notas?asig={$asig['asignacion_id']}&periodo=$periodo_id");
            return;
        }

        $evalId      = (int)$S['eval_id'];
        $matricula   = $_POST['matricula'] ?? []; // índice => matricula_id (ajustado o confirmado por el docente)
        $incluir     = $_POST['incluir'] ?? [];   // índice => '1' si se debe importar esa fila
        $pares       = $S['pares'];

        $guardadas = 0; $omitidas = 0;
        foreach ($pares as $i => $p) {
            if (empty($incluir[$i])) { $omitidas++; continue; }
            $matId = (int)($matricula[$i] ?? $p['matricula_id'] ?? 0);
            if (!$matId) { $omitidas++; continue; }

            // Verificar que la matrícula pertenece al grupo de esta asignación (seguridad)
            $chk = $this->db->prepare('SELECT id FROM matriculas WHERE id=? AND grupo_id=? LIMIT 1');
            $chk->execute([$matId, $asig['grupo_id']]);
            if (!$chk->fetch()) { $omitidas++; continue; }

            $v = max(notaMin(), min(notaMax(), (float)$p['valor']));
            $this->evalM->guardarCalificacion($evalId, $matId, $v);
            $guardadas++;
        }

        // Limpiar archivo temporal y sesión
        if (!empty($S['archivo']) && file_exists($S['archivo'])) @unlink($S['archivo']);
        unset($_SESSION['carga_notas']);

        flashSuccess("Carga completada: $guardadas nota(s) guardada(s)" . ($omitidas ? ", $omitidas fila(s) omitida(s)." : '.'));
        redirect("/grupos/$gid/evaluaciones?asig={$asig['asignacion_id']}&periodo=$periodo_id");
    }

    // ── PLANTILLA EN BLANCO ──────────────────────────────────────
    public function plantilla(string $grupo_id): void {
        $gid = (int)$grupo_id;
        [$asig] = $this->contexto($gid);

        $estudiantes = $this->grupoM->estudiantes($asig['grupo_id']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($asig['grado'] . ' ' . $asig['grupo'], 0, 31));
        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'Apellidos y nombres');
        $sheet->setCellValue('C1', 'Nota');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;
        foreach ($estudiantes as $i => $e) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $e['apellido'] . ', ' . $e['nombre']);
            $row++;
        }
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(10);

        $nombreArchivo = 'plantilla_notas_' . preg_replace('/\W+/', '_', $asig['grado'] . '_' . $asig['grupo']) . '.xlsx';

        // Descartar cualquier buffer de salida acumulado (p.ej. un BOM colado antes en el
        // bootstrap) para que nada se anteponga a los bytes binarios del .xlsx y lo corrompa.
        while (ob_get_level() > 0) { ob_end_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ── Normalización de nombres para emparejamiento ────────────
    private function normalizar(string $s): string {
        $s = mb_strtoupper(trim($s), 'UTF-8');
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($t !== false) $s = $t;
        $s = preg_replace('/[^A-Z ]/', ' ', $s); // quita comas, puntuación, deja solo letras
        $palabras = array_filter(explode(' ', $s));
        sort($palabras);
        return implode(' ', $palabras);
    }
}
