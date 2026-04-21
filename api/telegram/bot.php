<?php
/**
 * Webhook del Bot Telegram - SOLO PARA SUPERADMIN
 * 
 * Este bot envía alertas del sistema al superadmin.
 * Ya NO procesa vinculaciones de empresas (eliminado).
 * 
 * Configuración: El superadmin debe configurar el Chat ID en 
                Ajustes → Notificaciones del Sistema
 */

require_once __DIR__ . '/../../helpers.php';

// Token del bot - usar variable de entorno o definir aquí
$bot_token = getenv('TELEGRAM_BOT_TOKEN') ?: 'TU_BOT_TOKEN_AQUI';

header('Content-Type: application/json');

// Solo responder a webhook de Telegram, no procesar mensajes de usuarios
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si es un mensaje de un usuario, responder que solo es para superadmin
if (isset($data['message'])) {
    $chat_id = $data['message']['chat']['id'] ?? null;
    $text = trim($data['message']['text'] ?? '');
    
    if ($chat_id) {
        // Mensaje informativo: el bot ya no sirve para empresas
        $mensaje = "� <b>Bot de Notificaciones - Reservas GP</b>\n\n";
        $mensaje .= "Este bot ya no está disponible para usuarios de empresas.\n\n";
        $mensaje .= "📧 <b>Soporte:</b> Contacta a tu administrador por el sistema.\n\n";
        $mensaje .= "Este bot solo envía alertas del sistema al administrador.";
        
        telegram_send_simple($bot_token, $chat_id, $mensaje);
    }
    
    echo json_encode(['ok' => true]);
    exit;
}

// Si es otro tipo de update (callback, etc.), solo responder OK
echo json_encode(['ok' => true]);
exit;

/**
 * Enviar mensaje simple via Telegram
 */
function telegram_send_simple($token, $chat_id, $text) {
    if ($token === 'TU_BOT_TOKEN_AQUI' || empty($token)) {
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
    curl_close($ch);
    
    return $response !== false;
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
