<?php
// api/auth.php
require_once __DIR__ . '/../helpers.php';
header('Content-Type: application/json; charset=utf-8');
ensure_users_email_verified_column();

//session_start();

$action = $_REQUEST['action'] ?? '';

if ($action === 'request_password_reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email_identity((string) ($_POST['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
    }
    $guard = $email . '|' . (client_ip() ?: 'no-ip');
    if (request_guard_is_limited('password_reset_request', $guard, 120, 1, true)) {
        json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
    }

    $u = find_user_by_login_email($email);
    if (!$u || (int) ($u['activo'] ?? 0) !== 1) {
        json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
    }
    $u['empresa_slug'] = null;
    $u['empresa_nombre'] = null;
    if ((int) ($u['empresa_id'] ?? 0) > 0) {
        $stEmp = $pdo->prepare("SELECT slug, nombre FROM empresas WHERE id = ? LIMIT 1");
        $stEmp->execute([(int) $u['empresa_id']]);
        $empRow = $stEmp->fetch(PDO::FETCH_ASSOC) ?: [];
        $u['empresa_slug'] = $empRow['slug'] ?? null;
        $u['empresa_nombre'] = $empRow['nombre'] ?? null;
    }
    if (empty($u['email_verified_at'])) {
        json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
    }

    $token = create_password_reset_token((int) $u['id'], 30);
    if (!$token) {
        json_response(['success' => false, 'message' => 'No se pudo generar el enlace de recuperación.'], 500);
    }
    $empresa_ref = trim((string) ($u['empresa_slug'] ?? '')) !== '' ? (string) $u['empresa_slug'] : (string) ((int) ($u['empresa_id'] ?? 0));
    $reset_url = url_add_query(
        app_url_absolute(view_url('vistas/public/login.php', $empresa_ref)),
        ['recover' => '1', 'token' => $token]
    );
    send_password_reset_email($u, $reset_url);
    json_response(['success' => true, 'message' => 'Si el correo existe, enviaremos un enlace de recuperación.']);
}

if ($action === 'verify_email') {
    $token = trim((string) ($_REQUEST['token'] ?? ''));
    if ($token === '') {
        json_response(['success' => false, 'message' => 'Token de verificación inválido.'], 400);
    }
    $row = consume_email_verification_token($token);
    if (!$row) {
        json_response(['success' => false, 'message' => 'El enlace de verificación no es válido o ya expiró.'], 400);
    }
    json_response(['success' => true, 'message' => 'Correo verificado correctamente. Ya puedes iniciar sesión.']);
}

if ($action === 'resend_verification' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email_identity((string) ($_POST['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => true, 'message' => 'Si la cuenta existe, enviaremos el correo de verificación.']);
    }
    $guard = $email . '|' . (client_ip() ?: 'no-ip');
    if (request_guard_is_limited('resend_verification', $guard, 120, 1, true)) {
        json_response(['success' => true, 'message' => 'Si la cuenta existe, enviaremos el correo de verificación.']);
    }
    $u = find_user_by_login_email($email);
    if (!$u || empty($u['id']) || !empty($u['email_verified_at'])) {
        json_response(['success' => true, 'message' => 'Si la cuenta existe, enviaremos el correo de verificación.']);
    }
    $empresa_nombre = 'Reservas GP';
    $empresa_slug = '';
    if ((int) ($u['empresa_id'] ?? 0) > 0) {
        $stEmp = $pdo->prepare("SELECT nombre, slug FROM empresas WHERE id = ? LIMIT 1");
        $stEmp->execute([(int) $u['empresa_id']]);
        $emp = $stEmp->fetch(PDO::FETCH_ASSOC) ?: [];
        $empresa_nombre = (string) ($emp['nombre'] ?? $empresa_nombre);
        $empresa_slug = (string) ($emp['slug'] ?? '');
    }
    $token = create_email_verification_token((int) $u['id'], 1440);
    if ($token) {
        $empresa_ref = $empresa_slug !== '' ? $empresa_slug : (string) ((int) ($u['empresa_id'] ?? 0));
        $verify_url = url_add_query(app_url_absolute(view_url('vistas/public/login.php', $empresa_ref)), ['verify' => '1', 'token' => $token]);
        send_email_verification_email([
            'id' => (int) $u['id'],
            'nombre' => (string) ($u['nombre'] ?? ''),
            'email' => (string) ($u['email'] ?? ''),
            'empresa_id' => (int) ($u['empresa_id'] ?? 0),
            'empresa_nombre' => $empresa_nombre,
        ], $verify_url);
    }
    json_response(['success' => true, 'message' => 'Si la cuenta existe, enviaremos el correo de verificación.']);
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
    $email = normalize_email_identity((string) ($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        json_response(['error' => 'missing_fields'], 400);
    }

    // Buscar usuario por email (normaliza alias de Gmail).
    $user = find_user_by_login_email($email);

    // Verificar usuario y contraseña
    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response(['error' => 'invalid_credentials'], 401);
    }

    // Verificar si usuario está activo
    if (!$user['activo']) {
        if (($user['rol'] ?? '') !== 'superadmin' && empty($user['email_verified_at'])) {
            json_response(['error' => 'email_not_verified', 'message' => 'Debes verificar tu correo antes de ingresar.'], 403);
        }
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
        } elseif ($user['rol'] === 'empleado') {
            $redirect_url = $base_path . '/vistas/empleado/dashboard.php' . $tenant_query;
        } elseif ($user['rol'] === 'cliente') {
            // Panel de cliente desactivado: el cliente agenda/consulta desde flujo público.
            $empresa_slug = (string) ($id_e ?: (string) $empresa_id);
            $redirect_url = view_url('vistas/public/citas.php', $empresa_slug);
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
