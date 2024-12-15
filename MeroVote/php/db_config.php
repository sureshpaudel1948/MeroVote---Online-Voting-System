<?php
// db_config.php

$host = 'localhost';       // Database host (localhost or IP)
$db = 'online_voting';     // Database name
$user = 'postgres';        // Database user
$password = 'postgre';     // Database password

try {
    // Data Source Name (DSN) for PostgreSQL
    $dsn = "pgsql:host=$host;dbname=$db";

    // Create a new PDO instance with exception mode for error handling
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Fetch associative arrays by default
    ]);
} catch (PDOException $e) {
    // If connection fails, show error message and terminate script
    die("Database connection failed: " . $e->getMessage());
}
