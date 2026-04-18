-- ============================================
-- MIGRACIÓN: 18 de Abril 2026
-- Actualización para notificaciones Telegram y módulo de contacto
-- Compatible con Aiven (MySQL/MariaDB)
-- ============================================

-- ============================================
-- 1. TABLAS NUEVAS
-- ============================================

-- Tabla para notificaciones Telegram por usuario (planes de pago)
CREATE TABLE IF NOT EXISTS notificaciones_telegram (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  api_key VARCHAR(64) NOT NULL,
  chat_id VARCHAR(50) NULL,
  telegram_username VARCHAR(100) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
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

-- ============================================
-- 2. MODIFICACIONES A TABLAS EXISTENTES
-- ============================================

-- Modificar mensajes_contacto para permitir NULL en empresa_id
-- (para mensajes al superadmin/sistema)
ALTER TABLE mensajes_contacto 
MODIFY COLUMN empresa_id BIGINT UNSIGNED NULL COMMENT 'NULL para mensajes al superadmin/sistema';

-- ============================================
-- 3. VERIFICACIÓN
-- ============================================

-- Verificar que las tablas existen
SELECT 
  'Tablas creadas/modificadas correctamente' AS mensaje,
  (SELECT COUNT(*) FROM information_schema.tables 
   WHERE table_schema = DATABASE() 
   AND table_name = 'notificaciones_telegram') AS notificaciones_telegram_existe,
  (SELECT COUNT(*) FROM information_schema.columns 
   WHERE table_schema = DATABASE() 
   AND table_name = 'mensajes_contacto' 
   AND column_name = 'empresa_id' 
   AND is_nullable = 'YES') AS empresa_id_es_nullable;

-- ============================================
-- INSTRUCCIONES PARA EJECUTAR EN AIVEN:
-- ============================================
-- OPCIÓN 1 - Usando Aiven Console:
-- 1. Ve a tu dashboard de Aiven
-- 2. Selecciona tu servicio de MySQL
-- 3. Ve a la pestaña "Query Editor" o "SQL Editor"
-- 4. Copia y pega este script completo
-- 5. Ejecuta
--
-- OPCIÓN 2 - Usando MySQL CLI o cliente:
-- mysql -h TU_HOST_AIVEN -u TU_USUARIO -p TU_BASE_DATOS < migracion_20250418.sql
--
-- OPCIÓN 3 - Usando phpMyAdmin si está disponible:
-- 1. Abre phpMyAdmin
-- 2. Selecciona tu base de datos
-- 3. Ve a la pestaña "SQL"
-- 4. Copia y pega este script
-- 5. Ejecuta
-- ============================================
