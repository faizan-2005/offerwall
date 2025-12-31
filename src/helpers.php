<?php
require_once __DIR__ . '/init.php';

function create_token(): string {
    return bin2hex(random_bytes(32));
}

function find_user_by_token(string $token) {
    $db = get_db();
    $stmt = $db->prepare('SELECT s.*, u.* FROM sessions s JOIN users u ON s.user_id = u.id WHERE s.token = ? AND u.is_blocked = 0');
    $stmt->execute([$token]);
    $r = $stmt->fetch();
    if (!$r) return null;
    return $r;
}

function auth_guard() {
    $token = bearer_token();
    if (!$token) json_response(['error' => 'Missing token'], 401);
    $user = find_user_by_token($token);
    if (!$user) json_response(['error' => 'Invalid token'], 401);
    // update last_seen
    $db = get_db();
    $stmt = $db->prepare('UPDATE sessions SET last_seen = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);
    return $user;
}

function is_admin_user($user) {
    return isset($user['role']) && $user['role'] === 'admin';
}

function atomic_transaction(callable $fn) {
    $db = get_db();
    try {
        $db->beginTransaction();
        $res = $fn($db);
        $db->commit();
        return $res;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
