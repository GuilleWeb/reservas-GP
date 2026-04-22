<?php
require_once __DIR__ . '/helpers.php';

$user = current_user();
$role = $user['rol'] ?? null;

if (!$user || $role !== 'superadmin') {
    http_response_code(403);
    echo 'No autorizado.';
    exit;
}

function migrations_ensure_table(): void
{
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id VARCHAR(190) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
}

function migrations_is_applied(string $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT 1 FROM migrations WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function migrations_mark_applied(string $id): void
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO migrations (id) VALUES (?)');
    $stmt->execute([$id]);
}

function db_table_exists(string $table): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function db_column_exists(string $table, string $column): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function migrate_2026_04_22_clientes_v2(): array
{
    global $pdo;

    // Detectar si ya está en el nuevo modelo
    if (db_table_exists('clientes') && db_column_exists('clientes', 'empresa_id') && !db_table_exists('cliente_empresas')) {
        return ['changed' => false, 'message' => 'Clientes v2 ya estaba aplicado (no existe cliente_empresas y clientes ya tiene empresa_id).'];
    }

    if (!db_table_exists('clientes')) {
        throw new RuntimeException('No existe la tabla clientes.');
    }
    if (!db_table_exists('cliente_empresas')) {
        throw new RuntimeException('No existe cliente_empresas (necesario para migrar desde el modelo anterior).');
    }

    // 1) Renombrar tablas anteriores a modo respaldo
    if (!db_table_exists('clientes_global')) {
        $pdo->exec('RENAME TABLE clientes TO clientes_global');
    }
    if (!db_table_exists('cliente_empresas_old')) {
        $pdo->exec('RENAME TABLE cliente_empresas TO cliente_empresas_old');
    }

    // 2) Crear nueva tabla clientes
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        empresa_id BIGINT UNSIGNED NOT NULL,
        sucursal_id BIGINT UNSIGNED NULL,
        nombre VARCHAR(150) NOT NULL,
        email VARCHAR(150) NULL,
        telefono VARCHAR(40) NULL,
        direccion VARCHAR(255) NULL,
        fecha_nacimiento DATE NULL,
        notas TEXT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        legacy_cliente_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_clientes_empresa_email (empresa_id, email),
        KEY idx_clientes_empresa (empresa_id),
        KEY idx_clientes_sucursal (sucursal_id),
        KEY idx_clientes_email (email),
        KEY idx_clientes_telefono (telefono),
        KEY idx_clientes_legacy (legacy_cliente_id),
        CONSTRAINT fk_clientes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_clientes_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
            ON UPDATE CASCADE ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3) Poblar nueva tabla duplicando por empresa
    $pdo->exec("INSERT INTO clientes (empresa_id, sucursal_id, nombre, email, telefono, direccion, fecha_nacimiento, notas, activo, legacy_cliente_id, created_at, updated_at)
        SELECT
            ce.empresa_id,
            NULL,
            cg.nombre,
            cg.email,
            cg.telefono,
            NULL,
            cg.fecha_nacimiento,
            cg.notas,
            cg.activo,
            cg.id,
            cg.created_at,
            cg.updated_at
        FROM cliente_empresas_old ce
        JOIN clientes_global cg ON cg.id = ce.cliente_id");

    // 4) Remapear citas.cliente_id -> nuevo clientes.id
    //    (empresa_id + legacy_cliente_id = old cliente_id)
    if (db_column_exists('citas', 'cliente_id')) {
        $pdo->exec("UPDATE citas c
            JOIN clientes cl ON cl.empresa_id = c.empresa_id AND cl.legacy_cliente_id = c.cliente_id
            SET c.cliente_id = cl.id
            WHERE c.cliente_id IS NOT NULL");
    }

    return ['changed' => true, 'message' => 'Clientes v2 aplicado: nueva tabla clientes por empresa + remapeo de citas.cliente_id.'];
}

migrations_ensure_table();

$migrations = [
    '2026_04_22_clientes_v2' => [
        'title' => 'Clientes v2 (por empresa, email único por empresa, dirección, remapeo de citas) ',
        'fn' => 'migrate_2026_04_22_clientes_v2',
    ],
];

$results = [];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        foreach ($migrations as $id => $m) {
            if (migrations_is_applied($id)) {
                $results[] = ['id' => $id, 'status' => 'skipped', 'message' => 'Ya aplicado.'];
                continue;
            }

            // Importante: muchas migraciones usan DDL (CREATE/ALTER/RENAME) y MySQL hace COMMIT implícito.
            // Por eso evitamos envolver todo en una transacción global para no provocar
            // "There is no active transaction" al hacer commit/rollback.
            $r = call_user_func($m['fn']);
            migrations_mark_applied($id);

            $results[] = ['id' => $id, 'status' => 'applied', 'message' => (string) ($r['message'] ?? 'Aplicado.')];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Migraciones</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:#0b1220; color:#e5e7eb; margin:0; padding:24px;}
        .card{max-width:960px; margin:0 auto; background:#0f172a; border:1px solid #1f2937; border-radius:16px; padding:18px;}
        h1{margin:0 0 10px; font-size:18px;}
        .muted{color:#94a3b8; font-size:13px;}
        table{width:100%; border-collapse:collapse; margin-top:14px;}
        th,td{border-top:1px solid #1f2937; padding:10px; text-align:left; font-size:13px;}
        .btn{display:inline-block; background:#14b8a6; color:#031b1a; font-weight:700; border:none; padding:10px 14px; border-radius:12px; cursor:pointer;}
        .err{background:#450a0a; border:1px solid #7f1d1d; padding:10px; border-radius:12px; margin-top:12px; color:#fecaca;}
        .ok{background:#052e2b; border:1px solid #134e4a; padding:10px; border-radius:12px; margin-top:12px; color:#99f6e4;}
    </style>
</head>
<body>
    <div class="card">
        <h1>Migraciones</h1>
        <div class="muted">Ejecuta migraciones pendientes. Solo Superadmin. Se registran en la tabla <code>migrations</code> para no repetirse.</div>

        <form method="post" style="margin-top:12px;">
            <button class="btn" type="submit">Aplicar migraciones pendientes</button>
        </form>

        <?php if ($error !== ''): ?>
            <div class="err">Error: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <div class="ok">Proceso finalizado.</div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($migrations as $id => $m): ?>
                    <?php
                        $applied = migrations_is_applied($id);
                        $row = null;
                        foreach ($results as $rr) { if (($rr['id'] ?? '') === $id) { $row = $rr; break; } }
                        $status = $row['status'] ?? ($applied ? 'applied' : 'pending');
                        $detail = $row['message'] ?? ($applied ? 'Ya aplicado.' : 'Pendiente.');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($id) ?></td>
                        <td><?= htmlspecialchars((string) ($m['title'] ?? $id)) ?></td>
                        <td><?= htmlspecialchars($status) ?></td>
                        <td><?= htmlspecialchars($detail) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="muted" style="margin-top:12px;">
            Nota: esta migración renombra tablas anteriores como <code>clientes_global</code> y <code>cliente_empresas_old</code> como respaldo.
        </div>
    </div>
</body>
</html>
