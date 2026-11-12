<?php
class UsuariosController {
    private PDO $db;

    const CARGOS = [
        'Administrador',
        'Asistente Administrativo',
        'Coordinador Académico',
        'Coordinadora Sistema Académico y Comunicativo CSMA',
        'Docente Básica Primaria',
        'Docente Básica Secundaria',
        'Docentes Preescolar',
        'Orientación Escolar Preescolar Y Primaria CSMA',
        'Orientación Escolar Primaria-Bachillerato CSMA',
        'Psicología Escolar',
        'Rectora',
        'Secretaria',
        'Tesorería',
    ];

    const ROLES_LABEL = [
        'admin'      => 'Administrador',
        'docente'    => 'Docente',
        'estudiante' => 'Estudiante',
        'padre'      => 'Padre/Familiar',
        'soporte'    => 'Soporte',
        'secretaria' => 'Secretaría',
    ];

    /** La secretaría solo puede crear/editar estudiantes y padres — nunca roles administrativos. */
    private function restringirRolSecretaria(string $rol): void {
        if (Auth::rol() === 'secretaria' && !in_array($rol, ['estudiante', 'padre'], true)) {
            abort(403);
        }
    }

    public function __construct() {
        $this->db = Database::get();
    }

    // ── INDEX ─────────────────────────────────────────────────────
    public function index(): void {
        Auth::requireRole('admin', 'secretaria');

        $rol    = $_GET['rol']    ?? '';
        $q      = trim($_GET['q'] ?? '');
        $grupoId = (int)($_GET['grupo_id'] ?? 0);
        $sinAcudiente = isset($_GET['sin_acudiente']);
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $limit  = 20;
        $offset = ($pagina - 1) * $limit;

        $joins = ' LEFT JOIN perfiles p ON p.usuario_id = u.id';
        $where  = ['1=1'];
        $params = [];

        if ($rol && array_key_exists($rol, self::ROLES_LABEL)) {
            $where[]  = 'u.rol = ?';
            $params[] = $rol;
        }
        if ($q) {
            $where[]  = '(u.nombre LIKE ? OR u.apellido LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
            $like     = "%{$q}%";
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($grupoId) {
            $joins .= ' JOIN estudiantes est_f ON est_f.usuario_id = u.id
                        JOIN matriculas mt_f ON mt_f.estudiante_id = est_f.id AND mt_f.estado = "activo" AND mt_f.grupo_id = ?';
            $params[] = $grupoId;
        }
        if ($sinAcudiente) {
            $where[] = "u.rol = 'estudiante' AND NOT EXISTS (
                SELECT 1 FROM estudiantes e2 JOIN acudientes ac2 ON ac2.estudiante_id = e2.id WHERE e2.usuario_id = u.id
            )";
        }

        $sql = "SELECT u.*, p.ciudad
                FROM usuarios u" . $joins . '
                WHERE ' . implode(' AND ', $where) .
               ' ORDER BY u.apellido, u.nombre
                LIMIT ? OFFSET ?';
        $st = $this->db->prepare($sql);
        $st->execute([...$params, $limit, $offset]);
        $usuarios = $st->fetchAll();

        // Conteos por rol
        $conteos = $this->db->query(
            'SELECT rol, COUNT(*) AS n FROM usuarios GROUP BY rol'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $total = (function() use ($joins, $where, $params) {
            $st = $this->db->prepare(
                "SELECT COUNT(*) FROM usuarios u" . $joins . ' WHERE ' . implode(' AND ', $where)
            );
            $st->execute($params);
            return (int) $st->fetchColumn();
        })();

        $grupos = $this->getGrupos();

        render('usuarios/index', compact('usuarios','conteos','total','pagina','limit','rol','q','grupos','grupoId','sinAcudiente'));
    }

    // ── NUEVO ─────────────────────────────────────────────────────
    public function nuevo(): void {
        Auth::requireRole('admin', 'secretaria');
        $grupos  = $this->getGrupos();
        $usuario = null;
        $perfil  = null;
        $estudiante = null;
        $editando   = false;
        render('usuarios/form', compact(
            'usuario','perfil','estudiante','grupos','editando'
        ) + ['cargos' => self::CARGOS, 'roles' => self::ROLES_LABEL]);
    }

    // ── GUARDAR ───────────────────────────────────────────────────
    public function guardar(): void {
        Auth::requireRole('admin', 'secretaria');
        csrfVerify();

        [$datos, $perfil_d, $est_d, $errors] = $this->extraerDatos();
        $this->restringirRolSecretaria($datos['rol']);
        if ($errors) { foreach ($errors as $e) flashError($e); redirect('/usuarios/nuevo'); return; }

        if ($this->existeUsername($datos['username'])) {
            flashError("El username «{$datos['username']}» ya está en uso.");
            redirect('/usuarios/nuevo'); return;
        }
        if ($this->existeEmail($datos['email'])) {
            flashError("El email «{$datos['email']}» ya está registrado.");
            redirect('/usuarios/nuevo'); return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'INSERT INTO usuarios (username,email,password,nombre,apellido,rol,cargo,activo)
                 VALUES (?,?,?,?,?,?,?,1)'
            )->execute([
                $datos['username'], $datos['email'],
                password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
                $datos['nombre'], $datos['apellido'],
                $datos['rol'], $datos['cargo'] ?: null,
            ]);
            $uid = (int) $this->db->lastInsertId();

            $this->syncRoles($uid, $datos['roles_extra'] ?? [], $datos['rol']);
            $this->upsertPerfil($uid, $perfil_d);

            foreach ($datos['todos_roles'] as $r) {
                if ($r === 'estudiante') $this->crearEstudiante($uid, $est_d);
            }

            $this->db->commit();
            flashSuccess("Usuario «{$datos['nombre']} {$datos['apellido']}» creado correctamente.");
            redirect('/usuarios');
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('Error al crear usuario: ' . $e->getMessage());
            redirect('/usuarios/nuevo');
        }
    }

    // ── EDITAR ────────────────────────────────────────────────────
    public function editar(int $id): void {
        Auth::requireRole('admin', 'secretaria');
        $usuario = $this->fetchUsuario($id);
        $this->restringirRolSecretaria($usuario['rol']);

        // Cargar todos los roles del usuario
        $stR = $this->db->prepare('SELECT rol FROM usuario_roles WHERE usuario_id=?');
        $stR->execute([$id]);
        $usuario['roles'] = $stR->fetchAll(PDO::FETCH_COLUMN) ?: [$usuario['rol']];

        $perfil  = $this->db->prepare(
            'SELECT * FROM perfiles WHERE usuario_id=?'
        )->execute([$id]) ? $this->db->query(
            "SELECT * FROM perfiles WHERE usuario_id=$id"
        )->fetch() ?: [] : [];

        $estudiante = null;
        if ($usuario['rol'] === 'estudiante') {
            $st = $this->db->prepare('SELECT e.*, m.grupo_id FROM estudiantes e LEFT JOIN matriculas m ON m.estudiante_id=e.id AND m.estado="activo" WHERE e.usuario_id=? LIMIT 1');
            $st->execute([$id]);
            $estudiante = $st->fetch() ?: null;
        }

        $grupos   = $this->getGrupos();
        $editando = true;

        $hijosVinculados = [];
        if ($usuario['rol'] === 'padre') {
            $qh = $this->db->prepare(
                "SELECT ac.id AS acudiente_id, ac.parentesco, u.nombre, u.apellido,
                        (SELECT gr.nombre FROM matriculas mt JOIN grupos g ON g.id = mt.grupo_id JOIN grados gr ON gr.id = g.grado_id
                         WHERE mt.estudiante_id = e.id ORDER BY mt.id DESC LIMIT 1) AS grado,
                        (SELECT g.nombre FROM matriculas mt JOIN grupos g ON g.id = mt.grupo_id
                         WHERE mt.estudiante_id = e.id ORDER BY mt.id DESC LIMIT 1) AS grupo
                 FROM acudientes ac
                 JOIN estudiantes e ON e.id = ac.estudiante_id
                 JOIN usuarios u ON u.id = e.usuario_id
                 WHERE ac.usuario_id = ?
                 ORDER BY u.nombre"
            );
            $qh->execute([$id]);
            $hijosVinculados = $qh->fetchAll();
        }

        render('usuarios/form', compact(
            'usuario','perfil','estudiante','grupos','editando','hijosVinculados'
        ) + ['cargos' => self::CARGOS, 'roles' => self::ROLES_LABEL]);
    }

    // ── HIJOS VINCULADOS (acudientes) ──────────────────────────────
    public function buscarHijo(int $padreId): void {
        Auth::requireRole('admin', 'secretaria');
        header('Content-Type: application/json');

        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) { echo json_encode([]); return; }

        $st = $this->db->prepare(
            "SELECT e.id AS estudiante_id, u.nombre, u.apellido, e.documento
             FROM estudiantes e
             JOIN usuarios u ON u.id = e.usuario_id
             WHERE u.colegio_id = ?
               AND (u.nombre LIKE ? OR u.apellido LIKE ? OR e.documento LIKE ?)
               AND e.id NOT IN (SELECT estudiante_id FROM acudientes WHERE usuario_id = ?)
             ORDER BY u.apellido, u.nombre
             LIMIT 15"
        );
        $like = "%$q%";
        $st->execute([Tenant::id(), $like, $like, $like, $padreId]);
        echo json_encode($st->fetchAll());
    }

    public function agregarHijo(int $padreId): void {
        Auth::requireRole('admin', 'secretaria');
        csrfVerify();

        $padre = $this->fetchUsuario($padreId);
        if ($padre['rol'] !== 'padre') { abort(403); }

        $estudianteId = (int)($_POST['estudiante_id'] ?? 0);
        $parentesco = trim($_POST['parentesco'] ?? '') ?: 'Acudiente';

        $chkEst = $this->db->prepare(
            'SELECT 1 FROM estudiantes e JOIN usuarios u ON u.id = e.usuario_id WHERE e.id = ? AND u.colegio_id = ? LIMIT 1'
        );
        $chkEst->execute([$estudianteId, Tenant::id()]);
        if (!$estudianteId || !$chkEst->fetch()) {
            flashError('Estudiante inválido.');
            redirect("/usuarios/{$padreId}/editar");
            return;
        }

        $chkDup = $this->db->prepare('SELECT 1 FROM acudientes WHERE usuario_id = ? AND estudiante_id = ? LIMIT 1');
        $chkDup->execute([$padreId, $estudianteId]);
        if ($chkDup->fetch()) {
            flashError('Ese estudiante ya está vinculado a este padre.');
            redirect("/usuarios/{$padreId}/editar");
            return;
        }

        $this->db->prepare(
            'INSERT INTO acudientes (usuario_id, estudiante_id, parentesco) VALUES (?, ?, ?)'
        )->execute([$padreId, $estudianteId, $parentesco]);

        Auditoria::registrar('acudiente.vincular', "Vinculó al padre #$padreId con el estudiante #$estudianteId ($parentesco).");
        flashSuccess('Hijo vinculado correctamente.');
        redirect("/usuarios/{$padreId}/editar");
    }

    public function quitarHijo(int $padreId, int $acudienteId): void {
        Auth::requireRole('admin', 'secretaria');
        csrfVerify();

        $this->db->prepare('DELETE FROM acudientes WHERE id = ? AND usuario_id = ?')->execute([$acudienteId, $padreId]);

        Auditoria::registrar('acudiente.quitar', "Quitó el vínculo #$acudienteId del padre #$padreId.");
        flashSuccess('Vínculo eliminado.');
        redirect("/usuarios/{$padreId}/editar");
    }

    // ── ACTUALIZAR ────────────────────────────────────────────────
    public function actualizar(int $id): void {
        Auth::requireRole('admin', 'secretaria');
        csrfVerify();
        $usuario = $this->fetchUsuario($id);
        $this->restringirRolSecretaria($usuario['rol']);

        [$datos, $perfil_d, $est_d, $errors] = $this->extraerDatos(true);
        $this->restringirRolSecretaria($datos['rol']);
        if ($errors) {
            foreach ($errors as $e) flashError($e);
            redirect("/usuarios/{$id}/editar"); return;
        }

        // Verificar unicidad excluyendo usuario actual
        if ($this->existeUsername($datos['username'], $id)) {
            flashError("El username «{$datos['username']}» ya está en uso.");
            redirect("/usuarios/{$id}/editar"); return;
        }
        if ($this->existeEmail($datos['email'], $id)) {
            flashError("El email «{$datos['email']}» ya está registrado.");
            redirect("/usuarios/{$id}/editar"); return;
        }

        $this->db->beginTransaction();
        try {
            $sqlUpd = 'UPDATE usuarios SET username=?,email=?,nombre=?,apellido=?,rol=?,cargo=?';
            $pUpd   = [$datos['username'],$datos['email'],$datos['nombre'],$datos['apellido'],$datos['rol'],$datos['cargo']?:null];
            if (!empty($datos['password'])) {
                $sqlUpd .= ',password=?';
                $pUpd[]  = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            }
            $sqlUpd .= ' WHERE id=?';
            $pUpd[]  = $id;
            $this->db->prepare($sqlUpd)->execute($pUpd);

            $this->syncRoles($id, $datos['roles_extra'] ?? [], $datos['rol']);
            $this->upsertPerfil($id, $perfil_d);

            foreach ($datos['todos_roles'] as $r) {
                if ($r === 'estudiante') $this->crearEstudiante($id, $est_d, true);
            }

            $this->db->commit();
            flashSuccess("Usuario «{$datos['nombre']} {$datos['apellido']}» actualizado.");
            redirect('/usuarios');
        } catch (Exception $e) {
            $this->db->rollBack();
            flashError('Error al actualizar: ' . $e->getMessage());
            redirect("/usuarios/{$id}/editar");
        }
    }

    // ── TOGGLE PRINCIPAL ──────────────────────────────────────────
    public function togglePrincipal(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $u = $this->fetchUsuario($id);
        if ($u['rol'] !== 'admin') {
            flashError('Solo un usuario con rol admin puede ser marcado como principal.');
            redirect('/usuarios'); return;
        }
        if ($id === Auth::id() && !Auth::isPrincipal()) {
            flashError('No tienes permiso para modificar esto.');
            redirect('/usuarios'); return;
        }
        // Quitar principal de todos los admins del mismo colegio, luego marcar este
        $this->db->prepare('UPDATE usuarios SET es_principal=0 WHERE rol=\'admin\'')->execute();
        $this->db->prepare('UPDATE usuarios SET es_principal=1 WHERE id=?')->execute([$id]);
        // Actualizar sesión si el cambio afecta al usuario actual
        if ($id === Auth::id()) {
            $_SESSION['user']['es_principal'] = true;
        } elseif (Auth::id() !== $id && ($_SESSION['user']['es_principal'] ?? false)) {
            // El usuario actual deja de ser principal
            $_SESSION['user']['es_principal'] = false;
        }
        flashSuccess("Admin principal actualizado a «{$u['nombre']} {$u['apellido']}».");
        redirect('/usuarios');
    }

    // ── TOGGLE ACTIVO ─────────────────────────────────────────────
    public function toggleActivo(int $id): void {
        Auth::requireRole('admin');
        csrfVerify();
        if ($id === Auth::id()) { flashError('No puedes desactivar tu propia cuenta.'); redirect('/usuarios'); return; }
        $u = $this->fetchUsuario($id);
        $nuevo = $u['activo'] ? 0 : 1;
        $this->db->prepare('UPDATE usuarios SET activo=? WHERE id=?')->execute([$nuevo, $id]);
        flashSuccess($nuevo ? "Usuario activado." : "Usuario desactivado.");
        redirect('/usuarios');
    }

    // ── CERRAR SESIONES REMOTAS ────────────────────────────────────
    public function cerrarSesiones(int $id): void {
        Auth::requireRole('admin', 'soporte');
        csrfVerify();
        $u = $this->fetchUsuario($id);
        Auth::cerrarSesionesRemotas($id);
        Auditoria::registrar('usuario.cerrar_sesiones', "Forzó el cierre de sesión de \"{$u['nombre']} {$u['apellido']}\" en todos los dispositivos.");
        flashSuccess('Se cerró la sesión activa de este usuario en todos los dispositivos.');
        redirect("/usuarios/{$id}/editar");
    }

    // ── REPORTE: estudiantes sin acudiente vinculado ─────────────
    public function sinAcudiente(): void {
        Auth::requireRole('admin', 'soporte', 'secretaria');

        $st = $this->db->prepare(
            "SELECT e.id AS estudiante_id, u.nombre, u.apellido, e.documento,
                    gr.nombre AS grado, g.nombre AS grupo
             FROM estudiantes e
             JOIN usuarios u ON u.id = e.usuario_id
             JOIN matriculas mt ON mt.estudiante_id = e.id
             JOIN grupos g ON g.id = mt.grupo_id
             JOIN grados gr ON gr.id = g.grado_id
             JOIN anos_lectivos al ON al.id = g.ano_lectivo_id
             WHERE u.colegio_id = ? AND al.activo = 1 AND mt.estado = 'activo'
               AND NOT EXISTS (SELECT 1 FROM acudientes ac WHERE ac.estudiante_id = e.id)
             ORDER BY gr.nivel, u.apellido"
        );
        $st->execute([Tenant::id()]);
        render('admin/estudiantes_sin_acudiente', ['estudiantes' => $st->fetchAll()]);
    }

    // ── ELIMINAR ─────────────────────────────────────────────────
    public function eliminar(int $id): void {
        Auth::requireRole('admin');
        csrfVerify();
        if ($id === Auth::id()) { flashError('No puedes eliminarte a ti mismo.'); redirect('/usuarios'); return; }
        $u = $this->fetchUsuario($id);

        $qp = $this->db->prepare('SELECT * FROM perfiles WHERE usuario_id = ?');
        $qp->execute([$id]);
        $perfil = $qp->fetch() ?: null;

        $qa = $this->db->prepare('SELECT * FROM acudientes WHERE usuario_id = ?');
        $qa->execute([$id]);
        $acudientes = $qa->fetchAll();

        $qas = $this->db->prepare('SELECT * FROM asignaciones WHERE docente_id = ?');
        $qas->execute([$id]);
        $asignaciones = $qas->fetchAll();

        $this->db->prepare(
            'INSERT INTO papelera_general (colegio_id, tipo, descripcion, snapshot_json, eliminado_por)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            Tenant::id(), 'usuario',
            "Usuario \"{$u['nombre']} {$u['apellido']}\" ({$u['username']}, rol {$u['rol']})",
            json_encode(['usuario' => $u, 'perfil' => $perfil, 'acudientes' => $acudientes, 'asignaciones' => $asignaciones]),
            Auth::id(),
        ]);

        $this->db->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
        Auditoria::registrar('usuario.eliminar', "Eliminó al usuario \"{$u['nombre']} {$u['apellido']}\" ({$u['username']}, rol {$u['rol']}).");
        flashSuccess("Usuario «{$u['nombre']} {$u['apellido']}» eliminado. Puedes restaurarlo desde la Papelera si fue un error.");
        redirect('/usuarios');
    }

    // ── PLANTILLA CSV ─────────────────────────────────────────────
    public function plantilla(): void {
        Auth::requireRole('admin');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_usuarios.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel
        fputcsv($out, [
            'rol','nombre','apellido','email','username','password',
            'cargo','documento','tipo_documento','grupo_id',
            'fecha_nacimiento','genero','estrato',
            'telefono','celular','ciudad','departamento','direccion','barrio','nacionalidad',
        ]);
        // Filas de ejemplo
        fputcsv($out, ['docente','María','López','maria.lopez@colegio.edu.co','maria.lopez','Docente123*','Docente Básica Secundaria','','','','1985-03-20','F','','3101234567','3101234567','Yopal','Casanare','','','Colombiana']);
        fputcsv($out, ['estudiante','Camilo','Pérez','camilo.perez@colegio.edu.co','camilo.perez','Alumno123*','','1098001122','TI','1','2012-07-15','M','3','','','Yopal','Casanare','','','Colombiana']);
        fputcsv($out, ['padre','Roberto','Pérez','roberto.perez@gmail.com','roberto.perez','Padre123*','','','','','1980-01-10','M','','3209876543','3209876543','Yopal','Casanare','','','Colombiana']);
        fclose($out);
        exit;
    }

    // ── CARGA MASIVA ──────────────────────────────────────────────
    public function cargaMasiva(): void {
        Auth::requireRole('admin');
        $grupos = $this->getGrupos();
        render('usuarios/carga_masiva', compact('grupos') + ['roles' => self::ROLES_LABEL]);
    }

    public function procesarCarga(): void {
        Auth::requireRole('admin');
        csrfVerify();

        if (empty($_FILES['archivo']['tmp_name'])) {
            flashError('Selecciona un archivo CSV.');
            redirect('/usuarios/carga-masiva'); return;
        }

        $file = $_FILES['archivo']['tmp_name'];
        $handle = fopen($file, 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle) ?: []));

        $creados = 0; $errores = []; $fila = 1; $credenciales = [];

        while (($row = fgetcsv($handle)) !== false) {
            $fila++;
            if (count($row) < 3) continue;
            $d = array_combine(
                array_slice($headers, 0, count($row)),
                array_slice($row, 0, count($headers))
            ) ?: [];

            $nombre   = trim($d['nombre']   ?? '');
            $apellido = trim($d['apellido'] ?? '');
            $rol      = trim($d['rol']      ?? 'estudiante');
            $email    = trim($d['email']    ?? '');
            $username = trim($d['username'] ?? '') ?: $this->sugerirUsername($nombre, $apellido);
            // Si no se especifica contraseña en el archivo, se genera una aleatoria
            // por usuario (nunca una fija compartida entre todos).
            $passwordManual = trim($d['password'] ?? '');
            $password = $passwordManual ?: $this->generarPasswordAleatoria();
            $cargo    = trim($d['cargo']    ?? '');

            if (!$nombre || !$apellido) { $errores[] = "Fila {$fila}: nombre y apellido son obligatorios."; continue; }
            if (!$email)                { $email = $this->generarEmailTmp($username); }
            if (!array_key_exists($rol, self::ROLES_LABEL)) { $errores[] = "Fila {$fila}: rol «{$rol}» no válido."; continue; }
            if ($this->existeUsername($username)) { $username = $this->sugerirUsernameLibre($nombre, $apellido); }
            if ($this->existeEmail($email))       { $errores[] = "Fila {$fila}: email «{$email}» ya existe."; continue; }

            try {
                $this->db->prepare(
                    'INSERT INTO usuarios (username,email,password,nombre,apellido,rol,cargo,activo) VALUES (?,?,?,?,?,?,?,1)'
                )->execute([
                    $username, $email,
                    password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
                    $nombre, $apellido, $rol, $cargo ?: null,
                ]);
                $uid = (int) $this->db->lastInsertId();

                // Perfil básico
                $this->upsertPerfil($uid, [
                    'telefono'        => $d['telefono']   ?? '',
                    'celular'         => $d['celular']    ?? '',
                    'ciudad'          => $d['ciudad']     ?? '',
                    'departamento'    => $d['departamento'] ?? '',
                    'direccion'       => $d['direccion']  ?? '',
                    'barrio'          => $d['barrio']     ?? '',
                    'nacionalidad'    => $d['nacionalidad'] ?? '',
                    'fecha_nacimiento'=> $d['fecha_nacimiento'] ?? '',
                    'genero'          => $d['genero']     ?? '',
                    'estrato'         => $d['estrato']    ?? '',
                ]);

                // Estudiante
                if ($rol === 'estudiante' && !empty($d['documento'])) {
                    $this->crearEstudiante($uid, [
                        'documento'        => $d['documento'],
                        'tipo_documento'   => $d['tipo_documento'] ?? 'TI',
                        'fecha_nacimiento' => $d['fecha_nacimiento'] ?? null,
                        'genero'           => $d['genero'] ?? null,
                        'estrato'          => $d['estrato'] ?? null,
                        'grupo_id'         => $d['grupo_id'] ?? null,
                    ]);
                }

                // Solo se puede mostrar la contraseña una vez (queda hasheada en BD)
                $credenciales[] = [
                    'nombre'   => $nombre . ' ' . $apellido,
                    'rol'      => self::ROLES_LABEL[$rol] ?? $rol,
                    'username' => $username,
                    'password' => $password,
                    'generada' => $passwordManual === '',
                ];

                $creados++;
            } catch (Exception $e) {
                $errores[] = "Fila {$fila} ({$nombre} {$apellido}): " . $e->getMessage();
            }
        }
        fclose($handle);

        if ($creados) flashSuccess("Se crearon {$creados} usuario(s) correctamente.");
        if ($errores) {
            $_SESSION['carga_errores'] = $errores;
            flashError(count($errores) . ' fila(s) con errores. Ver detalle abajo.');
        }
        if ($credenciales) {
            $_SESSION['carga_credenciales'] = $credenciales;
        }
        redirect('/usuarios/carga-masiva');
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function getGrupos(): array {
        return $this->db->query(
            'SELECT gr.id, g.nombre AS grado, gr.nombre AS grupo, g.nivel
             FROM grupos gr
             JOIN grados g ON g.id = gr.grado_id
             JOIN anos_lectivos al ON al.id = gr.ano_lectivo_id AND al.activo=1
             ORDER BY g.nivel, gr.nombre'
        )->fetchAll();
    }

    private function fetchUsuario(int $id): array {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE id=?');
        $st->execute([$id]);
        $u = $st->fetch();
        if (!$u) { abort(404); }
        return $u;
    }

    private function existeUsername(string $u, int $excluir = 0): bool {
        $st = $this->db->prepare('SELECT id FROM usuarios WHERE username=? AND id!=?');
        $st->execute([$u, $excluir]);
        return (bool) $st->fetch();
    }

    private function existeEmail(string $e, int $excluir = 0): bool {
        $st = $this->db->prepare('SELECT id FROM usuarios WHERE email=? AND id!=?');
        $st->execute([$e, $excluir]);
        return (bool) $st->fetch();
    }

    private function sugerirUsername(string $nombre, string $apellido): string {
        $n = preg_replace('/[^a-z0-9]/', '', strtolower(iconv('UTF-8','ASCII//TRANSLIT', explode(' ',$nombre)[0] ?? $nombre)));
        $a = preg_replace('/[^a-z0-9]/', '', strtolower(iconv('UTF-8','ASCII//TRANSLIT', explode(' ',$apellido)[0] ?? $apellido)));
        return "{$n}.{$a}";
    }

    private function sugerirUsernameLibre(string $nombre, string $apellido): string {
        $base = $this->sugerirUsername($nombre, $apellido);
        $i = 2;
        $try = $base;
        while ($this->existeUsername($try)) { $try = $base . $i++; }
        return $try;
    }

    // Contraseña aleatoria legible: evita caracteres ambiguos (0/O, 1/l/I)
    private function generarPasswordAleatoria(int $largo = 10): string {
        $mayus  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minus  = 'abcdefghjkmnpqrstuvwxyz';
        $digitos= '23456789';
        $simbolos = '!@#$%*';
        $todos  = $mayus . $minus . $digitos . $simbolos;

        // Garantizar al menos uno de cada tipo
        $pass = [
            $mayus[random_int(0, strlen($mayus) - 1)],
            $minus[random_int(0, strlen($minus) - 1)],
            $digitos[random_int(0, strlen($digitos) - 1)],
            $simbolos[random_int(0, strlen($simbolos) - 1)],
        ];
        for ($i = count($pass); $i < $largo; $i++) {
            $pass[] = $todos[random_int(0, strlen($todos) - 1)];
        }
        shuffle($pass);
        return implode('', $pass);
    }

    private function generarEmailTmp(string $username): string {
        return $username . '@colegio.edu.co';
    }

    private function upsertPerfil(int $uid, array $d): void {
        $fn = fn($k) => !empty($d[$k]) ? $d[$k] : null;
        $this->db->prepare(
            'INSERT INTO perfiles
             (usuario_id,telefono,celular,ciudad,direccion,fecha_nacimiento,
              pais,departamento,barrio,nacionalidad,email_personal,genero,estrato,celular_indicativo)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               telefono=VALUES(telefono), celular=VALUES(celular),
               ciudad=VALUES(ciudad), direccion=VALUES(direccion),
               fecha_nacimiento=VALUES(fecha_nacimiento), pais=VALUES(pais),
               departamento=VALUES(departamento), barrio=VALUES(barrio),
               nacionalidad=VALUES(nacionalidad), email_personal=VALUES(email_personal),
               genero=VALUES(genero), estrato=VALUES(estrato),
               celular_indicativo=VALUES(celular_indicativo)'
        )->execute([
            $uid, $fn('telefono'), $fn('celular'), $fn('ciudad'),
            $fn('direccion'), $fn('fecha_nacimiento'),
            $fn('pais'), $fn('departamento'), $fn('barrio'), $fn('nacionalidad'),
            $fn('email_personal'), $fn('genero') ?: null,
            $fn('estrato') ? (int)$d['estrato'] : null,
            $d['celular_indicativo'] ?? '+57',
        ]);
    }

    // ── Sincronizar roles en pivot ────────────────────────────────
    private function syncRoles(int $uid, array $extra, string $principal): void {
        $this->db->prepare('DELETE FROM usuario_roles WHERE usuario_id=?')->execute([$uid]);
        $ins = $this->db->prepare('INSERT IGNORE INTO usuario_roles (usuario_id,rol) VALUES (?,?)');
        $ins->execute([$uid, $principal]);
        foreach ($extra as $r) {
            if ($r && $r !== $principal) $ins->execute([$uid, $r]);
        }
    }

    private function crearEstudiante(int $uid, array $d, bool $update = false): void {
        if (!empty($d['documento'])) {
            if ($update) {
                $this->db->prepare(
                    'UPDATE estudiantes SET tipo_documento=?,fecha_nacimiento=?,genero=?,estrato=? WHERE usuario_id=?'
                )->execute([
                    $d['tipo_documento'] ?? 'TI',
                    $d['fecha_nacimiento'] ?: null,
                    $d['genero'] ?: null,
                    $d['estrato'] ? (int)$d['estrato'] : null,
                    $uid,
                ]);
            } else {
                $this->db->prepare(
                    'INSERT IGNORE INTO estudiantes
                     (usuario_id,documento,tipo_documento,fecha_nacimiento,genero,estrato)
                     VALUES (?,?,?,?,?,?)'
                )->execute([
                    $uid, $d['documento'],
                    $d['tipo_documento'] ?? 'TI',
                    $d['fecha_nacimiento'] ?: null,
                    $d['genero'] ?: null,
                    $d['estrato'] ? (int)$d['estrato'] : null,
                ]);
            }
        }
        // Matrícula en grupo
        if (!empty($d['grupo_id'])) {
            $estId = $this->db->query("SELECT id FROM estudiantes WHERE usuario_id={$uid}")->fetchColumn();
            if ($estId) {
                $this->db->prepare(
                    'INSERT IGNORE INTO matriculas (estudiante_id,grupo_id,estado) VALUES (?,?,\'activo\')'
                )->execute([$estId, (int)$d['grupo_id']]);
            }
        }
    }

    private function extraerDatos(bool $editando = false): array {
        $errors = [];
        $rolesValidos = array_keys(self::ROLES_LABEL);

        $rolPrincipal = $_POST['rol'] ?? 'estudiante';
        if (!in_array($rolPrincipal, $rolesValidos)) $rolPrincipal = 'estudiante';

        // Roles adicionales (checkboxes)
        $rolesExtra = array_filter(
            (array)($_POST['roles_extra'] ?? []),
            fn($r) => in_array($r, $rolesValidos) && $r !== $rolPrincipal
        );
        $todosRoles = array_unique(array_merge([$rolPrincipal], array_values($rolesExtra)));

        $datos  = [
            'nombre'      => trim($_POST['nombre']   ?? ''),
            'apellido'    => trim($_POST['apellido'] ?? ''),
            'email'       => trim($_POST['email']    ?? ''),
            'username'    => trim($_POST['username'] ?? ''),
            'password'    => $_POST['password']      ?? '',
            'rol'         => $rolPrincipal,
            'roles_extra' => $rolesExtra,
            'todos_roles' => $todosRoles,
            'cargo'       => trim($_POST['cargo']    ?? ''),
        ];
        if (!$datos['nombre'])   $errors[] = 'El nombre es obligatorio.';
        if (!$datos['apellido']) $errors[] = 'El apellido es obligatorio.';
        if (!$datos['email'])    $errors[] = 'El email es obligatorio.';
        if (!$datos['username']) $datos['username'] = $this->sugerirUsername($datos['nombre'], $datos['apellido']);
        if (!$editando && !$datos['password']) $errors[] = 'La contraseña es obligatoria.';

        $perfil = [
            'telefono'         => $_POST['telefono']          ?? '',
            'celular'          => $_POST['celular']           ?? '',
            'celular_indicativo'=> $_POST['cel_indicativo']   ?? '+57',
            'ciudad'           => $_POST['ciudad']            ?? '',
            'direccion'        => $_POST['direccion']         ?? '',
            'pais'             => $_POST['pais']              ?? 'Colombia',
            'departamento'     => $_POST['departamento']      ?? '',
            'barrio'           => $_POST['barrio']            ?? '',
            'nacionalidad'     => $_POST['nacionalidad']      ?? '',
            'email_personal'   => $_POST['email_personal']    ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento']  ?? '',
            'genero'           => $_POST['genero']            ?? '',
            'estrato'          => $_POST['estrato']           ?? '',
        ];

        $est = [
            'documento'        => trim($_POST['documento']       ?? ''),
            'tipo_documento'   => $_POST['tipo_documento']       ?? 'TI',
            'fecha_nacimiento' => $_POST['fecha_nacimiento']     ?? '',
            'genero'           => $_POST['genero']               ?? '',
            'estrato'          => $_POST['estrato']              ?? '',
            'grupo_id'         => !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : null,
        ];

        return [$datos, $perfil, $est, $errors];
    }
}
