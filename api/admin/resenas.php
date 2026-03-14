<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// Alias CRUD (para estandarizar)
if ($action === 'create')
    $action = 'save';
if ($action === 'read')
    $action = 'get';
if ($action === 'update')
    $action = 'save';

$user = current_user();
$id_e = request_id_e();

// Public
if ($action === 'list_public') {
    if (!$id_e)
        json_response(['success' => false, 'message' => 'Empresa no especificada.'], 400);

    $stmt_emp = $pdo->prepare('SELECT id FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1');
    $stmt_emp->execute([$id_e]);
    $empresa_id = (int) ($stmt_emp->fetchColumn() ?: 0);
    if ($empresa_id <= 0)
        json_response(['success' => true, 'data' => []]);

    $limit = max(1, min(20, (int) ($_GET['limit'] ?? 3)));
    $stmt = $pdo->prepare('SELECT autor_nombre as autor, comentario, rating FROM resenas WHERE empresa_id = ? AND visible_en_home = 1 AND activo = 1 ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $empresa_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Auth tenant
$role = $user['rol'] ?? null;
$empresa_id = (int) ($user['empresa_id'] ?? 0);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['admin', 'superadmin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));

        $whereSql = 'empresa_id = ?';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE $whereSql");
        $stmt->execute([$empresa_id]);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT * FROM resenas WHERE $whereSql ORDER BY id DESC LIMIT $per OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM resenas WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $autor_nombre = trim($_POST['autor_nombre'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');
        $rating = (int) ($_POST['rating'] ?? 5);
        if ($rating < 1)
            $rating = 1;
        if ($rating > 5)
            $rating = 5;

        $visible_en_home = isset($_POST['visible_en_home']) ? (int) ($_POST['visible_en_home']) : 0;
        $activo = isset($_POST['activo']) ? (int) ($_POST['activo']) : 1;

        if ($autor_nombre === '' || $comentario === '')
            json_response(['success' => false, 'message' => 'Nombre y comentario requeridos.']);

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE resenas SET autor_nombre=?, comentario=?, rating=?, visible_en_home=?, activo=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$autor_nombre, $comentario, $rating, $visible_en_home, $activo, $id, $empresa_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO resenas (empresa_id, autor_nombre, comentario, rating, visible_en_home, activo) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$empresa_id, $autor_nombre, $comentario, $rating, $visible_en_home, $activo]);
                $id = (int) $pdo->lastInsertId();
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error al guardar.']);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.']);

        // Borrado lógico (NO eliminar fila)
        $stmt = $pdo->prepare('UPDATE resenas SET activo = 0 WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
