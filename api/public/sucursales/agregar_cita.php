<?php
require_once __DIR__ . '/../../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$slug = request_id_e();

if (!$slug) {
    json_response(['success' => false, 'message' => 'Slug de empresa no proporcionado'], 400);
}

// Obtener ID de empresa
$stmt = $pdo->prepare("SELECT id FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
$stmt->execute([$slug]);
$empresa_id = (int) $stmt->fetchColumn();

if (!$empresa_id) {
    json_response(['success' => false, 'message' => 'Empresa no encontrada'], 404);
}

switch ($action) {
    case 'get_sucursales':
        $stmt = $pdo->prepare("SELECT id, nombre, direccion, telefono, email, horarios_json FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_servicios':
        $stmt = $pdo->prepare("SELECT id, nombre, descripcion, duracion_minutos as duracion, precio_base as precio FROM servicios WHERE empresa_id = ? AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_empleados':
        // El usuario pide "foto, nombre, puesto". Usaremos la tabla equipo.
        $stmt = $pdo->prepare("SELECT id, nombre, especialidad as puesto, imagen_path as foto FROM equipo WHERE empresa_id = ? AND activo = 1 ORDER BY orden ASC");
        $stmt->execute([$empresa_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'get_horarios':
        $sede_id = (int) ($_GET['sede_id'] ?? 0);
        $fecha = $_GET['fecha'] ?? ''; // YYYY-MM-DD
        $servicio_id = (int) ($_GET['servicio_id'] ?? 0);
        $empleado_id = (int) ($_GET['empleado_id'] ?? 0);

        if (!$sede_id || !$fecha || !$servicio_id) {
            json_response(['success' => false, 'message' => 'Faltan parámetros'], 400);
        }

        // Obtener horario de la sucursal
        $stmt = $pdo->prepare("SELECT horarios_json FROM sucursales WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$sede_id, $empresa_id]);
        $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

        $horarios_full = json_decode($sucursal['horarios_json'] ?? '{}', true);
        $day_of_week = date('N', strtotime($fecha)); // 1-7
        $key = 'lun-vie';
        if ($day_of_week == 6)
            $key = 'sab';
        if ($day_of_week == 7)
            $key = 'dom';

        $rango = $horarios_full[$key] ?? null;
        if (!$rango || empty($rango['inicio']) || empty($rango['fin'])) {
            // Si no hay horario específico, intentamos el texto o devolvemos vacío
            json_response(['success' => true, 'data' => []]);
        }

        $inicio_laboral = $rango['inicio'];
        $fin_laboral = $rango['fin'];

        // Citas ya agendadas
        $stmt = $pdo->prepare("SELECT TIME_FORMAT(inicio, '%H:%i') as hora FROM citas WHERE sucursal_id = ? AND DATE(inicio) = ? AND estado != 'cancelada'");
        $stmt->execute([$sede_id, $fecha]);
        $ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generar slots
        $stmt_srv = $pdo->prepare("SELECT duracion_minutos FROM servicios WHERE id = ?");
        $stmt_srv->execute([$servicio_id]);
        $duracion = (int) $stmt_srv->fetchColumn() ?: 30;

        $slots = [];
        $current = strtotime("$fecha $inicio_laboral");
        $end = strtotime("$fecha $fin_laboral");

        while ($current < $end) {
            $h = date('H:i', $current);
            $is_ocupado = in_array($h, $ocupados);
            $slots[] = ['hora' => $h, 'disponible' => !$is_ocupado];
            $current += ($duracion * 60);
        }

        json_response(['success' => true, 'data' => $slots]);
        break;

    case 'save_cita':
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $sede_id = (int) ($_POST['sede_id'] ?? 0);
        $servicio_id = (int) ($_POST['servicio_id'] ?? 0);
        $empleado_id = (int) ($_POST['empleado_id'] ?? 0); // ID de la tabla equipo
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $notas = trim($_POST['notas'] ?? '');

        if (!$sede_id || !$servicio_id || !$fecha || !$hora || !$nombre) {
            json_response(['success' => false, 'message' => 'Información incompleta'], 400);
        }

        $inicio = "$fecha $hora:00";
        $stmt_srv = $pdo->prepare("SELECT duracion_minutos FROM servicios WHERE id = ?");
        $stmt_srv->execute([$servicio_id]);
        $duracion = (int) $stmt_srv->fetchColumn() ?: 30;
        $fin = date('Y-m-d H:i:s', strtotime($inicio) + ($duracion * 60));

        try {
            // Buscamos si el nombre del equipo coincide con algún usuario empleado para asignar el empleado_usuario_id
            $stmt_eq = $pdo->prepare("SELECT nombre FROM equipo WHERE id = ?");
            $stmt_eq->execute([$empleado_id]);
            $nombre_empleado = $stmt_eq->fetchColumn();

            $empleado_usuario_id = null;
            if ($nombre_empleado) {
                $stmt_u = $pdo->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND nombre = ? AND rol IN ('admin','gerente','empleado') LIMIT 1");
                $stmt_u->execute([$empresa_id, $nombre_empleado]);
                $empleado_usuario_id = $stmt_u->fetchColumn() ?: null;
            }

            $stmt = $pdo->prepare("INSERT INTO citas (empresa_id, sucursal_id, servicio_id, empleado_usuario_id, cliente_nombre, cliente_telefono, cliente_email, inicio, fin, estado, notas) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$empresa_id, $sede_id, $servicio_id, $empleado_usuario_id, $nombre, $telefono, $email, $inicio, $fin, 'pendiente', $notas]);

            json_response(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
