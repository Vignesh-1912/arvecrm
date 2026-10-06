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
        q.id,
        q.quote_number,
        q.subtotal,
        q.tax_amount,
        q.discount_amount,
        q.total_amount,
        q.status,
        q.valid_until,
        q.created_at,
        q.notes,
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
        d.title AS deal_title,
        u.name AS created_by_name
    FROM quotes q
    LEFT JOIN companies co
        ON co.id = q.company_id
    LEFT JOIN customers cu
        ON cu.id = q.customer_id
    LEFT JOIN contacts ct
        ON ct.id = q.contact_id
    LEFT JOIN deals d
        ON d.id = q.deal_id
    LEFT JOIN users u
        ON u.id = q.created_by
    WHERE q.id = :id
");
$stmt->execute([":id" => $id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        qi.description,
        qi.quantity,
        qi.unit_price,
        qi.discount,
        qi.tax,
        qi.total,
        p.name AS product_name,
        p.sku
    FROM quote_items qi
    LEFT JOIN products p
        ON p.id = qi.product_id
    WHERE qi.quote_id = :quote_id
    ORDER BY qi.id ASC
");
$stmt->execute([":quote_id" => $id]);
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

$company_name = trim((string) ($quote["company_name"] ?? ""));
$contact_name = trim((string) ($quote["contact_name"] ?? ""));
$company_address = $address($quote, "company_");
$contact_address = $address($quote, "contact_");
$status = ucfirst(trim((string) ($quote["status"] ?? "draft")));
$quote_date = !empty($quote["created_at"])
    ? date("d M Y", strtotime($quote["created_at"]))
    : "Not specified";
$valid_until = !empty($quote["valid_until"])
    ? date("d M Y", strtotime($quote["valid_until"]))
    : "Not specified";

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
        <td><div class="brand">QUOTATION</div><div class="tagline">ARVE CRM · SALES PROPOSAL</div></td>
        <td class="meta">
            <span class="meta-label">Quote number</span> &nbsp; <strong>' . $escape($quote["quote_number"]) . '</strong><br>
            <span class="meta-label">Date</span> &nbsp; ' . $escape($quote_date) . '<br>
            <span class="meta-label">Valid until</span> &nbsp; ' . $escape($valid_until) . '<br>
            <span class="meta-label">Status</span> &nbsp; ' . $escape($status) . '
        </td>
    </tr></table>
</div>

<table class="parties"><tr>
    <td style="padding-right:6px;"><div class="party">
        <div class="party-title">Company Information</div>
        <div class="party-name">' . $escape($company_name !== "" ? $company_name : "Company not linked") . '</div>
        ' . ($company_address !== "" ? '<div class="party-line">' . $escape($company_address) . '</div>' : '') . '
        ' . (!empty($quote["company_phone"]) ? '<div class="party-line">Phone: ' . $escape($quote["company_phone"]) . '</div>' : '') . '
        ' . (!empty($quote["company_email"]) ? '<div class="party-line">Email: ' . $escape($quote["company_email"]) . '</div>' : '') . '
        ' . (!empty($quote["company_website"]) ? '<div class="party-line">Web: ' . $escape($quote["company_website"]) . '</div>' : '') . '
    </div></td>
    <td style="padding-left:6px;"><div class="party">
        <div class="party-title">Customer Information</div>
        <div class="party-name">' . $escape($quote["customer_code"] ?? "Customer not linked") . '</div>
        ' . (!empty($quote["customer_type"]) ? '<div class="party-line">Type: ' . $escape($quote["customer_type"]) . '</div>' : '') . '
        ' . ($contact_name !== "" ? '<div class="party-line">Contact: ' . $escape($contact_name) . '</div>' : '') . '
        ' . (!empty($quote["contact_email"]) ? '<div class="party-line">Email: ' . $escape($quote["contact_email"]) . '</div>' : '') . '
        ' . (!empty($quote["contact_phone"]) ? '<div class="party-line">Phone: ' . $escape($quote["contact_phone"]) . '</div>' : '') . '
        ' . ($contact_address !== "" ? '<div class="party-line">Address: ' . $escape($contact_address) . '</div>' : '') . '
    </div></td>
</tr></table>

<h2>Quoted Products &amp; Services</h2>
<table class="items">
<thead><tr>
    <th style="width:4%;">#</th>
    <th style="width:17%;">Product</th>
    <th style="width:9%;">SKU</th>
    <th style="width:25%;">Description</th>
    <th class="numeric" style="width:7%;">Qty</th>
    <th class="numeric" style="width:11%;">Unit price</th>
    <th class="numeric" style="width:9%;">Discount</th>
    <th class="numeric" style="width:8%;">Tax</th>
    <th class="numeric" style="width:10%;">Total</th>
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
            <td class="numeric">₹ ' . $format_amount($item["discount"]) . '</td>
            <td class="numeric">₹ ' . $format_amount($item["tax"]) . '</td>
            <td class="numeric"><strong>₹ ' . $format_amount($item["total"]) . '</strong></td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="9" style="text-align:center;color:#64748b;padding:16px;">No products have been added to this quote.</td></tr>';
}

$html .= '</tbody></table>
<table class="bottom"><tr>
    <td class="notes">' . (!empty($quote["notes"]) ? '<strong>Notes</strong><br>' . nl2br($escape($quote["notes"])) : 'Thank you for considering our proposal.') . '</td>
    <td class="total-wrap"><table class="totals">
        <tr><td>Subtotal</td><td>₹ ' . $format_amount($quote["subtotal"]) . '</td></tr>
        <tr><td>Tax</td><td>₹ ' . $format_amount($quote["tax_amount"]) . '</td></tr>
        <tr><td>Discount</td><td>− ₹ ' . $format_amount($quote["discount_amount"]) . '</td></tr>
        <tr class="grand"><td>Total</td><td>₹ ' . $format_amount($quote["total_amount"]) . '</td></tr>
    </table></td>
</tr></table>
<div class="footer">Prepared by ' . $escape($quote["created_by_name"] ?? "Arve CRM") . ' &nbsp; · &nbsp; Quote ' . $escape($quote["quote_number"]) . '</div>
</body></html>';

$options = new Options();
$options->set("defaultFont", "DejaVu Sans");
$options->set("isRemoteEnabled", false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream(
    preg_replace("/[^A-Za-z0-9_-]/", "_", (string) $quote["quote_number"]) . ".pdf",
    ["Attachment" => false]
);
