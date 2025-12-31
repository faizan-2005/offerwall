<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../src/helpers.php';

$db = get_db();
$uri = $_SERVER['REQUEST_URI'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (preg_match('#/offers/?$#', $uri)) {
        // list offers
        $stmt = $db->query('SELECT id,title,slug,category,reward,is_active FROM offers WHERE is_active = 1 ORDER BY created_at DESC');
        $offers = $stmt->fetchAll();
        json_response(['offers'=>$offers]);
    }
    if (preg_match('#/offers/featured#', $uri)) {
        $stmt = $db->query('SELECT id,title,reward,category FROM offers WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4');
        json_response(['offers'=>$stmt->fetchAll()]);
    }
    if (preg_match('#/offers/(\d+)#', $uri, $m)) {
        $id = (int)$m[1];
        $stmt = $db->prepare('SELECT * FROM offers WHERE id = ?'); $stmt->execute([$id]);
        $offer = $stmt->fetch(); if (!$offer) json_response(['error'=>'Not found'],404);
        json_response(['offer'=>$offer]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/offers/start#', $uri)) {
    $data = require_json();
    $offer_id = $data['offer_id'] ?? null;
    if (!$offer_id) json_response(['error'=>'Missing offer_id'],400);
    $user = null; try { $user = auth_guard(); } catch(Exception $e) { json_response(['error'=>'Auth required'],401); }
    // create click record
    $click_id = bin2hex(random_bytes(16));
    $stmt = $db->prepare('INSERT INTO offer_clicks (offer_id,user_id,click_id,status,meta) VALUES (?,?,?,?,?)');
    $meta = json_encode(['ip'=>current_ip(),'ua'=>$_SERVER['HTTP_USER_AGENT']??null]);
    $stmt->execute([$offer_id,$user['user_id'],$click_id,'started',$meta]);
    json_response(['click_id'=>$click_id]);
}

json_response(['error'=>'Not found'],404);
