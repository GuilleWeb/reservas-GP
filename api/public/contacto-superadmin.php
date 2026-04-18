<?php
/**
 * API Pública para enviar mensajes de contacto al Superadmin
 * Desde la página principal del sistema (index.php)
 * Guarda en tabla de mensajes_contacto con empresa_id = 0 (sistema/superadmin)
 */
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Validar datos
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$empresa_remitente = trim($_POST['empresa_remitente'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if (empty($nombre) || empty($email) || empty($mensaje)) {
    json_response(['success' => false, 'message' => 'Nombre, email y mensaje son obligatorios'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Email no válido'], 400);
}

try {
    // Insertar mensaje en mensajes_contacto con empresa_id = 0 (superadmin)
    $asunto = 'Mensaje de contacto desde la web principal';
    $mensaje_completo = "Nombre: {$nombre}\nEmail: {$email}";
    if ($empresa_remitente) {
        $mensaje_completo .= "\nEmpresa/Negocio: {$empresa_remitente}";
    }
    $mensaje_completo .= "\n\nMensaje:\n{$mensaje}";
    
    // Usar empresa_id = NULL para indicar mensaje al superadmin (sistema)
    $stmt = $pdo->prepare("INSERT INTO mensajes_contacto 
        (empresa_id, nombre, email, asunto, mensaje, estado, created_at) 
        VALUES (NULL, ?, ?, ?, ?, 'nuevo', NOW())");
    $stmt->execute([$nombre, $email, $asunto, $mensaje_completo]);
    $mensaje_id = (int) $pdo->lastInsertId();
    
    // Crear notificación para el superadmin (empresa_id = 0 indica superadmin)
    // Buscamos usuarios con rol superadmin
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE rol = 'superadmin' AND activo = 1 LIMIT 1");
    $stmt->execute();
    $superadmins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($superadmins as $superadmin_id) {
        create_notification([
            'empresa_id' => 0, // Sistema/superadmin
            'usuario_id' => (int) $superadmin_id,
            'tipo' => 'mensaje_contacto_sistema',
            'titulo' => 'Nuevo mensaje del sitio web: ' . mb_substr($nombre, 0, 30),
            'descripcion' => mb_substr($mensaje, 0, 100) . (mb_strlen($mensaje) > 100 ? '...' : ''),
            'url' => 'vistas/superadmin/dashboard.php', // O vista de mensajes si existe
            'referencia_tipo' => 'mensaje_contacto',
            'referencia_id' => $mensaje_id,
        ]);
        
        // Notificación por Telegram si tiene activo
        $telegram_msg = "📨 <b>Nuevo mensaje del sitio web</b>\n\n";
        $telegram_msg .= "👤 <b>De:</b> " . htmlspecialchars($nombre) . "\n";
        $telegram_msg .= "📧 <b>Email:</b> " . htmlspecialchars($email) . "\n";
        if ($empresa_remitente) {
            $telegram_msg .= "🏢 <b>Negocio:</b> " . htmlspecialchars($empresa_remitente) . "\n";
        }
        $telegram_msg .= "\n📝 <b>Mensaje:</b>\n" . htmlspecialchars(mb_substr($mensaje, 0, 200));
        if (mb_strlen($mensaje) > 200) {
            $telegram_msg .= "...";
        }
        
        telegram_notify_usuario((int) $superadmin_id, $telegram_msg, 'mensaje_contacto');
    }
    
    json_response(['success' => true, 'message' => 'Mensaje enviado correctamente']);
    
} catch (Throwable $e) {
    error_log('Error enviando mensaje de contacto a superadmin: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error al enviar el mensaje. Intenta de nuevo.'], 500);
}
