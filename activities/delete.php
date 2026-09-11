<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

try {

    $sql = "DELETE FROM activities WHERE id = :id";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: index.php");
    exit;

} catch (PDOException $e) {

    die("Unable to delete activity: " . $e->getMessage());
}
?>