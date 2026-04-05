<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if (!$user || (($user['rol'] ?? null) !== 'superadmin'))
    json_response(['error' => 'unauthorized'], 403);
ensure_suscripciones_table();
ensure_suscripciones_historial_table();

function sync_empresa_subscription($empresa_id, $plan_id)
{
    global $pdo;
    $empresa_id = (int) $empresa_id;
    $plan_id = (int) $plan_id;
    if ($empresa_id <= 0 || $plan_id <= 0) {
        return;
    }
    $stmt = $pdo->prepare("SELECT id FROM suscripciones WHERE empresa_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$empresa_id]);
    $sid = (int) ($stmt->fetchColumn() ?: 0);
    if ($sid > 0) {
        $prevQ = $pdo->prepare("SELECT * FROM suscripciones WHERE id = ? LIMIT 1");
        $prevQ->execute([$sid]);
        $prev = $prevQ->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($prev) {
            suscripcion_history_append($prev, 'sync_empresa_plan', (int) ($GLOBALS['user']['id'] ?? 0));
        }
        $u = $pdo->prepare("UPDATE suscripciones
                            SET plan_id = ?, estado = 'activa',
                                fecha_inicio = COALESCE(fecha_inicio, CURDATE()),
                                plazo = COALESCE(plazo, 'mensual'),
                                fecha_fin = COALESCE(fecha_fin, DATE_ADD(COALESCE(fecha_inicio, CURDATE()), INTERVAL 1 MONTH))
                            WHERE id = ?");
        $u->execute([$plan_id, $sid]);
        $curQ = $pdo->prepare("SELECT * FROM suscripciones WHERE id = ? LIMIT 1");
        $curQ->execute([$sid]);
        $cur = $curQ->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($cur) {
            suscripcion_history_append($cur, 'sync_empresa_plan_after', (int) ($GLOBALS['user']['id'] ?? 0));
        }
        return;
    }
    $i = $pdo->prepare("INSERT INTO suscripciones (empresa_id, plan_id, estado, fecha_inicio, fecha_fin, plazo)
                        VALUES (?,?, 'activa', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'mensual')");
    $i->execute([$empresa_id, $plan_id]);
    $newId = (int) $pdo->lastInsertId();
    $curQ = $pdo->prepare("SELECT * FROM suscripciones WHERE id = ? LIMIT 1");
    $curQ->execute([$newId]);
    $cur = $curQ->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($cur) {
        suscripcion_history_append($cur, 'sync_empresa_create', (int) ($GLOBALS['user']['id'] ?? 0));
    }
}

function parse_json_or_keep($incoming, $current)
{
    $incoming = trim((string) $incoming);
    if ($incoming === '') {
        return $current;
    }
    json_decode($incoming, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $current;
    }
    return $incoming;
}

switch ($action) {
    case 'list':
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(1, intval($_GET['per'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $activo = $_GET['activo'] ?? '';
        $plan_id = intval($_GET['plan_id'] ?? 0);
        $sort = trim($_GET['sort'] ?? 'id');
        $dir = strtolower(trim($_GET['dir'] ?? 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $sortMap = [
            'id' => 'e.id',
            'nombre' => 'e.nombre',
            'slug' => 'e.slug',
            'plan' => 'p.nombre',
            'activo' => 'e.activo',
            'created_at' => 'e.created_at',
        ];
        $orderBy = $sortMap[$sort] ?? 'e.id';

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(e.slug LIKE :s OR e.nombre LIKE :s)';
            $params[':s'] = "%$search%";
        }
        if ($activo !== '' && ($activo === '0' || $activo === '1')) {
            $where[] = 'e.activo = :a';
            $params[':a'] = (int) $activo;
        }
        if ($plan_id > 0) {
            $where[] = 'e.plan_id = :pid';
            $params[':pid'] = $plan_id;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countWhere = [];
        $countParams = [];
        if ($search !== '') {
            $countWhere[] = '(slug LIKE :s OR nombre LIKE :s)';
            $countParams[':s'] = "%$search%";
        }
        if ($activo !== '' && ($activo === '0' || $activo === '1')) {
            $countWhere[] = 'activo = :a';
            $countParams[':a'] = (int) $activo;
        }
        if ($plan_id > 0) {
            $countWhere[] = 'plan_id = :pid';
            $countParams[':pid'] = $plan_id;
        }
        $countWhereSql = $countWhere ? ('WHERE ' . implode(' AND ', $countWhere)) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas $countWhereSql");
        foreach ($countParams as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();
        $total_pages = (int) ceil($total / $per);

        $offset = ($page - 1) * $per;
        $sql = "SELECT e.id, e.plan_id, e.slug, e.nombre, e.slogan, e.activo, e.created_at,
                       p.nombre AS plan_nombre
                FROM empresas e
                LEFT JOIN planes p ON p.id = e.plan_id
                $whereSql
                ORDER BY $orderBy $dir
                LIMIT :o,:p";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':p', $per, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $data, 'total' => $total, 'total_pages' => $total_pages]);
        break;

    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id=?');
        $stmt->execute([$id]);
        json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
        break;

    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $id = intval($_POST['id'] ?? 0);
        $plan_id = $_POST['plan_id'] ?? null;
        $plan_id = ($plan_id === '' || $plan_id === null) ? null : intval($plan_id);
        $slug = trim($_POST['slug'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $slogan = trim($_POST['slogan'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $logo_path = trim($_POST['logo_path'] ?? '');
        $portada_path = trim($_POST['portada_path'] ?? '');
        $colores_json = trim($_POST['colores_json'] ?? '');
        $redes_json = trim($_POST['redes_json'] ?? '');
        $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;

        if ($nombre === '')
            json_response(['success' => false, 'message' => 'Nombre obligatorio.'], 200);

        // Si no viene slug desde el formulario, reusar el existente (edición) o generar uno (alta).
        if ($slug === '' && $id > 0) {
            $stmt = $pdo->prepare('SELECT slug FROM empresas WHERE id=? LIMIT 1');
            $stmt->execute([$id]);
            $slug = (string) ($stmt->fetchColumn() ?: '');
        }
        if ($slug === '') {
            $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nombre), '-'));
            if ($baseSlug === '')
                $baseSlug = 'empresa';
            $baseSlug = substr($baseSlug, 0, 75);
            $slug = $baseSlug;
            $counter = 1;
            while (true) {
                $stmt = $pdo->prepare('SELECT id FROM empresas WHERE slug=?' . ($id > 0 ? ' AND id<>?' : '') . ' LIMIT 1');
                $bind = [$slug];
                if ($id > 0)
                    $bind[] = $id;
                $stmt->execute($bind);
                if (!$stmt->fetchColumn())
                    break;
                $suffix = str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
                $slug = substr($baseSlug, 0, 71) . '-' . $suffix;
                $counter++;
                if ($counter > 999)
                    json_response(['success' => false, 'message' => 'No se pudo generar un slug único.'], 200);
            }
        }
        if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,78}[a-z0-9]$/', $slug)) {
            json_response(['success' => false, 'message' => 'Slug inválido. Use minúsculas, números y guiones.'], 200);
        }

        $current = null;
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT slug, slogan, descripcion, logo_path, portada_path, colores_json, redes_json FROM empresas WHERE id=? LIMIT 1');
            $stmt->execute([$id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$current) {
                json_response(['success' => false, 'message' => 'Empresa no encontrada.'], 200);
            }
        }

        if ($id > 0) {
            if ($slogan === '' && !array_key_exists('slogan', $_POST))
                $slogan = (string) ($current['slogan'] ?? '');
            if ($descripcion === '' && !array_key_exists('descripcion', $_POST))
                $descripcion = (string) ($current['descripcion'] ?? '');
            if ($logo_path === '' && !array_key_exists('logo_path', $_POST))
                $logo_path = (string) ($current['logo_path'] ?? '');
            if ($portada_path === '' && !array_key_exists('portada_path', $_POST))
                $portada_path = (string) ($current['portada_path'] ?? '');
            $colores_json = parse_json_or_keep(array_key_exists('colores_json', $_POST) ? (string) $_POST['colores_json'] : '', $current['colores_json'] ?? null);
            $redes_json = parse_json_or_keep(array_key_exists('redes_json', $_POST) ? (string) $_POST['redes_json'] : '', $current['redes_json'] ?? null);
        } else {
            $colores_json = $colores_json === '' ? null : $colores_json;
            $redes_json = $redes_json === '' ? null : $redes_json;
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE empresas SET plan_id=?, slug=?, nombre=?, slogan=?, descripcion=?, logo_path=?, portada_path=?, colores_json=?, redes_json=?, activo=? WHERE id=?');
                $stmt->execute([
                    $plan_id,
                    $slug,
                    $nombre,
                    $slogan !== '' ? $slogan : null,
                    $descripcion !== '' ? $descripcion : null,
                    $logo_path !== '' ? $logo_path : null,
                    $portada_path !== '' ? $portada_path : null,
                    $colores_json,
                    $redes_json,
                    $activo ? 1 : 0,
                    $id
                ]);
                if ($plan_id !== null && $plan_id > 0) {
                    sync_empresa_subscription($id, (int) $plan_id);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO empresas (plan_id, slug, nombre, slogan, descripcion, logo_path, portada_path, colores_json, redes_json, activo) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$plan_id, $slug, $nombre, $slogan ?: null, $descripcion ?: null, $logo_path ?: null, $portada_path ?: null, $colores_json, $redes_json, $activo ? 1 : 0]);
                $id = (int) $pdo->lastInsertId();
                if ($plan_id !== null && $plan_id > 0) {
                    sync_empresa_subscription($id, (int) $plan_id);
                }
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'id' => $id]);
        break;

    case 'create_empresa':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $plan_id = $_POST['plan_id'] ?? null;
        $plan_id = ($plan_id === '' || $plan_id === null) ? null : intval($plan_id);
        $nombre = trim($_POST['nombre'] ?? '');
        $admin_nombre = trim($_POST['admin_nombre'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');

        // Validar campos obligatorios (slug ya no es necesario del frontend)
        if ($nombre === '' || $admin_nombre === '' || $admin_email === '') {
            json_response(['success' => false, 'message' => 'Nombre de empresa y datos del admin son obligatorios.'], 200);
        }

        try {
            $pdo->beginTransaction();

            // Generar slug base a partir del nombre
            $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nombre), '-'));

            // Si el slug base está vacío (nombres con solo caracteres especiales), usar uno genérico
            if (empty($baseSlug)) {
                $baseSlug = 'empresa';
            }

            // Limitar longitud del slug base para dar espacio al sufijo
            $baseSlug = substr($baseSlug, 0, 75); // Máx 75 + 3 dígitos + 1 guión = 79 (límite seguro)

            $slug = $baseSlug;
            $counter = 1;
            $maxAttempts = 999;

            // Buscar un slug único
            while ($counter <= $maxAttempts) {
                $stmt = $pdo->prepare('SELECT id FROM empresas WHERE slug=? LIMIT 1');
                $stmt->execute([$slug]);

                if (!$stmt->fetchColumn()) {
                    // Slug disponible
                    break;
                }

                // Generar nuevo slug con sufijo de 3 dígitos
                $suffix = str_pad($counter, 3, '0', STR_PAD_LEFT);
                $slug = substr($baseSlug, 0, 75 - 4) . '-' . $suffix; // -4 por el guión y 3 dígitos
                $counter++;
            }

            if ($counter > $maxAttempts) {
                throw new Exception('No se pudo generar un slug único después de ' . $maxAttempts . ' intentos');
            }

            // Validar que el slug cumple con el formato requerido (aunque lo generamos nosotros)
            if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,78}[a-z0-9]$/', $slug)) {
                throw new Exception('Error al generar el slug automático. Contacte al administrador.');
            }

            // Verificar plan si se proporcionó
            if ($plan_id !== null) {
                $stmt = $pdo->prepare('SELECT id FROM planes WHERE id=? LIMIT 1');
                $stmt->execute([$plan_id]);
                if (!$stmt->fetchColumn()) {
                    throw new Exception('Plan inválido.');
                }
            }

            // Insertar empresa (activo lo tomamos del POST si viene, sino 1 por defecto)
            $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
            $stmt = $pdo->prepare('INSERT INTO empresas (plan_id, slug, nombre, activo) VALUES (?,?,?,?)');
            $stmt->execute([$plan_id, $slug, $nombre, $activo]);
            $empresa_id = (int) $pdo->lastInsertId();
            if ($plan_id !== null && $plan_id > 0) {
                sync_empresa_subscription($empresa_id, (int) $plan_id);
            }

            // Generar contraseña temporal
            $temp_password = bin2hex(random_bytes(4)); // 8 caracteres
            $hash = password_hash($temp_password, PASSWORD_BCRYPT);

            // Insertar admin
            $stmt = $pdo->prepare("INSERT INTO usuarios (empresa_id, sucursal_id, rol, nombre, email, password_hash, activo) VALUES (?, NULL, 'admin', ?, ?, ?, 1)");
            $stmt->execute([$empresa_id, $admin_nombre, $admin_email, $hash]);
            $admin_id = (int) $pdo->lastInsertId();

            // Auditar
            audit_event('create', 'empresa', $empresa_id, 'Empresa creada', $empresa_id, [
                'slug' => $slug,
                'nombre' => $nombre,
                'plan_id' => $plan_id,
                'admin_id' => $admin_id
            ]);

            $pdo->commit();

            // Respuesta incluyendo la contraseña temporal
            json_response([
                'success' => true,
                'empresa_id' => $empresa_id,
                'id_e' => $slug,
                'empresa_nombre' => $nombre,
                'admin_id' => $admin_id,
                'admin_email' => $admin_email,
                'admin_nombre' => $admin_nombre,
                'temp_password' => $temp_password, // ← Contraseña generada
                'message' => 'Empresa creada exitosamente. Contraseña temporal: ' . $temp_password
            ]);

        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0)
            json_response(['success' => false], 200);

        $stmt = $pdo->prepare('SELECT id, slug, nombre FROM empresas WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            audit_event('delete', 'empresa', (int) $row['id'], 'Empresa eliminada', (int) $row['id'], ['slug' => $row['slug'], 'nombre' => $row['nombre']]);
        }
        $stmt = $pdo->prepare('DELETE FROM empresas WHERE id=?');
        $stmt->execute([$id]);
        json_response(['success' => true]);
        break;

    case 'create_admin':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
            json_response(['error' => 'invalid_method'], 405);

        $empresa_id = intval($_POST['empresa_id'] ?? 0);
        $admin_nombre = trim($_POST['admin_nombre'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($empresa_id <= 0 || $admin_nombre === '' || $admin_email === '' || $password === '') {
            json_response(['success' => false, 'message' => 'Datos incompletos.'], 200);
        }

        $stmt = $pdo->prepare('SELECT id FROM empresas WHERE id=? LIMIT 1');
        $stmt->execute([$empresa_id]);
        if (!$stmt->fetchColumn())
            json_response(['success' => false, 'message' => 'Empresa inválida.'], 200);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (empresa_id, sucursal_id, rol, nombre, email, password_hash, activo) VALUES (?, NULL, 'admin', ?, ?, ?, 1)");
            $stmt->execute([$empresa_id, $admin_nombre, $admin_email, $hash]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        json_response(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
        break;

    default:
        json_response(['error' => 'invalid_action'], 400);
}
