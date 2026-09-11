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
    $where[] = "DATE(sales.sale_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(sales.sale_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        sales.id,
        sales.sale_number,
        customers.customer_code,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        sales.subtotal,
        sales.tax_amount,
        sales.discount_amount,
        sales.total_amount,
        sales.payment_status,
        sales.sale_status,
        sales.sale_date
    FROM sales

    LEFT JOIN customers
        ON sales.customer_id = customers.id

    LEFT JOIN companies
        ON sales.company_id = companies.id

    LEFT JOIN contacts
        ON sales.contact_id = contacts.id

    $where_sql

    ORDER BY sales.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Create Excel Spreadsheet
|--------------------------------------------------------------------------
*/

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Sales Report");


/*
|--------------------------------------------------------------------------
| Header Row
|--------------------------------------------------------------------------
*/

$headers = [
    "ID",
    "Sale Number",
    "Customer Code",
    "Company",
    "Contact",
    "Subtotal",
    "Tax Amount",
    "Discount Amount",
    "Total Amount",
    "Payment Status",
    "Sale Status",
    "Sale Date"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}


/*
|--------------------------------------------------------------------------
| Data Rows
|--------------------------------------------------------------------------
*/

$row = 2;

foreach ($sales as $sale) {

    $contact_name = "";

    if (!empty($sale["first_name"])) {
        $contact_name = trim(
            $sale["first_name"] . " " . ($sale["last_name"] ?? "")
        );
    }

    $sheet->setCellValue("A" . $row, $sale["id"]);
    $sheet->setCellValue("B" . $row, $sale["sale_number"]);
    $sheet->setCellValue("C" . $row, $sale["customer_code"] ?? "");
    $sheet->setCellValue("D" . $row, $sale["company_name"] ?? "");
    $sheet->setCellValue("E" . $row, $contact_name);
    $sheet->setCellValue("F" . $row, $sale["subtotal"]);
    $sheet->setCellValue("G" . $row, $sale["tax_amount"]);
    $sheet->setCellValue("H" . $row, $sale["discount_amount"]);
    $sheet->setCellValue("I" . $row, $sale["total_amount"]);
    $sheet->setCellValue("J" . $row, $sale["payment_status"]);
    $sheet->setCellValue("K" . $row, $sale["sale_status"]);
    $sheet->setCellValue("L" . $row, $sale["sale_date"]);

    $row++;
}


/*
|--------------------------------------------------------------------------
| Style Header
|--------------------------------------------------------------------------
*/

$sheet->getStyle("A1:L1")->getFont()->setBold(true);


/*
|--------------------------------------------------------------------------
| Auto Size Columns
|--------------------------------------------------------------------------
*/

foreach (range("A", "L") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}


/*
|--------------------------------------------------------------------------
| Freeze Header Row
|--------------------------------------------------------------------------
*/

$sheet->freezePane("A2");


/*
|--------------------------------------------------------------------------
| File Name
|--------------------------------------------------------------------------
*/

$filename = "sales_report";

if (!empty($from_date) && !empty($to_date)) {

    $filename .= "_" . $from_date . "_to_" . $to_date;

} elseif (!empty($from_date)) {

    $filename .= "_from_" . $from_date;

} elseif (!empty($to_date)) {

    $filename .= "_until_" . $to_date;
}

$filename .= ".xlsx";


/*
|--------------------------------------------------------------------------
| Download Excel File
|--------------------------------------------------------------------------
*/

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;

?>