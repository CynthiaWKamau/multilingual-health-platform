<?php
require_once 'config.php';

if ($conn) {
    echo "✅ Connected successfully to: " . $dbname ;
} else {
    echo "❌ Connection failed: " . mysqli_connect_error();
}
?>