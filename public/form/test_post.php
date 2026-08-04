<?php
require_once __DIR__ . '/config.php';

$data = [
    'name' => '神道 花子',
    'furigana' => 'しんとう はなこ',
    'email' => 'hanako@example.com',
    'tel' => '09098765432',
    'pref' => '神奈川県',
    'payment_method' => 'クレジットカード',
    'tour_id' => 'T2026-02',
    'tour_name' => '悟りを開くまで帰れま10（福井・京都・滋賀）',
    'amount' => 135000
];

$payload = json_encode([
    'token' => GAS_SECRET_TOKEN,
    'action' => 'add_application',
    'data' => $data
]);

$ch = curl_init(GAS_WEB_APP_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);

echo "Response from GAS:\n" . $response . "\n";
