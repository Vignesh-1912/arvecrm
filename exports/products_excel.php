<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Products");

$headers = [
    "ID",
    "Product Name",
    "SKU",
    "Type",
    "Description",
    "Price",
    "Tax Rate",
    "Status",
    "Created Date"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($products as $product) {
    $status = ($product["status"] == 1) ? "Active" : "Inactive";

    $sheet->setCellValue("A" . $row, $product["id"]);
    $sheet->setCellValue("B" . $row, $product["name"]);
    $sheet->setCellValue("C" . $row, $product["sku"]);
    $sheet->setCellValue("D" . $row, $product["type"] ?? "");
    $sheet->setCellValue("E" . $row, $product["description"] ?? "");
    $sheet->setCellValue("F" . $row, $product["price"]);
    $sheet->setCellValue("G" . $row, $product["tax_rate"]);
    $sheet->setCellValue("H" . $row, $status);
    $sheet->setCellValue("I" . $row, $product["created_at"]);

    $row++;
}

foreach (range("A", "I") as $columnId) {
    $sheet->getColumnDimension($columnId)->setAutoSize(true);
}

$filename = "products_report.xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");

exit;
?>
