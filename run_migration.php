<?php
require_once "config/database.php";

$sql = file_get_contents("migrations/add_auth_features.sql");

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        $conn->exec($stmt);
        echo "OK: " . substr($stmt, 0, 60) . "...\n";
    } catch (PDOException $e) {
        // Some statements might fail if already applied (e.g., IF NOT EXISTS)
        if (strpos($e->getMessage(), 'Duplicate column') !== false ||
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "SKIP: " . substr($stmt, 0, 60) . "... (already exists)\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo "Statement: $stmt\n";
        }
    }
}

echo "Migration complete!\n";