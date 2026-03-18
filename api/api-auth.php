<?php
// api/auth.php
require_once __DIR__ . '/../helpers.php';
header('Content-Type: application/json; charset=utf-8');

//session_start();

$action = $_REQUEST['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        json_response(['error' => 'missing_fields'], 400);
    }

    // Buscar usuario por email
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar usuario y contraseña
    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response(['error' => 'invalid_credentials'], 401);
    }

    // Verificar si usuario está activo
    if (!$user['activo']) {
        json_response(['error' => 'inactive'], 403);
    }

    // Verificar si el login global está desactivado (excepto superadmin)
    if ($user['rol'] !== 'superadmin') {
        $stmt_aj = $pdo->prepare("SELECT valor_json FROM ajustes_globales WHERE clave = 'allow_login'");
        $stmt_aj->execute();
        $allow_login_val = $stmt_aj->fetchColumn();
        $allow_login = ($allow_login_val !== false) ? trim($allow_login_val, '"') : '1';

        if ($allow_login === '0') {
            json_response(['error' => 'login_disabled', 'message' => 'El inicio de sesión está desactivado de momento.'], 403);
        }
    }

    // Single session control
    $force_login = $_POST['force_login'] ?? '0';
    if (!empty($user['session_token']) && $user['session_token'] !== session_id() && $force_login !== '1') {
        json_response(['error' => 'session_active', 'message' => 'Ya tienes una sesión activa en otro dispositivo. ¿Deseas cerrarla e ingresar desde aquí?'], 409);
    }

    // Actualizar session_token del nuevo dispositivo
    $new_token = session_id();
    $stmt_st = $pdo->prepare("UPDATE usuarios SET session_token = ? WHERE id = ?");
    $stmt_st->execute([$new_token, $user['id']]);

    $id_e = null;
    $empresa_id = null;
    $sucursal_slug = null;

    if (!empty($user['empresa_id'])) {
        $stmt = $pdo->prepare('SELECT slug, activo FROM empresas WHERE id = ? LIMIT 1');
        $stmt->execute([$user['empresa_id']]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$emp || !$emp['activo']) {
            json_response(['error' => 'inactive_empresa', 'message' => 'La empresa a la que perteneces se encuentra desactivada.'], 403);
        }
        $empresa_id = (int) $user['empresa_id'];
        $id_e = $emp['slug'] ?: null;
    }

    if (!empty($user['sucursal_id'])) {
        $stmt = $pdo->prepare('SELECT slug, activo FROM sucursales WHERE id = ? LIMIT 1');
        $stmt->execute([$user['sucursal_id']]);
        $suc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$suc || !$suc['activo']) {
            json_response(['error' => 'inactive_sucursal', 'message' => 'La sucursal a la que perteneces se encuentra desactivada.'], 403);
        }
        $sucursal_slug = $suc['slug'] ?: null;
    }

    // Establecer sesión
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nombre' => $user['nombre'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'empresa_id' => $user['empresa_id'],
        'sucursal_id' => $user['sucursal_id'],
        'activo' => $user['activo'],
        'foto_path' => $user['foto_path']
    ];

    // Fix para base_path en Windows
    $script_path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
    if ($script_path === '/')
        $script_path = '';
    $base_path = rtrim($script_path, '/');

    $redirect_url = $base_path . '/vistas/public/landing.php';

    if ($user['rol'] === 'superadmin') {
        $redirect_url = $base_path . '/vistas/superadmin/dashboard.php';
    } elseif ($empresa_id) {
        $tenant_query = '?id_e=' . urlencode((string) $empresa_id);
        if ($user['rol'] === 'admin') {
            $redirect_url = $base_path . '/vistas/admin/dashboard.php' . $tenant_query;
        } elseif ($user['rol'] === 'gerente') {
            $redirect_url = $base_path . '/vistas/sucursal/dashboard.php' . $tenant_query;
        } elseif ($user['rol'] === 'empleado' || $user['rol'] === 'cliente') {
            $target = ($user['rol'] === 'empleado') ? 'empleado' : 'cliente';
            $redirect_url = $base_path . '/vistas/' . $target . '/dashboard.php' . $tenant_query;
        } else {
            $redirect_url = $base_path . '/vistas/admin/dashboard.php' . $tenant_query;
        }
    }

    json_response(['success' => true, 'user' => $_SESSION['user'], 'redirect_url' => $redirect_url]);
}

// Logout
if ($action === 'logout') {
    session_start();
    session_unset();
    session_destroy();
    json_response(['success' => true]);
}

json_response(['error' => 'invalid_action'], 400);
