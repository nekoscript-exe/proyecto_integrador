-- ATENEA clean database schema
-- This file contains structure only. No real users, emails, hashes, tokens or IP logs.

CREATE DATABASE IF NOT EXISTS `atenea`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `atenea`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `edad` int(11) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol` enum('usuario','admin') DEFAULT 'usuario',
  `estado` enum('activo','bloqueado') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `encuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `promedio` decimal(4,2) DEFAULT NULL,
  `materias_reprobadas` int(11) DEFAULT NULL,
  `asistencia` int(11) DEFAULT NULL,
  `horas_estudio` decimal(3,1) DEFAULT NULL,
  `horas_sueno` decimal(3,1) DEFAULT NULL,
  `uso_redes` decimal(3,1) DEFAULT NULL,
  `actividad_fisica` tinyint(1) DEFAULT NULL,
  `entrega_tareas` tinyint(4) DEFAULT NULL,
  `tiempo_transporte` int(11) DEFAULT NULL,
  `trabaja` tinyint(1) DEFAULT NULL,
  `acceso_internet` tinyint(1) DEFAULT NULL,
  `espacio_estudio` tinyint(1) DEFAULT NULL,
  `nivel_estres` tinyint(4) DEFAULT NULL,
  `desmotivacion` tinyint(4) DEFAULT NULL,
  `herramientas_digitales` tinyint(1) DEFAULT NULL,
  `administracion_tiempo` tinyint(4) DEFAULT NULL,
  `fecha_encuesta` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `encuestas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `resultados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `encuesta_id` int(11) NOT NULL,
  `nivel_riesgo` enum('Bajo','Medio','Alto') NOT NULL,
  `puntuacion_riesgo` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_resultado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `encuesta_id` (`encuesta_id`),
  CONSTRAINT `resultados_ibfk_1` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `recomendaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resultado_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `resultado_id` (`resultado_id`),
  CONSTRAINT `recomendaciones_ibfk_1` FOREIGN KEY (`resultado_id`) REFERENCES `resultados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `fecha_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) DEFAULT NULL,
  `dispositivo` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `admin_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `detalles` text DEFAULT NULL,
  `ip_admin` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `target_user_id` (`target_user_id`),
  CONSTRAINT `admin_historial_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_historial_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mail_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `subject` varchar(180) NOT NULL,
  `recipient_scope` enum('active','all','admins','specific') NOT NULL DEFAULT 'active',
  `body_html` mediumtext DEFAULT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `recipient_count` int(11) NOT NULL DEFAULT 0,
  `sent_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','queued','sending','completed','partial_error','failed','cancelled') NOT NULL DEFAULT 'queued',
  `scheduled_at` datetime DEFAULT NULL,
  `channel` varchar(20) NOT NULL DEFAULT 'email',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `status` (`status`),
  CONSTRAINT `mail_campaigns_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mail_campaign_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `status` enum('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_campaign_email` (`campaign_id`,`recipient_email`),
  KEY `campaign_id` (`campaign_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `status` (`status`),
  CONSTRAINT `mail_campaign_recipients_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `mail_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mail_campaign_recipients_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
