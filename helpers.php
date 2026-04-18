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

function request_id_e_from_referer()
{
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($ref === '') {
        return null;
    }
    $q = parse_url($ref, PHP_URL_QUERY);
    if (!$q) {
        return null;
    }
    parse_str($q, $params);
    $id = $params['id_e'] ?? null;
    if ($id === null || $id === '') {
        $id = $params['empresa'] ?? null;
    }
    if ($id === null || $id === '') {
        return null;
    }
    return is_numeric($id) ? (int) $id : null;
}

function can_act_as_role($actor_role, $target_role)
{
    $actor_role = (string) ($actor_role ?? '');
    $target_role = (string) ($target_role ?? '');
    if ($actor_role === '' || $target_role === '') {
        return false;
    }
    $matrix = [
        'superadmin' => ['superadmin', 'admin', 'gerente', 'empleado', 'cliente'],
        'admin' => ['admin', 'gerente', 'empleado'],
        'gerente' => ['gerente', 'empleado'],
        'empleado' => ['empleado'],
        'cliente' => ['cliente'],
    ];
    return in_array($target_role, $matrix[$actor_role] ?? [$actor_role], true);
}

// Resuelve empresa de contexto para vistas/APIs privadas.
// - superadmin puede actuar sobre otra empresa usando ?id_e={empresa_id}
// - otros roles usan su empresa_id de sesión (y no pueden cambiarla por URL)
function resolve_private_empresa_id($user = null)
{
    global $pdo;

    if (!$user) {
        return 0;
    }

    $role = (string) ($user['rol'] ?? '');
    $session_empresa_id = (int) ($user['empresa_id'] ?? 0);
    $requested_empresa_id = request_id_e();
    $requested_empresa_id = (int) ($requested_empresa_id ?? 0);

    if ($role === 'superadmin') {
        if ($requested_empresa_id <= 0) {
            $requested_empresa_id = (int) (request_id_e_from_referer() ?? 0);
        }
        if ($requested_empresa_id > 0) {
            $stmt = $pdo->prepare('SELECT id FROM empresas WHERE id = ? AND activo = 1 LIMIT 1');
            $stmt->execute([$requested_empresa_id]);
            return (int) ($stmt->fetchColumn() ?: 0);
        }
        return max(0, $session_empresa_id);
    }

    if ($session_empresa_id <= 0) {
        return 0;
    }

    // Evita que roles no superadmin cambien de tenant forzando id_e en URL.
    if ($requested_empresa_id > 0 && $requested_empresa_id !== $session_empresa_id) {
        return 0;
    }

    return $session_empresa_id;
}

function project_root_path()
{
    static $root = null;
    if ($root === null) {
        $root = realpath(__DIR__) ?: __DIR__;
    }
    return $root;
}

function project_path($path = '')
{
    $root = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, project_root_path()), DIRECTORY_SEPARATOR);
    $path = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, (string) $path), DIRECTORY_SEPARATOR);
    return $path === '' ? $root : $root . DIRECTORY_SEPARATOR . $path;
}

function app_root_path()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $script_name = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $script_file = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));

    if ($script_name !== '' && $script_file !== '') {
        $project_root = str_replace('\\', '/', project_root_path());
        $project_root = rtrim($project_root, '/');
        $script_dir_fs = str_replace('\\', '/', dirname($script_file));
        $script_dir_fs = rtrim($script_dir_fs, '/');

        if (
            $project_root !== ''
            && str_starts_with(strtolower($script_dir_fs), strtolower($project_root))
        ) {
            $relative_dir = trim(substr($script_dir_fs, strlen($project_root)), '/');
            $script_dir_url = str_replace('\\', '/', dirname($script_name));
            $script_dir_url = $script_dir_url === '.' ? '' : rtrim($script_dir_url, '/');

            if ($relative_dir !== '') {
                $suffix = '/' . $relative_dir;
                if (str_ends_with(strtolower($script_dir_url), strtolower($suffix))) {
                    $script_dir_url = substr($script_dir_url, 0, -strlen($suffix));
                }
            }

            $script_dir_url = trim((string) $script_dir_url, '/');
            $cached = $script_dir_url === '' ? '' : '/' . $script_dir_url;
            return $cached;
        }
    }

    // Fallback por heurística para contextos atípicos.
    $sn = '/' . ltrim($script_name, '/');
    $parts = array_values(array_filter(explode('/', $sn), static fn($p) => $p !== ''));
    if (empty($parts)) {
        $cached = '';
        return $cached;
    }

    $first = $parts[0];
    $known_project_dirs = ['api', 'assets', 'includes', 'uploads', 'vistas'];
    if (in_array(strtolower($first), $known_project_dirs, true)) {
        $cached = '';
        return $cached;
    }

    $cached = '/' . $first;
    return $cached;
}

function app_url($path)
{
    $path = trim((string) $path);
    if ($path === '') {
        return rtrim(app_root_path(), '/');
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    return rtrim(app_root_path(), '/') . '/' . $path;
}

function app_url_absolute($path)
{
    $url = app_url($path);
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    $override = trim((string) get_global_setting('public_base_url', ''));
    if ($override !== '' && preg_match('/^https?:\/\//i', $override)) {
        $base = rtrim($override, '/');
        $basePath = parse_url($base, PHP_URL_PATH) ?: '';
        $basePath = rtrim($basePath, '/');
        $cleanUrl = (string) $url;
        if ($basePath !== '' && str_starts_with($cleanUrl, $basePath . '/')) {
            $cleanUrl = substr($cleanUrl, strlen($basePath));
        }
        return $base . '/' . ltrim((string) $cleanUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $serverName = trim((string) ($_SERVER['SERVER_NAME'] ?? ''));

    // Algunos entornos locales envían HTTP_HOST = "clinica"; evitamos enlaces inválidos.
    if ($host === '' || (strpos($host, '.') === false && strpos($host, ':') === false && strtolower($host) !== 'localhost')) {
        $host = $serverName !== '' ? $serverName : 'localhost';
    }
    if ($host === '' || (strpos($host, '.') === false && strpos($host, ':') === false && strtolower($host) !== 'localhost')) {
        $host = 'localhost';
    }
    if ($host === '') {
        $host = 'localhost';
    }
    return $scheme . '://' . $host . '/' . ltrim((string) $url, '/');
}

function url_add_query($url, array $params): string
{
    $url = (string) $url;
    if (empty($params)) {
        return $url;
    }
    $parts = parse_url($url);
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    foreach ($params as $k => $v) {
        $query[(string) $k] = (string) $v;
    }
    $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host = $parts['host'] ?? '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = $parts['path'] ?? '';
    $qs = http_build_query($query);
    return $scheme . $host . $port . $path . ($qs !== '' ? ('?' . $qs) : '');
}

function public_pretty_url($path, $slug): ?string
{
    $slug = trim((string) $slug);
    if ($slug === '') {
        return null;
    }
    $path = ltrim((string) $path, '/');
    $query = '';
    if (strpos($path, '?') !== false) {
        [$path, $query] = explode('?', $path, 2);
    }
    if (!str_starts_with($path, 'vistas/public/')) {
        return null;
    }
    $name = substr($path, strlen('vistas/public/'));
    $name = preg_replace('/\.php$/', '', $name);
    $map = [
        'inicio' => 'inicio',
        'ver-sedes' => 'sedes',
        'servicios' => 'servicios',
        'citas' => 'citas',
        'blog' => 'blog',
        'login' => 'login',
        'resena' => 'resena',
    ];
    if (!isset($map[$name])) {
        return null;
    }
    $pretty = app_url($slug . '/' . $map[$name]);
    if ($query !== '') {
        $pretty .= '?' . $query;
    }
    return $pretty;
}

function app_link_with_slug($path, $id_e)
{
    if (!$path) {
        return '#';
    }
    $url = app_url($path);
    if ($id_e) {
        if (!is_numeric($id_e)) {
            $pretty = public_pretty_url($path, $id_e);
            if ($pretty) {
                return $pretty;
            }
        }
        $param_name = is_numeric($id_e) ? 'id_e' : 'empresa';
        $url .= (strpos($url, '?') === false ? '?' : '&') . $param_name . '=' . rawurlencode((string) $id_e);
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

        $allowed_roles = ['superadmin', 'admin', 'gerente', 'empleado'];
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

function home_page_module_defaults($modulo = null)
{
    $defs = [
        'blog' => ['titulo' => 'Nuestro Blog', 'limite' => 3],
        'usuarios' => ['titulo' => 'Nuestro Personal de confianza', 'limite' => 5],
        'resenas' => ['titulo' => 'Lo que opinan nuestros clientes', 'limite' => 6],
        'servicios' => ['titulo' => 'Nuestros Servicios', 'limite' => 6],
        'sucursales' => ['titulo' => 'Nuestras Sucursales', 'limite' => 6],
    ];

    if ($modulo === null) {
        return $defs;
    }
    return $defs[$modulo] ?? ['titulo' => ucfirst((string) $modulo), 'limite' => 3];
}

function home_page_ensure_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS home_page (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      empresa_id BIGINT UNSIGNED NOT NULL,
      modulo VARCHAR(60) NOT NULL,
      titulo VARCHAR(190) NOT NULL,
      valores_json JSON NULL,
      tipo TINYINT UNSIGNED NOT NULL DEFAULT 1,
      estado TINYINT(1) NOT NULL DEFAULT 1,
      orden INT NOT NULL DEFAULT 0,
      limite INT UNSIGNED NOT NULL DEFAULT 3,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_home_page_empresa_modulo (empresa_id, modulo),
      KEY idx_home_page_empresa_orden (empresa_id, orden),
      CONSTRAINT fk_home_page_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function home_page_get_section($empresa_id, $modulo)
{
    global $pdo;
    home_page_ensure_table();

    $stmt = $pdo->prepare('SELECT * FROM home_page WHERE empresa_id = ? AND modulo = ? LIMIT 1');
    $stmt->execute([(int) $empresa_id, (string) $modulo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($row) {
        return $row;
    }

    $def = home_page_module_defaults((string) $modulo);
    $insert = $pdo->prepare('INSERT INTO home_page (empresa_id, modulo, titulo, valores_json, tipo, estado, orden, limite) VALUES (?,?,?,?,?,?,?,?)');
    $insert->execute([
        (int) $empresa_id,
        (string) $modulo,
        (string) $def['titulo'],
        json_encode([], JSON_UNESCAPED_UNICODE),
        1,
        1,
        0,
        (int) $def['limite'],
    ]);

    $stmt->execute([(int) $empresa_id, (string) $modulo]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function home_page_sync_item($empresa_id, $modulo, $item_id, $enabled = true)
{
    global $pdo;
    $empresa_id = (int) $empresa_id;
    $modulo = (string) $modulo;
    $item_id = (int) $item_id;
    if ($empresa_id <= 0 || $item_id <= 0 || $modulo === '') {
        return false;
    }

    $row = home_page_get_section($empresa_id, $modulo);
    if (!$row) {
        return false;
    }

    $vals = json_decode((string) ($row['valores_json'] ?? '[]'), true);
    if (!is_array($vals)) {
        $vals = [];
    }
    $vals = array_values(array_unique(array_map('intval', $vals)));
    $vals = array_values(array_filter($vals, static fn($v) => $v > 0));

    if ($enabled) {
        $vals = array_values(array_filter($vals, static fn($v) => $v !== $item_id));
        array_unshift($vals, $item_id);
        $limite = max(1, (int) ($row['limite'] ?? home_page_module_defaults($modulo)['limite']));
        $vals = array_slice($vals, 0, $limite);
    } else {
        $vals = array_values(array_filter($vals, static fn($v) => $v !== $item_id));
    }

    $upd = $pdo->prepare('UPDATE home_page SET valores_json = ? WHERE empresa_id = ? AND modulo = ?');
    $upd->execute([json_encode($vals, JSON_UNESCAPED_UNICODE), $empresa_id, $modulo]);
    return true;
}

function home_page_is_item_selected($empresa_id, $modulo, $item_id)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0) {
        return false;
    }
    $row = home_page_get_section((int) $empresa_id, (string) $modulo);
    if (!$row) {
        return false;
    }
    $vals = json_decode((string) ($row['valores_json'] ?? '[]'), true);
    if (!is_array($vals)) {
        return false;
    }
    $vals = array_map('intval', $vals);
    return in_array($item_id, $vals, true);
}

function home_page_selected_ids($empresa_id, $modulo)
{
    $row = home_page_get_section((int) $empresa_id, (string) $modulo);
    if (!$row) {
        return [];
    }
    $vals = json_decode((string) ($row['valores_json'] ?? '[]'), true);
    if (!is_array($vals)) {
        return [];
    }
    $vals = array_values(array_unique(array_map('intval', $vals)));
    return array_values(array_filter($vals, static fn($v) => $v > 0));
}

function notifications_ensure_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS notificaciones (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      empresa_id BIGINT UNSIGNED NOT NULL,
      usuario_id BIGINT UNSIGNED NULL,
      rol_destino VARCHAR(30) NULL,
      tipo VARCHAR(60) NOT NULL DEFAULT 'general',
      titulo VARCHAR(190) NOT NULL,
      descripcion TEXT NULL,
      url VARCHAR(255) NULL,
      referencia_tipo VARCHAR(60) NULL,
      referencia_id BIGINT UNSIGNED NULL,
      leida TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_notif_empresa_user (empresa_id, usuario_id, leida, created_at),
      KEY idx_notif_empresa_rol (empresa_id, rol_destino, leida, created_at),
      KEY idx_notif_tipo (tipo, leida, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function create_notification(array $data)
{
    global $pdo;
    notifications_ensure_table();
    $empresa_id = (int) ($data['empresa_id'] ?? 0);
    $titulo = trim((string) ($data['titulo'] ?? ''));
    if ($empresa_id <= 0 || $titulo === '') {
        return false;
    }
    $stmt = $pdo->prepare("INSERT INTO notificaciones
        (empresa_id, usuario_id, rol_destino, tipo, titulo, descripcion, url, referencia_tipo, referencia_id, leida)
        VALUES (?,?,?,?,?,?,?,?,?,0)");
    $stmt->execute([
        $empresa_id,
        isset($data['usuario_id']) ? (int) $data['usuario_id'] : null,
        isset($data['rol_destino']) ? (string) $data['rol_destino'] : null,
        (string) ($data['tipo'] ?? 'general'),
        $titulo,
        isset($data['descripcion']) ? (string) $data['descripcion'] : null,
        isset($data['url']) ? (string) $data['url'] : null,
        isset($data['referencia_tipo']) ? (string) $data['referencia_tipo'] : null,
        isset($data['referencia_id']) ? (int) $data['referencia_id'] : null,
    ]);
    return true;
}

function notifications_fetch_for_user($empresa_id, $usuario_id, $rol, $limit = 8)
{
    global $pdo;
    notifications_ensure_table();
    $empresa_id = (int) $empresa_id;
    $usuario_id = (int) $usuario_id;
    $rol = (string) ($rol ?? '');
    $limit = max(1, min(50, (int) $limit));
    if ($empresa_id <= 0 || $rol === '') {
        return [];
    }
    $stmt = $pdo->prepare("SELECT id, tipo, titulo, descripcion, url, leida, created_at
                           FROM notificaciones
                           WHERE empresa_id = ?
                             AND (
                               usuario_id = ?
                               OR (usuario_id IS NULL AND (rol_destino = ? OR rol_destino IS NULL))
                             )
                           ORDER BY created_at DESC
                           LIMIT $limit");
    $stmt->execute([$empresa_id, $usuario_id, $rol]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ensure_anuncios_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS anuncios (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      slot VARCHAR(30) NOT NULL,
      imagen_path VARCHAR(255) NULL,
      link_url VARCHAR(255) NULL,
      activo TINYINT(1) NOT NULL DEFAULT 1,
      orden INT NOT NULL DEFAULT 0,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_anuncios_slot (slot)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function anuncios_get_active_map()
{
    global $pdo;
    ensure_anuncios_table();
    $rows = [];
    try {
        $stmt = $pdo->query("SELECT slot, imagen_path, link_url, activo FROM anuncios ORDER BY orden ASC, id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $rows = [];
    }
    $map = [];
    foreach ($rows as $row) {
        $slot = (string) ($row['slot'] ?? '');
        if ($slot === '') {
            continue;
        }
        $map[$slot] = [
            'slot' => $slot,
            'imagen_path' => (string) ($row['imagen_path'] ?? ''),
            'link_url' => (string) ($row['link_url'] ?? ''),
            'activo' => (int) ($row['activo'] ?? 0),
        ];
    }
    return $map;
}

function get_empresa_plan_info($empresa_id)
{
    global $pdo;
    $empresa_id = (int) $empresa_id;
    if ($empresa_id <= 0) {
        return null;
    }
    try {
        $stmt = $pdo->prepare("SELECT p.id, p.nombre
                               FROM empresas e
                               LEFT JOIN planes p ON p.id = e.plan_id
                               WHERE e.id = ? LIMIT 1");
        $stmt->execute([$empresa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function empresa_is_basic_plan($empresa_id)
{
    $info = get_empresa_plan_info((int) $empresa_id);
    if (!$info) {
        return false;
    }
    $name = strtolower(trim((string) ($info['nombre'] ?? '')));
    if ($name === '') {
        return false;
    }
    return $name === 'basico' || $name === 'básico' || str_contains($name, 'basico') || str_contains($name, 'básico');
}

function notifications_unread_count($empresa_id, $usuario_id, $rol, array $tipos = [])
{
    global $pdo;
    notifications_ensure_table();
    $empresa_id = (int) $empresa_id;
    $usuario_id = (int) $usuario_id;
    $rol = (string) ($rol ?? '');
    if ($empresa_id <= 0 || $rol === '') {
        return 0;
    }
    $sql = "SELECT COUNT(*)
            FROM notificaciones
            WHERE empresa_id = ?
              AND leida = 0
              AND (
                usuario_id = ?
                OR (usuario_id IS NULL AND (rol_destino = ? OR rol_destino IS NULL))
              )";
    $params = [$empresa_id, $usuario_id, $rol];
    if (!empty($tipos)) {
        $safe = array_values(array_filter(array_map('strval', $tipos), static fn($v) => $v !== ''));
        if (!empty($safe)) {
            $in = implode(',', array_fill(0, count($safe), '?'));
            $sql .= " AND tipo IN ($in)";
            $params = array_merge($params, $safe);
        }
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function build_cita_notas_timeline($prev, $incoming, $actor_name, $actor_id)
{
    $prev = trim(str_replace("\r\n", "\n", (string) $prev));
    $incoming = trim(str_replace("\r\n", "\n", (string) $incoming));
    if ($incoming === '') {
        return $prev;
    }
    if ($prev === '') {
        return $incoming;
    }
    if ($incoming === $prev) {
        return $prev;
    }
    // Si el frontend envía el historial completo + nueva nota, extraemos solo el delta nuevo.
    if ($prev !== '' && substr($incoming, 0, strlen($prev)) === $prev) {
        $delta = trim(substr($incoming, strlen($prev)));
        if ($delta === '') {
            return $prev;
        }
        $incoming = $delta;
    }
    if (preg_match('/\Q' . $incoming . '\E\s*$/u', $prev)) {
        return $prev;
    }
    $actor_name = trim((string) $actor_name);
    if ($actor_name === '') {
        $actor_name = 'usuario';
    }
    $actor_id = (int) $actor_id;
    $stamp = date('d/m/y - H:i:s');
    $header = "------------------- {$actor_name} (id: {$actor_id}) - {$stamp} --------------------";
    $merged = $prev . "\n\n" . $header . "\n" . $incoming;
    // Limitar crecimiento extremo conservando el final más reciente.
    $maxLen = 8000;
    $len = function_exists('mb_strlen') ? mb_strlen($merged) : strlen($merged);
    if ($len > $maxLen) {
        $start = $len - $maxLen;
        $merged = function_exists('mb_substr') ? mb_substr($merged, $start) : substr($merged, $start);
    }
    return trim($merged);
}

function get_global_setting($clave, $default = null)
{
    global $pdo;
    static $cache = [];
    $k = (string) $clave;
    if ($k === '') {
        return $default;
    }
    if (array_key_exists($k, $cache)) {
        return $cache[$k];
    }
    try {
        $stmt = $pdo->prepare('SELECT valor_json FROM ajustes_globales WHERE clave = ? LIMIT 1');
        $stmt->execute([$k]);
        $raw = $stmt->fetchColumn();
        if ($raw === false || $raw === null) {
            $cache[$k] = $default;
            return $cache[$k];
        }
        $decoded = json_decode((string) $raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $cache[$k] = $decoded;
        } else {
            $cache[$k] = trim((string) $raw, "\"'");
        }
        return $cache[$k];
    } catch (Throwable $e) {
        $cache[$k] = $default;
        return $cache[$k];
    }
}

function normalize_email_identity(string $email): string
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    [$local, $domain] = explode('@', $email, 2);
    $domain = trim($domain);
    if ($domain === 'googlemail.com') {
        $domain = 'gmail.com';
    }
    if ($domain === 'gmail.com') {
        $local = preg_replace('/\+.*$/', '', $local);
        $local = str_replace('.', '', (string) $local);
    }
    return trim($local) . '@' . $domain;
}

function trusted_email_domains_default(): array
{
    return [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'yahoo.es',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'icloud.com',
        'me.com',
        'msn.com',
        'aol.com',
        'proton.me',
        'protonmail.com',
        'gmx.com',
        'mail.com',
        'zoho.com',
        'fastmail.com',
    ];
}

function is_trusted_email_domain(string $email): bool
{
    $normalized = normalize_email_identity($email);
    if ($normalized === '' || strpos($normalized, '@') === false) {
        return false;
    }
    [, $domain] = explode('@', $normalized, 2);
    $trusted = get_global_setting('trusted_email_domains', []);
    if (!is_array($trusted) || empty($trusted)) {
        $trusted = trusted_email_domains_default();
    }
    $trusted = array_values(array_unique(array_map(static fn($d) => strtolower(trim((string) $d)), $trusted)));
    return in_array(strtolower($domain), $trusted, true);
}

function ensure_request_guard_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS request_guard (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      scope VARCHAR(80) NOT NULL,
      identity_hash CHAR(64) NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_request_guard_scope_identity_date (scope, identity_hash, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function request_guard_is_limited(string $scope, string $identity, int $seconds = 300, int $max = 1, bool $touch = true): bool
{
    global $pdo;
    $scope = trim($scope);
    $identity = trim($identity);
    $seconds = max(1, min(86400, $seconds));
    $max = max(1, min(1000, $max));
    if ($scope === '' || $identity === '') {
        return false;
    }
    ensure_request_guard_table();
    $hash = hash('sha256', $identity);
    $from = date('Y-m-d H:i:s', time() - $seconds);
    try {
        $q = $pdo->prepare("SELECT COUNT(*) FROM request_guard WHERE scope = ? AND identity_hash = ? AND created_at >= ?");
        $q->execute([$scope, $hash, $from]);
        $count = (int) ($q->fetchColumn() ?: 0);
        $limited = ($count >= $max);
        if ($touch) {
            $ins = $pdo->prepare("INSERT INTO request_guard (scope, identity_hash) VALUES (?,?)");
            $ins->execute([$scope, $hash]);
        }
        return $limited;
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_users_email_verified_column()
{
    global $pdo;
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN email_verified_at DATETIME NULL");
    } catch (Throwable $e) {
    }
    // Compatibilidad: cuentas históricas activas quedan verificadas automáticamente.
    try {
        $pdo->exec("UPDATE usuarios SET email_verified_at = NOW() WHERE activo = 1 AND email_verified_at IS NULL");
    } catch (Throwable $e) {
    }
    $done = true;
}

function ensure_email_verification_tokens_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    ensure_users_email_verified_column();
    $sql = "CREATE TABLE IF NOT EXISTS email_verification_tokens (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      usuario_id BIGINT UNSIGNED NOT NULL,
      token_hash CHAR(64) NOT NULL,
      expires_at DATETIME NOT NULL,
      used_at DATETIME NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_email_verify_token (token_hash),
      KEY idx_email_verify_user (usuario_id, created_at),
      CONSTRAINT fk_email_verify_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function create_email_verification_token(int $usuario_id, int $minutes = 1440): ?string
{
    global $pdo;
    if ($usuario_id <= 0) {
        return null;
    }
    ensure_email_verification_tokens_table();
    $token = strtoupper(bin2hex(random_bytes(24)));
    $hash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(10, min(10080, $minutes)) . ' minutes'));
    try {
        $pdo->prepare("UPDATE email_verification_tokens SET used_at = NOW() WHERE usuario_id = ? AND used_at IS NULL")->execute([$usuario_id]);
        $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (usuario_id, token_hash, expires_at) VALUES (?,?,?)");
        $stmt->execute([$usuario_id, $hash, $expiresAt]);
        return $token;
    } catch (Throwable $e) {
        return null;
    }
}

function find_email_verification_token(string $token): ?array
{
    global $pdo;
    ensure_email_verification_tokens_table();
    $hash = hash('sha256', trim($token));
    try {
        $stmt = $pdo->prepare("SELECT evt.*, u.id AS usuario_id_real, u.email, u.nombre, u.empresa_id, u.rol, u.email_verified_at,
                                      e.slug AS empresa_slug, e.nombre AS empresa_nombre
                               FROM email_verification_tokens evt
                               INNER JOIN usuarios u ON u.id = evt.usuario_id
                               LEFT JOIN empresas e ON e.id = u.empresa_id
                               WHERE evt.token_hash = ?
                               LIMIT 1");
        $stmt->execute([$hash]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function consume_email_verification_token(string $token): ?array
{
    global $pdo;
    $row = find_email_verification_token($token);
    if (!$row) {
        return null;
    }
    if (!empty($row['used_at'])) {
        return null;
    }
    if (strtotime((string) ($row['expires_at'] ?? '')) < time()) {
        return null;
    }
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE usuarios SET email_verified_at = COALESCE(email_verified_at, NOW()), activo = 1 WHERE id = ?")
            ->execute([(int) $row['usuario_id']]);
        $pdo->prepare("UPDATE email_verification_tokens SET used_at = NOW() WHERE id = ?")
            ->execute([(int) $row['id']]);
        $pdo->commit();
        $row['email_verified_at'] = date('Y-m-d H:i:s');
        return $row;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return null;
    }
}

function find_user_by_login_email(string $email): ?array
{
    global $pdo;
    $normalized = normalize_email_identity($email);
    if ($normalized === '') {
        return null;
    }
    [, $domain] = explode('@', $normalized, 2);
    try {
        if ($domain === 'gmail.com') {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) LIKE '%@gmail.com' OR LOWER(email) LIKE '%@googlemail.com' LIMIT 200");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                if (normalize_email_identity((string) ($r['email'] ?? '')) === $normalized) {
                    return $r;
                }
            }
            return null;
        }
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$normalized]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function get_empresa_config_value($empresa_info, $key, $default = null)
{
    $cfg = [];
    if (is_array($empresa_info) && !empty($empresa_info['config_json'])) {
        $cfg = json_decode((string) $empresa_info['config_json'], true) ?: [];
    }
    return array_key_exists($key, $cfg) ? $cfg[$key] : $default;
}

function get_currency_meta($empresa_info = null)
{
    $code = strtoupper((string) get_empresa_config_value($empresa_info, 'moneda', 'GTQ'));
    $map = [
        'GTQ' => ['code' => 'GTQ', 'symbol' => 'Q'],
        'USD' => ['code' => 'USD', 'symbol' => '$'],
        'EUR' => ['code' => 'EUR', 'symbol' => '€'],
        'MXN' => ['code' => 'MXN', 'symbol' => '$'],
    ];
    return $map[$code] ?? ['code' => $code, 'symbol' => $code . ' '];
}

function format_currency_amount($amount, $empresa_info = null, $decimals = 2)
{
    $cur = get_currency_meta($empresa_info);
    $n = number_format((float) $amount, (int) $decimals, '.', ',');
    return $cur['symbol'] . $n;
}

function ensure_planes_extra_columns()
{
    global $pdo;
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE planes ADD COLUMN precio DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("ALTER TABLE planes ADD COLUMN modulos_json JSON NULL");
    } catch (Throwable $e) {
    }
    $done = true;
}

function ensure_suscripciones_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    ensure_planes_extra_columns();
    $sql = "CREATE TABLE IF NOT EXISTS suscripciones (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      empresa_id BIGINT UNSIGNED NOT NULL,
      plan_id BIGINT UNSIGNED NOT NULL,
      estado ENUM('activa','vencida','cancelada','pendiente') NOT NULL DEFAULT 'activa',
      fecha_inicio DATE NOT NULL,
      fecha_fin DATE NULL,
      ultimo_pago_monto DECIMAL(10,2) NULL,
      ultimo_pago_fecha DATE NULL,
      detalle_pago_json JSON NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_suscrip_empresa (empresa_id, estado, fecha_fin),
      KEY idx_suscrip_plan (plan_id),
      CONSTRAINT fk_suscrip_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_suscrip_plan FOREIGN KEY (plan_id) REFERENCES planes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function ensure_suscripciones_historial_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    ensure_suscripciones_table();
    $sql = "CREATE TABLE IF NOT EXISTS suscripciones_historial (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      suscripcion_id BIGINT UNSIGNED NULL,
      empresa_id BIGINT UNSIGNED NOT NULL,
      plan_id BIGINT UNSIGNED NOT NULL,
      estado VARCHAR(20) NOT NULL,
      plazo VARCHAR(20) NULL,
      fecha_inicio DATE NULL,
      fecha_fin DATE NULL,
      ultimo_pago_monto DECIMAL(10,2) NULL,
      ultimo_pago_fecha DATE NULL,
      detalle_pago_json TEXT NULL,
      adjunto_pago_path VARCHAR(255) NULL,
      accion VARCHAR(30) NOT NULL DEFAULT 'snapshot',
      accion_usuario_id BIGINT UNSIGNED NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_sus_hist_empresa (empresa_id, created_at),
      KEY idx_sus_hist_fecha_pago (ultimo_pago_fecha),
      KEY idx_sus_hist_accion (accion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function suscripcion_history_append(array $row, string $accion = 'snapshot', ?int $actor_user_id = null): bool
{
    global $pdo;
    ensure_suscripciones_historial_table();
    $empresa_id = (int) ($row['empresa_id'] ?? 0);
    $plan_id = (int) ($row['plan_id'] ?? 0);
    if ($empresa_id <= 0 || $plan_id <= 0) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO suscripciones_historial
            (suscripcion_id, empresa_id, plan_id, estado, plazo, fecha_inicio, fecha_fin, ultimo_pago_monto, ultimo_pago_fecha, detalle_pago_json, adjunto_pago_path, accion, accion_usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            isset($row['id']) ? (int) $row['id'] : null,
            $empresa_id,
            $plan_id,
            (string) ($row['estado'] ?? 'activa'),
            isset($row['plazo']) ? (string) $row['plazo'] : null,
            isset($row['fecha_inicio']) ? (string) $row['fecha_inicio'] : null,
            isset($row['fecha_fin']) ? (string) $row['fecha_fin'] : null,
            isset($row['ultimo_pago_monto']) ? (float) $row['ultimo_pago_monto'] : null,
            isset($row['ultimo_pago_fecha']) ? (string) $row['ultimo_pago_fecha'] : null,
            isset($row['detalle_pago_json']) ? (string) $row['detalle_pago_json'] : null,
            isset($row['adjunto_pago_path']) ? (string) $row['adjunto_pago_path'] : null,
            $accion,
            $actor_user_id ?: null,
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function suscripciones_refresh_statuses(?int $empresa_id = null): int
{
    global $pdo;
    ensure_suscripciones_table();
    $updated = 0;
    try {
        if ($empresa_id !== null && $empresa_id > 0) {
            $stmt = $pdo->prepare("UPDATE suscripciones
                                   SET estado='vencida'
                                   WHERE empresa_id = ?
                                     AND estado IN ('activa','pendiente')
                                     AND fecha_fin IS NOT NULL
                                     AND fecha_fin < CURDATE()");
            $stmt->execute([(int) $empresa_id]);
            $updated += (int) $stmt->rowCount();
        } else {
            $stmt = $pdo->prepare("UPDATE suscripciones
                                   SET estado='vencida'
                                   WHERE estado IN ('activa','pendiente')
                                     AND fecha_fin IS NOT NULL
                                     AND fecha_fin < CURDATE()");
            $stmt->execute();
            $updated += (int) $stmt->rowCount();
        }
    } catch (Throwable $e) {
    }
    return $updated;
}

function suscripciones_normalize_single(): int
{
    global $pdo;
    ensure_suscripciones_table();
    $count = 0;
    try {
        $stmt = $pdo->query("SELECT empresa_id, GROUP_CONCAT(id ORDER BY id DESC) ids, COUNT(*) c
                             FROM suscripciones
                             GROUP BY empresa_id
                             HAVING c > 1");
        $dups = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($dups as $d) {
            $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($d['ids'] ?? '')))));
            if (count($ids) <= 1) {
                continue;
            }
            $keep = array_shift($ids);
            if (!empty($ids)) {
                $q = $pdo->query("SELECT * FROM suscripciones WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")");
                $rows = $q ? ($q->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
                foreach ($rows as $r) {
                    suscripcion_history_append($r, 'normalize', (int) ($GLOBALS['user']['id'] ?? 0));
                }
                $in = implode(',', array_map('intval', $ids));
                $pdo->exec("DELETE FROM suscripciones WHERE id IN ($in)");
                $count += count($ids);
            }
            $pdo->prepare("UPDATE suscripciones SET estado='activa' WHERE id=?")->execute([$keep]);
        }
    } catch (Throwable $e) {
    }
    return $count;
}

function cron_jobs_log(string $message): void
{
    $file = project_path('cron_jobs.log');
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

function cron_jobs_list(): array
{
    return [
        [
            'key' => 'suscripciones_refresh',
            'title' => 'Actualizar estados de suscripciones',
            'description' => 'Marca suscripciones vencidas según fecha_fin.',
        ],
        [
            'key' => 'suscripciones_normalize',
            'title' => 'Normalizar suscripciones duplicadas',
            'description' => 'Mantiene una suscripción activa por empresa y archiva el historial.',
        ],
        [
            'key' => 'limpiar_email_envios',
            'title' => 'Limpiar envíos de correo antiguos',
            'description' => 'Elimina registros de email_envios con más de 120 días.',
        ],
    ];
}

function cron_jobs_run(array $task_keys, ?int $actor_id = null): array
{
    global $pdo;
    $results = [];
    $tasks = cron_jobs_list();
    $map = [];
    foreach ($tasks as $t) {
        $map[$t['key']] = $t;
    }
    foreach ($task_keys as $key) {
        $key = (string) $key;
        if (!isset($map[$key])) {
            continue;
        }
        $ok = true;
        $meta = [];
        try {
            if ($key === 'suscripciones_refresh') {
                $count = suscripciones_refresh_statuses();
                $meta = ['updated' => $count];
            } elseif ($key === 'suscripciones_normalize') {
                $count = suscripciones_normalize_single();
                $meta = ['removed' => $count];
            } elseif ($key === 'limpiar_email_envios') {
                $stmt = $pdo->prepare("DELETE FROM email_envios WHERE created_at < DATE_SUB(NOW(), INTERVAL 120 DAY)");
                $stmt->execute();
                $meta = ['removed' => (int) $stmt->rowCount()];
            }
        } catch (Throwable $e) {
            $ok = false;
            $meta = ['error' => $e->getMessage()];
        }
        $results[] = [
            'key' => $key,
            'ok' => $ok,
            'meta' => $meta,
        ];
        $label = $map[$key]['title'] ?? $key;
        cron_jobs_log(($ok ? 'OK' : 'ERROR') . " {$label} " . json_encode($meta, JSON_UNESCAPED_UNICODE));
    }
    if ($actor_id) {
        cron_jobs_log("Ejecutado por usuario {$actor_id}");
    }
    return $results;
}

function cron_jobs_auto_run_if_needed(?int $actor_id = null): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $today = date('Y-m-d');
    $last = $_SESSION['cron_last_run'] ?? null;
    if ($last === $today) {
        return;
    }
    cron_jobs_run(['suscripciones_refresh', 'limpiar_email_envios'], $actor_id);
    $_SESSION['cron_last_run'] = $today;
}

function get_empresa_suscripcion_actual(int $empresa_id): ?array
{
    global $pdo;
    static $cache = [];
    if ($empresa_id <= 0) {
        return null;
    }
    if (array_key_exists($empresa_id, $cache)) {
        return $cache[$empresa_id];
    }
    ensure_suscripciones_table();
    suscripciones_refresh_statuses($empresa_id);
    try {
        $stmt = $pdo->prepare("SELECT s.*, p.nombre AS plan_nombre, p.precio AS plan_precio
                               FROM suscripciones s
                               LEFT JOIN planes p ON p.id = s.plan_id
                               WHERE s.empresa_id = ?
                               ORDER BY s.id DESC
                               LIMIT 1");
        $stmt->execute([$empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            $cache[$empresa_id] = null;
            return null;
        }
        $fin = (string) ($row['fecha_fin'] ?? '');
        $estado = (string) ($row['estado'] ?? '');
        if ($fin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
            if ($estado === 'vencida' && $fin >= date('Y-m-d')) {
                try {
                    $pdo->prepare("UPDATE suscripciones SET estado='activa' WHERE id=?")->execute([(int) $row['id']]);
                    $row['estado'] = 'activa';
                    $estado = 'activa';
                } catch (Throwable $e) {
                }
            }
        }
        if ($fin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
            $days = (int) floor((strtotime($fin . ' 23:59:59') - time()) / 86400);
            $row['dias_restantes'] = $days;
            $row['por_vencer'] = ($days >= 0 && $days <= 7) ? 1 : 0;
        } else {
            $row['dias_restantes'] = null;
            $row['por_vencer'] = 0;
        }
        $cache[$empresa_id] = $row;
        return $row;
    } catch (Throwable $e) {
        $cache[$empresa_id] = null;
        return null;
    }
}

function ensure_citas_public_ref_column()
{
    global $pdo;
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE citas ADD COLUMN codigo_publico VARCHAR(40) NULL");
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("CREATE INDEX idx_citas_codigo_publico ON citas(codigo_publico)");
    } catch (Throwable $e) {
    }
    $done = true;
}

function get_empresa_plan_permissions($empresa_id)
{
    global $pdo;
    ensure_planes_extra_columns();
    $empresa_id = (int) $empresa_id;
    if ($empresa_id <= 0) {
        return ['modules' => []];
    }
    $stmt = $pdo->prepare("SELECT p.modulos_json
                           FROM empresas e
                           LEFT JOIN planes p ON p.id = e.plan_id
                           WHERE e.id = ? LIMIT 1");
    $stmt->execute([$empresa_id]);
    $mod = $stmt->fetchColumn();
    $arr = json_decode((string) ($mod ?: '[]'), true);
    if (!is_array($arr)) {
        $arr = [];
    }
    $safe = array_values(array_unique(array_filter(array_map('strval', $arr), static fn($v) => $v !== '')));
    return ['modules' => $safe];
}

function plan_allows_module($empresa_id, $module_key)
{
    $module_key = trim((string) $module_key);
    if ($module_key === '') {
        return true;
    }
    $perm = get_empresa_plan_permissions((int) $empresa_id);
    $mods = $perm['modules'] ?? [];
    if (empty($mods)) {
        // Sin configuración => permitir por compatibilidad retro.
        $modsOk = true;
    } else {
        $modsOk = in_array($module_key, $mods, true);
    }
    if (!$modsOk) {
        return false;
    }

    // Reglas extra por vencimiento de suscripción.
    return true;
}

function enforce_module_access_or_403($empresa_id, $module_key)
{
    if (plan_allows_module((int) $empresa_id, (string) $module_key)) {
        return true;
    }
    http_response_code(403);
    $err403 = project_path('includes/errors/403.php');
    if (is_file($err403)) {
        include $err403;
    } else {
        echo 'No autorizado.';
    }
    exit;
}

function ensure_email_envios_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS email_envios (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      empresa_id BIGINT UNSIGNED NULL,
      tipo VARCHAR(40) NOT NULL,
      destinatario_email VARCHAR(255) NOT NULL,
      asunto VARCHAR(255) NOT NULL,
      proveedor VARCHAR(20) NOT NULL DEFAULT 'mail',
      estado ENUM('sent','failed') NOT NULL DEFAULT 'sent',
      error_msg TEXT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_email_empresa_fecha (empresa_id, created_at),
      KEY idx_email_estado_fecha (estado, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function log_email_envio($empresa_id, $tipo, $to, $subject, $estado, $proveedor, $error = null)
{
    global $pdo;
    ensure_email_envios_table();
    try {
        $stmt = $pdo->prepare("INSERT INTO email_envios
          (empresa_id, tipo, destinatario_email, asunto, proveedor, estado, error_msg)
          VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $empresa_id ?: null,
            (string) $tipo,
            (string) $to,
            (string) $subject,
            (string) $proveedor,
            (string) $estado,
            $error !== null ? (string) $error : null,
        ]);
    } catch (Throwable $e) {
    }
}

function email_delivery_stats($days = 30)
{
    global $pdo;
    ensure_email_envios_table();
    $days = max(1, min(365, (int) $days));
    $from = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
    try {
        $stmt = $pdo->prepare("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado='sent' THEN 1 ELSE 0 END) AS sent,
            SUM(CASE WHEN estado='failed' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN tipo='booking_confirmation' THEN 1 ELSE 0 END) AS booking_sent,
            SUM(CASE WHEN tipo='review_invitation' THEN 1 ELSE 0 END) AS review_sent,
            SUM(CASE WHEN tipo='password_reset' THEN 1 ELSE 0 END) AS password_reset_sent,
            SUM(CASE WHEN tipo='email_verification' THEN 1 ELSE 0 END) AS email_verification_sent
          FROM email_envios
          WHERE created_at >= ?");
        $stmt->execute([$from]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'days' => $days,
            'total' => (int) ($row['total'] ?? 0),
            'sent' => (int) ($row['sent'] ?? 0),
            'failed' => (int) ($row['failed'] ?? 0),
            'booking_sent' => (int) ($row['booking_sent'] ?? 0),
            'review_sent' => (int) ($row['review_sent'] ?? 0),
            'password_reset_sent' => (int) ($row['password_reset_sent'] ?? 0),
            'email_verification_sent' => (int) ($row['email_verification_sent'] ?? 0),
        ];
    } catch (Throwable $e) {
        return ['days' => $days, 'total' => 0, 'sent' => 0, 'failed' => 0, 'booking_sent' => 0, 'review_sent' => 0, 'password_reset_sent' => 0, 'email_verification_sent' => 0];
    }
}

function resolve_empresa_branding($empresa_info = null)
{
    global $pdo;
    $row = is_array($empresa_info) ? $empresa_info : [];
    $eid = (int) ($row['id'] ?? 0);
    if ($eid > 0 && (empty($row['logo_path']) || empty($row['colores_json']))) {
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, logo_path, colores_json, config_json FROM empresas WHERE id = ? LIMIT 1");
            $stmt->execute([$eid]);
            $db = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $row = array_merge($db, $row);
        } catch (Throwable $e) {
        }
    }
    $name = (string) ($row['nombre'] ?? 'Reservas GP');
    $logo = trim((string) ($row['logo_path'] ?? ''));
    if ($logo !== '' && !preg_match('#^https?://#i', $logo)) {
        $logo = app_url(ltrim($logo, '/'));
    }
    if ($logo === '') {
        $logo = app_url('assets/logo.avif');
    }
    $primary = '#0d9488';
    $secondary = '#14b8a6';
    $cj = json_decode((string) ($row['colores_json'] ?? '{}'), true) ?: [];
    if (is_array($cj)) {
        $candidate = $cj['primary'] ?? $cj['color_principal'] ?? $cj['primario'] ?? $cj['p'] ?? $cj['teal'] ?? null;
        if (is_string($candidate) && preg_match('/^#[0-9A-Fa-f]{6}$/', $candidate)) {
            $primary = $candidate;
        }
        $candidate2 = $cj['secondary'] ?? $cj['accent'] ?? $cj['secundario'] ?? $cj['s'] ?? null;
        if (is_string($candidate2) && preg_match('/^#[0-9A-Fa-f]{6}$/', $candidate2)) {
            $secondary = $candidate2;
        }
    }
    $cfg = json_decode((string) ($row['config_json'] ?? '{}'), true) ?: [];
    if (is_array($cfg)) {
        $c1 = $cfg['ui_primary_color'] ?? $cfg['color_principal'] ?? null;
        if (is_string($c1) && preg_match('/^#[0-9A-Fa-f]{6}$/', $c1)) {
            $primary = $c1;
        }
    }
    return ['id' => $eid, 'name' => $name, 'logo' => $logo, 'primary' => $primary, 'secondary' => $secondary];
}

function render_email_layout($empresa_info, $subject, $title, $intro, $content_html, $cta_text = '', $cta_url = '')
{
    $b = resolve_empresa_branding($empresa_info);
    $year = date('Y');
    $cta = '';
    if (trim((string) $cta_text) !== '' && trim((string) $cta_url) !== '') {
        $cta = '<p style="margin:20px 0"><a href="' . htmlspecialchars((string) $cta_url) . '" style="display:inline-block;padding:12px 16px;background:' . htmlspecialchars($b['primary']) . ';color:#fff;text-decoration:none;border-radius:10px;font-weight:700">' . htmlspecialchars((string) $cta_text) . '</a></p>';
    }
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . htmlspecialchars((string) $subject) . '</title></head><body style="margin:0;background:#f3f4f6;font-family:Segoe UI,Arial,sans-serif;color:#111827">'
      . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px"><tr><td align="center">'
      . '<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb">'
      . '<tr><td style="padding:20px 24px;background:linear-gradient(135deg,' . htmlspecialchars($b['primary']) . ', ' . htmlspecialchars($b['secondary']) . ');color:#fff">'
      . '<table width="100%" role="presentation"><tr><td><div style="font-size:20px;font-weight:800">' . htmlspecialchars($b['name']) . '</div><div style="opacity:.9;font-size:12px;margin-top:2px">Notificación del sistema</div></td>'
      . '<td align="right"><img src="' . htmlspecialchars($b['logo']) . '" alt="logo" style="height:48px;width:48px;border-radius:50%;object-fit:cover;background:#fff"></td></tr></table>'
      . '</td></tr>'
      . '<tr><td style="padding:24px"><h1 style="margin:0 0 10px;font-size:24px;line-height:1.2">' . htmlspecialchars((string) $title) . '</h1>'
      . '<p style="margin:0 0 18px;color:#4b5563">' . htmlspecialchars((string) $intro) . '</p>'
      . '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px">' . $content_html . '</div>'
      . $cta
      . '<p style="margin:18px 0 0;color:#6b7280;font-size:12px">Si no reconoces este correo, puedes ignorarlo.</p>'
      . '</td></tr>'
      . '<tr><td style="padding:14px 24px;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:12px">© ' . $year . ' ' . htmlspecialchars($b['name']) . '</td></tr>'
      . '</table></td></tr></table></body></html>';
}

function smtp_read_response($fp)
{
    $full = '';
    $code = 0;
    while (!feof($fp)) {
        $line = fgets($fp, 515);
        if ($line === false) {
            break;
        }
        $full .= $line;
        if (preg_match('/^(\d{3})([\s-])/', $line, $m)) {
            $code = (int) $m[1];
            if ($m[2] === ' ') {
                break;
            }
        }
    }
    return [$code, $full];
}

function smtp_command($fp, $cmd, array $okCodes)
{
    fwrite($fp, $cmd . "\r\n");
    [$code, $resp] = smtp_read_response($fp);
    return [in_array($code, $okCodes, true), $code, $resp];
}

function send_via_smtp_socket(array $smtp, $fromEmail, $fromName, $toEmail, $subject, $html, &$error = null)
{
    $host = trim((string) ($smtp['host'] ?? ''));
    $port = (int) ($smtp['port'] ?? 587);
    $user = trim((string) ($smtp['user'] ?? ''));
    $pass = trim((string) ($smtp['pass'] ?? ''));
    $secure = strtolower(trim((string) ($smtp['secure'] ?? 'tls')));
    $timeout = max(5, min(30, (int) ($smtp['timeout'] ?? 12)));
    if ($host === '' || $port <= 0 || $user === '' || $pass === '') {
        $error = 'SMTP incompleto.';
        return false;
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        $error = "No conecta SMTP: $errstr ($errno)";
        return false;
    }
    stream_set_timeout($fp, $timeout);
    [$code, $resp] = smtp_read_response($fp);
    if ($code !== 220) {
        fclose($fp);
        $error = 'SMTP saludo inválido: ' . trim($resp);
        return false;
    }

    [$ok] = smtp_command($fp, 'EHLO ' . (parse_url(app_url(''), PHP_URL_HOST) ?: 'localhost'), [250]);
    if (!$ok) {
        fclose($fp);
        $error = 'EHLO falló.';
        return false;
    }
    if ($secure === 'tls') {
        [$okTls, , $rTls] = smtp_command($fp, 'STARTTLS', [220]);
        if (!$okTls) {
            fclose($fp);
            $error = 'STARTTLS falló: ' . trim($rTls);
            return false;
        }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            $error = 'No se pudo habilitar TLS.';
            return false;
        }
        [$ok] = smtp_command($fp, 'EHLO ' . (parse_url(app_url(''), PHP_URL_HOST) ?: 'localhost'), [250]);
        if (!$ok) {
            fclose($fp);
            $error = 'EHLO post-TLS falló.';
            return false;
        }
    }

    [$ok] = smtp_command($fp, 'AUTH LOGIN', [334]);
    if (!$ok) {
        fclose($fp);
        $error = 'AUTH LOGIN falló.';
        return false;
    }
    [$ok] = smtp_command($fp, base64_encode($user), [334]);
    if (!$ok) {
        fclose($fp);
        $error = 'Usuario SMTP inválido.';
        return false;
    }
    [$ok, , $rPass] = smtp_command($fp, base64_encode($pass), [235]);
    if (!$ok) {
        fclose($fp);
        $error = 'Password SMTP inválido: ' . trim($rPass);
        return false;
    }

    [$ok] = smtp_command($fp, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    if (!$ok) {
        fclose($fp);
        $error = 'MAIL FROM falló.';
        return false;
    }
    [$ok] = smtp_command($fp, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
    if (!$ok) {
        fclose($fp);
        $error = 'RCPT TO falló.';
        return false;
    }
    [$ok] = smtp_command($fp, 'DATA', [354]);
    if (!$ok) {
        fclose($fp);
        $error = 'DATA falló.';
        return false;
    }

    $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [];
    $headers[] = 'Date: ' . date(DATE_RFC2822);
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $headers[] = 'Subject: ' . $subjectEncoded;
    $headers[] = 'To: <' . $toEmail . '>';
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
    fwrite($fp, $data . "\r\n");
    [$code, $resp] = smtp_read_response($fp);
    smtp_command($fp, 'QUIT', [221, 250]);
    fclose($fp);
    if ($code !== 250) {
        $error = 'SMTP no aceptó mensaje: ' . trim($resp);
        return false;
    }
    return true;
}

function send_email_message($empresa_info, $tipo, $to, $subject, $html)
{
    $to = trim((string) $to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $empresa_nombre = (string) (($empresa_info['nombre'] ?? 'Tu empresa'));
    $fromEmail = trim((string) get_global_setting('smtp_from_email', ''));
    $fromName = trim((string) get_global_setting('smtp_from_name', $empresa_nombre));
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'no-reply@' . (preg_replace('/^www\./', '', ($_SERVER['HTTP_HOST'] ?? 'localhost')));
    }
    $smtp = [
        'host' => get_global_setting('smtp_host', ''),
        'port' => (int) get_global_setting('smtp_port', 587),
        'user' => get_global_setting('smtp_user', ''),
        'pass' => get_global_setting('smtp_pass', ''),
        'secure' => get_global_setting('smtp_secure', 'tls'),
        'timeout' => (int) get_global_setting('smtp_timeout', 12),
    ];

    $ok = false;
    $err = null;
    $provider = 'mail';
    if (trim((string) $smtp['host']) !== '' && trim((string) $smtp['user']) !== '' && trim((string) $smtp['pass']) !== '') {
        $provider = 'smtp';
        $ok = send_via_smtp_socket($smtp, $fromEmail, $fromName, $to, $subject, $html, $err);
    } else {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        try {
            $ok = @mail($to, $subject, $html, implode("\r\n", $headers));
        } catch (Throwable $e) {
            $ok = false;
            $err = $e->getMessage();
        }
    }
    log_email_envio((int) ($empresa_info['id'] ?? 0), (string) $tipo, $to, $subject, $ok ? 'sent' : 'failed', $provider, $err);
    return $ok;
}

function ensure_password_reset_tokens_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      usuario_id BIGINT UNSIGNED NOT NULL,
      token_hash CHAR(64) NOT NULL,
      expires_at DATETIME NOT NULL,
      used_at DATETIME NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_pass_reset_token (token_hash),
      KEY idx_pass_reset_user (usuario_id, created_at),
      CONSTRAINT fk_pass_reset_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function create_password_reset_token(int $usuario_id, int $minutes = 30): ?string
{
    global $pdo;
    if ($usuario_id <= 0) {
        return null;
    }
    ensure_password_reset_tokens_table();
    $token = strtoupper(bin2hex(random_bytes(24)));
    $hash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(5, min(180, $minutes)) . ' minutes'));
    try {
        // invalidar previos activos
        $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE usuario_id = ? AND used_at IS NULL")->execute([$usuario_id]);
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (usuario_id, token_hash, expires_at) VALUES (?,?,?)");
        $stmt->execute([$usuario_id, $hash, $expiresAt]);
        return $token;
    } catch (Throwable $e) {
        return null;
    }
}

function find_password_reset_token(string $token): ?array
{
    global $pdo;
    ensure_password_reset_tokens_table();
    $hash = hash('sha256', trim($token));
    try {
        $stmt = $pdo->prepare("SELECT prt.*, u.id AS usuario_id_real, u.email, u.nombre, u.empresa_id, u.rol, e.slug AS empresa_slug, e.nombre AS empresa_nombre
                               FROM password_reset_tokens prt
                               INNER JOIN usuarios u ON u.id = prt.usuario_id
                               LEFT JOIN empresas e ON e.id = u.empresa_id
                               WHERE prt.token_hash = ?
                               LIMIT 1");
        $stmt->execute([$hash]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function send_password_reset_email(array $usuario, string $reset_url): bool
{
    $to = trim((string) ($usuario['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $empresa_info = [
        'id' => (int) ($usuario['empresa_id'] ?? 0),
        'nombre' => (string) (($usuario['empresa_nombre'] ?? '') ?: 'Reservas GP'),
    ];
    $subject = 'Recuperación de contraseña';
    $headline = 'Restablece tu contraseña';
    $lead = 'Recibimos una solicitud para cambiar tu contraseña. Este enlace es válido por 30 minutos y solo puede usarse una vez.';
    $content = '<p style="margin:0 0 14px">Hola <strong>' . htmlspecialchars((string) ($usuario['nombre'] ?? '')) . '</strong>,</p>'
        . '<p style="margin:0 0 16px">Para continuar, haz clic en el siguiente botón:</p>'
        . '<p style="margin:0 0 16px"><a href="' . htmlspecialchars($reset_url) . '" style="display:inline-block;padding:11px 18px;border-radius:999px;background:#0d9488;color:#fff;text-decoration:none;font-weight:700">Restablecer contraseña</a></p>'
        . '<p style="margin:0 0 8px;font-size:13px;color:#475569">Si no solicitaste este cambio, puedes ignorar este correo.</p>';
    $html = render_email_layout($empresa_info, $subject, $headline, $lead, $content);
    return send_email_message($empresa_info, 'password_reset', $to, $subject, $html);
}

function send_email_verification_email(array $usuario, string $verify_url): bool
{
    $to = trim((string) ($usuario['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $empresa_info = [
        'id' => (int) ($usuario['empresa_id'] ?? 0),
        'nombre' => (string) (($usuario['empresa_nombre'] ?? '') ?: 'Reservas GP'),
    ];
    $subject = 'Verifica tu correo electrónico';
    $headline = 'Confirma tu correo para activar tu acceso';
    $lead = 'Tu cuenta fue creada correctamente. Antes de iniciar sesión, confirma que este correo te pertenece.';
    $content = '<p style="margin:0 0 14px">Hola <strong>' . htmlspecialchars((string) ($usuario['nombre'] ?? '')) . '</strong>,</p>'
        . '<p style="margin:0 0 16px">Haz clic en el siguiente botón para verificar tu correo:</p>'
        . '<p style="margin:0 0 16px"><a href="' . htmlspecialchars($verify_url) . '" style="display:inline-block;padding:11px 18px;border-radius:999px;background:#0d9488;color:#fff;text-decoration:none;font-weight:700">Verificar correo</a></p>'
        . '<p style="margin:0 0 8px;font-size:13px;color:#475569">Este enlace es de un solo uso y vence en 24 horas.</p>';
    $html = render_email_layout($empresa_info, $subject, $headline, $lead, $content);
    return send_email_message($empresa_info, 'email_verification', $to, $subject, $html);
}

function build_google_calendar_url($title, $startSql, $endSql, $details, $location)
{
    try {
        $tz = new DateTimeZone(date_default_timezone_get() ?: 'UTC');
        $dtS = new DateTime((string) $startSql, $tz);
        $dtE = new DateTime((string) $endSql, $tz);
        $fmt = static function (DateTime $d): string {
            return $d->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        };
        $params = [
            'action' => 'TEMPLATE',
            'text' => (string) $title,
            'dates' => $fmt($dtS) . '/' . $fmt($dtE),
            'details' => (string) $details,
            'location' => (string) $location,
        ];
        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    } catch (Throwable $e) {
        return '';
    }
}

function send_booking_confirmation_email($empresa_info, array $payload)
{
    $to = trim((string) ($payload['to_email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $empresa_nombre = (string) ($empresa_info['nombre'] ?? 'Tu empresa');
    $subject = 'Confirmación de cita - ' . $empresa_nombre;
    $b = resolve_empresa_branding($empresa_info);
    $gcal = trim((string) ($payload['calendar_google_url'] ?? ''));
    $ics = trim((string) ($payload['calendar_ics_url'] ?? ''));
    $fecha = (string) ($payload['fecha'] ?? '');
    $hora = (string) ($payload['hora'] ?? '');
    $inicioTxt = (string) ($payload['inicio'] ?? '-');
    $precio = trim((string) ($payload['precio'] ?? ''));
    $barbero = trim((string) ($payload['empleado'] ?? ''));
    $direccion = trim((string) ($payload['direccion'] ?? ''));
    $telefono = trim((string) ($payload['telefono_contacto'] ?? ''));

    $calendarButtons = '';
    if ($gcal !== '') {
        $calendarButtons .= '<a href="' . htmlspecialchars($gcal) . '" style="display:inline-block;margin:4px 6px 4px 0;padding:10px 15px;border-radius:999px;background:' . htmlspecialchars($b['primary']) . ';color:#fff;text-decoration:none;font-weight:700;font-size:14px">Añadir a Calendar</a>';
    }
    if ($ics !== '') {
        $calendarButtons .= '<a href="' . htmlspecialchars($ics) . '" style="display:inline-block;margin:4px 0;padding:10px 15px;border-radius:999px;background:#93c5fd;color:#0f172a;text-decoration:none;font-weight:700;font-size:14px">Descargar .ics</a>';
    }
    if ($calendarButtons === '') {
        $calendarButtons = '<span style="font-size:13px;color:#64748b">No hay enlaces de calendario disponibles.</span>';
    }

    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;background:#f3f4f6;padding:20px;font-family:Arial,sans-serif;color:#0f172a">'
      . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden">'
      . '<div style="background:' . htmlspecialchars($b['primary']) . ';padding:20px;text-align:center">'
      . '<img src="' . htmlspecialchars($b['logo']) . '" alt="Logo" style="max-width:150px;max-height:72px;object-fit:contain">'
      . '</div>'
      . '<div style="padding:20px;text-align:center">'
      . '<h1 style="margin:0 0 10px;color:' . htmlspecialchars($b['primary']) . '">¡Tu cita está confirmada!</h1>'
      . '<p style="margin:0;color:#0f1d39;font-size:16px;line-height:24px">Gracias por reservar en <strong>' . htmlspecialchars($empresa_nombre) . '</strong>. Tu cita se encuentra <strong>CONFIRMADA</strong>.</p>'
      . '<div style="margin-top:20px;text-align:left;padding:12px;border:1px solid ' . htmlspecialchars($b['primary']) . ';border-radius:8px;background:#f8fafc">'
      . '<h2 style="margin:0 0 10px;color:' . htmlspecialchars($b['primary']) . ';font-size:18px">Detalles de la Cita</h2>'
      . '<p style="margin:8px 0;font-size:15px"><strong>Fecha:</strong> ' . htmlspecialchars($fecha !== '' ? $fecha : $inicioTxt) . '</p>'
      . '<p style="margin:8px 0;font-size:15px"><strong>Hora:</strong> ' . htmlspecialchars($hora !== '' ? $hora : $inicioTxt) . '</p>'
      . '<p style="margin:8px 0;font-size:15px"><strong>Servicio:</strong> ' . htmlspecialchars((string) ($payload['servicio'] ?? '-')) . '</p>'
      . ($barbero !== '' ? '<p style="margin:8px 0;font-size:15px"><strong>Barbero/Especialista:</strong> ' . htmlspecialchars($barbero) . '</p>' : '')
      . ($precio !== '' ? '<p style="margin:8px 0;font-size:15px"><strong>Precio:</strong> ' . htmlspecialchars($precio) . '</p>' : '')
      . '</div>'
      . '<p style="margin:22px 0 10px;color:' . htmlspecialchars($b['primary']) . ';font-size:16px;font-weight:bold">¡RECUERDA AGREGAR TU CITA A TU CALENDARIO!</p>'
      . '<div style="margin-top:6px">' . $calendarButtons . '</div>'
      . '<p style="margin-top:18px;color:#334155;font-size:13px"><strong>NOTA:</strong> Te recomendamos llegar 5-10 minutos antes de tu horario.</p>'
      . '</div>'
      . '<div style="background:' . htmlspecialchars($b['primary']) . ';padding:12px;text-align:center;color:#fff;font-size:12px">'
      . '<p style="margin:0 0 6px">Muchas gracias por tu preferencia. ¡Te esperamos!</p>'
      . ($direccion !== '' ? '<p style="margin:0 0 4px">Dirección: ' . htmlspecialchars($direccion) . '</p>' : '')
      . ($telefono !== '' ? '<p style="margin:0">Teléfono: ' . htmlspecialchars($telefono) . '</p>' : '')
      . '</div>'
      . '</div></body></html>';
    return send_email_message($empresa_info, 'booking_confirmation', $to, $subject, $html);
}

function ensure_resena_invitaciones_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS resena_invitaciones (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      empresa_id BIGINT UNSIGNED NOT NULL,
      cita_id BIGINT UNSIGNED NOT NULL,
      token_hash CHAR(64) NOT NULL,
      estado ENUM('pendiente','usada','expirada') NOT NULL DEFAULT 'pendiente',
      sent_at DATETIME NULL,
      used_at DATETIME NULL,
      expires_at DATETIME NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_resena_inv_cita (cita_id),
      UNIQUE KEY uq_resena_inv_token (token_hash),
      KEY idx_resena_inv_empresa_estado (empresa_id, estado),
      CONSTRAINT fk_resena_inv_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_resena_inv_cita FOREIGN KEY (cita_id) REFERENCES citas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function ensure_resena_context_table()
{
    global $pdo;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS resena_contexto (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      resena_id BIGINT UNSIGNED NOT NULL,
      empresa_id BIGINT UNSIGNED NOT NULL,
      cita_id BIGINT UNSIGNED NULL,
      servicio_id BIGINT UNSIGNED NULL,
      empleado_usuario_id BIGINT UNSIGNED NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_resena_contexto_resena (resena_id),
      KEY idx_resena_contexto_empresa (empresa_id),
      CONSTRAINT fk_resena_contexto_resena FOREIGN KEY (resena_id) REFERENCES resenas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    $ensured = true;
}

function create_review_token(): string
{
    return strtoupper(bin2hex(random_bytes(16)));
}

function send_review_invitation_email($empresa_info, array $payload)
{
    $to = trim((string) ($payload['to_email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $empresa_nombre = (string) ($empresa_info['nombre'] ?? 'Tu empresa');
    $subject = '¿Cómo fue tu cita? - ' . $empresa_nombre;
    $link = (string) ($payload['review_url'] ?? '');
    $b = resolve_empresa_branding($empresa_info);
    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;background:#f3f4f6;padding:20px;font-family:Arial,sans-serif;color:#0f172a">'
      . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden">'
      . '<div style="background:' . htmlspecialchars($b['primary']) . ';padding:20px;text-align:center">'
      . '<img src="' . htmlspecialchars($b['logo']) . '" alt="Logo" style="max-width:150px;max-height:72px;object-fit:contain">'
      . '</div>'
      . '<div style="padding:20px;text-align:center">'
      . '<h1 style="margin:0 0 10px;color:' . htmlspecialchars($b['primary']) . '">¿Cómo fue tu cita?</h1>'
      . '<p style="margin:0;color:#0f1d39;font-size:16px;line-height:24px">Tu opinión nos ayuda a mejorar. Déjanos tu reseña de forma rápida.</p>'
      . '<div style="margin-top:20px;text-align:left;padding:12px;border:1px solid ' . htmlspecialchars($b['primary']) . ';border-radius:8px;background:#f8fafc">'
      . '<p style="margin:8px 0;font-size:15px"><strong>Servicio:</strong> ' . htmlspecialchars((string) ($payload['servicio'] ?? '-')) . '</p>'
      . '<p style="margin:8px 0;font-size:15px"><strong>Sucursal:</strong> ' . htmlspecialchars((string) ($payload['sucursal'] ?? '-')) . '</p>'
      . '<p style="margin:8px 0;font-size:15px"><strong>Fecha:</strong> ' . htmlspecialchars((string) ($payload['inicio'] ?? '-')) . '</p>'
      . '</div>'
      . '<p style="margin-top:20px"><a href="' . htmlspecialchars($link) . '" style="display:inline-block;padding:11px 18px;border-radius:999px;background:' . htmlspecialchars($b['primary']) . ';color:#fff;text-decoration:none;font-weight:700">Dejar reseña</a></p>'
      . '<p style="margin-top:14px;color:#334155;font-size:13px">Este enlace vence en 30 días y solo puede usarse una vez.</p>'
      . '</div>'
      . '<div style="background:' . htmlspecialchars($b['primary']) . ';padding:12px;text-align:center;color:#fff;font-size:12px">'
      . '<p style="margin:0">Gracias por confiar en ' . htmlspecialchars($empresa_nombre) . '.</p>'
      . '</div>'
      . '</div></body></html>';
    return send_email_message($empresa_info, 'review_invitation', $to, $subject, $html);
}

function maybe_send_review_invitation_for_cita($empresa_id, $cita_id): bool
{
    global $pdo;
    $empresa_id = (int) $empresa_id;
    $cita_id = (int) $cita_id;
    if ($empresa_id <= 0 || $cita_id <= 0) {
        return false;
    }
    // Verificar si las encuestas están activas para esta empresa
    if (!empresa_get_encuestas_activas($empresa_id)) {
        return false;
    }
    ensure_resena_invitaciones_table();
    try {
        $stmt = $pdo->prepare("SELECT c.id, c.empresa_id, c.sucursal_id, c.servicio_id, c.cliente_nombre, c.cliente_email, c.inicio,
                                      s.nombre AS sucursal_nombre, srv.nombre AS servicio_nombre, e.slug, e.nombre AS empresa_nombre
                               FROM citas c
                               LEFT JOIN sucursales s ON s.id = c.sucursal_id
                               LEFT JOIN servicios srv ON srv.id = c.servicio_id
                               LEFT JOIN empresas e ON e.id = c.empresa_id
                               WHERE c.id = ? AND c.empresa_id = ? AND c.estado = 'completada'
                               LIMIT 1");
        $stmt->execute([$cita_id, $empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            return false;
        }
        $to = trim((string) ($row['cliente_email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $stmtInv = $pdo->prepare("SELECT id, estado, expires_at FROM resena_invitaciones WHERE cita_id = ? LIMIT 1");
        $stmtInv->execute([$cita_id]);
        $inv = $stmtInv->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($inv && ($inv['estado'] ?? '') === 'usada') {
            return false;
        }

        $token = create_review_token();
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        if ($inv) {
            $upd = $pdo->prepare("UPDATE resena_invitaciones
                                  SET token_hash = ?, estado = 'pendiente', sent_at = NOW(), used_at = NULL, expires_at = ?
                                  WHERE id = ?");
            $upd->execute([$tokenHash, $expiresAt, (int) $inv['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO resena_invitaciones (empresa_id, cita_id, token_hash, estado, sent_at, expires_at)
                                  VALUES (?,?,?,?,NOW(),?)");
            $ins->execute([$empresa_id, $cita_id, $tokenHash, 'pendiente', $expiresAt]);
        }

        $empresa_ref = trim((string) ($row['slug'] ?? '')) !== '' ? (string) $row['slug'] : (string) $empresa_id;
        $reviewUrl = url_add_query(app_url_absolute(view_url('vistas/public/resena.php', $empresa_ref)), ['token' => $token]);
        $ok = send_review_invitation_email(
            ['id' => (int) $empresa_id, 'nombre' => (string) ($row['empresa_nombre'] ?? 'Tu empresa')],
            [
                'to_email' => $to,
                'cliente_nombre' => (string) ($row['cliente_nombre'] ?? ''),
                'sucursal' => (string) ($row['sucursal_nombre'] ?? ''),
                'servicio' => (string) ($row['servicio_nombre'] ?? ''),
                'inicio' => date('d/m/Y H:i', strtotime((string) ($row['inicio'] ?? 'now'))),
                'review_url' => $reviewUrl,
            ]
        );
        return (bool) $ok;
    } catch (Throwable $e) {
        return false;
    }
}

function dispatch_pending_review_invitations($empresa_id, $limit = 20)
{
    global $pdo;
    $empresa_id = (int) $empresa_id;
    $limit = max(1, min(100, (int) $limit));
    if ($empresa_id <= 0) {
        return 0;
    }
    ensure_resena_invitaciones_table();
    try {
        $stmt = $pdo->prepare("SELECT c.id
                               FROM citas c
                               LEFT JOIN resena_invitaciones ri ON ri.cita_id = c.id
                               WHERE c.empresa_id = ?
                                 AND c.estado = 'completada'
                                 AND c.cliente_email IS NOT NULL
                                 AND c.cliente_email <> ''
                                 AND ri.id IS NULL
                               ORDER BY c.id DESC
                               LIMIT $limit");
        $stmt->execute([$empresa_id]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $sent = 0;
        foreach ($ids as $id) {
            if (maybe_send_review_invitation_for_cita($empresa_id, (int) $id)) {
                $sent++;
            }
        }
        return $sent;
    } catch (Throwable $e) {
        return 0;
    }
}

function send_superadmin_smtp_test_email($to_email)
{
    $to_email = trim((string) $to_email);
    if ($to_email === '' || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $sys = (string) get_global_setting('system_name', 'Reservas GP');
    $empresa_info = ['id' => 0, 'nombre' => $sys];
    $subject = 'Prueba SMTP - ' . $sys;
    $content = '<p style="margin:0 0 8px"><strong>Estado:</strong> Configuración SMTP operativa.</p>'
      . '<p style="margin:0 0 8px"><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>'
      . '<p style="margin:0"><strong>Servidor:</strong> ' . htmlspecialchars((string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) . '</p>';
    $html = render_email_layout($empresa_info, $subject, 'Prueba de correo exitosa', 'Este correo confirma que el motor SMTP está funcionando.', $content);
    return send_email_message($empresa_info, 'smtp_test', $to_email, $subject, $html);
}

// ─────────────────────────────────────────────────────────────────────────────
// NOTIFICACIONES TELEGRAM (Superadmin + Empresas)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Envía mensaje a Telegram para notificaciones de sistema.
 * Prioridad: superadmin (servidor, suscripciones, nuevos registros)
 * Futuro: empresas individuales (citas, mensajes)
 *
 * @param string $message Mensaje a enviar
 * @param string $tipo Tipo de notificación: 'superadmin', 'empresa'
 * @param int|null $empresa_id ID de empresa (para notificaciones específicas)
 * @return bool
 */
function telegram_notify(string $message, string $tipo = 'superadmin', ?int $empresa_id = null): bool
{
    global $pdo;

    // Configuración para superadmin (desde ajustes globales)
    if ($tipo === 'superadmin') {
        $token = get_global_setting('telegram_superadmin_token', '');
        $chatId = get_global_setting('telegram_superadmin_chat_id', '');

        // Fallback a constantes si no hay ajustes en BD
        if ($token === '') {
            $token = defined('TELEGRAM_SUPERADMIN_TOKEN') ? TELEGRAM_SUPERADMIN_TOKEN : '';
        }
        if ($chatId === '') {
            $chatId = defined('TELEGRAM_SUPERADMIN_CHAT_ID') ? TELEGRAM_SUPERADMIN_CHAT_ID : '';
        }

        if ($token === '' || $chatId === '') {
            error_log('Telegram: No configurado para superadmin');
            return false;
        }

        return telegram_send_message($token, $chatId, $message);
    }

    // Configuración por empresa (futura implementación)
    if ($tipo === 'empresa' && $empresa_id !== null && $empresa_id > 0) {
        $enabled = get_empresa_setting($empresa_id, 'telegram_notificaciones_activas', '0');
        if ((string) $enabled !== '1') {
            return false; // Notificaciones desactivadas para esta empresa
        }

        $token = get_empresa_setting($empresa_id, 'telegram_bot_token', '');
        $chatId = get_empresa_setting($empresa_id, 'telegram_chat_id', '');

        if ($token === '' || $chatId === '') {
            return false;
        }

        return telegram_send_message($token, $chatId, $message);
    }

    return false;
}

/**
 * Envía mensaje directo a Telegram API
 *
 * @param string $token Bot token
 * @param string $chatId Chat ID
 * @param string $message Mensaje
 * @return bool
 */
function telegram_send_message(string $token, string $chatId, string $message): bool
{
    if ($token === '' || $chatId === '' || $message === '') {
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') {
        error_log("Telegram cURL error: {$error}");
        return false;
    }

    if ($httpCode !== 200) {
        error_log("Telegram HTTP error: {$httpCode}, response: {$response}");
        return false;
    }

    $data = json_decode($response, true);
    if (!isset($data['ok']) || $data['ok'] !== true) {
        error_log("Telegram API error: " . ($data['description'] ?? 'Unknown'));
        return false;
    }

    return true;
}

/**
 * Crea tabla ajustes_empresa si no existe
 */
function ajustes_empresa_ensure_table(): void
{
    global $pdo;
    static $ensured = false;
    if ($ensured) return;

    $sql = "CREATE TABLE IF NOT EXISTS ajustes_empresa (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id BIGINT UNSIGNED NOT NULL,
        clave VARCHAR(120) NOT NULL,
        valor TEXT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_ajustes_empresa_empresa_clave (empresa_id, clave),
        KEY idx_ajustes_empresa_clave (clave),
        CONSTRAINT fk_ajustes_empresa_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
            ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        error_log('Error creando tabla ajustes_empresa: ' . $e->getMessage());
    }
    $ensured = true;
}

/**
 * Establece ajuste específico de una empresa
 *
 * @param int $empresa_id
 * @param string $key
 * @param string|null $value
 * @return bool
 */
function set_empresa_setting(int $empresa_id, string $key, ?string $value): bool
{
    global $pdo;
    ajustes_empresa_ensure_table();

    if ($empresa_id <= 0 || $key === '') {
        return false;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO ajustes_empresa (empresa_id, clave, valor) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");
        $stmt->execute([$empresa_id, $key, $value]);
        return true;
    } catch (Throwable $e) {
        error_log('Error guardando ajuste empresa: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene ajuste específico de una empresa
 *
 * @param int $empresa_id
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_empresa_setting(int $empresa_id, string $key, $default = null)
{
    global $pdo;
    ajustes_empresa_ensure_table();

    if ($empresa_id <= 0 || $key === '') {
        return $default;
    }

    try {
        $stmt = $pdo->prepare("SELECT valor FROM ajustes_empresa WHERE empresa_id = ? AND clave = ? LIMIT 1");
        $stmt->execute([$empresa_id, $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

/**
 * Notificación de nuevo registro de empresa (para superadmin)
 *
 * @param array $empresaDatos ['nombre', 'slug', 'email', 'plan']
 * @return bool
 */
function telegram_notify_nueva_empresa(array $empresaDatos): bool
{
    $nombre = htmlspecialchars($empresaDatos['nombre'] ?? 'Desconocida');
    $slug = htmlspecialchars($empresaDatos['slug'] ?? 'N/A');
    $email = htmlspecialchars($empresaDatos['email'] ?? 'N/A');
    $plan = htmlspecialchars($empresaDatos['plan'] ?? 'Básico');

    $message = "🎉 <b>Nuevo registro de empresa</b>\n\n";
    $message .= "🏢 <b>Empresa:</b> {$nombre}\n";
    $message .= "🔗 <b>Slug:</b> {$slug}\n";
    $message .= "📧 <b>Email:</b> {$email}\n";
    $message .= "⭐ <b>Plan:</b> {$plan}\n";
    $message .= "⏰ <b>Fecha:</b> " . date('d/m/Y H:i:s') . "\n";

    return telegram_notify($message, 'superadmin');
}

/**
 * Notificación de alerta del sistema (para superadmin)
 *
 * @param string $tipo 'servidor_caido', 'sin_espacio', 'suscripcion_vencida', 'error_critico'
 * @param array $detalles
 * @return bool
 */
function telegram_notify_alerta_sistema(string $tipo, array $detalles = []): bool
{
    $iconos = [
        'servidor_caido' => '🔥',
        'sin_espacio' => '💾',
        'suscripcion_vencida' => '⚠️',
        'error_critico' => '🚨',
        'mensaje_superadmin' => '📨',
    ];

    $titulos = [
        'servidor_caido' => 'SERVIDOR CAÍDO',
        'sin_espacio' => 'SIN ESPACIO EN DISCO',
        'suscripcion_vencida' => 'SUSCRIPCIÓN VENCIDA',
        'error_critico' => 'ERROR CRÍTICO',
        'mensaje_superadmin' => 'NUEVO MENSAJE PARA SUPERADMIN',
    ];

    $icono = $iconos[$tipo] ?? '⚡';
    $titulo = $titulos[$tipo] ?? 'ALERTA DE SISTEMA';

    $message = "{$icono} <b>{$titulo}</b>\n\n";

    foreach ($detalles as $key => $value) {
        $message .= "• <b>{$key}:</b> {$value}\n";
    }

    $message .= "⏰ <b>Detectado:</b> " . date('d/m/Y H:i:s') . "\n";
    $message .= "🖥️ <b>Servidor:</b> " . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'localhost') . "\n";

    return telegram_notify($message, 'superadmin');
}

// ─────────────────────────────────────────────────────────────────────────────
// NOTIFICACIONES TELEGRAM PARA EMPRESAS (PLANES DE PAGO)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Verifica si la empresa tiene plan de pago (para habilitar Telegram)
 */
function empresa_tiene_plan_pago(int $empresa_id): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.nombre FROM empresas e LEFT JOIN planes p ON e.plan_id = p.id WHERE e.id = ? LIMIT 1");
        $stmt->execute([$empresa_id]);
        $plan = $stmt->fetchColumn();
        // Considerar "basico" o "básico" como plan gratuito, todo lo demás es de pago
        $plan = strtolower((string) $plan);
        return !in_array($plan, ['basico', 'básico', 'gratuito', 'free', ''], true);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Genera una API key única para notificaciones Telegram
 */
function generar_api_key_telegram(): string
{
    return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
}

/**
 * Activa notificaciones Telegram para un usuario
 */
function telegram_activar_usuario(int $usuario_id, string $chat_id, ?string $telegram_username = null): ?array
{
    global $pdo;

    // Verificar plan de pago
    $stmt = $pdo->prepare("SELECT empresa_id FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$usuario_id]);
    $empresa_id = (int) $stmt->fetchColumn();

    if (!$empresa_id || !empresa_tiene_plan_pago($empresa_id)) {
        return null; // Solo planes de pago
    }

    $api_key = generar_api_key_telegram();

    // Alertas por defecto según rol
    $alertas_default = [
        'cita_nueva' => true,
        'cita_cancelada' => true,
        'cita_completada' => true,
        'cita_auto_completada' => true,
        'mensaje_interno' => true,
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO notificaciones_telegram
            (usuario_id, api_key, chat_id, telegram_username, alertas_config)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                chat_id = VALUES(chat_id),
                telegram_username = VALUES(telegram_username),
                activo = 1,
                alertas_config = COALESCE(alertas_config, VALUES(alertas_config)),
                updated_at = NOW()");
        $stmt->execute([$usuario_id, $api_key, $chat_id, $telegram_username, json_encode($alertas_default)]);

        return [
            'api_key' => $api_key,
            'chat_id' => $chat_id,
        ];
    } catch (Throwable $e) {
        error_log('Error activando Telegram: ' . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene configuración de Telegram de un usuario
 */
function telegram_get_config_usuario(int $usuario_id): ?array
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM notificaciones_telegram WHERE usuario_id = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$usuario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['alertas_config'] = json_decode($row['alertas_config'] ?? '{}', true);
            return $row;
        }
        return null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Verifica si un tipo de alerta está activo para el usuario
 */
function telegram_alerta_activa(int $usuario_id, string $tipo_alerta): bool
{
    $config = telegram_get_config_usuario($usuario_id);
    if (!$config) return false;
    return (bool) ($config['alertas_config'][$tipo_alerta] ?? false);
}

/**
 * Actualiza las alertas activas para un usuario
 */
function telegram_set_alertas(int $usuario_id, array $alertas): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE notificaciones_telegram SET alertas_config = ? WHERE usuario_id = ? AND activo = 1");
        $stmt->execute([json_encode($alertas), $usuario_id]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Desactiva notificaciones Telegram para un usuario
 */
function telegram_desactivar_usuario(int $usuario_id): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE notificaciones_telegram SET activo = 0 WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Envía notificación a un usuario específico (si tiene Telegram activo)
 */
function telegram_notify_usuario(int $usuario_id, string $message, string $tipo_alerta): bool
{
    $config = telegram_get_config_usuario($usuario_id);
    if (!$config) return false;

    // Verificar si esta alerta está activa para el usuario
    if (!($config['alertas_config'][$tipo_alerta] ?? false)) {
        return false; // Usuario no quiere este tipo de alerta
    }

    return telegram_send_message($config['api_key'], $config['chat_id'], $message);
}

/**
 * Notificación de nueva cita (para empleado asignado o gerente según rol)
 */
function telegram_notify_cita_nueva(int $cita_id, int $usuario_id_destino, string $rol): bool
{
    global $pdo;

    // Obtener datos de la cita
    $stmt = $pdo->prepare("SELECT c.*, s.nombre as sucursal, cl.nombre as cliente
                           FROM citas c
                           LEFT JOIN sucursales s ON c.sucursal_id = s.id
                           LEFT JOIN clientes cl ON c.cliente_id = cl.id
                           WHERE c.id = ? LIMIT 1");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cita) return false;

    $cliente = htmlspecialchars($cita['cliente_nombre'] ?: ($cita['cliente'] ?? 'Cliente'));
    $servicio = htmlspecialchars($cita['servicio_nombre'] ?? 'Servicio');
    $sucursal = htmlspecialchars($cita['sucursal'] ?? 'Sucursal');
    $fecha = date('d/m/Y H:i', strtotime($cita['inicio']));

    $message = "📅 <b>Nueva cita asignada</b>\n\n";
    $message .= "👤 <b>Cliente:</b> {$cliente}\n";
    $message .= "💇 <b>Servicio:</b> {$servicio}\n";
    $message .= "🏢 <b>Sucursal:</b> {$sucursal}\n";
    $message .= "📆 <b>Fecha:</b> {$fecha}\n";

    return telegram_notify_usuario($usuario_id_destino, $message, 'cita_nueva');
}

/**
 * Notificación de cita cancelada
 */
function telegram_notify_cita_cancelada(int $cita_id, int $usuario_id_destino, string $cancelado_por): bool
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT c.*, cl.nombre as cliente FROM citas c LEFT JOIN clientes cl ON c.cliente_id = cl.id WHERE c.id = ? LIMIT 1");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cita) return false;

    $cliente = htmlspecialchars($cita['cliente_nombre'] ?: ($cita['cliente'] ?? 'Cliente'));
    $fecha = date('d/m/Y H:i', strtotime($cita['inicio']));

    $message = "❌ <b>Cita cancelada</b>\n\n";
    $message .= "👤 <b>Cliente:</b> {$cliente}\n";
    $message .= "📆 <b>Fecha:</b> {$fecha}\n";
    $message .= "🚫 <b>Cancelado por:</b> {$cancelado_por}\n";

    return telegram_notify_usuario($usuario_id_destino, $message, 'cita_cancelada');
}

/**
 * Notificación de cita completada
 */
function telegram_notify_cita_completada(int $cita_id, int $usuario_id_destino): bool
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT c.*, cl.nombre as cliente FROM citas c LEFT JOIN clientes cl ON c.cliente_id = cl.id WHERE c.id = ? LIMIT 1");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cita) return false;

    $cliente = htmlspecialchars($cita['cliente_nombre'] ?: ($cita['cliente'] ?? 'Cliente'));
    $fecha = date('d/m/Y H:i', strtotime($cita['inicio']));

    $message = "✅ <b>Cita completada</b>\n\n";
    $message .= "👤 <b>Cliente:</b> {$cliente}\n";
    $message .= "📆 <b>Fecha:</b> {$fecha}\n";
    $message .= "✨ La cita ha sido marcada como completada.\n";

    return telegram_notify_usuario($usuario_id_destino, $message, 'cita_completada');
}

/**
 * Notificación de mensaje interno
 */
function telegram_notify_mensaje(int $mensaje_id, int $usuario_id_destino): bool
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT mi.*, u.nombre as remitente FROM mensajes_internos mi
                           LEFT JOIN usuarios u ON mi.de_usuario_id = u.id
                           WHERE mi.id = ? LIMIT 1");
    $stmt->execute([$mensaje_id]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$msg) return false;

    $remitente = htmlspecialchars($msg['remitente'] ?? 'Usuario');
    $titulo = htmlspecialchars($msg['titulo'] ?? 'Mensaje');
    $cuerpo = htmlspecialchars(strip_tags($msg['cuerpo'] ?? ''));

    // Truncar cuerpo si es muy largo
    if (mb_strlen($cuerpo) > 150) {
        $cuerpo = mb_substr($cuerpo, 0, 150) . '...';
    }

    $message = "📨 <b>Nuevo mensaje interno</b>\n\n";
    $message .= "👤 <b>De:</b> {$remitente}\n";
    $message .= "📌 <b>Asunto:</b> {$titulo}\n";
    $message .= "📝 <b>Mensaje:</b> {$cuerpo}\n";

    return telegram_notify_usuario($usuario_id_destino, $message, 'mensaje_interno');
}

/**
 * Obtiene configuración de encuestas al completar cita
 */
function empresa_get_encuestas_activas(int $empresa_id): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ? LIMIT 1");
        $stmt->execute([$empresa_id]);
        $config = $stmt->fetchColumn();
        $data = json_decode($config ?: '{}', true);
        // Por defecto activado (true) si no está definido
        return ($data['encuestas_activas'] ?? '1') === '1' || ($data['encuestas_activas'] ?? true) === true;
    } catch (Throwable $e) {
        return true; // Por defecto activado
    }
}

/**
 * Establece configuración de encuestas al completar cita
 */
function empresa_set_encuestas_activas(int $empresa_id, bool $activo): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ? LIMIT 1");
        $stmt->execute([$empresa_id]);
        $config = $stmt->fetchColumn();
        $data = json_decode($config ?: '{}', true);
        $data['encuestas_activas'] = $activo ? '1' : '0';

        $stmt = $pdo->prepare("UPDATE empresas SET config_json = ? WHERE id = ?");
        $stmt->execute([json_encode($data), $empresa_id]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
