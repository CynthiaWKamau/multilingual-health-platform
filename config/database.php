<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "multilingual_health_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Groq AI 
define('GROQ_API_KEY', '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');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL',   'qwen/qwen3.8-27b');

//  Google Cloud Translation 
define('GOOGLE_TRANSLATE_API_KEY', 'YOUR_GOOGLE_API_KEY_HERE');

//  ML Flask API 
define('ML_API_URL', 'http://127.0.0.1:5000');

//  Language codes (for Google Translate) 
define('LANG_CODES', [
    'english'   => 'en',
    'kiswahili' => 'sw',
    'maa'       => 'mas',
]);
?>