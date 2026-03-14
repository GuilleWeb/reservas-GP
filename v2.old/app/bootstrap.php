<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$id_e = request_id_e();
if (!$id_e) {
    $sess_slug = $_SESSION['user']['id_e'] ?? null;
    $id_e = is_string($sess_slug) ? trim($sess_slug) : null;
    if ($id_e === '')
        $id_e = null;
}
if ($id_e) {
    try {
        $stmt = $pdo->prepare('SELECT id, slug, nombre, logo_path, colores_json, activo FROM empresas WHERE slug = ? LIMIT 1');
        $stmt->execute([$id_e]);
        $empresa_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($empresa_row) {
            $GLOBALS['empresa_info'] = $empresa_row;
            $GLOBALS['empresa_id'] = (int) $empresa_row['id'];
        }
    } catch (Throwable $e) {
        // ignore
    }
}
