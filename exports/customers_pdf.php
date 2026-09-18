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
    $where[] = "DATE(customers.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(customers.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where)
    ? " WHERE " . implode(" AND ", $where)
    : "";

$sql = "
    SELECT
        customers.id,
        customers.customer_code,
        customers.customer_type,
        customers.status,
        customers.credit_limit,
        customers.notes,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        contacts.email,
        contacts.phone,
        customers.created_at
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
<h1>Customers Report</h1>
<div class="date-filter">';

if (!empty($from_date) && !empty($to_date)) {
    $html .= "Date Range: " . $escape($from_date) . " to " . $escape($to_date);
} elseif (!empty($from_date)) {
    $html .= "From Date: " . $escape($from_date);
} elseif (!empty($to_date)) {
    $html .= "To Date: " . $escape($to_date);
} else {
    $html .= "All Customers";
}

$html .= '
</div>
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Customer Code</th>
    <th>Customer Type</th>
    <th>Status</th>
    <th>Company</th>
    <th>Contact</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Credit Limit</th>
    <th>Created Date</th>
    <th>Notes</th>
</tr>
</thead>
<tbody>';

foreach ($customers as $customer) {
    $contact_name = "";

    if (!empty($customer["first_name"])) {
        $contact_name = trim(
            $customer["first_name"] . " " . ($customer["last_name"] ?? "")
        );
    }

    $created_date = !empty($customer["created_at"])
        ? date("d-m-Y", strtotime($customer["created_at"]))
        : "";

    $html .= '
<tr>
    <td>' . $escape($customer["id"]) . '</td>
    <td>' . $escape($customer["customer_code"]) . '</td>
    <td>' . $escape($customer["customer_type"]) . '</td>
    <td>' . $escape($customer["status"]) . '</td>
    <td>' . $escape($customer["company_name"]) . '</td>
    <td>' . $escape($contact_name) . '</td>
    <td>' . $escape($customer["email"]) . '</td>
    <td>' . $escape($customer["phone"]) . '</td>
    <td>' . $escape(number_format((float) ($customer["credit_limit"] ?? 0), 2)) . '</td>
    <td>' . $escape($created_date) . '</td>
    <td>' . $escape($customer["notes"]) . '</td>
</tr>';
}

$html .= '
</tbody>
</table>
<div class="total">Total Customers: ' . count($customers) . '</div>
</body>
</html>';

$options = new Options();
$options->set("defaultFont", "DejaVu Sans");
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$dompdf->stream("customers_report.pdf", ["Attachment" => true]);

exit;
?>
