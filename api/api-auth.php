<?php
// api/auth.php
require_once __DIR__ . '/../helpers.php';
header('Content-Type: application/json; charset=utf-8');

//session_start();

$action = $_REQUEST['action'] ?? '';

if ($action === 'request_password_reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
    }
    $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.email, u.empresa_id, u.rol, e.slug AS empresa_slug, e.nombre AS empresa_nombre
                           FROM usuarios u
                           LEFT JOIN empresas e ON e.id = u.empresa_id
                           WHERE LOWER(u.email) = LOWER(?) AND u.activo = 1
                           LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$u) {
        json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
    }

    $token = create_password_reset_token((int) $u['id'], 30);
    if (!$token) {
        json_response(['success' => false, 'message' => 'No se pudo generar el enlace de recuperación.'], 500);
    }
    $empresa_ref = trim((string) ($u['empresa_slug'] ?? '')) !== '' ? (string) $u['empresa_slug'] : (string) ((int) ($u['empresa_id'] ?? 0));
    $reset_url = app_url_absolute(view_url('vistas/public/login.php', $empresa_ref))
        . '&recover=1&token=' . rawurlencode($token);
    send_password_reset_email($u, $reset_url);
    json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
}

if ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string) ($_POST['token'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($token === '' || strlen($password) < 6) {
        json_response(['success' => false, 'message' => 'Token o contraseña inválidos.'], 400);
    }
    $row = find_password_reset_token($token);
    if (!$row) {
        json_response(['success' => false, 'message' => 'Enlace inválido.'], 400);
    }
    if (!empty($row['used_at'])) {
        json_response(['success' => false, 'message' => 'Este enlace ya fue utilizado.'], 400);
    }
    if (strtotime((string) ($row['expires_at'] ?? '')) < time()) {
        json_response(['success' => false, 'message' => 'El enlace expiró.'], 400);
    }

    try {
        $pdo->beginTransaction();
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE usuarios SET password_hash = ?, session_token = NULL WHERE id = ?")->execute([$newHash, (int) $row['usuario_id']]);
        $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?")->execute([(int) $row['id']]);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => 'No se pudo actualizar la contraseña.'], 500);
    }
}

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
