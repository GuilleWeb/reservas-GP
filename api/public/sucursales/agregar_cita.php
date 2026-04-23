<?php
require_once __DIR__ . '/../../../helpers.php';

function json_out(array $payload, int $status = 200): void
{
    header('Content-Type: application/json; charset=utf-8');
    json_response($payload, $status);
}

function make_public_booking_code(int $empresa_id): string
{
    $prefix = 'RES' . str_pad((string) max(1, $empresa_id), 3, '0', STR_PAD_LEFT);
    $seed = strtoupper(bin2hex(random_bytes(4)));
    return $prefix . '-' . substr($seed, 0, 4) . '-' . substr($seed, 4, 4);
}

function b64url_encode_str(string $raw): string
{
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function b64url_decode_str(string $raw): string
{
    $pad = strlen($raw) % 4;
    if ($pad > 0) {
        $raw .= str_repeat('=', 4 - $pad);
    }
    return (string) base64_decode(strtr($raw, '-_', '+/'));
}

function cliente_lookup_secret(int $empresa_id): string
{
    $seed = (string) ($_SESSION['csrf_token'] ?? session_id() ?? 'lookup-default');
    return hash('sha256', $seed . '|' . $empresa_id . '|cliente-lookup');
}

function create_cliente_lookup_token(int $empresa_id, int $cliente_id, string $email): string
{
    $payload = [
        'eid' => $empresa_id,
        'cid' => $cliente_id,
        'em' => strtolower(trim($email)),
        'iat' => time(),
        'exp' => time() + (15 * 60),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $body = b64url_encode_str((string) $json);
    $sig = hash_hmac('sha256', $body, cliente_lookup_secret($empresa_id));
    return $body . '.' . $sig;
}

function mask_name_public(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $words = array_values(array_filter($words, static fn($w) => $w !== ''));
    if (!$words) {
        return '***';
    }
    $masked = array_map(static function ($w) {
        $w = (string) $w;
        $len = mb_strlen($w, 'UTF-8');
        if ($len <= 1) {
            return '*';
        }
        return mb_substr($w, 0, 1, 'UTF-8') . str_repeat('*', max(2, $len - 1));
    }, $words);
    return implode(' ', $masked);
}

function mask_phone_public(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (!$digits) {
        return '***';
    }
    $keep = substr($digits, -2);
    return str_repeat('*', max(4, strlen($digits) - 2)) . $keep;
}

function verify_cliente_lookup_token(int $empresa_id, string $token): ?array
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return null;
    }
    [$body, $sig] = $parts;
    $calc = hash_hmac('sha256', $body, cliente_lookup_secret($empresa_id));
    if (!hash_equals($calc, (string) $sig)) {
        return null;
    }
    $payload = json_decode(b64url_decode_str((string) $body), true);
    if (!is_array($payload)) {
        return null;
    }
    $exp = (int) ($payload['exp'] ?? 0);
    if ($exp < time()) {
        return null;
    }
    if ((int) ($payload['eid'] ?? 0) !== $empresa_id) {
        return null;
    }
    if ((int) ($payload['cid'] ?? 0) <= 0) {
        return null;
    }
    return $payload;
}

function hhmm_from_client_or_server(string $clientToday, string $clientTime): array
{
    $today = date('Y-m-d');
    $time = date('H:i');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $clientToday)) {
        $today = $clientToday;
    }
    if (preg_match('/^\d{2}:\d{2}$/', $clientTime)) {
        $time = $clientTime;
    }
    return [$today, $time];
}

$action = $_REQUEST['action'] ?? '';
$empresa_ref = $_REQUEST['empresa'] ?? ($_REQUEST['id_e'] ?? null);
$empresa_ref = is_string($empresa_ref) ? trim($empresa_ref) : $empresa_ref;

if ($empresa_ref === null || $empresa_ref === '') {
    json_response(['success' => false, 'message' => 'Empresa no proporcionada'], 400);
}

// Resolver empresa por slug (público) o por ID (compatibilidad).
if (is_numeric($empresa_ref)) {
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([(int) $empresa_ref]);
} else {
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
    $stmt->execute([(string) $empresa_ref]);
}
$empresa_id = (int) ($stmt->fetchColumn() ?: 0);

if (!$empresa_id) {
    json_out(['success' => false, 'message' => 'Empresa no encontrada'], 404);
}

switch ($action) {
    case 'get_sucursales':
        $stmt = $pdo->prepare("SELECT id, nombre, direccion, telefono, email, horarios_json, foto_path FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        json_out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_servicios':
        $stmt = $pdo->prepare("SELECT id, nombre, descripcion, duracion_minutos as duracion, precio_base as precio FROM servicios WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        json_out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_empleados':
        $sede_id = (int) ($_GET['sede_id'] ?? 0);
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $where = ['u.empresa_id = ?', 'u.activo = 1', "u.rol IN ('admin','gerente','empleado')"];
        $params = [$empresa_id];
        if ($sede_id > 0) {
            $where[] = '(u.sucursal_id IS NULL OR u.sucursal_id = 0 OR u.sucursal_id = ?)';
            $params[] = $sede_id;
        }
        if ($servicio_id > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM empleado_servicios es WHERE es.empleado_usuario_id = u.id AND es.servicio_id = ? AND es.activo = 1)';
            $params[] = $servicio_id;
        }
        $sql = "SELECT u.id, u.nombre, u.rol AS puesto, u.foto_path AS foto
                FROM usuarios u
                WHERE " . implode(' AND ', $where) . "
                ORDER BY u.nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'find_cliente_secure':
        $email = strtolower(trim((string) ($_GET['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_out(['success' => true, 'data' => ['exists' => false]]);
        }
        $stmt = $pdo->prepare("SELECT c.id, c.nombre, c.email, c.telefono
                               FROM clientes c
                               WHERE c.empresa_id = ? AND c.activo = 1 AND LOWER(c.email) = LOWER(?)
                               ORDER BY c.id DESC
                               LIMIT 1");
        $stmt->execute([$empresa_id, $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            json_out(['success' => true, 'data' => ['exists' => false]]);
        }
        $token = create_cliente_lookup_token($empresa_id, (int) $row['id'], (string) $row['email']);
        json_out([
            'success' => true,
            'data' => [
                'exists' => true,
                'lookup_token' => $token,
                'nombre' => (string) ($row['nombre'] ?? ''),
                'telefono' => (string) ($row['telefono'] ?? ''),
            ]
        ]);
        break;

    case 'get_horarios':
        $sede_id = (int) ($_GET['sede_id'] ?? 0);
        $fecha = $_GET['fecha'] ?? ''; // YYYY-MM-DD
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $empleado_id = (int) ($_GET['empleado_id'] ?? 0);
        $client_today = trim((string) ($_GET['client_today'] ?? ''));
        $client_time = trim((string) ($_GET['client_time'] ?? ''));

        if (!$sede_id || !$fecha || !$servicio_id) {
            json_out(['success' => false, 'message' => 'Faltan parámetros'], 400);
        }

        // Obtener horario de la sucursal
        $stmt = $pdo->prepare("SELECT horarios_json FROM sucursales WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$sede_id, $empresa_id]);
        $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

        $horarios_full = json_decode($sucursal['horarios_json'] ?? '{}', true);
        if (!is_array($horarios_full)) {
            $horarios_full = [];
        }
        $day_of_week = (int) date('N', strtotime($fecha)); // 1-7
        $newKeys = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo'];
        $newKey = $newKeys[$day_of_week] ?? 'lunes';

        $rango = null;
        if (isset($horarios_full[$newKey]) && is_array($horarios_full[$newKey])) {
            $d = $horarios_full[$newKey];
            $activo = (int) ($d['activo'] ?? 0);
            if ($activo === 1 && !empty($d['inicio']) && !empty($d['fin'])) {
                $rango = ['inicio' => $d['inicio'], 'fin' => $d['fin']];
            }
        }
        if (!$rango) {
            $legacyKey = 'lun-vie';
            if ($day_of_week === 6)
                $legacyKey = 'sab';
            if ($day_of_week === 7)
                $legacyKey = 'dom';
            $legacy = $horarios_full[$legacyKey] ?? null;
            if (is_array($legacy) && !empty($legacy['inicio']) && !empty($legacy['fin'])) {
                $rango = ['inicio' => $legacy['inicio'], 'fin' => $legacy['fin']];
            }
        }

        if (!$rango || empty($rango['inicio']) || empty($rango['fin'])) {
            // Si no hay horario específico, intentamos el texto o devolvemos vacío
            json_out([
                'success' => true,
                'data' => [],
                'meta' => [
                    'dia_activo' => false,
                    'horario' => null,
                ],
            ]);
        }

        $inicio_laboral = $rango['inicio'];
        $fin_laboral = $rango['fin'];

        // Citas ya agendadas
        $sqlO = "SELECT TIME_FORMAT(inicio, '%H:%i') as hora FROM citas WHERE sucursal_id = ? AND DATE(inicio) = ? AND estado != 'cancelada'";
        $parO = [$sede_id, $fecha];
        if ($empleado_id > 0) {
            $sqlO .= " AND empleado_usuario_id = ?";
            $parO[] = $empleado_id;
        }
        $stmt = $pdo->prepare($sqlO);
        $stmt->execute($parO);
        $ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generar slots
        $stmt_srv = $pdo->prepare("SELECT duracion_minutos FROM servicios WHERE id = ?");
        $stmt_srv->execute([$servicio_id]);
        $duracion = (int) $stmt_srv->fetchColumn() ?: 30;

        $slots = [];
        $current = strtotime("$fecha $inicio_laboral");
        $end = strtotime("$fecha $fin_laboral");

        while (($current + ($duracion * 60)) <= $end) {
            $h = date('H:i', $current);
            $is_ocupado = in_array($h, $ocupados);
            [$localToday, $localTime] = hhmm_from_client_or_server($client_today, $client_time);
            $is_past_or_now = ($fecha === $localToday && $h <= $localTime);
            $slots[] = ['hora' => $h, 'disponible' => !($is_ocupado || $is_past_or_now)];
            $current += ($duracion * 60);
        }

        json_out([
            'success' => true,
            'data' => $slots,
            'meta' => [
                'dia_activo' => true,
                'horario' => [
                    'inicio' => $inicio_laboral,
                    'fin' => $fin_laboral,
                ],
            ],
        ]);
        break;

    case 'get_reserva':
        ensure_citas_public_ref_column();
        $codigo = trim((string) ($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            json_out(['success' => false, 'message' => 'Código de reserva no proporcionado.'], 400);
        }
        $stmt = $pdo->prepare("SELECT c.id, c.codigo_publico, c.cliente_nombre, c.cliente_email, c.cliente_telefono, c.notas, c.inicio, c.fin, c.estado,
                                      s.id AS sucursal_id, s.nombre AS sucursal_nombre, s.direccion AS sucursal_direccion,
                                      srv.id AS servicio_id, srv.nombre AS servicio_nombre, srv.descripcion AS servicio_descripcion, srv.duracion_minutos AS servicio_duracion, srv.precio_base AS servicio_precio,
                                      u.id AS empleado_id, u.nombre AS empleado_nombre
                               FROM citas c
                               LEFT JOIN sucursales s ON s.id = c.sucursal_id
                               LEFT JOIN servicios srv ON srv.id = c.servicio_id
                               LEFT JOIN usuarios u ON u.id = c.empleado_usuario_id
                               WHERE c.empresa_id = ? AND c.codigo_publico = ?
                               LIMIT 1");
        $stmt->execute([$empresa_id, $codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            json_out(['success' => false, 'message' => 'No se encontró la reserva.'], 404);
        }
        json_out(['success' => true, 'data' => $row]);
        break;

    case 'calendar_file':
        ensure_citas_public_ref_column();
        $codigo = trim((string) ($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Código de reserva no válido.';
            exit;
        }
        $stmt = $pdo->prepare("SELECT c.codigo_publico, c.cliente_nombre, c.inicio, c.fin, s.nombre AS sucursal_nombre, s.direccion AS sucursal_direccion, srv.nombre AS servicio_nombre
                               FROM citas c
                               LEFT JOIN sucursales s ON s.id = c.sucursal_id
                               LEFT JOIN servicios srv ON srv.id = c.servicio_id
                               WHERE c.empresa_id = ? AND c.codigo_publico = ?
                               LIMIT 1");
        $stmt->execute([$empresa_id, $codigo]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$reserva) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Reserva no encontrada.';
            exit;
        }
        $tz = new DateTimeZone('America/Mexico_City');
        $dtStart = new DateTime((string) $reserva['inicio'], $tz);
        $dtEnd = !empty($reserva['fin']) ? new DateTime((string) $reserva['fin'], $tz) : (clone $dtStart)->modify('+30 minutes');
        $uid = strtolower((string) ($reserva['codigo_publico'] ?? uniqid('res-'))) . '@reservas-gp';
        $title = 'Cita: ' . (string) ($reserva['servicio_nombre'] ?? 'Servicio');
        $location = trim((string) (($reserva['sucursal_nombre'] ?? '') . ' - ' . ($reserva['sucursal_direccion'] ?? '')));
        $description = 'Cliente: ' . (string) ($reserva['cliente_nombre'] ?? '')
            . '\nServicio: ' . (string) ($reserva['servicio_nombre'] ?? '')
            . '\nCódigo: ' . (string) ($reserva['codigo_publico'] ?? '');
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Reservas GP//Citas//ES\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . preg_replace('/[^A-Za-z0-9\-@_.]/', '', $uid) . "\r\n";
        $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:" . $dtStart->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\n";
        $ics .= "DTEND:" . $dtEnd->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\n";
        $ics .= "SUMMARY:" . str_replace(["\r", "\n"], ' ', $title) . "\r\n";
        $ics .= "LOCATION:" . str_replace(["\r", "\n"], ' ', $location) . "\r\n";
        $ics .= "DESCRIPTION:" . str_replace(["\r", "\n"], '\\n', $description) . "\r\n";
        $ics .= "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $fname = 'reserva-' . preg_replace('/[^A-Za-z0-9\-]/', '-', (string) ($reserva['codigo_publico'] ?? 'cita')) . '.ics';
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        echo $ics;
        exit;

    case 'save_cita':
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $sede_id = (int) ($_POST['sede_id'] ?? 0);
        $servicio_id = (int) ($_POST['servicio_id'] ?? 0);
        $empleado_id = (int) ($_POST['empleado_id'] ?? 0); // ID de usuarios
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $notas = trim($_POST['notas'] ?? '');
        $cliente_lookup_token = trim((string) ($_POST['cliente_lookup_token'] ?? ''));
        $client_today = trim((string) ($_POST['client_today'] ?? ''));
        $client_time = trim((string) ($_POST['client_time'] ?? ''));

        if (!$sede_id || !$servicio_id || !$fecha || !$hora || !$nombre) {
            json_out(['success' => false, 'message' => 'Información incompleta'], 400);
        }
        if ($cliente_lookup_token === '' && (strpos($nombre, '*') !== false || strpos($telefono, '*') !== false)) {
            json_out(['success' => false, 'message' => 'Debes validar tus datos de cliente nuevamente.'], 400);
        }

        $inicio = "$fecha $hora:00";
        [$localToday, $localTime] = hhmm_from_client_or_server($client_today, $client_time);
        if (($fecha < $localToday) || ($fecha === $localToday && $hora <= $localTime)) {
            json_out(['success' => false, 'message' => 'No puedes agendar una cita en una hora pasada o en la hora actual.'], 400);
        }

        // Validar que la fecha/hora esté dentro de horario activo de la sucursal.
        $stmtSuc = $pdo->prepare("SELECT horarios_json FROM sucursales WHERE id = ? AND empresa_id = ? AND activo = 1 LIMIT 1");
        $stmtSuc->execute([$sede_id, $empresa_id]);
        $suc = $stmtSuc->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$suc) {
            json_out(['success' => false, 'message' => 'Sucursal no válida.'], 400);
        }
        $horarios_full = json_decode((string) ($suc['horarios_json'] ?? '{}'), true);
        if (!is_array($horarios_full)) {
            $horarios_full = [];
        }
        $day_of_week = (int) date('N', strtotime($fecha)); // 1..7
        $newKeys = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo'];
        $newKey = $newKeys[$day_of_week] ?? 'lunes';

        $rango = null;
        if (isset($horarios_full[$newKey]) && is_array($horarios_full[$newKey])) {
            $d = $horarios_full[$newKey];
            $activo = (int) ($d['activo'] ?? 0);
            if ($activo === 1 && !empty($d['inicio']) && !empty($d['fin'])) {
                $rango = ['inicio' => (string) $d['inicio'], 'fin' => (string) $d['fin']];
            }
        }
        if (!$rango) {
            $legacyKey = 'lun-vie';
            if ($day_of_week === 6) {
                $legacyKey = 'sab';
            }
            if ($day_of_week === 7) {
                $legacyKey = 'dom';
            }
            $legacy = $horarios_full[$legacyKey] ?? null;
            if (is_array($legacy) && !empty($legacy['inicio']) && !empty($legacy['fin'])) {
                $rango = ['inicio' => (string) $legacy['inicio'], 'fin' => (string) $legacy['fin']];
            }
        }
        if (!$rango) {
            json_out(['success' => false, 'message' => 'La sucursal no atiende en el día seleccionado.'], 400);
        }

        $stmt_srv = $pdo->prepare("SELECT duracion_minutos FROM servicios WHERE id = ?");
        $stmt_srv->execute([$servicio_id]);
        $duracion = (int) $stmt_srv->fetchColumn() ?: 30;
        $fin = date('Y-m-d H:i:s', strtotime($inicio) + ($duracion * 60));

        $inicio_min = date('H:i', strtotime($inicio));
        $fin_min = date('H:i', strtotime($fin));
        if ($inicio_min < $rango['inicio'] || $fin_min > $rango['fin']) {
            json_out(['success' => false, 'message' => 'La hora seleccionada queda fuera del horario de la sucursal.'], 400);
        }

        try {
            ensure_citas_public_ref_column();
            $empleado_usuario_id = null;
            if ($empleado_id > 0) {
                $stmt_u = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND empresa_id = ? AND activo = 1 AND rol IN ('admin','gerente','empleado') LIMIT 1");
                $stmt_u->execute([$empleado_id, $empresa_id]);
                $empleado_usuario_id = $stmt_u->fetchColumn() ?: null;
            }

            $cliente_id = null;
            if ($cliente_lookup_token !== '') {
                $tok = verify_cliente_lookup_token($empresa_id, $cliente_lookup_token);
                if (!$tok) {
                    json_out(['success' => false, 'message' => 'Validación de cliente expirada. Intenta nuevamente.'], 400);
                }
                $stmtCliToken = $pdo->prepare("SELECT c.id, c.nombre, c.email, c.telefono
                                               FROM clientes c
                                               WHERE c.empresa_id = ? AND c.id = ? AND c.activo = 1
                                               LIMIT 1");
                $stmtCliToken->execute([$empresa_id, (int) ($tok['cid'] ?? 0)]);
                $cli = $stmtCliToken->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$cli) {
                    json_out(['success' => false, 'message' => 'No se pudo validar el cliente recuperado.'], 400);
                }
                $cliente_id = (int) $cli['id'];
                // Mantener correo del cliente (no permitir cambio sin validación adicional).
                $email = (string) ($cli['email'] ?? $email);
                // Permitir actualizar nombre/teléfono si el usuario los modificó.
                if ($nombre === '') {
                    $nombre = (string) ($cli['nombre'] ?? '');
                }
                if ($telefono === '') {
                    $telefono = (string) ($cli['telefono'] ?? '');
                }
            } elseif ($email !== '' || $telefono !== '') {
                $where = [];
                $params = [$empresa_id];
                if ($email !== '') {
                    $where[] = 'LOWER(c.email) = LOWER(?)';
                    $params[] = $email;
                }
                if ($telefono !== '') {
                    $where[] = "REPLACE(REPLACE(REPLACE(c.telefono, ' ', ''), '-', ''), '(', '') = REPLACE(REPLACE(REPLACE(?, ' ', ''), '-', ''), '(', '')";
                    $params[] = $telefono;
                }
                $sqlCli = "SELECT c.id
                           FROM clientes c
                           WHERE c.empresa_id = ? AND c.activo = 1 AND (" . implode(' OR ', $where) . ")
                           LIMIT 1";
                $stmtCli = $pdo->prepare($sqlCli);
                $stmtCli->execute($params);
                $cliente_id = $stmtCli->fetchColumn() ?: null;
            }

            if ((int) $cliente_id <= 0) {
                $insCli = $pdo->prepare("INSERT INTO clientes (empresa_id, sucursal_id, nombre, email, telefono, activo, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())");
                $insCli->execute([
                    $empresa_id,
                    $sede_id > 0 ? $sede_id : null,
                    (string) $nombre,
                    $email !== '' ? (string) $email : null,
                    $telefono !== '' ? (string) $telefono : null,
                    1,
                ]);
                $cliente_id = (int) $pdo->lastInsertId();
            } elseif ((int) $cliente_id > 0) {
                $updCli = $pdo->prepare("UPDATE clientes
                                         SET nombre = ?, email = ?, telefono = ?, sucursal_id = COALESCE(?, sucursal_id), updated_at = NOW()
                                         WHERE id = ? AND empresa_id = ?");
                $updCli->execute([
                    (string) $nombre,
                    $email !== '' ? (string) $email : null,
                    $telefono !== '' ? (string) $telefono : null,
                    $sede_id > 0 ? $sede_id : null,
                    (int) $cliente_id,
                    $empresa_id,
                ]);
            }

            $codigo_publico = make_public_booking_code($empresa_id);
            $stmt = $pdo->prepare("INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_id, cliente_nombre, cliente_telefono, cliente_email, inicio, fin, estado, notas, codigo_publico) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$empresa_id, $sede_id, $servicio_id, $empleado_usuario_id, $cliente_id, $nombre, $telefono, $email, $inicio, $fin, 'pendiente', $notas, $codigo_publico]);
            $newId = (int) $pdo->lastInsertId();
            audit_event(
                'create',
                'citas',
                $newId,
                'Cita pública agendada',
                $empresa_id,
                [
                    'canal' => 'publico',
                    'codigo_publico' => $codigo_publico,
                    'cliente_nombre' => $nombre,
                    'inicio' => $inicio,
                ],
                true
            );

            // Notificar roles internos de la empresa de forma centralizada.
            create_notification([
                'empresa_id' => $empresa_id,
                'rol_destino' => 'admin',
                'tipo' => 'cita_nueva',
                'titulo' => 'Nueva cita agendada',
                'descripcion' => $nombre . ' · ' . date('d/m/Y H:i', strtotime($inicio)),
                'url' => view_url('vistas/admin/admin-citas.php', $empresa_id),
                'referencia_tipo' => 'cita',
                'referencia_id' => $newId,
            ]);
            create_notification([
                'empresa_id' => $empresa_id,
                'rol_destino' => 'gerente',
                'tipo' => 'cita_nueva',
                'titulo' => 'Nueva cita agendada',
                'descripcion' => $nombre . ' · ' . date('d/m/Y H:i', strtotime($inicio)),
                'url' => view_url('vistas/sucursal/admin-citas.php', $empresa_id),
                'referencia_tipo' => 'cita',
                'referencia_id' => $newId,
            ]);
            if (!empty($empleado_usuario_id)) {
                create_notification([
                    'empresa_id' => $empresa_id,
                    'usuario_id' => (int) $empleado_usuario_id,
                    'rol_destino' => 'empleado',
                    'tipo' => 'cita_nueva',
                    'titulo' => 'Nueva cita asignada',
                    'descripcion' => $nombre . ' · ' . date('d/m/Y H:i', strtotime($inicio)),
                    'url' => view_url('vistas/empleado/citas.php', $empresa_id),
                    'referencia_tipo' => 'cita',
                    'referencia_id' => $newId,
                ]);
            }

            // Confirmación por correo al cliente (si existe email válido).
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmtEmp = $pdo->prepare("SELECT id, slug, nombre, logo_path, colores_json, config_json FROM empresas WHERE id = ? LIMIT 1");
                $stmtEmp->execute([$empresa_id]);
                $empresa_info = $stmtEmp->fetch(PDO::FETCH_ASSOC) ?: ['id' => $empresa_id, 'nombre' => 'Tu empresa'];

                $sucNombre = '';
                $sucDireccion = '';
                $srvNombre = '';
                $srvPrecio = 0.0;
                $empNombre = '';
                $stmtS = $pdo->prepare("SELECT nombre, direccion FROM sucursales WHERE id = ? LIMIT 1");
                $stmtS->execute([$sede_id]);
                $rowS = $stmtS->fetch(PDO::FETCH_ASSOC) ?: [];
                $sucNombre = (string) ($rowS['nombre'] ?? '');
                $sucDireccion = (string) ($rowS['direccion'] ?? '');
                $stmtV = $pdo->prepare("SELECT nombre, precio_base FROM servicios WHERE id = ? LIMIT 1");
                $stmtV->execute([$servicio_id]);
                $rowV = $stmtV->fetch(PDO::FETCH_ASSOC) ?: [];
                $srvNombre = (string) ($rowV['nombre'] ?? '');
                $srvPrecio = (float) ($rowV['precio_base'] ?? 0);
                if (!empty($empleado_usuario_id)) {
                    $stmtU = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ? LIMIT 1");
                    $stmtU->execute([$empleado_usuario_id]);
                    $empNombre = (string) ($stmtU->fetchColumn() ?: '');
                }

                $empresa_ref_mail = (string) (($empresa_info['slug'] ?? '') !== '' ? $empresa_info['slug'] : $empresa_id);
                $calendar_ics_url = app_url_absolute('api/public/sucursales/agregar_cita.php?action=calendar_file&id_e=' . rawurlencode($empresa_ref_mail) . '&codigo=' . rawurlencode((string) $codigo_publico));
                $curMeta = get_currency_meta($empresa_info);
                $calendar_google_url = build_google_calendar_url(
                    'Cita: ' . $srvNombre,
                    (string) $inicio,
                    (string) $fin,
                    'Código: ' . $codigo_publico . "\nCliente: " . $nombre . "\nServicio: " . $srvNombre,
                    trim($sucNombre . ' ' . $sucDireccion)
                );

                send_booking_confirmation_email($empresa_info, [
                    'to_email' => (string) $email,
                    'cliente_nombre' => (string) $nombre,
                    'sucursal' => $sucNombre,
                    'direccion' => $sucDireccion,
                    'servicio' => $srvNombre,
                    'empleado' => $empNombre,
                    'precio' => (string) (($curMeta['symbol'] ?? '$') . number_format($srvPrecio, 2)),
                    'inicio' => date('d/m/Y H:i', strtotime($inicio)),
                    'fecha' => date('Y-m-d', strtotime($inicio)),
                    'hora' => date('h:i A', strtotime($inicio)),
                    'calendar_ics_url' => $calendar_ics_url,
                    'calendar_google_url' => $calendar_google_url,
                ]);
            }

            json_out([
                'success' => true,
                'id' => $newId,
                'codigo' => $codigo_publico,
                'calendar_ics_url' => app_url_absolute('api/public/sucursales/agregar_cita.php?action=calendar_file&id_e=' . rawurlencode((string) $empresa_ref) . '&codigo=' . rawurlencode($codigo_publico)),
            ]);
        } catch (Throwable $e) {
            json_out(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    default:
        json_out(['error' => 'invalid_action'], 400);
}
