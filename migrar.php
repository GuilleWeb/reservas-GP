<?php
/**
 * Script de Migración - 18 Abril 2026
 * Ejecutar en navegador o CLI para actualizar la base de datos
 * 
 * Ejecutar desde navegador: https://tu-dominio.com/migrar.php?token=migracion2024
 * Ejecutar desde CLI: php migrar.php --force
 */

require_once __DIR__ . '/helpers.php';

// Token de seguridad para ejecución via web
$expected_token = 'migracion2024';
$is_cli = PHP_SAPI === 'cli';

// Verificar autenticación
if (!$is_cli) {
    $token = $_GET['token'] ?? '';
    if ($token !== $expected_token) {
        http_response_code(403);
        echo "Acceso denegado. Usa: migrar.php?token=migracion2024";
        exit;
    }
}

// En CLI requiere --force para ejecutar
if ($is_cli && !in_array('--force', $argv)) {
    echo "Uso: php migrar.php --force\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

echo "========================================\n";
echo "  MIGRACIÓN - 18 Abril 2026\n";
echo "========================================\n\n";

$errores = [];
$exitos = [];

// ============================================
// 1. Crear tabla notificaciones_telegram
// ============================================
try {
    $sql = "CREATE TABLE IF NOT EXISTS notificaciones_telegram (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $exitos[] = "✅ Tabla 'notificaciones_telegram' creada/existente";
} catch (PDOException $e) {
    $errores[] = "❌ Error creando notificaciones_telegram: " . $e->getMessage();
}

// ============================================
// 2. Modificar mensajes_contacto (empresa_id a NULL)
// ============================================
try {
    // Primero verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'mensajes_contacto'");
    if ($stmt->rowCount() === 0) {
        $exitos[] = "ℹ️ Tabla 'mensajes_contacto' no existe - se creará cuando se use por primera vez";
    } else {
        // Verificar la columna actual
        $stmt = $pdo->query("SHOW COLUMNS FROM mensajes_contacto WHERE Field = 'empresa_id'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column) {
            $is_nullable = strtoupper($column['Null']) === 'YES';
            
            if ($is_nullable) {
                $exitos[] = "✅ Columna 'empresa_id' ya permite NULL (ya estaba migrada)";
            } else {
                // Verificar si hay datos con empresa_id NULL que impedirían la modificación
                $stmt = $pdo->query("SELECT COUNT(*) FROM mensajes_contacto WHERE empresa_id IS NULL");
                $null_count = $stmt->fetchColumn();
                
                if ($null_count > 0) {
                    // Hay datos conflictivos, necesitamos un default temporal
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $pdo->exec("UPDATE mensajes_contacto SET empresa_id = 1 WHERE empresa_id IS NULL");
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $exitos[] = "ℹ️ Datos NULL temporalmente asignados a empresa_id=1 para permitir migración";
                }
                
                // Modificar la columna
                $pdo->exec("ALTER TABLE mensajes_contacto MODIFY COLUMN empresa_id BIGINT UNSIGNED NULL");
                $exitos[] = "✅ Columna 'empresa_id' modificada para permitir NULL";
            }
        } else {
            $errores[] = "⚠️ Columna 'empresa_id' no encontrada en mensajes_contacto";
        }
    }
} catch (PDOException $e) {
    $errores[] = "❌ Error modificando mensajes_contacto: " . $e->getMessage();
}

// ============================================
// 3. Verificar/crear tabla mensajes_contacto si no existe
// ============================================
try {
    $sql = "CREATE TABLE IF NOT EXISTS mensajes_contacto (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id BIGINT UNSIGNED NULL,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $exitos[] = "✅ Tabla 'mensajes_contacto' verificada/creada";
} catch (PDOException $e) {
    $errores[] = "❌ Error con mensajes_contacto: " . $e->getMessage();
}

// ============================================
// RESULTADO
// ============================================
echo "RESULTADO DE LA MIGRACIÓN:\n";
echo "----------------------------------------\n\n";

foreach ($exitos as $exito) {
    echo $exito . "\n";
}

if (!empty($errores)) {
    echo "\n";
    echo "ERRORES:\n";
    echo "----------------------------------------\n";
    foreach ($errores as $error) {
        echo $error . "\n";
    }
}

echo "\n========================================\n";
echo count($exitos) . " operaciones exitosas\n";
echo count($errores) . " errores\n";
echo "========================================\n";

if (empty($errores)) {
    echo "\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
    exit(0);
} else {
    echo "\n⚠️ MIGRACIÓN COMPLETADA CON ERRORES\n";
    exit(1);
}
