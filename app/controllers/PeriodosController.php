<?php
class PeriodosController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    // ── Año lectivo activo ────────────────────────────────────────
    private function anoActivo(): array {
        $ano = $this->db->query(
            'SELECT * FROM anos_lectivos WHERE activo=1 LIMIT 1'
        )->fetch();
        if (!$ano) {
            flashError('No hay un año lectivo activo. Activa uno primero.');
            redirect('/dashboard');
        }
        return $ano;
    }

    // ── Suma de porcentajes (excluyendo un ID) ────────────────────
    private function sumaOtros(int $ano_id, int $excluir = 0): float {
        $st = $this->db->prepare(
            'SELECT COALESCE(SUM(porcentaje_ano),0) FROM periodos
             WHERE ano_lectivo_id=? AND id!=?'
        );
        $st->execute([$ano_id, $excluir]);
        return (float) $st->fetchColumn();
    }

    // ── INDEX ─────────────────────────────────────────────────────
    public function index(): void {
        Auth::requireRole('admin');
        $ano = $this->anoActivo();

        $st = $this->db->prepare(
            'SELECT * FROM periodos WHERE ano_lectivo_id=?
             ORDER BY numero ASC, id ASC'
        );
        $st->execute([$ano['id']]);
        $periodos = $st->fetchAll();

        $total_pct = array_sum(array_column($periodos, 'porcentaje_ano'));

        render('periodos/index', compact('ano', 'periodos', 'total_pct'));
    }

    // ── NUEVO ─────────────────────────────────────────────────────
    public function nuevo(): void {
        Auth::requireRole('admin');
        $ano  = $this->anoActivo();
        $suma = $this->sumaOtros($ano['id']);

        $siguiente = (int) $this->db->query(
            "SELECT COALESCE(MAX(numero),0)+1 FROM periodos WHERE ano_lectivo_id={$ano['id']}"
        )->fetchColumn();

        $periodo   = null;
        $editando  = false;
        $pct_libre = max(0, 100 - $suma);

        render('periodos/form', compact('ano', 'periodo', 'editando', 'siguiente', 'pct_libre', 'suma'));
    }

    // ── GUARDAR (POST) ────────────────────────────────────────────
    public function guardar(): void {
        Auth::requireRole('admin');
        csrfVerify();
        $ano = $this->anoActivo();

        [$nombre, $numero, $pct, $campos] = $this->extraerCampos();

        if (!$nombre) {
            flashError('El nombre del período es obligatorio.');
            redirect('/periodos/nuevo');
            return;
        }

        $suma = $this->sumaOtros($ano['id']);
        if (round($suma + $pct, 4) > 100.01) {
            flashError(sprintf(
                'El porcentaje (%.2f%%) supera el 100%%. Disponible: %.2f%%.',
                $pct, max(0, 100 - $suma)
            ));
            redirect('/periodos/nuevo');
            return;
        }

        $this->db->prepare(
            'INSERT INTO periodos
             (ano_lectivo_id, nombre, numero, porcentaje_ano,
              fecha_inicio, fecha_fin, cierre_notas, max_notas,
              fecha_evaluaciones, activo, permite_notas)
             VALUES (?,?,?,?,?,?,?,?,?,0,0)'
        )->execute([
            $ano['id'], $nombre, $numero, $pct,
            $campos['fecha_inicio'], $campos['fecha_fin'],
            $campos['cierre_notas'], $campos['max_notas'],
            $campos['fecha_evaluaciones'],
        ]);

        flashSuccess("Período «{$nombre}» creado correctamente.");
        redirect('/periodos');
    }

    // ── EDITAR ────────────────────────────────────────────────────
    public function editar(int $id): void {
        Auth::requireRole('admin');
        $ano       = $this->anoActivo();
        $periodo   = $this->fetchPeriodo($id, $ano['id']);
        $suma      = $this->sumaOtros($ano['id'], $id);
        $pct_libre = max(0, 100 - $suma);
        $editando  = true;
        $siguiente = $periodo['numero'];

        render('periodos/form', compact('ano', 'periodo', 'editando', 'siguiente', 'pct_libre', 'suma'));
    }

    // ── ACTUALIZAR (POST) ─────────────────────────────────────────
    public function actualizar(int $id): void {
        Auth::requireRole('admin');
        csrfVerify();
        $ano     = $this->anoActivo();
        $this->fetchPeriodo($id, $ano['id']);

        [$nombre, $numero, $pct, $campos] = $this->extraerCampos();

        $suma = $this->sumaOtros($ano['id'], $id);
        if (round($suma + $pct, 4) > 100.01) {
            flashError(sprintf(
                'El porcentaje (%.2f%%) supera el 100%%. Disponible: %.2f%%.',
                $pct, max(0, 100 - $suma)
            ));
            redirect("/periodos/{$id}/editar");
            return;
        }

        $this->db->prepare(
            'UPDATE periodos SET
               nombre=?, numero=?, porcentaje_ano=?,
               fecha_inicio=?, fecha_fin=?,
               cierre_notas=?, max_notas=?, fecha_evaluaciones=?
             WHERE id=?'
        )->execute([
            $nombre, $numero, $pct,
            $campos['fecha_inicio'], $campos['fecha_fin'],
            $campos['cierre_notas'], $campos['max_notas'],
            $campos['fecha_evaluaciones'], $id,
        ]);

        flashSuccess("Período «{$nombre}» actualizado.");
        redirect('/periodos');
    }

    // ── ELIMINAR (POST) ───────────────────────────────────────────
    public function eliminar(int $id): void {
        Auth::requireRole('admin');
        csrfVerify();
        $ano     = $this->anoActivo();
        $periodo = $this->fetchPeriodo($id, $ano['id']);

        $this->db->prepare('DELETE FROM periodos WHERE id=?')->execute([$id]);
        flashSuccess("Período «{$periodo['nombre']}» eliminado.");
        redirect('/periodos');
    }

    // ── TOGGLE NOTAS (POST) ───────────────────────────────────────
    public function toggleNotas(int $id): void {
        Auth::requireRole('admin');
        csrfVerify();
        $ano     = $this->anoActivo();
        $periodo = $this->fetchPeriodo($id, $ano['id']);
        $nuevo   = $periodo['permite_notas'] ? 0 : 1;

        $this->db->prepare(
            'UPDATE periodos SET permite_notas=? WHERE id=?'
        )->execute([$nuevo, $id]);

        flashSuccess($nuevo
            ? "Subida de notas habilitada para «{$periodo['nombre']}»."
            : "Subida de notas cerrada para «{$periodo['nombre']}»."
        );
        redirect('/periodos');
    }

    // ── TOGGLE ACTIVO (POST) ──────────────────────────────────────
    public function toggleActivo(int $id): void {
        Auth::requireRole('admin');
        csrfVerify();
        $ano     = $this->anoActivo();
        $periodo = $this->fetchPeriodo($id, $ano['id']);
        $nuevo   = $periodo['activo'] ? 0 : 1;

        if ($nuevo === 1) {
            $this->db->prepare(
                'UPDATE periodos SET activo=0 WHERE ano_lectivo_id=?'
            )->execute([$ano['id']]);
        }
        $this->db->prepare('UPDATE periodos SET activo=? WHERE id=?')
                 ->execute([$nuevo, $id]);

        flashSuccess($nuevo
            ? "«{$periodo['nombre']}» marcado como período activo."
            : "Período desactivado."
        );
        redirect('/periodos');
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function fetchPeriodo(int $id, int $ano_id): array {
        $st = $this->db->prepare(
            'SELECT * FROM periodos WHERE id=? AND ano_lectivo_id=?'
        );
        $st->execute([$id, $ano_id]);
        $p = $st->fetch();
        if (!$p) {
            http_response_code(404);
            die('Período no encontrado.');
        }
        return $p;
    }

    private function extraerCampos(): array {
        $nombre = trim($_POST['nombre'] ?? '');
        $numero = max(1, (int)($_POST['numero'] ?? 1));
        $pct    = round((float)str_replace(',', '.', $_POST['porcentaje'] ?? '0'), 2);
        $pct    = max(0, min(100, $pct));
        $fecha  = fn(string $k) => !empty($_POST[$k]) ? $_POST[$k] : null;

        return [$nombre, $numero, $pct, [
            'fecha_inicio'       => $fecha('fecha_inicio'),
            'fecha_fin'          => $fecha('fecha_fin'),
            'cierre_notas'       => $fecha('cierre_notas'),
            'max_notas'          => $fecha('max_notas'),
            'fecha_evaluaciones' => $fecha('fecha_evaluaciones'),
        ]];
    }
}
