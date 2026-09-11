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
    $where[] = "DATE(deals.expected_close_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(deals.expected_close_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        deals.id,
        deals.title,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        deals.amount,
        deals.stage,
        deals.probability,
        deals.expected_close_date,
        users.name AS assigned_user,
        deals.description
    FROM deals

    LEFT JOIN companies
        ON deals.company_id = companies.id

    LEFT JOIN contacts
        ON deals.contact_id = contacts.id

    LEFT JOIN users
        ON deals.assigned_to = users.id

    $where_sql

    ORDER BY deals.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Create Excel
|--------------------------------------------------------------------------
*/

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Deals Report");


/*
|--------------------------------------------------------------------------
| Headers
|--------------------------------------------------------------------------
*/

$headers = [
    "ID",
    "Deal Title",
    "Company",
    "Contact",
    "Amount",
    "Stage",
    "Probability",
    "Expected Close Date",
    "Assigned To",
    "Description"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}


/*
|--------------------------------------------------------------------------
| Data
|--------------------------------------------------------------------------
*/

$row = 2;

foreach ($deals as $deal) {

    $contact_name = "";

    if (!empty($deal["first_name"])) {
        $contact_name = trim(
            $deal["first_name"] . " " . ($deal["last_name"] ?? "")
        );
    }

    $sheet->setCellValue("A" . $row, $deal["id"]);
    $sheet->setCellValue("B" . $row, $deal["title"]);
    $sheet->setCellValue("C" . $row, $deal["company_name"] ?? "");
    $sheet->setCellValue("D" . $row, $contact_name);
    $sheet->setCellValue("E" . $row, $deal["amount"]);
    $sheet->setCellValue("F" . $row, $deal["stage"]);
    $sheet->setCellValue("G" . $row, $deal["probability"] . "%");
    $sheet->setCellValue("H" . $row, $deal["expected_close_date"] ?? "");
    $sheet->setCellValue("I" . $row, $deal["assigned_user"] ?? "");
    $sheet->setCellValue("J" . $row, $deal["description"] ?? "");

    $row++;
}


/*
|--------------------------------------------------------------------------
| Format Header
|--------------------------------------------------------------------------
*/

$sheet->getStyle("A1:J1")->getFont()->setBold(true);


/*
|--------------------------------------------------------------------------
| Auto Size
|--------------------------------------------------------------------------
*/

foreach (range("A", "J") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}


/*
|--------------------------------------------------------------------------
| Freeze Header
|--------------------------------------------------------------------------
*/

$sheet->freezePane("A2");


/*
|--------------------------------------------------------------------------
| Filename
|--------------------------------------------------------------------------
*/

$filename = "deals_report";

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
| Download
|--------------------------------------------------------------------------
*/

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