<?php
// tools/import_aiven.php
// Importa un dump SQL en la base de datos configurada en conexion.php

require_once __DIR__ . '/../conexion.php';

$file = $argv[1] ?? __DIR__ . '/../citas_gp.sql';
if (!is_file($file)) {
    echo "Archivo no encontrado: {$file}\n";
    exit(1);
}

$sql = file_get_contents($file);
if ($sql === false) {
    echo "No se pudo leer el archivo.\n";
    exit(1);
}

// Normalizar saltos de línea
$sql = str_replace("\r\n", "\n", $sql);
$sql = str_replace("\r", "\n", $sql);

// Eliminar comentarios tipo -- y /* */
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$sql = preg_replace('/\/\*![\s\S]*?\*\//', '', $sql);
$sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);

// Eliminar SET/COMMIT/START TRANSACTION para evitar errores en drivers externos
$sql = preg_replace('/^\s*SET\s+.+?;\s*$/mi', '', $sql);
$sql = preg_replace('/^\s*START\s+TRANSACTION\s*;\s*$/mi', '', $sql);
$sql = preg_replace('/^\s*COMMIT\s*;\s*$/mi', '', $sql);

// Split seguro por ; (no cortar dentro de comillas)
$statements = [];
$buf = '';
$inSingle = false;
$inDouble = false;
$inBacktick = false;
$len = strlen($sql);
for ($i = 0; $i < $len; $i++) {
    $ch = $sql[$i];
    $prev = $i > 0 ? $sql[$i - 1] : '';
    if ($ch === "'" && !$inDouble && !$inBacktick && $prev !== '\\') {
        $inSingle = !$inSingle;
    } elseif ($ch === '"' && !$inSingle && !$inBacktick && $prev !== '\\') {
        $inDouble = !$inDouble;
    } elseif ($ch === '`' && !$inSingle && !$inDouble) {
        $inBacktick = !$inBacktick;
    }
    if ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
        $stmt = trim($buf);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
        $buf = '';
        continue;
    }
    $buf .= $ch;
}
$tail = trim($buf);
if ($tail !== '') {
    $statements[] = $tail;
}

echo "Total de sentencias: " . count($statements) . "\n";

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($statements as $idx => $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        $pdo->exec($stmt);
        if (($idx + 1) % 200 === 0) {
            echo "Ejecutadas " . ($idx + 1) . "...\n";
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Importación completada.\n";
} catch (Throwable $e) {
    echo "Error en importación: " . $e->getMessage() . "\n";
    exit(1);
}
