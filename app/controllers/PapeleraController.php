<?php
class PapeleraController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('admin', 'soporte');

        $st = $this->db->prepare(
            "SELECT p.id, p.tipo, p.descripcion, p.created_at, p.restaurado, p.restaurado_at,
                    eu.nombre AS elim_nombre, eu.apellido AS elim_apellido,
                    ru.nombre AS rest_nombre, ru.apellido AS rest_apellido
             FROM papelera_notas p
             JOIN usuarios eu ON eu.id = p.eliminado_por
             JOIN grupos g ON g.id = p.grupo_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             LEFT JOIN usuarios ru ON ru.id = p.restaurado_por
             WHERE al.colegio_id = ?
             ORDER BY p.created_at DESC
             LIMIT 200"
        );
        $st->execute([Tenant::id()]);
        render('admin/papelera_lista', ['items' => $st->fetchAll()]);
    }

    private function item(int $id): ?array {
        $q = $this->db->prepare(
            'SELECT p.* FROM papelera_notas p
             JOIN grupos g ON g.id = p.grupo_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE p.id = ? AND al.colegio_id = ? LIMIT 1'
        );
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
        render('admin/papelera_detalle', ['item' => $item]);
    }

    public function restaurar(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $item = $this->item($id);
        if (!$item) { abort(404); }
        if ($item['restaurado']) {
            flashError('Esto ya fue restaurado anteriormente.');
            redirect('/papelera');
            return;
        }

        $this->db->beginTransaction();
        try {
            if ($item['tipo'] === 'indicador') {
                $this->restaurarIndicador($item['snapshot']);
            } else {
                $this->restaurarEvaluacion($item['snapshot']);
            }

            $this->db->prepare(
                'UPDATE papelera_notas SET restaurado = 1, restaurado_por = ?, restaurado_at = NOW() WHERE id = ?'
            )->execute([Auth::id(), $id]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudo restaurar: ' . $e->getMessage());
            redirect('/papelera');
            return;
        }

        flashSuccess('Elemento restaurado correctamente.');
        redirect('/papelera');
    }

    private function restaurarIndicador(array $snapshot): void {
        $ind = $snapshot['indicador'];

        $this->db->prepare(
            'INSERT INTO indicadores_eval
                (id, asignacion_id, periodo_id, codigo, descripcion, descripcion_2, peso, privado, autoevaluacion,
                 redaccion_tipo, desc_superior, desc_alto, desc_basico, desc_inferior, orden)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $ind['id'], $ind['asignacion_id'], $ind['periodo_id'], $ind['codigo'], $ind['descripcion'], $ind['descripcion_2'],
            $ind['peso'], $ind['privado'], $ind['autoevaluacion'], $ind['redaccion_tipo'],
            $ind['desc_superior'], $ind['desc_alto'], $ind['desc_basico'], $ind['desc_inferior'], $ind['orden'],
        ]);

        foreach ($snapshot['evaluaciones'] as $ev) {
            $this->restaurarFilaEvaluacion($ev);
        }
    }

    private function restaurarEvaluacion(array $snapshot): void {
        $this->restaurarFilaEvaluacion($snapshot['evaluacion'] + ['calificaciones' => $snapshot['calificaciones']]);
    }

    private function restaurarFilaEvaluacion(array $ev): void {
        $this->db->prepare(
            'INSERT INTO evaluaciones (id, indicador_id, nombre, fecha, porcentaje, privado, cantidad, recuperacion_de, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $ev['id'], $ev['indicador_id'], $ev['nombre'], $ev['fecha'], $ev['porcentaje'],
            $ev['privado'], $ev['cantidad'], $ev['recuperacion_de'], $ev['created_at'],
        ]);

        foreach ($ev['calificaciones'] as $c) {
            $this->db->prepare(
                'INSERT INTO calificaciones (id, evaluacion_id, matricula_id, valor, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$c['id'], $c['evaluacion_id'], $c['matricula_id'], $c['valor'], $c['created_at'], $c['updated_at']]);
        }
    }
}
