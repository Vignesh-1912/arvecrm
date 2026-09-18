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

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
};

$options = new Options();
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: Arial, sans-serif;
    font-size: 9px;
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
    background-color: #2563eb;
    color: white;
    padding: 6px;
    border: 1px solid #ddd;
    text-align: left;
}
td {
    padding: 5px;
    border: 1px solid #ddd;
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
<h1>Contacts Report</h1>
<div class="date-filter">';

if (!empty($from_date) && !empty($to_date)) {
    $html .= "Date Range: " . $escape($from_date) . " to " . $escape($to_date);
} elseif (!empty($from_date)) {
    $html .= "From Date: " . $escape($from_date);
} elseif (!empty($to_date)) {
    $html .= "To Date: " . $escape($to_date);
} else {
    $html .= "All Contacts";
}

$html .= '
</div>
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Company</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Job Title</th>
    <th>City</th>
    <th>State</th>
    <th>Country</th>
    <th>Status</th>
    <th>Created Date</th>
</tr>
</thead>
<tbody>';

foreach ($contacts as $contact) {
    $full_name = trim(
        ($contact["first_name"] ?? "") . " " .
        ($contact["last_name"] ?? "")
    );

    $created_date = !empty($contact["created_at"])
        ? date("d-m-Y", strtotime($contact["created_at"]))
        : "";

    $html .= '
    <tr>
        <td>' . $escape($contact["id"]) . '</td>
        <td>' . $escape($full_name) . '</td>
        <td>' . $escape($contact["company_name"]) . '</td>
        <td>' . $escape($contact["email"]) . '</td>
        <td>' . $escape($contact["phone"]) . '</td>
        <td>' . $escape($contact["job_title"]) . '</td>
        <td>' . $escape($contact["city"]) . '</td>
        <td>' . $escape($contact["state"]) . '</td>
        <td>' . $escape($contact["country"]) . '</td>
        <td>' . $escape($contact["status"]) . '</td>
        <td>' . $escape($created_date) . '</td>
    </tr>';
}

$html .= '
</tbody>
</table>
<div class="total">Total Contacts: ' . count($contacts) . '</div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream("contacts_report.pdf", ["Attachment" => true]);

exit;
?>
