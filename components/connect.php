<?php

$host = 'localhost';
$dbname = 'unikart_db';
$username = 'root';  
$password = '';   

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>