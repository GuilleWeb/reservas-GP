<?php
function require_role($roles) {
    $u = current_user();
    if (!$u) {
        http_response_code(403);
        echo 'No autorizado.';
        exit;
    }
    $roles = is_array($roles) ? $roles : [$roles];
    $role = $u['rol'] ?? null;
    if (!$role || !in_array($role, $roles, true)) {
        http_response_code(403);
        echo 'No autorizado.';
        exit;
    }
    return $u;
}
