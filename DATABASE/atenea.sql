-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 22-06-2026 a las 04:56:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `atenea`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin_historial`
--

CREATE TABLE `admin_historial` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `detalles` text DEFAULT NULL,
  `ip_admin` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin_historial`
--

INSERT INTO `admin_historial` (`id`, `admin_id`, `accion`, `target_user_id`, `detalles`, `ip_admin`, `fecha`) VALUES
(1, 2, 'Ejecutar SQL', NULL, 'describe usuarios;', '::1', '2026-05-28 23:24:52'),
(2, 2, 'Bloquear/desbloquear usuario', 4, 'Estado alternado', '::1', '2026-05-28 23:25:15'),
(3, 2, 'Ejecutar SQL', NULL, 'select * from usuarios;', '::1', '2026-05-29 14:22:19'),
(4, 2, 'Bloquear/desbloquear usuario', NULL, 'Estado alternado', '::1', '2026-05-31 21:48:21'),
(5, 2, 'Bloquear/desbloquear usuario', NULL, 'Estado alternado', '::1', '2026-05-31 21:48:40'),
(6, 2, 'Eliminar usuario', NULL, 'Se eliminaron datos asociados', '::1', '2026-05-31 21:48:44'),
(7, 2, 'Ejecutar SQL', NULL, 'select * from usuarios;', '::1', '2026-06-04 16:39:00'),
(8, 2, 'Ejecutar SQL', NULL, 'select * from usuarios;', '::1', '2026-06-04 17:55:30'),
(9, 2, 'Ejecutar SQL', NULL, 'select * from usuarios;', '::1', '2026-06-17 14:49:17'),
(10, 2, 'Actualizar usuario', NULL, 'Nombre, edad o carrera modificados', '::1', '2026-06-17 14:49:42'),
(11, 2, 'Actualizar usuario', 6, 'Nombre, edad o carrera modificados', '::1', '2026-06-17 14:49:53'),
(12, 2, 'Actualizar usuario', 7, 'Nombre, edad o carrera modificados', '::1', '2026-06-17 14:50:09'),
(13, 2, 'Actualizar usuario', 10, 'Nombre, edad o carrera modificados', '::1', '2026-06-17 14:50:28'),
(14, 2, 'Actualizar usuario', 5, 'Nombre, edad o carrera modificados', '::1', '2026-06-17 14:50:50'),
(15, 2, 'Actualizar usuario', 7, 'Nombre, edad o carrera modificados', '::1', '2026-06-17 14:50:58'),
(16, 2, 'Eliminar usuario', NULL, 'Se eliminaron datos asociados', '::1', '2026-06-17 14:51:28'),
(17, 2, 'Ejecutar SQL', NULL, 'Select * from usuarios;', '::1', '2026-06-17 14:51:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dataset_uploads`
--

CREATE TABLE `dataset_uploads` (
  `id` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `archivo_guardado` varchar(255) DEFAULT NULL,
  `fuente` varchar(100) DEFAULT 'Kaggle',
  `filas_raw` int(11) DEFAULT 0,
  `filas_clean` int(11) DEFAULT 0,
  `estado` enum('procesando','completado','error') NOT NULL DEFAULT 'procesando',
  `mensaje` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_procesado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dataset_analysis_results`
--

CREATE TABLE `dataset_analysis_results` (
  `id` int(11) NOT NULL,
  `dataset_upload_id` int(11) DEFAULT NULL,
  `total_students` int(11) DEFAULT NULL,
  `promedio_general` decimal(5,2) DEFAULT NULL,
  `porcentaje_aprobados` decimal(5,2) DEFAULT NULL,
  `porcentaje_reprobados` decimal(5,2) DEFAULT NULL,
  `promedio_matematicas` decimal(5,2) DEFAULT NULL,
  `promedio_ciencias` decimal(5,2) DEFAULT NULL,
  `promedio_ingles` decimal(5,2) DEFAULT NULL,
  `correlacion_estudio_desempeno` decimal(6,4) DEFAULT NULL,
  `correlacion_asistencia_desempeno` decimal(6,4) DEFAULT NULL,
  `fecha_analisis` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dataset_estudiantes_clean`
--

CREATE TABLE `dataset_estudiantes_clean` (
  `id` int(11) NOT NULL,
  `dataset_upload_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `study_hours_per_day` decimal(4,2) DEFAULT NULL,
  `attendance_percentage` decimal(5,2) DEFAULT NULL,
  `parental_education` varchar(50) DEFAULT NULL,
  `internet_access` tinyint(1) DEFAULT NULL,
  `extracurricular_activities` tinyint(1) DEFAULT NULL,
  `math_score` int(11) DEFAULT NULL,
  `science_score` int(11) DEFAULT NULL,
  `english_score` int(11) DEFAULT NULL,
  `previous_year_score` decimal(5,2) DEFAULT NULL,
  `final_percentage` decimal(5,2) DEFAULT NULL,
  `performance_level` varchar(20) DEFAULT NULL,
  `pass_fail` tinyint(1) DEFAULT NULL,
  `promedio_materias` decimal(5,2) DEFAULT NULL,
  `fecha_procesado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dataset_estudiantes_raw`
--

CREATE TABLE `dataset_estudiantes_raw` (
  `id` int(11) NOT NULL,
  `dataset_upload_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `class` int(11) DEFAULT NULL,
  `study_hours_per_day` decimal(4,2) DEFAULT NULL,
  `attendance_percentage` decimal(5,2) DEFAULT NULL,
  `parental_education` varchar(50) DEFAULT NULL,
  `internet_access` enum('Yes','No') DEFAULT NULL,
  `extracurricular_activities` enum('Yes','No') DEFAULT NULL,
  `math_score` int(11) DEFAULT NULL,
  `science_score` int(11) DEFAULT NULL,
  `english_score` int(11) DEFAULT NULL,
  `previous_year_score` decimal(5,2) DEFAULT NULL,
  `final_percentage` decimal(5,2) DEFAULT NULL,
  `performance_level` varchar(20) DEFAULT NULL,
  `pass_fail` varchar(10) DEFAULT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas`
--

CREATE TABLE `encuestas` (
  `id` int(11) NOT NULL,
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
  `fecha_encuesta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `encuestas`
--

INSERT INTO `encuestas` (`id`, `usuario_id`, `promedio`, `materias_reprobadas`, `asistencia`, `horas_estudio`, `horas_sueno`, `uso_redes`, `actividad_fisica`, `entrega_tareas`, `tiempo_transporte`, `trabaja`, `acceso_internet`, `espacio_estudio`, `nivel_estres`, `desmotivacion`, `herramientas_digitales`, `administracion_tiempo`, `fecha_encuesta`) VALUES
(1, 2, 8.50, 2, 90, 4.0, 6.0, 9.0, 1, 2, 40, 0, 1, 1, 6, 2, 1, 2, '2026-05-26 00:10:54'),
(2, NULL, 8.00, 1, 80, 0.0, 7.0, 24.0, 1, 2, 20, 1, 1, 0, 10, 5, 1, 4, '2026-05-26 03:18:00'),
(3, 4, 7.00, 2, 80, 7.0, 6.0, 10.0, 0, 2, 40, 0, 1, 1, 2, 2, 1, 2, '2026-05-26 05:52:54'),
(4, 5, 8.20, 0, 100, 9.0, 6.0, 4.0, 1, 3, 5, 0, 1, 0, 10, 3, 1, 3, '2026-05-26 05:56:41'),
(5, 6, 8.90, 0, 10, 4.0, 5.0, 4.0, 1, 4, 80, 1, 1, 0, 8, 3, 1, 3, '2026-05-26 06:04:08'),
(6, 7, 8.60, 0, 95, 0.5, 5.0, 6.0, 1, 3, 30, 0, 1, 0, 10, 3, 0, 1, '2026-05-26 14:46:40'),
(8, 9, 8.20, 2, 90, 6.0, 4.0, 5.0, 1, 3, 60, 0, 1, 1, 10, 4, 0, 1, '2026-05-26 15:03:38'),
(9, 10, 8.20, 2, 90, 6.0, 4.0, 5.0, 1, 3, 60, 0, 1, 1, 10, 4, 0, 1, '2026-05-26 15:04:17'),
(10, 11, 8.00, 0, 90, 0.5, 6.0, 3.0, 1, 3, 120, 1, 1, 1, 5, 4, 0, 3, '2026-05-26 15:06:48'),
(13, 14, 8.70, 0, 100, 2.0, 4.0, 6.0, 1, 3, 15, 0, 1, 1, 10, 4, 0, 1, '2026-06-01 15:38:25'),
(14, 15, 8.50, 1, 90, 2.5, 5.0, 4.0, 1, 3, 45, 0, 1, 1, 7, 4, 1, 3, '2026-06-01 15:50:30'),
(15, 16, 8.50, 0, 80, 3.0, 8.0, 6.0, 0, 3, 60, 0, 1, 1, 5, 4, 1, 3, '2026-06-01 16:34:51'),
(16, 17, 5.00, 4, 36, 6.0, 7.0, 3.0, 1, 3, 60, 1, 1, 1, 8, 4, 1, 2, '2026-06-04 17:52:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `requested_ip`, `created_at`) VALUES
(1, 4, '76d597c5af970bf2d9a049780bb89fa4867214044e21976140dee7e4f522690d', '2026-05-29 02:27:54', '2026-05-28 17:29:17', '::1', '2026-05-28 23:27:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recomendaciones`
--

CREATE TABLE `recomendaciones` (
  `id` int(11) NOT NULL,
  `resultado_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recomendaciones`
--

INSERT INTO `recomendaciones` (`id`, `resultado_id`, `mensaje`, `fecha`) VALUES
(1, 1, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-05-26 05:23:36'),
(2, 1, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-05-26 05:23:36'),
(3, 1, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-05-26 05:23:36'),
(4, 1, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-05-26 05:23:36'),
(5, 2, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-05-26 05:23:36'),
(6, 2, 'Mejora la asistencia: cada clase recuperada aumenta la información disponible para resolver tareas y exámenes.', '2026-05-26 05:23:36'),
(7, 2, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-05-26 05:23:36'),
(8, 2, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-05-26 05:23:36'),
(9, 2, 'Identifica un espacio fijo de estudio, aunque sea compartido, con horario definido y materiales listos.', '2026-05-26 05:23:36'),
(102, 1, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-06-01 15:59:29'),
(103, 1, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(104, 1, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 15:59:29'),
(105, 1, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(106, 3, 'Agenda dos bloques de estudio enfocado por semana para reforzar las materias con menor desempeño.', '2026-06-01 15:59:29'),
(107, 3, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-06-01 15:59:29'),
(108, 3, 'Mejora la asistencia: cada clase recuperada aumenta la información disponible para resolver tareas y exámenes.', '2026-06-01 15:59:29'),
(109, 3, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(110, 3, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 15:59:29'),
(111, 4, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(112, 4, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(113, 4, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(114, 4, 'Identifica un espacio fijo de estudio, aunque sea compartido, con horario definido y materiales listos.', '2026-06-01 15:59:29'),
(115, 5, 'Mejora la asistencia: cada clase recuperada aumenta la información disponible para resolver tareas y exámenes.', '2026-06-01 15:59:29'),
(116, 5, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(117, 5, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(118, 5, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(119, 5, 'Identifica un espacio fijo de estudio, aunque sea compartido, con horario definido y materiales listos.', '2026-06-01 15:59:29'),
(120, 6, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(121, 6, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 15:59:29'),
(122, 6, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(123, 6, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(124, 6, 'Identifica un espacio fijo de estudio, aunque sea compartido, con horario definido y materiales listos.', '2026-06-01 15:59:29'),
(125, 8, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-06-01 15:59:29'),
(126, 8, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(127, 8, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 15:59:29'),
(128, 8, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(129, 8, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(130, 9, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-06-01 15:59:29'),
(131, 9, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(132, 9, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 15:59:29'),
(133, 9, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(134, 9, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(135, 10, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(136, 10, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(140, 13, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(141, 13, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 15:59:29'),
(142, 13, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(143, 13, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(144, 14, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-06-01 15:59:29'),
(145, 14, 'Ajusta tu descanso; dormir mejor ayuda a memoria, concentración y manejo del estrés.', '2026-06-01 15:59:29'),
(146, 14, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-01 15:59:29'),
(147, 14, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 15:59:29'),
(151, 15, 'Mejora la asistencia: cada clase recuperada aumenta la información disponible para resolver tareas y exámenes.', '2026-06-01 16:35:04'),
(152, 15, 'Reduce redes sociales durante horarios de estudio usando bloques sin notificaciones de 25 a 40 minutos.', '2026-06-01 16:35:04'),
(153, 15, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-01 16:35:04'),
(159, 16, 'Agenda dos bloques de estudio enfocado por semana para reforzar las materias con menor desempeño.', '2026-06-04 17:52:40'),
(160, 16, 'Prioriza un plan de recuperación para materias reprobadas y busca asesoría antes del siguiente corte.', '2026-06-04 17:52:40'),
(161, 16, 'Mejora la asistencia: cada clase recuperada aumenta la información disponible para resolver tareas y exámenes.', '2026-06-04 17:52:40'),
(162, 16, 'Incluye pausas activas y habla con un tutor si el estrés se mantiene alto varios días.', '2026-06-04 17:52:40'),
(163, 16, 'Usa una lista semanal de entregas y separa tareas urgentes de tareas importantes.', '2026-06-04 17:52:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados`
--

CREATE TABLE `resultados` (
  `id` int(11) NOT NULL,
  `encuesta_id` int(11) NOT NULL,
  `nivel_riesgo` enum('Bajo','Medio','Alto') NOT NULL,
  `puntuacion_riesgo` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_resultado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resultados`
--

INSERT INTO `resultados` (`id`, `encuesta_id`, `nivel_riesgo`, `puntuacion_riesgo`, `observaciones`, `fecha_resultado`) VALUES
(1, 1, 'Medio', 35.00, 'Riesgo Medio con puntuación 35/100. Promedio: 8.50, asistencia: 90%, estrés: 6/10.', '2026-05-26 05:23:36'),
(2, 2, 'Alto', 80.00, 'Riesgo Alto con puntuación 80/100. Promedio: 8.00, asistencia: 80%, estrés: 10/10.', '2026-05-26 05:23:36'),
(3, 3, 'Medio', 51.00, 'Riesgo Medio con puntuación 51/100. Promedio: 7.00, asistencia: 80%, estrés: 2/10.', '2026-05-26 05:52:54'),
(4, 4, 'Bajo', 28.00, 'Riesgo Bajo con puntuación 28/100. Promedio: 8.20, asistencia: 100%, estrés: 10/10.', '2026-05-26 05:56:41'),
(5, 5, 'Medio', 55.00, 'Riesgo Medio con puntuación 55/100. Promedio: 8.90, asistencia: 10%, estrés: 8/10.', '2026-05-26 06:04:08'),
(6, 6, 'Medio', 40.00, 'Riesgo Medio con puntuación 40/100. Promedio: 8.60, asistencia: 95%, estrés: 10/10.', '2026-05-26 14:46:40'),
(8, 8, 'Medio', 51.00, 'Riesgo Medio con puntuación 51/100. Promedio: 8.20, asistencia: 90%, estrés: 10/10.', '2026-05-26 15:03:38'),
(9, 9, 'Medio', 51.00, 'Riesgo Medio con puntuación 51/100. Promedio: 8.20, asistencia: 90%, estrés: 10/10.', '2026-05-26 15:04:17'),
(10, 10, 'Bajo', 31.00, 'Riesgo Bajo con puntuación 31/100. Promedio: 8.00, asistencia: 90%, estrés: 5/10.', '2026-05-26 15:06:48'),
(13, 13, 'Medio', 38.00, 'Riesgo Medio con puntuación 38/100. Promedio: 8.70, asistencia: 100%, estrés: 10/10.', '2026-06-01 15:38:25'),
(14, 14, 'Medio', 32.00, 'Riesgo Medio con puntuación 32/100. Promedio: 8.50, asistencia: 90%, estrés: 7/10.', '2026-06-01 15:50:30'),
(15, 15, 'Bajo', 27.00, 'Riesgo Bajo con puntuación 27/100. Promedio: 8.50, asistencia: 80%, estrés: 5/10.', '2026-06-01 16:34:51'),
(16, 16, 'Alto', 98.00, 'Riesgo Alto con puntuación 98/100. Promedio: 5.00, asistencia: 36%, estrés: 8/10.', '2026-06-04 17:52:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) DEFAULT NULL,
  `dispositivo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `usuario_id`, `fecha_inicio`, `ip_usuario`, `dispositivo`) VALUES
(1, 2, '2026-05-26 00:54:26', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(3, 2, '2026-05-26 05:02:25', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(4, 2, '2026-05-26 05:11:24', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(5, 2, '2026-05-26 05:27:10', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(6, 2, '2026-05-26 05:28:25', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(7, 2, '2026-05-26 05:45:37', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(8, 2, '2026-05-26 05:49:35', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(9, 4, '2026-05-26 05:53:09', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(10, 5, '2026-05-26 05:56:55', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 15; en; Infinix X6855 Build/SP1A.210812.016) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.7778.120 HiBrowser/v2.25.12.3;lang=es;nation=MX;locale=es_US UWS/ Mobile Safari/537.36'),
(11, 6, '2026-05-26 06:04:17', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(12, 2, '2026-05-26 06:12:39', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(13, 2, '2026-05-26 12:12:11', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(15, 10, '2026-05-26 15:04:51', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(16, 11, '2026-05-26 15:06:59', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(17, 12, '2026-05-26 15:38:49', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(18, 2, '2026-05-27 16:54:26', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(19, 2, '2026-05-28 22:09:21', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(20, 4, '2026-05-28 22:17:28', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(21, 2, '2026-05-28 22:17:43', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(22, 2, '2026-05-28 22:22:07', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(23, 2, '2026-05-28 22:23:11', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(24, 2, '2026-05-28 23:05:21', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(25, 2, '2026-05-28 23:14:57', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(26, 2, '2026-05-28 23:24:05', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(27, 2, '2026-05-28 23:24:23', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(28, 4, '2026-05-28 23:29:32', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(29, 4, '2026-05-28 23:36:10', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(30, 2, '2026-05-28 23:36:48', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(31, 2, '2026-05-28 23:39:24', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(32, 2, '2026-05-28 23:41:07', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(33, 2, '2026-05-28 23:48:26', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(34, 2, '2026-05-28 23:52:10', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(35, 2, '2026-05-28 23:59:39', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(36, 2, '2026-05-29 14:20:58', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(37, 4, '2026-05-31 20:16:52', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(38, 4, '2026-05-31 21:48:02', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(39, 2, '2026-05-31 21:48:15', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(40, 4, '2026-05-31 21:48:27', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(41, 2, '2026-05-31 21:48:33', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(42, 4, '2026-05-31 21:48:52', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(43, 4, '2026-06-01 05:45:37', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(44, 4, '2026-06-01 15:30:44', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(45, 14, '2026-06-01 15:38:40', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(46, 15, '2026-06-01 15:50:37', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1'),
(47, 16, '2026-06-01 16:35:03', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36'),
(48, 2, '2026-06-04 16:38:10', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(49, 17, '2026-06-04 17:52:40', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(50, 2, '2026-06-04 17:54:56', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0'),
(51, 2, '2026-06-17 14:48:54', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0'),
(52, 4, '2026-06-17 14:52:10', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `edad` int(11) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol` enum('usuario','admin') DEFAULT 'usuario',
  `estado` enum('activo','bloqueado') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password`, `edad`, `carrera`, `fecha_registro`, `rol`, `estado`) VALUES
(2, 'Ian Azael Hernandez Silva', 'ianazaelhernandezsilva@gmail.com', '$2y$10$rtcH/WaZpqQk40grA7n2F.03wH/oAryaECFw2Wn3UdVqEfL6BPaXW', 18, 'Ingenieria en Datos e Inteligencia Artificial', '2026-05-26 00:10:54', 'admin', 'activo'),
(4, 'Anareya Silva Mauricio', 'anyt4@live.com', '$2y$10$iJXa4sF5WGvkZqT5/P0/0.L9X5Km1rii1y9ZY.35aMdqOvaAo/R3W', 25, 'Ingenieria Datos e Inteligencia Artificial', '2026-05-26 05:52:54', 'usuario', 'activo'),
(5, 'Anel Eligio Tomás', 'aneleligiotomas@gmail.com', '$2y$10$YcAJT1GnlA812JOnkqqZXO2URi5u0ZMtMD.cg.0TrYcIkerh8o.ae', 17, 'Mecatronica', '2026-05-26 05:56:41', 'usuario', 'activo'),
(6, 'Jose', 'jdwosito@gmail.com', '$2y$10$jXhRGaxtrbt52pJrhH1TT.ylK2ffK20bRFc4IrnL70WY8VKnkTAXi', 18, 'Ingeniería en Datos e Inteligencia Artificial', '2026-05-26 06:04:08', 'usuario', 'activo'),
(7, 'Alan Noé Vega Nieto', '125053132@upq.edu.mx', '$2y$10$8Dvb7H9qUgSaJvTVkaI9SOWtxUQBmaaKm2DoifV2k.Aic3b7cZOgC', 18, 'Ingeniería en Datos e Inteligencia Artificial', '2026-05-26 14:46:40', 'usuario', 'activo'),
(9, 'Azul Dalí Hernández Alarcón', 'azuldali.ha27@gmail.com', '$2y$10$tTsELOR1vFLhTbGJC4ByS.l1JjjhAbc4msVjEL9qbDWfuvUP/fqMq', 18, 'IDIA', '2026-05-26 15:03:38', 'usuario', 'bloqueado'),
(10, 'Azul Dalí Hernández Alarcón', '125053549@upq.edu.mx', '$2y$10$dkyHql6Jlu/J6eEME.x20O9Rz3m9a2h7p1EAqCQbxcMZtrKRgISuS', 18, 'Ingeniería en Datos e Inteligencia Artificial', '2026-05-26 15:04:17', 'usuario', 'activo'),
(11, 'Anareya Silva Mauricio', 'anareyasilva@gmail.com', '$2y$10$hNfJRkOHSZTJNz0J55nbrOM4AqWwj5.Qvu/R8OsELewAymqPjhAiS', 19, 'Logística', '2026-05-26 15:06:48', 'usuario', 'activo'),
(14, 'Axel Isaac Sanchez Morales', 'axelisaac.samor@gmail.com', '$2y$10$rcQPjJvjLS8SFyAz1iSeDOBc3dym34mdkJDwXQVte3gO9xWZYd6Wi', 18, 'Ingeniería en Datos e Inteligencia Artificial', '2026-06-01 15:38:25', 'usuario', 'activo'),
(15, 'Paola Regina Morales Jaimes', 'paola.moralesj2005@gmail.com', '$2y$10$WUNyXyogBVHxhazlwdsg2u/sfCzHkHyJYxZlja7VBaRe0lD2.65CW', 20, 'Ingeniería en Datos e Inteligencia Artificial', '2026-06-01 15:50:30', 'usuario', 'activo'),
(16, 'Sarai Eligio Tomás', 'tomassara290@gmail.com', '$2y$10$vJeUYwKqVdB9CqwryaB6mOjjHbB2zOZp7HhBy4YYxFNNmdwgbUK/a', 18, 'Ingeniería en Datos e Inteligencia Artificial', '2026-06-01 16:34:51', 'usuario', 'activo'),
(17, 'Rene Francisco Santana Cruz', 'rsantanac2200@alumno.ipn.mx', '$2y$10$sgKK0G0aDyozC8oaa1QlzupftqU3GzPPjLI6ElJpQh6GJUKtyTj2K', 25, 'Ingeniería en Datos e Inteligencia Artificial', '2026-06-04 17:52:25', 'usuario', 'activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin_historial`
--
ALTER TABLE `admin_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `target_user_id` (`target_user_id`);

--
-- Indices de la tabla `dataset_uploads`
--
ALTER TABLE `dataset_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indices de la tabla `dataset_analysis_results`
--
ALTER TABLE `dataset_analysis_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataset_upload_id` (`dataset_upload_id`);

--
-- Indices de la tabla `dataset_estudiantes_clean`
--
ALTER TABLE `dataset_estudiantes_clean`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataset_upload_id` (`dataset_upload_id`);

--
-- Indices de la tabla `dataset_estudiantes_raw`
--
ALTER TABLE `dataset_estudiantes_raw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataset_upload_id` (`dataset_upload_id`);

--
-- Indices de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `recomendaciones`
--
ALTER TABLE `recomendaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resultado_id` (`resultado_id`);

--
-- Indices de la tabla `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `encuesta_id` (`encuesta_id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin_historial`
--
ALTER TABLE `admin_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `dataset_uploads`
--
ALTER TABLE `dataset_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `dataset_analysis_results`
--
ALTER TABLE `dataset_analysis_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `dataset_estudiantes_clean`
--
ALTER TABLE `dataset_estudiantes_clean`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `dataset_estudiantes_raw`
--
ALTER TABLE `dataset_estudiantes_raw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `recomendaciones`
--
ALTER TABLE `recomendaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT de la tabla `resultados`
--
ALTER TABLE `resultados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `dataset_uploads`
--
ALTER TABLE `dataset_uploads`
  ADD CONSTRAINT `dataset_uploads_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `dataset_analysis_results`
--
ALTER TABLE `dataset_analysis_results`
  ADD CONSTRAINT `dataset_analysis_results_ibfk_1` FOREIGN KEY (`dataset_upload_id`) REFERENCES `dataset_uploads` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `dataset_estudiantes_clean`
--
ALTER TABLE `dataset_estudiantes_clean`
  ADD CONSTRAINT `dataset_estudiantes_clean_ibfk_1` FOREIGN KEY (`dataset_upload_id`) REFERENCES `dataset_uploads` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `dataset_estudiantes_raw`
--
ALTER TABLE `dataset_estudiantes_raw`
  ADD CONSTRAINT `dataset_estudiantes_raw_ibfk_1` FOREIGN KEY (`dataset_upload_id`) REFERENCES `dataset_uploads` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `admin_historial`
--
ALTER TABLE `admin_historial`
  ADD CONSTRAINT `admin_historial_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_historial_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD CONSTRAINT `encuestas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recomendaciones`
--
ALTER TABLE `recomendaciones`
  ADD CONSTRAINT `recomendaciones_ibfk_1` FOREIGN KEY (`resultado_id`) REFERENCES `resultados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resultados`
--
ALTER TABLE `resultados`
  ADD CONSTRAINT `resultados_ibfk_1` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
