<?php
require_once __DIR__ . '/../helpers.php';

$id_e = request_id_e();

if ($id_e) {
    try {
        $stmt = $pdo->prepare('SELECT id, nombre, logo_path, colores_json FROM empresas WHERE id = ? LIMIT 1');
        $stmt->execute([$id_e]);
        $empresa_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($empresa_row) {
            $GLOBALS['empresa_info'] = $empresa_row;
            $_GET['_empresa'] = $id_e;
            $_GET['_empresa_id'] = (int) $empresa_row['id'];
        }
    } catch (Throwable $e) {
        // ignore
    }
}
$user = current_user();
if (!$user) {
   http_response_code(403);
  include 'topbar.php';
  include __DIR__ . '/../vistas/403.php';
  include 'footer.php';
  exit;
}