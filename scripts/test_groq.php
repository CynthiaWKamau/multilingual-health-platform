<?php
session_start();
include_once __DIR__ . '/../config/database.php';

echo '<pre>Using model: ' . GROQ_MODEL . "\n\n";

$payload = json_encode([
    'model'    => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => 'You are an expert translator for the Maa language spoken in Kenya and Tanzania. Translate the following health-related English text into Maa. Return ONLY the translated text with no explanations.'],
        ['role' => 'user',   'content' => 'Clean water prevents disease.'],
    ],
    'max_tokens' => 500,
    'temperature' => 0.2,
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
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
echo 'HTTP      : ' . $httpcode . "\n";
echo 'content   : ' . ($data['choices'][0]['message']['content'] ?? '(empty)') . "\n";
echo 'reasoning : ' . mb_substr($data['choices'][0]['message']['reasoning'] ?? '(none)', 0, 150) . "\n";
echo 'finish    : ' . ($data['choices'][0]['finish_reason'] ?? '') . "\n";
echo 'tokens    : prompt=' . ($data['usage']['prompt_tokens'] ?? '?') . ' completion=' . ($data['usage']['completion_tokens'] ?? '?') . ' reasoning=' . ($data['usage']['completion_tokens_details']['reasoning_tokens'] ?? 0) . "\n";
echo '</pre>';