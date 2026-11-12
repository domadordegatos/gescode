<?php
class DesempeniosController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::require();
        Auth::requireRole('admin', 'soporte');

        $desempenios = $this->db->query(
            'SELECT * FROM desempenios ORDER BY orden ASC'
        )->fetchAll();

        render('soporte/desempenios', compact('desempenios'));
    }

    public function guardar(): void {
        Auth::require();
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $items = $_POST['d'] ?? [];

        // Validar que los rangos no se solapen y cubran 0-10
        $errores = $this->validarRangos($items);
        if ($errores) {
            flashError(implode(' ', $errores));
            redirect('/soporte/desempenios');
            return;
        }

        // Verificar que exactamente uno esté marcado como perdido
        $perdidos = array_filter($items, fn($d) => !empty($d['es_perdido']));
        if (count($perdidos) !== 1) {
            flashError('Debes marcar exactamente un desempeño como "perdido" (reprobatorio).');
            redirect('/soporte/desempenios');
            return;
        }

        $this->db->exec('DELETE FROM desempenios');
        $stmt = $this->db->prepare(
            'INSERT INTO desempenios (nombre, abreviatura, rango_min, rango_max, es_perdido, orden, activo)
             VALUES (?,?,?,?,?,?,1)'
        );

        foreach (array_values($items) as $i => $d) {
            $nombre = trim($d['nombre'] ?? '');
            $abrev  = strtoupper(trim($d['abreviatura'] ?? ''));
            $min    = (float)($d['rango_min'] ?? 0);
            $max    = (float)($d['rango_max'] ?? 0);
            $perd   = empty($d['es_perdido']) ? 0 : 1;

            if (!$nombre || !$abrev) continue;
            $stmt->execute([$nombre, $abrev, $min, $max, $perd, $i + 1]);
        }

        flashSuccess('Desempeños guardados correctamente.');
        redirect('/soporte/desempenios');
    }

    private function validarRangos(array $items): array {
        $errores = [];
        $prev_max = null;

        // Ordenar por rango_min
        usort($items, fn($a, $b) => (float)($a['rango_min'] ?? 0) <=> (float)($b['rango_min'] ?? 0));

        foreach ($items as $d) {
            $nombre = trim($d['nombre'] ?? '');
            $min    = (float)($d['rango_min'] ?? 0);
            $max    = (float)($d['rango_max'] ?? 0);

            if (!$nombre) continue;

            if ($min >= $max) {
                $errores[] = "El rango mín debe ser menor que máx en «$nombre».";
            }
            if ($prev_max !== null && $min < $prev_max) {
                $errores[] = "Los rangos se solapan en «$nombre» (desde $min).";
            }
            $prev_max = $max;
        }

        return $errores;
    }
}
