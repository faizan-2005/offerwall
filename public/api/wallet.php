<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../src/helpers.php';

$db = get_db();
$uri = $_SERVER['REQUEST_URI'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#/wallet$#',$uri)) {
    $u = auth_guard();
    // compute balance: sum credits - debits
    $stmt = $db->prepare("SELECT SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) as bal FROM wallet_transactions WHERE user_id = ?");
    $stmt->execute([$u['user_id']]);
    $bal = $stmt->fetchColumn(); $bal = $bal ?? '0.00';
    $stmt = $db->prepare('SELECT type,amount,reference,created_at FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$u['user_id']]);
    $tx = $stmt->fetchAll();
    json_response(['balance'=>$bal,'transactions'=>$tx]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#/wallet/withdraw#',$uri)) {
    $u = auth_guard();
    $data = require_json();
    $amount = (float)($data['amount'] ?? 0);
    $upi = $data['upi'] ?? null;
    $min = (float)get_setting('min_withdraw','100');
    if (!$upi) json_response(['error'=>'Missing UPI'],400);
    if ($amount < $min) json_response(['error'=>'Below minimum withdraw'],400);
    // check balance
    $stmt = $db->prepare("SELECT SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) as bal FROM wallet_transactions WHERE user_id = ?");
    $stmt->execute([$u['user_id']]); $bal = $stmt->fetchColumn() ?: 0;
    if ($amount > $bal) json_response(['error'=>'Insufficient funds'],400);
    // create withdrawal request and a debit transaction (pending)
    atomic_transaction(function($db) use($u,$amount,$upi){
        $stmt = $db->prepare('INSERT INTO withdrawals (user_id,upi,amount,status) VALUES (?,?,?,"requested")');
        $stmt->execute([$u['user_id'],$upi,$amount]);
        $before = $db->prepare("SELECT SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) as bal FROM wallet_transactions WHERE user_id = ?");
        $before->execute([$u['user_id']]); $b = $before->fetchColumn() ?: 0;
        $after = $b - $amount;
        $ins = $db->prepare('INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,reference) VALUES (?,?,?,?,?,?)');
        $ins->execute([$u['user_id'],'debit',$amount,$b,$after,'withdraw_request']);
    });
    json_response(['status'=>'requested']);
}

json_response(['error'=>'Not found'],404);
