<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
$id_e = request_id_e();

if (!$user || ($user['rol'] ?? null) !== 'cliente') {
    json_response(['error' => 'unauthorized'], 403);
}

$empresa_id = (int) ($user['empresa_id'] ?? 0);
$cliente_id = (int) ($user['id'] ?? 0);

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = max(1, (int) ($_GET['per'] ?? 10));
        $offset = ($page - 1) * $per;

        $stmt = $pdo->prepare("
            SELECT c.*, s.nombre as sucursal_nombre, srv.nombre as servicio_nombre
            FROM citas c
            LEFT JOIN sucursales s ON c.sucursal_id = s.id
            LEFT JOIN servicios srv ON c.servicio_id = srv.id
            WHERE c.empresa_id = ? AND c.creado_por_usuario_id = ?
            ORDER BY c.inicio DESC LIMIT ? OFFSET ?
        ");
        $stmt->execute([$empresa_id, $cliente_id, $per, $offset]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE empresa_id = ? AND creado_por_usuario_id = ?");
        $stmt->execute([$empresa_id, $cliente_id]);
        $total = (int) $stmt->fetchColumn();

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => ceil($total / $per)]);
        break;

    case 'get_sedes':
        $stmt = $pdo->prepare('SELECT id, nombre, direccion, horarios_json as horario FROM sucursales WHERE empresa_id = ? AND activo = 1');
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_servicios':
        $stmt = $pdo->prepare('SELECT id, nombre, precio_base, duracion_minutos, descripcion FROM servicios WHERE empresa_id = ? AND activo = 1');
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_booked_slots':
        $sede_id = (int) ($_GET['sede_id'] ?? 0);
        $fecha = $_GET['fecha'] ?? '';
        if (!$sede_id || !$fecha)
            json_response(['success' => false, 'message' => 'Missing data']);

        $stmt = $pdo->prepare("SELECT DATE_FORMAT(inicio, '%H:%00') as hora FROM citas WHERE sucursal_id = ? AND DATE(inicio) = ? AND estado != 'cancelada'");
        $stmt->execute([$sede_id, $fecha]);
        $slots = $stmt->fetchAll(PDO::FETCH_COLUMN);
        json_response(['success' => true, 'data' => $slots]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $sede_id = (int) ($_POST['sede_id'] ?? 0);
        $servicio_id = (int) ($_POST['servicio_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? ''); // YYYY-MM-DD HH:MM:SS
        $notas = trim($_POST['notas'] ?? '');

        if (!$sede_id || !$servicio_id || !$fecha) {
            json_response(['success' => false, 'message' => 'Faltan campos obligatorios.']);
        }

        // Obtener la duración del servicio para calcular el fin
        $stmt = $pdo->prepare('SELECT duracion_minutos FROM servicios WHERE id=? AND empresa_id=?');
        $stmt->execute([$servicio_id, $empresa_id]);
        $duracion = (int) $stmt->fetchColumn() ?: 60;
        $fin = date('Y-m-d H:i:s', strtotime($fecha) + ($duracion * 60));

        // Buscar un empleado disponible para esa sede (asignación automática simple para el cliente)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE empresa_id=? AND sucursal_id=? AND rol IN ('admin','gerente','empleado') AND activo=1 LIMIT 1");
        $stmt->execute([$empresa_id, $sede_id]);
        $empleado_id = (int) $stmt->fetchColumn();

        if (!$empleado_id) {
            json_response(['success' => false, 'message' => 'No hay personal disponible en esta sede por el momento.']);
        }

        $stmt = $pdo->prepare('INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_nombre, cliente_telefono, cliente_email, inicio, fin, estado, notas, creado_por_usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $empresa_id,
            $sede_id,
            $servicio_id,
            $empleado_id,
            $user['nombre'],
            $user['telefono'],
            $user['email'],
            $fecha,
            $fin,
            'pendiente',
            $notas,
            $cliente_id
        ]);

        json_response(['success' => true, 'message' => 'Cita reservada con éxito.']);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
