<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);

if (!$user || $empresa_id <= 0) {
    json_response(['error' => 'unauthorized'], 403);
}

// Carpeta base para archivos de la empresa (ofuscada con base64)
$carpeta_hash = base64_encode($empresa_id);
$base_media_path = __DIR__ . '/../../assets/' . $carpeta_hash . '/media/';

switch ($action) {
    // ================== EMPRESA ==================
    case 'get_company':
        if (!in_array($role, ['superadmin', 'admin'])) {
            json_response(['error' => 'forbidden'], 403);
        }

        $stmt = $pdo->prepare("SELECT nombre, slug, slogan, descripcion, logo_path, colores_json, redes_json, config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            json_response(['success' => false, 'message' => 'Empresa no encontrada'], 404);
        }

        $colores = json_decode($row['colores_json'] ?? '{}', true) ?: [];
        $redes = json_decode($row['redes_json'] ?? '{}', true) ?: [];
        $config = json_decode($row['config_json'] ?? '{}', true) ?: [];

        $data = [
            'nombre'          => $row['nombre'],
            'slug'            => $row['slug'],
            'slogan'          => $row['slogan'] ?? '',
            'descripcion'     => $row['descripcion'] ?? '',
            'logo_path'       => $row['logo_path'] ?? '',
            'primary_color'   => $colores['principal'] ?? '',
            'redes'           => $redes,
            'email_contacto'  => $config['email_contacto'] ?? '',
            'telefono_contacto' => $config['telefono_contacto'] ?? '',
            'moneda'          => $config['moneda'] ?? 'GTQ',
            'direccion_general' => $config['direccion_general'] ?? '',
            'horario_general' => $config['horario_general'] ?? '',
            'gsc_meta_tag'    => $config['gsc_meta_tag'] ?? '',
            'encuestas_activas' => $config['encuestas_activas'] ?? '1',
        ];
        json_response(['success' => true, 'data' => $data]);
        break;

    case 'save_company':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }
        if (!in_array($role, ['superadmin', 'admin'])) {
            json_response(['error' => 'forbidden'], 403);
        }

        // Obtener logo actual para posible eliminación
        $stmt = $pdo->prepare("SELECT logo_path FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $old_logo = $stmt->fetchColumn();

        // Procesar logo
        $logo_path = null;
        if (!empty($_FILES['logo_file']['name'])) {
            $archivo = $_FILES['logo_file'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'avif', 'webp'];
            if (!in_array($extension, $permitidas)) {
                json_response(['success' => false, 'message' => 'Tipo de archivo no permitido. Use JPG, PNG, AVIF, WEBP.'], 200);
            }
            if ($archivo['size'] > 2 * 1024 * 1024) {
                json_response(['success' => false, 'message' => 'El archivo excede 2MB'], 200);
            }

            // Crear carpeta si no existe
            if (!is_dir($base_media_path)) {
                mkdir($base_media_path, 0755, true);
            }

            $nombre_archivo = time() . '_' . uniqid() . '.' . $extension;
            $ruta_completa = $base_media_path . $nombre_archivo;

            if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                $logo_path = 'assets/' . $carpeta_hash . '/media/' . $nombre_archivo;

                // Eliminar logo anterior si es local
                if ($old_logo && file_exists(__DIR__ . '/../../' . $old_logo)) {
                    unlink(__DIR__ . '/../../' . $old_logo);
                }
            } else {
                json_response(['success' => false, 'message' => 'Error al guardar el archivo'], 200);
            }
        } elseif (!empty($_POST['logo_url'])) {
            $logo_path = trim($_POST['logo_url']);
        } else {
            $logo_path = $old_logo; // conservar actual
        }

        // Resto de campos
        $slogan = trim($_POST['slogan'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $primary_color = trim($_POST['primary_color'] ?? '');

        if ($primary_color && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $primary_color)) {
            $primary_color = '#0d9488';
        }
        if (in_array(strtoupper($primary_color), ['#FFFFFF', '#FFF', '#000000', '#000'])) {
            $primary_color = '#0d9488';
        }
        $colores_json = json_encode(['principal' => $primary_color]);

        // Redes sociales
        $redes = [];
        if (isset($_POST['redes']) && is_array($_POST['redes'])) {
            foreach ($_POST['redes'] as $platform => $url) {
                $url = trim($url);
                if ($url !== '') {
                    $redes[$platform] = $url;
                }
            }
        }
        $redes_json = json_encode($redes, JSON_UNESCAPED_UNICODE);

        // Config extra (email, teléfono, moneda, dirección, horario)
        $stmt = $pdo->prepare("SELECT config_json FROM empresas WHERE id = ?");
        $stmt->execute([$empresa_id]);
        $current_config = $stmt->fetchColumn();
        $config = $current_config ? json_decode($current_config, true) : [];

        $config['email_contacto'] = trim($_POST['email_contacto'] ?? '');
        $config['telefono_contacto'] = trim($_POST['telefono_contacto'] ?? '');
        $config['moneda'] = trim($_POST['moneda'] ?? 'GTQ');
        $config['direccion_general'] = trim($_POST['direccion_general'] ?? '');
        $config['horario_general'] = trim($_POST['horario_general'] ?? '');
        $config['gsc_meta_tag'] = trim($_POST['gsc_meta_tag'] ?? '');
        $config['encuestas_activas'] = isset($_POST['encuestas_activas']) ? '1' : '0';

        $config_json = json_encode($config, JSON_UNESCAPED_UNICODE);

        try {
            $stmt = $pdo->prepare("UPDATE empresas SET slogan = ?, descripcion = ?, logo_path = ?, colores_json = ?, redes_json = ?, config_json = ? WHERE id = ?");
            $stmt->execute([$slogan, $descripcion, $logo_path, $colores_json, $redes_json, $config_json, $empresa_id]);
            audit_event('update', 'empresas', $empresa_id, 'Configuración de empresa actualizada');
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error en BD: ' . $e->getMessage()], 200);
        }

        json_response(['success' => true]);
        break;

    // ================== PERFIL ==================
    case 'get_profile':
        $stmt = $pdo->prepare("SELECT id, nombre, email, telefono, foto_path, rol FROM usuarios WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$user['id'], $empresa_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            json_response(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        json_response(['success' => true, 'data' => $row]);
        break;

    case 'save_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (empty($nombre) || empty($email)) {
            json_response(['success' => false, 'message' => 'Nombre y email son obligatorios'], 200);
        }

        // Validar email único dentro de la misma empresa
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND empresa_id = ? AND id != ?");
        $check->execute([$email, $empresa_id, $user['id']]);
        if ($check->fetch()) {
            json_response(['success' => false, 'message' => 'El email ya está en uso por otro usuario'], 200);
        }

        // Cambio de contraseña
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $update_pass = false;
        if (!empty($current) || !empty($new) || !empty($confirm)) {
            if (empty($current) || empty($new) || empty($confirm)) {
                json_response(['success' => false, 'message' => 'Para cambiar la contraseña debe completar todos los campos'], 200);
            }
            if ($new !== $confirm) {
                json_response(['success' => false, 'message' => 'La nueva contraseña y su confirmación no coinciden'], 200);
            }
            $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();
            if (!password_verify($current, $hash)) {
                json_response(['success' => false, 'message' => 'La contraseña actual es incorrecta'], 200);
            }
            $update_pass = true;
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
        }

        // Procesar foto de perfil
        $foto_path = null;
        if (!empty($_FILES['foto_file']['name'])) {
            $archivo = $_FILES['foto_file'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'avif', 'webp'];
            if (!in_array($extension, $permitidas)) {
                json_response(['success' => false, 'message' => 'Tipo de archivo no permitido. Use JPG, PNG, AVIF, WEBP.'], 200);
            }
            if ($archivo['size'] > 2 * 1024 * 1024) {
                json_response(['success' => false, 'message' => 'El archivo excede 2MB'], 200);
            }

            // Obtener foto actual para eliminarla después
            $stmt = $pdo->prepare("SELECT foto_path FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $old_foto = $stmt->fetchColumn();

            // Crear carpeta si no existe
            if (!is_dir($base_media_path)) {
                mkdir($base_media_path, 0755, true);
            }

            $nombre_archivo = 'user_' . $user['id'] . '_' . time() . '.' . $extension;
            $ruta_completa = $base_media_path . $nombre_archivo;

            if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                $foto_path = 'assets/' . $carpeta_hash . '/media/' . $nombre_archivo;

                // Eliminar foto anterior si existe y es local
                if ($old_foto && file_exists(__DIR__ . '/../../' . $old_foto)) {
                    unlink(__DIR__ . '/../../' . $old_foto);
                }
            } else {
                json_response(['success' => false, 'message' => 'Error al guardar la foto'], 200);
            }
        } else {
            // No se subió nueva foto, conservar la actual
            $stmt = $pdo->prepare("SELECT foto_path FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $foto_path = $stmt->fetchColumn();
        }

        try {
            if ($update_pass) {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, foto_path = ?, password_hash = ? WHERE id = ? AND empresa_id = ?");
                $stmt->execute([$nombre, $email, $telefono, $foto_path, $new_hash, $user['id'], $empresa_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, foto_path = ? WHERE id = ? AND empresa_id = ?");
                $stmt->execute([$nombre, $email, $telefono, $foto_path, $user['id'], $empresa_id]);
            }
            audit_event('update', 'usuarios', $user['id'], 'Perfil actualizado');
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => 'Error en BD: ' . $e->getMessage()], 200);
        }

        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
