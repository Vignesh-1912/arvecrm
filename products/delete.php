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

    $conn->beginTransaction();

    // Delete product from deals first
    $sql = "
        DELETE FROM deal_products
        WHERE product_id = :product_id
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":product_id" => $id
    ]);

    // Delete the product
    $sql = "
        DELETE FROM products
        WHERE id = :id
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":id" => $id
    ]);

    $conn->commit();

    header(
        "Location: index.php?success=" .
        urlencode("Product deleted successfully.")
    );

    exit;

} catch (PDOException $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    die(
        "Unable to delete product: " .
        htmlspecialchars($e->getMessage())
    );
}

?>