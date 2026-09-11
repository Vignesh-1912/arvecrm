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
        id,
        company_name,
        industry,
        phone,
        email,
        website,
        city,
        state,
        country,
        created_at
    FROM companies
    WHERE 1=1
";

$params = [];

if (!empty($from_date)) {
    $sql .= " AND DATE(created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
td { border: 1px solid #ddd; padding: 6px; font-size: 8px; }
tr:nth-child(even) { background: #f9fafb; }
.footer { margin-top: 20px; text-align: center; font-size: 8px; color: #777; }
</style>
</head>
<body>
<h1>Companies Report</h1>';

if (!empty($from_date) && !empty($to_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . ' &nbsp;&nbsp; To: ' . $escape($to_date) . '</div>';
} elseif (!empty($from_date)) {
    $html .= '<div class="report-info">From: ' . $escape($from_date) . '</div>';
} elseif (!empty($to_date)) {
    $html .= '<div class="report-info">Until: ' . $escape($to_date) . '</div>';
} else {
    $html .= '<div class="report-info">All Companies</div>';
}

$html .= '
<table>
<thead>
<tr>
<th>ID</th>
<th>Company Name</th>
<th>Industry</th>
<th>Phone</th>
<th>Email</th>
<th>Website</th>
<th>City</th>
<th>State</th>
<th>Country</th>
<th>Created Date</th>
</tr>
</thead>
<tbody>';

foreach ($companies as $company) {
    $html .= "<tr>";
    $html .= "<td>" . $escape($company["id"]) . "</td>";
    $html .= "<td>" . $escape($company["company_name"]) . "</td>";
    $html .= "<td>" . $escape($company["industry"]) . "</td>";
    $html .= "<td>" . $escape($company["phone"]) . "</td>";
    $html .= "<td>" . $escape($company["email"]) . "</td>";
    $html .= "<td>" . $escape($company["website"]) . "</td>";
    $html .= "<td>" . $escape($company["city"]) . "</td>";
    $html .= "<td>" . $escape($company["state"]) . "</td>";
    $html .= "<td>" . $escape($company["country"]) . "</td>";
    $html .= "<td>" . $escape(
        !empty($company["created_at"])
            ? date("d-m-Y", strtotime($company["created_at"]))
            : ""
    ) . "</td>";
    $html .= "</tr>";
}

$html .= '
</tbody>
</table>
<div class="footer">CRM Companies Report</div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$filename = "companies_report";

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
