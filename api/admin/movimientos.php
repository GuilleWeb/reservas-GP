<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);
if (!$user || $empresa_id <= 0 || !in_array($role, ['superadmin', 'admin', 'gerente'], true)) {
    json_response(['error' => 'unauthorized'], 403);
}

$action = $_REQUEST['action'] ?? 'list';
if ($action !== 'list') {
    json_response(['error' => 'invalid_action'], 400);
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$per = max(1, min(100, (int) ($_GET['per'] ?? 20)));
$search = trim((string) ($_GET['search'] ?? ''));
$actor_id = (int) ($_GET['actor_id'] ?? 0);
$entidad = trim((string) ($_GET['entidad'] ?? ''));
$tipo = trim((string) ($_GET['tipo'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));

$where = ['ae.empresa_id = ?'];
$params = [$empresa_id];
if ($search !== '') {
    $where[] = '(ae.descripcion LIKE ? OR ae.entidad LIKE ? OR ae.tipo LIKE ? OR u.nombre LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
if ($actor_id > 0) {
    $where[] = 'ae.actor_usuario_id = ?';
    $params[] = $actor_id;
}
if ($entidad !== '') {
    $where[] = 'ae.entidad = ?';
    $params[] = $entidad;
}
if ($tipo !== '') {
    $where[] = 'ae.tipo = ?';
    $params[] = $tipo;
}
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = 'DATE(ae.created_at) >= ?';
    $params[] = $from;
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = 'DATE(ae.created_at) <= ?';
    $params[] = $to;
}
$whereSql = implode(' AND ', $where);

$count = $pdo->prepare("SELECT COUNT(*)
                        FROM auditoria_eventos ae
                        LEFT JOIN usuarios u ON u.id = ae.actor_usuario_id
                        WHERE $whereSql");
$count->execute($params);
$total = (int) $count->fetchColumn();
$total_pages = (int) ceil($total / $per);
$offset = ($page - 1) * $per;

$sql = "SELECT ae.*,
               u.nombre AS actor_nombre,
               u.email AS actor_email
        FROM auditoria_eventos ae
        LEFT JOIN usuarios u ON u.id = ae.actor_usuario_id
        WHERE $whereSql
        ORDER BY ae.id DESC
        LIMIT $per OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

json_response([
    'success' => true,
    'data' => $rows,
    'total' => $total,
    'total_pages' => $total_pages
]);

