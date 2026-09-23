<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

$sql = "
    SELECT
        id,
        name,
        sku,
        type,
        description,
        price,
        tax_rate,
        status,
        created_at
    FROM products
    WHERE 1=1
";

$params = [];

if (!empty($from_date)) {
    $sql .= " AND DATE(created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=products_report.csv");

$output = fopen("php://output", "w");

fputcsv($output, [
    "ID",
    "Product Name",
    "SKU",
    "Type",
    "Description",
    "Price",
    "Tax Rate",
    "Status",
    "Created Date"
]);

foreach ($products as $product) {
    $status = ($product["status"] == 1) ? "Active" : "Inactive";

    fputcsv($output, [
        $product["id"],
        $product["name"],
        $product["sku"],
        $product["type"] ?? "",
        $product["description"] ?? "",
        number_format((float)$product["price"], 2, ".", ""),
        number_format((float)$product["tax_rate"], 2, ".", ""),
        $status,
        date("d-m-Y", strtotime($product["created_at"]))
    ]);
}

fclose($output);
exit;

?>
