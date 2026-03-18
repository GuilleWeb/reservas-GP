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
    ? '../../' . $empresa_info['logo_path']
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