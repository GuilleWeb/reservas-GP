<?php
require_once __DIR__ . '/../app/bootstrap.php';

$action = $_REQUEST['action'] ?? '';
$ctx_slug = request_id_e();

if (!$ctx_slug) {
    json_response(['success' => false, 'error' => 'missing_empresa'], 400);
}

$stmt = $pdo->prepare('SELECT id, slug, activo FROM empresas WHERE slug = ? LIMIT 1');
$stmt->execute([$ctx_slug]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$empresa)
    json_response(['success' => false, 'error' => 'empresa_not_found'], 404);
if (empty($empresa['activo']))
    json_response(['success' => false, 'error' => 'empresa_inactiva'], 403);
$empresa_id = (int) $empresa['id'];

function parse_date_ymd($s)
{
    if (!is_string($s) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $s))
        return null;
    return $s;
}

function dt($s)
{
    try {
        return new DateTime($s);
    } catch (Throwable $e) {
        return null;
    }
}

function overlaps($aStart, $aEnd, $bStart, $bEnd)
{
    return ($aStart < $bEnd) && ($bStart < $aEnd);
}

function fetch_employee_intervals_for_day(PDO $pdo, int $empleado_id, string $dateYmd)
{
    $d = new DateTime($dateYmd);
    $weekday = (int) $d->format('w'); // 0 domingo .. 6 sábado

    $stmt = $pdo->prepare('SELECT hora_inicio, hora_fin FROM empleado_horarios WHERE empleado_usuario_id = ? AND weekday = ? ORDER BY hora_inicio ASC');
    $stmt->execute([$empleado_id, $weekday]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $intervals = [];
    foreach ($rows as $r) {
        $hi = (string) $r['hora_inicio'];
        $hf = (string) $r['hora_fin'];
        if ($hi === '' || $hf === '')
            continue;
        $start = new DateTime($dateYmd . ' ' . $hi);
        $end = new DateTime($dateYmd . ' ' . $hf);
        if ($end <= $start)
            continue;
        $intervals[] = ['start' => $start, 'end' => $end];
    }
    return $intervals;
}

function fetch_employee_blocks_for_range(PDO $pdo, int $empleado_id, DateTime $rangeStart, DateTime $rangeEnd)
{
    $stmt = $pdo->prepare('SELECT inicio, fin FROM empleado_bloqueos WHERE empleado_usuario_id = ? AND inicio < ? AND fin > ?');
    $stmt->execute([$empleado_id, $rangeEnd->format('Y-m-d H:i:s'), $rangeStart->format('Y-m-d H:i:s')]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $s = dt($r['inicio']);
        $e = dt($r['fin']);
        if (!$s || !$e)
            continue;
        $out[] = ['start' => $s, 'end' => $e];
    }
    return $out;
}

function fetch_employee_citas_for_range(PDO $pdo, int $empresa_id, int $empleado_id, DateTime $rangeStart, DateTime $rangeEnd)
{
    $stmt = $pdo->prepare("SELECT inicio, fin FROM citas WHERE empresa_id = ? AND empleado_usuario_id = ? AND estado IN ('pendiente','confirmada') AND inicio < ? AND fin > ?");
    $stmt->execute([$empresa_id, $empleado_id, $rangeEnd->format('Y-m-d H:i:s'), $rangeStart->format('Y-m-d H:i:s')]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $s = dt($r['inicio']);
        $e = dt($r['fin']);
        if (!$s || !$e)
            continue;
        $out[] = ['start' => $s, 'end' => $e];
    }
    return $out;
}

function build_slots(PDO $pdo, int $empresa_id, int $empleado_id, string $dateYmd, int $durMin, int $stepMin = 15)
{
    $intervals = fetch_employee_intervals_for_day($pdo, $empleado_id, $dateYmd);
    if (!$intervals)
        return [];

    // Rango del día para cargar bloqueos/citas una sola vez
    $dayStart = new DateTime($dateYmd . ' 00:00:00');
    $dayEnd = new DateTime($dateYmd . ' 23:59:59');

    $blocks = fetch_employee_blocks_for_range($pdo, $empleado_id, $dayStart, $dayEnd);
    $citas = fetch_employee_citas_for_range($pdo, $empresa_id, $empleado_id, $dayStart, $dayEnd);

    $busy = array_merge($blocks, $citas);

    $slots = [];
    $dur = new DateInterval('PT' . max(1, $durMin) . 'M');

    foreach ($intervals as $iv) {
        $cursor = clone $iv['start'];
        // redondear hacia arriba al múltiplo del step
        $m = (int) $cursor->format('i');
        $mod = $m % $stepMin;
        if ($mod !== 0) {
            $cursor->modify('+' . ($stepMin - $mod) . ' minutes');
            $cursor->setTime((int) $cursor->format('H'), (int) $cursor->format('i'), 0);
        }

        while (true) {
            $end = (clone $cursor);
            $end->add($dur);
            if ($end > $iv['end'])
                break;

            $ok = true;
            foreach ($busy as $b) {
                if (overlaps($cursor, $end, $b['start'], $b['end'])) {
                    $ok = false;
                    break;
                }
            }

            // No permitir slots en el pasado
            $now = new DateTime();
            if ($cursor < $now)
                $ok = false;

            if ($ok) {
                $slots[] = $cursor->format('H:i');
            }

            $cursor->modify('+' . $stepMin . ' minutes');
        }
    }

    return array_values(array_unique($slots));
}

switch ($action) {
    case 'services':
        $stmt = $pdo->prepare('SELECT id, nombre, descripcion, duracion_minutos, precio_base FROM servicios WHERE empresa_id = ? AND activo=1 ORDER BY nombre ASC');
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'branches':
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        if ($servicio_id <= 0)
            json_response(['success' => false, 'error' => 'missing_servicio'], 400);

        $stmt = $pdo->prepare('SELECT s.id, s.nombre, s.direccion, s.telefono FROM sucursales s INNER JOIN servicio_sucursales ss ON ss.sucursal_id=s.id AND ss.activo=1 WHERE s.empresa_id=? AND s.activo=1 AND ss.servicio_id=? ORDER BY s.nombre ASC');
        $stmt->execute([$empresa_id, $servicio_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'employees':
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $sucursal_id = (int) ($_GET['sucursal_id'] ?? 0);
        if ($servicio_id <= 0)
            json_response(['success' => false, 'error' => 'missing_servicio'], 400);
        if ($sucursal_id <= 0)
            json_response(['success' => false, 'error' => 'missing_sucursal'], 400);

        // empleados de la sucursal que tienen el servicio
        $stmt = $pdo->prepare(
            "SELECT u.id, u.nombre, u.foto_path, es.precio_override
             FROM usuarios u
             INNER JOIN empleado_servicios es ON es.empleado_usuario_id=u.id AND es.activo=1
             WHERE u.empresa_id=? AND u.sucursal_id=? AND u.rol IN ('empleado','gerente') AND u.activo=1 AND es.servicio_id=?
             ORDER BY u.nombre ASC"
        );
        $stmt->execute([$empresa_id, $sucursal_id, $servicio_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'calendar':
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $empleado_id = (int) ($_GET['empleado_id'] ?? 0);
        $month = $_GET['month'] ?? '';
        if ($servicio_id <= 0 || $empleado_id <= 0)
            json_response(['success' => false, 'error' => 'missing_fields'], 400);
        if (!is_string($month) || !preg_match('/^\d{4}-\d{2}$/', $month))
            json_response(['success' => false, 'error' => 'invalid_month'], 400);

        $stmt = $pdo->prepare('SELECT duracion_minutos FROM servicios WHERE empresa_id=? AND id=? AND activo=1 LIMIT 1');
        $stmt->execute([$empresa_id, $servicio_id]);
        $dur = (int) $stmt->fetchColumn();
        if ($dur <= 0)
            json_response(['success' => false, 'error' => 'servicio_not_found'], 404);

        $start = new DateTime($month . '-01');
        $end = (clone $start);
        $end->modify('last day of this month');

        $days = [];
        $cursor = clone $start;
        while ($cursor <= $end) {
            $ymd = $cursor->format('Y-m-d');
            $slots = build_slots($pdo, $empresa_id, $empleado_id, $ymd, $dur);
            $days[] = [
                'date' => $ymd,
                'available' => count($slots) > 0,
            ];
            $cursor->modify('+1 day');
        }

        json_response(['success' => true, 'data' => $days]);
        break;

    case 'slots':
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $empleado_id = (int) ($_GET['empleado_id'] ?? 0);
        $date = parse_date_ymd($_GET['date'] ?? '');
        if ($servicio_id <= 0 || $empleado_id <= 0 || !$date)
            json_response(['success' => false, 'error' => 'missing_fields'], 400);

        $stmt = $pdo->prepare('SELECT duracion_minutos FROM servicios WHERE empresa_id=? AND id=? AND activo=1 LIMIT 1');
        $stmt->execute([$empresa_id, $servicio_id]);
        $dur = (int) $stmt->fetchColumn();
        if ($dur <= 0)
            json_response(['success' => false, 'error' => 'servicio_not_found'], 404);

        $slots = build_slots($pdo, $empresa_id, $empleado_id, $date, $dur);
        json_response(['success' => true, 'data' => $slots]);
        break;

    case 'book':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['success' => false, 'error' => 'invalid_method'], 405);

        $servicio_id = (int) ($_POST['servicio_id'] ?? 0);
        $sucursal_id = (int) ($_POST['sucursal_id'] ?? 0);
        $empleado_id = (int) ($_POST['empleado_id'] ?? 0);
        $date = parse_date_ymd($_POST['date'] ?? '');
        $time = $_POST['time'] ?? '';
        $cliente_nombre = trim((string) ($_POST['cliente_nombre'] ?? ''));
        $cliente_email = trim((string) ($_POST['cliente_email'] ?? ''));
        $cliente_telefono = trim((string) ($_POST['cliente_telefono'] ?? ''));

        if ($servicio_id <= 0 || $sucursal_id <= 0 || $empleado_id <= 0 || !$date || !is_string($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            json_response(['success' => false, 'error' => 'missing_fields'], 400);
        }
        if ($cliente_nombre === '')
            json_response(['success' => false, 'error' => 'missing_cliente_nombre'], 400);

        // Validar servicio y obtener duración
        $stmt = $pdo->prepare('SELECT duracion_minutos FROM servicios WHERE empresa_id=? AND id=? AND activo=1 LIMIT 1');
        $stmt->execute([$empresa_id, $servicio_id]);
        $dur = (int) $stmt->fetchColumn();
        if ($dur <= 0)
            json_response(['success' => false, 'error' => 'servicio_not_found'], 404);

        $inicio = dt($date . ' ' . $time . ':00');
        if (!$inicio)
            json_response(['success' => false, 'error' => 'invalid_datetime'], 400);
        $fin = (clone $inicio);
        $fin->modify('+' . $dur . ' minutes');

        try {
            $pdo->beginTransaction();

            // Revalidar slot disponible
            $slots = build_slots($pdo, $empresa_id, $empleado_id, $date, $dur);
            if (!in_array($time, $slots, true)) {
                $pdo->rollBack();
                json_response(['success' => false, 'error' => 'slot_unavailable'], 409);
            }

            // Crear cliente
            $stmt = $pdo->prepare('INSERT INTO clientes (nombre, email, telefono, activo) VALUES (?,?,?,1)');
            $stmt->execute([$cliente_nombre, ($cliente_email !== '' ? $cliente_email : null), ($cliente_telefono !== '' ? $cliente_telefono : null)]);
            $cliente_id = (int) $pdo->lastInsertId();

            // Relacionar con empresa
            $stmt = $pdo->prepare('INSERT INTO cliente_empresas (cliente_id, empresa_id) VALUES (?,?)');
            $stmt->execute([$cliente_id, $empresa_id]);

            // Crear cita
            $stmt = $pdo->prepare("INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_id, cliente_nombre, cliente_email, cliente_telefono, inicio, fin, estado) VALUES (?,?,?,?,?,?,?,?,?,?, 'pendiente')");
            $stmt->execute([
                $empresa_id,
                $sucursal_id,
                $servicio_id,
                $empleado_id,
                $cliente_id,
                $cliente_nombre,
                ($cliente_email !== '' ? $cliente_email : null),
                ($cliente_telefono !== '' ? $cliente_telefono : null),
                $inicio->format('Y-m-d H:i:s'),
                $fin->format('Y-m-d H:i:s'),
            ]);
            $cita_id = (int) $pdo->lastInsertId();

            $pdo->commit();
            json_response(['success' => true, 'cita_id' => $cita_id]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            json_response(['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()], 200);
        }
        break;

    default:
        json_response(['success' => false, 'error' => 'invalid_action'], 400);
}
