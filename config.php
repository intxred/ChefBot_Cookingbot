<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Change this to your MySQL username
define('DB_PASS', '');               // Change this to your MySQL password
define('DB_NAME', 'chefbot_db');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Test connection (optional - remove in production)
function testConnection() {
    $conn = getDBConnection();
    if ($conn) {
        echo "Database connected successfully!";
        $conn->close();
        return true;
    }
    return false;
}
?>