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
    $where[] = "DATE(leads.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(leads.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        leads.id,
        leads.lead_name,
        leads.email,
        leads.phone,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        leads.source,
        leads.status,
        leads.lead_value,
        leads.created_at,
        users.name AS assigned_user,
        leads.notes
    FROM leads

    LEFT JOIN companies
        ON leads.company_id = companies.id

    LEFT JOIN contacts
        ON leads.contact_id = contacts.id

    LEFT JOIN users
        ON leads.assigned_to = users.id

    $where_sql

    ORDER BY leads.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Leads Report");

$headers = [
    "ID",
    "Lead Name",
    "Email",
    "Phone",
    "Company",
    "Contact",
    "Source",
    "Status",
    "Lead Value",
    "Created Date",
    "Assigned To",
    "Notes"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($leads as $lead) {

    $contact_name = "";

    if (!empty($lead["first_name"])) {
        $contact_name = trim(
            $lead["first_name"] . " " . ($lead["last_name"] ?? "")
        );
    }

    $sheet->setCellValue("A" . $row, $lead["id"]);
    $sheet->setCellValue("B" . $row, $lead["lead_name"]);
    $sheet->setCellValue("C" . $row, $lead["email"] ?? "");
    $sheet->setCellValue("D" . $row, $lead["phone"] ?? "");
    $sheet->setCellValue("E" . $row, $lead["company_name"] ?? "");
    $sheet->setCellValue("F" . $row, $contact_name);
    $sheet->setCellValue("G" . $row, $lead["source"] ?? "");
    $sheet->setCellValue("H" . $row, $lead["status"] ?? "");
    $sheet->setCellValue("I" . $row, $lead["lead_value"]);
    $sheet->setCellValue("J" . $row, $lead["created_at"]);
    $sheet->setCellValue("K" . $row, $lead["assigned_user"] ?? "");
    $sheet->setCellValue("L" . $row, $lead["notes"] ?? "");

    $row++;
}

$sheet->getStyle("A1:L1")->getFont()->setBold(true);

foreach (range("A", "L") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$sheet->freezePane("A2");

$filename = "leads_report";

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