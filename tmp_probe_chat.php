<?php
$base = 'http://101.132.65.180';
$key = 'sk-LD8Icrrc05maK7rrtwB83wZuqFMnOueqnxse11j2z305elfv';
$model = 'gpt5.4';
$payload = [
  'model' => $model,
  'messages' => [
    ['role' => 'user', 'content' => '只回复ok']
  ],
  'temperature' => 0.1,
];
$ch = curl_init($base . '/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $key,
    'Accept: application/json',
  ],
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  CURLOPT_TIMEOUT => 30,
  CURLOPT_CONNECTTIMEOUT => 8,
]);
$raw = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$err = curl_error($ch);
curl_close($ch);
echo json_encode(['status'=>$status,'error'=>$err,'body'=>$raw], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), "\n";
