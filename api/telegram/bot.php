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
 * 
 * NOTA: Usa get_telegram_session_file() y save_telegram_session_file() 
 * que ya existen en helpers.php
 */

require_once __DIR__ . '/../../helpers.php';

// Token del bot de empresas (diferente al superadmin)
// IMPORTANTE: Configura esto en el webhook o usa variable de entorno
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: 'TU_BOT_TOKEN_AQUI');

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['message'])) {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

$message = $data['message'];
$chat_id = $message['chat']['id'] ?? null;
$text = trim($message['text'] ?? '');
$username = $message['from']['username'] ?? null;
$first_name = $message['from']['first_name'] ?? null;

if (!$chat_id) {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// Verificar si el usuario ya está registrado
$existing_config = telegram_get_config_by_chat_id((string)$chat_id);

// Comando /start
if (strpos($text, '/start') === 0) {
    // Si ya está registrado, mostrar info y opciones
    if ($existing_config && $existing_config['activo']) {
        send_message($chat_id, 
            "👋 ¡Hola de nuevo!\n\n" .
            "Tu cuenta ya está vinculada:\n" .
            "👤 " . htmlspecialchars($existing_config['nombre'] ?? 'Usuario') . "\n" .
            "🏢 " . htmlspecialchars($existing_config['empresa'] ?? 'Empresa') . "\n\n" .
            "Comandos disponibles:\n" .
            "• /stop - Desactivar notificaciones\n" .
            "• /status - Ver estado\n" .
            "• /help - Ayuda"
        );
    } else {
        // Nuevo usuario o reactivación
        delete_telegram_session_file($chat_id); // Limpiar sesión anterior si existe
        
        send_message($chat_id, 
            "👋 ¡Bienvenido al Bot de Notificaciones Reservas GP!\n\n" .
            "Para recibir alertas de tu empresa, necesito vincular tu cuenta.\n\n" .
            "¿Cuál es tu rol?\n\n" .
            "1️⃣ Admin\n" .
            "2️⃣ Gerente\n" .
            "3️⃣ Empleado\n\n" .
            "Responde con el número (1, 2 o 3):"
        );
    }
    exit;
}

// Comando /status
if ($text === '/status') {
    if ($existing_config && $existing_config['activo']) {
        send_message($chat_id,
            "📊 Estado de tu cuenta:\n\n" .
            "✅ Notificaciones: Activadas\n" .
            "👤 Usuario: " . htmlspecialchars($existing_config['nombre'] ?? 'N/A') . "\n" .
            "🏢 Empresa: " . htmlspecialchars($existing_config['empresa'] ?? 'N/A') . "\n" .
            "🔰 Rol: " . ucfirst($existing_config['rol'] ?? 'N/A') . "\n" .
            "📅 Vinculado: " . ($existing_config['created_at'] ?? 'N/A') . "\n\n" .
            "Para desactivar: /stop"
        );
    } else {
        send_message($chat_id, "❌ No tienes notificaciones activas. Usa /start para vincular tu cuenta.");
    }
    exit;
}

// Comando /stop
if ($text === '/stop') {
    if ($existing_config) {
        // Desactivar en base de datos
        $stmt = $pdo->prepare("UPDATE notificaciones_telegram SET activo = 0, updated_at = NOW() WHERE chat_id = ?");
        $stmt->execute([(string)$chat_id]);
        
        // Limpiar sesión
        delete_telegram_session_file($chat_id);
        
        send_message($chat_id, 
            "🔕 Notificaciones desactivadas.\n\n" .
            "Ya no recibirás alertas en este chat.\n\n" .
            "Para reactivar en el futuro, usa /start y genera una nueva API Key en tu perfil."
        );
    } else {
        send_message($chat_id, "No tienes notificaciones activas en este chat.");
    }
    exit;
}

// Comando /help
if ($text === '/help' || $text === '/ayuda') {
    $help_text = "📖 Comandos disponibles:\n\n";
    
    if ($existing_config && $existing_config['activo']) {
        $help_text .= "/start - Reiniciar o ver estado\n" .
                      "/status - Ver detalles de tu cuenta\n" .
                      "/stop - Desactivar notificaciones\n" .
                      "/help - Mostrar esta ayuda\n\n" .
                      "✅ Tu cuenta está activa.";
    } else {
        $help_text .= "/start - Vincular tu cuenta\n" .
                      "/help - Mostrar esta ayuda\n\n" .
                      "❌ No estás vinculado. Usa /start para comenzar.";
    }
    
    send_message($chat_id, $help_text);
    exit;
}

// Verificar sesión actual
$session = get_telegram_session_file($chat_id);

// Si no hay sesión y no es un comando especial, ignorar
if (!$session && !$existing_config) {
    send_message($chat_id, "Usa /start para comenzar o /help para ver ayuda.");
    exit;
}

// Si hay sesión en proceso de registro
if ($session) {
    $step = $session['step'] ?? '';
    
    // Paso 1: Esperando selección de rol
    if ($step === '' || $step === 'waiting_role') {
        if (in_array($text, ['1', '2', '3'], true)) {
            $roles = ['1' => 'admin', '2' => 'gerente', '3' => 'empleado'];
            $rol = $roles[$text];
            
            save_telegram_session_file($chat_id, ['step' => 'waiting_api_key', 'rol' => $rol, 'attempts' => 0]);
            
            send_message($chat_id,
                "✅ Rol seleccionado: " . ucfirst($rol) . "\n\n" .
                "Ahora ingresa tu API Key (64 caracteres).\n\n" .
                "📌 Genera tu API Key en:\n" .
                "Panel Admin → Ajustes → Notificaciones Telegram\n\n" .
                "Si no tienes acceso, contacta al administrador de tu empresa."
            );
        } else {
            send_message($chat_id, "Por favor selecciona: 1 (Admin), 2 (Gerente) o 3 (Empleado)");
        }
        exit;
    }
    
    // Paso 2: Esperando API Key
    if ($step === 'waiting_api_key') {
        // Sanitizar API Key
        $api_key = preg_replace('/[^a-f0-9]/', '', strtolower($text));
        
        if (strlen($api_key) !== 64) {
            $attempts = ($session['attempts'] ?? 0) + 1;
            save_telegram_session_file($chat_id, array_merge($session, ['attempts' => $attempts]));
            
            if ($attempts >= 3) {
                delete_telegram_session_file($chat_id);
                send_message($chat_id, "❌ Demasiados intentos fallidos. Usa /start para comenzar de nuevo.");
            } else {
                send_message($chat_id, "❌ API Key inválida. Debe tener 64 caracteres. Intentos restantes: " . (3 - $attempts));
            }
            exit;
        }
        
        // Buscar usuario por API key
        $usuario = find_user_by_telegram_api_key($api_key);
        
        if (!$usuario) {
            $attempts = ($session['attempts'] ?? 0) + 1;
            save_telegram_session_file($chat_id, array_merge($session, ['attempts' => $attempts]));
            
            if ($attempts >= 3) {
                delete_telegram_session_file($chat_id);
                send_message($chat_id, "❌ API Key no encontrada tras 3 intentos. Genera una nueva en tu perfil y usa /start.");
            } else {
                send_message($chat_id, "❌ API Key no encontrada. Intentos restantes: " . (3 - $attempts));
            }
            exit;
        }
        
        // Verificar que el rol coincida
        if ($usuario['rol'] !== $session['rol']) {
            send_message($chat_id, 
                "⚠️ Rol incorrecto.\n\n" .
                "Seleccionaste: " . ucfirst($session['rol']) . "\n" .
                "Tu rol real: " . ucfirst($usuario['rol']) . "\n\n" .
                "Usa /start y selecciona el rol correcto."
            );
            delete_telegram_session_file($chat_id);
            exit;
        }
        
        // Verificar plan de pago
        if (!empresa_tiene_plan_pago($usuario['empresa_id'])) {
            send_message($chat_id, 
                "❌ Plan no válido.\n\n" .
                "Tu empresa tiene un plan gratuito.\n" .
                "Las notificaciones Telegram requieren un plan de pago.\n\n" .
                "Contacta a tu administrador para actualizar el plan."
            );
            delete_telegram_session_file($chat_id);
            exit;
        }
        
        // Verificar si ya está vinculado a otro chat
        $existing = telegram_get_config_usuario($usuario['id']);
        if ($existing && $existing['activo'] && $existing['chat_id'] && $existing['chat_id'] != (string)$chat_id) {
            send_message($chat_id,
                "⚠️ Esta cuenta ya está vinculada a otro chat de Telegram.\n\n" .
                "Desvincula la cuenta anterior primero o contacta a soporte."
            );
            delete_telegram_session_file($chat_id);
            exit;
        }
        
        // Activar notificaciones
        $result = telegram_activar_usuario($usuario['id'], (string) $chat_id, $username);
        
        if ($result) {
            delete_telegram_session_file($chat_id);
            
            send_message($chat_id,
                "🎉 ¡Cuenta vinculada exitosamente!\n\n" .
                "👤 Usuario: " . htmlspecialchars($usuario['nombre']) . "\n" .
                "🏢 Empresa: " . htmlspecialchars($usuario['empresa_nombre']) . "\n" .
                "🔰 Rol: " . ucfirst($usuario['rol']) . "\n\n" .
                "📨 Recibirás alertas de:\n" .
                "• Nuevas citas\n" .
                "• Citas canceladas\n" .
                "• Citas completadas\n" .
                "• Mensajes internos\n\n" .
                "Comandos útiles:\n" .
                "/status - Ver estado\n" .
                "/stop - Desactivar\n" .
                "/help - Ayuda"
            );
        } else {
            send_message($chat_id, "❌ Error al vincular. Intenta de nuevo con /start o contacta a soporte.");
            delete_telegram_session_file($chat_id);
        }
        exit;
    }
}

// Si llegamos aquí, mensaje no reconocido
send_message($chat_id, "No entendí ese mensaje. Usa /help para ver los comandos disponibles.");
exit;

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

/**
 * Buscar usuario por API Key de Telegram
 */
function find_user_by_telegram_api_key(string $api_key): ?array {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.rol, u.empresa_id, e.nombre as empresa_nombre
        FROM notificaciones_telegram nt
        JOIN usuarios u ON u.id = nt.usuario_id
        JOIN empresas e ON e.id = u.empresa_id
        WHERE nt.api_key = ?
        AND nt.activo = 1
        AND u.activo = 1
        LIMIT 1
    ");
    $stmt->execute([$api_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ?: null;
}

/**
 * Obtener config por chat_id
 */
function telegram_get_config_by_chat_id(string $chat_id): ?array {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT nt.*, u.nombre, u.rol, e.nombre as empresa
        FROM notificaciones_telegram nt
        JOIN usuarios u ON u.id = nt.usuario_id
        JOIN empresas e ON e.id = u.empresa_id
        WHERE nt.chat_id = ?
        AND nt.activo = 1
        LIMIT 1
    ");
    $stmt->execute([$chat_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ?: null;
}

/**
 * Enviar mensaje via Telegram
 */
function send_message($chat_id, $text) {
    $token = TELEGRAM_BOT_TOKEN;
    if ($token === 'TU_BOT_TOKEN_AQUI') {
        error_log('ERROR: Token de bot no configurado');
        return false;
    }
    
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Telegram send_message error: {$error}");
        return false;
    }
    
    $data = json_decode($response, true);
    return isset($data['ok']) && $data['ok'] === true;
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
