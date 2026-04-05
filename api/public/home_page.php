<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

home_page_ensure_table();

$empresa_ref = $_GET['empresa'] ?? ($_GET['id_e'] ?? ($_GET['slug'] ?? null));
$empresa_ref = is_string($empresa_ref) ? trim($empresa_ref) : $empresa_ref;
if ($empresa_ref === null || $empresa_ref === '') {
    json_response(['success' => false, 'message' => 'Empresa no especificada.'], 400);
}

if (is_numeric($empresa_ref)) {
    $stmt = $pdo->prepare('SELECT id, slug, nombre FROM empresas WHERE id = ? AND activo = 1 LIMIT 1');
    $stmt->execute([(int) $empresa_ref]);
} else {
    $stmt = $pdo->prepare('SELECT id, slug, nombre FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1');
    $stmt->execute([(string) $empresa_ref]);
}
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$empresa) {
    json_response(['success' => false, 'message' => 'Empresa no encontrada.'], 404);
}
$empresa_id = (int) $empresa['id'];

$sectionsStmt = $pdo->prepare('SELECT modulo, titulo, valores_json, tipo, estado, orden, limite FROM home_page WHERE empresa_id = ? AND estado = 1 ORDER BY orden ASC, id ASC');
$sectionsStmt->execute([$empresa_id]);
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

function hp_ids_from_json($json)
{
    $arr = json_decode((string) $json, true);
    if (!is_array($arr)) {
        return [];
    }
    $arr = array_values(array_unique(array_map('intval', $arr)));
    return array_values(array_filter($arr, static fn($v) => $v > 0));
}

function hp_build_order_case($ids, $column = 'id')
{
    $parts = [];
    foreach ($ids as $idx => $id) {
        $parts[] = "WHEN " . (int) $id . " THEN " . (int) $idx;
    }
    if (!$parts) {
        return '';
    }
    return "CASE {$column} " . implode(' ', $parts) . " END";
}

function hp_query_items($pdo, $empresa_id, $modulo, $tipo, $ids, $limite)
{
    $limite = max(1, min(30, (int) $limite));
    $tipo = (int) $tipo;

    $cfg = [
        'blog' => [
            'table' => 'blog_posts',
            'fields' => 'id, titulo, slug, contenido, imagen_path, publicado_at, created_at',
            'where' => 'empresa_id = ? AND publicado = 1',
            'order_recent' => 'COALESCE(publicado_at, created_at) DESC',
            'order_default' => 'id DESC',
        ],
        'usuarios' => [
            'table' => 'usuarios',
            'fields' => 'id, nombre, rol, foto_path',
            'where' => "empresa_id = ? AND activo = 1 AND rol IN ('admin','gerente','empleado','cliente')",
            'order_recent' => 'id DESC',
            'order_default' => 'nombre ASC',
        ],
        'resenas' => [
            'table' => 'resenas',
            'fields' => 'id, autor_nombre, comentario, rating, created_at',
            'where' => 'empresa_id = ? AND activo = 1',
            'order_recent' => 'created_at DESC',
            'order_default' => 'id DESC',
        ],
        'servicios' => [
            'table' => 'servicios',
            'fields' => 'id, nombre, descripcion, duracion_minutos, precio_base',
            'where' => 'empresa_id = ? AND activo = 1',
            'order_recent' => 'id DESC',
            'order_default' => 'nombre ASC',
        ],
        'sucursales' => [
            'table' => 'sucursales',
            'fields' => 'id, nombre, slug, direccion, telefono, horarios_json, foto_path',
            'where' => 'empresa_id = ? AND activo = 1',
            'order_recent' => 'id DESC',
            'order_default' => 'nombre ASC',
        ],
    ];

    if (!isset($cfg[$modulo])) {
        return [];
    }
    $c = $cfg[$modulo];

    $params = [$empresa_id];
    $where = $c['where'];
    $order = $c['order_default'];

    if ($tipo === 1) { // custom
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $where .= " AND id IN ($in)";
        foreach ($ids as $id) {
            $params[] = (int) $id;
        }
        $orderCase = hp_build_order_case($ids, 'id');
        if ($orderCase !== '') {
            $order = $orderCase;
        }
    } elseif ($tipo === 2) { // recientes
        $order = $c['order_recent'];
    } elseif ($tipo === 3) { // aleatorio
        $order = 'RAND()';
    }

    $sql = "SELECT {$c['fields']} FROM {$c['table']} WHERE {$where} ORDER BY {$order} LIMIT {$limite}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$payload = [];
foreach ($sections as $s) {
    $modulo = (string) ($s['modulo'] ?? '');
    $ids = hp_ids_from_json($s['valores_json'] ?? '[]');
    $items = hp_query_items($pdo, $empresa_id, $modulo, (int) ($s['tipo'] ?? 1), $ids, (int) ($s['limite'] ?? 3));
    $payload[] = [
        'modulo' => $modulo,
        'titulo' => (string) ($s['titulo'] ?? ucfirst($modulo)),
        'tipo' => (int) ($s['tipo'] ?? 1),
        'estado' => (int) ($s['estado'] ?? 1),
        'orden' => (int) ($s['orden'] ?? 0),
        'limite' => (int) ($s['limite'] ?? 3),
        'valores' => $ids,
        'items' => $items,
    ];
}

json_response([
    'success' => true,
    'empresa' => ['id' => $empresa_id, 'slug' => $empresa['slug'], 'nombre' => $empresa['nombre']],
    'sections' => $payload,
]);
