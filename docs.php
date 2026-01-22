<?php
// docs.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$path = __DIR__ . '/docs.json';
if (!is_file($path)) {
  http_response_code(404);
  echo json_encode(['error' => 'docs.json not found'], JSON_UNESCAPED_UNICODE);
  exit;
}

$json = file_get_contents($path);
if ($json === false) {
  http_response_code(500);
  echo json_encode(['error' => 'Cannot read docs.json'], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode($json, true);
if (!is_array($data)) {
  http_response_code(500);
  echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);