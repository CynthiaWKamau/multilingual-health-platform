<?php
$servername = "localhost";
$username = "root";  
$password = "";      
$dbname = "multilingual_health_platform"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Google Cloud Translation API
define('GOOGLE_TRANSLATE_API_KEY', 'YOUR_GOOGLE_API_KEY_HERE');

// Groq API
define('GROQ_API_KEY', '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');

// Language codes
define('LANG_CODES', [
    'english'   => 'en',
    'kiswahili' => 'sw',
    'maa'       => 'mas'
]);
?>
?>