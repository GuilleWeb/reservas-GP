<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_login();
$id_e = request_id_e();

$roles_permitidos = ['superadmin', 'admin', 'gerente'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
  json_response(['error' => 'unauthorized'], 403);
}

$action = $_REQUEST['action'] ?? 'read';
$ctx = tenant_context();
$id_e = $ctx['id_e'];
$sucursal_slug = $ctx['sucursal_slug'];

if (!$id_e) {
  json_response(['success' => false, 'error' => 'missing_empresa'], 400);
}

switch ($action) {
  case 'read':
    // Métricas base. Si no viene sucursal, el front puede pedirla o resolverla con api/api-contexto.php.
    json_response([
      'success' => true,
      'data' => [
        'id_e' => $id_e,
        'sucursal_slug' => $sucursal_slug,
        'metrics' => []
      ]
    ]);
    break;

  default:
    json_response(['success' => false, 'error' => 'invalid_action'], 400);
}
