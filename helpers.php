<?php
// helpers.php
session_start();
require_once __DIR__ . '/conexion.php';

function json_response($data, $code = 200)
{
    header_remove();
    header("Content-Type: application/json; charset=utf-8");
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function login_user_by_id($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol_id, activo FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function require_login()
{
    if (empty($_SESSION['user'])) {
        json_response(['error' => 'no_auth'], 401);
    }
    return $_SESSION['user'];
}

function current_user()
{
    if (empty($_SESSION['user'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT session_token FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user']['id']]);
    $db_token = $stmt->fetchColumn();

    if (!empty($db_token) && $db_token !== session_id()) {
        session_unset();
        session_destroy();
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if (strpos($base, '/api') !== false) {
            $base = dirname($base);
        }
        setcookie('session_msg', 'Tu sesión ha sido cerrada porque se ingresó desde otro dispositivo.', time() + 10, '/');
        return null;
    }

    return $_SESSION['user'];
}

function has_permission($perm_name, $effective_role = null)
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    $role = $effective_role ?? ($user['rol'] ?? null);

    if ($role === 'superadmin') {
        return true;
    }
    if ($role === 'admin') {
        return true;
    }
    if ($role === 'gerente') {
        return true;
    }

    // Fallback a tabla de roles si tiene rol_id y no tiene rol ENUM
    if (!empty($user['rol_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT $perm_name FROM roles WHERE id = ? LIMIT 1");
        $stmt->execute([$user['rol_id']]);
        $r = $stmt->fetch();
        return !empty($r[$perm_name]);
    }

    return false;
}

function generate_csrf()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function verify_csrf($token)
{
    return check_csrf((string) ($token ?? ''));
}

// Contexto de empresa determinado exclusivamente por la URL.
// Para vistas públicas: ?id_e=slug o ?empresa=slug
// Para vistas privadas: ?id_e=123 (numérico)
// Nunca lee $_SESSION para determinar empresa de contexto.
function request_id_e()
{
    $id = $_GET['id_e'] ?? $_POST['id_e'] ?? null;
    if ($id === null || $id === '') {
        return null;
    }
    // Solo retorna entero si es numérico; vistas privadas siempre pasan ID numérico
    return is_numeric($id) ? (int) $id : null;
}

function app_root_path()
{
    $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $sn = '/' . ltrim($sn, '/');
    $parts = array_values(array_filter(explode('/', $sn), fn($p) => $p !== ''));
    if (empty($parts)) {
        return '';
    }
    return '/' . $parts[0];
}

function app_url($path)
{
    $path = ltrim((string) $path, '/');
    return rtrim(app_root_path(), '/') . '/' . $path;
}

function app_link_with_slug($path, $id_e)
{
    if (!$path) {
        return '#';
    }
    $url = app_url($path);
    if ($id_e) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'id_e=' . rawurlencode((string) $id_e);
    }
    return $url;
}

function view_url($path, $id_e = null)
{
    return app_link_with_slug($path, $id_e);
}

function request_sucursal_slug()
{
    $slug = $_GET['_sucursal'] ?? ($_GET['sucursal_slug'] ?? ($_POST['sucursal_slug'] ?? null));
    $slug = is_string($slug) ? trim($slug) : null;
    return $slug ?: null;
}

function request_sucursal_id()
{
    $id = $_GET['sucursal_id'] ?? ($_POST['sucursal_id'] ?? null);
    if ($id === null || $id === '') {
        return null;
    }
    return intval($id);
}

function tenant_context()
{
    return [
        'id_e' => request_id_e(),
        'sucursal_slug' => request_sucursal_slug(),
        'sucursal_id' => request_sucursal_id(),
    ];
}

function client_ip()
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    if (is_string($ip)) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return $ip ?: null;
}

function audit_event($tipo, $entidad, $entidad_id = null, $descripcion = null, $empresa_id = null, $metadata = null, $force = false)
{
    global $pdo;
    try {
        $user = current_user();
        $actor_user_id = $user['id'] ?? null;
        $actor_rol = $user['rol'] ?? null;

        $allowed_roles = ['superadmin', 'admin'];
        if (!$force) {
            if (!$actor_rol || !in_array($actor_rol, $allowed_roles, true)) {
                return false;
            }
        }

        $metadata_json = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare('INSERT INTO auditoria_eventos (empresa_id, actor_usuario_id, actor_rol, tipo, entidad, entidad_id, descripcion, metadata_json, ip) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $empresa_id,
            $actor_user_id,
            $actor_rol,
            (string) $tipo,
            (string) $entidad,
            $entidad_id ? (int) $entidad_id : null,
            $descripcion,
            $metadata_json,
            client_ip(),
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}