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
        company_name,
        industry,
        phone,
        email,
        website,
        city,
        state,
        country,
        created_at
    FROM companies
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

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Companies Report");

$headers = [
    "ID",
    "Company Name",
    "Industry",
    "Phone",
    "Email",
    "Website",
    "City",
    "State",
    "Country",
    "Created Date"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($companies as $company) {
    $sheet->setCellValue("A" . $row, $company["id"]);
    $sheet->setCellValue("B" . $row, $company["company_name"]);
    $sheet->setCellValue("C" . $row, $company["industry"]);
    $sheet->setCellValue("D" . $row, $company["phone"]);
    $sheet->setCellValue("E" . $row, $company["email"]);
    $sheet->setCellValue("F" . $row, $company["website"]);
    $sheet->setCellValue("G" . $row, $company["city"]);
    $sheet->setCellValue("H" . $row, $company["state"]);
    $sheet->setCellValue("I" . $row, $company["country"]);
    $sheet->setCellValue(
        "J" . $row,
        date("d-m-Y", strtotime($company["created_at"]))
    );

    $row++;
}

foreach (range("A", "J") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=companies_report.xlsx"
);

header("Cache-Control: max-age=0");

$writer->save("php://output");
exit;

?>
