<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$slug = $_GET['id_e'] ?? 'list';

if (!$slug) {
    json_response(['success' => false, 'message' => 'Empresa no especificada']);
}

// Obtener ID de la empresa por slug
$stmt = $pdo->prepare("SELECT id FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
$stmt->execute([$slug]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    json_response(['success' => false, 'message' => 'Empresa no encontrada']);
}

$empresa_id = $empresa['id'];

switch ($action) {
    case 'list':
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(1, intval($_GET['per'] ?? 10));
        $search = $_GET['search'] ?? '';

        $where = ["empresa_id = ?", "publicado = 1"];
        $params = [$empresa_id];

        if ($search !== '') {
            $where[] = "(titulo LIKE ? OR contenido LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSql = implode(" AND ", $where);
        $offset = ($page - 1) * $per;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT b.id, b.titulo, b.slug, b.contenido, b.imagen_path, b.publicado_at  
                FROM blog_posts b
                WHERE b.$whereSql 
                ORDER BY b.publicado_at DESC, b.id DESC 
                LIMIT $per OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'total_pages' => ceil($total / $per)
        ]);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM blog_posts b WHERE b.id = ? AND b.empresa_id = ? AND b.publicado = 1");
        $stmt->execute([$id, $empresa_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            json_response(['success' => true, 'data' => $post]);
        } else {
            json_response(['success' => false, 'message' => 'Publicación no encontrada']);
        }
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
