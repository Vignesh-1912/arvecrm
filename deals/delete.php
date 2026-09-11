<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

try {

    // Start transaction
    $conn->beginTransaction();

    // Delete products associated with this deal
    $sql = "DELETE FROM deal_products WHERE deal_id = :deal_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":deal_id" => $id
    ]);

    // Delete the deal
    $sql = "DELETE FROM deals WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => $id
    ]);

    // Commit transaction
    $conn->commit();

    header("Location: index.php?success=" . urlencode("Deal deleted successfully."));
    exit;

} catch (PDOException $e) {

    // Rollback if something goes wrong
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    die("Unable to delete deal: " . htmlspecialchars($e->getMessage()));
}
?>