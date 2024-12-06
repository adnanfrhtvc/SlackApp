<?php
// Database configuration
$host = 'localhost:3307';
$username = 'root';   // Default XAMPP MySQL username
$password = '';       // No password for root
$database = 'slack_app';    // Name of your database

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
