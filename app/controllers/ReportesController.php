<?php
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    private function recolectarDatos(): array {
        $cid = Tenant::id();

        $flags = $this->db->query(
            "SELECT permite_planeador_clases, permite_circulares, permite_pqrs, permite_encuestas, permite_observador_consolidado,
                    permite_autorizaciones, permite_retiro_anticipado
             FROM colegios WHERE id = $cid"
        )->fetch();

        $periodoActivo = $this->db->query(
            'SELECT p.* FROM periodos p JOIN anos_lectivos al ON al.id = p.ano_lectivo_id
             WHERE al.activo = 1 AND p.activo = 1 LIMIT 1'
        )->fetch();

        $colegio = $this->db->query("SELECT * FROM colegios WHERE id = $cid")->fetch();

        return [
            'flags' => $flags,
            'colegio' => $colegio,
            'periodoActivo' => $periodoActivo,
            'promedioPorGrado' => $periodoActivo ? $this->promedioPorGrado((int)$periodoActivo['id']) : [],
            'asistenciaGlobal' => $this->asistenciaGlobal(),
            'observacionesPorTipo' => !empty($flags['permite_observador_consolidado']) ? $this->observacionesPorTipo() : [],
            'planeacionesPorPeriodo' => !empty($flags['permite_planeador_clases']) ? $this->planeacionesPorPeriodo() : [],
            'circularesStats' => !empty($flags['permite_circulares']) ? $this->circularesStats() : [],
            'pqrsStats' => !empty($flags['permite_pqrs']) ? $this->pqrsStats() : null,
            'encuestasStats' => !empty($flags['permite_encuestas']) ? $this->encuestasStats() : [],
            'autorizacionesStats' => !empty($flags['permite_autorizaciones']) ? $this->autorizacionesStats() : null,
            'retirosStats' => !empty($flags['permite_retiro_anticipado']) ? $this->retirosStats() : null,
            'notaMin' => (float)($colegio['nota_min'] ?? 1.0),
            'notaMax' => (float)($colegio['nota_max'] ?? 10.0),
        ];
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        render('admin/reportes', $this->recolectarDatos());
    }

    public function exportarPdf(): void {
        Auth::requireRole('admin', 'soporte');

        $datos = $this->recolectarDatos();
        $datos['fecha_emision'] = date('d/m/Y H:i');

        ob_start();
        extract($datos, EXTR_SKIP);
        include ROOT . '/app/views/admin/reportes_pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        while (ob_get_level() > 0) { ob_end_clean(); }
        $dompdf->stream('Estadisticas_' . date('Y-m-d') . '.pdf', ['Attachment' => false]);
    }

    public function exportarExcel(): void {
        Auth::requireRole('admin', 'soporte');
        $datos = $this->recolectarDatos();

        $labAsist = ['presente' => 'Presente', 'tarde' => 'Tarde', 'excusa' => 'Excusa', 'ausente' => 'Ausente', 'incapacidad' => 'Incapacidad médica'];
        $tipoObsLabel = ['academica'=>'Académica','convivencia'=>'Convivencia','logro'=>'Logro','disciplinaria'=>'Disciplinaria','otro'=>'Otro'];
        $pqrsEstadoLabel = ['radicado'=>'Radicado','en_tramite'=>'En trámite','resuelto'=>'Resuelto','cerrado'=>'Cerrado'];

        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E0EB3']],
        ];

        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Promedio por grado');
        $hoja->fromArray(['Grado', 'Total estudiantes', 'Promedio'], null, 'A1');
        $hoja->getStyle('A1:C1')->applyFromArray($headerStyle);
        $fila = 2;
        foreach ($datos['promedioPorGrado'] as $g) {
            $hoja->fromArray([$g['grado'], (int)$g['total_est'], (float)$g['promedio']], null, "A{$fila}");
            $fila++;
        }
        foreach (['A', 'B', 'C'] as $col) { $hoja->getColumnDimension($col)->setAutoSize(true); }

        $hojaAsist = $spreadsheet->createSheet();
        $hojaAsist->setTitle('Asistencia');
        $hojaAsist->fromArray(['Estado', 'Total'], null, 'A1');
        $hojaAsist->getStyle('A1:B1')->applyFromArray($headerStyle);
        $fila = 2;
        foreach ($datos['asistenciaGlobal'] as $estado => $n) {
            $hojaAsist->fromArray([$labAsist[$estado] ?? $estado, (int)$n], null, "A{$fila}");
            $fila++;
        }
        foreach (['A', 'B'] as $col) { $hojaAsist->getColumnDimension($col)->setAutoSize(true); }

        if (!empty($datos['flags']['permite_observador_consolidado']) && $datos['observacionesPorTipo']) {
            $hojaObs = $spreadsheet->createSheet();
            $hojaObs->setTitle('Observaciones');
            $hojaObs->fromArray(['Tipo', 'Total'], null, 'A1');
            $hojaObs->getStyle('A1:B1')->applyFromArray($headerStyle);
            $fila = 2;
            foreach ($datos['observacionesPorTipo'] as $tipo => $n) {
                $hojaObs->fromArray([$tipoObsLabel[$tipo] ?? $tipo, (int)$n], null, "A{$fila}");
                $fila++;
            }
            foreach (['A', 'B'] as $col) { $hojaObs->getColumnDimension($col)->setAutoSize(true); }
        }

        if (!empty($datos['flags']['permite_pqrs']) && $datos['pqrsStats']) {
            $hojaPqrs = $spreadsheet->createSheet();
            $hojaPqrs->setTitle('PQRS');
            $hojaPqrs->fromArray(['Estado', 'Total'], null, 'A1');
            $hojaPqrs->getStyle('A1:B1')->applyFromArray($headerStyle);
            $fila = 2;
            foreach ($datos['pqrsStats']['por_estado'] as $estado => $n) {
                $hojaPqrs->fromArray([$pqrsEstadoLabel[$estado] ?? $estado, (int)$n], null, "A{$fila}");
                $fila++;
            }
            foreach (['A', 'B'] as $col) { $hojaPqrs->getColumnDimension($col)->setAutoSize(true); }
        }

        if (!empty($datos['flags']['permite_encuestas']) && $datos['encuestasStats']) {
            $hojaEnc = $spreadsheet->createSheet();
            $hojaEnc->setTitle('Encuestas');
            $hojaEnc->fromArray(['Título', 'Invitados', 'Completados', '% completado'], null, 'A1');
            $hojaEnc->getStyle('A1:D1')->applyFromArray($headerStyle);
            $fila = 2;
            foreach ($datos['encuestasStats'] as $enc) {
                $pct = $enc['total_invitados'] > 0 ? round($enc['total_completados'] / $enc['total_invitados'] * 100) : 0;
                $hojaEnc->fromArray([$enc['titulo'], (int)$enc['total_invitados'], (int)$enc['total_completados'], $pct . '%'], null, "A{$fila}");
                $fila++;
            }
            foreach (['A', 'B', 'C', 'D'] as $col) { $hojaEnc->getColumnDimension($col)->setAutoSize(true); }
        }

        if (!empty($datos['flags']['permite_autorizaciones']) && $datos['autorizacionesStats']) {
            $hojaAut = $spreadsheet->createSheet();
            $hojaAut->setTitle('Autorizaciones');
            $hojaAut->fromArray(['Título', 'Tipo', 'Invitados', 'Firmados'], null, 'A1');
            $hojaAut->getStyle('A1:D1')->applyFromArray($headerStyle);
            $fila = 2;
            foreach ($datos['autorizacionesStats'] as $a) {
                $hojaAut->fromArray([$a['titulo'], $a['tipo'], (int)$a['total_invitados'], (int)$a['total_firmados']], null, "A{$fila}");
                $fila++;
            }
            foreach (['A', 'B', 'C', 'D'] as $col) { $hojaAut->getColumnDimension($col)->setAutoSize(true); }
        }

        if (!empty($datos['flags']['permite_retiro_anticipado']) && $datos['retirosStats']) {
            $hojaRet = $spreadsheet->createSheet();
            $hojaRet->setTitle('Retiros anticipados');
            $hojaRet->fromArray(['Mes', 'Total'], null, 'A1');
            $hojaRet->getStyle('A1:B1')->applyFromArray($headerStyle);
            $fila = 2;
            foreach ($datos['retirosStats']['por_mes'] as $r) {
                $hojaRet->fromArray([$r['mes'], (int)$r['total']], null, "A{$fila}");
                $fila++;
            }
            foreach (['A', 'B'] as $col) { $hojaRet->getColumnDimension($col)->setAutoSize(true); }
        }

        $spreadsheet->setActiveSheetIndex(0);

        // Descartar cualquier buffer de salida acumulado (p.ej. un BOM colado antes en el
        // bootstrap) para que nada se anteponga a los bytes binarios del .xlsx y lo corrompa.
        while (ob_get_level() > 0) { ob_end_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Estadisticas_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    private function promedioPorGrado(int $periodoId): array {
        $st = $this->db->prepare(
            'SELECT gr.nombre AS grado, gr.nivel, ROUND(AVG(c.valor), 2) AS promedio, COUNT(DISTINCT mt.id) AS total_est
             FROM calificaciones c
             JOIN evaluaciones ev      ON c.evaluacion_id = ev.id
             JOIN indicadores_eval ie  ON ev.indicador_id = ie.id
             JOIN matriculas mt        ON c.matricula_id = mt.id
             JOIN grupos g             ON mt.grupo_id = g.id
             JOIN grados gr            ON g.grado_id = gr.id
             WHERE ie.periodo_id = ?
             GROUP BY gr.id
             ORDER BY gr.nivel'
        );
        $st->execute([$periodoId]);
        return $st->fetchAll();
    }

    private function asistenciaGlobal(): array {
        $st = $this->db->query(
            "SELECT a.estado, COUNT(*) AS total
             FROM asistencia a
             JOIN matriculas mt ON a.matricula_id = mt.id
             JOIN grupos g ON mt.grupo_id = g.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE al.activo = 1
             GROUP BY a.estado"
        );
        $datos = ['presente' => 0, 'tarde' => 0, 'excusa' => 0, 'ausente' => 0, 'incapacidad' => 0];
        foreach ($st->fetchAll() as $r) {
            if (isset($datos[$r['estado']])) $datos[$r['estado']] = (int)$r['total'];
        }
        return $datos;
    }

    private function observacionesPorTipo(): array {
        $st = $this->db->query(
            "SELECT ob.tipo, COUNT(*) AS total
             FROM observaciones ob
             JOIN matriculas mt ON ob.matricula_id = mt.id
             JOIN grupos g ON mt.grupo_id = g.id
             JOIN anos_lectivos al ON g.ano_lectivo_id = al.id
             WHERE al.activo = 1
             GROUP BY ob.tipo"
        );
        $datos = [];
        foreach ($st->fetchAll() as $r) { $datos[$r['tipo']] = (int)$r['total']; }
        return $datos;
    }

    private function planeacionesPorPeriodo(): array {
        $st = $this->db->query(
            "SELECT per.nombre AS periodo, per.numero, COUNT(*) AS total
             FROM planeaciones p
             JOIN periodos per ON per.id = p.periodo_id
             JOIN anos_lectivos al ON al.id = per.ano_lectivo_id
             WHERE al.activo = 1
             GROUP BY per.id
             ORDER BY per.numero"
        );
        return $st->fetchAll();
    }

    private function circularesStats(): array {
        $cid = Tenant::id();
        $st = $this->db->prepare(
            "SELECT c.id, c.titulo, c.created_at,
                    COUNT(cd.usuario_id) AS total_invitados,
                    SUM(cd.leido) AS total_leidos
             FROM circulares c
             LEFT JOIN circulares_destinatarios cd ON cd.circular_id = c.id
             WHERE c.colegio_id = ?
             GROUP BY c.id
             ORDER BY c.created_at DESC
             LIMIT 10"
        );
        $st->execute([$cid]);
        return $st->fetchAll();
    }

    private function pqrsStats(): array {
        $cid = Tenant::id();
        $porTipo = $this->db->prepare("SELECT tipo, COUNT(*) AS total FROM pqrs WHERE colegio_id = ? GROUP BY tipo");
        $porTipo->execute([$cid]);
        $porEstado = $this->db->prepare("SELECT estado, COUNT(*) AS total FROM pqrs WHERE colegio_id = ? GROUP BY estado");
        $porEstado->execute([$cid]);
        return [
            'por_tipo' => $porTipo->fetchAll(PDO::FETCH_KEY_PAIR),
            'por_estado' => $porEstado->fetchAll(PDO::FETCH_KEY_PAIR),
        ];
    }

    private function autorizacionesStats(): array {
        $cid = Tenant::id();
        $st = $this->db->prepare(
            "SELECT a.id, a.titulo, a.tipo, a.activa,
                    COUNT(p.estudiante_id) AS total_invitados,
                    SUM(p.firmado) AS total_firmados
             FROM autorizaciones a
             LEFT JOIN autorizaciones_participantes p ON p.autorizacion_id = a.id
             WHERE a.colegio_id = ?
             GROUP BY a.id
             ORDER BY a.created_at DESC
             LIMIT 10"
        );
        $st->execute([$cid]);
        return $st->fetchAll();
    }

    private function retirosStats(): array {
        $cid = Tenant::id();

        $porMes = $this->db->prepare(
            "SELECT DATE_FORMAT(r.created_at, '%Y-%m') AS mes, COUNT(*) AS total
             FROM retiros_anticipados r WHERE r.colegio_id = ?
             GROUP BY mes ORDER BY mes DESC LIMIT 6"
        );
        $porMes->execute([$cid]);

        $total = $this->db->prepare("SELECT COUNT(*) FROM retiros_anticipados WHERE colegio_id = ?");
        $total->execute([$cid]);

        return [
            'por_mes' => $porMes->fetchAll(),
            'total' => (int)$total->fetchColumn(),
        ];
    }

    private function encuestasStats(): array {
        $cid = Tenant::id();
        $st = $this->db->prepare(
            "SELECT e.id, e.titulo, e.activa,
                    COUNT(p.usuario_id) AS total_invitados,
                    SUM(p.completado) AS total_completados
             FROM encuestas e
             LEFT JOIN encuestas_participantes p ON p.encuesta_id = e.id
             WHERE e.colegio_id = ?
             GROUP BY e.id
             ORDER BY e.created_at DESC
             LIMIT 10"
        );
        $st->execute([$cid]);
        return $st->fetchAll();
    }
}
