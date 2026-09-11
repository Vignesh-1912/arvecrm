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
    $where[] = "DATE(deals.expected_close_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(deals.expected_close_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        deals.id,
        deals.title,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        deals.amount,
        deals.stage,
        deals.probability,
        deals.expected_close_date,
        users.name AS assigned_user,
        deals.description
    FROM deals
    LEFT JOIN companies ON deals.company_id = companies.id
    LEFT JOIN contacts ON deals.contact_id = contacts.id
    LEFT JOIN users ON deals.assigned_to = users.id
    $where_sql
    ORDER BY deals.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
};

$options = new Options();
$options->set("defaultFont", "Arial");
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
h1 { text-align: center; margin-bottom: 5px; font-size: 20px; }
.report-info { text-align: center; margin-bottom: 20px; color: #666; }
table { width: 100%; border-collapse: collapse; }
th { background: #111827; color: white; padding: 7px; text-align: left; font-size: 9px; }
td { border: 1px solid #ddd; padding: 6px; font-size: 8px; }
tr:nth-child(even) { background: #f9fafb; }
.total-row { font-weight: bold; background: #f3f4f6; }
.footer { margin-top: 20px; text-align: center; font-size: 8px; color: #777; }
</style>
</head>
<body>
<h1>Deals Report</h1>';

if (!empty($from_date) && !empty($to_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . ' &nbsp;&nbsp; To: ' . $escape($to_date) . '</div>';
} elseif (!empty($from_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . '</div>';
} elseif (!empty($to_date)) {
    $html .= '<div class="report-info">Until: ' . $escape($to_date) . '</div>';
} else {
    $html .= '<div class="report-info">All Deals</div>';
}

$html .= '
<table>
<thead>
<tr>
<th>ID</th><th>Deal Title</th><th>Company</th><th>Contact</th><th>Amount</th>
<th>Stage</th><th>Probability</th><th>Expected Close Date</th><th>Assigned To</th><th>Description</th>
</tr>
</thead>
<tbody>';

$grand_total = 0;

foreach ($deals as $deal) {
    $contact_name = "";

    if (!empty($deal["first_name"])) {
        $contact_name = trim($deal["first_name"] . " " . ($deal["last_name"] ?? ""));
    }

    $grand_total += (float) $deal["amount"];

    $html .= "<tr>";
    $html .= "<td>" . $escape($deal["id"]) . "</td>";
    $html .= "<td>" . $escape($deal["title"]) . "</td>";
    $html .= "<td>" . $escape($deal["company_name"]) . "</td>";
    $html .= "<td>" . $escape($contact_name) . "</td>";
    $html .= "<td>₹" . number_format((float) $deal["amount"], 2) . "</td>";
    $html .= "<td>" . $escape($deal["stage"]) . "</td>";
    $html .= "<td>" . $escape($deal["probability"]) . "%</td>";
    $html .= "<td>" . $escape($deal["expected_close_date"]) . "</td>";
    $html .= "<td>" . $escape($deal["assigned_user"]) . "</td>";
    $html .= "<td>" . $escape($deal["description"]) . "</td>";
    $html .= "</tr>";
}

$html .= '
<tr class="total-row">
<td colspan="4" style="text-align:right;">Grand Total</td>
<td>₹' . number_format($grand_total, 2) . '</td>
<td colspan="5"></td>
</tr>
</tbody>
</table>
<div class="footer">CRM Deals Report</div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$filename = "deals_report";

if (!empty($from_date) && !empty($to_date)) {
    $filename .= "_" . $from_date . "_to_" . $to_date;
} elseif (!empty($from_date)) {
    $filename .= "_from_" . $from_date;
} elseif (!empty($to_date)) {
    $filename .= "_until_" . $to_date;
}

$dompdf->stream($filename . ".pdf", ["Attachment" => true]);

exit;
?>
