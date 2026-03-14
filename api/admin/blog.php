<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
//print_r($_SESSION);
$user = current_user();

$id_e = request_id_e();

$role = $user['rol'] ?? null;
$empresa_id = (int) ($user['empresa_id'] ?? 0);

// if ($role === 'superadmin' && $id_e) {
//     $stmt = $pdo->prepare('SELECT id FROM empresas WHERE slug = ?');
//     $stmt->execute([$id_e]);
//     $empresa_id = (int) $stmt->fetchColumn();
// }
$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['superadmin', 'admin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);
switch ($action) {
    case 'list':
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(1, intval($_GET['per'] ?? 10));
        $search = $_GET['search'] ?? '';
        $only_active = isset($_GET['active_only']) ? "AND publicado=1" : "";

        $where = ["empresa_id = ?"];
        $params = [$empresa_id];

        if ($search !== '') {
            $where[] = "(titulo LIKE ? OR contenido LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (isset($_GET['active_only'])) {
            $where[] = "publicado=1";
        }

        $whereSql = implode(" AND ", $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM blog_posts b
                
                WHERE b.$whereSql ORDER BY b.id DESC LIMIT $per OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'total_pages' => $total_pages
        ]);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM blog_posts b WHERE b.id = ? AND b.empresa_id = ?");
        $stmt->execute([$id, $empresa_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            json_response(['success' => true, 'data' => $post]);
        } else {
            json_response(['success' => false, 'message' => 'Publicación no encontrada']);
        }
        break;

    case 'save':
        $id = intval($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $publicado = isset($_POST['publicado']) ? (int) $_POST['publicado'] : 0;
        $slug = trim($_POST['slug'] ?? '');

        if ($titulo === '')
            json_response(['success' => false, 'message' => 'Título obligatorio']);
        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo), '-'));
        }

        $imagen_path = null;
        if (!empty($_FILES['imagen']['name'])) {
            $dir = __DIR__ . '/../../assets/blog/' . $empresa_id . '/';
            if (!is_dir($dir))
                mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $filename = 'post_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . $filename)) {
                $imagen_path = 'assets/blog/' . $empresa_id . '/' . $filename;
            }
        }

        try {
            if ($id > 0) {
                // Verificar pertenencia
                $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE id=? AND empresa_id=?");
                $stmt->execute([$id, $empresa_id]);
                if (!$stmt->fetch())
                    json_response(['success' => false, 'message' => 'No autorizado'], 403);

                $sql = "UPDATE blog_posts SET titulo=?, contenido=?, publicado=?, slug=? " . ($imagen_path ? ", imagen_path=?" : "") . " WHERE id=? AND empresa_id=?";
                $params = [$titulo, $contenido, $publicado, $slug];
                if ($imagen_path)
                    $params[] = $imagen_path;
                $params[] = $id;
                $params[] = $empresa_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_posts (empresa_id, titulo, slug, contenido, imagen_path, publicado, publicado_at) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$empresa_id, $titulo, $slug, $contenido, $imagen_path, $publicado, $publicado ? date('Y-m-d H:i:s') : null]);
            }
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
