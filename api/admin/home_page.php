<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);

if (!$user || $empresa_id <= 0 || !in_array($role, ['admin', 'superadmin'], true)) {
    json_response(['error' => 'unauthorized'], 403);
}

home_page_ensure_table();
$allowed_modules = array_keys(home_page_module_defaults());

function hp_bootstrap_sections($empresa_id)
{
    global $pdo, $allowed_modules;
    $defs = home_page_module_defaults();
    $order = 1;
    foreach ($allowed_modules as $mod) {
        $row = home_page_get_section((int) $empresa_id, $mod);
        $limite = max(1, (int) ($row['limite'] ?? $defs[$mod]['limite']));
        $orden_actual = (int) ($row['orden'] ?? 0);
        if ($orden_actual <= 0) {
            $upd = $pdo->prepare('UPDATE home_page SET orden = ?, limite = COALESCE(NULLIF(limite,0), ?) WHERE empresa_id = ? AND modulo = ?');
            $upd->execute([$order, $limite, (int) $empresa_id, $mod]);
        } else {
            $upd = $pdo->prepare('UPDATE home_page SET limite = COALESCE(NULLIF(limite,0), ?) WHERE empresa_id = ? AND modulo = ?');
            $upd->execute([$limite, (int) $empresa_id, $mod]);
        }
        $order++;
    }
    $stmt = $pdo->prepare('SELECT id FROM home_page WHERE empresa_id = ? ORDER BY orden ASC, id ASC');
    $stmt->execute([(int) $empresa_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $n = 1;
    $upd = $pdo->prepare('UPDATE home_page SET orden = ? WHERE id = ? AND empresa_id = ?');
    foreach ($rows as $rid) {
        $upd->execute([$n, (int) $rid, (int) $empresa_id]);
        $n++;
    }
}

function hp_catalog_by_module($empresa_id, $modulo)
{
    global $pdo;
    switch ($modulo) {
        case 'blog':
            $stmt = $pdo->prepare('SELECT id, titulo AS nombre FROM blog_posts WHERE empresa_id = ? AND publicado = 1 ORDER BY COALESCE(publicado_at, created_at) DESC LIMIT 200');
            $stmt->execute([(int) $empresa_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        case 'usuarios':
            $stmt = $pdo->prepare("SELECT id, CONCAT(nombre, ' (', rol, ')') AS nombre FROM usuarios WHERE empresa_id = ? AND rol IN ('admin','gerente','empleado','cliente') AND activo = 1 ORDER BY nombre ASC LIMIT 300");
            $stmt->execute([(int) $empresa_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        case 'resenas':
            $stmt = $pdo->prepare("SELECT id, CONCAT(autor_nombre, ' - ', LEFT(comentario, 40)) AS nombre FROM resenas WHERE empresa_id = ? AND activo = 1 ORDER BY created_at DESC LIMIT 300");
            $stmt->execute([(int) $empresa_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        case 'servicios':
            $stmt = $pdo->prepare('SELECT id, nombre FROM servicios WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC LIMIT 300');
            $stmt->execute([(int) $empresa_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        case 'sucursales':
            $stmt = $pdo->prepare('SELECT id, nombre FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC LIMIT 300');
            $stmt->execute([(int) $empresa_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        default:
            return [];
    }
}

function hp_items_by_ids($empresa_id, $modulo, $ids)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids), static fn($v) => $v > 0)));
    if (empty($ids)) return [];
    $catalog = hp_catalog_by_module($empresa_id, $modulo);
    $byId = [];
    foreach ($catalog as $row) {
        $byId[(int) ($row['id'] ?? 0)] = $row;
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) $out[] = $byId[$id];
    }
    return $out;
}

function hp_get_home_config($empresa_id)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, data_json FROM empresa_home_config WHERE empresa_id = ? LIMIT 1');
    $stmt->execute([(int) $empresa_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$row) {
        $stmt = $pdo->prepare('INSERT INTO empresa_home_config (empresa_id, data_json) VALUES (?, ?)');
        $stmt->execute([(int) $empresa_id, '{}']);
        return ['id' => (int) $pdo->lastInsertId(), 'data' => []];
    }
    $cfg = json_decode((string) ($row['data_json'] ?? '{}'), true);
    if (!is_array($cfg)) {
        $cfg = [];
    }
    return ['id' => (int) $row['id'], 'data' => $cfg];
}

function hp_save_home_config($empresa_id, array $cfg)
{
    global $pdo;
    $json = json_encode($cfg, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare('UPDATE empresa_home_config SET data_json = ? WHERE empresa_id = ?');
    $stmt->execute([$json, (int) $empresa_id]);
}

hp_bootstrap_sections($empresa_id);

switch ($action) {
    case 'get_hero':
        $row = hp_get_home_config($empresa_id);
        $cfg = $row['data'] ?? [];
        json_response(['success' => true, 'data' => [
            'hero_visible' => (int) ($cfg['hero_visible'] ?? 1),
            'hero_tipo' => max(1, min(3, (int) ($cfg['hero_tipo'] ?? 1))),
            'hero_titulo' => (string) ($cfg['hero_titulo'] ?? 'Bienvenid@'),
            'hero_subtitulo' => (string) ($cfg['hero_subtitulo'] ?? ''),
            'hero_btn_texto' => (string) ($cfg['hero_btn_texto'] ?? 'Agendar cita'),
            'hero_btn_link' => (string) ($cfg['hero_btn_link'] ?? ''),
            'hero_imagen' => (string) ($cfg['hero_imagen'] ?? ''),
        ]]);
        break;

    case 'save_hero':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }
        $row = hp_get_home_config($empresa_id);
        $cfg = $row['data'] ?? [];
        $cfg['hero_visible'] = isset($_POST['hero_visible']) ? ((int) $_POST['hero_visible'] === 1 ? 1 : 0) : 1;
        $cfg['hero_tipo'] = max(1, min(3, (int) ($_POST['hero_tipo'] ?? 1)));
        $cfg['hero_titulo'] = trim((string) ($_POST['hero_titulo'] ?? 'Bienvenid@'));
        $cfg['hero_subtitulo'] = trim((string) ($_POST['hero_subtitulo'] ?? ''));
        $cfg['hero_btn_texto'] = trim((string) ($_POST['hero_btn_texto'] ?? 'Agendar cita'));
        $cfg['hero_btn_link'] = trim((string) ($_POST['hero_btn_link'] ?? ''));
        $cfg['hero_imagen'] = trim((string) ($_POST['hero_imagen'] ?? ($cfg['hero_imagen'] ?? '')));

        if (!empty($_FILES['hero_imagen_file']['name'])) {
            $dir = __DIR__ . '/../../assets/home/' . (int) $empresa_id . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $ext = strtolower((string) pathinfo((string) $_FILES['hero_imagen_file']['name'], PATHINFO_EXTENSION));
            if (!preg_match('/^(png|jpe?g|webp|gif)$/', $ext)) {
                json_response(['success' => false, 'message' => 'Formato de imagen no válido.'], 200);
            }
            $filename = 'hero_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file((string) $_FILES['hero_imagen_file']['tmp_name'], $dir . $filename)) {
                $cfg['hero_imagen'] = 'assets/home/' . (int) $empresa_id . '/' . $filename;
            }
        }
        hp_save_home_config($empresa_id, $cfg);
        json_response(['success' => true]);
        break;

    case 'list_sections':
        $stmt = $pdo->prepare('SELECT id, empresa_id, modulo, titulo, valores_json, tipo, estado, orden, limite FROM home_page WHERE empresa_id = ? ORDER BY orden ASC, id ASC');
        $stmt->execute([$empresa_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $vals = json_decode((string) ($r['valores_json'] ?? '[]'), true);
            $r['valores'] = is_array($vals) ? array_values(array_map('intval', $vals)) : [];
            $r['valores_json'] = json_encode($r['valores'], JSON_UNESCAPED_UNICODE);
        }
        unset($r);
        json_response(['success' => true, 'data' => $rows]);
        break;

    case 'get_catalog':
        $modulo = trim((string) ($_GET['modulo'] ?? ''));
        if (!in_array($modulo, $allowed_modules, true)) {
            json_response(['success' => false, 'message' => 'Módulo inválido.'], 400);
        }
        json_response(['success' => true, 'data' => hp_catalog_by_module($empresa_id, $modulo)]);
        break;

    case 'get_selected':
        $modulo = trim((string) ($_GET['modulo'] ?? ''));
        if (!in_array($modulo, $allowed_modules, true)) {
            json_response(['success' => false, 'message' => 'Módulo inválido.'], 400);
        }
        $row = home_page_get_section($empresa_id, $modulo);
        $vals = json_decode((string) ($row['valores_json'] ?? '[]'), true);
        $vals = is_array($vals) ? $vals : [];
        json_response(['success' => true, 'data' => hp_items_by_ids($empresa_id, $modulo, $vals)]);
        break;

    case 'get_catalog_all':
        $all = [];
        foreach ($allowed_modules as $mod) {
            $all[$mod] = hp_catalog_by_module($empresa_id, $mod);
        }
        json_response(['success' => true, 'data' => $all]);
        break;

    case 'save_section':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }

        $modulo = trim((string) ($_POST['modulo'] ?? ''));
        if (!in_array($modulo, $allowed_modules, true)) {
            json_response(['success' => false, 'message' => 'Módulo inválido.'], 400);
        }

        $def = home_page_module_defaults($modulo);
        $titulo = trim((string) ($_POST['titulo'] ?? $def['titulo']));
        if ($titulo === '') {
            $titulo = $def['titulo'];
        }
        $tipo = (int) ($_POST['tipo'] ?? 1); // 1 custom, 2 recientes, 3 aleatorio
        if (!in_array($tipo, [1, 2, 3], true)) {
            $tipo = 1;
        }
        $estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;
        $estado = $estado === 1 ? 1 : 0;
        $orden = (int) ($_POST['orden'] ?? 1);
        $orden = max(1, min(20, $orden));
        $limite = max(1, min(30, (int) ($_POST['limite'] ?? $def['limite'])));

        $vals = $_POST['valores'] ?? ($_POST['valores_json'] ?? '[]');
        if (is_string($vals)) {
            $tmp = json_decode($vals, true);
            $vals = is_array($tmp) ? $tmp : [];
        }
        if (!is_array($vals)) {
            $vals = [];
        }
        $vals = array_values(array_unique(array_map('intval', $vals)));
        $vals = array_values(array_filter($vals, static fn($v) => $v > 0));
        $vals = array_slice($vals, 0, $limite);

        $row = home_page_get_section($empresa_id, $modulo);
        $stmt = $pdo->prepare('UPDATE home_page SET titulo=?, valores_json=?, tipo=?, estado=?, orden=?, limite=? WHERE id=? AND empresa_id=?');
        $stmt->execute([
            $titulo,
            json_encode($vals, JSON_UNESCAPED_UNICODE),
            $tipo,
            $estado,
            $orden,
            $limite,
            (int) $row['id'],
            $empresa_id,
        ]);

        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
