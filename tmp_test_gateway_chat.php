<?php
$base = 'http://101.132.65.180';
$key = 'sk-LD8Icrrc05maK7rrtwB83wZuqFMnOueqnxse11j2z305elfv';
$model = 'gpt-5.2';
$payload = [
  'model' => $model,
  'messages' => [
    ['role' => 'system', 'content' => '你是连接测试助手，请只回复连接正常。'],
    ['role' => 'user', 'content' => '请只回复连接正常'],
  ],
  'temperature' => 0.2,
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
  CURLOPT_TIMEOUT => 120,
  CURLOPT_CONNECTTIMEOUT => 8,
]);
$raw = curl_exec($ch);
$out = [
  'status' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
  'error' => curl_error($ch),
  'raw' => $raw,
];
curl_close($ch);
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), "\n";
