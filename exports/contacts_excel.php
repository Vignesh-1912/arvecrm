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
        contacts.id,
        contacts.first_name,
        contacts.last_name,
        companies.company_name,
        contacts.email,
        contacts.phone,
        contacts.job_title,
        contacts.city,
        contacts.state,
        contacts.country,
        contacts.status,
        contacts.created_at
    FROM contacts
    LEFT JOIN companies
        ON contacts.company_id = companies.id
    WHERE 1=1
";

$params = [];

if (!empty($from_date)) {
    $sql .= " AND DATE(contacts.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(contacts.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$sql .= " ORDER BY contacts.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Contacts Report");

$headers = [
    "ID",
    "First Name",
    "Last Name",
    "Company",
    "Email",
    "Phone",
    "Job Title",
    "City",
    "State",
    "Country",
    "Status",
    "Created Date"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($contacts as $contact) {
    $sheet->setCellValue("A" . $row, $contact["id"]);
    $sheet->setCellValue("B" . $row, $contact["first_name"]);
    $sheet->setCellValue("C" . $row, $contact["last_name"]);
    $sheet->setCellValue("D" . $row, $contact["company_name"] ?? "");
    $sheet->setCellValue("E" . $row, $contact["email"] ?? "");
    $sheet->setCellValue("F" . $row, $contact["phone"] ?? "");
    $sheet->setCellValue("G" . $row, $contact["job_title"] ?? "");
    $sheet->setCellValue("H" . $row, $contact["city"] ?? "");
    $sheet->setCellValue("I" . $row, $contact["state"] ?? "");
    $sheet->setCellValue("J" . $row, $contact["country"] ?? "");
    $sheet->setCellValue("K" . $row, $contact["status"] ?? "");
    $sheet->setCellValue(
        "L" . $row,
        date("d-m-Y", strtotime($contact["created_at"]))
    );

    $row++;
}

foreach (range("A", "L") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=contacts_report.xlsx"
);

header("Cache-Control: max-age=0");

$writer->save("php://output");

exit;

?>
