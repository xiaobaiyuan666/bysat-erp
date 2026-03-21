<?php
$base = 'http://101.132.65.180';
$key = 'sk-LD8Icrrc05maK7rrtwB83wZuqFMnOueqnxse11j2z305elfv';
$tests = [
  ['label' => 'root', 'url' => $base],
  ['label' => 'models_v1', 'url' => $base . '/v1/models'],
  ['label' => 'models_root', 'url' => $base . '/models'],
  ['label' => 'chat_v1_options', 'url' => $base . '/v1/chat/completions', 'method' => 'OPTIONS'],
];
foreach ($tests as $test) {
    $ch = curl_init($test['url']);
    $headers = ['Authorization: Bearer ' . $key, 'Accept: application/json'];
    $method = $test['method'] ?? 'GET';
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $body = $raw !== false ? substr($raw, $headerSize) : '';
    echo "=== {$test['label']} ===\n";
    echo json_encode([
        'url' => $test['url'],
        'status' => $status,
        'error' => $err,
        'body_preview' => substr($body, 0, 600),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n\n";
}
