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

    // Delete sale
    // Related sale_items will be deleted automatically
    // because sale_items.sale_id uses ON DELETE CASCADE.

    $sql = "DELETE FROM sales WHERE id = :id";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: index.php");
    exit;

} catch (PDOException $e) {

    die("Unable to delete sale: " . $e->getMessage());

}

?>