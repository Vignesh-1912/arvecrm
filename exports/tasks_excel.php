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
    $where[] = "DATE(tasks.due_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(tasks.due_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

$sql = "
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.due_date,
        tasks.priority,
        tasks.status,
        users.name AS assigned_user,
        contacts.first_name,
        contacts.last_name,
        customers.customer_code
    FROM tasks

    LEFT JOIN users
        ON tasks.assigned_to = users.id

    LEFT JOIN contacts
        ON tasks.contact_id = contacts.id

    LEFT JOIN customers
        ON tasks.customer_id = customers.id

    $where_sql

    ORDER BY tasks.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Tasks Report");

$headers = [
    "ID",
    "Title",
    "Description",
    "Due Date",
    "Priority",
    "Status",
    "Assigned To",
    "Contact",
    "Customer Code"
];

$column = "A";

foreach ($headers as $header) {
    $sheet->setCellValue($column . "1", $header);
    $column++;
}

$row = 2;

foreach ($tasks as $task) {

    $contact_name = "";

    if (!empty($task["first_name"])) {
        $contact_name = trim(
            $task["first_name"] . " " . ($task["last_name"] ?? "")
        );
    }

    $sheet->setCellValue("A" . $row, $task["id"]);
    $sheet->setCellValue("B" . $row, $task["title"]);
    $sheet->setCellValue("C" . $row, $task["description"] ?? "");
    $sheet->setCellValue("D" . $row, $task["due_date"] ?? "");
    $sheet->setCellValue("E" . $row, $task["priority"]);
    $sheet->setCellValue("F" . $row, $task["status"]);
    $sheet->setCellValue("G" . $row, $task["assigned_user"] ?? "");
    $sheet->setCellValue("H" . $row, $contact_name);
    $sheet->setCellValue("I" . $row, $task["customer_code"] ?? "");

    $row++;
}

$sheet->getStyle("A1:I1")->getFont()->setBold(true);

foreach (range("A", "I") as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$sheet->freezePane("A2");

$filename = "tasks_report";

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