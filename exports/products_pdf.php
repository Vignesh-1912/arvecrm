<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

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

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
};

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10px;
        color: #333;
    }

    h1 {
        text-align: center;
        margin-bottom: 5px;
    }

    .date-filter {
        text-align: center;
        margin-bottom: 20px;
        font-size: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #2563eb;
        color: white;
        padding: 7px;
        border: 1px solid #ddd;
    }

    td {
        padding: 6px;
        border: 1px solid #ddd;
        vertical-align: top;
    }

    .total {
        margin-top: 15px;
        font-weight: bold;
        font-size: 11px;
    }
</style>
</head>
<body>
<h1>Products Report</h1>';

if (!empty($from_date) || !empty($to_date)) {
    $html .= '<div class="date-filter">';

    if (!empty($from_date)) {
        $html .= "From: " . $escape($from_date) . " ";
    }

    if (!empty($to_date)) {
        $html .= "To: " . $escape($to_date);
    }

    $html .= "</div>";
}

$html .= '
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>SKU</th>
            <th>Type</th>
            <th>Description</th>
            <th>Price</th>
            <th>Tax Rate</th>
            <th>Status</th>
            <th>Created Date</th>
        </tr>
    </thead>
    <tbody>';

foreach ($products as $product) {
    $status = ($product["status"] == 1) ? "Active" : "Inactive";

    $html .= '
        <tr>
            <td>' . $escape($product["id"]) . '</td>
            <td>' . $escape($product["name"]) . '</td>
            <td>' . $escape($product["sku"]) . '</td>
            <td>' . $escape($product["type"]) . '</td>
            <td>' . $escape($product["description"]) . '</td>
            <td>' . $escape($product["price"]) . '</td>
            <td>' . $escape($product["tax_rate"]) . '%</td>
            <td>' . $escape($status) . '</td>
            <td>' . $escape($product["created_at"]) . '</td>
        </tr>';
}

$html .= '
    </tbody>
</table>
<div class="total">Total Products: ' . count($products) . '</div>
</body>
</html>';

$options = new Options();
$options->set("defaultFont", "DejaVu Sans");
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$dompdf->stream(
    "products_report.pdf",
    [
        "Attachment" => true
    ]
);

exit;
?>
