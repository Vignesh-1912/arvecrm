<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Check Deal ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Deal Details
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        deals.*,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        customers.customer_code,
        users.name AS assigned_name
    FROM deals

    LEFT JOIN companies
        ON deals.company_id = companies.id

    LEFT JOIN contacts
        ON deals.contact_id = contacts.id

    LEFT JOIN customers
        ON deals.customer_id = customers.id

    LEFT JOIN users
        ON deals.assigned_to = users.id

    WHERE deals.id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$deal = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Deal Not Found
|--------------------------------------------------------------------------
*/

if (!$deal) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Products Added To This Deal
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        deal_products.id,
        deal_products.quantity,
        deal_products.unit_price,
        deal_products.total,
        products.name,
        products.sku
    FROM deal_products

    INNER JOIN products
        ON products.id = deal_products.product_id

    WHERE deal_products.deal_id = :deal_id

    ORDER BY deal_products.id ASC
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":deal_id" => $id
]);

$deal_products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Calculate Products Total
|--------------------------------------------------------------------------
*/

$products_total = 0;

foreach ($deal_products as $item) {
    $products_total += (float) $item["total"];
}


/*
|--------------------------------------------------------------------------
| Deal Values
|--------------------------------------------------------------------------
*/

$deal_title = trim($deal["title"] ?? "");

$company_name = trim(
    $deal["company_name"] ?? ""
);

$contact_name = trim(
    ($deal["first_name"] ?? "") . " " .
    ($deal["last_name"] ?? "")
);

$customer_code = trim(
    $deal["customer_code"] ?? ""
);

$assigned_name = trim(
    $deal["assigned_name"] ?? ""
);

$amount = (float) ($deal["amount"] ?? 0);

$probability = (int) ($deal["probability"] ?? 0);

$stage = strtolower(
    trim($deal["stage"] ?? "new")
);

$expected_close_date =
    $deal["expected_close_date"] ?? "";

$description =
    trim($deal["description"] ?? "");

$created_at =
    $deal["created_at"] ?? "";

$updated_at =
    $deal["updated_at"] ?? "";


/*
|--------------------------------------------------------------------------
| Initial
|--------------------------------------------------------------------------
*/

$initial = $deal_title !== ""
    ? strtoupper(substr($deal_title, 0, 1))
    : "D";


/*
|--------------------------------------------------------------------------
| Stage Labels
|--------------------------------------------------------------------------
*/

$stageLabels = [
    "new" => "New",
    "prospecting" => "Prospecting",
    "qualification" => "Qualification",
    "proposal" => "Proposal",
    "negotiation" => "Negotiation",
    "closed_won" => "Closed Won",
    "closed_lost" => "Closed Lost"
];

$stageLabel =
    $stageLabels[$stage] ??
    ucfirst(str_replace("_", " ", $stage));


/*
|--------------------------------------------------------------------------
| Stage Classes
|--------------------------------------------------------------------------
*/

$stageClasses = [
    "new",
    "prospecting",
    "qualification",
    "proposal",
    "negotiation",
    "closed_won",
    "closed_lost"
];

$stageClass = in_array(
    $stage,
    $stageClasses,
    true
)
    ? $stage
    : "new";


/*
|--------------------------------------------------------------------------
| Product Count
|--------------------------------------------------------------------------
*/

$productCount = count($deal_products);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($deal_title); ?> - Deal - CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
        }

        .page-wrapper {
            max-width: 1450px;
            margin: 0 auto;
        }

        /* ------------------------------------------------------------
           Breadcrumb
        ------------------------------------------------------------ */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        /* ------------------------------------------------------------
           Header
        ------------------------------------------------------------ */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0;
            color: #172554;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .edit-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .edit-btn:hover {
            background: #1d4ed8;
        }

        .products-btn {
            background: #16a34a;
            color: #ffffff;
        }

        .products-btn:hover {
            background: #15803d;
        }

        .list-btn {
            background: #ffffff;
            border: 1px solid #dbe2ea;
            color: #334155;
        }

        .list-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Deal Hero
        ------------------------------------------------------------ */

        .deal-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 24px;
            margin-bottom: 22px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .deal-main {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .deal-avatar {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 29px;
            font-weight: 700;
        }

        .deal-info {
            min-width: 0;
        }

        .deal-info h2 {
            margin: 0;
            color: #172554;
            font-size: 23px;
            font-weight: 700;
        }

        .deal-subtitle {
            margin-top: 5px;
            color: #64748b;
            font-size: 14px;
        }

        .deal-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .deal-id {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .company-tag,
        .customer-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .company-tag {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .customer-tag {
            background: #f1f5f9;
            color: #475569;
        }

        /* ------------------------------------------------------------
           Stage Badge
        ------------------------------------------------------------ */

        .stage-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stage-badge.new {
            background: #e2e8f0;
            color: #475569;
        }

        .stage-badge.prospecting {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .stage-badge.qualification {
            background: #ede9fe;
            color: #6d28d9;
        }

        .stage-badge.proposal {
            background: #ffedd5;
            color: #c2410c;
        }

        .stage-badge.negotiation {
            background: #fef9c3;
            color: #a16207;
        }

        .stage-badge.closed_won {
            background: #dcfce7;
            color: #15803d;
        }

        .stage-badge.closed_lost {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* ------------------------------------------------------------
           Hero Amount
        ------------------------------------------------------------ */

        .hero-amount {
            text-align: right;
            flex-shrink: 0;
        }

        .hero-amount-label {
            display: block;
            margin-bottom: 5px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-amount-value {
            display: block;
            color: #15803d;
            font-size: 25px;
            font-weight: 700;
        }

        .hero-probability {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
        }

        /* ------------------------------------------------------------
           Summary Cards
        ------------------------------------------------------------ */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            padding: 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
        }

        .summary-label {
            display: block;
            margin-bottom: 7px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .summary-value {
            display: block;
            color: #1e293b;
            font-size: 15px;
            font-weight: 700;
            word-break: break-word;
        }

        .summary-value.amount {
            color: #15803d;
            font-size: 19px;
        }

        .empty-value {
            color: #94a3b8;
            font-weight: 500;
        }

        /* ------------------------------------------------------------
           Main Layout
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 22px;
            align-items: start;
        }

        .info-card,
        .side-card,
        .products-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .info-card {
            overflow: hidden;
        }

        .card-header {
            padding: 19px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        /* ------------------------------------------------------------
           Information Grid
        ------------------------------------------------------------ */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .info-item {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-item:nth-child(odd) {
            border-right: 1px solid #e5e7eb;
        }

        .info-item.full {
            grid-column: 1 / -1;
            border-right: none;
        }

        .info-label {
            display: block;
            margin-bottom: 7px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .info-value {
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }

        .notes-value {
            white-space: pre-line;
        }

        /* ------------------------------------------------------------
           Probability
        ------------------------------------------------------------ */

        .probability-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .probability-track {
            width: 130px;
            height: 7px;
            overflow: hidden;
            background: #e2e8f0;
            border-radius: 999px;
        }

        .probability-fill {
            height: 100%;
            background: #22c55e;
            border-radius: inherit;
        }

        .probability-number {
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        /* ------------------------------------------------------------
           Products Section
        ------------------------------------------------------------ */

        .products-card {
            margin-top: 22px;
            padding: 20px;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
        }

        .products-header h2 {
            margin: 0;
            color: #172554;
            font-size: 17px;
        }

        .products-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .product-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 39px;
            padding: 0 14px;
            background: #16a34a;
            border-radius: 8px;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .product-add-btn:hover {
            background: #15803d;
        }

        .products-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        .products-table th,
        .products-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .products-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .products-table td {
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .products-table tbody tr:hover {
            background: #f8fafc;
        }

        .product-name {
            color: #172554;
            font-weight: 600;
        }

        .sku {
            color: #64748b;
        }

        .product-qty {
            font-weight: 600;
        }

        .product-price,
        .product-total {
            font-weight: 600;
            white-space: nowrap;
        }

        .product-total {
            color: #15803d;
        }

        .products-total-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .products-total-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .products-total-value {
            color: #15803d;
            font-size: 20px;
            font-weight: 700;
        }

        .no-products {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
            color: #64748b;
            font-size: 13px;
        }

        .no-products-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ------------------------------------------------------------
           Side Cards
        ------------------------------------------------------------ */

        .side-card {
            padding: 20px;
            margin-bottom: 18px;
        }

        .side-title {
            margin-bottom: 17px;
        }

        .side-title h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .side-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ------------------------------------------------------------
           Assignment
        ------------------------------------------------------------ */

        .assigned-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .assigned-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .assigned-text strong {
            display: block;
            color: #334155;
            font-size: 13px;
        }

        .assigned-text span {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* ------------------------------------------------------------
           Quick Actions
        ------------------------------------------------------------ */

        .quick-action {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            min-height: 42px;
            padding: 0 12px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .quick-action:last-child {
            margin-bottom: 0;
        }

        .quick-action:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .quick-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ------------------------------------------------------------
           Timeline
        ------------------------------------------------------------ */

        .timeline-item {
            position: relative;
            padding-left: 28px;
            margin-bottom: 20px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 19px;
            width: 1px;
            height: calc(100% + 7px);
            background: #dbe2ea;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 3px;
            width: 15px;
            height: 15px;
            border: 3px solid #2563eb;
            border-radius: 50%;
            background: #dbeafe;
        }

        .timeline-label {
            display: block;
            margin-bottom: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .timeline-value {
            display: block;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        /* ------------------------------------------------------------
           Value Box
        ------------------------------------------------------------ */

        .value-highlight {
            padding: 15px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 9px;
        }

        .value-label {
            display: block;
            margin-bottom: 4px;
            color: #15803d;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value-amount {
            display: block;
            color: #166534;
            font-size: 23px;
            font-weight: 700;
        }

        .value-progress {
            height: 7px;
            margin-top: 10px;
            overflow: hidden;
            background: #dcfce7;
            border-radius: 999px;
        }

        .value-progress-fill {
            height: 100%;
            background: #22c55e;
            border-radius: inherit;
        }

        .value-progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            color: #64748b;
            font-size: 11px;
        }

        /* ------------------------------------------------------------
           Bottom Actions
        ------------------------------------------------------------ */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .bottom-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .bottom-edit {
            background: #2563eb;
            color: #ffffff;
        }

        .bottom-edit:hover {
            background: #1d4ed8;
        }

        .bottom-products {
            background: #16a34a;
            color: #ffffff;
        }

        .bottom-products:hover {
            background: #15803d;
        }

        .bottom-back {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #475569;
        }

        .bottom-back:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Dark Mode
        ------------------------------------------------------------ */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .deal-info h2,
        html.dark-mode .card-header h3,
        html.dark-mode .products-header h2,
        html.dark-mode .side-title h3,
        html.dark-mode .summary-value,
        html.dark-mode .assigned-text strong,
        html.dark-mode .timeline-value {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .deal-subtitle,
        html.dark-mode .deal-id,
        html.dark-mode .card-header p,
        html.dark-mode .products-header p,
        html.dark-mode .side-title p {
            color: #94a3b8;
        }

        html.dark-mode .deal-hero,
        html.dark-mode .summary-card,
        html.dark-mode .info-card,
        html.dark-mode .products-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .company-tag {
            background: #172554;
            color: #bfdbfe;
        }

        html.dark-mode .customer-tag {
            background: #1e293b;
            color: #cbd5e1;
        }

        html.dark-mode .info-item,
        html.dark-mode .timeline-item::before,
        html.dark-mode .products-total-row {
            border-color: #1f2937;
        }

        html.dark-mode .info-label,
        html.dark-mode .summary-label,
        html.dark-mode .products-table th {
            color: #64748b;
        }

        html.dark-mode .info-value,
        html.dark-mode .products-table td {
            color: #cbd5e1;
        }

        html.dark-mode .probability-track {
            background: #334155;
        }

        html.dark-mode .list-btn,
        html.dark-mode .bottom-back {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .list-btn:hover,
        html.dark-mode .bottom-back:hover {
            background: #1e293b;
        }

        html.dark-mode .products-table th {
            background: #0f172a;
        }

        html.dark-mode .products-table tbody tr:hover {
            background: #1e293b;
        }

        html.dark-mode .product-name {
            color: #f8fafc;
        }

        html.dark-mode .sku {
            color: #94a3b8;
        }

        html.dark-mode .no-products {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }

        html.dark-mode .no-products-icon {
            background: #1e293b;
        }

        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #1e293b;
        }

        html.dark-mode .quick-icon {
            background: #1e293b;
        }

        html.dark-mode .assigned-text span,
        html.dark-mode .timeline-label {
            color: #64748b;
        }

        html.dark-mode .timeline-item::before {
            background: #334155;
        }

        html.dark-mode .value-highlight {
            background: #052e16;
            border-color: #166534;
        }

        html.dark-mode .value-label {
            color: #86efac;
        }

        html.dark-mode .value-amount {
            color: #bbf7d0;
        }

        html.dark-mode .value-progress {
            background: #14532d;
        }

        html.dark-mode .value-progress-text {
            color: #64748b;
        }

        /* ------------------------------------------------------------
           Responsive
        ------------------------------------------------------------ */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .page-header {
                flex-direction: column;
            }

            .deal-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-amount {
                text-align: left;
            }

        }

        @media (max-width: 650px) {

            .main-content {
                padding: 20px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item:nth-child(odd) {
                border-right: none;
            }

            .info-item.full {
                grid-column: auto;
            }

            .products-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .product-add-btn {
                width: 100%;
            }

            .bottom-actions {
                flex-direction: column;
            }

            .bottom-btn {
                width: 100%;
            }

            .header-actions {
                width: 100%;
            }

            .header-btn {
                flex: 1;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-wrapper">


        <!-- =========================================================
             Breadcrumb
        ========================================================== -->

        <div class="breadcrumb">

            <a href="index.php">
                Deals
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                Deal Details
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Deal Details
                </h1>

                <p>
                    View the complete opportunity information and products.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="edit.php?id=<?php echo (int) $deal["id"]; ?>"
                    class="header-btn edit-btn"
                >
                    ✎ Edit Deal
                </a>

                <a
                    href="deal_products.php?deal_id=<?php echo $id; ?>"
                    class="header-btn products-btn"
                >
                    📦 Manage Products
                </a>

                <a
                    href="index.php"
                    class="header-btn list-btn"
                >
                    ← Deal List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Deal Hero
        ========================================================== -->

        <div class="deal-hero">

            <div class="deal-main">

                <div class="deal-avatar">

                    <?php echo htmlspecialchars($initial); ?>

                </div>


                <div class="deal-info">

                    <h2>
                        <?php echo htmlspecialchars($deal_title); ?>
                    </h2>


                    <div class="deal-subtitle">

                        <?php if ($company_name !== ""): ?>

                            <?php echo htmlspecialchars($company_name); ?>

                        <?php elseif ($contact_name !== ""): ?>

                            <?php echo htmlspecialchars($contact_name); ?>

                        <?php else: ?>

                            Sales opportunity

                        <?php endif; ?>

                    </div>


                    <div class="deal-meta">

                        <span class="deal-id">
                            Deal #<?php echo (int) $deal["id"]; ?>
                        </span>


                        <span
                            class="stage-badge <?php echo $stageClass; ?>"
                        >
                            <?php echo htmlspecialchars($stageLabel); ?>
                        </span>


                        <?php if ($company_name !== ""): ?>

                            <span class="company-tag">

                                🏢

                                <?php echo htmlspecialchars($company_name); ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($customer_code !== ""): ?>

                            <span class="customer-tag">

                                🧾

                                <?php echo htmlspecialchars($customer_code); ?>

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <div class="hero-amount">

                <span class="hero-amount-label">
                    Deal Amount
                </span>

                <span class="hero-amount-value">

                    ₹<?php echo number_format(
                        $amount,
                        2
                    ); ?>

                </span>

                <span class="hero-probability">

                    <?php echo $probability; ?>% probability

                </span>

            </div>

        </div>


        <!-- =========================================================
             Summary Cards
        ========================================================== -->

        <div class="summary-grid">


            <!-- Amount -->

            <div class="summary-card">

                <span class="summary-label">
                    Deal Amount
                </span>

                <span class="summary-value amount">

                    ₹<?php echo number_format(
                        $amount,
                        2
                    ); ?>

                </span>

            </div>


            <!-- Stage -->

            <div class="summary-card">

                <span class="summary-label">
                    Stage
                </span>

                <span class="summary-value">

                    <?php echo htmlspecialchars($stageLabel); ?>

                </span>

            </div>


            <!-- Probability -->

            <div class="summary-card">

                <span class="summary-label">
                    Probability
                </span>

                <span class="summary-value">

                    <?php echo $probability; ?>%

                </span>

            </div>


            <!-- Products -->

            <div class="summary-card">

                <span class="summary-label">
                    Products
                </span>

                <span class="summary-value">

                    <?php echo $productCount; ?>

                </span>

            </div>

        </div>


        <!-- =========================================================
             Main Content
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 LEFT SIDE
            ====================================================== -->

            <div>


                <!-- Deal Information -->

                <div class="info-card">

                    <div class="card-header">

                        <h3>
                            Deal Information
                        </h3>

                        <p>
                            Complete opportunity details and sales forecast.
                        </p>

                    </div>


                    <div class="info-grid">


                        <!-- Deal ID -->

                        <div class="info-item">

                            <span class="info-label">
                                Deal ID
                            </span>

                            <div class="info-value">
                                #<?php echo (int) $deal["id"]; ?>
                            </div>

                        </div>


                        <!-- Stage -->

                        <div class="info-item">

                            <span class="info-label">
                                Stage
                            </span>

                            <div class="info-value">

                                <span
                                    class="stage-badge <?php echo $stageClass; ?>"
                                >

                                    <?php echo htmlspecialchars($stageLabel); ?>

                                </span>

                            </div>

                        </div>


                        <!-- Deal Title -->

                        <div class="info-item full">

                            <span class="info-label">
                                Deal Title
                            </span>

                            <div class="info-value">
                                <?php echo htmlspecialchars($deal_title); ?>
                            </div>

                        </div>


                        <!-- Company -->

                        <div class="info-item">

                            <span class="info-label">
                                Company
                            </span>

                            <div class="info-value">

                                <?php

                                echo $company_name !== ""
                                    ? htmlspecialchars($company_name)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Contact -->

                        <div class="info-item">

                            <span class="info-label">
                                Contact
                            </span>

                            <div class="info-value">

                                <?php

                                echo $contact_name !== ""
                                    ? htmlspecialchars($contact_name)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Customer -->

                        <div class="info-item">

                            <span class="info-label">
                                Customer
                            </span>

                            <div class="info-value">

                                <?php

                                echo $customer_code !== ""
                                    ? htmlspecialchars($customer_code)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Assigned -->

                        <div class="info-item">

                            <span class="info-label">
                                Assigned To
                            </span>

                            <div class="info-value">

                                <?php

                                echo $assigned_name !== ""
                                    ? htmlspecialchars($assigned_name)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Amount -->

                        <div class="info-item">

                            <span class="info-label">
                                Amount
                            </span>

                            <div class="info-value">

                                ₹<?php echo number_format(
                                    $amount,
                                    2
                                ); ?>

                            </div>

                        </div>


                        <!-- Probability -->

                        <div class="info-item">

                            <span class="info-label">
                                Probability
                            </span>

                            <div class="info-value">

                                <div class="probability-wrap">

                                    <div class="probability-track">

                                        <div
                                            class="probability-fill"
                                            style="width: <?php echo min(
                                                100,
                                                max(
                                                    0,
                                                    $probability
                                                )
                                            ); ?>%;"
                                        >
                                        </div>

                                    </div>


                                    <span class="probability-number">

                                        <?php echo $probability; ?>%

                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- Expected Close -->

                        <div class="info-item">

                            <span class="info-label">
                                Expected Close Date
                            </span>

                            <div class="info-value">

                                <?php

                                echo $expected_close_date !== ""
                                    ? htmlspecialchars(
                                        $expected_close_date
                                    )
                                    : '<span class="empty-value">Not set</span>';

                                ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Description -->

                <div class="info-card">

                    <div class="card-header">

                        <h3>
                            Description
                        </h3>

                        <p>
                            Additional information about this opportunity.
                        </p>

                    </div>


                    <div class="info-grid">

                        <div class="info-item full">

                            <div class="info-value notes-value">

                                <?php if ($description !== ""): ?>

                                    <?php echo htmlspecialchars($description); ?>

                                <?php else: ?>

                                    <span class="empty-value">
                                        No description has been added.
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Products -->

                <div class="products-card">

                    <div class="products-header">

                        <div>

                            <h2>
                                Products In This Deal
                            </h2>

                            <p>
                                Products and quantities currently associated with this deal.
                            </p>

                        </div>


                        <a
                            href="deal_products.php?deal_id=<?php echo $id; ?>"
                            class="product-add-btn"
                        >
                            + Add Product
                        </a>

                    </div>


                    <?php if ($productCount > 0): ?>

                        <div class="products-table-container">

                            <table class="products-table">

                                <thead>

                                    <tr>

                                        <th>
                                            S.No.
                                        </th>

                                        <th>
                                            Product
                                        </th>

                                        <th>
                                            SKU
                                        </th>

                                        <th>
                                            Quantity
                                        </th>

                                        <th>
                                            Unit Price
                                        </th>

                                        <th>
                                            Total
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php $serial_no = 1; ?>


                                    <?php foreach ($deal_products as $item): ?>

                                        <tr>

                                            <td>
                                                <?php echo $serial_no++; ?>
                                            </td>


                                            <td class="product-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $item["name"]
                                                );
                                                ?>

                                            </td>


                                            <td class="sku">

                                                <?php

                                                echo !empty($item["sku"])
                                                    ? htmlspecialchars(
                                                        $item["sku"]
                                                    )
                                                    : "-";

                                                ?>

                                            </td>


                                            <td class="product-qty">

                                                <?php

                                                echo number_format(
                                                    (float) $item["quantity"],
                                                    2
                                                );

                                                ?>

                                            </td>


                                            <td class="product-price">

                                                ₹<?php

                                                echo number_format(
                                                    (float) $item["unit_price"],
                                                    2
                                                );

                                                ?>

                                            </td>


                                            <td class="product-total">

                                                ₹<?php

                                                echo number_format(
                                                    (float) $item["total"],
                                                    2
                                                );

                                                ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <div class="products-total-row">

                            <span class="products-total-label">
                                Products Total
                            </span>

                            <span class="products-total-value">

                                ₹<?php echo number_format(
                                    $products_total,
                                    2
                                ); ?>

                            </span>

                        </div>

                    <?php else: ?>

                        <div class="no-products">

                            <div class="no-products-icon">
                                📦
                            </div>

                            <span>
                                No products have been added to this deal yet.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- Bottom Actions -->

                <div class="bottom-actions">

                    <a
                        href="edit.php?id=<?php echo $id; ?>"
                        class="bottom-btn bottom-edit"
                    >
                        ✎ Edit Deal
                    </a>


                    <a
                        href="deal_products.php?deal_id=<?php echo $id; ?>"
                        class="bottom-btn bottom-products"
                    >
                        📦 Manage Products
                    </a>


                    <a
                        href="index.php"
                        class="bottom-btn bottom-back"
                    >
                        ← Back to Deals
                    </a>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDE
            ====================================================== -->

            <div>


                <!-- Deal Value -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Deal Value
                        </h3>

                        <p>
                            Current opportunity value and probability.
                        </p>

                    </div>


                    <div class="value-highlight">

                        <span class="value-label">
                            Deal Amount
                        </span>

                        <span class="value-amount">

                            ₹<?php echo number_format(
                                $amount,
                                2
                            ); ?>

                        </span>


                        <div class="value-progress">

                            <div
                                class="value-progress-fill"
                                style="width: <?php echo min(
                                    100,
                                    max(
                                        0,
                                        $probability
                                    )
                                ); ?>%;"
                            >
                            </div>

                        </div>


                        <div class="value-progress-text">

                            <span>
                                Probability
                            </span>

                            <span>
                                <?php echo $probability; ?>%
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Assignment -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Assigned To
                        </h3>

                        <p>
                            Team member responsible for this deal.
                        </p>

                    </div>


                    <div class="assigned-box">

                        <div class="assigned-avatar">

                            <?php

                            echo $assigned_name !== ""
                                ? strtoupper(
                                    substr(
                                        $assigned_name,
                                        0,
                                        1
                                    )
                                )
                                : "?";

                            ?>

                        </div>


                        <div class="assigned-text">

                            <strong>

                                <?php

                                echo $assigned_name !== ""
                                    ? htmlspecialchars($assigned_name)
                                    : "Not assigned";

                                ?>

                            </strong>

                            <span>
                                Deal owner
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Product Summary -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Product Summary
                        </h3>

                        <p>
                            Product information associated with this deal.
                        </p>

                    </div>


                    <div class="summary-card"
                         style="
                            box-shadow:none;
                            padding:0;
                            border:none;
                            background:transparent;
                         "
                    >

                        <span class="summary-label">
                            Products
                        </span>

                        <span class="summary-value">
                            <?php echo $productCount; ?>
                        </span>

                    </div>


                    <div
                        style="
                            margin-top:15px;
                            padding-top:15px;
                            border-top:1px solid #e5e7eb;
                         "
                    >

                        <span class="summary-label">
                            Products Total
                        </span>

                        <span
                            class="summary-value amount"
                            style="margin-top:5px;"
                        >

                            ₹<?php echo number_format(
                                $products_total,
                                2
                            ); ?>

                        </span>

                    </div>

                </div>


                <!-- Quick Actions -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Quick Actions
                        </h3>

                        <p>
                            Common deal actions.
                        </p>

                    </div>


                    <a
                        href="edit.php?id=<?php echo $id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✎
                        </span>

                        Edit Deal

                    </a>


                    <a
                        href="deal_products.php?deal_id=<?php echo $id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            📦
                        </span>

                        Manage Products

                    </a>


                    <a
                        href="index.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ←
                        </span>

                        Back to Deals

                    </a>

                </div>


                <!-- Record Timeline -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Record Timeline
                        </h3>

                        <p>
                            Deal record history.
                        </p>

                    </div>


                    <div class="timeline-item">

                        <span class="timeline-dot"></span>

                        <span class="timeline-label">
                            Created
                        </span>

                        <span class="timeline-value">

                            <?php

                            echo $created_at !== ""
                                ? htmlspecialchars($created_at)
                                : "Not available";

                            ?>

                        </span>

                    </div>


                    <div class="timeline-item">

                        <span class="timeline-dot"></span>

                        <span class="timeline-label">
                            Last Updated
                        </span>

                        <span class="timeline-value">

                            <?php

                            echo $updated_at !== ""
                                ? htmlspecialchars($updated_at)
                                : "Not available";

                            ?>

                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>