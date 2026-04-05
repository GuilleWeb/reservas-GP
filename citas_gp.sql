SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS=0;




--
-- Volcado de datos para la tabla `ajustes_globales`
--

INSERT INTO `ajustes_globales` (`id`, `clave`, `valor_json`, `updated_at`) VALUES
(1, 'system_name', '\"Sistema de reservas GP\"', '2026-03-30 06:28:24'),
(2, 'maintenance_mode', '1', '2026-04-04 20:00:43'),
(3, 'allow_login', '1', '2026-03-08 00:02:17'),
(4, 'allow_register', '1', '2026-03-08 00:02:17'),
(5, 'smtp_host', '\"smtp-reservas-gp.alwaysdata.net\"', '2026-04-04 19:56:34'),
(6, 'smtp_port', '587', '2026-03-02 07:20:10'),
(7, 'smtp_user', '\"reservas-gp@alwaysdata.net\"', '2026-04-04 19:57:13'),
(8, 'smtp_pass', '\"!Guilleweb042\"', '2026-04-04 20:24:21'),
(9, 'smtp_from_email', '\"reservas-gp@alwaysdata.net\"', '2026-04-04 19:56:34'),
(10, 'smtp_from_name', '\"Sistema de reservas GP\"', '2026-04-04 19:56:34'),
(15, 'system_logo_path', '\"\"', '2026-03-02 07:25:17'),
(16, 'system_favicon_path', '\"\"', '2026-03-02 07:25:17'),
(17, 'ui_primary_color', '\"\"', '2026-03-02 07:25:17'),
(18, 'ui_accent_color', '\"\"', '2026-03-02 07:25:17'),
(19, 'support_email', '\"\"', '2026-03-02 07:25:17'),
(20, 'support_phone', '\"\"', '2026-03-02 07:25:17'),
(21, 'public_footer_text', '\"\"', '2026-03-02 07:25:17'),
(22, 'analytics_ga4_id', '\"\"', '2026-03-02 07:25:17'),
(297, 'smtp_secure', '\"tls\"', '2026-04-04 19:56:34'),
(298, 'smtp_timeout', '12', '2026-04-04 19:56:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anuncios`
--

CREATE TABLE `anuncios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slot` varchar(30) NOT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `anuncios`
--

INSERT INTO `anuncios` (`id`, `slot`, `imagen_path`, `link_url`, `activo`, `orden`, `updated_at`) VALUES
(1, 'sidebar', 'uploads/anuncios/ad_1775344110_265cdd.jpg', 'http://localhost/clinica/vistas/superadmin/anuncios.php', 1, 0, '2026-04-04 23:12:50'),
(2, 'footer', 'uploads/anuncios/ad_1775344110_e5dd47.png', 'http://localhost/clinica/vistas/superadmin/anuncios.php', 1, 0, '2026-04-04 23:12:59'),
(3, 'panel', 'uploads/anuncios/ad_1775344110_f47314.png', 'http://localhost/clinica/vistas/superadmin/anuncios.php', 1, 0, '2026-04-04 23:13:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_eventos`
--

CREATE TABLE `auditoria_eventos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_rol` enum('superadmin','admin','gerente','empleado','cliente') DEFAULT NULL,
  `tipo` varchar(60) NOT NULL,
  `entidad` varchar(60) NOT NULL,
  `entidad_id` bigint(20) UNSIGNED DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `ip` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria_eventos`
--

INSERT INTO `auditoria_eventos` (`id`, `empresa_id`, `actor_usuario_id`, `actor_rol`, `tipo`, `entidad`, `entidad_id`, `descripcion`, `metadata_json`, `ip`, `created_at`) VALUES
(1, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-07 18:38:25'),
(2, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":0}}', '127.0.0.1', '2026-03-07 18:52:33'),
(3, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-07 18:58:48'),
(4, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-08 00:06:06'),
(5, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-08 00:06:13'),
(6, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-08 00:08:43'),
(7, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-08 00:08:49'),
(8, 1, 1, 'superadmin', 'update', 'usuario', 2, 'Usuario actualizado', '{\"before\":{\"id\":2,\"empresa_id\":1,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\"},\"after\":{\"empresa_id\":1,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"adminBarber\",\"email\":\"admin@barberiaeyg.com\",\"activo\":1}}', '127.0.0.1', '2026-03-08 00:08:51'),
(9, 1, 2, 'admin', 'create', 'servicios', 1, 'Nuevo servicio creado: Corte Clasico', NULL, '127.0.0.1', '2026-03-08 04:45:50'),
(10, 2, 1, 'superadmin', 'create', 'empresa', 2, 'Empresa creada', '{\"slug\":\"saloneyg\",\"nombre\":\"salon eyg\",\"plan_id\":1,\"admin_id\":4}', '127.0.0.1', '2026-03-08 18:42:14'),
(11, 3, 1, 'superadmin', 'create', 'empresa', 3, 'Empresa creada', '{\"slug\":\"prueba\",\"nombre\":\"prueba\",\"plan_id\":1,\"admin_id\":5}', '127.0.0.1', '2026-03-08 19:10:14'),
(12, NULL, 1, 'superadmin', 'update', 'usuario', 1, 'Usuario actualizado', '{\"before\":{\"id\":1,\"empresa_id\":null,\"rol\":\"superadmin\",\"nombre\":\"Super Admin\",\"email\":\"superadmin@citas.com\"},\"after\":{\"empresa_id\":null,\"sucursal_id\":null,\"rol\":\"superadmin\",\"nombre\":\"Super Admin\",\"email\":\"superadmin@citas.com\",\"activo\":1}}', '127.0.0.1', '2026-03-10 00:33:05'),
(13, 3, 1, 'superadmin', 'create', 'sucursales', 1, 'Nueva sucursal creada: central', NULL, '127.0.0.1', '2026-03-10 02:23:21'),
(14, 3, 1, 'superadmin', 'update', 'usuario', 6, 'Usuario actualizado', '{\"before\":{\"id\":6,\"empresa_id\":3,\"rol\":\"gerente\",\"nombre\":\"gerenteCentral\",\"email\":\"gerenteCentral@prueba.com\"},\"after\":{\"empresa_id\":3,\"sucursal_id\":null,\"rol\":\"gerente\",\"nombre\":\"gerenteCentral\",\"email\":\"gerentecentral@prueba.com\",\"activo\":1}}', '::1', '2026-03-12 02:14:58'),
(15, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-12 03:02:16'),
(16, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-12 03:02:37'),
(17, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-12 03:03:11'),
(18, NULL, 5, 'admin', 'update', 'usuarios', 5, 'Perfil actualizado', NULL, '::1', '2026-03-13 06:36:34'),
(19, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:10:32'),
(20, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:10:54'),
(21, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:11:06'),
(22, NULL, 5, 'admin', 'update', 'usuarios', 5, 'Perfil actualizado', NULL, '::1', '2026-03-13 07:11:39'),
(23, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:12:12'),
(24, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:12:30'),
(25, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:12:44'),
(26, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:13:07'),
(27, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:13:43'),
(28, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:14:56'),
(29, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:15:18'),
(30, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:20:55'),
(31, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:21:39'),
(32, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:21:56'),
(33, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-13 07:22:21'),
(34, NULL, 5, 'admin', 'update', 'usuarios', 5, 'Perfil actualizado', NULL, '::1', '2026-03-13 07:23:00'),
(35, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-14 21:58:39'),
(36, 3, 5, 'admin', 'create', 'servicios', 2, 'Nuevo servicio creado: corte de pelo calsico', NULL, '127.0.0.1', '2026-03-18 05:33:09'),
(37, 3, 1, 'superadmin', 'update', 'usuario', 5, 'Usuario actualizado', '{\"before\":{\"id\":5,\"empresa_id\":3,\"rol\":\"admin\",\"nombre\":\"admin-prueba\",\"email\":\"prueba@admin.com\"},\"after\":{\"empresa_id\":3,\"sucursal_id\":null,\"rol\":\"admin\",\"nombre\":\"admin-prueba\",\"email\":\"prueba@admin.com\",\"activo\":1}}', '::1', '2026-03-28 15:42:45'),
(38, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-28 17:03:50'),
(39, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-28 17:07:05'),
(40, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-28 17:08:34'),
(41, 3, 1, 'superadmin', 'create', 'sucursales', 3, 'Nueva sucursal creada: Lorem Ipsum', NULL, '::1', '2026-03-28 17:29:57'),
(42, 3, 1, 'superadmin', 'create', 'servicios', 3, 'Nuevo servicio creado: corte de barbacalsico', NULL, '::1', '2026-03-28 17:31:04'),
(43, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-28 17:43:57'),
(44, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barbacalsico', NULL, '::1', '2026-03-28 17:50:56'),
(45, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 03:08:31'),
(46, 3, 1, 'superadmin', 'update', 'servicios', 2, 'Servicio actualizado: corte de pelo calsico', NULL, '::1', '2026-03-29 04:01:12'),
(47, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 04:03:08'),
(48, 3, 1, 'superadmin', 'update', 'sucursales', 1, 'Sucursal actualizada: central', NULL, '::1', '2026-03-29 04:05:47'),
(49, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-29 04:35:34'),
(50, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-29 04:44:45'),
(51, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 04:51:43'),
(52, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 04:58:02'),
(53, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 04:58:12'),
(54, 3, 1, 'superadmin', 'update', 'servicios', 2, 'Servicio actualizado: corte de pelo calsico', NULL, '::1', '2026-03-29 05:30:56'),
(55, 3, 5, 'admin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 05:37:19'),
(56, 3, 5, 'admin', 'update', 'servicios', 2, 'Servicio actualizado: corte de pelo calsico', NULL, '::1', '2026-03-29 05:39:29'),
(57, 3, 1, 'superadmin', 'update', 'servicios', 3, 'Servicio actualizado: corte de barba clasico', NULL, '::1', '2026-03-29 05:53:24'),
(58, 3, 1, 'superadmin', 'update', 'servicios', 2, 'Servicio actualizado: corte de pelo calsico', NULL, '::1', '2026-03-29 06:18:58'),
(59, 3, 5, 'admin', 'update', 'sucursales', 1, 'Sucursal actualizada: central', NULL, '::1', '2026-03-30 06:07:51'),
(60, 3, 5, 'admin', 'update', 'sucursales', 3, 'Sucursal actualizada: Lorem Ipsum', NULL, '::1', '2026-03-30 06:08:48'),
(61, 3, 5, 'admin', 'update', 'sucursales', 1, 'Sucursal actualizada: central', NULL, '::1', '2026-03-30 06:09:18'),
(62, 3, 5, 'admin', 'update', 'sucursales', 1, 'Sucursal actualizada: central', NULL, '::1', '2026-03-30 06:09:29'),
(63, 3, 5, 'admin', 'update', 'servicios', 2, 'Servicio actualizado: corte de pelo calsico', NULL, '::1', '2026-03-30 06:30:56'),
(64, NULL, 5, 'admin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-03-30 06:39:44'),
(65, 3, 5, 'admin', 'update', 'sucursales', 1, 'Sucursal actualizada: central', NULL, '::1', '2026-04-04 18:09:08'),
(66, 3, 5, 'admin', 'create', 'citas', 4, 'Cita pública agendada', '{\"canal\":\"publico\",\"codigo_publico\":\"RES003-89DD-F2C0\",\"cliente_nombre\":\"Lorem Ipsum\",\"inicio\":\"2026-04-18 15:45:00\"}', '::1', '2026-04-04 19:35:01'),
(67, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-04-04 20:00:08'),
(68, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-04-04 20:02:35'),
(69, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-04-04 20:02:41'),
(70, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-04-04 20:04:48'),
(71, NULL, 1, 'superadmin', 'update', 'empresas', 3, 'Configuración de empresa actualizada', NULL, '::1', '2026-04-04 20:15:20'),
(72, 3, 1, 'superadmin', 'create', 'citas', 5, 'Cita pública agendada', '{\"canal\":\"publico\",\"codigo_publico\":\"RES003-0F32-D1E6\",\"cliente_nombre\":\"guillermo palma\",\"inicio\":\"2026-04-04 15:45:00\"}', '::1', '2026-04-04 20:17:56'),
(73, 3, 1, 'superadmin', 'update', 'citas', 5, 'Actualización de estado de cita', '{\"estado\":\"completada\"}', '::1', '2026-04-04 20:18:55'),
(74, 3, 1, 'superadmin', 'create', 'citas', 6, 'Cita pública agendada', '{\"canal\":\"publico\",\"codigo_publico\":\"RES003-C898-AC58\",\"cliente_nombre\":\"guille palma\",\"inicio\":\"2026-04-25 15:30:00\"}', '::1', '2026-04-04 20:26:44'),
(75, 3, 1, 'superadmin', 'update', 'citas', 6, 'Actualización de estado de cita', '{\"estado\":\"completada\"}', '::1', '2026-04-04 20:40:02'),
(76, 3, NULL, NULL, 'create', 'citas', 7, 'Cita pública agendada', '{\"canal\":\"publico\",\"codigo_publico\":\"RES003-F1F8-BBFE\",\"cliente_nombre\":\"guillermo palma\",\"inicio\":\"2026-04-04 15:30:00\"}', '::1', '2026-04-04 21:24:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `contenido` longtext NOT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `publicado` tinyint(1) NOT NULL DEFAULT 0,
  `publicado_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `empresa_id`, `titulo`, `slug`, `contenido`, `imagen_path`, `publicado`, `publicado_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'prueba de post', 'prueba123', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi nisi eros, tristique eget sapien eu, dictum lobortis lectus. Maecenas vulputate efficitur mauris eget ultrices. Integer dapibus nec enim at bibendum. Nulla finibus lorem ligula, eu luctus sapien dictum non. Fusce vitae neque et mi placerat dignissim. Integer eu volutpat augue. Donec quis eros quis mauris consequat semper eu vel augue. Vivamus elit quam, vehicula vel neque at, pharetra varius nulla. Morbi molestie finibus sem eu cursus. Vestibulum cursus consequat mollis.</p><p><img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABM4AAALZCAYAAABPim1MAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAP+lSURBVHhe7N13YBzVtQbwb2aLumTZsmW594YLGDCm904oAUKHQAIkpPFIT4D0SvrLCyH0kkYg9F5MMW644t4kuUmyetle5r4/Znc1e3dmm1b9+5HJ3XvOmbsrsWh3796ZUc469xwBIiIiIiIiIiIiiqO8fP1aoShKLCAAdPcAIQSYT8yf+eARhioiIiIiIiIiIhpqVOOkEKRJIwBxk0ZgPiFPRERERERERERDkxo9TpNtZi0REREREREREQ1tanQFFdvMWiIiIiIiIiIiGtpUIQREZCWVgH5OL/ZT94mIiIiIiIiIaGhTFUWBEllJpUA/pxf7qftERERERERERDS0qXGrqbilvRERERERERER0dCmwriaKhJkP3WfiIiIiIiIiIiGNhXRc3hFz93Fflp9IiIiIiIiIiIa2lQokfVTbDNriYiIiIiIiIhoSFMhIuunEtpIRUKceSIiIiIiIiIiGvqSrDiLVCTEmSciIiIiIiIioqFPFZEVVGwza4mIiIiIiIiIaGhTlcgKKraZtURERERERERENLRxxVmWLRERERERERERDW1ccZZlS0REREREREREQ5sqhED3pq+oYj91n4iIiIiIiIiIhjYViqKvolIU/YKR7KfXJyIiIiIiIiKiIU2FEBAA2GbYEhERERERERHRkKavOENkJRXb9FsiIiIiIiIiIhrS9BVn+sm72GbSEhERERERERHRkKYicv4ubhluREREREREREQ0pKmIrKJCdBUV++n1iYiIiIiIiIhoSItfcQb9HF7sp9EnIiIiIiIiIqIhTTU9fxfblC0REREREREREQ1tqgJ9BZUSWUnVl33FpkJVVf1RmOShKLA5bLDl2aDaI3UW46mqAkVVoCqRNtK3qu9pn4iIiIiIiIiIhjblxevXxpZPKQCMa6l6u18+vQgj5xSjeUsXXA0+iIAGzVCgAJh81miMmFqEjv0euOt88HWGEOgIIuTTIEIaNA2omFeCvBGOxPksAfg6gmja3BkbL9njyaR/zkPzDRkiIiIiIiIiIhpqlJduWNcvxx3a8lSc/KM5KKzMQ8gbxqEVrah+rREBVwhaQIMQ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q09EREREREREREOaqihKZBWVvpKK/TT7REREREREREQ0pEVWnEVXUrFNtyUiIiIiIiIioqEtsuIsupKKbbotERERERERERENbaoQAtwy34iIiIiIiIiIaGhTFShQFMPGflp9IiIiIiIiIiIa2lQB40oqQO/r5/Lq7vd9vvDM6Tj3T/Nw1AlO03yq/Xs7T0REREREREREQ5sK42oqBdD7+rm8uvt9my+/bDbOvLEcBY48zLhtLk6+KD+j/fsiT0REREREREREQ5uK2GoqfevfvhOTvnAEzriiCOG1+/DmF3Zg8zZgzDXzcMbVpSb1/dcnIiIiIiIiIqKhTY1cKhL93+Zj9tfn4tiTnOh8cy9e+VMT3IoX23+9Ax8vD2LERTNw4VcrUZSwX3+1REREREREREQ0lKmAvoqqf9tSLP75PCxaADT+cyveerzdkPdh//2bseJlD5zHTcKZ369CecL+/dESEREREREREdFQZr3iLLrJ8VznKytxwu9mYMa4IPb/bQc+fDlgun/9P3fgzcfaEJ45Hqf9fDImjbUYVx5fjucqT0REREREREREQ5oqIiuoElphEc9lfmoVTr13EiYUerDzT5uxaoUv6f7ut/bg5T81wjV6DI79/kzMHmMybpL9c5knIiIiIiIiIqKhTVUAQFGgRFZS9VW/+ITpOO/e8ahEB9b/cAc2rU9eH+uv34e3fnEYnYVlWPSjuThycYr6XuoTEREREREREdHQpgpAWlHV+/2iC2fizNtGoiCoAZ4A9jQkr0/oVwcQ0IBwfiFmf3UhTjjTmby+F/pERERERERERDS0qd0rqfqunXFsMcLbDuG9tQFEzxlmVmfdAgoA98q92FmtonzRCIu63muJiIiIiIiIiGhoU/517Zr+Wz5123xcNdOFf3+ztjtWORYnfrkCpU5DnduDPQ9UY/fhaGAsTntwIgo+/hiv/c1Q14cufWShHCIiIiIiIiIioiFEBaCvojJrIyurEuK9mb+kAhPGKXDXetBe60VbbQCYPAozPmWsizDbP9X4OcwTEREREREREdHQpQLQz99l1kbO5ZUQ7+U8tAAO31+Nlffvxar7O+HV9EfaXRdhsX+q8XOVJyIiIiIiIiKioUtfcaYo+jqq6Aqrfu4DUj7y/9310TLz/fumT0REREREREREQ5m+4kwIfR1VdIVVP/cBKR/5/+76aJn5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\"></p><p><br></p><p>Vivamus sollicitudin sem nec pellentesque consequat. Quisque neque quam, hendrerit sit amet ligula sit amet, elementum scelerisque dui. Integer vel vestibulum est. Etiam tincidunt tellus ac orci aliquam, vel tempus tortor ornare. In varius, turpis sed pretium aliquam, risus urna rutrum lectus, a gravida eros eros ut augue. Donec tincidunt ante et pharetra blandit. Vivamus augue orci, placerat at consectetur ac, mattis in sapien. Duis feugiat augue felis, non tristique dolor finibus at. Nullam dignissim nulla quis euismod placerat. Pellentesque laoreet vitae turpis nec aliquet. Proin id turpis libero. Quisque finibus varius semper. Sed non lacus felis. Mauris volutpat mi vel maximus tristique.</p>', 'assets/blog/1/post_1772946664.png', 1, '2026-03-08 06:11:04', '2026-03-08 05:11:04', '2026-03-10 03:17:06');
INSERT INTO `blog_posts` (`id`, `empresa_id`, `titulo`, `slug`, `contenido`, `imagen_path`, `publicado`, `publicado_at`, `created_at`, `updated_at`) VALUES
(2, 3, 'Lorem ipsum', 'sdfsdfsdfsd', '<p class=\"ql-align-justify\"><strong>Lorem ipsum </strong>dolor sit amet, consectetur adipiscing elit. Fusce elementum condimentum quam, quis sodales lectus venenatis maximus. Integer pellentesque mauris arcu, vitae tristique quam sodales ultricies. Pellentesque vehicula justo vitae commodo gravida. Suspendisse at massa eu magna molestie ultricies quis in eros. Phasellus consequat ante at lectus rutrum, sit amet semper ipsum varius. Nunc lacinia nunc ut metus dignissim tincidunt. Pellentesque sed mollis tortor. Nullam a ex a urna lacinia dapibus. Nunc dignissim augue nec nibh auctor rutrum.</p><p class=\"ql-align-justify\"><img src=\"data:image/jpeg;base64,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\"></p><p class=\"ql-align-justify\"><br></p><p class=\"ql-align-justify\">Maecenas nec augue a est eleifend efficitur. Interdum et malesuada fames ac ante ipsum primis in faucibus. Maecenas venenatis euismod sem, eu varius elit hendrerit at. Proin suscipit ligula nisi, eget sollicitudin ante blandit a. Proin imperdiet, ante sed malesuada euismod, justo justo accumsan lacus, et gravida elit purus ut arcu. Donec facilisis dui ac lacus fermentum consectetur. Suspendisse et ullamcorper justo.</p><p><br></p>', 'assets/blog/3/post_1774719383.jpg', 1, '2026-03-28 18:36:23', '2026-03-28 17:36:23', '2026-03-28 17:36:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `sucursal_id` bigint(20) UNSIGNED NOT NULL,
  `servicio_id` bigint(20) UNSIGNED NOT NULL,
  `empleado_usuario_id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_nombre` varchar(150) DEFAULT NULL,
  `cliente_email` varchar(150) DEFAULT NULL,
  `cliente_telefono` varchar(40) DEFAULT NULL,
  `inicio` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `estado` enum('pendiente','confirmada','rechazada','cancelada','completada','no_asistio') NOT NULL DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `creado_por_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `codigo_publico` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `empresa_id`, `sucursal_id`, `servicio_id`, `empleado_usuario_id`, `cliente_id`, `cliente_nombre`, `cliente_email`, `cliente_telefono`, `inicio`, `fin`, `estado`, `notas`, `creado_por_usuario_id`, `created_at`, `updated_at`, `codigo_publico`) VALUES
(1, 3, 1, 3, 9, 1, 'Lorem Ipsum', 'loremipsum@gmail.com', '50201545', '2026-03-30 12:10:28', '2026-03-29 06:10:28', 'completada', 'no lavar el cabello cona gua fria.', 1, '2026-03-29 04:11:58', '2026-03-30 05:19:17', NULL),
(2, 3, 3, 2, 9, NULL, 'colegio bonanza', 'carlos.pine042@gmail.com', '50000000', '2026-03-31 16:15:00', '2026-03-31 17:00:00', 'no_asistio', 'preuba mod\n\n------------------- Super Admin (id: 1) - 30/03/26 - 08:12:14 --------------------\npreuba mod\nporeuab de modificacion\n\n------------------- Super Admin (id: 1) - 30/03/26 - 08:12:52 --------------------\neliminacion\n\n------------------- Super Admin (id: 1) - 30/03/26 - 08:16:47 --------------------\npreuba 23\n\n------------------- admin-prueba (id: 5) - 30/03/26 - 08:17:20 --------------------\notr aprueba de admin', NULL, '2026-03-29 06:24:04', '2026-03-30 06:17:20', NULL),
(3, 3, 1, 2, 10, NULL, 'colegio bonanza', 'carlos.pine042@gmail.com', '50000000', '2026-04-25 15:45:00', '2026-04-25 16:30:00', 'pendiente', '', NULL, '2026-04-04 18:13:26', '2026-04-04 18:13:26', NULL),
(4, 3, 1, 2, 10, 1, 'Lorem Ipsum', 'juevesnegro@gmail.com', '50000000', '2026-04-18 15:45:00', '2026-04-18 16:30:00', 'pendiente', 'usar agua fria y no masage', NULL, '2026-04-04 19:35:00', '2026-04-04 19:35:00', 'RES003-89DD-F2C0'),
(5, 3, 1, 2, 10, NULL, 'guillermo palma', 'guille.palma2050@gmail.com', '51036244', '2026-04-04 15:45:00', '2026-04-04 16:30:00', 'completada', 'gracias', NULL, '2026-04-04 20:17:55', '2026-04-04 20:18:53', 'RES003-0F32-D1E6'),
(6, 3, 1, 3, 5, NULL, 'guille palma', 'guille.palma2050@gmail.com', '51036244', '2026-04-25 15:30:00', '2026-04-25 16:00:00', 'completada', '', NULL, '2026-04-04 20:26:43', '2026-04-04 20:39:59', 'RES003-C898-AC58'),
(7, 3, 1, 3, 10, 1, 'guillermo palma', 'guille.palma2050@gmail.com', '50000000', '2026-04-04 15:30:00', '2026-04-04 16:00:00', 'completada', '', NULL, '2026-04-04 21:24:09', '2026-04-04 23:15:35', 'RES003-F1F8-BBFE'),
(8, 3, 1, 3, 10, NULL, 'guillermo palma', 'guille.palma2050@gmail.com', NULL, '2026-04-11 16:30:00', '2026-04-11 17:00:00', 'pendiente', NULL, 11, '2026-04-04 22:17:38', '2026-04-04 22:17:38', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `email`, `telefono`, `fecha_nacimiento`, `notas`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Lorem Ipsum', 'juevesnegro@gmail.com', '50000000', '2026-03-25', NULL, 1, '2026-03-28 17:42:32', '2026-03-28 17:47:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente_empresas`
--

CREATE TABLE `cliente_empresas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `creado_por_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cliente_empresas`
--

INSERT INTO `cliente_empresas` (`id`, `cliente_id`, `empresa_id`, `creado_por_usuario_id`, `created_at`) VALUES
(1, 1, 3, 1, '2026-03-28 17:42:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_envios`
--

CREATE TABLE `email_envios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` varchar(40) NOT NULL,
  `destinatario_email` varchar(255) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `proveedor` varchar(20) NOT NULL DEFAULT 'mail',
  `estado` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_msg` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `email_envios`
--

INSERT INTO `email_envios` (`id`, `empresa_id`, `tipo`, `destinatario_email`, `asunto`, `proveedor`, `estado`, `error_msg`, `created_at`) VALUES
(1, NULL, 'smtp_test', 'guille.palma2050@gmail.com', 'Prueba SMTP - Sistema de reservas GP', 'smtp', 'sent', NULL, '2026-04-04 19:57:44'),
(2, 3, 'booking_confirmation', 'guille.palma2050@gmail.com', 'Confirmación de cita - prueba', 'smtp', 'failed', 'Password SMTP inválido: 535 Incorrect authentication data', '2026-04-04 20:17:59'),
(3, 3, 'review_invitation', 'guille.palma2050@gmail.com', '¿Cómo fue tu cita? - prueba', 'smtp', 'failed', 'Password SMTP inválido: 535 Incorrect authentication data', '2026-04-04 20:18:55'),
(4, 3, 'booking_confirmation', 'guille.palma2050@gmail.com', 'Confirmación de cita - prueba', 'smtp', 'sent', NULL, '2026-04-04 20:26:47'),
(5, 3, 'review_invitation', 'guille.palma2050@gmail.com', '¿Cómo fue tu cita? - prueba', 'smtp', 'sent', NULL, '2026-04-04 20:40:02'),
(6, 3, 'booking_confirmation', 'guille.palma2050@gmail.com', 'Confirmación de cita - prueba', 'smtp', 'sent', NULL, '2026-04-04 21:24:14'),
(7, 3, 'password_reset', 'guille.palma2050@gmail.com', 'Recuperación de contraseña', 'smtp', 'sent', NULL, '2026-04-04 21:34:45'),
(8, 3, 'review_invitation', 'guille.palma2050@gmail.com', '¿Cómo fue tu cita? - prueba', 'smtp', 'sent', NULL, '2026-04-04 23:15:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_bloqueos`
--

CREATE TABLE `empleado_bloqueos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empleado_usuario_id` bigint(20) UNSIGNED NOT NULL,
  `inicio` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `tipo` enum('descanso','comida','vacaciones','bloqueo') NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_horarios`
--

CREATE TABLE `empleado_horarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empleado_usuario_id` bigint(20) UNSIGNED NOT NULL,
  `weekday` tinyint(3) UNSIGNED NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_servicios`
--

CREATE TABLE `empleado_servicios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empleado_usuario_id` bigint(20) UNSIGNED NOT NULL,
  `servicio_id` bigint(20) UNSIGNED NOT NULL,
  `precio_override` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleado_servicios`
--

INSERT INTO `empleado_servicios` (`id`, `empleado_usuario_id`, `servicio_id`, `precio_override`, `activo`, `created_at`) VALUES
(20, 7, 3, NULL, 1, '2026-03-29 04:58:12'),
(21, 9, 3, NULL, 1, '2026-03-29 04:58:12'),
(22, 10, 3, NULL, 1, '2026-03-29 04:58:12'),
(23, 5, 3, NULL, 1, '2026-03-29 04:58:12'),
(24, 6, 3, NULL, 1, '2026-03-29 04:58:12'),
(25, 7, 2, NULL, 1, '2026-03-29 05:30:57'),
(26, 9, 2, NULL, 1, '2026-03-29 05:30:57'),
(27, 10, 2, NULL, 1, '2026-03-29 05:30:57'),
(28, 5, 2, NULL, 0, '2026-03-29 05:30:57'),
(29, 6, 2, NULL, 1, '2026-03-29 05:30:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `slug` varchar(80) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `portada_path` varchar(255) DEFAULT NULL,
  `colores_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`colores_json`)),
  `redes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`redes_json`)),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_json` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `plan_id`, `slug`, `nombre`, `slogan`, `descripcion`, `logo_path`, `portada_path`, `colores_json`, `redes_json`, `activo`, `created_at`, `updated_at`, `config_json`) VALUES
(1, 2, 'barberia', 'barberiaEYG v1', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-03-02 06:13:30', '2026-04-04 23:05:21', ''),
(2, 1, 'saloneyg', 'salon eyg', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-03-08 18:42:13', '2026-03-08 18:42:13', ''),
(3, 3, 'prueba', 'prueba', NULL, NULL, 'assets/Mw==/media/1775333720_69d1715837815.png', NULL, '{\"principal\":\"#5f0d96\"}', '[]', 1, '2026-03-08 19:10:13', '2026-04-04 22:56:25', '{\"email_contacto\":\"contacto@prueba.com\",\"telefono_contacto\":\"51036244\",\"moneda\":\"GTQ\",\"direccion_general\":\"15-01 Guatemala., 01010\",\"horario_general\":\"Lun-Vie, 8am-5pm\",\"gsc_meta_tag\":\"\"}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_home_config`
--

CREATE TABLE `empresa_home_config` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_json`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresa_home_config`
--

INSERT INTO `empresa_home_config` (`id`, `empresa_id`, `data_json`, `updated_at`) VALUES
(1, 3, '{\"hero_visible\":1,\"hero_tipo\":3,\"hero_titulo\":\"Bienvenid@\",\"hero_subtitulo\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce elementum condimentum quam, quis sodales lectus venenatis maximus. Integer pellentesque mauris arcu, vitae tristique quam sodales ultricies\",\"hero_btn_texto\":\"Agendar cita\",\"hero_btn_link\":\"\",\"hero_imagen\":\"https:\\/\\/guillepalma.alwaysdata.net\\/placeholder\\/api.php?size=250x250&text=logo\"}', '2026-03-29 05:56:03'),
(2, 2, '{}', '2026-04-04 23:10:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipo`
--

CREATE TABLE `equipo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `especialidad` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `visible_en_home` tinyint(1) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `home_page`
--

CREATE TABLE `home_page` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `modulo` varchar(60) NOT NULL,
  `titulo` varchar(190) NOT NULL,
  `valores_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_json`)),
  `tipo` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `limite` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `home_page`
--

INSERT INTO `home_page` (`id`, `empresa_id`, `modulo`, `titulo`, `valores_json`, `tipo`, `estado`, `orden`, `limite`, `updated_at`) VALUES
(1, 3, 'usuarios', 'Nuestro Personal de confianza', '[7,9,10]', 1, 1, 4, 5, '2026-03-29 05:49:17'),
(2, 3, 'sucursales', 'Nuestras Sucursales', '[1,3]', 1, 1, 3, 6, '2026-03-30 06:09:18'),
(3, 3, 'blog', 'Nuestro Blog', '[2]', 1, 1, 2, 3, '2026-03-29 05:48:59'),
(4, 3, 'resenas', 'Lo que opinan nuestros clientes', '[3,1,2]', 1, 1, 5, 6, '2026-04-04 23:29:40'),
(5, 3, 'servicios', 'Nuestros Servicios', '[2,3]', 1, 1, 1, 6, '2026-03-29 06:18:59'),
(6, 2, 'usuarios', 'Nuestro Personal de confianza', '[]', 1, 1, 2, 5, '2026-04-04 23:10:48'),
(7, 2, 'servicios', 'Nuestros Servicios', '[]', 1, 1, 4, 6, '2026-04-04 23:10:48'),
(8, 2, 'blog', 'Nuestro Blog', '[]', 1, 1, 1, 3, '2026-04-04 23:10:48'),
(9, 2, 'resenas', 'Lo que opinan nuestros clientes', '[]', 1, 1, 3, 6, '2026-04-04 23:10:48'),
(10, 2, 'sucursales', 'Nuestras Sucursales', '[]', 1, 1, 5, 6, '2026-04-04 23:10:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_contacto`
--

CREATE TABLE `mensajes_contacto` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `sucursal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `asunto` varchar(200) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('nuevo','leido','archivado') NOT NULL DEFAULT 'nuevo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_internos`
--

CREATE TABLE `mensajes_internos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `para_rol` enum('admin','gerente','empleado') DEFAULT NULL,
  `para_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `de_usuario_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `cuerpo` text NOT NULL,
  `estado` enum('enviado','leido','archivado') NOT NULL DEFAULT 'enviado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mensajes_internos`
--

INSERT INTO `mensajes_internos` (`id`, `empresa_id`, `para_rol`, `para_usuario_id`, `de_usuario_id`, `titulo`, `cuerpo`, `estado`, `created_at`) VALUES
(1, 1, NULL, 2, 1, 'preuba', 'preuab de envio de emnsaje para todos los admins', 'enviado', '2026-03-07 18:37:43'),
(2, 3, NULL, 5, 1, 'Lorem ipsum', 'dxfcxcvcxv', 'archivado', '2026-03-14 22:32:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_destino` varchar(30) DEFAULT NULL,
  `tipo` varchar(60) NOT NULL DEFAULT 'general',
  `titulo` varchar(190) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `referencia_tipo` varchar(60) DEFAULT NULL,
  `referencia_id` bigint(20) UNSIGNED DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `empresa_id`, `usuario_id`, `rol_destino`, `tipo`, `titulo`, `descripcion`, `url`, `referencia_tipo`, `referencia_id`, `leida`, `created_at`) VALUES
(1, 3, NULL, 'admin', 'cita_nueva', 'Nueva cita agendada', 'colegio bonanza · 25/04/2026 15:45', '/clinica/vistas/admin/admin-citas.php?id_e=3', 'cita', 3, 0, '2026-04-04 18:13:26'),
(2, 3, NULL, 'gerente', 'cita_nueva', 'Nueva cita agendada', 'colegio bonanza · 25/04/2026 15:45', '/clinica/vistas/sucursal/admin-citas.php?id_e=3', 'cita', 3, 0, '2026-04-04 18:13:26'),
(3, 3, 10, 'empleado', 'cita_nueva', 'Nueva cita asignada', 'colegio bonanza · 25/04/2026 15:45', '/clinica/vistas/empleado/citas.php?id_e=3', 'cita', 3, 0, '2026-04-04 18:13:26'),
(4, 3, NULL, 'admin', 'cita_nueva', 'Nueva cita agendada', 'Lorem Ipsum · 18/04/2026 15:45', '/clinica/vistas/admin/admin-citas.php?id_e=3', 'cita', 4, 0, '2026-04-04 19:35:01'),
(5, 3, NULL, 'gerente', 'cita_nueva', 'Nueva cita agendada', 'Lorem Ipsum · 18/04/2026 15:45', '/clinica/vistas/sucursal/admin-citas.php?id_e=3', 'cita', 4, 0, '2026-04-04 19:35:01'),
(6, 3, 10, 'empleado', 'cita_nueva', 'Nueva cita asignada', 'Lorem Ipsum · 18/04/2026 15:45', '/clinica/vistas/empleado/citas.php?id_e=3', 'cita', 4, 0, '2026-04-04 19:35:01'),
(7, 3, NULL, 'admin', 'cita_nueva', 'Nueva cita agendada', 'guillermo palma · 04/04/2026 15:45', '/clinica/vistas/admin/admin-citas.php?id_e=3', 'cita', 5, 0, '2026-04-04 20:17:56'),
(8, 3, NULL, 'gerente', 'cita_nueva', 'Nueva cita agendada', 'guillermo palma · 04/04/2026 15:45', '/clinica/vistas/sucursal/admin-citas.php?id_e=3', 'cita', 5, 0, '2026-04-04 20:17:56'),
(9, 3, 10, 'empleado', 'cita_nueva', 'Nueva cita asignada', 'guillermo palma · 04/04/2026 15:45', '/clinica/vistas/empleado/citas.php?id_e=3', 'cita', 5, 0, '2026-04-04 20:17:56'),
(10, 3, NULL, 'admin', 'cita_nueva', 'Nueva cita agendada', 'guille palma · 25/04/2026 15:30', '/clinica/vistas/admin/admin-citas.php?id_e=3', 'cita', 6, 0, '2026-04-04 20:26:44'),
(11, 3, NULL, 'gerente', 'cita_nueva', 'Nueva cita agendada', 'guille palma · 25/04/2026 15:30', '/clinica/vistas/sucursal/admin-citas.php?id_e=3', 'cita', 6, 0, '2026-04-04 20:26:44'),
(12, 3, 5, 'empleado', 'cita_nueva', 'Nueva cita asignada', 'guille palma · 25/04/2026 15:30', '/clinica/vistas/empleado/citas.php?id_e=3', 'cita', 6, 0, '2026-04-04 20:26:44'),
(13, 3, NULL, 'admin', 'cita_nueva', 'Nueva cita agendada', 'guillermo palma · 04/04/2026 15:30', '/clinica/vistas/admin/admin-citas.php?id_e=3', 'cita', 7, 0, '2026-04-04 21:24:10'),
(14, 3, NULL, 'gerente', 'cita_nueva', 'Nueva cita agendada', 'guillermo palma · 04/04/2026 15:30', '/clinica/vistas/sucursal/admin-citas.php?id_e=3', 'cita', 7, 0, '2026-04-04 21:24:10'),
(15, 3, 10, 'empleado', 'cita_nueva', 'Nueva cita asignada', 'guillermo palma · 04/04/2026 15:30', '/clinica/vistas/empleado/citas.php?id_e=3', 'cita', 7, 0, '2026-04-04 21:24:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 11, 'fd6d1bf2003ebe9907aaec27023267740d926701172434850cc7a813c8d8603a', '2026-04-05 00:04:42', '2026-04-04 15:35:56', '2026-04-04 21:34:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `max_sucursales` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `max_empleados` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `max_servicios` int(10) UNSIGNED NOT NULL DEFAULT 50,
  `max_clientes` int(10) UNSIGNED NOT NULL DEFAULT 10000,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `precio_mensual` int(11) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `modulos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`modulos_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `nombre`, `descripcion`, `max_sucursales`, `max_empleados`, `max_servicios`, `max_clientes`, `activo`, `created_at`, `updated_at`, `precio_mensual`, `precio`, `modulos_json`) VALUES
(1, 'basico', 'acceso a funcniones basicas del ssitema, ideal para principiantes o negocios pequenos.', 1, 10, 10, 100, 1, '2026-03-04 02:47:09', '2026-04-04 22:30:30', 0, 0.00, '[\"dashboard\",\"citas\",\"servicios\",\"sucursales\",\"usuarios\",\"clientes\",\"mensajes\",\"resenas\",\"home_page\"]'),
(2, 'Premiun', 'Ideal para empresas medianas o negocios en expoancion, acceso a la mayoria de funciones basicas y avanzadas del sistema.', 5, 50, 50, 500, 1, '2026-03-04 02:48:24', '2026-03-30 06:34:01', 100, 149.99, '[\"dashboard\",\"citas\",\"servicios\",\"sucursales\",\"usuarios\",\"clientes\",\"mensajes\",\"resenas\",\"home_page\",\"blog\"]'),
(3, 'Enterprise', 'acceso a todas las funciones del sistema incluyendo soporte prioritario y copias de seguridad.', 500, 500, 500, 5000, 1, '2026-03-04 02:49:21', '2026-03-30 06:33:45', 190, 199.99, '[\"dashboard\",\"citas\",\"servicios\",\"sucursales\",\"usuarios\",\"clientes\",\"mensajes\",\"resenas\",\"home_page\",\"blog\"]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `sucursal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `autor_nombre` varchar(150) DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `visible_en_home` tinyint(1) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `resenas`
--

INSERT INTO `resenas` (`id`, `empresa_id`, `sucursal_id`, `cliente_id`, `autor_nombre`, `rating`, `titulo`, `comentario`, `visible_en_home`, `activo`, `created_at`) VALUES
(1, 3, NULL, NULL, 'Lorem Ipsum', 3, NULL, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce elementum condimentum quam, quis sodales lectus venenatis maximus. Integer pellentesque mauris arcu, vitae tristique quam sodales ultricies. Pellentesque vehicula justo vitae commodo gravida. Suspendisse at massa eu magna molestie ultricies quis in eros. Phasellus consequat ante at lectus rutrum, sit amet semper ipsum varius. Nunc lacinia nunc ut metus dignissim tincidunt. Pellentesque sed mollis tortor. Nullam a ex a urna lacinia dapibus. Nunc dignissim augue nec nibh auctor rutrum.', 1, 1, '2026-03-28 17:39:29'),
(2, 3, NULL, NULL, 'Lorem Ipsum', 5, NULL, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce elementum condimentum quam,', 1, 1, '2026-03-28 17:40:23'),
(3, 3, 1, 1, 'guillermo palma', 5, 'Reseña de cita', 'buen servicio 123', 1, 1, '2026-04-04 23:29:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resena_contexto`
--

CREATE TABLE `resena_contexto` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resena_id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `cita_id` bigint(20) UNSIGNED DEFAULT NULL,
  `servicio_id` bigint(20) UNSIGNED DEFAULT NULL,
  `empleado_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `resena_contexto`
--

INSERT INTO `resena_contexto` (`id`, `resena_id`, `empresa_id`, `cita_id`, `servicio_id`, `empleado_usuario_id`, `created_at`) VALUES
(1, 3, 3, 7, 3, 10, '2026-04-04 23:29:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resena_invitaciones`
--

CREATE TABLE `resena_invitaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `cita_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `estado` enum('pendiente','usada','expirada') NOT NULL DEFAULT 'pendiente',
  `sent_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `resena_invitaciones`
--

INSERT INTO `resena_invitaciones` (`id`, `empresa_id`, `cita_id`, `token_hash`, `estado`, `sent_at`, `used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 3, 1, '843e37a316c71107f787d17fd7101187adb4084e280d8b7e61a1343a67217ba6', 'pendiente', '2026-04-04 13:28:22', NULL, '2026-05-04 21:28:22', '2026-04-04 19:28:22', '2026-04-04 19:28:22'),
(2, 3, 5, '736c9a87da653027ebb9dfea74f9551d11e164f5884dabeb1929311aae95d5f6', 'pendiente', '2026-04-04 14:18:53', NULL, '2026-05-04 22:18:53', '2026-04-04 20:18:53', '2026-04-04 20:18:53'),
(3, 3, 6, 'b691d61c18fffd898bf9ede4b5af2479cc25ded56d5f0282bcdaf5995dacc7d8', 'pendiente', '2026-04-04 14:39:59', NULL, '2026-05-04 22:39:59', '2026-04-04 20:39:59', '2026-04-04 20:39:59'),
(4, 3, 7, '4fdbcbf6a5cfdea43bc0cff190372700c4275102151153f75a954788611921bc', 'usada', '2026-04-04 17:15:36', '2026-04-04 17:29:14', '2026-05-05 01:15:36', '2026-04-04 23:15:36', '2026-04-04 23:29:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_minutos` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `precio_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `empresa_id`, `nombre`, `descripcion`, `duracion_minutos`, `precio_base`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Corte Clasico', 'corte de cabello clasico, incluye produto par el cabello', 30, 100.00, 1, '2026-03-08 04:45:50', '2026-03-08 04:45:50'),
(2, 3, 'corte de pelo calsico', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla consectetur nec tellus finibus consectetur. Maecenas tempor ex a enim dictum lobortis.', 45, 150.00, 1, '2026-03-18 05:33:09', '2026-03-18 05:33:09'),
(3, 3, 'corte de barba clasico', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla consectetur nec tellus finibus consectetur. Maecenas tempor ex a enim dictum lobortis.', 30, 100.00, 1, '2026-03-28 17:31:04', '2026-03-29 03:08:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_sucursales`
--

CREATE TABLE `servicio_sucursales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `servicio_id` bigint(20) UNSIGNED NOT NULL,
  `sucursal_id` bigint(20) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicio_sucursales`
--

INSERT INTO `servicio_sucursales` (`id`, `servicio_id`, `sucursal_id`, `activo`, `created_at`) VALUES
(13, 3, 1, 1, '2026-03-29 04:58:12'),
(14, 3, 3, 1, '2026-03-29 04:58:12'),
(15, 2, 1, 1, '2026-03-29 05:30:57'),
(16, 2, 3, 1, '2026-03-29 05:30:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `sucursal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` varchar(60) NOT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`)),
  `estado` enum('pendiente','aprobada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `solicitado_por_usuario_id` bigint(20) UNSIGNED NOT NULL,
  `resuelto_por_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resuelto_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `colores_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`colores_json`)),
  `horarios_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`horarios_json`)),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `empresa_id`, `slug`, `nombre`, `direccion`, `telefono`, `email`, `foto_path`, `colores_json`, `horarios_json`, `activo`, `created_at`, `updated_at`) VALUES
(1, 3, 'central', 'central', 'ciudad', '12345678', '', 'https://guillepalma.alwaysdata.net/placeholder/api.php?size=250x250&text=central', NULL, '{\"lunes\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"martes\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"miercoles\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"jueves\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"viernes\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"17:00\"},\"sabado\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"17:00\"},\"domingo\":{\"activo\":false,\"inicio\":\"09:00\",\"fin\":\"17:00\"}}', 1, '2026-03-10 02:23:21', '2026-04-04 18:09:08'),
(3, 3, 'lorem-ipsum', 'Lorem Ipsum', 'COLONIA LA GRAN VILLA PRINCIPAL', '50000000', '', NULL, NULL, '{\"lunes\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"16:00\"},\"martes\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"16:00\"},\"miercoles\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"16:00\"},\"jueves\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"16:00\"},\"viernes\":{\"activo\":true,\"inicio\":\"09:00\",\"fin\":\"16:00\"},\"sabado\":{\"activo\":false,\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"domingo\":{\"activo\":false,\"inicio\":\"09:00\",\"fin\":\"18:00\"}}', 1, '2026-03-28 17:29:57', '2026-03-30 06:08:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

CREATE TABLE `suscripciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('activa','vencida','cancelada','pendiente') NOT NULL DEFAULT 'activa',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `ultimo_pago_monto` decimal(10,2) DEFAULT NULL,
  `ultimo_pago_fecha` date DEFAULT NULL,
  `detalle_pago_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalle_pago_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `plazo` enum('mensual','anual') NOT NULL DEFAULT 'mensual',
  `adjunto_pago_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `empresa_id`, `plan_id`, `estado`, `fecha_inicio`, `fecha_fin`, `ultimo_pago_monto`, `ultimo_pago_fecha`, `detalle_pago_json`, `created_at`, `updated_at`, `plazo`, `adjunto_pago_path`) VALUES
(2, 3, 3, 'activa', '2026-04-04', '2026-05-04', 199.99, '2026-04-04', NULL, '2026-04-04 19:49:11', '2026-04-04 23:15:22', 'mensual', NULL),
(3, 1, 2, 'activa', '2026-04-04', '2027-04-04', 149.99, '2026-04-04', NULL, '2026-04-04 20:16:21', '2026-04-04 23:47:42', 'anual', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones_historial`
--

CREATE TABLE `suscripciones_historial` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `suscripcion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `estado` varchar(20) NOT NULL,
  `plazo` varchar(20) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `ultimo_pago_monto` decimal(10,2) DEFAULT NULL,
  `ultimo_pago_fecha` date DEFAULT NULL,
  `detalle_pago_json` text DEFAULT NULL,
  `adjunto_pago_path` varchar(255) DEFAULT NULL,
  `accion` varchar(30) NOT NULL DEFAULT 'snapshot',
  `accion_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `suscripciones_historial`
--

INSERT INTO `suscripciones_historial` (`id`, `suscripcion_id`, `empresa_id`, `plan_id`, `estado`, `plazo`, `fecha_inicio`, `fecha_fin`, `ultimo_pago_monto`, `ultimo_pago_fecha`, `detalle_pago_json`, `adjunto_pago_path`, `accion`, `accion_usuario_id`, `created_at`) VALUES
(1, 2, 3, 2, 'vencida', 'anual', '2026-04-04', '2027-04-04', 149.99, '2026-04-04', NULL, NULL, 'replace', 1, '2026-04-04 21:27:04'),
(2, 2, 3, 2, 'activa', 'anual', '2026-04-04', '2027-04-04', 149.99, '2026-04-04', NULL, NULL, 'update', 1, '2026-04-04 21:27:05'),
(3, 3, 1, 2, 'activa', 'mensual', '2026-04-04', '2026-05-04', 149.99, '2026-04-04', NULL, NULL, 'replace', 1, '2026-04-04 21:33:33'),
(4, 3, 1, 2, 'activa', 'mensual', '2026-04-01', '2026-05-01', 149.99, '2026-04-01', NULL, NULL, 'update', 1, '2026-04-04 21:33:33'),
(5, 2, 3, 2, 'activa', 'anual', '2026-04-04', '2027-04-04', 149.99, '2026-04-04', NULL, NULL, 'replace', 1, '2026-04-04 21:51:41'),
(6, 2, 3, 2, 'activa', 'anual', '2026-04-01', '2027-04-01', 149.99, '2026-04-01', NULL, NULL, 'update', 1, '2026-04-04 21:51:41'),
(7, 2, 3, 2, 'activa', 'anual', '2026-04-01', '2027-04-01', 149.99, '2026-04-01', NULL, NULL, 'replace', 1, '2026-04-04 21:52:10'),
(8, 2, 3, 2, 'activa', 'anual', '2026-04-01', '2027-04-01', 149.99, '2026-04-01', NULL, NULL, 'update', 1, '2026-04-04 21:52:10'),
(9, 3, 1, 2, 'activa', 'mensual', '2026-04-01', '2026-05-01', 149.99, '2026-04-01', NULL, NULL, 'replace', 1, '2026-04-04 21:52:34'),
(10, 3, 1, 2, 'activa', 'mensual', '2026-04-01', '2026-05-01', 149.99, '2026-04-01', NULL, NULL, 'update', 1, '2026-04-04 21:52:34'),
(11, 3, 1, 2, 'activa', 'mensual', '2026-04-01', '2026-05-01', 149.99, '2026-04-01', NULL, NULL, 'replace', 1, '2026-04-04 21:52:46'),
(12, 3, 1, 1, 'activa', 'mensual', '2026-03-03', '2026-04-03', 0.00, '2026-03-03', NULL, NULL, 'update', 1, '2026-04-04 21:52:46'),
(13, 2, 3, 2, 'activa', 'anual', '2026-04-01', '2027-04-01', 149.99, '2026-04-01', NULL, NULL, 'replace', 1, '2026-04-04 21:53:05'),
(14, 2, 3, 2, 'activa', 'anual', '2025-04-03', '2026-04-03', 149.99, '2025-04-03', NULL, NULL, 'update', 1, '2026-04-04 21:53:05'),
(15, 2, 3, 2, 'vencida', 'anual', '2025-04-03', '2026-04-03', 149.99, '2025-04-03', NULL, NULL, 'sync_empresa_plan', 1, '2026-04-04 22:18:37'),
(16, 2, 3, 1, 'activa', 'anual', '2025-04-03', '2026-04-03', 149.99, '2025-04-03', NULL, NULL, 'sync_empresa_plan_after', 1, '2026-04-04 22:18:38'),
(17, 2, 3, 1, 'vencida', 'anual', '2025-04-03', '2026-04-03', 149.99, '2025-04-03', NULL, NULL, 'replace', 1, '2026-04-04 22:55:40'),
(18, 2, 3, 1, 'vencida', 'mensual', '2026-04-04', '2026-05-04', 0.00, '2026-04-04', NULL, NULL, 'update', 1, '2026-04-04 22:55:41'),
(19, 2, 3, 1, 'vencida', 'mensual', '2026-04-04', '2026-05-04', 0.00, '2026-04-04', NULL, NULL, 'replace', 1, '2026-04-04 22:56:24'),
(20, 2, 3, 3, 'vencida', 'mensual', '2026-04-04', '2026-05-04', 199.99, '2026-04-04', NULL, NULL, 'update', 1, '2026-04-04 22:56:25'),
(21, 3, 1, 1, 'vencida', 'mensual', '2026-03-03', '2026-04-03', 0.00, '2026-03-03', NULL, NULL, 'replace', 1, '2026-04-04 23:05:21'),
(22, 3, 1, 2, 'vencida', 'anual', '2026-04-04', '2027-04-04', 149.99, '2026-04-04', NULL, NULL, 'update', 1, '2026-04-04 23:05:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sucursal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol` enum('superadmin','admin','gerente','empleado','cliente') NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_token` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `sucursal_id`, `rol`, `nombre`, `email`, `telefono`, `password_hash`, `foto_path`, `activo`, `ultimo_login_at`, `created_at`, `updated_at`, `session_token`) VALUES
(1, NULL, NULL, 'superadmin', 'Super Admin', 'superadmin@citas.com', NULL, '$2y$10$m8Ew/BNy1js.MyzyIAaxkOQ/M4xGXd7Af.SeGH0njVMeLFjDJ8Fj2', NULL, 1, NULL, '2026-03-02 05:56:22', '2026-03-28 15:41:08', 'k7al3r2mbrrq935ik6jl8e869g'),
(2, 1, NULL, 'admin', 'adminBarber', 'admin@barberiaeyg.com', '510217800123', '$2a$12$7dz9Q1cCyamq6hHvgRhAgOoumPLiincch7AesusRYZ9wLpWFRiJdG', NULL, 1, NULL, '2026-03-02 06:13:30', '2026-03-08 00:06:13', '6rgrrrsjaou3kqg52vs5l3kf8f'),
(3, 1, NULL, 'empleado', 'jkkose reoamiasd', 'adsasd@gmail.com', '51488754', '$2y$10$eC1P3yjqCSzK.VNxfkBlOeMluzr/Qxbzi104rXkg6dlzVZ9ISER6m', NULL, 1, NULL, '2026-03-08 04:57:51', '2026-03-08 04:57:51', NULL),
(4, 2, NULL, 'admin', 'adminsaloneyg', 'admin@saloneyg.com', NULL, '$2y$10$.GXDM2Tkm3m1ip5xzXeMvOMxLR44MewviQ/Zv5qu6cqSIxGmlcPG6', NULL, 1, NULL, '2026-03-08 18:42:14', '2026-03-08 18:42:14', NULL),
(5, 3, NULL, 'admin', 'admin-prueba', 'prueba@admin.com', '50000000', '$2y$10$.Dn5Q8fFkQbvk7BdavzCkeyU5pBJD9ABd5jRzrYJX.UrJe4qCdVHy', 'assets/Mw==/media/user_5_1773385899.png', 1, NULL, '2026-03-08 19:10:13', '2026-04-04 18:07:36', 'k7al3r2mbrrq935ik6jl8e869g'),
(6, 3, 1, 'gerente', 'gerenteCentral', 'gerentecentral@prueba.com', '52412154', '$2y$10$l84oC95c3OC4frcGUSCPgOufKVAhJuTVZYb1DAIcjxRbymdW0ReaK', NULL, 1, NULL, '2026-03-10 02:24:25', '2026-03-29 05:52:27', 'pvf9ofcupkh4h77c585rs4nreg'),
(7, 3, 3, 'empleado', 'Lorem Ipsum', 'loremipsum1@mail.com', '00000000', '$2y$10$euzjOJ2EfUvf78gBOgoH0.aNJvIesnqzpfg9JPmZM6vTRzYujHweS', NULL, 1, NULL, '2026-03-28 17:09:28', '2026-03-29 05:52:18', NULL),
(9, 3, 3, 'empleado', 'lorem ipsum 2', 'loremipsum2@mail.com', '50000000', '$2y$10$SNMcDJDvHvttXcztZAEwiOMXXbzLL40wbM.EHpZnF8VvYQq89gMe.', NULL, 1, NULL, '2026-03-28 17:09:50', '2026-03-29 06:01:18', NULL),
(10, 3, 1, 'empleado', 'Lorem Ipsum 3', 'loremipsum3@mail.com', '50000000', '$2y$10$2oMmP/0cdnv7wso8fc1YX.ag5r7cFHXH4MwfqQUZuITA3dUq7qQfm', NULL, 1, NULL, '2026-03-28 17:10:05', '2026-03-29 05:52:27', NULL),
(11, 3, NULL, 'cliente', 'guillermo palma', 'guille.palma2050@gmail.com', '50000000', '$2y$10$2vvfXevubPLSjYCbTMW.geUapxNVqJH31BJMenvqpNX9Zp5s90PMW', NULL, 1, NULL, '2026-04-04 21:24:09', '2026-04-04 21:36:37', 'k7al3r2mbrrq935ik6jl8e869g');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ajustes_globales`
--
ALTER TABLE `ajustes_globales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ajustes_globales_clave` (`clave`);

--
-- Indices de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_anuncios_slot` (`slot`);

--
-- Indices de la tabla `auditoria_eventos`
--
ALTER TABLE `auditoria_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_empresa` (`empresa_id`),
  ADD KEY `idx_auditoria_actor` (`actor_usuario_id`),
  ADD KEY `idx_auditoria_tipo` (`tipo`),
  ADD KEY `idx_auditoria_created` (`created_at`);

--
-- Indices de la tabla `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_empresa_slug` (`empresa_id`,`slug`),
  ADD KEY `idx_blog_empresa` (`empresa_id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_citas_empresa` (`empresa_id`),
  ADD KEY `idx_citas_sucursal` (`sucursal_id`),
  ADD KEY `idx_citas_empleado` (`empleado_usuario_id`),
  ADD KEY `idx_citas_servicio` (`servicio_id`),
  ADD KEY `idx_citas_inicio` (`inicio`),
  ADD KEY `fk_citas_cliente` (`cliente_id`),
  ADD KEY `fk_citas_creado_por` (`creado_por_usuario_id`),
  ADD KEY `idx_citas_codigo_publico` (`codigo_publico`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clientes_email` (`email`),
  ADD KEY `idx_clientes_telefono` (`telefono`);

--
-- Indices de la tabla `cliente_empresas`
--
ALTER TABLE `cliente_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cliente_empresas` (`cliente_id`,`empresa_id`),
  ADD KEY `idx_cliente_empresas_empresa` (`empresa_id`),
  ADD KEY `fk_cliente_empresas_creado_por` (`creado_por_usuario_id`);

--
-- Indices de la tabla `email_envios`
--
ALTER TABLE `email_envios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_empresa_fecha` (`empresa_id`,`created_at`),
  ADD KEY `idx_email_estado_fecha` (`estado`,`created_at`);

--
-- Indices de la tabla `empleado_bloqueos`
--
ALTER TABLE `empleado_bloqueos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empleado_bloqueos_empleado` (`empleado_usuario_id`),
  ADD KEY `idx_empleado_bloqueos_rango` (`inicio`,`fin`);

--
-- Indices de la tabla `empleado_horarios`
--
ALTER TABLE `empleado_horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empleado_horarios_empleado` (`empleado_usuario_id`),
  ADD KEY `idx_empleado_horarios_weekday` (`weekday`);

--
-- Indices de la tabla `empleado_servicios`
--
ALTER TABLE `empleado_servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empleado_servicio` (`empleado_usuario_id`,`servicio_id`),
  ADD KEY `idx_empleado_servicios_servicio` (`servicio_id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresas_slug` (`slug`),
  ADD KEY `idx_empresas_plan` (`plan_id`);

--
-- Indices de la tabla `empresa_home_config`
--
ALTER TABLE `empresa_home_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresa_home_config_empresa` (`empresa_id`);

--
-- Indices de la tabla `equipo`
--
ALTER TABLE `equipo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipo_empresa` (`empresa_id`);

--
-- Indices de la tabla `home_page`
--
ALTER TABLE `home_page`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_home_page_empresa_modulo` (`empresa_id`,`modulo`),
  ADD KEY `idx_home_page_empresa_orden` (`empresa_id`,`orden`);

--
-- Indices de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mensajes_contacto_empresa` (`empresa_id`),
  ADD KEY `idx_mensajes_contacto_estado` (`estado`),
  ADD KEY `fk_mensajes_contacto_sucursal` (`sucursal_id`),
  ADD KEY `fk_mensajes_contacto_cliente` (`cliente_id`);

--
-- Indices de la tabla `mensajes_internos`
--
ALTER TABLE `mensajes_internos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mensajes_internos_empresa` (`empresa_id`),
  ADD KEY `idx_mensajes_internos_para_usuario` (`para_usuario_id`),
  ADD KEY `idx_mensajes_internos_para_rol` (`para_rol`),
  ADD KEY `fk_mensajes_internos_de_usuario` (`de_usuario_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_empresa_user` (`empresa_id`,`usuario_id`,`leida`,`created_at`),
  ADD KEY `idx_notif_empresa_rol` (`empresa_id`,`rol_destino`,`leida`,`created_at`),
  ADD KEY `idx_notif_tipo` (`tipo`,`leida`,`created_at`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pass_reset_token` (`token_hash`),
  ADD KEY `idx_pass_reset_user` (`usuario_id`,`created_at`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_planes_nombre` (`nombre`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resenas_empresa` (`empresa_id`),
  ADD KEY `idx_resenas_sucursal` (`sucursal_id`),
  ADD KEY `idx_resenas_rating` (`rating`),
  ADD KEY `fk_resenas_cliente` (`cliente_id`);

--
-- Indices de la tabla `resena_contexto`
--
ALTER TABLE `resena_contexto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resena_contexto_resena` (`resena_id`),
  ADD KEY `idx_resena_contexto_empresa` (`empresa_id`);

--
-- Indices de la tabla `resena_invitaciones`
--
ALTER TABLE `resena_invitaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_resena_inv_cita` (`cita_id`),
  ADD UNIQUE KEY `uq_resena_inv_token` (`token_hash`),
  ADD KEY `idx_resena_inv_empresa_estado` (`empresa_id`,`estado`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicios_empresa_nombre` (`empresa_id`,`nombre`),
  ADD KEY `idx_servicios_empresa` (`empresa_id`);

--
-- Indices de la tabla `servicio_sucursales`
--
ALTER TABLE `servicio_sucursales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_servicio_sucursal` (`servicio_id`,`sucursal_id`),
  ADD KEY `idx_servicio_sucursales_sucursal` (`sucursal_id`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitudes_empresa` (`empresa_id`),
  ADD KEY `idx_solicitudes_estado` (`estado`),
  ADD KEY `fk_solicitudes_sucursal` (`sucursal_id`),
  ADD KEY `fk_solicitudes_solicitado_por` (`solicitado_por_usuario_id`),
  ADD KEY `fk_solicitudes_resuelto_por` (`resuelto_por_usuario_id`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sucursales_empresa_slug` (`empresa_id`,`slug`),
  ADD KEY `idx_sucursales_empresa` (`empresa_id`);

--
-- Indices de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suscrip_empresa` (`empresa_id`,`estado`,`fecha_fin`),
  ADD KEY `idx_suscrip_plan` (`plan_id`);

--
-- Indices de la tabla `suscripciones_historial`
--
ALTER TABLE `suscripciones_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sus_hist_empresa` (`empresa_id`,`created_at`),
  ADD KEY `idx_sus_hist_fecha_pago` (`ultimo_pago_fecha`),
  ADD KEY `idx_sus_hist_accion` (`accion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_empresa` (`empresa_id`),
  ADD KEY `idx_usuarios_sucursal` (`sucursal_id`),
  ADD KEY `idx_usuarios_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ajustes_globales`
--
ALTER TABLE `ajustes_globales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=361;

--
-- AUTO_INCREMENT de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `auditoria_eventos`
--
ALTER TABLE `auditoria_eventos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cliente_empresas`
--
ALTER TABLE `cliente_empresas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `email_envios`
--
ALTER TABLE `email_envios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `empleado_bloqueos`
--
ALTER TABLE `empleado_bloqueos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleado_horarios`
--
ALTER TABLE `empleado_horarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleado_servicios`
--
ALTER TABLE `empleado_servicios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `empresa_home_config`
--
ALTER TABLE `empresa_home_config`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `equipo`
--
ALTER TABLE `equipo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `home_page`
--
ALTER TABLE `home_page`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_internos`
--
ALTER TABLE `mensajes_internos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `resena_contexto`
--
ALTER TABLE `resena_contexto`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `resena_invitaciones`
--
ALTER TABLE `resena_invitaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `servicio_sucursales`
--
ALTER TABLE `servicio_sucursales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `suscripciones_historial`
--
ALTER TABLE `suscripciones_historial`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria_eventos`
--
ALTER TABLE `auditoria_eventos`
  ADD CONSTRAINT `fk_auditoria_actor` FOREIGN KEY (`actor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditoria_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `fk_blog_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_citas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_creado_por` FOREIGN KEY (`creado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_empleado` FOREIGN KEY (`empleado_usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cliente_empresas`
--
ALTER TABLE `cliente_empresas`
  ADD CONSTRAINT `fk_cliente_empresas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cliente_empresas_creado_por` FOREIGN KEY (`creado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cliente_empresas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleado_bloqueos`
--
ALTER TABLE `empleado_bloqueos`
  ADD CONSTRAINT `fk_empleado_bloqueos_empleado` FOREIGN KEY (`empleado_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleado_horarios`
--
ALTER TABLE `empleado_horarios`
  ADD CONSTRAINT `fk_empleado_horarios_empleado` FOREIGN KEY (`empleado_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleado_servicios`
--
ALTER TABLE `empleado_servicios`
  ADD CONSTRAINT `fk_empleado_servicios_empleado` FOREIGN KEY (`empleado_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_empleado_servicios_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `fk_empresas_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `empresa_home_config`
--
ALTER TABLE `empresa_home_config`
  ADD CONSTRAINT `fk_empresa_home_config_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `equipo`
--
ALTER TABLE `equipo`
  ADD CONSTRAINT `fk_equipo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `home_page`
--
ALTER TABLE `home_page`
  ADD CONSTRAINT `fk_home_page_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD CONSTRAINT `fk_mensajes_contacto_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensajes_contacto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensajes_contacto_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes_internos`
--
ALTER TABLE `mensajes_internos`
  ADD CONSTRAINT `fk_mensajes_internos_de_usuario` FOREIGN KEY (`de_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensajes_internos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensajes_internos_para_usuario` FOREIGN KEY (`para_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_pass_reset_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `fk_resenas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resenas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resenas_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `resena_contexto`
--
ALTER TABLE `resena_contexto`
  ADD CONSTRAINT `fk_resena_contexto_resena` FOREIGN KEY (`resena_id`) REFERENCES `resenas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `resena_invitaciones`
--
ALTER TABLE `resena_invitaciones`
  ADD CONSTRAINT `fk_resena_inv_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resena_inv_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `fk_servicios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `servicio_sucursales`
--
ALTER TABLE `servicio_sucursales`
  ADD CONSTRAINT `fk_servicio_sucursales_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_servicio_sucursales_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `fk_solicitudes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_resuelto_por` FOREIGN KEY (`resuelto_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_solicitado_por` FOREIGN KEY (`solicitado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD CONSTRAINT `fk_sucursales_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `fk_suscrip_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_suscrip_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;

