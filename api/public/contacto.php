<?php
/**
 * API Pública para enviar mensajes de contacto a empresas
 * Guarda en mensajes_contacto y crea notificación para el admin
 */
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

// Validar CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf($csrf)) {
    json_response(['success' => false, 'message' => 'Token de seguridad inválido'], 403);
}

// Rate limiting: máximo 1 mensaje cada 15 minutos por IP + email
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$email = trim($_POST['email'] ?? '');
$rate_limit_key = md5($ip . ':' . $email . ':empresa');
$rate_limit_file = __DIR__ . '/../../temp/rate_limit_' . $rate_limit_key . '.txt';
$rate_limit_minutes = 15;

if (file_exists($rate_limit_file)) {
    $last_sent = (int) file_get_contents($rate_limit_file);
    $minutes_ago = (time() - $last_sent) / 60;
    
    if ($minutes_ago < $rate_limit_minutes) {
        $wait_minutes = ceil($rate_limit_minutes - $minutes_ago);
        json_response(['success' => false, 'message' => "Has enviado un mensaje recientemente. Por favor espera {$wait_minutes} minutos antes de enviar otro."], 429);
    }
}

// Validar datos
$empresa_id = intval($_POST['empresa_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$empresa_remitente = trim($_POST['empresa_remitente'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if ($empresa_id <= 0) {
    json_response(['success' => false, 'message' => 'Empresa no válida'], 400);
}

if (empty($nombre) || empty($email) || empty($mensaje)) {
    json_response(['success' => false, 'message' => 'Nombre, email y mensaje son obligatorios'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Email no válido'], 400);
}

if (mb_strlen($mensaje) < 10) {
    json_response(['success' => false, 'message' => 'El mensaje debe tener al menos 10 caracteres'], 400);
}

// Verificar que la empresa existe
$stmt = $pdo->prepare("SELECT id, nombre FROM empresas WHERE id = ? AND activo = 1");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    json_response(['success' => false, 'message' => 'Empresa no encontrada'], 404);
}

try {
    // Insertar mensaje en mensajes_contacto
    $asunto = 'Mensaje de contacto desde la web';
    $mensaje_completo = "Nombre: {$nombre}\nEmail: {$email}";
    if ($empresa_remitente) {
        $mensaje_completo .= "\nEmpresa: {$empresa_remitente}";
    }
    $mensaje_completo .= "\n\nMensaje:\n{$mensaje}";
    
    $stmt = $pdo->prepare("INSERT INTO mensajes_contacto 
        (empresa_id, nombre, email, asunto, mensaje, estado, created_at) 
        VALUES (?, ?, ?, ?, ?, 'nuevo', NOW())");
    $stmt->execute([$empresa_id, $nombre, $email, $asunto, $mensaje_completo]);
    $mensaje_id = (int) $pdo->lastInsertId();
    
    // Crear notificación para el admin de la empresa
    create_notification([
        'empresa_id' => $empresa_id,
        'rol_destino' => 'admin',
        'tipo' => 'mensaje_contacto',
        'titulo' => 'Nuevo mensaje de contacto: ' . mb_substr($nombre, 0, 30),
        'descripcion' => mb_substr($mensaje, 0, 100) . (mb_strlen($mensaje) > 100 ? '...' : ''),
        'url' => 'vistas/admin/mensajes.php',
        'referencia_tipo' => 'mensaje_contacto',
        'referencia_id' => $mensaje_id,
    ]);
    
    // Intentar enviar notificación por Telegram si el admin tiene activo
    $stmt = $pdo->prepare("SELECT u.id FROM usuarios u WHERE u.empresa_id = ? AND u.rol = 'admin' AND u.activo = 1 LIMIT 1");
    $stmt->execute([$empresa_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $telegram_msg = "📨 <b>Nuevo mensaje de contacto</b>\n\n";
        $telegram_msg .= "👤 <b>De:</b> " . htmlspecialchars($nombre) . "\n";
        $telegram_msg .= "📧 <b>Email:</b> " . htmlspecialchars($email) . "\n";
        if ($empresa_remitente) {
            $telegram_msg .= "🏢 <b>Empresa:</b> " . htmlspecialchars($empresa_remitente) . "\n";
        }
        $telegram_msg .= "\n📝 <b>Mensaje:</b>\n" . htmlspecialchars(mb_substr($mensaje, 0, 200));
        if (mb_strlen($mensaje) > 200) {
            $telegram_msg .= "...";
        }
        
        telegram_notify_usuario((int) $admin['id'], $telegram_msg, 'mensaje_contacto');
    }
    
    // Guardar timestamp para rate limiting
    $dir = dirname($rate_limit_file);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($rate_limit_file, time());
    
    json_response(['success' => true, 'message' => 'Mensaje enviado correctamente']);
    
} catch (Throwable $e) {
    error_log('Error enviando mensaje de contacto: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error al enviar el mensaje. Intenta de nuevo.'], 500);
}
