<?php
class GradosController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    // ── Listado de grupos del año activo ────────────────────────
    public function index(): void {
        Auth::requireRole('admin', 'soporte');
        $cid = Tenant::id();

        $anoActivo = $this->db->prepare(
            'SELECT * FROM anos_lectivos WHERE colegio_id = ? AND activo = 1 LIMIT 1'
        );
        $anoActivo->execute([$cid]);
        $anoActivo = $anoActivo->fetch();

        $grupos = [];
        if ($anoActivo) {
            $qg = $this->db->prepare(
                "SELECT g.id, g.nombre, gr.nombre AS grado, gr.nivel,
                        (SELECT COUNT(*) FROM matriculas mt WHERE mt.grupo_id = g.id AND mt.estado = 'activo') AS total_est,
                        u.nombre AS director_nombre, u.apellido AS director_apellido
                 FROM grupos g
                 JOIN grados gr ON gr.id = g.grado_id
                 LEFT JOIN usuarios u ON u.id = g.director_id
                 WHERE g.ano_lectivo_id = ? AND g.activo = 1
                 ORDER BY gr.nivel, g.nombre"
            );
            $qg->execute([$anoActivo['id']]);
            $grupos = $qg->fetchAll();
        }

        $qHist = $this->db->prepare(
            'SELECT * FROM anos_lectivos WHERE colegio_id = ? ORDER BY id DESC'
        );
        $qHist->execute([$cid]);
        $anosHistoricos = $qHist->fetchAll();

        render('admin/grados_index', [
            'anoActivo' => $anoActivo,
            'grupos' => $grupos,
            'anosHistoricos' => $anosHistoricos,
        ]);
    }

    // ── Formulario: iniciar promoción a un nuevo año lectivo ───
    public function promocionForm(): void {
        Auth::requireRole('admin', 'soporte');
        $cid = Tenant::id();

        $anoActivo = $this->db->prepare('SELECT * FROM anos_lectivos WHERE colegio_id = ? AND activo = 1 LIMIT 1');
        $anoActivo->execute([$cid]);
        $anoActivo = $anoActivo->fetch();
        if (!$anoActivo) {
            flashError('No hay un año lectivo activo para promover.');
            redirect('/grados');
            return;
        }

        // Preview: cuántos estudiantes se promueven por grado, y quiénes se graduarían
        $qp = $this->db->prepare(
            "SELECT gr.nombre AS grado, gr.nivel, COUNT(mt.id) AS total
             FROM matriculas mt
             JOIN grupos g ON g.id = mt.grupo_id
             JOIN grados gr ON gr.id = g.grado_id
             WHERE g.ano_lectivo_id = ? AND mt.estado = 'activo'
             GROUP BY gr.id ORDER BY gr.nivel"
        );
        $qp->execute([$anoActivo['id']]);
        $preview = $qp->fetchAll();

        $maxNivel = (int)$this->db->query('SELECT MAX(nivel) FROM grados WHERE activo = 1')->fetchColumn();

        render('admin/grados_promocion_form', [
            'anoActivo' => $anoActivo,
            'preview' => $preview,
            'maxNivel' => $maxNivel,
        ]);
    }

    public function promocionEjecutar(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $cid = Tenant::id();

        $nombreNuevoAno = trim($_POST['nombre_nuevo_ano'] ?? '');
        if (!$nombreNuevoAno) {
            flashError('Escribe el nombre del nuevo año lectivo.');
            redirect('/grados/promocion');
            return;
        }

        $chkNombre = $this->db->prepare('SELECT 1 FROM anos_lectivos WHERE nombre = ? LIMIT 1');
        $chkNombre->execute([$nombreNuevoAno]);
        if ($chkNombre->fetch()) {
            flashError('Ya existe un año lectivo con ese nombre.');
            redirect('/grados/promocion');
            return;
        }

        $anoActivo = $this->db->prepare('SELECT * FROM anos_lectivos WHERE colegio_id = ? AND activo = 1 LIMIT 1');
        $anoActivo->execute([$cid]);
        $anoActivo = $anoActivo->fetch();
        if (!$anoActivo) {
            flashError('No hay un año lectivo activo para promover.');
            redirect('/grados');
            return;
        }

        $this->db->beginTransaction();
        try {
            // 1. Crear el nuevo año lectivo (aún inactivo)
            $this->db->prepare(
                'INSERT INTO anos_lectivos (colegio_id, nombre, activo, estado) VALUES (?, ?, 0, ?)'
            )->execute([$cid, $nombreNuevoAno, 'activo']);
            $nuevoAnoId = (int)$this->db->lastInsertId();

            // 2. Espejar TODOS los grupos del año actual en el año nuevo (mismo grado, misma letra)
            //    — esto permite tanto promover (grado+1) como repetir (mismo grado) en el año nuevo.
            $qg = $this->db->prepare('SELECT id, grado_id, nombre FROM grupos WHERE ano_lectivo_id = ? AND activo = 1');
            $qg->execute([$anoActivo['id']]);
            $gruposOrigen = $qg->fetchAll();

            $insGrupo = $this->db->prepare('INSERT INTO grupos (grado_id, ano_lectivo_id, nombre, activo) VALUES (?, ?, ?, 1)');
            $mapaGrupoNuevo = []; // "grado_id-nombre" => nuevo_grupo_id
            foreach ($gruposOrigen as $g) {
                $insGrupo->execute([$g['grado_id'], $nuevoAnoId, $g['nombre']]);
                $mapaGrupoNuevo[$g['grado_id'] . '-' . $g['nombre']] = (int)$this->db->lastInsertId();
            }

            // 3. Mapa de grado_id -> grado_id del siguiente nivel (para promoción)
            $grados = $this->db->query('SELECT id, nivel FROM grados WHERE activo = 1')->fetchAll();
            $gradoPorNivel = [];
            foreach ($grados as $gr) { $gradoPorNivel[(int)$gr['nivel']] = (int)$gr['id']; }
            $nivelPorGrado = [];
            foreach ($grados as $gr) { $nivelPorGrado[(int)$gr['id']] = (int)$gr['nivel']; }

            // 4. Promover cada matrícula activa
            $qm = $this->db->prepare(
                "SELECT mt.id, mt.estudiante_id, g.id AS grupo_id, g.grado_id, g.nombre AS grupo_nombre
                 FROM matriculas mt JOIN grupos g ON g.id = mt.grupo_id
                 WHERE g.ano_lectivo_id = ? AND mt.estado = 'activo'"
            );
            $qm->execute([$anoActivo['id']]);
            $matriculas = $qm->fetchAll();

            $insMatricula = $this->db->prepare('INSERT INTO matriculas (estudiante_id, grupo_id, estado) VALUES (?, ?, ?)');
            $updOrigen = $this->db->prepare('UPDATE matriculas SET estado = ? WHERE id = ?');

            $totalPromovidos = 0;
            $totalEgresados = 0;
            $totalSinGrupo = 0;

            foreach ($matriculas as $m) {
                $nivelActual = $nivelPorGrado[$m['grado_id']] ?? null;
                $nivelSiguiente = $nivelActual !== null ? $nivelActual + 1 : null;
                $gradoDestinoId = $nivelSiguiente !== null ? ($gradoPorNivel[$nivelSiguiente] ?? null) : null;

                if (!$gradoDestinoId) {
                    // Era el grado más alto (ej. Undécimo): se gradúa, no se crea matrícula nueva.
                    $updOrigen->execute(['egresado', $m['id']]);
                    $totalEgresados++;
                    continue;
                }

                $claveDestino = $gradoDestinoId . '-' . $m['grupo_nombre'];
                $grupoDestinoId = $mapaGrupoNuevo[$claveDestino] ?? null;

                if (!$grupoDestinoId) {
                    // No existe un grupo espejo con esa letra en el grado destino (caso raro) — se marca
                    // promovido pero queda sin matrícula nueva, para que el admin lo matricule manualmente.
                    $updOrigen->execute(['promovido', $m['id']]);
                    $totalSinGrupo++;
                    continue;
                }

                $insMatricula->execute([$m['estudiante_id'], $grupoDestinoId, 'activo']);
                $updOrigen->execute(['promovido', $m['id']]);
                $totalPromovidos++;
            }

            // 5. Activar el año nuevo y cerrar el anterior
            $this->db->prepare('UPDATE anos_lectivos SET activo = 0, estado = ? WHERE id = ?')
                      ->execute(['cerrado', $anoActivo['id']]);
            $this->db->prepare('UPDATE anos_lectivos SET activo = 1 WHERE id = ?')
                      ->execute([$nuevoAnoId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('No se pudo completar la promoción. No se realizó ningún cambio.');
            redirect('/grados/promocion');
            return;
        }

        Auditoria::registrar('promocion.ejecutar', "Promovió el colegio al año lectivo \"$nombreNuevoAno\" ($totalPromovidos promovidos, $totalEgresados egresados).");
        flashSuccess(
            "Promoción completada: $totalPromovidos estudiante(s) promovido(s), $totalEgresados graduado(s)" .
            ($totalSinGrupo > 0 ? ", $totalSinGrupo sin grupo destino (requieren matrícula manual)" : "") . "."
        );
        redirect('/grados');
    }

    // ── Ajuste individual: retirar o repetir, después de la promoción masiva ──
    public function ajustarForm(int $grupoId): void {
        Auth::requireRole('admin', 'soporte');

        $qg = $this->db->prepare(
            'SELECT g.*, gr.nombre AS grado, gr.nivel, al.nombre AS ano
             FROM grupos g JOIN grados gr ON gr.id = g.grado_id JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE g.id = ? LIMIT 1'
        );
        $qg->execute([$grupoId]);
        $grupo = $qg->fetch();
        if (!$grupo) { abort(404); }

        $qe = $this->db->prepare(
            "SELECT mt.id AS matricula_id, e.id AS estudiante_id, u.nombre, u.apellido, mt.estado
             FROM matriculas mt JOIN estudiantes e ON e.id = mt.estudiante_id JOIN usuarios u ON u.id = e.usuario_id
             WHERE mt.grupo_id = ? ORDER BY u.apellido"
        );
        $qe->execute([$grupoId]);
        $estudiantes = $qe->fetchAll();

        // Grupos del mismo año con el grado anterior (nivel-1), por si hay que "devolver" a alguien
        $qgAnt = $this->db->prepare(
            "SELECT g2.id, g2.nombre FROM grupos g2
             JOIN grados gr2 ON gr2.id = g2.grado_id
             WHERE g2.ano_lectivo_id = ? AND gr2.nivel = ?"
        );
        $qgAnt->execute([$grupo['ano_lectivo_id'], (int)$grupo['nivel'] - 1]);
        $gruposGradoAnterior = $qgAnt->fetchAll();

        render('admin/grados_ajustar', [
            'grupo' => $grupo,
            'estudiantes' => $estudiantes,
            'gruposGradoAnterior' => $gruposGradoAnterior,
        ]);
    }

    public function retirarMatricula(int $matriculaId): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $q = $this->db->prepare(
            'SELECT mt.*, g.ano_lectivo_id FROM matriculas mt JOIN grupos g ON g.id = mt.grupo_id WHERE mt.id = ? LIMIT 1'
        );
        $q->execute([$matriculaId]);
        $mat = $q->fetch();
        if (!$mat) { abort(404); }

        $this->db->prepare("UPDATE matriculas SET estado = 'retirado' WHERE id = ?")->execute([$matriculaId]);

        flashSuccess('Estudiante retirado del grupo.');
        redirect('/grados/ajustar/' . $mat['grupo_id']);
    }

    public function moverGrupo(int $matriculaId): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $grupoDestinoId = (int)($_POST['grupo_destino_id'] ?? 0);

        $q = $this->db->prepare('SELECT * FROM matriculas WHERE id = ? LIMIT 1');
        $q->execute([$matriculaId]);
        $mat = $q->fetch();
        if (!$mat || !$grupoDestinoId) { abort(404); }

        $grupoOrigenId = (int)$mat['grupo_id'];

        // Verificar que no exista ya una matrícula de este estudiante en el grupo destino
        $chk = $this->db->prepare('SELECT id FROM matriculas WHERE estudiante_id = ? AND grupo_id = ? LIMIT 1');
        $chk->execute([$mat['estudiante_id'], $grupoDestinoId]);
        $existente = $chk->fetch();

        if ($existente) {
            $this->db->prepare("UPDATE matriculas SET estado = 'activo' WHERE id = ?")->execute([$existente['id']]);
        } else {
            $this->db->prepare('INSERT INTO matriculas (estudiante_id, grupo_id, estado) VALUES (?, ?, ?)')
                      ->execute([$mat['estudiante_id'], $grupoDestinoId, 'activo']);
        }
        $this->db->prepare("UPDATE matriculas SET estado = 'reprobado' WHERE id = ?")->execute([$matriculaId]);

        flashSuccess('Estudiante movido correctamente.');
        redirect('/grados/ajustar/' . $grupoOrigenId);
    }

    // ── AÑOS LECTIVOS (renombrar / reabrir / archivar) ──────────────
    public function renombrarAno(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            flashError('Escribe el nombre del año lectivo.');
            redirect('/grados');
            return;
        }

        $chk = $this->db->prepare('SELECT 1 FROM anos_lectivos WHERE nombre = ? AND id != ?');
        $chk->execute([$nombre, $id]);
        if ($chk->fetch()) {
            flashError('Ya existe otro año lectivo con ese nombre.');
            redirect('/grados');
            return;
        }

        $this->db->prepare('UPDATE anos_lectivos SET nombre = ? WHERE id = ? AND colegio_id = ?')
                  ->execute([$nombre, $id, Tenant::id()]);
        Auditoria::registrar('ano.renombrar', "Renombró el año lectivo #$id a \"$nombre\".");
        flashSuccess('Año lectivo renombrado.');
        redirect('/grados');
    }

    public function reabrirAno(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $cid = Tenant::id();

        $q = $this->db->prepare('SELECT nombre FROM anos_lectivos WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, $cid]);
        $nombre = $q->fetchColumn();
        if (!$nombre) { abort(404); }

        $this->db->beginTransaction();
        $this->db->prepare('UPDATE anos_lectivos SET activo = 0, estado = ? WHERE colegio_id = ? AND activo = 1')
                  ->execute(['cerrado', $cid]);
        $this->db->prepare('UPDATE anos_lectivos SET activo = 1, estado = ? WHERE id = ?')
                  ->execute(['activo', $id]);
        $this->db->commit();

        Auditoria::registrar('ano.reabrir', "Reabrió el año lectivo \"$nombre\" (#$id) como año activo.");
        flashSuccess("El año lectivo \"$nombre\" ahora es el año activo.");
        redirect('/grados');
    }

    public function archivarAno(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $cid = Tenant::id();

        $q = $this->db->prepare('SELECT nombre, activo FROM anos_lectivos WHERE id = ? AND colegio_id = ? LIMIT 1');
        $q->execute([$id, $cid]);
        $ano = $q->fetch();
        if (!$ano) { abort(404); }
        if ($ano['activo']) {
            flashError('No puedes archivar el año lectivo activo.');
            redirect('/grados');
            return;
        }

        $this->db->prepare('UPDATE anos_lectivos SET estado = ? WHERE id = ?')->execute(['archivado', $id]);
        Auditoria::registrar('ano.archivar', "Archivó el año lectivo \"{$ano['nombre']}\" (#$id).");
        flashSuccess('Año lectivo archivado.');
        redirect('/grados');
    }

    // ── CATÁLOGO DE GRADOS (niveles) ────────────────────────────────
    public function catalogoGrados(): void {
        Auth::requireRole('admin', 'soporte');
        $grados = $this->db->query(
            "SELECT gr.*, (SELECT COUNT(*) FROM grupos g WHERE g.grado_id = gr.id) AS total_grupos
             FROM grados gr ORDER BY gr.nivel"
        )->fetchAll();
        render('admin/grados_catalogo', ['grados' => $grados]);
    }

    public function guardarGrado(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $nombre = trim($_POST['nombre'] ?? '');
        $nivel = (int)($_POST['nivel'] ?? 0);
        if (!$nombre || !$nivel) {
            flashError('Completa el nombre y el nivel del grado.');
            redirect('/grados/catalogo');
            return;
        }

        $this->db->prepare('INSERT INTO grados (nombre, nivel, activo) VALUES (?, ?, 1)')->execute([$nombre, $nivel]);
        Auditoria::registrar('grado.crear', "Creó el grado \"$nombre\" (nivel $nivel).");
        flashSuccess('Grado creado.');
        redirect('/grados/catalogo');
    }

    public function actualizarGrado(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $nombre = trim($_POST['nombre'] ?? '');
        $nivel = (int)($_POST['nivel'] ?? 0);
        if (!$nombre || !$nivel) {
            flashError('Completa el nombre y el nivel del grado.');
            redirect('/grados/catalogo');
            return;
        }

        $this->db->prepare('UPDATE grados SET nombre = ?, nivel = ? WHERE id = ?')->execute([$nombre, $nivel, $id]);
        Auditoria::registrar('grado.actualizar', "Actualizó el grado #$id a \"$nombre\" (nivel $nivel).");
        flashSuccess('Grado actualizado.');
        redirect('/grados/catalogo');
    }

    public function eliminarGrado(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $chk = $this->db->prepare('SELECT COUNT(*) FROM grupos WHERE grado_id = ?');
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            flashError('No se puede eliminar: este grado tiene grupos asociados.');
            redirect('/grados/catalogo');
            return;
        }

        $this->db->prepare('DELETE FROM grados WHERE id = ?')->execute([$id]);
        Auditoria::registrar('grado.eliminar', "Eliminó el grado #$id.");
        flashSuccess('Grado eliminado.');
        redirect('/grados/catalogo');
    }

    // ── GRUPOS NUEVOS A MITAD DE AÑO ────────────────────────────────
    public function nuevoGrupoForm(): void {
        Auth::requireRole('admin', 'soporte');
        $cid = Tenant::id();

        $anoActivo = $this->db->prepare('SELECT * FROM anos_lectivos WHERE colegio_id = ? AND activo = 1 LIMIT 1');
        $anoActivo->execute([$cid]);
        $anoActivo = $anoActivo->fetch();
        if (!$anoActivo) {
            flashError('No hay un año lectivo activo.');
            redirect('/grados');
            return;
        }

        $grados = $this->db->query('SELECT id, nombre FROM grados WHERE activo = 1 ORDER BY nivel')->fetchAll();
        $docentes = $this->db->prepare("SELECT id, nombre, apellido FROM usuarios WHERE colegio_id = ? AND rol = 'docente' AND activo = 1 ORDER BY nombre");
        $docentes->execute([$cid]);
        $sedes = $this->db->prepare('SELECT id, nombre FROM sedes WHERE colegio_id = ? AND activa = 1 ORDER BY nombre');
        $sedes->execute([$cid]);

        render('admin/grupo_nuevo', ['grados' => $grados, 'docentes' => $docentes->fetchAll(), 'anoActivo' => $anoActivo, 'sedes' => $sedes->fetchAll()]);
    }

    public function crearGrupo(): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $cid = Tenant::id();

        $anoActivo = $this->db->prepare('SELECT id FROM anos_lectivos WHERE colegio_id = ? AND activo = 1 LIMIT 1');
        $anoActivo->execute([$cid]);
        $anoId = $anoActivo->fetchColumn();
        if (!$anoId) { abort(404); }

        $gradoId = (int)($_POST['grado_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $directorId = (int)($_POST['director_id'] ?? 0) ?: null;
        $sedeId = (int)($_POST['sede_id'] ?? 0) ?: null;

        if (!$gradoId || !$nombre) {
            flashError('Selecciona el grado y escribe el nombre/letra del grupo.');
            redirect('/grados/nuevo-grupo');
            return;
        }

        $chk = $this->db->prepare('SELECT 1 FROM grupos WHERE grado_id = ? AND ano_lectivo_id = ? AND nombre = ?');
        $chk->execute([$gradoId, $anoId, $nombre]);
        if ($chk->fetch()) {
            flashError('Ya existe un grupo con ese nombre en ese grado para el año activo.');
            redirect('/grados/nuevo-grupo');
            return;
        }

        $this->db->prepare('INSERT INTO grupos (grado_id, ano_lectivo_id, nombre, director_id, sede_id, activo) VALUES (?, ?, ?, ?, ?, 1)')
                  ->execute([$gradoId, $anoId, $nombre, $directorId, $sedeId]);

        Auditoria::registrar('grupo.crear', "Creó el grupo \"$nombre\" en el grado #$gradoId.");
        flashSuccess('Grupo creado correctamente.');
        redirect('/grados');
    }

    public function editarGrupoForm(int $id): void {
        Auth::requireRole('admin', 'soporte');

        $qg = $this->db->prepare(
            'SELECT g.*, gr.nombre AS grado FROM grupos g JOIN grados gr ON gr.id = g.grado_id WHERE g.id = ? LIMIT 1'
        );
        $qg->execute([$id]);
        $grupo = $qg->fetch();
        if (!$grupo) { abort(404); }

        $docentes = $this->db->prepare("SELECT id, nombre, apellido FROM usuarios WHERE colegio_id = ? AND rol = 'docente' AND activo = 1 ORDER BY nombre");
        $docentes->execute([Tenant::id()]);
        $sedes = $this->db->prepare('SELECT id, nombre FROM sedes WHERE colegio_id = ? AND activa = 1 ORDER BY nombre');
        $sedes->execute([Tenant::id()]);

        render('admin/grupo_editar', ['grupo' => $grupo, 'docentes' => $docentes->fetchAll(), 'sedes' => $sedes->fetchAll()]);
    }

    public function actualizarGrupo(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $nombre = trim($_POST['nombre'] ?? '');
        $directorId = (int)($_POST['director_id'] ?? 0) ?: null;
        $sedeId = (int)($_POST['sede_id'] ?? 0) ?: null;
        if (!$nombre) {
            flashError('Escribe el nombre/letra del grupo.');
            redirect('/grados/grupo/' . $id . '/editar');
            return;
        }

        $this->db->prepare('UPDATE grupos SET nombre = ?, director_id = ?, sede_id = ? WHERE id = ?')->execute([$nombre, $directorId, $sedeId, $id]);
        Auditoria::registrar('grupo.actualizar', "Actualizó el grupo #$id.");
        flashSuccess('Grupo actualizado.');
        redirect('/grados');
    }

    public function eliminarGrupo(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();

        $chk = $this->db->prepare("SELECT COUNT(*) FROM matriculas WHERE grupo_id = ? AND estado = 'activo'");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            flashError('No se puede eliminar: este grupo tiene estudiantes matriculados.');
            redirect('/grados');
            return;
        }

        $this->db->prepare('DELETE FROM grupos WHERE id = ?')->execute([$id]);
        Auditoria::registrar('grupo.eliminar', "Eliminó el grupo #$id.");
        flashSuccess('Grupo eliminado.');
        redirect('/grados');
    }
}
