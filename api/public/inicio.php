<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$empresa_id = (int) ($_REQUEST['empresa_id'] ?? 0);
$empresa_slug = $_REQUEST['slug'] ?? '';

if (!$empresa_id && $empresa_slug) {
    // Obtener ID desde slug si es necesario
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE slug = ?");
    $stmt->execute([$empresa_slug]);
    $empresa_id = (int) $stmt->fetchColumn();
}

if (!$empresa_id) {
    json_response(['success' => false, 'error' => 'Empresa no especificada'], 400);
}

$response = ['success' => true];

switch ($action) {
    case 'get_hero':
        // Obtener configuración del hero
        $stmt = $pdo->prepare("SELECT data_json FROM empresa_home_config WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $empresa_home_config = $stmt->fetch(PDO::FETCH_ASSOC);
        

        $config = json_decode($empresa_home_config['data_json'] ?? '{}', true);
        //print_r($empresa);
        $response['data'] = [
            'hero_visible' => $config['hero_visible'] ?? true,
            'hero_titulo' => $config['hero_titulo'] ?? 'Tu salud en buenas manos',
            //'hero_subtitulo' => $config['hero_subtitulo'] ?? 'Agenda tu cita hoy mismo con los mejores profesionales.',
            'hero_btn_texto' => $config['hero_btn_texto'] ?? 'Agendar Cita Ahora',
            'hero_btn_link' => ($config['hero_btn_link'] ?? 'citas.php') . '?empresa_id=' . $empresa_id,
            'hero_imagen' => $config['hero_imagen'] ?? 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=1000'
        ];
        break;

    case 'get_about':
        // Obtener misión/visión
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $config = json_decode($stmt->fetchColumn() ?: '{}', true);

        $response['data'] = [
            'about_visible' => $config['about_visible'] ?? true,
            'about_titulo' => $config['about_titulo'] ?? 'Sobre Nosotros',
            'mision' => $config['mision'] ?? '',
            'vision' => $config['vision'] ?? ''
        ];
        break;

    case 'get_servicios':
        // Obtener servicios destacados
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $config = json_decode($stmt->fetchColumn() ?: '{}', true);

        $servicios = [];
        if (!empty($config['featured_servicios']) && ($config['servicios_visible'] ?? true)) {
            $ids = array_map('intval', $config['featured_servicios']);
            $in = implode(',', $ids);
            if ($in) {
                $servicios = $pdo->query("SELECT id, nombre, descripcion, icono FROM servicios WHERE id IN ($in) AND empresa_id = $empresa_id LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $response['data'] = [
            'visible' => $config['servicios_visible'] ?? true,
            'items' => $servicios
        ];
        break;

    case 'get_blog':
        // Obtener posts del blog
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $config = json_decode($stmt->fetchColumn() ?: '{}', true);

        $posts = [];
        if ($config['blog_visible'] ?? true) {
            $stmtBlog = $pdo->prepare('SELECT id, titulo, contenido, imagen_path, publicado_at FROM blog_posts WHERE publicado = 1 AND empresa_id = ? ORDER BY publicado_at DESC LIMIT 3');
            $stmtBlog->execute([$empresa_id]);
            $posts = $stmtBlog->fetchAll(PDO::FETCH_ASSOC);
        }

        $response['data'] = [
            'visible' => $config['blog_visible'] ?? true,
            'items' => $posts
        ];
        break;

    case 'get_equipo':
        // Obtener equipo destacado
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $config = json_decode($stmt->fetchColumn() ?: '{}', true);

        $equipo = [];
        if (!empty($config['featured_equipo']) && ($config['equipo_visible'] ?? true)) {
            $ids = array_map('intval', $config['featured_equipo']);
            $in = implode(',', $ids);
            if ($in) {
                $equipo = $pdo->query("SELECT id, nombre, especialidad, imagen_path FROM equipo WHERE id IN ($in) AND empresa_id = $empresa_id LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $response['data'] = [
            'visible' => $config['equipo_visible'] ?? true,
            'items' => $equipo
        ];
        break;

    case 'get_sucursales_mini':
        // Obtener sucursales para el mini listado
        $stmt = $pdo->prepare("SELECT id, nombre, direccion FROM sucursales WHERE empresa_id = ? LIMIT 3");
        $stmt->execute([$empresa_id]);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'get_contacto':
        // Obtener información de contacto
        $stmt = $pdo->prepare("SELECT telefono_contacto, email_contacto, config_json FROM empresas WHERE id = ?");
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
        $config = json_decode($empresa['config_json'] ?? '{}', true);

        $response['data'] = [
            'contacto_visible' => $config['contacto_visible'] ?? true,
            'telefono' => $empresa['telefono_contacto'] ?? 'No disponible',
            'email' => $empresa['email_contacto'] ?? 'No disponible',
            'empresa_id' => $empresa_id
        ];
        break;

    default:
        $response = ['success' => false, 'error' => 'Acción no válida'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);