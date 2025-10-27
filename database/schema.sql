-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 27-10-2025 a las 09:41:11
-- Versión del servidor: 8.0.36
-- Versión de PHP: 8.3.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `streaming`
--

DELIMITER $$
--
-- Funciones
--
CREATE DEFINER=`streaming`@`localhost` FUNCTION `is_free_event` (`event_price` DECIMAL(10,2)) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    RETURN event_price = 0.00;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `active_sessions`
--

CREATE TABLE `active_sessions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `device_info` text,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_heartbeat` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analytics`
--

CREATE TABLE `analytics` (
  `id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `is_moderated` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `events`
--

CREATE TABLE `events` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `stream_key` varchar(100) NOT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','live','ended','cancelled') DEFAULT 'scheduled',
  `scheduled_start` datetime NOT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `max_viewers` int DEFAULT '0',
  `enable_recording` tinyint(1) DEFAULT '1',
  `enable_chat` tinyint(1) DEFAULT '1',
  `enable_dvr` tinyint(1) DEFAULT '0',
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Tabla de eventos (price=0.00 indica evento gratuito)';

--
-- Volcado de datos para la tabla `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `category`, `thumbnail_url`, `price`, `currency`, `stream_key`, `stream_url`, `status`, `scheduled_start`, `actual_start`, `actual_end`, `max_viewers`, `enable_recording`, `enable_chat`, `enable_dvr`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Partido de Práctica - Transmisión Gratuita', 'Evento de prueba completamente gratuito. Disfruta de la transmisión sin costo alguno. Ideal para probar nuestra plataforma.', 'Fútbol', NULL, 0.00, 'ARS', 'free_test_a84a7182e42af8cb2a3e005ac1063e53', NULL, 'scheduled', '2025-10-27 03:41:02', NULL, NULL, 0, 1, 1, 0, 1, '2025-10-27 01:41:02', '2025-10-27 01:41:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchases`
--

CREATE TABLE `purchases` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'Métodos: pending, completed, mercadopago, stripe, paypal, free',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `access_token` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `purchased_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Tabla de compras y accesos (incluye eventos gratuitos con payment_method=free)';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recordings`
--

CREATE TABLE `recordings` (
  `id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint UNSIGNED DEFAULT NULL,
  `duration` int UNSIGNED DEFAULT NULL,
  `format` varchar(20) DEFAULT 'mp4',
  `resolution` varchar(20) DEFAULT NULL,
  `status` enum('processing','ready','failed') DEFAULT 'processing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stream_settings`
--

CREATE TABLE `stream_settings` (
  `id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `video_bitrates` json DEFAULT NULL,
  `audio_bitrate` int DEFAULT '128',
  `keyframe_interval` int DEFAULT '2',
  `enable_watermark` tinyint(1) DEFAULT '1',
  `watermark_position` enum('top-left','top-right','bottom-left','bottom-right') DEFAULT 'top-right',
  `watermark_opacity` decimal(3,2) DEFAULT '0.70',
  `dvr_duration` int DEFAULT '7200',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` enum('user','admin','moderator','streamer') DEFAULT 'user',
  `status` enum('active','suspended','banned') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin@test.com', '$2y$10$.xb576Oc9fKmJGWVIVzDK.XlX589iRFWacS1vetTD2IuF3Zty8ADq', 'Administrador', NULL, 'admin', 'active', 1, NULL, NULL, NULL, '2025-10-26 21:11:11', '2025-10-27 03:30:32'),
(2, 'user@test.com', '$2y$10$.xb576Oc9fKmJGWVIVzDK.XlX589iRFWacS1vetTD2IuF3Zty8ADq', 'Usuario Test', NULL, 'user', 'active', 1, NULL, NULL, NULL, '2025-10-26 21:11:11', '2025-10-27 01:09:07'),
(3, 'streamer@test.com', '$2y$10$.xb576Oc9fKmJGWVIVzDK.XlX589iRFWacS1vetTD2IuF3Zty8ADq', 'Streamer Test', NULL, 'streamer', 'active', 1, NULL, NULL, NULL, '2025-10-27 02:38:36', '2025-10-27 00:36:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `session_token` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `logout_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para gestión de sesiones JWT y control de dispositivos';

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_purchase_stats`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_purchase_stats` (
`currency` varchar(3)
,`event_id` int unsigned
,`event_title` varchar(255)
,`free_access` decimal(23,0)
,`paid_purchases` decimal(23,0)
,`price` decimal(10,2)
,`total_purchases` bigint
,`total_revenue` decimal(32,2)
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD UNIQUE KEY `unique_user_event` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_heartbeat` (`last_heartbeat`);

--
-- Indices de la tabla `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_event_created` (`event_id`,`created_at`);

--
-- Indices de la tabla `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stream_key` (`stream_key`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_start`),
  ADD KEY `idx_stream_key` (`stream_key`);

--
-- Indices de la tabla `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD UNIQUE KEY `access_token` (`access_token`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_user_event` (`user_id`,`event_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_token` (`access_token`),
  ADD KEY `idx_payment_method` (`payment_method`);

--
-- Indices de la tabla `recordings`
--
ALTER TABLE `recordings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `stream_settings`
--
ALTER TABLE `stream_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indices de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`(255)),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_user_active_expires` (`user_id`,`is_active`,`expires_at`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `active_sessions`
--
ALTER TABLE `active_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `events`
--
ALTER TABLE `events`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recordings`
--
ALTER TABLE `recordings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stream_settings`
--
ALTER TABLE `stream_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_purchase_stats`
--
DROP TABLE IF EXISTS `v_purchase_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`streaming`@`localhost` SQL SECURITY DEFINER VIEW `v_purchase_stats`  AS SELECT `e`.`id` AS `event_id`, `e`.`title` AS `event_title`, `e`.`price` AS `price`, `e`.`currency` AS `currency`, count(`p`.`id`) AS `total_purchases`, sum((case when (`p`.`payment_method` = 'free') then 1 else 0 end)) AS `free_access`, sum((case when (`p`.`payment_method` <> 'free') then 1 else 0 end)) AS `paid_purchases`, sum((case when (`p`.`payment_method` <> 'free') then `p`.`amount` else 0 end)) AS `total_revenue` FROM (`events` `e` left join `purchases` `p` on(((`e`.`id` = `p`.`event_id`) and (`p`.`status` = 'completed')))) GROUP BY `e`.`id`, `e`.`title`, `e`.`price`, `e`.`currency` ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `active_sessions_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `analytics`
--
ALTER TABLE `analytics`
  ADD CONSTRAINT `analytics_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analytics_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recordings`
--
ALTER TABLE `recordings`
  ADD CONSTRAINT `recordings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `stream_settings`
--
ALTER TABLE `stream_settings`
  ADD CONSTRAINT `stream_settings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
