<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../src/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $data = require_json();
    if (strpos($_SERVER['REQUEST_URI'],'/login') !== false) {
        // login
        $email = $data['email'] ?? '';
        $pass = $data['password'] ?? '';
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($pass, $u['password_hash'])) json_response(['error'=>'Invalid credentials'], 401);
        if ($u['is_blocked']) json_response(['error'=>'Account blocked'],403);
        // create session
        $token = create_token();
        $ip = current_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $db->prepare('INSERT INTO sessions (user_id, token, ip, user_agent) VALUES (?,?,?,?)');
        $stmt->execute([$u['id'],$token,$ip,$ua]);
        json_response(['token'=>$token,'user'=>['id'=>$u['id'],'email'=>$u['email'],'name'=>$u['name'],'role'=>$u['role']]]);
    }
    if (strpos($_SERVER['REQUEST_URI'],'/register') !== false) {
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $pass = $data['password'] ?? null;
        if (!$email || !$pass) json_response(['error'=>'Missing fields'],400);
        $db = get_db();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?'); $stmt->execute([$email]);
        if ($stmt->fetch()) json_response(['error'=>'Email exists'],409);
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (email,password_hash,name) VALUES (?,?,?)');
        $stmt->execute([$email,$hash,$name]);
        $id = $db->lastInsertId();
        $token = create_token();
        $ip = current_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $db->prepare('INSERT INTO sessions (user_id, token, ip, user_agent) VALUES (?,?,?,?)');
        $stmt->execute([$id,$token,$ip,$ua]);
        json_response(['token'=>$token,'user'=>['id'=>$id,'email'=>$email,'name'=>$name]]);
    }
}

// simple catch for GET /me
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'],'/me') !== false) {
    $token = bearer_token(); if (!$token) json_response(['error'=>'Missing token'],401);
    $s = find_user_by_token($token); if (!$s) json_response(['error'=>'Invalid token'],401);
    json_response(['user'=>['id'=>$s['user_id'],'email'=>$s['email'],'name'=>$s['name'],'role'=>$s['role']]]);
}

json_response(['error'=>'Not found'],404);
