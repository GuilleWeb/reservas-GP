<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if (!$user || (($user['rol'] ?? null) !== 'superadmin')) {
    json_response(['error' => 'unauthorized'], 403);
}
ensure_suscripciones_table();
ensure_suscripciones_historial_table();

function ensure_suscripciones_extra_columns()
{
    global $pdo;
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo->exec("ALTER TABLE suscripciones ADD COLUMN plazo ENUM('mensual','anual') NOT NULL DEFAULT 'mensual'");
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("ALTER TABLE suscripciones ADD COLUMN adjunto_pago_path VARCHAR(255) NULL");
    } catch (Throwable $e) {
    }
    $done = true;
}

function normalize_single_subscription_per_company()
{
    global $pdo;
    ensure_suscripciones_extra_columns();
    try {
        $stmt = $pdo->query("SELECT empresa_id, GROUP_CONCAT(id ORDER BY id DESC) ids, COUNT(*) c
                             FROM suscripciones
                             GROUP BY empresa_id
                             HAVING c > 1");
        $dups = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($dups as $d) {
            $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($d['ids'] ?? '')))));
            if (count($ids) <= 1) {
                continue;
            }
            $keep = array_shift($ids);
            if (!empty($ids)) {
                $q = $pdo->query("SELECT * FROM suscripciones WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")");
                $rows = $q ? ($q->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
                foreach ($rows as $r) {
                    suscripcion_history_append($r, 'normalize', (int) ($GLOBALS['user']['id'] ?? 0));
                }
                $in = implode(',', array_map('intval', $ids));
                $pdo->exec("DELETE FROM suscripciones WHERE id IN ($in)");
            }
            $pdo->prepare("UPDATE suscripciones SET estado='activa' WHERE id=?")->execute([$keep]);
        }
    } catch (Throwable $e) {
    }
}

function calc_fecha_fin($fecha_inicio, $plazo)
{
    $base = strtotime((string) $fecha_inicio . ' 00:00:00');
    if ($base === false) {
        $base = strtotime(date('Y-m-d') . ' 00:00:00');
    }
    if ($plazo === 'anual') {
        return date('Y-m-d', strtotime('+1 year', $base));
    }
    return date('Y-m-d', strtotime('+1 month', $base));
}

function save_suscripcion_adjunto(array $file): ?string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $name = (string) ($file['name'] ?? 'comprobante');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allow = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($ext, $allow, true)) {
        return null;
    }
    $dirRel = 'uploads/comprobantes/suscripciones';
    $dirAbs = project_path($dirRel);
    if (!is_dir($dirAbs)) {
        @mkdir($dirAbs, 0775, true);
    }
    $fname = 'sus_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = rtrim($dirAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fname;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return app_url($dirRel . '/' . $fname);
}

normalize_single_subscription_per_company();
suscripciones_refresh_statuses();

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim((string) ($_GET['search'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(e.nombre LIKE :s OR e.slug LIKE :s)';
            $params[':s'] = "%$search%";
        }
        if ($estado !== '' && in_array($estado, ['activa', 'vencida', 'cancelada', 'pendiente'], true)) {
            $where[] = 's.estado = :estado';
            $params[':estado'] = $estado;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $count = $pdo->prepare("SELECT COUNT(*)
                                FROM suscripciones s
                                INNER JOIN empresas e ON e.id = s.empresa_id
                                $whereSql");
        foreach ($params as $k => $v) {
            $count->bindValue($k, $v, PDO::PARAM_STR);
        }
        $count->execute();
        $total = (int) $count->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT s.*, e.nombre AS empresa_nombre, e.slug AS empresa_slug, p.nombre AS plan_nombre, p.precio AS plan_precio
                FROM suscripciones s
                INNER JOIN empresas e ON e.id = s.empresa_id
                INNER JOIN planes p ON p.id = s.plan_id
                $whereSql
                ORDER BY s.id DESC
                LIMIT :o, :p";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':p', $per, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $fin = (string) ($r['fecha_fin'] ?? '');
            if ($fin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
                $r['dias_restantes'] = (int) floor((strtotime($fin . ' 23:59:59') - time()) / 86400);
            } else {
                $r['dias_restantes'] = null;
            }
        }
        unset($r);

        json_response(['success' => true, 'data' => $rows, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }
        $id = (int) ($_POST['id'] ?? 0);
        $empresa_id = (int) ($_POST['empresa_id'] ?? 0);
        $plan_id = (int) ($_POST['plan_id'] ?? 0);
        $estado = trim((string) ($_POST['estado'] ?? 'activa'));
        $fecha_inicio = trim((string) ($_POST['fecha_inicio'] ?? date('Y-m-d')));
        $plazo = trim((string) ($_POST['plazo'] ?? 'mensual'));
        $detalle_pago_texto = trim((string) ($_POST['detalle_pago_texto'] ?? ''));
        $adjunto_pago_path = trim((string) ($_POST['adjunto_pago_path'] ?? ''));
        if (isset($_FILES['adjunto_pago_file']) && (int) ($_FILES['adjunto_pago_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadedPath = save_suscripcion_adjunto($_FILES['adjunto_pago_file']);
            if ($uploadedPath === null) {
                json_response(['success' => false, 'message' => 'No se pudo guardar el adjunto. Formato permitido: jpg, png, webp o pdf.'], 200);
            }
            $adjunto_pago_path = $uploadedPath;
        }

        if ($empresa_id <= 0 || $plan_id <= 0) {
            json_response(['success' => false, 'message' => 'Empresa y plan son obligatorios.'], 200);
        }
        if (!in_array($estado, ['activa', 'vencida', 'cancelada', 'pendiente'], true)) {
            $estado = 'activa';
        }
        if ($estado === 'vencida' && $fecha_fin >= date('Y-m-d')) {
            $estado = 'activa';
        }
        if (!in_array($plazo, ['mensual', 'anual'], true)) {
            $plazo = 'mensual';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
            $fecha_inicio = date('Y-m-d');
        }
        $fecha_fin = calc_fecha_fin($fecha_inicio, $plazo);
        $ultimo_pago_fecha = $fecha_inicio;
        $ultimo_pago_monto = 0;
        try {
            $stmtPlan = $pdo->prepare("SELECT precio FROM planes WHERE id=? LIMIT 1");
            $stmtPlan->execute([$plan_id]);
            $ultimo_pago_monto = (float) ($stmtPlan->fetchColumn() ?: 0);
        } catch (Throwable $e) {
        }

        try {
            // Una sola suscripción por empresa.
            $stmtExists = $pdo->prepare("SELECT id FROM suscripciones WHERE empresa_id = ? ORDER BY id DESC LIMIT 1");
            $stmtExists->execute([$empresa_id]);
            $existingId = (int) ($stmtExists->fetchColumn() ?: 0);
            if ($id > 0) {
                $existingId = $id;
            }

            if ($existingId > 0) {
                $q = $pdo->prepare("SELECT * FROM suscripciones WHERE id = ? LIMIT 1");
                $q->execute([$existingId]);
                $prevRow = $q->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($prevRow) {
                    suscripcion_history_append($prevRow, 'replace', (int) ($user['id'] ?? 0));
                }
                $stmt = $pdo->prepare("UPDATE suscripciones
                                       SET empresa_id=?, plan_id=?, estado=?, fecha_inicio=?, fecha_fin=?, plazo=?, ultimo_pago_monto=?, ultimo_pago_fecha=?, detalle_pago_json=?, adjunto_pago_path=?
                                       WHERE id=?");
                $stmt->execute([
                    $empresa_id,
                    $plan_id,
                    $estado,
                    $fecha_inicio,
                    $fecha_fin,
                    $plazo,
                    $ultimo_pago_monto,
                    $ultimo_pago_fecha,
                    $detalle_pago_texto !== '' ? $detalle_pago_texto : null,
                    $adjunto_pago_path !== '' ? $adjunto_pago_path : null,
                    $existingId
                ]);
                $id = $existingId;
            } else {
                $stmt = $pdo->prepare("INSERT INTO suscripciones
                    (empresa_id, plan_id, estado, fecha_inicio, fecha_fin, plazo, ultimo_pago_monto, ultimo_pago_fecha, detalle_pago_json, adjunto_pago_path)
                    VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $empresa_id,
                    $plan_id,
                    $estado,
                    $fecha_inicio,
                    $fecha_fin,
                    $plazo,
                    $ultimo_pago_monto,
                    $ultimo_pago_fecha,
                    $detalle_pago_texto !== '' ? $detalle_pago_texto : null,
                    $adjunto_pago_path !== '' ? $adjunto_pago_path : null
                ]);
                $id = (int) $pdo->lastInsertId();
            }
            $q2 = $pdo->prepare("SELECT * FROM suscripciones WHERE id = ? LIMIT 1");
            $q2->execute([$id]);
            $curRow = $q2->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($curRow) {
                suscripcion_history_append($curRow, $existingId > 0 ? 'update' : 'create', (int) ($user['id'] ?? 0));
            }

            // mantener plan asignado en empresa
            $pdo->prepare("UPDATE empresas SET plan_id=? WHERE id=?")->execute([$plan_id, $empresa_id]);
            suscripciones_refresh_statuses($empresa_id);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }
        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'ID inválido'], 200);
        }
        try {
            $q = $pdo->prepare("SELECT * FROM suscripciones WHERE id = ? LIMIT 1");
            $q->execute([$id]);
            $prevRow = $q->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($prevRow) {
                suscripcion_history_append($prevRow, 'delete', (int) ($user['id'] ?? 0));
            }
            $stmt = $pdo->prepare("DELETE FROM suscripciones WHERE id=?");
            $stmt->execute([$id]);
            if ((int) $stmt->rowCount() <= 0) {
                json_response(['success' => false, 'message' => 'No se encontró la suscripción.'], 200);
            }
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }
        break;

    case 'history':
        $empresa_id = (int) ($_GET['empresa_id'] ?? 0);
        if ($empresa_id <= 0) {
            json_response(['success' => true, 'data' => []]);
        }
        $stmt = $pdo->prepare("SELECT h.*, p.nombre AS plan_nombre
                               FROM suscripciones_historial h
                               LEFT JOIN planes p ON p.id = h.plan_id
                               WHERE h.empresa_id = ?
                               ORDER BY h.id DESC
                               LIMIT 120");
        $stmt->execute([$empresa_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        json_response(['success' => true, 'data' => $rows]);
        break;

    case 'catalogs':
        $empresas = $pdo->query("SELECT id, nombre, slug FROM empresas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
        $planes = $pdo->query("SELECT id, nombre, precio FROM planes WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'empresas' => $empresas, 'planes' => $planes]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
