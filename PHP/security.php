<?php

function ateneaTableExists(mysqli $conn, string $table): bool
{
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    return (bool) ($result && $result->num_rows > 0);
}

function ateneaEnsureSecuritySchema(mysqli $conn): void
{
    if (!ateneaTableExists($conn, 'password_resets')) {
        $conn->query("
            CREATE TABLE password_resets (
                id int(11) NOT NULL AUTO_INCREMENT,
                usuario_id int(11) NOT NULL,
                token_hash char(64) NOT NULL,
                expires_at datetime NOT NULL,
                used_at datetime DEFAULT NULL,
                requested_ip varchar(45) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                UNIQUE KEY token_hash (token_hash),
                KEY usuario_id (usuario_id),
                CONSTRAINT password_resets_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    if (!ateneaTableExists($conn, 'admin_historial')) {
        $conn->query("
            CREATE TABLE admin_historial (
                id int(11) NOT NULL AUTO_INCREMENT,
                admin_id int(11) NOT NULL,
                accion varchar(100) NOT NULL,
                target_user_id int(11) DEFAULT NULL,
                detalles text DEFAULT NULL,
                ip_admin varchar(45) DEFAULT NULL,
                fecha timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY admin_id (admin_id),
                KEY target_user_id (target_user_id),
                CONSTRAINT admin_historial_ibfk_1 FOREIGN KEY (admin_id) REFERENCES usuarios (id) ON DELETE CASCADE,
                CONSTRAINT admin_historial_ibfk_2 FOREIGN KEY (target_user_id) REFERENCES usuarios (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
}

function ateneaLogAdminAction(mysqli $conn, int $adminId, string $action, ?int $targetUserId = null, ?string $details = null): void
{
    if (!ateneaTableExists($conn, 'admin_historial')) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("
        INSERT INTO admin_historial
        (
            admin_id,
            accion,
            target_user_id,
            detalles,
            ip_admin
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param("isiss", $adminId, $action, $targetUserId, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function ateneaCreatePasswordReset(mysqli $conn, int $userId): ?array
{
    if (!ateneaTableExists($conn, 'password_resets')) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO password_resets
        (
            usuario_id,
            token_hash,
            expires_at,
            requested_ip
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("isss", $userId, $hash, $expiresAt, $ip);
    $stmt->execute();
    $stmt->close();

    return [
        'token' => $token,
        'expires_at' => $expiresAt,
    ];
}

function ateneaGetPasswordReset(mysqli $conn, string $token): ?array
{
    if (!ateneaTableExists($conn, 'password_resets')) {
        return null;
    }

    $hash = hash('sha256', $token);
    $stmt = $conn->prepare("
        SELECT pr.id, pr.usuario_id, pr.expires_at, pr.used_at, u.nombre, u.correo
        FROM password_resets pr
        INNER JOIN usuarios u ON u.id = pr.usuario_id
        WHERE pr.token_hash = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    if (!empty($row['used_at']) || strtotime($row['expires_at']) < time()) {
        return null;
    }

    return $row;
}

function ateneaConsumePasswordReset(mysqli $conn, string $token): ?array
{
    return ateneaGetPasswordReset($conn, $token);
}
