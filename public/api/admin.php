<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../src/helpers.php';

$db = get_db();
$uri = $_SERVER['REQUEST_URI'];

try { $user = auth_guard(); } catch(Exception $e) { json_response(['error'=>'Auth required'],401); }
if (!is_admin_user($user)) json_response(['error'=>'Admin only'],403);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/admin/stats#',$uri)){
    $totalUsers = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $pendingPostbacks = $db->query("SELECT COUNT(*) FROM offer_clicks WHERE status='pending'")->fetchColumn();
    $pendingWithdraws = $db->query("SELECT COUNT(*) FROM withdrawals WHERE status='requested'")->fetchColumn();
    json_response(['total_users'=>$totalUsers,'pending_postbacks'=>$pendingPostbacks,'pending_withdrawals'=>$pendingWithdraws]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/admin/offers#',$uri)){
    $data = require_json();
    $stmt = $db->prepare('INSERT INTO offers (title,slug,description,steps,category,reward,is_active) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$data['title'],$data['slug'],$data['description'],json_encode($data['steps'] ?? []),$data['category'],$data['reward'],$data['is_active']?1:0]);
    json_response(['ok'=>true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/admin/withdraws/([0-9]+)/approve#',$uri,$m)){
    $wid = intval($m[1]);
    atomic_transaction(function($db) use($wid,$user){
        $db->prepare('UPDATE withdrawals SET status = "paid", admin_id = ? WHERE id = ?')->execute([$user['user_id'],$wid]);
    });
    json_response(['ok'=>true]);
}

json_response(['error'=>'Not found'],404);
