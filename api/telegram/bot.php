<?php
/**
 * Webhook del Bot Telegram para Notificaciones de Empresas
 * 
 * Flujo de activación:
 * 1. Usuario escribe /start
 * 2. Bot pregunta: "¿Cuál es tu rol?" (Admin/Gerente/Empleado)
 * 3. Usuario selecciona rol
 * 4. Bot pide: "Ingresa tu API Key"
 * 5. Usuario envía API Key
 * 6. Bot valida y vincula chat_id con usuario
 */

require_once __DIR__ . '/../../helpers.php';

// Token del bot de empresas (diferente al superadmin)
define('TELEGRAM_BOT_TOKEN', 'TU_BOT_TOKEN_AQUI'); // Cambiar por el token real

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['message'])) {
    http_response_code(200);
    exit;
}

$message = $data['message'];
$chat_id = $message['chat']['id'] ?? null;
$text = trim($message['text'] ?? '');
$username = $message['from']['username'] ?? null;
$first_name = $message['from']['first_name'] ?? null;

if (!$chat_id) {
    http_response_code(200);
    exit;
}

// Manejar comandos y mensajes
if (strpos($text, '/start') === 0) {
    send_message($chat_id, 
        "👋 ¡Bienvenido al Bot de Notificaciones!\n\n" .
        "Para recibir alertas de tu empresa, necesito vincular tu cuenta.\n\n" .
        "¿Cuál es tu rol?\n\n" .
        "1️⃣ Admin\n" .
        "2️⃣ Gerente\n" .
        "3️⃣ Empleado\n\n" .
        "Responde con el número de tu rol (1, 2 o 3):"
    );
    exit;
}

// Verificar si el usuario está en proceso de registro
$session = get_telegram_session($chat_id);

if (!$session) {
    // Primera interacción - esperando selección de rol
    if (in_array($text, ['1', '2', '3', 'Admin', 'Gerente', 'Empleado'], true)) {
        $roles = ['1' => 'admin', '2' => 'gerente', '3' => 'empleado'];
        $rol = $roles[$text] ?? strtolower($text);
        
        save_telegram_session($chat_id, ['step' => 'waiting_api_key', 'rol' => $rol]);
        
        send_message($chat_id,
            "✅ Rol seleccionado: " . ucfirst($rol) . "\n\n" .
            "Ahora ingresa tu API Key.\n\n" .
            "📌 La API Key se genera automáticamente cuando un administrador activa las notificaciones Telegram en tu perfil.\n\n" .
            "Si no tienes una, contacta a tu administrador."
        );
    } else {
        send_message($chat_id, "Por favor selecciona tu rol: 1 (Admin), 2 (Gerente) o 3 (Empleado)");
    }
    exit;
}

if ($session['step'] === 'waiting_api_key') {
    // Validar API Key
    $api_key = preg_replace('/[^a-f0-9]/', '', strtolower($text)); // Sanitizar
    
    if (strlen($api_key) !== 64) {
        send_message($chat_id, "❌ API Key inválida. Debe tener 64 caracteres hexadecimales. Intenta de nuevo:");
        exit;
    }
    
    // Buscar usuario por API key
    $usuario = find_user_by_api_key($api_key);
    
    if (!$usuario) {
        send_message($chat_id, "❌ API Key no encontrada. Verifica que esté correcta o contacta a tu administrador.");
        exit;
    }
    
    // Verificar que el rol coincida
    if ($usuario['rol'] !== $session['rol']) {
        send_message($chat_id, 
            "⚠️ El rol de la API Key no coincide con el seleccionado.\n\n" .
            "Rol seleccionado: " . ucfirst($session['rol']) . "\n" .
            "Rol en sistema: " . ucfirst($usuario['rol']) . "\n\n" .
            "Por favor selecciona el rol correcto."
        );
        delete_telegram_session($chat_id);
        exit;
    }
    
    // Verificar plan de pago
    if (!empresa_tiene_plan_pago($usuario['empresa_id'])) {
        send_message($chat_id, "❌ Tu empresa tiene un plan gratuito. Las notificaciones Telegram solo están disponibles para planes de pago.");
        delete_telegram_session($chat_id);
        exit;
    }
    
    // Activar notificaciones
    $result = telegram_activar_usuario($usuario['id'], (string) $chat_id, $username);
    
    if ($result) {
        send_message($chat_id,
            "🎉 ¡Cuenta vinculada exitosamente!\n\n" .
            "👤 Usuario: " . htmlspecialchars($usuario['nombre']) . "\n" .
            "🏢 Empresa: " . htmlspecialchars($usuario['empresa_nombre']) . "\n" .
            "🔰 Rol: " . ucfirst($usuario['rol']) . "\n\n" .
            "Ahora recibirás notificaciones según tu configuración:\n" .
            "• Citas nuevas\n" .
            "• Citas canceladas\n" .
            "• Citas completadas\n" .
            "• Mensajes internos\n\n" .
            "Puedes personalizar las alertas en tu perfil del sistema.\n\n" .
            "Para desactivar, usa /stop"
        );
    } else {
        send_message($chat_id, "❌ Error al activar notificaciones. Contacta a soporte.");
    }
    
    delete_telegram_session($chat_id);
    exit;
}

if ($text === '/stop') {
    // Desactivar notificaciones
    $stmt = $pdo->prepare("UPDATE notificaciones_telegram SET activo = 0 WHERE chat_id = ?");
    $stmt->execute([$chat_id]);
    
    if ($stmt->rowCount() > 0) {
        send_message($chat_id, "🔕 Notificaciones desactivadas. Para reactivar, usa /start");
    } else {
        send_message($chat_id, "No tienes notificaciones activas.");
    }
    exit;
}

if ($text === '/help') {
    send_message($chat_id,
        "📖 Comandos disponibles:\n\n" .
        "/start - Vincular cuenta\n" .
        "/stop - Desactivar notificaciones\n" .
        "/help - Mostrar esta ayuda\n\n" .
        "Para personalizar alertas, usa el panel web en tu perfil."
    );
    exit;
}

// Mensaje por defecto
send_message($chat_id, 
    "Comando no reconocido.\n\n" .
    "Usa /start para vincular tu cuenta\n" .
    "Usa /help para ver los comandos disponibles"
);

// ─────────────────────────────────────────────────────────────────────────────
// FUNCIONES AUXILIARES
// ─────────────────────────────────────────────────────────────────────────────

function send_message($chat_id, $text) {
    $token = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function get_telegram_session($chat_id) {
    // Almacenamiento simple en archivo (en producción usar Redis o BD)
    $file = __DIR__ . '/../../temp/telegram_sessions.json';
    if (!file_exists($file)) return null;
    
    $sessions = json_decode(file_get_contents($file), true) ?: [];
    return $sessions[$chat_id] ?? null;
}

function save_telegram_session($chat_id, $data) {
    $file = __DIR__ . '/../../temp/telegram_sessions.json';
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $sessions = [];
    if (file_exists($file)) {
        $sessions = json_decode(file_get_contents($file), true) ?: [];
    }
    
    $sessions[$chat_id] = array_merge($data, ['timestamp' => time()]);
    file_put_contents($file, json_encode($sessions));
}

function delete_telegram_session($chat_id) {
    $file = __DIR__ . '/../../temp/telegram_sessions.json';
    if (!file_exists($file)) return;
    
    $sessions = json_decode(file_get_contents($file), true) ?: [];
    unset($sessions[$chat_id]);
    file_put_contents($file, json_encode($sessions));
}

function find_user_by_api_key($api_key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.nombre, u.rol, u.empresa_id, e.nombre as empresa_nombre
            FROM usuarios u
            JOIN empresas e ON u.empresa_id = e.id
            JOIN notificaciones_telegram nt ON nt.usuario_id = u.id
            WHERE nt.api_key = ? AND nt.activo = 0
            LIMIT 1
        ");
        $stmt->execute([$api_key]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('Error find_user_by_api_key: ' . $e->getMessage());
        return null;
    }
}
