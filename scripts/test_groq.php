<?php
session_start();
include_once __DIR__ . '/../config/database.php';

$payload = json_encode([
    'model'    => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => 'Translate to Maa language. Return only the translation.'],
        ['role' => 'user',   'content' => 'Clean water prevents disease.'],
    ],
    'temperature' => 0.2,
    'max_tokens'  => 100,
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo '<pre>';
echo 'HTTP Code : ' . $httpcode . "\n";
echo 'cURL Error: ' . ($err ?: 'none') . "\n";
echo 'Response  : ' . $response . "\n";
echo '</pre>';