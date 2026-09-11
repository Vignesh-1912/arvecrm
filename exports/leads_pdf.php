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
    $where[] = "DATE(leads.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(leads.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

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
    LEFT JOIN companies ON leads.company_id = companies.id
    LEFT JOIN contacts ON leads.contact_id = contacts.id
    LEFT JOIN users ON leads.assigned_to = users.id
    $where_sql
    ORDER BY leads.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
th { background: #111827; color: white; padding: 7px; text-align: left; font-size: 8px; }
td { border: 1px solid #ddd; padding: 5px; font-size: 7px; }
tr:nth-child(even) { background: #f9fafb; }
.total-row { font-weight: bold; background: #f3f4f6; }
.footer { margin-top: 20px; text-align: center; font-size: 8px; color: #777; }
</style>
</head>
<body>
<h1>Leads Report</h1>';

if (!empty($from_date) && !empty($to_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . ' &nbsp;&nbsp; To: ' . $escape($to_date) . '</div>';
} elseif (!empty($from_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . '</div>';
} elseif (!empty($to_date)) {
    $html .= '<div class="report-info">Until: ' . $escape($to_date) . '</div>';
} else {
    $html .= '<div class="report-info">All Leads</div>';
}

$html .= '
<table>
<thead>
<tr>
<th>ID</th><th>Lead Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Contact</th>
<th>Source</th><th>Status</th><th>Lead Value</th><th>Created Date</th><th>Assigned To</th><th>Notes</th>
</tr>
</thead>
<tbody>';

$total_value = 0;

foreach ($leads as $lead) {
    $contact_name = "";

    if (!empty($lead["first_name"])) {
        $contact_name = trim($lead["first_name"] . " " . ($lead["last_name"] ?? ""));
    }

    $total_value += (float) $lead["lead_value"];

    $html .= "<tr>";
    $html .= "<td>" . $escape($lead["id"]) . "</td>";
    $html .= "<td>" . $escape($lead["lead_name"]) . "</td>";
    $html .= "<td>" . $escape($lead["email"]) . "</td>";
    $html .= "<td>" . $escape($lead["phone"]) . "</td>";
    $html .= "<td>" . $escape($lead["company_name"]) . "</td>";
    $html .= "<td>" . $escape($contact_name) . "</td>";
    $html .= "<td>" . $escape($lead["source"]) . "</td>";
    $html .= "<td>" . $escape($lead["status"]) . "</td>";
    $html .= "<td>₹" . number_format((float) $lead["lead_value"], 2) . "</td>";
    $html .= "<td>" . $escape($lead["created_at"]) . "</td>";
    $html .= "<td>" . $escape($lead["assigned_user"]) . "</td>";
    $html .= "<td>" . $escape($lead["notes"]) . "</td>";
    $html .= "</tr>";
}

$html .= '
<tr class="total-row">
<td colspan="8" style="text-align:right;">Total Lead Value</td>
<td>₹' . number_format($total_value, 2) . '</td>
<td colspan="3"></td>
</tr>
</tbody>
</table>
<div class="footer">CRM Leads Report</div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$filename = "leads_report";

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
