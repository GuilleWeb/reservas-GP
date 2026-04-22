<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

// API pública para obtener planes activos (para landing page)
try {
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion, max_sucursales, max_empleados, max_servicios, max_clientes, precio_mensual, precio, modulos_json FROM planes WHERE activo = 1 ORDER BY precio_mensual ASC");
    $stmt->execute();
    $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normalizar datos para el frontend
    $data = array_map(function($p) {
        $pm = (float) ($p['precio_mensual'] ?? $p['precio'] ?? 0);
        return [
            'id' => (int) $p['id'],
            'nombre' => $p['nombre'],
            'descripcion' => $p['descripcion'],
            'max_sucursales' => (int) $p['max_sucursales'],
            'max_empleados' => (int) $p['max_empleados'],
            'max_servicios' => (int) $p['max_servicios'],
            'max_clientes' => (int) $p['max_clientes'],
            'precio_mensual' => $pm,
            'precio_anual' => round($pm * 12 * 0.85, 2), // 15% descuento anual
            'modulos' => json_decode($p['modulos_json'] ?? '[]', true) ?: [],
        ];
    }, $planes);
    
    json_response(['success' => true, 'data' => $data, 'count' => count($data)]);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Error al cargar planes'], 500);
}
