-- Migracion para habilitar funcionalidades de administrador en ATENEA
-- Ejecuta este archivo sobre una BD ya existente.
-- Si tu tabla usuarios ya tiene la columna estado, esta migracion solo crea
-- el historial de admin y la tabla de restablecimiento de contrasena.

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS estado ENUM('activo','bloqueado') DEFAULT 'activo' AFTER rol;

-- Cambia este correo por el usuario que quieras promover a admin.
-- Puedes ejecutar varias veces con diferentes correos.
UPDATE usuarios
SET rol = 'admin'
WHERE correo = 'ianazaelhernandezsilva@gmail.com';

CREATE TABLE IF NOT EXISTS password_resets (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS admin_historial (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
