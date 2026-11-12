<?php
class Usuario {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function findByUsername(string $username): ?array {
        $st = $this->db->prepare(
            'SELECT id, username, email, password, nombre, apellido, foto, rol, activo, es_principal, colegio_id
             FROM usuarios WHERE username = ? LIMIT 1'
        );
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array {
        $st = $this->db->prepare(
            'SELECT id, username, email, nombre, apellido, foto, rol, activo, ultimo_login
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function updateUltimoLogin(int $id): void {
        $this->db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')
                 ->execute([$id]);
    }

    public function updatePassword(int $id, string $hash): void {
        $this->db->prepare('UPDATE usuarios SET password = ? WHERE id = ?')
                 ->execute([$hash, $id]);
    }

    public function findByEmail(string $email): ?array {
        $st = $this->db->prepare(
            'SELECT id, username, email, nombre, apellido, activo
             FROM usuarios WHERE email = ? LIMIT 1'
        );
        $st->execute([trim($email)]);
        return $st->fetch() ?: null;
    }

    // ── Password resets ───────────────────────────────────────

    public function crearResetToken(int $usuario_id): string {
        $db    = $this->db;
        $token = bin2hex(random_bytes(32));
        $exp   = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TTL);
        // Invalidar tokens previos del mismo usuario
        $db->prepare('UPDATE password_resets SET used=1 WHERE usuario_id=? AND used=0')
           ->execute([$usuario_id]);
        $db->prepare(
            'INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?,?,?)'
        )->execute([$usuario_id, $token, $exp]);
        return $token;
    }

    public function findResetToken(string $token): ?array {
        $st = $this->db->prepare(
            'SELECT pr.*, u.nombre, u.apellido, u.email, u.username
             FROM password_resets pr
             JOIN usuarios u ON u.id = pr.usuario_id
             WHERE pr.token=? AND pr.used=0 AND pr.expires_at > NOW()
             LIMIT 1'
        );
        $st->execute([$token]);
        return $st->fetch() ?: null;
    }

    public function consumirResetToken(string $token): void {
        $this->db->prepare('UPDATE password_resets SET used=1 WHERE token=?')
                 ->execute([$token]);
    }

    public function buscarMenciones(string $q, int $limit = 8): array {
        $like = '%' . $q . '%';
        $st = $this->db->prepare(
            "SELECT id, username, nombre, apellido, rol
             FROM usuarios
             WHERE activo = 1
               AND (username LIKE ? OR nombre LIKE ? OR apellido LIKE ?)
             ORDER BY apellido, nombre
             LIMIT ?"
        );
        $st->execute([$like, $like, $like, $limit]);
        return $st->fetchAll();
    }

    // Listado paginado para admin
    public function listar(string $rol = '', int $pagina = 1, int $por_pagina = 30): array {
        $offset = ($pagina - 1) * $por_pagina;
        $where  = $rol ? 'WHERE rol = ?' : '';
        $params = $rol ? [$rol, $por_pagina, $offset] : [$por_pagina, $offset];
        $st = $this->db->prepare(
            "SELECT id, username, nombre, apellido, email, rol, activo, ultimo_login
             FROM usuarios $where ORDER BY apellido, nombre LIMIT ? OFFSET ?"
        );
        $st->execute($params);
        return $st->fetchAll();
    }

    public function crear(array $data): int {
        $st = $this->db->prepare(
            'INSERT INTO usuarios (username, email, password, nombre, apellido, rol)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
            $data['nombre'],
            $data['apellido'],
            $data['rol'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function verificarPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public function totalNoLeidos(int $usuario_id): int {
        $st = $this->db->prepare(
            'SELECT COUNT(*) FROM mensajes_destinatarios
             WHERE usuario_id = ? AND leido = 0 AND eliminado = 0'
        );
        $st->execute([$usuario_id]);
        return (int) $st->fetchColumn();
    }
}
