<?php
function json_response($data, $code = 200)
{
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code((int) $code);
    echo json_encode($data);
    exit;
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    $u = current_user();
    if (!$u) {
        json_response(['error' => 'no_auth'], 401);
    }
    return $u;
}

function request_id_e()
{
    $slug = $_GET['id_e'] ?? ($_GET['slugempresa'] ?? ($_GET['id_e'] ?? ($_GET['_empresa'] ?? null)));
    $slug = is_string($slug) ? trim($slug) : null;
    return $slug ?: null;
}

function request_sucursal_slug()
{
    $slug = $_GET['sucursal_slug'] ?? ($_GET['_sucursal'] ?? null);
    $slug = is_string($slug) ? trim($slug) : null;
    return $slug ?: null;
}

function tenant_context()
{
    return [
        'id_e' => request_id_e(),
        'sucursal_slug' => request_sucursal_slug(),
    ];
}
