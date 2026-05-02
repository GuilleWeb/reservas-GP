<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$role = (string) ($user['rol'] ?? '');
$id_e = request_id_e();

$roles_permitidos = ['superadmin', 'admin', 'gerente'];
if (!$user || !in_array($user['rol'] ?? null, $roles_permitidos)) {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = resolve_private_empresa_id($user);
$sucursal_id_filtro = (int) ($user['sucursal_id'] ?? 0);
if ($empresa_id <= 0) {
    json_response(['error' => 'unauthorized'], 403);
}

function sucursal_citas_autocomplete($empresa_id, $sucursal_id_filtro)
{
    global $pdo;
    $sql = "UPDATE citas
            SET estado = 'completada'
            WHERE empresa_id = ?
              AND estado IN ('pendiente','confirmada')
              AND fin IS NOT NULL
              AND fin < NOW()";
    $params = [(int) $empresa_id];
    if ((int) $sucursal_id_filtro > 0) {
        $sql .= " AND sucursal_id = ?";
        $params[] = (int) $sucursal_id_filtro;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    dispatch_pending_review_invitations((int) $empresa_id, 25);
}

// Mapeo de acciones para compatibilidad
if ($action === 'create')
    $action = 'save';
if ($action === 'read')
    $action = 'get';
if ($action === 'update')
    $action = 'save';

if (in_array($action, ['list', 'get', 'calendar', 'update_status'], true)) {
    sucursal_citas_autocomplete($empresa_id, $sucursal_id_filtro);
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? '';
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        $where = ['c.empresa_id = ?'];
        $params = [$empresa_id];

        if ($sucursal_id_filtro > 0) {
            $where[] = 'c.sucursal_id = ?';
            $params[] = $sucursal_id_filtro;
        }

        if ($search !== '') {
            $where[] = '(c.cliente_nombre LIKE ? OR c.cliente_email LIKE ? OR s.nombre LIKE ? OR u.nombre LIKE ?)';
            $term = "%$search%";
            $params[] = $term;
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

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas c LEFT JOIN sucursales s ON c.sucursal_id = s.id LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id WHERE $whereSql");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = ($page - 1) * $per;
        $sql = "
            SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                   srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                   srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos,
                   u.nombre as empleado_nombre
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
            WHERE $whereSql
            ORDER BY c.inicio DESC LIMIT $per OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => ceil($total / $per)]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $sql = 'SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                       srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                       srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos,
                       u.nombre as empleado_nombre
                FROM citas c
                LEFT JOIN sucursales s ON c.sucursal_id = s.id
                LEFT JOIN servicios srv ON c.servicio_id = srv.id
                LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
                WHERE c.id = ? AND c.empresa_id = ?';
        $params = [$id, $empresa_id];
        if ($sucursal_id_filtro > 0) {
            $sql .= ' AND c.sucursal_id = ?';
            $params[] = $sucursal_id_filtro;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'calendar':
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));
        if ($from === '' || $to === '') {
            json_response(['success' => false, 'message' => 'Rango de fechas requerido.'], 400);
        }
        $where = ['c.empresa_id = ?', 'DATE(c.inicio) BETWEEN ? AND ?'];
        $params = [$empresa_id, $from, $to];
        if ($sucursal_id_filtro > 0) {
            $where[] = 'c.sucursal_id = ?';
            $params[] = $sucursal_id_filtro;
        }
        $whereSql = implode(' AND ', $where);
        $stmt = $pdo->prepare("
            SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                   srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                   srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos,
                   u.nombre as empleado_nombre
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
            WHERE $whereSql
            ORDER BY c.inicio ASC
        ");
        $stmt->execute($params);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'update_status':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $notas = trim((string) ($_POST['notas'] ?? ''));
        $permitidos = ['pendiente', 'confirmada', 'cancelada', 'completada', 'no_asistio'];
        if ($id <= 0 || !in_array($estado, $permitidos, true)) {
            json_response(['success' => false, 'message' => 'Datos inválidos.']);
        }
        $sqlc = 'SELECT estado, inicio, fin, sucursal_id, notas FROM citas WHERE id=? AND empresa_id=?';
        $paramsc = [$id, $empresa_id];
        if ($sucursal_id_filtro > 0) {
            $sqlc .= ' AND sucursal_id=?';
            $paramsc[] = $sucursal_id_filtro;
        }
        $stmt = $pdo->prepare($sqlc);
        $stmt->execute($paramsc);
        $cur = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$cur) {
            json_response(['success' => false, 'message' => 'Cita no encontrada.']);
        }
        $hoy = date('Y-m-d');
        $inicio_day = substr((string) ($cur['inicio'] ?? ''), 0, 10);
        $fin_time = strtotime((string) ($cur['fin'] ?? ''));
        $estado_actual = (string) ($cur['estado'] ?? '');
        if (!in_array($role, ['admin', 'superadmin'], true) && in_array($estado_actual, ['completada', 'cancelada'], true)) {
            json_response(['success' => false, 'message' => 'Esta cita ya no puede ser modificada.']);
        }
        if ($role !== 'superadmin' && ($inicio_day < $hoy || ($fin_time !== false && $fin_time < time()) || $estado_actual === 'completada')) {
            json_response(['success' => false, 'message' => 'Esta cita ya no puede ser modificada por gerente.']);
        }
        $actor_name = trim((string) ($user['nombre'] ?? $user['email'] ?? 'gerente'));
        $actor_id = (int) ($user['id'] ?? 0);
        $notas_final = build_cita_notas_timeline((string) ($cur['notas'] ?? ''), $notas, $actor_name, $actor_id);

        $sql = 'UPDATE citas SET estado=?, notas=? WHERE id=? AND empresa_id=?';
        $params = [$estado, $notas_final, $id, $empresa_id];
        if ($sucursal_id_filtro > 0) {
            $sql .= ' AND sucursal_id=?';
            $params[] = $sucursal_id_filtro;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->rowCount() <= 0) {
            json_response(['success' => false, 'message' => 'No se pudo actualizar la cita.']);
        }
        if ($estado === 'completada') {
            maybe_send_review_invitation_for_cita($empresa_id, $id);
        }
        audit_event('update', 'citas', $id, 'Actualización de estado de cita (gerencia)', $empresa_id);
        json_response(['success' => true]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $suc_id = (int) ($_POST['sucursal_id'] ?? 0);
        $srv_id = (int) ($_POST['servicio_id'] ?? 0);
        $emp_id = (int) ($_POST['empleado_usuario_id'] ?? 0);

        // Si el gerente está atado a una sucursal, forzamos esa sucursal
        if ($sucursal_id_filtro > 0)
            $suc_id = $sucursal_id_filtro;

        $cliente_id = (int) ($_POST['cliente_id'] ?? 0);
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
        $cliente_email = trim($_POST['cliente_email'] ?? '');
        $inicio = trim($_POST['inicio'] ?? '');
        $estado = trim($_POST['estado'] ?? 'pendiente');
        $notas = trim($_POST['notas'] ?? '');

        if (!$cliente_nombre || !$suc_id || !$srv_id || !$emp_id || !$inicio) {
            json_response(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        }
        if ($cliente_id > 0) {
            $stmt = $pdo->prepare('SELECT c.id, c.nombre, c.telefono, c.email
                                   FROM clientes c
                                   WHERE c.empresa_id = ? AND c.id = ? AND c.activo = 1
                                   LIMIT 1');
            $stmt->execute([$empresa_id, $cliente_id]);
            $cli = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$cli) {
                json_response(['success' => false, 'message' => 'Cliente inválido para esta empresa.']);
            }
            $cliente_nombre = (string) ($cli['nombre'] ?? '');
            $cliente_telefono = (string) ($cli['telefono'] ?? '');
            $cliente_email = (string) ($cli['email'] ?? '');
        }

        $stmt = $pdo->prepare('SELECT duracion_minutos FROM servicios WHERE id=? AND empresa_id=? AND activo=1 LIMIT 1');
        $stmt->execute([$srv_id, $empresa_id]);
        $duracion = (int) ($stmt->fetchColumn() ?: 30);
        $inicioTs = strtotime($inicio);
        if ($inicioTs === false) {
            json_response(['success' => false, 'message' => 'Fecha/hora inválida.']);
        }
        $inicio = date('Y-m-d H:i:s', $inicioTs);
        $fin = date('Y-m-d H:i:s', $inicioTs + ($duracion * 60));

        if ($id > 0) {
            $stmtCur = $pdo->prepare('SELECT estado FROM citas WHERE id = ? AND empresa_id = ?' . ($sucursal_id_filtro > 0 ? ' AND sucursal_id = ?' : '') . ' LIMIT 1');
            $paramsCur = [$id, $empresa_id];
            if ($sucursal_id_filtro > 0) {
                $paramsCur[] = $sucursal_id_filtro;
            }
            $stmtCur->execute($paramsCur);
            $estadoCur = (string) ($stmtCur->fetchColumn() ?: '');
            if (!in_array($role, ['admin', 'superadmin'], true) && in_array($estadoCur, ['completada', 'cancelada'], true)) {
                json_response(['success' => false, 'message' => 'Esta cita ya no puede ser editada.']);
            }

            $stmt = $pdo->prepare('UPDATE citas SET sucursal_id=?, servicio_id=?, empleado_usuario_id=?, cliente_id=?, cliente_nombre=?, cliente_telefono=?, cliente_email=?, inicio=?, fin=?, estado=?, notas=? WHERE id=? AND empresa_id=?');
            $stmt->execute([$suc_id, $srv_id, $emp_id, $cliente_id > 0 ? $cliente_id : null, $cliente_nombre, $cliente_telefono, $cliente_email, $inicio, $fin, $estado, $notas, $id, $empresa_id]);
            audit_event('update', 'citas', $id, 'Edición de cita (gerencia)', $empresa_id);
        } else {
            $stmt = $pdo->prepare('INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_id, cliente_nombre, cliente_telefono, cliente_email, inicio, fin, estado, notas, creado_por_usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$empresa_id, $suc_id, $srv_id, $emp_id, $cliente_id > 0 ? $cliente_id : null, $cliente_nombre, $cliente_telefono, $cliente_email, $inicio, $fin, $estado, $notas, $user['id']]);
            $id = (int) $pdo->lastInsertId();
            audit_event('create', 'citas', $id, 'Creación de cita (gerencia)', $empresa_id);
            create_notification([
                'empresa_id' => $empresa_id,
                'rol_destino' => 'admin',
                'tipo' => 'cita_nueva',
                'titulo' => 'Nueva cita registrada',
                'descripcion' => $cliente_nombre . ' · ' . date('d/m/Y H:i', strtotime($inicio)),
                'url' => view_url('vistas/admin/admin-citas.php', $empresa_id),
                'referencia_tipo' => 'cita',
                'referencia_id' => $id,
            ]);
            create_notification([
                'empresa_id' => $empresa_id,
                'rol_destino' => 'gerente',
                'tipo' => 'cita_nueva',
                'titulo' => 'Nueva cita registrada',
                'descripcion' => $cliente_nombre . ' · ' . date('d/m/Y H:i', strtotime($inicio)),
                'url' => view_url('vistas/sucursal/admin-citas.php', $empresa_id),
                'referencia_tipo' => 'cita',
                'referencia_id' => $id,
            ]);
            create_notification([
                'empresa_id' => $empresa_id,
                'usuario_id' => (int) $emp_id,
                'rol_destino' => 'empleado',
                'tipo' => 'cita_nueva',
                'titulo' => 'Nueva cita asignada',
                'descripcion' => $cliente_nombre . ' · ' . date('d/m/Y H:i', strtotime($inicio)),
                'url' => view_url('vistas/empleado/citas.php', $empresa_id),
                'referencia_tipo' => 'cita',
                'referencia_id' => $id,
            ]);
        }
        json_response(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE citas SET estado='cancelada' WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $empresa_id]);
        audit_event('delete', 'citas', $id, 'Cancelación de cita (gerencia)', $empresa_id);
        json_response(['success' => true]);
        break;

    case 'get_options':
        $sucursal_filter = (int) ($_GET['sucursal_id'] ?? $sucursal_id_filtro);
        $servicio_filter = (int) ($_GET['servicio_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT id, nombre FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC');
        $stmt->execute([$empresa_id]);
        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT id, nombre FROM servicios WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC');
        $stmt->execute([$empresa_id]);
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql_emp = "SELECT DISTINCT u.id, u.nombre
                    FROM usuarios u
                    LEFT JOIN empleado_servicios es ON es.empleado_usuario_id = u.id AND es.activo = 1
                    WHERE u.empresa_id = ?
                      AND u.rol IN ('admin','gerente','empleado')
                      AND u.activo = 1";
        $params_emp = [$empresa_id];
        if ($sucursal_filter > 0) {
            $sql_emp .= " AND (u.sucursal_id IS NULL OR u.sucursal_id = 0 OR u.sucursal_id = ?)";
            $params_emp[] = $sucursal_filter;
        }
        if ($servicio_filter > 0) {
            $sql_emp .= " AND es.servicio_id = ?";
            $params_emp[] = $servicio_filter;
        }
        $sql_emp .= " ORDER BY u.nombre ASC";
        $stmt = $pdo->prepare($sql_emp);
        $stmt->execute($params_emp);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT c.id, c.nombre, c.telefono, c.email
                               FROM clientes c
                               WHERE c.empresa_id = ? AND c.activo = 1
                               ORDER BY c.nombre ASC');
        $stmt->execute([$empresa_id]);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'sucursales' => $sucursales, 'servicios' => $servicios, 'empleados' => $empleados, 'clientes' => $clientes]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
