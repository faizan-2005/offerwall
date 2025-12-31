<?php
// init.php - bootstrap DB and common helpers
require_once __DIR__ . '/config.php';

// PDO connection
function get_db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $cfg = DB_CONFIG();
    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function get_setting(string $k, $default = null) {
    $db = get_db();
    $stmt = $db->prepare('SELECT v FROM settings WHERE k = ?');
    $stmt->execute([$k]);
    $r = $stmt->fetchColumn();
    return $r !== false ? $r : $default;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function bearer_token() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/', $h, $m)) return $m[1];
    return null;
}

function require_json() {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) json_response(['error' => 'Invalid JSON'], 400);
    return $data;
}

function current_ip() {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
