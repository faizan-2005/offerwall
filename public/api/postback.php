<?php
// External systems call this endpoint to postback offer completions
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../src/helpers.php';

$db = get_db();
$data = json_decode(file_get_contents('php://input'), true);
// log raw payload
$stmt = $db->prepare('INSERT INTO postbacks_log (payload, processed) VALUES (?,0)');
$stmt->execute([json_encode($data)]);

$secret = $data['secret'] ?? null;
if (!$secret || $secret !== get_setting('postback_secret', 'CHANGE_ME')) {
    // mark log with error
    $db->prepare('UPDATE postbacks_log SET error=? WHERE id = ?')->execute(['Invalid secret', $db->lastInsertId()]);
    http_response_code(403); echo 'Forbidden'; exit;
}

$click_id = $data['click_id'] ?? null;
$reward = (float)($data['reward'] ?? 0);
if (!$click_id) { echo 'Missing click_id'; exit; }

// idempotent processing
$stmt = $db->prepare('SELECT * FROM offer_clicks WHERE click_id = ?'); $stmt->execute([$click_id]);
$click = $stmt->fetch();
if (!$click) { echo 'Unknown click'; exit; }
if ($click['status'] === 'approved') { echo 'Already processed'; exit; }

// validate reward matches or accept provided
// process: approve click, credit wallet, log transaction
atomic_transaction(function($db) use($click,$reward,$data){
    // update click status
    $upd = $db->prepare('UPDATE offer_clicks SET status = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute(['approved',$click['id']]);
    // credit wallet
    $stmt = $db->prepare("SELECT SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) as bal FROM wallet_transactions WHERE user_id = ?");
    $stmt->execute([$click['user_id']]); $bal = $stmt->fetchColumn() ?: 0;
    $after = $bal + $reward;
    $ins = $db->prepare('INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,reference,meta) VALUES (?,?,?,?,?,?,?)');
    $ins->execute([$click['user_id'],'credit',$reward,$bal,$after,'offer_'.$click['offer_id'],json_encode($data)]);
    // mark postback processed
    $db->prepare('UPDATE postbacks_log SET processed = 1 WHERE id = ?')->execute([$db->lastInsertId()]);
});

echo 'OK';
