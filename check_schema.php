<?php
require_once "config/database.php";
$stmt = $conn->query('DESCRIBE users');
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Key'] . ' - ' . $row['Default'] . PHP_EOL;
}