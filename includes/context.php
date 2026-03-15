<?php
// includes/context.php
// Contexto de empresa determinado exclusivamente por la URL. Nunca lee ni escribe sesión.

function get_empresa_from_url()
{
    global $pdo;

    $param = $_GET['empresa'] ?? $_GET['id_e'] ?? null;
    if ($param === null || $param === '') {
        return null;
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
    $publicos = ['inicio', 'login', 'blog', 'sedes', 'ver-sedes', 'citas', '404', 'landing', 'register'];
    if (in_array($m, $publicos)) {
        return true;
    }
    // También detectar por ruta si el módulo aún no está definido
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($uri, '/vistas/public/') !== false;
}