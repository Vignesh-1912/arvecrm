<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit;
}

require_once "../config/database.php";

header("Content-Type: application/json");

$q = trim($_GET["q"] ?? "");

if ($q === "") {
    echo json_encode([]);
    exit;
}

$search = "%" . $q . "%";

$results = [];

/* ==========================
   CONTACTS
========================== */

$stmt = $conn->prepare("
    SELECT
        id,
        CONCAT(first_name, ' ', last_name) AS title,
        email
    FROM contacts
    WHERE
        first_name LIKE :search1
        OR last_name LIKE :search2
        OR email LIKE :search3
        OR phone LIKE :search4
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->execute([
    ":search1" => $search,
    ":search2" => $search,
    ":search3" => $search,
    ":search4" => $search
]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        "type" => "Contact",
        "title" => trim($row["title"]),
        "description" => $row["email"],
        "url" => "../contacts/index.php"
    ];
}

/* ==========================
   LEADS
========================== */

$stmt = $conn->prepare("
    SELECT
        id,
        lead_name AS title,
        email
    FROM leads
    WHERE
        lead_name LIKE :search1
        OR email LIKE :search2
        OR phone LIKE :search3
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->execute([
    ":search1" => $search,
    ":search2" => $search,
    ":search3" => $search
]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        "type" => "Lead",
        "title" => $row["title"],
        "description" => $row["email"],
        "url" => "../leads/index.php"
    ];
}

/* ==========================
   DEALS
========================== */

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        stage
    FROM deals
    WHERE
        title LIKE :search1
        OR stage LIKE :search2
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->execute([
    ":search1" => $search,
    ":search2" => $search
]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        "type" => "Deal",
        "title" => $row["title"],
        "description" => $row["stage"],
        "url" => "../deals/index.php"
    ];
}

/* ==========================
   PRODUCTS
========================== */

$stmt = $conn->prepare("
    SELECT
        id,
        name AS title,
        sku
    FROM products
    WHERE
        name LIKE :search1
        OR sku LIKE :search2
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->execute([
    ":search1" => $search,
    ":search2" => $search
]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        "type" => "Product",
        "title" => $row["title"],
        "description" => "SKU: " . $row["sku"],
        "url" => "../products/index.php"
    ];
}

/* ==========================
   RETURN RESULTS
========================== */

echo json_encode(
    array_slice($results, 0, 10)
);

?>
