<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/context.php';

// Módulo por defecto para evitar notices si la vista no lo define antes del include de topbar
if (!isset($module)) {
    $module = '';
}

// Cargar empresa desde URL (nunca desde sesión)
$empresa_info = get_current_empresa();

// Definir variables que usan topbar, footer y vistas — con fallbacks seguros
$logo_path = ($empresa_info && !empty($empresa_info['logo_path']))
    ? app_url(ltrim((string) $empresa_info['logo_path'], '/'))
    : app_url('assets/logo.avif');
$empresa_nombre = $empresa_info ? htmlspecialchars($empresa_info['nombre']) : 'Sistema de reservas GP';
$empresa_slogan = ($empresa_info && !empty($empresa_info['slogan']))
    ? htmlspecialchars($empresa_info['slogan'])
    : 'Agendá fácil, viví mejor';
$empresa_descripcion = ($empresa_info && !empty($empresa_info['descripcion']))
    ? htmlspecialchars($empresa_info['descripcion'])
    : 'Sistema de gestión de citas multi-empresa y multisucursal.';

$config = [];
if ($empresa_info && !empty($empresa_info['config_json'])) {
    $config = json_decode($empresa_info['config_json'], true) ?: [];
}
$email_contacto = $config['email_contacto'] ?? 'soporte@reservasgp.com';
$telefono_contacto = $config['telefono_contacto'] ?? '+502 5103-6244';
$horaios = $config['horario_general'] ?? 'Siempre abierto';
$direccion = $config['direccion_general'] ?? 'Negocio en línea';
$gsc_meta_tag = trim((string) ($config['gsc_meta_tag'] ?? ''));
$moneda_code = strtoupper((string) ($config['moneda'] ?? 'GTQ'));
$currency_meta = get_currency_meta($empresa_info);
$currency_symbol = $currency_meta['symbol'];

$redes_default = [
    'facebook' => '',
    'instagram' => '',
    'whatsapp' => '',
    'tiktok' => '',
    'x' => '',
    'otro' => '',
];
$redes = ($empresa_info && !empty($empresa_info['redes_json']))
    ? (json_decode($empresa_info['redes_json'], true) ?: $redes_default)
    : $redes_default;

$colores = ($empresa_info && !empty($empresa_info['colores_json']))
    ? (json_decode($empresa_info['colores_json'], true) ?: [])
    : [];
$color_p = $colores['principal'] ?? '#46C9BB';

// Auth: si no hay sesión activa, mostrar 403
$user = current_user();
if (!is_public_view() && !$user) {
    http_response_code(403);
    include __DIR__ . '/errors/403.php';
    exit;
}

// Seguridad multi-tenant global para vistas privadas:
// cualquier rol distinto de superadmin queda atado a su empresa de sesión.
if (!is_public_view() && $user && (($user['rol'] ?? null) !== 'superadmin')) {
    $resolved_empresa_id = (int) resolve_private_empresa_id($user);
    $requested_empresa_id = request_id_e();
    if ($resolved_empresa_id <= 0 || ($requested_empresa_id !== null && (int) $requested_empresa_id !== $resolved_empresa_id)) {
        http_response_code(403);
        include __DIR__ . '/errors/403.php';
        exit;
    }
}

// Ejecutar tareas tipo cron cuando el superadmin inicia sesión (1 vez por día).
if ($user && ($user['rol'] ?? null) === 'superadmin' && !request_id_e()) {
    cron_jobs_auto_run_if_needed((int) ($user['id'] ?? 0));
}

// Enforzar permisos por plan para módulos puntuales (retrocompatible: si no hay modulos_json, permite todo).
if ($empresa_info) {
    $module_map = [
        'blog' => 'blog',
        'home_page' => 'home_page',
        'resenas' => 'resenas',
        'mensajes' => 'mensajes',
        'admin-citas' => 'citas',
        'citas' => 'citas',
    ];
    $m = trim((string) ($module ?? ''));
    if (isset($module_map[$m])) {
        $mk = $module_map[$m];
        // superadmin siempre puede inspeccionar
        if (!(($user['rol'] ?? null) === 'superadmin')) {
            enforce_module_access_or_403((int) $empresa_info['id'], $mk);
        }
    }
}
