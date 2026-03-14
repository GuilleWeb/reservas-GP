<?php
require_once __DIR__ . '/../app/bootstrap.php';

$action = $_REQUEST['action'] ?? '';

function v2_base_path()
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim($scriptDir, '/');
    if ($scriptDir === '')
        return '';
    // if called from /v2/api, go up one
    if (preg_match('~/v2/api$~', $scriptDir)) {
        return preg_replace('~/api$~', '', $scriptDir);
    }
    // if called from /v2, keep
    return $scriptDir;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        json_response(['error' => 'missing_fields'], 400);
    }

    $stmt = $pdo->prepare('SELECT id, nombre, email, password_hash, rol, empresa_id, sucursal_id, activo, session_token FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        json_response(['error' => 'invalid_credentials'], 401);
    }

    if (empty($user['activo'])) {
        json_response(['error' => 'inactive'], 403);
    }

    // allow_login global (si existe la tabla/clave). Si falla, se asume permitido.
    if (($user['rol'] ?? null) !== 'superadmin') {
        try {
            $stmtAj = $pdo->prepare("SELECT valor_json FROM ajustes_globales WHERE clave = 'allow_login' LIMIT 1");
            $stmtAj->execute();
            $allowLoginVal = $stmtAj->fetchColumn();
            $allowLogin = ($allowLoginVal !== false) ? trim((string) $allowLoginVal, '"') : '1';
            if ($allowLogin === '0') {
                json_response(['error' => 'login_disabled', 'message' => 'El inicio de sesión está desactivado de momento.'], 403);
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    $force = (string) ($_POST['force_login'] ?? '0');
    if (!empty($user['session_token']) && $user['session_token'] !== session_id() && $force !== '1') {
        json_response(['error' => 'session_active', 'message' => 'Ya tienes una sesión activa en otro dispositivo. ¿Deseas cerrarla e ingresar desde aquí?'], 409);
    }

    $stmtSt = $pdo->prepare('UPDATE usuarios SET session_token = ? WHERE id = ?');
    $stmtSt->execute([session_id(), (int) $user['id']]);

    // Validar empresa/sucursal activas si aplica
    $id_e = null;
    $sucursal_slug = null;

    if (!empty($user['empresa_id'])) {
        $stmtEmp = $pdo->prepare('SELECT id, slug, activo FROM empresas WHERE id = ? LIMIT 1');
        $stmtEmp->execute([(int) $user['empresa_id']]);
        $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
        if (!$emp || empty($emp['activo'])) {
            json_response(['error' => 'inactive_empresa', 'message' => 'La empresa a la que perteneces se encuentra desactivada.'], 403);
        }
        $id_e = $emp['slug'] ?: null;
    }

    if (!empty($user['sucursal_id'])) {
        $stmtSuc = $pdo->prepare('SELECT id, slug, activo FROM sucursales WHERE id = ? LIMIT 1');
        $stmtSuc->execute([(int) $user['sucursal_id']]);
        $suc = $stmtSuc->fetch(PDO::FETCH_ASSOC);
        if (!$suc || empty($suc['activo'])) {
            json_response(['error' => 'inactive_sucursal', 'message' => 'La sucursal a la que perteneces se encuentra desactivada.'], 403);
        }
        $sucursal_slug = $suc['slug'] ?: null;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'nombre' => $user['nombre'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'empresa_id' => $user['empresa_id'] ? (int) $user['empresa_id'] : null,
        'id_e' => $id_e,
        'sucursal_id' => $user['sucursal_id'] ? (int) $user['sucursal_id'] : null,
        'sucursal_slug' => $sucursal_slug,
        'activo' => (int) $user['activo'],
    ];

    $base = v2_base_path();
    $q_slug = $id_e ? ('?id_e=' . rawurlencode((string) $id_e)) : '';
    $redirect = $base . '/public/inicio.php' . $q_slug;

    switch ($user['rol']) {
        case 'superadmin':
            $redirect = $base . '/sadmin/dashboard.php';
            break;
        case 'admin':
        case 'gerente':
        case 'empleado':
            $redirect = $base . '/admin/dashboard.php' . $q_slug;
            break;
        case 'cliente':
            $redirect = $base . '/cadmin/citas.php' . $q_slug;
            break;
        default:
            $redirect = $base . '/public/inicio.php' . $q_slug;
            break;
    }

    json_response(['success' => true, 'user' => $_SESSION['user'], 'redirect_url' => $redirect]);
}

if ($action === 'logout') {
    $u = current_user();
    if ($u && !empty($u['id'])) {
        try {
            $stmt = $pdo->prepare('UPDATE usuarios SET session_token = NULL WHERE id = ?');
            $stmt->execute([(int) $u['id']]);
        } catch (Throwable $e) {
            // ignore
        }
    }

    session_unset();
    session_destroy();
    json_response(['success' => true]);
}

json_response(['error' => 'invalid_action'], 400);
