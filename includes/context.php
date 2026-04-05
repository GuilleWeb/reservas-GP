<?php
// includes/context.php
// Contexto de empresa determinado exclusivamente por la URL. Nunca lee ni escribe sesión.

function get_empresa_from_url()
{
    global $pdo;

    $param = $_GET['empresa'] ?? $_GET['id_e'] ?? null;
    if ($param === null || $param === '') {
        // Intentar extraer slug desde la URL bonita: /{slug}/...
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH) ?: '';
        $base = app_root_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = trim((string) $path, '/');
        if ($path !== '') {
            $parts = explode('/', $path);
            $candidate = $parts[0] ?? '';
            $reserved = ['vistas', 'api', 'assets', 'includes', 'uploads', 'vendor'];
            if ($candidate !== '' && !in_array(strtolower($candidate), $reserved, true)) {
                $param = $candidate;
            }
        }
        if ($param === null || $param === '') {
            return null;
        }
    }
    $param = trim($param);

    if (!is_numeric($param)) {
        if (isset($_GET['id_e'])) {
            error_log('[context] URL antigua detectada: usar ?empresa=' . $param . ' en lugar de ?id_e=' . $param);
        }
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$param]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([(int) $param]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function get_current_empresa()
{
    if (!isset($GLOBALS['empresa_info'])) {
        $GLOBALS['empresa_info'] = get_empresa_from_url();
    }
    return $GLOBALS['empresa_info'];
}

function get_empresa_id()
{
    $e = get_current_empresa();
    return $e ? (int) $e['id'] : null;
}

function get_empresa_slug()
{
    $e = get_current_empresa();
    return $e ? ($e['slug'] ?? null) : null;
}

function get_effective_role($user, $uri = null)
{
    if (!$user) {
        return null;
    }

    $role = $user['rol'] ?? null;
    $uri = $uri ?? $_SERVER['REQUEST_URI'] ?? '';

    // Roles que no heredan hacia abajo: aplican siempre su rol real
    if (!in_array($role, ['superadmin', 'admin', 'gerente'])) {
        return $role;
    }

    $empresa = get_current_empresa();

    // Sin empresa en URL: rol real sin cambios
    if (!$empresa) {
        return $role;
    }

    // Con empresa en URL: determinar rol efectivo por la ruta
    if (strpos($uri, '/sucursal/') !== false || strpos($uri, '/empleado/') !== false) {
        if (in_array($role, ['superadmin', 'admin', 'gerente'])) {
            return strpos($uri, '/empleado/') !== false ? 'empleado' : 'gerente';
        }
    }

    if (strpos($uri, '/admin/') !== false) {
        if (in_array($role, ['superadmin', 'admin'])) {
            return 'admin';
        }
    }

    if (strpos($uri, '/cliente/') !== false) {
        return 'cliente';
    }

    return $role;
}

function is_public_view($module = null)
{
    $m = $module ?? ($GLOBALS['module'] ?? '');
    // Eliminar el prefijo " | " que agregan los topbars
    $m = ltrim($m, ' |');
    $publicos = ['inicio', 'login', 'blog', 'sedes', 'ver-sedes', 'servicios', 'citas', '404', 'landing', 'register', 'resena'];
    if (in_array($m, $publicos)) {
        return true;
    }
    // También detectar por ruta si el módulo aún no está definido
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/vistas/public/') !== false) {
        return true;
    }
    $path = parse_url($uri, PHP_URL_PATH) ?: '';
    $base = app_root_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = trim($path, '/');
    if ($path === '') {
        return true;
    }
    $parts = explode('/', $path);
    $reserved = ['vistas', 'api', 'assets', 'includes', 'uploads', 'vendor'];
    if (in_array(strtolower($parts[0] ?? ''), $reserved, true)) {
        return false;
    }
    $page = $parts[1] ?? '';
    if ($page === '') {
        return true; // /{slug}
    }
    $allowed = ['inicio', 'sedes', 'servicios', 'citas', 'blog', 'login', 'resena'];
    return in_array(strtolower($page), $allowed, true);
}
