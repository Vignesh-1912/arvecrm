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
    $where[] = "DATE(customers.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(customers.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        customers.id,
        customers.customer_code,
        customers.customer_type,
        customers.status,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        contacts.email,
        contacts.phone,
        customers.credit_limit,
        customers.created_at,
        customers.notes
    FROM customers

    LEFT JOIN companies
        ON customers.company_id = companies.id

    LEFT JOIN contacts
        ON customers.contact_id = contacts.id

    $where_sql

    ORDER BY customers.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Customers Report");

$headers = [
    "ID",
    "Customer Code",
    "Customer Type",
    "Status",
    "Company",
    "Contact",
    "Email",
    "Phone",
    "Credit Limit",
    "Created Date",
    "Notes"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($customers as $customer) {

    $contact_name = "";

    if (!empty($customer["first_name"])) {
        $contact_name = trim(
            $customer["first_name"] . " " . ($customer["last_name"] ?? "")
        );
    }

    $sheet->setCellValue("A" . $row, $customer["id"]);
    $sheet->setCellValue("B" . $row, $customer["customer_code"] ?? "");
    $sheet->setCellValue("C" . $row, $customer["customer_type"] ?? "");
    $sheet->setCellValue("D" . $row, $customer["status"] ?? "");
    $sheet->setCellValue("E" . $row, $customer["company_name"] ?? "");
    $sheet->setCellValue("F" . $row, $contact_name);
    $sheet->setCellValue("G" . $row, $customer["email"] ?? "");
    $sheet->setCellValue("H" . $row, $customer["phone"] ?? "");
    $sheet->setCellValue("I" . $row, $customer["credit_limit"]);
    $sheet->setCellValue("J" . $row, $customer["created_at"]);
    $sheet->setCellValue("K" . $row, $customer["notes"] ?? "");

    $row++;
}

$sheet->getStyle("A1:K1")->getFont()->setBold(true);

foreach (range("A", "K") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$sheet->freezePane("A2");

$filename = "customers_report";

if (!empty($from_date) && !empty($to_date)) {

    $filename .= "_" . $from_date . "_to_" . $to_date;

} elseif (!empty($from_date)) {

    $filename .= "_from_" . $from_date;

} elseif (!empty($to_date)) {

    $filename .= "_until_" . $to_date;
}

$filename .= ".xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"$filename\""
);

header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;

?>