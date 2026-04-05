<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $p, int $c = 200): void
{
    json_response($p, $c);
}

function client_now_pair(): array
{
    $today = date('Y-m-d');
    $time = date('H:i');
    $ct = trim((string) ($_REQUEST['client_today'] ?? ''));
    $ch = trim((string) ($_REQUEST['client_time'] ?? ''));
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ct)) {
        $today = $ct;
    }
    if (preg_match('/^\d{2}:\d{2}$/', $ch)) {
        $time = $ch;
    }
    return [$today, $time];
}

$action = (string) ($_REQUEST['action'] ?? '');
$user = current_user();
if (!$user || (string) ($user['rol'] ?? '') !== 'cliente') {
    out(['error' => 'unauthorized'], 403);
}
$empresa_id = (int) (resolve_private_empresa_id($user) ?: ((int) ($user['empresa_id'] ?? 0)));
if ($empresa_id <= 0) {
    out(['success' => false, 'message' => 'Empresa inválida.'], 400);
}
$cliente_user_id = (int) ($user['id'] ?? 0);
$cliente_email = trim((string) ($user['email'] ?? ''));
$cliente_nombre = trim((string) ($user['nombre'] ?? ''));
$cliente_tel = trim((string) ($user['telefono'] ?? ''));

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, min(50, (int) ($_GET['per'] ?? 10)));
        $offset = ($page - 1) * $per;
        $where = "c.empresa_id = ? AND (c.creado_por_usuario_id = ?";
        $params = [$empresa_id, $cliente_user_id];
        if ($cliente_email !== '') {
            $where .= " OR LOWER(c.cliente_email) = LOWER(?)";
            $params[] = $cliente_email;
        }
        $where .= ")";

        $sql = "SELECT c.*, s.nombre AS sucursal_nombre, srv.nombre AS servicio_nombre
                FROM citas c
                LEFT JOIN sucursales s ON s.id = c.sucursal_id
                LEFT JOIN servicios srv ON srv.id = c.servicio_id
                WHERE {$where}
                ORDER BY c.inicio DESC
                LIMIT " . (int) $per . " OFFSET " . (int) $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlC = "SELECT COUNT(*)
                 FROM citas c
                 WHERE {$where}";
        $stc = $pdo->prepare($sqlC);
        $stc->execute($params);
        $total = (int) ($stc->fetchColumn() ?: 0);
        out(['success' => true, 'data' => $rows, 'total' => $total, 'total_pages' => (int) ceil($total / $per)]);
        break;

    case 'get_sedes':
        $stmt = $pdo->prepare("SELECT id, nombre, direccion, telefono, email, horarios_json, foto_path
                               FROM sucursales
                               WHERE empresa_id = ? AND activo = 1
                               ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'get_servicios':
        $stmt = $pdo->prepare("SELECT id, nombre, descripcion, duracion_minutos, precio_base
                               FROM servicios
                               WHERE empresa_id = ? AND activo = 1
                               ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'get_empleados':
        $sede_id = (int) ($_GET['sede_id'] ?? 0);
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        if ($sede_id <= 0 || $servicio_id <= 0) {
            out(['success' => true, 'data' => []]);
        }
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.rol AS puesto, u.foto_path AS foto
                               FROM usuarios u
                               WHERE u.empresa_id = ?
                                 AND u.activo = 1
                                 AND u.rol IN ('admin','gerente','empleado')
                                 AND (u.sucursal_id IS NULL OR u.sucursal_id = 0 OR u.sucursal_id = ?)
                                 AND EXISTS (
                                     SELECT 1 FROM empleado_servicios es
                                     WHERE es.empleado_usuario_id = u.id
                                       AND es.servicio_id = ?
                                       AND es.activo = 1
                                 )
                               ORDER BY u.nombre ASC");
        $stmt->execute([$empresa_id, $sede_id, $servicio_id]);
        out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'get_horarios':
        $sede_id = (int) ($_GET['sede_id'] ?? 0);
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $empleado_id = (int) ($_GET['empleado_id'] ?? 0);
        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        if ($sede_id <= 0 || $servicio_id <= 0 || $empleado_id <= 0 || $fecha === '') {
            out(['success' => false, 'message' => 'Parámetros incompletos.'], 400);
        }

        $stmt = $pdo->prepare("SELECT horarios_json FROM sucursales WHERE id = ? AND empresa_id = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$sede_id, $empresa_id]);
        $suc = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$suc) {
            out(['success' => false, 'message' => 'Sucursal no válida.'], 400);
        }
        $h = json_decode((string) ($suc['horarios_json'] ?? '{}'), true);
        if (!is_array($h)) {
            $h = [];
        }
        $day = (int) date('N', strtotime($fecha));
        $k = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo'][$day] ?? 'lunes';
        $r = null;
        if (isset($h[$k]) && is_array($h[$k])) {
            $d = $h[$k];
            if ((int) ($d['activo'] ?? 0) === 1 && !empty($d['inicio']) && !empty($d['fin'])) {
                $r = ['inicio' => (string) $d['inicio'], 'fin' => (string) $d['fin']];
            }
        }
        if (!$r) {
            $legacy = $h[$day === 6 ? 'sab' : ($day === 7 ? 'dom' : 'lun-vie')] ?? null;
            if (is_array($legacy) && !empty($legacy['inicio']) && !empty($legacy['fin'])) {
                $r = ['inicio' => (string) $legacy['inicio'], 'fin' => (string) $legacy['fin']];
            }
        }
        if (!$r) {
            out(['success' => true, 'data' => [], 'meta' => ['dia_activo' => false, 'horario' => null]]);
        }

        $srv = $pdo->prepare("SELECT duracion_minutos FROM servicios WHERE id = ? AND empresa_id = ? LIMIT 1");
        $srv->execute([$servicio_id, $empresa_id]);
        $dur = (int) ($srv->fetchColumn() ?: 30);
        $occ = $pdo->prepare("SELECT TIME_FORMAT(inicio, '%H:%i') AS hora
                              FROM citas
                              WHERE empresa_id = ?
                                AND sucursal_id = ?
                                AND empleado_usuario_id = ?
                                AND DATE(inicio) = ?
                                AND estado != 'cancelada'");
        $occ->execute([$empresa_id, $sede_id, $empleado_id, $fecha]);
        $taken = $occ->fetchAll(PDO::FETCH_COLUMN) ?: [];

        [$today, $timeNow] = client_now_pair();
        $slots = [];
        $cur = strtotime($fecha . ' ' . $r['inicio']);
        $end = strtotime($fecha . ' ' . $r['fin']);
        while (($cur + ($dur * 60)) <= $end) {
            $hm = date('H:i', $cur);
            $isPast = ($fecha === $today && $hm <= $timeNow);
            $isTaken = in_array($hm, $taken, true);
            $slots[] = ['hora' => $hm, 'disponible' => !($isPast || $isTaken)];
            $cur += ($dur * 60);
        }
        out([
            'success' => true,
            'data' => $slots,
            'meta' => ['dia_activo' => true, 'horario' => ['inicio' => $r['inicio'], 'fin' => $r['fin']]],
        ]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            out(['error' => 'invalid_method'], 405);
        }
        $sede_id = (int) ($_POST['sede_id'] ?? 0);
        $servicio_id = (int) ($_POST['servicio_id'] ?? 0);
        $empleado_id = (int) ($_POST['empleado_id'] ?? 0);
        $fecha = trim((string) ($_POST['fecha'] ?? ''));
        $hora = trim((string) ($_POST['hora'] ?? ''));
        $notas = trim((string) ($_POST['notas'] ?? ''));
        if ($sede_id <= 0 || $servicio_id <= 0 || $empleado_id <= 0 || $fecha === '' || $hora === '') {
            out(['success' => false, 'message' => 'Datos incompletos.'], 400);
        }
        [$today, $timeNow] = client_now_pair();
        if (($fecha < $today) || ($fecha === $today && $hora <= $timeNow)) {
            out(['success' => false, 'message' => 'No puedes agendar en un horario pasado o actual.'], 400);
        }

        // Validar empleado
        $u = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND empresa_id = ? AND activo = 1 AND rol IN ('admin','gerente','empleado') LIMIT 1");
        $u->execute([$empleado_id, $empresa_id]);
        if (!(int) $u->fetchColumn()) {
            out(['success' => false, 'message' => 'Empleado no válido.'], 400);
        }
        // Validar servicio
        $srv = $pdo->prepare("SELECT duracion_minutos FROM servicios WHERE id = ? AND empresa_id = ? AND activo = 1 LIMIT 1");
        $srv->execute([$servicio_id, $empresa_id]);
        $dur = (int) ($srv->fetchColumn() ?: 0);
        if ($dur <= 0) {
            out(['success' => false, 'message' => 'Servicio no válido.'], 400);
        }
        $inicio = $fecha . ' ' . $hora . ':00';
        $fin = date('Y-m-d H:i:s', strtotime($inicio) + ($dur * 60));

        // Confirmar que slot siga disponible
        $v = $pdo->prepare("SELECT COUNT(*)
                            FROM citas
                            WHERE empresa_id = ?
                              AND sucursal_id = ?
                              AND empleado_usuario_id = ?
                              AND DATE(inicio) = ?
                              AND TIME_FORMAT(inicio, '%H:%i') = ?
                              AND estado != 'cancelada'");
        $v->execute([$empresa_id, $sede_id, $empleado_id, $fecha, $hora]);
        if ((int) $v->fetchColumn() > 0) {
            out(['success' => false, 'message' => 'Ese horario ya no está disponible.'], 409);
        }

        $stmt = $pdo->prepare("INSERT INTO citas
            (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_id, cliente_nombre, cliente_telefono, cliente_email, inicio, fin, estado, notas, creado_por_usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?)");
        $stmt->execute([
            $empresa_id,
            $sede_id,
            $servicio_id,
            $empleado_id,
            null,
            $cliente_nombre !== '' ? $cliente_nombre : 'Cliente',
            $cliente_tel !== '' ? $cliente_tel : null,
            $cliente_email !== '' ? $cliente_email : null,
            $inicio,
            $fin,
            'pendiente',
            $notas !== '' ? $notas : null,
            $cliente_user_id,
        ]);
        out(['success' => true, 'message' => 'Cita reservada con éxito.']);
        break;

    default:
        out(['error' => 'invalid_action'], 400);
}

