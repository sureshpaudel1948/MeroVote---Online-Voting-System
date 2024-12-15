<?php
require 'db_config.php';

try {
    // Test query
    $query = "SELECT 1 AS test_connection";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch();

    if ($result) {
        echo "Database connection is successful. Test query result: " . $result['test_connection'];
    } else {
        echo "Database connection is successful, but test query returned no results.";
    }
} catch (PDOException $e) {
    echo "Error testing database connection: " . $e->getMessage();
}
