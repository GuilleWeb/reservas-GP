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

// Login / auth helpers
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

    // Check single session
    global $pdo;
    $stmt = $pdo->prepare("SELECT session_token FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user']['id']]);
    $db_token = $stmt->fetchColumn();

    if (!empty($db_token) && $db_token !== session_id()) {
        // La sesión ha sido tomada por otro dispositivo
        session_unset();
        session_destroy();
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        // si es en api, subimos un nivel
        if (strpos($base, '/api') !== false) {
            $base = dirname($base);
        }
        setcookie('session_msg', 'Tu sesión ha sido cerrada porque se ingresó desde otro dispositivo.', time() + 10, '/');
        return null;
    }

    return $_SESSION['user'];
}

// permission check
function has_permission($perm_name)
{
    // perm_name: 'permiso_crear', 'permiso_leer', 'permiso_actualizar', 'permiso_borrar'
    $user = current_user();
    if (!$user)
        return false;
    if (!empty($user['rol']) && $user['rol'] === 'superadmin')
        return true;

    // Nuevo esquema: roles por ENUM en usuarios
    if (!empty($user['rol'])) {
        if ($user['rol'] === 'superadmin')
            return true;
        if ($user['rol'] === 'admin')
            return true;
        if ($user['rol'] === 'gerente')
            return true;
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT $perm_name FROM roles WHERE id = ? LIMIT 1");
    $stmt->execute([$user['rol_id']]);
    $r = $stmt->fetch();
    return !empty($r[$perm_name]);
}

// simple CSRF token - for AJAX include header X-CSRF-Token
// helpers.php

function generate_csrf()
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function request_id_e()
{
    // Si no viene en request pero hay sesión con empresa_id, usarlo
    if (isset($_SESSION['user']['empresa_id'])) {
        $id = $_SESSION['user']['empresa_id'];
    } else {
        $id = $_GET['id_e'] ?? $_POST['id_e'] ?? null;
    }
    return $id ? (int) $id : null;
}

function app_root_path()
{
    $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $sn = '/' . ltrim($sn, '/');
    $parts = array_values(array_filter(explode('/', $sn), fn($p) => $p !== ''));
    if (empty($parts))
        return '';
    return '/' . $parts[0];
}

function app_url($path)
{
    $path = ltrim((string) $path, '/');
    return rtrim(app_root_path(), '/') . '/' . $path;
}

function app_link_with_slug($path, $id_e)
{
    if (!$path)
        return '#';
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
    if ($id === null || $id === '')
        return null;
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

        $metadata_json = null;
        if ($metadata !== null) {
            $metadata_json = json_encode($metadata, JSON_UNESCAPED_UNICODE);
        }

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
