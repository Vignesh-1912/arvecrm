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
    $where[] = "DATE(sales.sale_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(sales.sale_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        sales.id,
        sales.sale_number,
        customers.customer_code,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        sales.subtotal,
        sales.tax_amount,
        sales.discount_amount,
        sales.total_amount,
        sales.payment_status,
        sales.sale_status,
        sales.sale_date
    FROM sales
    LEFT JOIN customers ON sales.customer_id = customers.id
    LEFT JOIN companies ON sales.company_id = companies.id
    LEFT JOIN contacts ON sales.contact_id = contacts.id
    $where_sql
    ORDER BY sales.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<h1>Sales Report</h1>';

if (!empty($from_date) && !empty($to_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . ' &nbsp;&nbsp; To: ' . $escape($to_date) . '</div>';
} elseif (!empty($from_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . '</div>';
} elseif (!empty($to_date)) {
    $html .= '<div class="report-info">Until: ' . $escape($to_date) . '</div>';
} else {
    $html .= '<div class="report-info">All Sales</div>';
}

$html .= '
<table>
<thead>
<tr>
<th>ID</th><th>Sale Number</th><th>Customer</th><th>Company</th><th>Contact</th>
<th>Subtotal</th><th>Tax</th><th>Discount</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th>
</tr>
</thead>
<tbody>';

$grand_total = 0;

foreach ($sales as $sale) {
    $contact_name = "";

    if (!empty($sale["first_name"])) {
        $contact_name = trim($sale["first_name"] . " " . ($sale["last_name"] ?? ""));
    }

    $grand_total += (float) $sale["total_amount"];

    $html .= "<tr>";
    $html .= "<td>" . $escape($sale["id"]) . "</td>";
    $html .= "<td>" . $escape($sale["sale_number"]) . "</td>";
    $html .= "<td>" . $escape($sale["customer_code"]) . "</td>";
    $html .= "<td>" . $escape($sale["company_name"]) . "</td>";
    $html .= "<td>" . $escape($contact_name) . "</td>";
    $html .= "<td>₹" . number_format((float) $sale["subtotal"], 2) . "</td>";
    $html .= "<td>₹" . number_format((float) $sale["tax_amount"], 2) . "</td>";
    $html .= "<td>₹" . number_format((float) $sale["discount_amount"], 2) . "</td>";
    $html .= "<td>₹" . number_format((float) $sale["total_amount"], 2) . "</td>";
    $html .= "<td>" . $escape($sale["payment_status"]) . "</td>";
    $html .= "<td>" . $escape($sale["sale_status"]) . "</td>";
    $html .= "<td>" . $escape($sale["sale_date"]) . "</td>";
    $html .= "</tr>";
}

$html .= '
<tr class="total-row">
<td colspan="8" style="text-align:right;">Grand Total</td>
<td>₹' . number_format($grand_total, 2) . '</td>
<td colspan="3"></td>
</tr>
</tbody>
</table>
<div class="footer">CRM Sales Report</div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$filename = "sales_report";

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
