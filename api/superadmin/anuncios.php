<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if (!$user || (($user['rol'] ?? null) !== 'superadmin')) {
    json_response(['error' => 'unauthorized'], 403);
}

ensure_anuncios_table();

function save_anuncio_file(array $file): ?string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $name = (string) ($file['name'] ?? 'anuncio');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allow = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allow, true)) {
        return null;
    }
    $dirRel = 'uploads/anuncios';
    $dirAbs = project_path($dirRel);
    if (!is_dir($dirAbs)) {
        @mkdir($dirAbs, 0775, true);
    }
    $fname = 'ad_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = rtrim($dirAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fname;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return $dirRel . '/' . $fname;
}

switch ($action) {
    case 'list':
        $rows = anuncios_get_active_map();
        json_response(['success' => true, 'data' => $rows]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }

        $slots = ['sidebar', 'footer', 'panel'];
        $saved = [];
        foreach ($slots as $slot) {
            $activo = isset($_POST[$slot . '_activo']) ? 1 : 0;
            $url = trim((string) ($_POST[$slot . '_url'] ?? ''));

            $stmt = $pdo->prepare("SELECT * FROM anuncios WHERE slot = ? LIMIT 1");
            $stmt->execute([$slot]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $imagen_path = (string) ($current['imagen_path'] ?? '');

            $fileKey = $slot . '_file';
            if (isset($_FILES[$fileKey]) && (int) ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = save_anuncio_file($_FILES[$fileKey]);
                if ($uploaded === null) {
                    json_response(['success' => false, 'message' => 'No se pudo subir la imagen. Formatos permitidos: jpg, png, webp, gif.'], 200);
                }
                $imagen_path = $uploaded;
            }

            if ($current) {
                $upd = $pdo->prepare("UPDATE anuncios SET imagen_path = ?, link_url = ?, activo = ?, orden = ? WHERE slot = ?");
                $upd->execute([$imagen_path, $url, $activo, (int) ($current['orden'] ?? 0), $slot]);
            } else {
                $ins = $pdo->prepare("INSERT INTO anuncios (slot, imagen_path, link_url, activo, orden) VALUES (?,?,?,?,?)");
                $ins->execute([$slot, $imagen_path, $url, $activo, 0]);
            }

            $saved[$slot] = [
                'slot' => $slot,
                'imagen_path' => $imagen_path,
                'link_url' => $url,
                'activo' => $activo,
            ];
        }

        json_response(['success' => true, 'data' => $saved]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}

