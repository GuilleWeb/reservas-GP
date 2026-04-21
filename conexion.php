<?php
// conexion.php
$DB_HOST = getenv('DB_HOST') ?: 'mysql-104e8dee-guille-3162.b.aivencloud.com';
$DB_NAME = getenv('DB_NAME') ?: 'defaultdb';
$DB_USER = getenv('DB_USER') ?: 'avnadmin';
$DB_PASS = getenv('DB_PASS') ?: 'AVNS_3Bpb30UgOgzFjYKs8yK';
//AVNS_3Bpb30UgOgzFjYKs8yK
$DB_PORT = (int) (getenv('DB_PORT') ?: 25474);
$DB_SSL_CA = getenv('DB_SSL_CA') ?: (__DIR__ . '/ca.pem');

$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
if ($DB_SSL_CA && file_exists($DB_SSL_CA)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $DB_SSL_CA;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}
