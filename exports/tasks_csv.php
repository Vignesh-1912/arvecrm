<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

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

$filename = "tasks_report";

if (!empty($from_date) && !empty($to_date)) {
    $filename .= "_" . $from_date . "_to_" . $to_date;
} elseif (!empty($from_date)) {
    $filename .= "_from_" . $from_date;
} elseif (!empty($to_date)) {
    $filename .= "_until_" . $to_date;
}

$filename .= ".csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    "ID",
    "Title",
    "Description",
    "Due Date",
    "Priority",
    "Status",
    "Assigned To",
    "Contact",
    "Customer Code"
]);

foreach ($tasks as $task) {

    $contact_name = "";

    if (!empty($task["first_name"])) {
        $contact_name = trim(
            $task["first_name"] . " " . ($task["last_name"] ?? "")
        );
    }

    fputcsv($output, [
        $task["id"],
        $task["title"],
        $task["description"] ?? "",
        $task["due_date"] ?? "",
        $task["priority"],
        $task["status"],
        $task["assigned_user"] ?? "",
        $contact_name,
        $task["customer_code"] ?? ""
    ]);
}

fclose($output);
exit;
?>