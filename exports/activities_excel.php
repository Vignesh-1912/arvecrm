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
    $where[] = "DATE(activities.activity_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(activities.activity_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        activities.id,
        activities.type,
        activities.subject,
        activities.description,
        activities.activity_date,

        contacts.first_name,
        contacts.last_name,

        customers.customer_code,

        users.name AS created_by

    FROM activities

    LEFT JOIN contacts
        ON activities.contact_id = contacts.id

    LEFT JOIN customers
        ON activities.customer_id = customers.id

    LEFT JOIN users
        ON activities.created_by = users.id

    $where_sql

    ORDER BY activities.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Activities Report");

$headers = [
    "ID",
    "Type",
    "Subject",
    "Description",
    "Activity Date",
    "Contact",
    "Customer",
    "Created By"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($activities as $activity) {

    $contact_name = "";

    if (!empty($activity["first_name"])) {
        $contact_name = trim(
            $activity["first_name"] . " " .
            ($activity["last_name"] ?? "")
        );
    }

    $sheet->setCellValue("A" . $row, $activity["id"]);
    $sheet->setCellValue("B" . $row, $activity["type"] ?? "");
    $sheet->setCellValue("C" . $row, $activity["subject"] ?? "");
    $sheet->setCellValue("D" . $row, $activity["description"] ?? "");
    $sheet->setCellValue("E" . $row, $activity["activity_date"] ?? "");
    $sheet->setCellValue("F" . $row, $contact_name);
    $sheet->setCellValue("G" . $row, $activity["customer_code"] ?? "");
    $sheet->setCellValue("H" . $row, $activity["created_by"] ?? "");

    $row++;
}

$sheet->getStyle("A1:H1")->getFont()->setBold(true);

foreach (range("A", "H") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$sheet->freezePane("A2");

$filename = "activities_report";

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
