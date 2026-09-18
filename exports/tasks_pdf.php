<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

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

$where_sql = !empty($where)
    ? " WHERE " . implode(" AND ", $where)
    : "";

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

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
};

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9px;
    color: #333;
}
h1 {
    text-align: center;
    font-size: 20px;
    margin-bottom: 5px;
}
.date-filter {
    text-align: center;
    color: #555;
    margin-bottom: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    background: #2563eb;
    color: white;
    padding: 6px;
    border: 1px solid #ddd;
    text-align: left;
}
td {
    padding: 5px;
    border: 1px solid #ddd;
    vertical-align: top;
}
tr:nth-child(even) {
    background-color: #f5f5f5;
}
.total {
    margin-top: 15px;
    font-weight: bold;
}
</style>
</head>
<body>
<h1>Tasks Report</h1>
<div class="date-filter">';

if (!empty($from_date) && !empty($to_date)) {
    $html .= "Date Range: " . $escape($from_date) . " to " . $escape($to_date);
} elseif (!empty($from_date)) {
    $html .= "From Date: " . $escape($from_date);
} elseif (!empty($to_date)) {
    $html .= "To Date: " . $escape($to_date);
} else {
    $html .= "All Tasks";
}

$html .= '
</div>
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Description</th>
    <th>Due Date</th>
    <th>Priority</th>
    <th>Status</th>
    <th>Assigned To</th>
    <th>Contact</th>
    <th>Customer Code</th>
</tr>
</thead>
<tbody>';

foreach ($tasks as $task) {
    $contact_name = "";

    if (!empty($task["first_name"])) {
        $contact_name = trim(
            $task["first_name"] . " " . ($task["last_name"] ?? "")
        );
    }

    $html .= '
<tr>
    <td>' . $escape($task["id"]) . '</td>
    <td>' . $escape($task["title"]) . '</td>
    <td>' . $escape($task["description"]) . '</td>
    <td>' . $escape($task["due_date"]) . '</td>
    <td>' . $escape($task["priority"]) . '</td>
    <td>' . $escape($task["status"]) . '</td>
    <td>' . $escape($task["assigned_user"]) . '</td>
    <td>' . $escape($contact_name) . '</td>
    <td>' . $escape($task["customer_code"]) . '</td>
</tr>';
}

$html .= '
</tbody>
</table>
<div class="total">Total Tasks: ' . count($tasks) . '</div>
</body>
</html>';

$options = new Options();
$options->set("defaultFont", "DejaVu Sans");
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$dompdf->stream("tasks_report.pdf", ["Attachment" => true]);

exit;
?>
