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

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "DATE(quotes.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(quotes.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        quotes.id,
        quotes.quote_number,
        customers.customer_code,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        deals.title AS deal_title,
        quotes.subtotal,
        quotes.tax_amount,
        quotes.discount_amount,
        quotes.total_amount,
        quotes.status,
        quotes.valid_until,
        quotes.created_at,
        users.name AS created_by
    FROM quotes
    LEFT JOIN customers ON quotes.customer_id = customers.id
    LEFT JOIN companies ON quotes.company_id = companies.id
    LEFT JOIN contacts ON quotes.contact_id = contacts.id
    LEFT JOIN deals ON quotes.deal_id = deals.id
    LEFT JOIN users ON quotes.created_by = users.id
    $where_sql
    ORDER BY quotes.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Quotes Report");

$headers = [
    "ID", "Quote Number", "Customer Code", "Company", "Contact", "Deal",
    "Subtotal", "Tax Amount", "Discount Amount", "Total Amount", "Status",
    "Valid Until", "Created Date", "Created By"
];

$column = "A";
foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;
foreach ($quotes as $quote) {
    $contact_name = "";
    if (!empty($quote["first_name"])) {
        $contact_name = trim($quote["first_name"] . " " . ($quote["last_name"] ?? ""));
    }

    $values = [
        $quote["id"],
        $quote["quote_number"],
        $quote["customer_code"] ?? "",
        $quote["company_name"] ?? "",
        $contact_name,
        $quote["deal_title"] ?? "",
        $quote["subtotal"],
        $quote["tax_amount"],
        $quote["discount_amount"],
        $quote["total_amount"],
        $quote["status"],
        $quote["valid_until"] ?? "",
        $quote["created_at"],
        $quote["created_by"] ?? ""
    ];

    $column = "A";
    foreach ($values as $value) {
        $sheet->setCellValue($column . $row, $value);
        $column++;
    }
    $row++;
}

$sheet->getStyle("A1:N1")->getFont()->setBold(true);
foreach (range("A", "N") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}
$sheet->freezePane("A2");

$filename = "quotes_report";
if (!empty($from_date) && !empty($to_date)) {
    $filename .= "_" . $from_date . "_to_" . $to_date;
} elseif (!empty($from_date)) {
    $filename .= "_from_" . $from_date;
} elseif (!empty($to_date)) {
    $filename .= "_until_" . $to_date;
}
$filename .= ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
