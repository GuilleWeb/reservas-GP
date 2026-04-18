<?php
/**
 * API para configuración de Telegram por usuario
 * - Generar API key
 - Obtener estado
 * - Desactivar notificaciones
 */
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$role = $user['rol'] ?? 'cliente';
$usuario_id = (int) ($user['id'] ?? 0);
$empresa_id = (int) ($user['empresa_id'] ?? 0);

// Solo admin, gerente y empleado pueden usar esta API
if (!in_array($role, ['superadmin', 'admin', 'gerente', 'empleado'])) {
    json_response(['success' => false, 'message' => 'Acceso denegado'], 403);
}

if ($usuario_id <= 0) {
    json_response(['success' => false, 'message' => 'Usuario no válido'], 400);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_status':
        // Verificar si el usuario tiene plan de pago
        $tiene_plan_pago = empresa_tiene_plan_pago($empresa_id);
        
        if (!$tiene_plan_pago) {
            json_response(['success' => true, 'active' => false, 'message' => 'Requiere plan de pago']);
        }
        
        $config = telegram_get_config_usuario($usuario_id);
        
        if ($config && $config['activo'] && !empty($config['chat_id'])) {
            json_response([
                'success' => true, 
                'active' => true,
                'api_key' => $config['api_key'],
                'telegram_username' => $config['telegram_username'],
                'alertas' => $config['alertas_config']
            ]);
        } else {
            json_response(['success' => true, 'active' => false]);
        }
        break;

    case 'generate_api_key':
        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($csrf)) {
            json_response(['success' => false, 'message' => 'Token inválido'], 403);
        }
        
        // Verificar plan de pago
        if (!empresa_tiene_plan_pago($empresa_id)) {
            json_response(['success' => false, 'message' => 'Las notificaciones de Telegram solo están disponibles para planes de pago'], 403);
        }
        
        // Verificar si ya tiene config
        $config = telegram_get_config_usuario($usuario_id);
        
        if ($config && !empty($config['api_key'])) {
            // Ya tiene API key, la devolvemos
            json_response([
                'success' => true,
                'api_key' => $config['api_key'],
                'message' => 'Ya tienes una API Key generada'
            ]);
        } else {
            // Generar nueva API key
            $api_key = generar_api_key_telegram();
            
            $stmt = $pdo->prepare("INSERT INTO notificaciones_telegram 
                (usuario_id, api_key, activo, alertas_config, created_at, updated_at) 
                VALUES (?, ?, 1, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                api_key = VALUES(api_key),
                activo = 1,
                updated_at = NOW()");
            
            $alertas_default = json_encode([
                'cita_nueva' => true,
                'cita_cancelada' => true,
                'cita_completada' => true,
                'cita_auto_completada' => true,
                'mensaje_interno' => true
            ]);
            
            $stmt->execute([$usuario_id, $api_key, $alertas_default]);
            
            json_response([
                'success' => true,
                'api_key' => $api_key,
                'message' => 'API Key generada correctamente'
            ]);
        }
        break;

    case 'deactivate':
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($csrf)) {
            json_response(['success' => false, 'message' => 'Token inválido'], 403);
        }
        
        $resultado = telegram_desactivar_usuario($usuario_id);
        
        if ($resultado) {
            json_response(['success' => true, 'message' => 'Notificaciones desactivadas']);
        } else {
            json_response(['success' => false, 'message' => 'Error al desactivar'], 500);
        }
        break;

    case 'update_alertas':
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($csrf)) {
            json_response(['success' => false, 'message' => 'Token inválido'], 403);
        }
        
        $alertas = $_POST['alertas'] ?? [];
        $alertas_permitidas = [
            'cita_nueva' => !empty($alertas['cita_nueva']),
            'cita_cancelada' => !empty($alertas['cita_cancelada']),
            'cita_completada' => !empty($alertas['cita_completada']),
            'cita_auto_completada' => !empty($alertas['cita_auto_completada']),
            'mensaje_interno' => !empty($alertas['mensaje_interno'])
        ];
        
        $resultado = telegram_set_alertas($usuario_id, $alertas_permitidas);
        
        if ($resultado) {
            json_response(['success' => true, 'message' => 'Preferencias actualizadas']);
        } else {
            json_response(['success' => false, 'message' => 'Error al actualizar'], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Acción no válida'], 400);
}
