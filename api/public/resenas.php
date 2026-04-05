<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = trim((string) ($_REQUEST['action'] ?? ''));
$empresa_ref = $_REQUEST['empresa'] ?? ($_REQUEST['id_e'] ?? null);
$empresa_ref = is_string($empresa_ref) ? trim($empresa_ref) : $empresa_ref;
if ($empresa_ref === null || $empresa_ref === '') {
    json_response(['success' => false, 'message' => 'Empresa no proporcionada.'], 400);
}

if (is_numeric($empresa_ref)) {
    $stmt = $pdo->prepare("SELECT id, slug, nombre FROM empresas WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([(int) $empresa_ref]);
} else {
    $stmt = $pdo->prepare("SELECT id, slug, nombre FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
    $stmt->execute([(string) $empresa_ref]);
}
$empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
$empresa_id = (int) ($empresa['id'] ?? 0);
if ($empresa_id <= 0) {
    json_response(['success' => false, 'message' => 'Empresa no encontrada.'], 404);
}

ensure_resena_invitaciones_table();
ensure_resena_context_table();

function find_review_invitation($empresa_id, $token)
{
    global $pdo;
    $hash = hash('sha256', (string) $token);
    $stmt = $pdo->prepare("SELECT ri.*, c.cliente_id, c.cliente_nombre, c.cliente_email, c.inicio,
                                  c.servicio_id, c.empleado_usuario_id, c.fin,
                                  s.nombre AS sucursal_nombre, s.id AS sucursal_id,
                                  srv.nombre AS servicio_nombre, srv.duracion_minutos AS servicio_duracion, srv.precio_base AS servicio_precio,
                                  u.nombre AS empleado_nombre
                           FROM resena_invitaciones ri
                           JOIN citas c ON c.id = ri.cita_id
                           LEFT JOIN sucursales s ON s.id = c.sucursal_id
                           LEFT JOIN servicios srv ON srv.id = c.servicio_id
                           LEFT JOIN usuarios u ON u.id = c.empleado_usuario_id
                           WHERE ri.empresa_id = ? AND ri.token_hash = ?
                           LIMIT 1");
    $stmt->execute([(int) $empresa_id, $hash]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

switch ($action) {
    case 'preview':
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            json_response(['success' => false, 'message' => 'Token requerido.'], 400);
        }
        $inv = find_review_invitation($empresa_id, $token);
        if (!$inv) {
            json_response(['success' => false, 'message' => 'Enlace no válido.'], 404);
        }
        if ((string) ($inv['estado'] ?? '') === 'usada') {
            json_response(['success' => false, 'message' => 'Esta reseña ya fue enviada.', 'used' => true], 200);
        }
        if (!empty($inv['expires_at']) && strtotime((string) $inv['expires_at']) < time()) {
            $upd = $pdo->prepare("UPDATE resena_invitaciones SET estado='expirada' WHERE id=?");
            $upd->execute([(int) $inv['id']]);
            json_response(['success' => false, 'message' => 'El enlace de reseña expiró.'], 410);
        }
        json_response([
            'success' => true,
            'data' => [
                'cliente_nombre' => (string) ($inv['cliente_nombre'] ?? ''),
                'servicio_nombre' => (string) ($inv['servicio_nombre'] ?? ''),
                'sucursal_nombre' => (string) ($inv['sucursal_nombre'] ?? ''),
                'empleado_nombre' => (string) ($inv['empleado_nombre'] ?? ''),
                'fecha' => !empty($inv['inicio']) ? date('d/m/Y', strtotime((string) $inv['inicio'])) : '',
                'hora' => !empty($inv['inicio']) ? date('H:i', strtotime((string) $inv['inicio'])) : '',
                'duracion' => (int) ($inv['servicio_duracion'] ?? 0),
                'precio' => format_currency_amount((float) ($inv['servicio_precio'] ?? 0), $empresa),
                'token_valid' => true,
            ]
        ]);
        break;

    case 'submit':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['success' => false, 'message' => 'Método inválido.'], 405);
        }
        $token = trim((string) ($_POST['token'] ?? ''));
        $rating = (int) ($_POST['rating'] ?? 0);
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        if ($token === '' || $rating < 1 || $rating > 5 || $comentario === '') {
            json_response(['success' => false, 'message' => 'Datos incompletos o inválidos.'], 400);
        }
        $comentario = mb_substr($comentario, 0, 500, 'UTF-8');
        $inv = find_review_invitation($empresa_id, $token);
        if (!$inv) {
            json_response(['success' => false, 'message' => 'Enlace no válido.'], 404);
        }
        if ((string) ($inv['estado'] ?? '') === 'usada') {
            json_response(['success' => false, 'message' => 'Esta reseña ya fue enviada.'], 200);
        }
        if (!empty($inv['expires_at']) && strtotime((string) $inv['expires_at']) < time()) {
            $upd = $pdo->prepare("UPDATE resena_invitaciones SET estado='expirada' WHERE id=?");
            $upd->execute([(int) $inv['id']]);
            json_response(['success' => false, 'message' => 'El enlace de reseña expiró.'], 410);
        }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("INSERT INTO resenas
                (empresa_id, sucursal_id, cliente_id, autor_nombre, rating, titulo, comentario, visible_en_home, activo, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,NOW())");
            $ins->execute([
                (int) $empresa_id,
                (int) ($inv['sucursal_id'] ?? 0),
                !empty($inv['cliente_id']) ? (int) $inv['cliente_id'] : null,
                (string) ($inv['cliente_nombre'] ?? 'Cliente'),
                $rating,
                'Reseña de cita',
                $comentario,
                0,
                0, // pendiente de aprobación
            ]);
            $resena_id = (int) $pdo->lastInsertId();
            $ctx = $pdo->prepare("INSERT INTO resena_contexto
                (resena_id, empresa_id, cita_id, servicio_id, empleado_usuario_id, created_at)
                VALUES (?,?,?,?,?,NOW())
                ON DUPLICATE KEY UPDATE
                    empresa_id=VALUES(empresa_id),
                    cita_id=VALUES(cita_id),
                    servicio_id=VALUES(servicio_id),
                    empleado_usuario_id=VALUES(empleado_usuario_id)");
            $ctx->execute([
                $resena_id,
                (int) $empresa_id,
                (int) ($inv['cita_id'] ?? 0),
                (int) ($inv['servicio_id'] ?? 0),
                (int) ($inv['empleado_usuario_id'] ?? 0),
            ]);
            $upd = $pdo->prepare("UPDATE resena_invitaciones SET estado='usada', used_at=NOW() WHERE id=?");
            $upd->execute([(int) $inv['id']]);
            $pdo->commit();
            json_response(['success' => true, 'message' => 'Gracias, tu reseña fue enviada para revisión.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            json_response(['success' => false, 'message' => 'No se pudo guardar la reseña.'], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Acción inválida.'], 400);
}
