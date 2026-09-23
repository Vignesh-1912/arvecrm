<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Get Quote Details
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            quotes.*,

            companies.company_name,

            contacts.first_name,
            contacts.last_name,
            contacts.email AS contact_email,
            contacts.phone AS contact_phone,

            customers.customer_code,

            deals.title AS deal_title,

            users.name AS created_by_name

        FROM quotes

        LEFT JOIN companies
            ON quotes.company_id = companies.id

        LEFT JOIN contacts
            ON quotes.contact_id = contacts.id

        LEFT JOIN customers
            ON quotes.customer_id = customers.id

        LEFT JOIN deals
            ON quotes.deal_id = deals.id

        LEFT JOIN users
            ON quotes.created_by = users.id

        WHERE quotes.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Quote Items
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            quote_items.*,
            products.name AS product_name,
            products.sku

        FROM quote_items

        LEFT JOIN products
            ON quote_items.product_id = products.id

        WHERE quote_items.quote_id = :quote_id

        ORDER BY quote_items.id ASC";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":quote_id" => $id
]);

$quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Calculate Items Subtotal
|--------------------------------------------------------------------------
*/

$items_subtotal = 0;

foreach ($quote_items as $item) {
    $items_subtotal += (float) $item["total"];
}

/*
|--------------------------------------------------------------------------
| Quote Values
|--------------------------------------------------------------------------
*/

$quoteNumber = $quote["quote_number"] ?? "";
$companyName = $quote["company_name"] ?? "";
$customerCode = $quote["customer_code"] ?? "";
$dealTitle = $quote["deal_title"] ?? "";
$contactName = "";

if (!empty($quote["first_name"])) {
    $contactName = trim(
        $quote["first_name"]
        . " "
        . ($quote["last_name"] ?? "")
    );
}

$status = strtolower($quote["status"] ?? "draft");

$allowed_status_classes = [
    "draft",
    "sent",
    "accepted",
    "rejected",
    "expired"
];

if (!in_array($status, $allowed_status_classes, true)) {
    $status = "draft";
}

$statusLabel = ucfirst($status);

$statusClass = "status-" . $status;

$subtotal = (float)($quote["subtotal"] ?? 0);
$taxAmount = (float)($quote["tax_amount"] ?? 0);
$discountAmount = (float)($quote["discount_amount"] ?? 0);
$totalAmount = (float)($quote["total_amount"] ?? 0);

$initial = strtoupper(
    substr(
        trim($quoteNumber) !== "" ? $quoteNumber : "Q",
        0,
        1
    )
);

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
        <?php echo htmlspecialchars($quoteNumber); ?> - Quote - CRM
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
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .main-content {
            padding: 28px 32px;
        }

        /* Breadcrumb */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Header */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }

        .page-title h1 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 27px;
            font-weight: 700;
        }

        .page-title p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Buttons */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #dbe2ea;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        /* Quote Hero */

        .quote-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 22px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .quote-avatar {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #2563eb;
            color: #ffffff;
            font-size: 23px;
            font-weight: 700;
        }

        .hero-info {
            min-width: 0;
        }

        .hero-info h2 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 21px;
            font-weight: 700;
            word-break: break-word;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .meta-separator {
            color: #cbd5e1;
        }

        /* Status */

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
        }

        .status-sent {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }

        /* Summary */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            padding: 17px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .summary-label {
            margin-bottom: 7px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            word-break: break-word;
        }

        .summary-value.green {
            color: #16a34a;
        }

        /* Main Grid */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Cards */

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .card + .card {
            margin-top: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 19px 21px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
            margin: 0 0 4px;
            color: #0f172a;
            font-size: 16px;
        }

        .card-header p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .card-body {
            padding: 21px;
        }

        /* Details */

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .detail-item {
            padding: 15px 14px;
            border-bottom: 1px solid #eef2f7;
        }

        .detail-item:nth-last-child(-n + 2) {
            border-bottom: none;
        }

        .detail-label {
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .detail-value {
            color: #1e293b;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }

        .detail-value strong {
            color: #0f172a;
            font-weight: 600;
        }

        .detail-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .detail-value a:hover {
            text-decoration: underline;
        }

        /* Notes */

        .notes-box {
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
            word-break: break-word;
        }

        .empty-notes {
            color: #94a3b8;
            font-style: italic;
        }

        /* Items */

        .items-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .items-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            padding: 12px 11px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: middle;
            font-size: 12px;
        }

        .items-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .items-table th:nth-child(1),
        .items-table td:nth-child(1) {
            width: 55px;
            text-align: center;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            width: 18%;
        }

        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            width: 13%;
        }

        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            width: 18%;
        }

        .items-table th:nth-child(5),
        .items-table td:nth-child(5) {
            width: 9%;
        }

        .items-table th:nth-child(6),
        .items-table td:nth-child(6) {
            width: 10%;
        }

        .items-table th:nth-child(7),
        .items-table td:nth-child(7) {
            width: 9%;
        }

        .items-table th:nth-child(8),
        .items-table td:nth-child(8) {
            width: 9%;
        }

        .items-table th:nth-child(9),
        .items-table td:nth-child(9) {
            width: 10%;
            text-align: right;
        }

        .product-name {
            color: #1e293b;
            font-weight: 600;
        }

        .sku-text {
            color: #64748b;
        }

        .currency {
            white-space: nowrap;
        }

        .item-total {
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
        }

        .no-items {
            padding: 22px;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
            background: #f8fafc;
            color: #64748b;
            text-align: center;
            font-size: 13px;
        }

        /* Summary Amount */

        .amount-summary {
            max-width: 390px;
            margin-left: auto;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
            color: #475569;
            font-size: 13px;
        }

        .summary-row span:last-child {
            color: #1e293b;
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-row.total {
            padding-top: 15px;
            border-bottom: none;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
        }

        .summary-row.total span:last-child {
            color: #16a34a;
            font-size: 20px;
        }

        /* Side */

        .side-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .side-card {
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .side-card h3 {
            margin: 0 0 15px;
            color: #0f172a;
            font-size: 15px;
        }

        /* Total Box */

        .total-box {
            padding: 18px;
            border: 1px solid #dcfce7;
            border-radius: 10px;
            background: linear-gradient(
                135deg,
                #f0fdf4,
                #f8fafc
            );
        }

        .total-label {
            margin-bottom: 5px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
        }

        .total-value {
            color: #15803d;
            font-size: 25px;
            font-weight: 700;
        }

        .validity-text {
            margin-top: 7px;
            color: #64748b;
            font-size: 12px;
        }

        /* Side Rows */

        .side-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 12px;
        }

        .side-row:last-child {
            border-bottom: none;
        }

        .side-row span:first-child {
            color: #64748b;
        }

        .side-row strong {
            color: #1e293b;
            text-align: right;
            word-break: break-word;
        }

        /* Quick Actions */

        .quick-actions {
            display: grid;
            gap: 9px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .quick-action:hover {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #2563eb;
        }

        .quick-icon {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #dbeafe;
            color: #2563eb;
            font-size: 12px;
        }

        /* Timeline */

        .timeline-item {
            position: relative;
            padding-left: 23px;
            padding-bottom: 17px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 5px;
            top: 7px;
            bottom: -1px;
            width: 1px;
            background: #dbe2ea;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 2px;
            width: 11px;
            height: 11px;
            border: 2px solid #dbeafe;
            border-radius: 50%;
            background: #2563eb;
        }

        .timeline-title {
            margin-bottom: 4px;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }

        .timeline-date {
            color: #94a3b8;
            font-size: 11px;
        }

        /* Bottom Actions */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
        }

        /* Dark Mode */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .hero-info h2,
        html.dark-mode .summary-value,
        html.dark-mode .card-header h3,
        html.dark-mode .side-card h3,
        html.dark-mode .detail-value,
        html.dark-mode .detail-value strong,
        html.dark-mode .side-row strong,
        html.dark-mode .timeline-title,
        html.dark-mode .items-table th,
        html.dark-mode .items-table td,
        html.dark-mode .summary-row,
        html.dark-mode .summary-row.total {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .card-header p,
        html.dark-mode .summary-label,
        html.dark-mode .detail-label,
        html.dark-mode .side-row span:first-child,
        html.dark-mode .timeline-date,
        html.dark-mode .hero-meta,
        html.dark-mode .validity-text,
        html.dark-mode .sku-text {
            color: #94a3b8;
        }

        html.dark-mode .quote-hero,
        html.dark-mode .summary-card,
        html.dark-mode .card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .detail-item,
        html.dark-mode .items-table th,
        html.dark-mode .items-table td,
        html.dark-mode .side-row,
        html.dark-mode .bottom-actions {
            border-color: #1f2937;
        }

        html.dark-mode .items-table th {
            background: #0f172a;
        }

        html.dark-mode .items-table td {
            background: #111827;
        }

        html.dark-mode .notes-box,
        html.dark-mode .no-items,
        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .notes-box,
        html.dark-mode .no-items {
            color: #cbd5e1;
        }

        html.dark-mode .quick-action {
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #172554;
            border-color: #1e40af;
            color: #93c5fd;
        }

        html.dark-mode .quick-icon {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .total-box {
            background: linear-gradient(
                135deg,
                #052e16,
                #111827
            );
            border-color: #14532d;
        }

        html.dark-mode .btn-secondary {
            background: #111827;
            color: #cbd5e1;
            border-color: #334155;
        }

        html.dark-mode .btn-secondary:hover {
            background: #1e293b;
        }

        html.dark-mode .timeline-item::before {
            background: #334155;
        }

        /* Responsive */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .side-column {
                display: grid;
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 768px) {

            .main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .quote-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .detail-item:nth-last-child(-n + 2) {
                border-bottom: 1px solid #eef2f7;
            }

            .detail-item:last-child {
                border-bottom: none;
            }

            .side-column {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 480px) {

            .main-content {
                padding: 15px;
            }

            .header-actions {
                flex-direction: column;
            }

            .header-actions .btn {
                width: 100%;
            }

            .hero-info h2 {
                font-size: 18px;
            }

            .quote-avatar {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
            }

            .card-body,
            .side-card {
                padding: 16px;
            }

            .bottom-actions {
                flex-direction: column;
            }

            .bottom-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- Breadcrumb -->

    <div class="breadcrumb">

        <a href="../dashboard/index.php">
            Dashboard
        </a>

        <span>/</span>

        <a href="index.php">
            Quotes
        </a>

        <span>/</span>

        <span>
            View Quote
        </span>

    </div>

    <!-- Page Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Quote Details
            </h1>

            <p>
                Review quote information, products, pricing and related records.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="edit.php?id=<?php echo $id; ?>"
                class="btn btn-primary"
            >
                ✎ Edit Quote
            </a>

            <a
                href="quote_items.php?quote_id=<?php echo $id; ?>"
                class="btn btn-success"
            >
                + Manage Products
            </a>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Quote List
            </a>

        </div>

    </div>

    <!-- Quote Hero -->

    <div class="quote-hero">

        <div class="hero-left">

            <div class="quote-avatar">
                <?php echo htmlspecialchars($initial); ?>
            </div>

            <div class="hero-info">

                <h2>
                    <?php echo htmlspecialchars($quoteNumber); ?>
                </h2>

                <div class="hero-meta">

                    <span>
                        Quote ID #<?php echo $id; ?>
                    </span>

                    <span class="meta-separator">
                        •
                    </span>

                    <?php if ($companyName !== ""): ?>

                        <span>
                            <?php echo htmlspecialchars($companyName); ?>
                        </span>

                        <span class="meta-separator">
                            •
                        </span>

                    <?php endif; ?>

                    <span
                        class="status <?php echo $statusClass; ?>"
                    >
                        <?php echo htmlspecialchars($statusLabel); ?>
                    </span>

                </div>

            </div>

        </div>

    </div>

    <!-- Summary -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Quote ID
            </div>

            <div class="summary-value">
                #<?php echo $id; ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Items
            </div>

            <div class="summary-value">
                <?php echo count($quote_items); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Subtotal
            </div>

            <div class="summary-value">
                ₹ <?php echo number_format($subtotal, 2); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Total Amount
            </div>

            <div class="summary-value green">
                ₹ <?php echo number_format($totalAmount, 2); ?>
            </div>

        </div>

    </div>

    <!-- Main Content -->

    <div class="content-grid">

        <!-- Main Column -->

        <div>

            <!-- Quote Information -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h3>
                            Quote Information
                        </h3>

                        <p>
                            Main quote and related record details.
                        </p>

                    </div>

                </div>

                <div class="card-body">

                    <div class="details-grid">

                        <!-- Quote ID -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Quote ID
                            </div>

                            <div class="detail-value">

                                <strong>
                                    #<?php echo $id; ?>
                                </strong>

                            </div>

                        </div>

                        <!-- Quote Number -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Quote Number
                            </div>

                            <div class="detail-value">

                                <strong>
                                    <?php echo htmlspecialchars($quoteNumber); ?>
                                </strong>

                            </div>

                        </div>

                        <!-- Company -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Company
                            </div>

                            <div class="detail-value">

                                <?php if ($companyName !== ""): ?>

                                    <?php echo htmlspecialchars($companyName); ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Contact -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Contact
                            </div>

                            <div class="detail-value">

                                <?php if ($contactName !== ""): ?>

                                    <?php echo htmlspecialchars($contactName); ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Contact Email -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Contact Email
                            </div>

                            <div class="detail-value">

                                <?php if (!empty($quote["contact_email"])): ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($quote["contact_email"]); ?>"
                                    >
                                        <?php echo htmlspecialchars($quote["contact_email"]); ?>
                                    </a>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not available
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Contact Phone -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Contact Phone
                            </div>

                            <div class="detail-value">

                                <?php if (!empty($quote["contact_phone"])): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($quote["contact_phone"]); ?>"
                                    >
                                        <?php echo htmlspecialchars($quote["contact_phone"]); ?>
                                    </a>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not available
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Customer -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Customer
                            </div>

                            <div class="detail-value">

                                <?php if ($customerCode !== ""): ?>

                                    <?php echo htmlspecialchars($customerCode); ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Deal -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Deal
                            </div>

                            <div class="detail-value">

                                <?php if ($dealTitle !== ""): ?>

                                    <?php echo htmlspecialchars($dealTitle); ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Status -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Status
                            </div>

                            <div class="detail-value">

                                <span
                                    class="status <?php echo $statusClass; ?>"
                                >
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </span>

                            </div>

                        </div>

                        <!-- Valid Until -->

                        <div class="detail-item">

                            <div class="detail-label">
                                Valid Until
                            </div>

                            <div class="detail-value">

                                <?php if (!empty($quote["valid_until"])): ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $quote["valid_until"]
                                    );
                                    ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Notes -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h3>
                            Notes
                        </h3>

                        <p>
                            Additional information associated with this quote.
                        </p>

                    </div>

                </div>

                <div class="card-body">

                    <div class="notes-box">

                        <?php if (!empty($quote["notes"])): ?>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $quote["notes"]
                                )
                            );
                            ?>

                        <?php else: ?>

                            <span class="empty-notes">
                                No notes have been added to this quote.
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- Products -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h3>
                            Products In This Quote
                        </h3>

                        <p>
                            Products and quote items associated with this quote.
                        </p>

                    </div>

                    <a
                        href="quote_items.php?quote_id=<?php echo $id; ?>"
                        class="btn btn-success"
                    >
                        + Add Product
                    </a>

                </div>

                <div class="card-body">

                    <?php if (count($quote_items) > 0): ?>

                        <div class="items-table-container">

                            <table class="items-table">

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
                                            Description
                                        </th>

                                        <th>
                                            Qty
                                        </th>

                                        <th>
                                            Unit Price
                                        </th>

                                        <th>
                                            Discount
                                        </th>

                                        <th>
                                            Tax
                                        </th>

                                        <th>
                                            Total
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php $serial_no = 1; ?>

                                    <?php foreach ($quote_items as $item): ?>

                                        <tr>

                                            <td>
                                                <?php
                                                echo $serial_no++;
                                                ?>
                                            </td>

                                            <td>

                                                <div class="product-name">

                                                    <?php
                                                    echo !empty($item["product_name"])
                                                        ? htmlspecialchars(
                                                            $item["product_name"]
                                                        )
                                                        : "-";
                                                    ?>

                                                </div>

                                            </td>

                                            <td>

                                                <span class="sku-text">

                                                    <?php
                                                    echo !empty($item["sku"])
                                                        ? htmlspecialchars(
                                                            $item["sku"]
                                                        )
                                                        : "-";
                                                    ?>

                                                </span>

                                            </td>

                                            <td>

                                                <?php
                                                echo !empty($item["description"])
                                                    ? nl2br(
                                                        htmlspecialchars(
                                                            $item["description"]
                                                        )
                                                    )
                                                    : "-";
                                                ?>

                                            </td>

                                            <td>

                                                <?php
                                                echo number_format(
                                                    (float)$item["quantity"],
                                                    2
                                                );
                                                ?>

                                            </td>

                                            <td class="currency">

                                                ₹ <?php
                                                echo number_format(
                                                    (float)$item["unit_price"],
                                                    2
                                                );
                                                ?>

                                            </td>

                                            <td class="currency">

                                                ₹ <?php
                                                echo number_format(
                                                    (float)$item["discount"],
                                                    2
                                                );
                                                ?>

                                            </td>

                                            <td class="currency">

                                                ₹ <?php
                                                echo number_format(
                                                    (float)$item["tax"],
                                                    2
                                                );
                                                ?>

                                            </td>

                                            <td class="item-total">

                                                ₹ <?php
                                                echo number_format(
                                                    (float)$item["total"],
                                                    2
                                                );
                                                ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        <!-- Amount Summary -->

                        <div class="amount-summary">

                            <div class="summary-row">

                                <span>
                                    Items Subtotal
                                </span>

                                <span>
                                    ₹ <?php
                                    echo number_format(
                                        $items_subtotal,
                                        2
                                    );
                                    ?>
                                </span>

                            </div>

                            <div class="summary-row">

                                <span>
                                    Quote Subtotal
                                </span>

                                <span>
                                    ₹ <?php
                                    echo number_format(
                                        $subtotal,
                                        2
                                    );
                                    ?>
                                </span>

                            </div>

                            <div class="summary-row">

                                <span>
                                    Tax
                                </span>

                                <span>
                                    ₹ <?php
                                    echo number_format(
                                        $taxAmount,
                                        2
                                    );
                                    ?>
                                </span>

                            </div>

                            <div class="summary-row">

                                <span>
                                    Discount
                                </span>

                                <span>
                                    ₹ <?php
                                    echo number_format(
                                        $discountAmount,
                                        2
                                    );
                                    ?>
                                </span>

                            </div>

                            <div class="summary-row total">

                                <span>
                                    Total
                                </span>

                                <span>
                                    ₹ <?php
                                    echo number_format(
                                        $totalAmount,
                                        2
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="no-items">

                            No products have been added to this quote yet.

                            <div style="margin-top:12px;">

                                <a
                                    href="quote_items.php?quote_id=<?php echo $id; ?>"
                                    class="btn btn-success"
                                >
                                    + Add Product
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <!-- Right Column -->

        <div class="side-column">

            <!-- Quote Total -->

            <div class="side-card">

                <h3>
                    Quote Total
                </h3>

                <div class="total-box">

                    <div class="total-label">
                        Total Amount
                    </div>

                    <div class="total-value">
                        ₹ <?php
                        echo number_format(
                            $totalAmount,
                            2
                        );
                        ?>
                    </div>

                    <div class="validity-text">

                        <?php if (!empty($quote["valid_until"])): ?>

                            Valid until:
                            <?php
                            echo htmlspecialchars(
                                $quote["valid_until"]
                            );
                            ?>

                        <?php else: ?>

                            Validity date not specified.

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- Financial Summary -->

            <div class="side-card">

                <h3>
                    Financial Summary
                </h3>

                <div class="side-row">

                    <span>
                        Items
                    </span>

                    <strong>
                        <?php echo count($quote_items); ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>
                        ₹ <?php
                        echo number_format(
                            $subtotal,
                            2
                        );
                        ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Tax
                    </span>

                    <strong>
                        ₹ <?php
                        echo number_format(
                            $taxAmount,
                            2
                        );
                        ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Discount
                    </span>

                    <strong>
                        ₹ <?php
                        echo number_format(
                            $discountAmount,
                            2
                        );
                        ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Total
                    </span>

                    <strong style="color:#16a34a;">
                        ₹ <?php
                        echo number_format(
                            $totalAmount,
                            2
                        );
                        ?>
                    </strong>

                </div>

            </div>

            <!-- Related Records -->

            <div class="side-card">

                <h3>
                    Related Records
                </h3>

                <div class="side-row">

                    <span>
                        Company
                    </span>

                    <strong>

                        <?php
                        echo $companyName !== ""
                            ? htmlspecialchars($companyName)
                            : "-";
                        ?>

                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Contact
                    </span>

                    <strong>

                        <?php
                        echo $contactName !== ""
                            ? htmlspecialchars($contactName)
                            : "-";
                        ?>

                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Customer
                    </span>

                    <strong>

                        <?php
                        echo $customerCode !== ""
                            ? htmlspecialchars($customerCode)
                            : "-";
                        ?>

                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Deal
                    </span>

                    <strong>

                        <?php
                        echo $dealTitle !== ""
                            ? htmlspecialchars($dealTitle)
                            : "-";
                        ?>

                    </strong>

                </div>

            </div>

            <!-- Quick Actions -->

            <div class="side-card">

                <h3>
                    Quick Actions
                </h3>

                <div class="quick-actions">

                    <a
                        href="edit.php?id=<?php echo $id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✎
                        </span>

                        Edit Quote

                    </a>

                    <a
                        href="quote_items.php?quote_id=<?php echo $id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            +
                        </span>

                        Manage Products

                    </a>

                    <a
                        href="add.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            Q
                        </span>

                        Create New Quote

                    </a>

                    <a
                        href="index.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ☷
                        </span>

                        Quote List

                    </a>

                </div>

            </div>

            <!-- Timeline -->

            <div class="side-card">

                <h3>
                    Record Timeline
                </h3>

                <div class="timeline-item">

                    <span class="timeline-dot"></span>

                    <div class="timeline-title">
                        Quote Created
                    </div>

                    <div class="timeline-date">

                        <?php
                        echo !empty($quote["created_at"])
                            ? htmlspecialchars(
                                $quote["created_at"]
                            )
                            : "Date unavailable";
                        ?>

                    </div>

                </div>

                <div class="timeline-item">

                    <span class="timeline-dot"></span>

                    <div class="timeline-title">
                        Last Updated
                    </div>

                    <div class="timeline-date">

                        <?php
                        echo !empty($quote["updated_at"])
                            ? htmlspecialchars(
                                $quote["updated_at"]
                            )
                            : "No update date available";
                        ?>

                    </div>

                </div>

                <div class="timeline-item">

                    <span class="timeline-dot"></span>

                    <div class="timeline-title">
                        Created By
                    </div>

                    <div class="timeline-date">

                        <?php
                        echo !empty($quote["created_by_name"])
                            ? htmlspecialchars(
                                $quote["created_by_name"]
                            )
                            : "User unavailable";
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Bottom Actions -->

    <div class="bottom-actions">

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← Back to Quotes
        </a>

        <a
            href="quote_items.php?quote_id=<?php echo $id; ?>"
            class="btn btn-success"
        >
            + Manage Products
        </a>

        <a
            href="edit.php?id=<?php echo $id; ?>"
            class="btn btn-primary"
        >
            ✎ Edit Quote
        </a>

    </div>

</div>

</body>

</html>