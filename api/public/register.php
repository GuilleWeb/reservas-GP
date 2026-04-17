<?php
/**
 * API Público para registro de nuevos usuarios (administradores de empresa)
 * POST /api/public/register.php
 * 
 * Parámetros:
 * - nombre_completo: string (requerido)
 * - email: string (requerido, único)
 * - password: string (requerido, mínimo 6 caracteres)
 * - nombre_empresa: string (requerido, único, solo letras y números)
 */

require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Validar bandera global de registro.
$allow_register = get_global_setting('allow_register', 1);
if ((string) $allow_register === '0' || (int) $allow_register === 0) {
    json_response(['success' => false, 'message' => 'El registro público está deshabilitado temporalmente.'], 200);
}

// Obtener y validar datos
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$email_input = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$nombre_empresa = trim($_POST['nombre_empresa'] ?? '');

// Validaciones básicas
if ($nombre_completo === '' || $email_input === '' || $password === '' || $nombre_empresa === '') {
    json_response(['success' => false, 'message' => 'Todos los campos son obligatorios.'], 200);
}

$email = normalize_email_identity($email_input);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'El correo electrónico no es válido.'], 200);
}
if (!is_trusted_email_domain($email)) {
    json_response(['success' => false, 'message' => 'Por seguridad, usa un proveedor de correo confiable (ej: Gmail, Outlook, Yahoo, iCloud).'], 200);
}

// Rate-limit: 1 intento de registro cada 5 minutos por correo+IP.
$register_guard_id = $email . '|' . (client_ip() ?: 'no-ip');
if (request_guard_is_limited('public_register', $register_guard_id, 300, 1, true)) {
    json_response(['success' => false, 'message' => 'Ya recibimos un intento reciente. Espera 5 minutos antes de reenviar.'], 200);
}

if (strlen($password) < 6) {
    json_response(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'], 200);
}

// Validar nombre de empresa: solo letras, números y espacios (mínimo 2 caracteres)
if (!preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚüÜ\s]{2,100}$/', $nombre_empresa)) {
    json_response(['success' => false, 'message' => 'El nombre de empresa solo puede contener letras, números y espacios (mínimo 2 caracteres).'], 200);
}

try {
    ensure_users_email_verified_column();

    // Verificar que el email no exista (incluye alias de Gmail).
    $email_exists = false;
    if (str_ends_with($email, '@gmail.com')) {
        $q = $pdo->prepare("SELECT email FROM usuarios WHERE LOWER(email) LIKE '%@gmail.com' OR LOWER(email) LIKE '%@googlemail.com' LIMIT 500");
        $q->execute();
        $rows = $q->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($rows as $existingEmail) {
            if (normalize_email_identity((string) $existingEmail) === $email) {
                $email_exists = true;
                break;
            }
        }
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        $email_exists = (bool) $stmt->fetchColumn();
    }
    if ($email_exists) {
        json_response(['success' => false, 'message' => 'El correo electrónico ya está registrado.'], 200);
    }

    // Verificar que el nombre de empresa no exista (case insensitive)
    $stmt = $pdo->prepare('SELECT id FROM empresas WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
    $stmt->execute([$nombre_empresa]);
    if ($stmt->fetchColumn()) {
        json_response(['success' => false, 'message' => 'El nombre de empresa ya está registrado.'], 200);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Generar slug único para la empresa
    $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nombre_empresa), '-'));
    if (empty($baseSlug)) {
        $baseSlug = 'empresa';
    }
    $baseSlug = substr($baseSlug, 0, 75);
    
    $slug = $baseSlug;
    $counter = 1;
    $maxAttempts = 999;
    
    while ($counter <= $maxAttempts) {
        $stmt = $pdo->prepare('SELECT id FROM empresas WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if (!$stmt->fetchColumn()) {
            break;
        }
        $suffix = str_pad($counter, 3, '0', STR_PAD_LEFT);
        $slug = substr($baseSlug, 0, 71) . '-' . $suffix;
        $counter++;
    }
    
    if ($counter > $maxAttempts) {
        throw new Exception('No se pudo generar un identificador único para la empresa.');
    }

    // Validar formato final del slug
    if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,78}[a-z0-9]$/', $slug)) {
        throw new Exception('Error al generar el identificador de empresa.');
    }

    // Insertar empresa (plan_id = 1 por defecto)
    $stmt = $pdo->prepare('INSERT INTO empresas (plan_id, slug, nombre, slogan, descripcion, logo_path, portada_path, colores_json, redes_json, config_json, activo, created_at, updated_at) VALUES (?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, ?, 1, NOW(), NOW())');
    $stmt->execute([1, $slug, $nombre_empresa, '{}']);
    $empresa_id = (int) $pdo->lastInsertId();

    // Crear carpeta de la empresa (hash base64 del ID)
    $carpeta_hash = base64_encode($empresa_id);
    $empresa_dir = __DIR__ . '/../../assets/' . $carpeta_hash . '/media/';
    
    if (!is_dir($empresa_dir)) {
        if (!mkdir($empresa_dir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de la empresa.');
        }
    }

    // Insertar usuario admin
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (empresa_id, sucursal_id, rol, nombre, email, password_hash, activo, email_verified_at, created_at, updated_at) VALUES (?, NULL, 'admin', ?, ?, ?, 0, NULL, NOW(), NOW())");
    $stmt->execute([$empresa_id, $nombre_completo, $email, $hash]);
    $usuario_id = (int) $pdo->lastInsertId();

    // Confirmar transacción
    $pdo->commit();

    // Auditar (si la función existe)
    if (function_exists('audit_event')) {
        audit_event('create', 'empresa', $empresa_id, 'Empresa creada vía registro público', $empresa_id, [
            'slug' => $slug,
            'nombre' => $nombre_empresa,
            'plan_id' => 1,
            'admin_id' => $usuario_id,
            'admin_email' => $email
        ]);
    }

    // Enviar verificación de correo (sin romper registro si falla envío).
    $verify_token = create_email_verification_token($usuario_id, 1440);
    if ($verify_token) {
        $verify_url = url_add_query(
            app_url_absolute(view_url('vistas/public/login.php', $slug)),
            ['verify' => '1', 'token' => $verify_token]
        );
        send_email_verification_email([
            'id' => $usuario_id,
            'nombre' => $nombre_completo,
            'email' => $email,
            'empresa_id' => $empresa_id,
            'empresa_nombre' => $nombre_empresa,
        ], $verify_url);
    }

    // Respuesta exitosa
    $successMessage = 'Registro completado exitosamente. Hemos enviado un correo de verificación a tu email. Tu cuenta y empresa permanecerán inactivas hasta que verifiques el correo. Tienes hasta 72 horas para verificarlo, de lo contrario el registro se eliminará automáticamente.';
    json_response([
        'success' => true,
        'message' => $successMessage,
        'data' => [
            'empresa_id' => $empresa_id,
            'empresa_slug' => $slug,
            'empresa_nombre' => $nombre_empresa,
            'usuario_id' => $usuario_id,
            'redirect_url' => view_url('vistas/public/login.php', $slug)
        ]
    ], 200);

} catch (Throwable $e) {
    // Revertir transacción si está activa
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $isDev = (($_ENV['APP_ENV'] ?? '') === 'development') || (($_SERVER['HTTP_HOST'] ?? '') === 'localhost');
    $errorMsg = $isDev ? ('Error: ' . $e->getMessage()) : 'Error al procesar el registro. Por favor intente nuevamente.';

    error_log('Error en registro público: ' . $e->getMessage());
    json_response(['success' => false, 'message' => $errorMsg], 200);
}
