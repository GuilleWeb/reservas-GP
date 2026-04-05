<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$u = current_user();
if (!$u || (string) ($u['rol'] ?? '') !== 'cliente') {
    http_response_code(403);
    $module = '403';
    include __DIR__ . '/../../includes/topbar.php';
    include __DIR__ . '/../403.php';
    include __DIR__ . '/../../includes/footer.php';
    exit;
}
$eid = (int) (resolve_private_empresa_id($u) ?: ((int) ($u['empresa_id'] ?? 0)));
header('Location: ' . view_url('vistas/cliente/citas.php', $eid));
exit;

