<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
if ($action === 'create')
    $action = 'save';
if ($action === 'read')
    $action = 'get';
if ($action === 'update')
    $action = 'save';

$user = current_user();
$id_e = request_id_e();
$role = $user['rol'] ?? null;
$empresa_id = resolve_private_empresa_id($user);



$is_authorized = ($user && $empresa_id > 0 && in_array($role, ['superadmin', 'admin'], true));
if (!$is_authorized)
    json_response(['error' => 'unauthorized'], 403);

function admin_citas_autocomplete($empresa_id)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE citas
                           SET estado = 'completada'
                           WHERE empresa_id = ?
                             AND estado IN ('pendiente','confirmada')
                             AND fin IS NOT NULL
                             AND fin < NOW()");
    $stmt->execute([(int) $empresa_id]);
    dispatch_pending_review_invitations((int) $empresa_id, 25);
}

if (in_array($action, ['list', 'get', 'calendar', 'update_status'], true)) {
    admin_citas_autocomplete($empresa_id);
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

        if ($search !== '') {
            $where[] = '(c.cliente_nombre LIKE ? OR c.cliente_email LIKE ? OR s.nombre LIKE ? OR u.nombre LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
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

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
            WHERE $whereSql
        ");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

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

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT c.*, s.nombre as sucursal_nombre, s.direccion as sucursal_direccion,
                                      srv.nombre as servicio_nombre, srv.descripcion as servicio_descripcion,
                                      srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos,
                                      u.nombre as empleado_nombre
                               FROM citas c
                               LEFT JOIN sucursales s ON c.sucursal_id = s.id
                               LEFT JOIN servicios srv ON c.servicio_id = srv.id
                               LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
                               WHERE c.id = ? AND c.empresa_id = ?');
        $stmt->execute([$id, $empresa_id]);
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
                   srv.precio_base as servicio_precio, srv.duracion_minutos as servicio_duracion_minutos,
                   u.nombre as empleado_nombre
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            LEFT JOIN usuarios u ON c.empleado_usuario_id = u.id
            WHERE c.empresa_id = ? AND DATE(c.inicio) BETWEEN ? AND ?
            ORDER BY c.inicio ASC
        ");
        $stmt->execute([$empresa_id, $from, $to]);
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
        $stmt = $pdo->prepare('SELECT estado, inicio, notas FROM citas WHERE id=? AND empresa_id=? LIMIT 1');
        $stmt->execute([$id, $empresa_id]);
        $cur = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$cur) {
            json_response(['success' => false, 'message' => 'Cita no encontrada.']);
        }
        if ((string) ($cur['estado'] ?? '') === 'completada' && ($role !== 'superadmin')) {
            json_response(['success' => false, 'message' => 'Esta cita ya está completada y no puede modificarse.']);
        }
        $actor_name = trim((string) ($user['nombre'] ?? $user['email'] ?? 'admin'));
        $actor_id = (int) ($user['id'] ?? 0);
        $notas_final = build_cita_notas_timeline((string) ($cur['notas'] ?? ''), $notas, $actor_name, $actor_id);

        $stmt = $pdo->prepare('UPDATE citas SET estado=?, notas=? WHERE id=? AND empresa_id=?');
        $stmt->execute([$estado, $notas_final, $id, $empresa_id]);
        if ((int) $stmt->rowCount() <= 0) {
            json_response(['success' => false, 'message' => 'No se pudo actualizar la cita.']);
        }
        if ($estado === 'completada') {
            maybe_send_review_invitation_for_cita($empresa_id, $id);
        }
        audit_event('update', 'citas', $id, 'Actualización de estado de cita', $empresa_id, [
            'estado' => $estado,
        ]);
        json_response(['success' => true]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        $sucursal_id = (int) ($_POST['sucursal_id'] ?? 0);
        $servicio_id = (int) ($_POST['servicio_id'] ?? 0);
        $empleado_usuario_id = (int) ($_POST['empleado_usuario_id'] ?? 0);
        $cliente_id = (int) ($_POST['cliente_id'] ?? 0);
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
        $cliente_email = trim($_POST['cliente_email'] ?? '');
        $inicio = trim($_POST['inicio'] ?? '');
        $estado = trim($_POST['estado'] ?? 'pendiente');
        $notas = trim($_POST['notas'] ?? '');

        if ($cliente_id > 0) {
            $stmt = $pdo->prepare('SELECT c.id, c.nombre, c.telefono, c.email
                                   FROM cliente_empresas ce
                                   JOIN clientes c ON c.id = ce.cliente_id
                                   WHERE ce.empresa_id = ? AND ce.cliente_id = ? AND c.activo = 1
                                   LIMIT 1');
            $stmt->execute([$empresa_id, $cliente_id]);
            $cli = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$cli) {
                json_response(['success' => false, 'message' => 'El cliente seleccionado no pertenece a esta empresa.']);
            }
            $cliente_nombre = (string) ($cli['nombre'] ?? '');
            $cliente_telefono = (string) ($cli['telefono'] ?? '');
            $cliente_email = (string) ($cli['email'] ?? '');
        }

        if ($cliente_nombre === '' || $sucursal_id <= 0 || $servicio_id <= 0 || $empleado_usuario_id <= 0 || $inicio === '') {
            json_response(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        }

        $inicio_dt = strtotime($inicio);
        if ($inicio_dt === false) {
            json_response(['success' => false, 'message' => 'Fecha/hora de inicio inválida.']);
        }
        $inicio = date('Y-m-d H:i:s', $inicio_dt);

        $stmt = $pdo->prepare('SELECT duracion_minutos FROM servicios WHERE id=? AND empresa_id=? AND activo=1 LIMIT 1');
        $stmt->execute([$servicio_id, $empresa_id]);
        $duracion = (int) ($stmt->fetchColumn() ?: 0);
        if ($duracion <= 0) {
            json_response(['success' => false, 'message' => 'Servicio inválido o inactivo.']);
        }
        $fin = date('Y-m-d H:i:s', $inicio_dt + ($duracion * 60));

        $stmt = $pdo->prepare('SELECT id FROM sucursales WHERE id = ? AND empresa_id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$sucursal_id, $empresa_id]);
        if (!$stmt->fetchColumn()) {
            json_response(['success' => false, 'message' => 'Sucursal inválida o inactiva.']);
        }

        $stmt = $pdo->prepare('SELECT id, sucursal_id FROM usuarios WHERE id = ? AND empresa_id = ? AND rol IN ("admin","gerente","empleado") AND activo = 1 LIMIT 1');
        $stmt->execute([$empleado_usuario_id, $empresa_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$emp) {
            json_response(['success' => false, 'message' => 'Empleado inválido o inactivo.']);
        }
        $emp_sucursal = (int) ($emp['sucursal_id'] ?? 0);
        if ($emp_sucursal > 0 && $emp_sucursal !== $sucursal_id) {
            json_response(['success' => false, 'message' => 'El empleado no pertenece a la sucursal seleccionada.']);
        }

        $stmt = $pdo->prepare('SELECT id FROM empleado_servicios WHERE empleado_usuario_id = ? AND servicio_id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$empleado_usuario_id, $servicio_id]);
        if (!$stmt->fetchColumn()) {
            json_response(['success' => false, 'message' => 'El empleado no tiene asignado este servicio.']);
        }

        $weekday = (int) date('w', $inicio_dt);
        $hora_inicio = date('H:i:s', $inicio_dt);
        $hora_fin = date('H:i:s', strtotime($fin));
        $stmt = $pdo->prepare('SELECT hora_inicio, hora_fin FROM empleado_horarios WHERE empleado_usuario_id = ? AND weekday = ?');
        $stmt->execute([$empleado_usuario_id, $weekday]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($horarios)) {
            $ok_window = false;
            foreach ($horarios as $h) {
                if ($hora_inicio >= $h['hora_inicio'] && $hora_fin <= $h['hora_fin']) {
                    $ok_window = true;
                    break;
                }
            }
            if (!$ok_window) {
                json_response(['success' => false, 'message' => 'La hora seleccionada está fuera del horario laboral del empleado.']);
            }
        }

        $sql_overlap = "SELECT COUNT(*) FROM citas
                        WHERE empresa_id = ?
                          AND empleado_usuario_id = ?
                          AND estado <> 'cancelada'
                          AND inicio < ?
                          AND fin > ?";
        $params_overlap = [$empresa_id, $empleado_usuario_id, $fin, $inicio];
        if ($id > 0) {
            $sql_overlap .= " AND id <> ?";
            $params_overlap[] = $id;
        }
        $stmt = $pdo->prepare($sql_overlap);
        $stmt->execute($params_overlap);
        if ((int) $stmt->fetchColumn() > 0) {
            json_response(['success' => false, 'message' => 'El empleado ya tiene una cita en ese horario.']);
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM empleado_bloqueos WHERE empleado_usuario_id = ? AND inicio < ? AND fin > ?');
        $stmt->execute([$empleado_usuario_id, $fin, $inicio]);
        if ((int) $stmt->fetchColumn() > 0) {
            json_response(['success' => false, 'message' => 'El horario está bloqueado para este empleado.']);
        }

        if ($id > 0) {
            $stmtCur = $pdo->prepare('SELECT estado FROM citas WHERE id = ? AND empresa_id = ? LIMIT 1');
            $stmtCur->execute([$id, $empresa_id]);
            $currentEstado = (string) ($stmtCur->fetchColumn() ?: '');
            if ($currentEstado === 'completada' && ($role !== 'superadmin')) {
                json_response(['success' => false, 'message' => 'Esta cita completada solo puede ser modificada por superadmin.']);
            }
            $stmt = $pdo->prepare('UPDATE citas SET sucursal_id=?, servicio_id=?, empleado_usuario_id=?, cliente_id=?, cliente_nombre=?, cliente_telefono=?, cliente_email=?, inicio=?, fin=?, estado=?, notas=? WHERE id=? AND empresa_id=?');
            $stmt->execute([$sucursal_id, $servicio_id, $empleado_usuario_id, $cliente_id > 0 ? $cliente_id : null, $cliente_nombre, $cliente_telefono, $cliente_email, $inicio, $fin, $estado, $notas, $id, $empresa_id]);
            audit_event('update', 'citas', $id, 'Edición de cita', $empresa_id);
        } else {
            $creado_por = (int) $user['id'];
            $stmt = $pdo->prepare('INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_id, cliente_nombre, cliente_telefono, cliente_email, inicio, fin, estado, notas, creado_por_usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$empresa_id, $sucursal_id, $servicio_id, $empleado_usuario_id, $cliente_id > 0 ? $cliente_id : null, $cliente_nombre, $cliente_telefono, $cliente_email, $inicio, $fin, $estado, $notas, $creado_por]);
            $id = (int) $pdo->lastInsertId();
            audit_event('create', 'citas', $id, 'Creación de cita', $empresa_id);
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
                'usuario_id' => (int) $empleado_usuario_id,
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
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false, 'message' => 'ID inválido.']);
        $stmtCur = $pdo->prepare("SELECT estado FROM citas WHERE id=? AND empresa_id=? LIMIT 1");
        $stmtCur->execute([$id, $empresa_id]);
        $curEstado = (string) ($stmtCur->fetchColumn() ?: '');
        if ($curEstado === 'completada' && ($role !== 'superadmin')) {
            json_response(['success' => false, 'message' => 'Esta cita completada solo puede ser modificada por superadmin.']);
        }

        // Borrado lógico: cancelar
        $stmt = $pdo->prepare("UPDATE citas SET estado='cancelada' WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $empresa_id]);
        audit_event('delete', 'citas', $id, 'Cancelación de cita', $empresa_id);
        json_response(['success' => true]);
        break;

    case 'get_options':
        $sucursal_filter = (int) ($_GET['sucursal_id'] ?? 0);
        $servicio_filter = (int) ($_GET['servicio_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT id, nombre FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC');
        $stmt->execute([$empresa_id]);
        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT id, nombre FROM servicios WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC');
        $stmt->execute([$empresa_id]);
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($servicio_filter > 0) {
            $sql_emp = "SELECT DISTINCT u.id, u.nombre
                        FROM usuarios u
                        JOIN empleado_servicios es ON es.empleado_usuario_id = u.id AND es.activo = 1
                        WHERE u.empresa_id = ?
                          AND u.rol IN ('admin','gerente','empleado')
                          AND u.activo = 1
                          AND es.servicio_id = ?";
            $params_emp = [$empresa_id, $servicio_filter];
            if ($sucursal_filter > 0) {
                $sql_emp .= " AND (u.sucursal_id IS NULL OR u.sucursal_id = 0 OR u.sucursal_id = ?)";
                $params_emp[] = $sucursal_filter;
            }
            $sql_emp .= " ORDER BY u.nombre ASC";
            $stmt = $pdo->prepare($sql_emp);
            $stmt->execute($params_emp);
        } else {
            $sql_emp = "SELECT id, nombre
                        FROM usuarios
                        WHERE empresa_id = ?
                          AND rol IN ('admin','gerente','empleado')
                          AND activo = 1";
            $params_emp = [$empresa_id];
            if ($sucursal_filter > 0) {
                $sql_emp .= " AND (sucursal_id IS NULL OR sucursal_id = 0 OR sucursal_id = ?)";
                $params_emp[] = $sucursal_filter;
            }
            $sql_emp .= " ORDER BY nombre ASC";
            $stmt = $pdo->prepare($sql_emp);
            $stmt->execute($params_emp);
        }
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT c.id, c.nombre, c.telefono, c.email
                               FROM cliente_empresas ce
                               JOIN clientes c ON c.id = ce.cliente_id
                               WHERE ce.empresa_id = ? AND c.activo = 1
                               ORDER BY c.nombre ASC');
        $stmt->execute([$empresa_id]);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'sucursales' => $sucursales, 'servicios' => $servicios, 'empleados' => $empleados, 'clientes' => $clientes]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
