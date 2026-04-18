-- schema.sql
-- Multi-empresa (single DB) - MySQL 8+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------
-- Core: planes / empresas
-- -----------------------------

CREATE TABLE IF NOT EXISTS planes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  max_sucursales INT UNSIGNED NOT NULL DEFAULT 1,
  max_empleados INT UNSIGNED NOT NULL DEFAULT 1,
  max_servicios INT UNSIGNED NOT NULL DEFAULT 50,
  max_clientes INT UNSIGNED NOT NULL DEFAULT 10000,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_planes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_id BIGINT UNSIGNED NULL,
  slug VARCHAR(80) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  slogan VARCHAR(255) NULL,
  descripcion TEXT NULL,
  logo_path VARCHAR(255) NULL,
  portada_path VARCHAR(255) NULL,
  colores_json JSON NULL,
  redes_json JSON NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_empresas_slug (slug),
  KEY idx_empresas_plan (plan_id),
  CONSTRAINT fk_empresas_plan FOREIGN KEY (plan_id) REFERENCES planes(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Sucursales
-- -----------------------------

CREATE TABLE IF NOT EXISTS sucursales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(80) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  direccion VARCHAR(255) NULL,
  telefono VARCHAR(40) NULL,
  email VARCHAR(150) NULL,
  foto_path VARCHAR(255) NULL,
  colores_json JSON NULL,
  horarios_json JSON NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sucursales_empresa_slug (empresa_id, slug),
  KEY idx_sucursales_empresa (empresa_id),
  CONSTRAINT fk_sucursales_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Usuarios / Roles
-- -----------------------------

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NULL,
  sucursal_id BIGINT UNSIGNED NULL,
  rol ENUM('superadmin','admin','gerente','empleado','cliente') NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  telefono VARCHAR(40) NULL,
  password_hash VARCHAR(255) NOT NULL,
  foto_path VARCHAR(255) NULL,
  session_token VARCHAR(128) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_empresa (empresa_id),
  KEY idx_usuarios_sucursal (sucursal_id),
  KEY idx_usuarios_rol (rol),
  CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_usuarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Auditoría / Movimientos
-- -----------------------------

CREATE TABLE IF NOT EXISTS auditoria_eventos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NULL,
  actor_usuario_id BIGINT UNSIGNED NULL,
  actor_rol ENUM('superadmin','admin','gerente','empleado','cliente') NULL,
  tipo VARCHAR(60) NOT NULL,
  entidad VARCHAR(60) NOT NULL,
  entidad_id BIGINT UNSIGNED NULL,
  descripcion VARCHAR(255) NULL,
  metadata_json JSON NULL,
  ip VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_auditoria_empresa (empresa_id),
  KEY idx_auditoria_actor (actor_usuario_id),
  KEY idx_auditoria_tipo (tipo),
  KEY idx_auditoria_created (created_at),
  CONSTRAINT fk_auditoria_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_auditoria_actor FOREIGN KEY (actor_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Clientes (global) + relación empresa
-- -----------------------------

CREATE TABLE IF NOT EXISTS clientes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  telefono VARCHAR(40) NULL,
  fecha_nacimiento DATE NULL,
  notas TEXT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_clientes_email (email),
  KEY idx_clientes_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cliente_empresas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id BIGINT UNSIGNED NOT NULL,
  empresa_id BIGINT UNSIGNED NOT NULL,
  creado_por_usuario_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cliente_empresas (cliente_id, empresa_id),
  KEY idx_cliente_empresas_empresa (empresa_id),
  CONSTRAINT fk_cliente_empresas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cliente_empresas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cliente_empresas_creado_por FOREIGN KEY (creado_por_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Servicios (por empresa) y asignación a sucursales
-- -----------------------------

CREATE TABLE IF NOT EXISTS servicios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  duracion_minutos INT UNSIGNED NOT NULL DEFAULT 30,
  precio_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicios_empresa_nombre (empresa_id, nombre),
  KEY idx_servicios_empresa (empresa_id),
  CONSTRAINT fk_servicios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicio_sucursales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  servicio_id BIGINT UNSIGNED NOT NULL,
  sucursal_id BIGINT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicio_sucursal (servicio_id, sucursal_id),
  KEY idx_servicio_sucursales_sucursal (sucursal_id),
  CONSTRAINT fk_servicio_sucursales_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_servicio_sucursales_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Empleado <-> Servicios (y override de precio)
-- -----------------------------

CREATE TABLE IF NOT EXISTS empleado_servicios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empleado_usuario_id BIGINT UNSIGNED NOT NULL,
  servicio_id BIGINT UNSIGNED NOT NULL,
  precio_override DECIMAL(10,2) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_empleado_servicio (empleado_usuario_id, servicio_id),
  KEY idx_empleado_servicios_servicio (servicio_id),
  CONSTRAINT fk_empleado_servicios_empleado FOREIGN KEY (empleado_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_empleado_servicios_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Horarios y bloqueos del empleado
-- -----------------------------

CREATE TABLE IF NOT EXISTS empleado_horarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empleado_usuario_id BIGINT UNSIGNED NOT NULL,
  weekday TINYINT UNSIGNED NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_empleado_horarios_empleado (empleado_usuario_id),
  KEY idx_empleado_horarios_weekday (weekday),
  CONSTRAINT fk_empleado_horarios_empleado FOREIGN KEY (empleado_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empleado_bloqueos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empleado_usuario_id BIGINT UNSIGNED NOT NULL,
  inicio DATETIME NOT NULL,
  fin DATETIME NOT NULL,
  tipo ENUM('descanso','comida','vacaciones','bloqueo') NOT NULL,
  motivo VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_empleado_bloqueos_empleado (empleado_usuario_id),
  KEY idx_empleado_bloqueos_rango (inicio, fin),
  CONSTRAINT fk_empleado_bloqueos_empleado FOREIGN KEY (empleado_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Citas
-- -----------------------------

CREATE TABLE IF NOT EXISTS citas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  sucursal_id BIGINT UNSIGNED NOT NULL,
  servicio_id BIGINT UNSIGNED NOT NULL,
  empleado_usuario_id BIGINT UNSIGNED NOT NULL,
  cliente_id BIGINT UNSIGNED NULL,
  cliente_nombre VARCHAR(150) NULL,
  cliente_email VARCHAR(150) NULL,
  cliente_telefono VARCHAR(40) NULL,
  inicio DATETIME NOT NULL,
  fin DATETIME NOT NULL,
  estado ENUM('pendiente','confirmada','rechazada','cancelada','completada','no_asistio') NOT NULL DEFAULT 'pendiente',
  notas TEXT NULL,
  creado_por_usuario_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_citas_empresa (empresa_id),
  KEY idx_citas_sucursal (sucursal_id),
  KEY idx_citas_empleado (empleado_usuario_id),
  KEY idx_citas_servicio (servicio_id),
  KEY idx_citas_inicio (inicio),
  CONSTRAINT fk_citas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_citas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_citas_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_citas_empleado FOREIGN KEY (empleado_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_citas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_citas_creado_por FOREIGN KEY (creado_por_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Solicitudes / Aprobaciones
-- -----------------------------

CREATE TABLE IF NOT EXISTS solicitudes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  sucursal_id BIGINT UNSIGNED NULL,
  tipo VARCHAR(60) NOT NULL,
  payload_json JSON NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  solicitado_por_usuario_id BIGINT UNSIGNED NOT NULL,
  resuelto_por_usuario_id BIGINT UNSIGNED NULL,
  resuelto_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_solicitudes_empresa (empresa_id),
  KEY idx_solicitudes_estado (estado),
  CONSTRAINT fk_solicitudes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_solicitudes_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_solicitudes_solicitado_por FOREIGN KEY (solicitado_por_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_solicitudes_resuelto_por FOREIGN KEY (resuelto_por_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Reseñas
-- -----------------------------

CREATE TABLE IF NOT EXISTS resenas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  sucursal_id BIGINT UNSIGNED NULL,
  cliente_id BIGINT UNSIGNED NULL,
  autor_nombre VARCHAR(150) NULL,
  rating TINYINT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NULL,
  comentario TEXT NULL,
  visible_en_home TINYINT(1) NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_resenas_empresa (empresa_id),
  KEY idx_resenas_sucursal (sucursal_id),
  KEY idx_resenas_rating (rating),
  CONSTRAINT fk_resenas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_resenas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_resenas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Blog (por empresa)
-- -----------------------------

CREATE TABLE IF NOT EXISTS blog_posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  contenido LONGTEXT NOT NULL,
  imagen_path VARCHAR(255) NULL,
  publicado TINYINT(1) NOT NULL DEFAULT 0,
  publicado_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_empresa_slug (empresa_id, slug),
  KEY idx_blog_empresa (empresa_id),
  CONSTRAINT fk_blog_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Ajustes globales (superadmin)
-- -----------------------------

CREATE TABLE IF NOT EXISTS ajustes_globales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave VARCHAR(120) NOT NULL,
  valor_json JSON NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ajustes_globales_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Ajustes por empresa (incluye Telegram)
-- -----------------------------
CREATE TABLE IF NOT EXISTS ajustes_empresa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  clave VARCHAR(120) NOT NULL,
  valor TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ajustes_empresa_empresa_clave (empresa_id, clave),
  KEY idx_ajustes_empresa_clave (clave),
  CONSTRAINT fk_ajustes_empresa_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Home / contenido público por empresa
-- -----------------------------

CREATE TABLE IF NOT EXISTS empresa_home_config (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  data_json JSON NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_empresa_home_config_empresa (empresa_id),
  CONSTRAINT fk_empresa_home_config_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Home dinámico por módulos
-- -----------------------------

CREATE TABLE IF NOT EXISTS home_page (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NOT NULL,
  modulo VARCHAR(60) NOT NULL,
  titulo VARCHAR(190) NOT NULL,
  valores_json JSON NULL,
  tipo TINYINT UNSIGNED NOT NULL DEFAULT 1, -- 1=custom,2=recientes,3=aleatorio
  estado TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  limite INT UNSIGNED NOT NULL DEFAULT 3,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_home_page_empresa_modulo (empresa_id, modulo),
  KEY idx_home_page_empresa_orden (empresa_id, orden),
  CONSTRAINT fk_home_page_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Mensajes (contacto + internos)
-- -----------------------------

CREATE TABLE IF NOT EXISTS mensajes_contacto (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NULL, -- NULL para mensajes al superadmin/sistema
  sucursal_id BIGINT UNSIGNED NULL,
  cliente_id BIGINT UNSIGNED NULL,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  asunto VARCHAR(255) NULL,
  mensaje TEXT NOT NULL,
  estado ENUM('nuevo','leido','archivado') NOT NULL DEFAULT 'nuevo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mensajes_contacto_empresa (empresa_id),
  KEY idx_mensajes_contacto_estado (estado),
  CONSTRAINT fk_mensajes_contacto_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_mensajes_contacto_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mensajes_contacto_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensajes_internos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id BIGINT UNSIGNED NULL,
  para_rol ENUM('admin','gerente','empleado') NULL,
  para_usuario_id BIGINT UNSIGNED NULL,
  de_usuario_id BIGINT UNSIGNED NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  cuerpo TEXT NOT NULL,
  estado ENUM('enviado','leido','archivado') NOT NULL DEFAULT 'enviado',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mensajes_internos_empresa (empresa_id),
  KEY idx_mensajes_internos_para_usuario (para_usuario_id),
  KEY idx_mensajes_internos_para_rol (para_rol),
  CONSTRAINT fk_mensajes_internos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mensajes_internos_para_usuario FOREIGN KEY (para_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mensajes_internos_de_usuario FOREIGN KEY (de_usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Anuncios del sistema
-- -----------------------------

CREATE TABLE IF NOT EXISTS anuncios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slot VARCHAR(30) NOT NULL,
  imagen_path VARCHAR(255) NULL,
  link_url VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_anuncios_slot (slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Notificaciones Telegram por Usuario (Planes de pago)
-- -----------------------------
CREATE TABLE IF NOT EXISTS notificaciones_telegram (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  api_key VARCHAR(64) NOT NULL,
  chat_id VARCHAR(50) NULL,
  telegram_username VARCHAR(100) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  -- Configuración de alertas por tipo (JSON con los tipos activos)
  alertas_config JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notif_telegram_api_key (api_key),
  UNIQUE KEY uq_notif_telegram_usuario (usuario_id),
  KEY idx_notif_telegram_chat (chat_id),
  CONSTRAINT fk_notif_telegram_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
