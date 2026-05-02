<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();

$roles_permitidos = ['superadmin', 'admin', 'gerente', 'empleado'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = resolve_private_empresa_id($user);
$empleado_id = (int) ($user['id'] ?? 0);
$actor_role = (string) ($user['rol'] ?? '');
$sucursal_id_actor = (int) ($user['sucursal_id'] ?? 0);

function empleado_scope($actor_role, $empleado_id, $sucursal_id_actor)
{
    if ($actor_role === 'empleado') {
        return ['sql' => ' AND empleado_usuario_id = ?', 'params' => [(int) $empleado_id]];
    }
    if ($actor_role === 'gerente' && $sucursal_id_actor > 0) {
        return ['sql' => ' AND sucursal_id = ?', 'params' => [(int) $sucursal_id_actor]];
    }
    return ['sql' => '', 'params' => []];
}

function empleado_citas_autocomplete($empresa_id, $scope_sql, $scope_params)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE citas
                           SET estado = 'completada'
                           WHERE empresa_id = ?
                             AND estado IN ('pendiente','confirmada')
                             AND fin IS NOT NULL
                             AND fin < NOW()" . $scope_sql);
    $stmt->execute(array_merge([(int) $empresa_id], $scope_params));
    dispatch_pending_review_invitations((int) $empresa_id, 25);
}

$scope = empleado_scope($actor_role, $empleado_id, $sucursal_id_actor);
$scope_sql = (string) ($scope['sql'] ?? '');
$scope_params = (array) ($scope['params'] ?? []);

if (in_array($action, ['list', 'get', 'calendar', 'update_status'], true)) {
    empleado_citas_autocomplete($empresa_id, $scope_sql, $scope_params);
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim((string) ($_GET['search'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));
        $offset = ($page - 1) * $per;

        $where = ['c.empresa_id = ?'];
        $params = [$empresa_id];
        if ($scope_sql !== '') {
            $where[] = ltrim($scope_sql, ' AND');
            $params = array_merge($params, $scope_params);
        }
        if ($search !== '') {
            $where[] = '(c.cliente_nombre LIKE ? OR c.cliente_email LIKE ? OR srv.nombre LIKE ?)';
            $term = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if ($estado !== '') {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'DATE(c.inicio) >= ?';
            $params[] = $from;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'DATE(c.inicio) <= ?';
            $params[] = $to;
        }
        $whereSql = implode(' AND ', $where);

        $per = max(1, (int) $per);
        $offset = max(0, (int) $offset);
        $stmt = $pdo->prepare("
            SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                   srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                   srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            WHERE $whereSql
            ORDER BY c.inicio DESC LIMIT $per OFFSET $offset
        ");
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM citas c
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            WHERE $whereSql
        ");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => ceil($total / $per)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                                      srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                                      srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos
                               FROM citas c
                               LEFT JOIN sucursales s ON c.sucursal_id = s.id
                               LEFT JOIN servicios srv ON c.servicio_id = srv.id
                               WHERE c.id = ? AND c.empresa_id = ?' . $scope_sql);
        $stmt->execute(array_merge([$id, $empresa_id], $scope_params));
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'calendar':
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        if ($from === '' || $to === '') {
            json_response(['success' => false, 'message' => 'Rango de fechas requerido.'], 400);
        }

        $stmt = $pdo->prepare("
            SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                   srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                   srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            WHERE c.empresa_id = ? $scope_sql AND DATE(c.inicio) BETWEEN ? AND ?
            ORDER BY c.inicio ASC
        ");
        $stmt->execute(array_merge([$empresa_id], $scope_params, [$from, $to]));
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data]);
        break;

    case 'update_status':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }
        $id = (int) ($_POST['id'] ?? 0);
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $notas = trim((string) ($_POST['notas'] ?? ''));
        $permitidos = ['pendiente', 'confirmada', 'cancelada', 'completada', 'no_asistio'];
        if ($id <= 0 || !in_array($estado, $permitidos, true)) {
            json_response(['success' => false, 'message' => 'Datos inválidos.']);
        }
        $stmt = $pdo->prepare('SELECT estado, inicio, fin, notas FROM citas WHERE id = ? AND empresa_id = ?' . $scope_sql . ' LIMIT 1');
        $stmt->execute(array_merge([$id, $empresa_id], $scope_params));
        $cur = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$cur) {
            json_response(['success' => false, 'message' => 'Cita no encontrada.']);
        }
        $estado_actual = (string) ($cur['estado'] ?? '');
        if (!in_array($actor_role, ['admin', 'superadmin'], true) && in_array($estado_actual, ['completada', 'cancelada'], true)) {
            json_response(['success' => false, 'message' => 'Esta cita ya no puede ser modificada.']);
        }
        $hoy = date('Y-m-d');
        $inicio_day = substr((string) ($cur['inicio'] ?? ''), 0, 10);
        $fin_time = strtotime((string) ($cur['fin'] ?? ''));
        if ($actor_role !== 'superadmin' && ($inicio_day < $hoy || ($fin_time !== false && $fin_time < time()) || (string) ($cur['estado'] ?? '') === 'completada')) {
            json_response(['success' => false, 'message' => 'Esta cita ya no puede ser modificada por empleado.']);
        }

        $actor_name = trim((string) ($user['nombre'] ?? $user['email'] ?? 'empleado'));
        $actor_id = (int) ($user['id'] ?? 0);
        $notas_final = build_cita_notas_timeline((string) ($cur['notas'] ?? ''), $notas, $actor_name, $actor_id);

        $stmt = $pdo->prepare('UPDATE citas SET estado = ?, notas = ? WHERE id = ? AND empresa_id = ?' . $scope_sql);
        $stmt->execute(array_merge([$estado, $notas_final, $id, $empresa_id], $scope_params));
        if ((int) $stmt->rowCount() <= 0) {
            json_response(['success' => false, 'message' => 'No se pudo actualizar la cita.']);
        }
        if ($estado === 'completada') {
            maybe_send_review_invitation_for_cita($empresa_id, $id);
        }
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
