<?php
$base = 'http://101.132.65.180';
$key = 'sk-LD8Icrrc05maK7rrtwB83wZuqFMnOueqnxse11j2z305elfv';
$raw = file_get_contents($base . '/v1/models', false, stream_context_create([
  'http' => [
    'method' => 'GET',
    'header' => "Authorization: Bearer {$key}\r\nAccept: application/json\r\n",
    'timeout' => 20,
  ]
]));
$data = json_decode($raw, true);
$found = [];
foreach (($data['data'] ?? []) as $item) {
  if (stripos($item['id'] ?? '', 'gpt5.4') !== false) {
    $found[] = $item['id'];
  }
}
echo json_encode(['count'=>count($data['data'] ?? []), 'matches'=>$found], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), "\n";
