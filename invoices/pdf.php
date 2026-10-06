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

if (!isset($_GET["id"]) || !ctype_digit((string) $_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

$stmt = $conn->prepare("
    SELECT
        i.*,
        co.company_name,
        co.phone AS company_phone,
        co.email AS company_email,
        co.website AS company_website,
        co.address AS company_address,
        co.city AS company_city,
        co.state AS company_state,
        co.country AS company_country,
        co.postal_code AS company_postal_code,
        cu.customer_code,
        cu.customer_type,
        cu.status AS customer_status,
        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,
        ct.email AS contact_email,
        ct.phone AS contact_phone,
        ct.address AS contact_address,
        ct.city AS contact_city,
        ct.state AS contact_state,
        ct.country AS contact_country,
        ct.postal_code AS contact_postal_code,
        u.name AS created_by_name
    FROM invoices i
    LEFT JOIN companies co
        ON co.id = i.company_id
    LEFT JOIN customers cu
        ON cu.id = i.customer_id
    LEFT JOIN contacts ct
        ON ct.id = i.contact_id
    LEFT JOIN users u
        ON u.id = i.created_by
    WHERE i.id = :id
");
$stmt->execute([":id" => $id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        ii.description,
        ii.quantity,
        ii.unit_price,
        ii.discount,
        ii.tax,
        ii.total,
        p.name AS product_name,
        p.sku
    FROM invoice_items ii
    LEFT JOIN products p
        ON p.id = ii.product_id
    WHERE ii.invoice_id = :invoice_id
    ORDER BY ii.id ASC
");
$stmt->execute([":invoice_id" => $id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
};

$format_amount = static function ($value): string {
    return number_format((float) ($value ?? 0), 2);
};

$address = static function (array $record, string $prefix): string {
    $keys = [
        "address",
        "city",
        "state",
        "country",
        "postal_code"
    ];

    $parts = [];
    foreach ($keys as $key) {
        $value = trim((string) ($record[$prefix . $key] ?? ""));
        if ($value !== "") {
            $parts[] = $value;
        }
    }

    return implode(", ", $parts);
};

$company_name = trim((string) ($invoice["company_name"] ?? ""));
$contact_name = trim((string) ($invoice["contact_name"] ?? ""));
$company_address = $address($invoice, "company_");
$contact_address = $address($invoice, "contact_");
$invoice_date = !empty($invoice["invoice_date"])
    ? date("d M Y", strtotime($invoice["invoice_date"]))
    : "Not specified";
$status = ucfirst(trim((string) ($invoice["invoice_status"] ?? "draft")));

$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 28px 32px 42px; }
    body { font-family: "DejaVu Sans", sans-serif; color: #172033; font-size: 9px; }
    .header { background: #172554; color: #fff; padding: 16px 18px; }
    .header-table, .parties, .items, .totals { width: 100%; border-collapse: collapse; }
    .brand { font-size: 19px; font-weight: bold; color: #fff; }
    .tagline { margin-top: 4px; color: #dbeafe; font-size: 8px; }
    .meta { text-align: right; color: #fff; line-height: 1.7; }
    .meta-label { color: #cbd5e1; }
    .parties { margin: 13px 0; }
    .parties td { width: 50%; vertical-align: top; }
    .party { border: 1px solid #dbe3ee; background: #f8fafc; padding: 10px 12px; }
    .party-title { color: #2563eb; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 7px; }
    .party-name { font-size: 11px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
    .party-line { color: #475569; line-height: 1.45; }
    h2 { font-size: 11px; margin: 14px 0 7px; color: #0f172a; }
    .items th { background: #eaf0fb; color: #172554; text-align: left; text-transform: uppercase; font-size: 7px; padding: 7px 6px; border-top: 1px solid #172554; border-bottom: 1px solid #cbd5e1; }
    .items td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .items tr { page-break-inside: avoid; }
    .numeric { text-align: right; white-space: nowrap; }
    .product { font-weight: bold; color: #0f172a; }
    .description { color: #64748b; font-size: 8px; margin-top: 3px; }
    .bottom { width: 100%; margin-top: 12px; }
    .bottom td { vertical-align: top; }
    .notes { width: 60%; border-left: 3px solid #60a5fa; background: #f8fafc; padding: 9px 11px; line-height: 1.5; }
    .total-wrap { width: 40%; }
    .totals td { padding: 5px 7px; }
    .totals td:last-child { text-align: right; white-space: nowrap; }
    .grand td { border-top: 2px solid #172554; font-weight: bold; color: #172554; font-size: 11px; padding-top: 8px; }
    .footer { position: fixed; bottom: -24px; left: 0; right: 0; padding-top: 7px; border-top: 1px solid #dbe3ee; color: #64748b; font-size: 7px; }
</style>
</head>
<body>
<div class="header">
    <table class="header-table"><tr>
        <td><div class="brand">INVOICE</div><div class="tagline">ARVE CRM · BILLING STATEMENT</div></td>
        <td class="meta">
            <span class="meta-label">Invoice number</span> &nbsp; <strong>' . $escape($invoice["invoice_number"]) . '</strong><br>
            <span class="meta-label">Date</span> &nbsp; ' . $escape($invoice_date) . '<br>
            <span class="meta-label">Status</span> &nbsp; ' . $escape($status) . '
        </td>
    </tr></table>
</div>

<table class="parties"><tr>
    <td style="padding-right:6px;"><div class="party">
        <div class="party-title">Company Information</div>
        <div class="party-name">' . $escape($company_name !== "" ? $company_name : "Company not linked") . '</div>
        ' . ($company_address !== "" ? '<div class="party-line">' . $escape($company_address) . '</div>' : '') . '
        ' . (!empty($invoice["company_phone"]) ? '<div class="party-line">Phone: ' . $escape($invoice["company_phone"]) . '</div>' : '') . '
        ' . (!empty($invoice["company_email"]) ? '<div class="party-line">Email: ' . $escape($invoice["company_email"]) . '</div>' : '') . '
        ' . (!empty($invoice["company_website"]) ? '<div class="party-line">Web: ' . $escape($invoice["company_website"]) . '</div>' : '') . '
    </div></td>
    <td style="padding-left:6px;"><div class="party">
        <div class="party-title">Customer Information</div>
        <div class="party-name">' . $escape($invoice["customer_code"] ?? "Customer not linked") . '</div>
        ' . (!empty($invoice["customer_type"]) ? '<div class="party-line">Type: ' . $escape($invoice["customer_type"]) . '</div>' : '') . '
        ' . (!empty($invoice["customer_status"]) ? '<div class="party-line">Status: ' . $escape($invoice["customer_status"]) . '</div>' : '') . '
        ' . ($contact_name !== "" ? '<div class="party-line">Contact: ' . $escape($contact_name) . '</div>' : '') . '
        ' . (!empty($invoice["contact_email"]) ? '<div class="party-line">Email: ' . $escape($invoice["contact_email"]) . '</div>' : '') . '
        ' . (!empty($invoice["contact_phone"]) ? '<div class="party-line">Phone: ' . $escape($invoice["contact_phone"]) . '</div>' : '') . '
        ' . ($contact_address !== "" ? '<div class="party-line">Address: ' . $escape($contact_address) . '</div>' : '') . '
    </div></td>
</tr></table>

<h2>Products &amp; Services</h2>
<table class="items">
<thead><tr>
    <th style="width:4%;">#</th>
    <th style="width:19%;">Product</th>
    <th style="width:10%;">SKU</th>
    <th style="width:27%;">Description</th>
    <th class="numeric" style="width:8%;">Qty</th>
    <th class="numeric" style="width:12%;">Unit price</th>
    <th class="numeric" style="width:9%;">Tax</th>
    <th class="numeric" style="width:11%;">Total</th>
</tr></thead><tbody>';

if ($items) {
    foreach ($items as $index => $item) {
        $product_name = trim((string) ($item["product_name"] ?? ""));
        $description = trim((string) ($item["description"] ?? ""));
        $html .= '<tr>
            <td>' . ($index + 1) . '</td>
            <td><div class="product">' . $escape($product_name !== "" ? $product_name : "-") . '</div></td>
            <td>' . $escape($item["sku"] ?? "-") . '</td>
            <td><div class="description">' . nl2br($escape($description !== "" ? $description : "-")) . '</div></td>
            <td class="numeric">' . $format_amount($item["quantity"]) . '</td>
            <td class="numeric">₹ ' . $format_amount($item["unit_price"]) . '</td>
            <td class="numeric">₹ ' . $format_amount($item["tax"]) . '</td>
            <td class="numeric"><strong>₹ ' . $format_amount($item["total"]) . '</strong></td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:16px;">No products are attached to this invoice.</td></tr>';
}

$html .= '</tbody></table>
<table class="bottom"><tr>
    <td class="notes">' . (!empty($invoice["notes"]) ? '<strong>Notes</strong><br>' . nl2br($escape($invoice["notes"])) : 'Thank you for your business.') . '</td>
    <td class="total-wrap"><table class="totals">
        <tr><td>Subtotal</td><td>₹ ' . $format_amount($invoice["subtotal"]) . '</td></tr>
        <tr><td>Tax</td><td>₹ ' . $format_amount($invoice["tax_amount"]) . '</td></tr>
        <tr><td>Discount</td><td>− ₹ ' . $format_amount($invoice["discount_amount"]) . '</td></tr>
        <tr class="grand"><td>Total Due</td><td>₹ ' . $format_amount($invoice["total_amount"]) . '</td></tr>
    </table></td>
</tr></table>
<div class="footer">Prepared by ' . $escape($invoice["created_by_name"] ?? "Arve CRM") . ' &nbsp; · &nbsp; Invoice ' . $escape($invoice["invoice_number"]) . '</div>
</body></html>';

$options = new Options();
$options->set("defaultFont", "DejaVu Sans");
$options->set("isRemoteEnabled", false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    preg_replace("/[^A-Za-z0-9_-]/", "_", (string) $invoice["invoice_number"]) . ".pdf",
    ["Attachment" => false]
);
