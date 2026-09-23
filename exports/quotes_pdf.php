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
    $where[] = "DATE(quotes.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(quotes.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where)
    ? " WHERE " . implode(" AND ", $where)
    : "";

$sql = "
    SELECT
        quotes.id,
        quotes.quote_number,
        customers.customer_code,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        deals.title AS deal_title,
        quotes.subtotal,
        quotes.tax_amount,
        quotes.discount_amount,
        quotes.total_amount,
        quotes.status,
        quotes.valid_until,
        quotes.created_at,
        quotes.notes
    FROM quotes
    LEFT JOIN customers
        ON quotes.customer_id = customers.id
    LEFT JOIN companies
        ON quotes.company_id = companies.id
    LEFT JOIN contacts
        ON quotes.contact_id = contacts.id
    LEFT JOIN deals
        ON quotes.deal_id = deals.id
    $where_sql
    ORDER BY quotes.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
};

$format_amount = static function ($value): string {
    return number_format((float) ($value ?? 0), 2);
};

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8px;
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
    padding: 5px;
    border: 1px solid #ddd;
    text-align: left;
}
td {
    padding: 4px;
    border: 1px solid #ddd;
    vertical-align: top;
}
tr:nth-child(even) {
    background-color: #f5f5f5;
}
.total {
    margin-top: 15px;
    font-weight: bold;
    font-size: 10px;
}
</style>
</head>
<body>
<h1>Quotes Report</h1>
<div class="date-filter">';

if (!empty($from_date) && !empty($to_date)) {
    $html .= "Date Range: " . $escape($from_date) . " to " . $escape($to_date);
} elseif (!empty($from_date)) {
    $html .= "From Date: " . $escape($from_date);
} elseif (!empty($to_date)) {
    $html .= "To Date: " . $escape($to_date);
} else {
    $html .= "All Quotes";
}

$html .= '
</div>
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Quote Number</th>
    <th>Customer Code</th>
    <th>Company</th>
    <th>Contact</th>
    <th>Deal</th>
    <th>Subtotal</th>
    <th>Tax Amount</th>
    <th>Discount Amount</th>
    <th>Total Amount</th>
    <th>Status</th>
    <th>Valid Until</th>
    <th>Created Date</th>
    <th>Notes</th>
</tr>
</thead>
<tbody>';

foreach ($quotes as $quote) {
    $contact_name = "";

    if (!empty($quote["first_name"])) {
        $contact_name = trim(
            $quote["first_name"] . " " . ($quote["last_name"] ?? "")
        );
    }

    $html .= '
<tr>
    <td>' . $escape($quote["id"]) . '</td>
    <td>' . $escape($quote["quote_number"]) . '</td>
    <td>' . $escape($quote["customer_code"]) . '</td>
    <td>' . $escape($quote["company_name"]) . '</td>
    <td>' . $escape($contact_name) . '</td>
    <td>' . $escape($quote["deal_title"]) . '</td>
    <td>' . $escape($format_amount($quote["subtotal"])) . '</td>
    <td>' . $escape($format_amount($quote["tax_amount"])) . '</td>
    <td>' . $escape($format_amount($quote["discount_amount"])) . '</td>
    <td>' . $escape($format_amount($quote["total_amount"])) . '</td>
    <td>' . $escape($quote["status"]) . '</td>
    <td>' . $escape($quote["valid_until"]) . '</td>
    <td>' . $escape($quote["created_at"]) . '</td>
    <td>' . $escape($quote["notes"]) . '</td>
</tr>';
}

$html .= '
</tbody>
</table>
<div class="total">Total Quotes: ' . count($quotes) . '</div>
</body>
</html>';

$options = new Options();
$options->set("defaultFont", "DejaVu Sans");
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$dompdf->stream("quotes_report.pdf", ["Attachment" => true]);

exit;
?>
