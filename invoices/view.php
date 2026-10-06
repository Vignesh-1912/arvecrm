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
        ii.id,
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

$company_address = implode(
    ", ",
    array_filter([
        $invoice["company_address"] ?? "",
        $invoice["company_city"] ?? "",
        $invoice["company_state"] ?? "",
        $invoice["company_country"] ?? "",
        $invoice["company_postal_code"] ?? ""
    ], static function ($value) {
        return trim((string) $value) !== "";
    })
);

$contact_address = implode(
    ", ",
    array_filter([
        $invoice["contact_address"] ?? "",
        $invoice["contact_city"] ?? "",
        $invoice["contact_state"] ?? "",
        $invoice["contact_country"] ?? "",
        $invoice["contact_postal_code"] ?? ""
    ], static function ($value) {
        return trim((string) $value) !== "";
    })
);

$subtotal = (float) ($invoice["subtotal"] ?? 0);
$tax_amount = (float) ($invoice["tax_amount"] ?? 0);
$discount_amount = (float) ($invoice["discount_amount"] ?? 0);
$total_amount = (float) ($invoice["total_amount"] ?? 0);
$status = strtolower(trim($invoice["invoice_status"] ?? "draft"));
$status_class = preg_match("/^[a-z0-9_-]+$/", $status)
    ? "status-" . $status
    : "status-default";
$invoice_date = !empty($invoice["invoice_date"])
    ? date("d M Y", strtotime($invoice["invoice_date"]))
    : "Not specified";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?php echo htmlspecialchars($invoice["invoice_number"]); ?> - Invoice</title>

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
            background: #f5f7fb;
            color: #172033;
            font-family: Arial, sans-serif;
        }

        .main-content {
            width: calc(100% - 250px);
            min-height: 100vh;
            margin-left: 250px;
            padding: 28px;
        }

        .breadcrumb {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .page-header h1 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 24px;
        }

        .page-header p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .btn-primary {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .invoice-document {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 3px 14px rgba(15, 23, 42, 0.05);
        }

        .document-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 24px;
            background: #172554;
            color: #ffffff;
        }

        .document-title {
            margin: 0 0 5px;
            color: #ffffff;
            font-size: 22px;
        }

        .document-subtitle {
            margin: 0;
            color: #cbd5e1;
            font-size: 11px;
        }

        .document-reference {
            display: grid;
            grid-template-columns: auto auto;
            gap: 6px 16px;
            text-align: right;
            font-size: 11px;
        }

        .document-reference span {
            color: #cbd5e1;
        }

        .document-reference strong {
            color: #ffffff;
        }

        .status-badge {
            display: inline-flex;
            padding: 4px 9px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #334155;
            font-size: 10px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status-paid,
        .status-sent,
        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-overdue,
        .status-cancelled,
        .status-canceled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .document-parties {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            padding: 18px 22px;
        }

        .party-card {
            min-width: 0;
            padding: 15px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .party-card h2 {
            margin: 0 0 10px;
            color: #2563eb;
            font-size: 10px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .party-name {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .party-detail {
            margin: 3px 0;
            color: #475569;
            font-size: 11px;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .items-section {
            padding: 0 22px 18px;
        }

        .section-title {
            margin: 0 0 9px;
            color: #0f172a;
            font-size: 14px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .invoice-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .invoice-table th {
            padding: 10px 9px;
            border-top: 1px solid #dbe3ee;
            border-bottom: 1px solid #dbe3ee;
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            font-size: 9px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .invoice-table td {
            padding: 10px 9px;
            border-bottom: 1px solid #eef2f7;
            color: #334155;
            vertical-align: top;
            font-size: 11px;
            overflow-wrap: anywhere;
        }

        .invoice-table tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        .numeric {
            text-align: right !important;
            white-space: nowrap;
        }

        .product-name {
            color: #0f172a;
            font-weight: 700;
        }

        .product-description {
            margin-top: 4px;
            color: #64748b;
            font-size: 10px;
            line-height: 1.4;
        }

        .empty-state {
            padding: 24px !important;
            color: #64748b !important;
            text-align: center;
        }

        .document-bottom {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 20px;
            padding: 0 22px 20px;
        }

        .notes-card {
            align-self: start;
            padding: 13px 15px;
            border-left: 3px solid #60a5fa;
            border-radius: 0 8px 8px 0;
            background: #f8fafc;
        }

        .notes-card h2 {
            margin: 0 0 5px;
            color: #172554;
            font-size: 10px;
            text-transform: uppercase;
        }

        .notes-card p {
            margin: 0;
            color: #475569;
            font-size: 11px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 7px;
            color: #64748b;
            font-size: 11px;
        }

        .totals-table td:last-child {
            color: #334155;
            text-align: right;
            white-space: nowrap;
        }

        .totals-table .grand-total td {
            padding-top: 9px;
            border-top: 2px solid #172554;
            color: #172554;
            font-size: 14px;
            font-weight: 700;
        }

        .document-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 22px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 10px;
        }

        @media (max-width: 900px) {
            .main-content {
                width: calc(100% - 220px);
                margin-left: 220px;
                padding: 18px;
            }

            .document-bottom {
                grid-template-columns: 1fr 250px;
            }
        }

        @media (max-width: 650px) {
            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .document-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .document-reference {
                width: 100%;
                text-align: left;
            }

            .document-parties,
            .document-bottom {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            html,
            body {
                width: 100%;
                background: #ffffff !important;
                color: #172033 !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .sidebar,
            .breadcrumb,
            .no-print {
                display: none !important;
            }

            .main-content {
                width: 100%;
                min-height: 0;
                margin: 0;
                padding: 0 !important;
            }

            .page-header {
                display: none !important;
            }

            .invoice-document {
                overflow: visible;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .document-header {
                padding: 12px 16px;
            }

            .document-parties {
                gap: 10px;
                padding: 12px 16px;
            }

            .party-card {
                padding: 10px 12px;
                break-inside: avoid;
            }

            .items-section {
                padding: 0 16px 12px;
            }

            .invoice-table {
                min-width: 0;
            }

            .invoice-table th,
            .invoice-table td {
                padding: 6px 7px;
                font-size: 8pt;
            }

            .product-description,
            .party-detail {
                font-size: 8pt;
            }

            .document-bottom {
                grid-template-columns: minmax(0, 1fr) 250px;
                gap: 14px;
                padding: 0 16px 12px;
            }

            .notes-card,
            .totals-table {
                break-inside: avoid;
            }

            .document-footer {
                padding: 7px 16px;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<main class="main-content">

    <nav class="breadcrumb no-print" aria-label="Breadcrumb">
        <a href="../dashboard/index.php">Dashboard</a>
        <span>/</span>
        <a href="index.php">Invoices</a>
        <span>/</span>
        <span><?php echo htmlspecialchars($invoice["invoice_number"]); ?></span>
    </nav>

    <header class="page-header no-print">
        <div>
            <h1>Invoice Details</h1>
            <p>Company, customer, product and payment information.</p>
        </div>
        <div class="header-actions">
            <a class="btn" href="index.php">← Invoice List</a>
            <a class="btn" href="invoice_items.php?invoice_id=<?php echo $id; ?>">Manage Products</a>
            <a class="btn btn-primary" href="pdf.php?id=<?php echo $id; ?>">↓ PDF / Print Invoice</a>
        </div>
    </header>

    <article class="invoice-document">
        <header class="document-header">
            <div>
                <h1 class="document-title">Invoice</h1>
                <p class="document-subtitle">Billing statement and product details</p>
            </div>
            <div class="document-reference">
                <span>Invoice Number</span>
                <strong><?php echo htmlspecialchars($invoice["invoice_number"]); ?></strong>
                <span>Invoice Date</span>
                <strong><?php echo htmlspecialchars($invoice_date); ?></strong>
                <span>Status</span>
                <strong>
                    <span class="status-badge <?php echo htmlspecialchars($status_class); ?>">
                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                    </span>
                </strong>
            </div>
        </header>

        <section class="document-parties">
            <section class="party-card">
                <h2>Company Information</h2>
                <p class="party-name">
                    <?php echo htmlspecialchars($invoice["company_name"] ?: "Company not linked"); ?>
                </p>
                <?php if ($company_address !== ""): ?>
                    <p class="party-detail"><?php echo htmlspecialchars($company_address); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["company_phone"])): ?>
                    <p class="party-detail"><strong>Phone:</strong> <?php echo htmlspecialchars($invoice["company_phone"]); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["company_email"])): ?>
                    <p class="party-detail"><strong>Email:</strong> <?php echo htmlspecialchars($invoice["company_email"]); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["company_website"])): ?>
                    <p class="party-detail"><strong>Website:</strong> <?php echo htmlspecialchars($invoice["company_website"]); ?></p>
                <?php endif; ?>
            </section>

            <section class="party-card">
                <h2>Customer Information</h2>
                <p class="party-name">
                    <?php echo htmlspecialchars($invoice["customer_code"] ?: "Customer not linked"); ?>
                </p>
                <?php if (!empty($invoice["customer_type"])): ?>
                    <p class="party-detail"><strong>Type:</strong> <?php echo htmlspecialchars($invoice["customer_type"]); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["customer_status"])): ?>
                    <p class="party-detail"><strong>Status:</strong> <?php echo htmlspecialchars($invoice["customer_status"]); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["contact_name"])): ?>
                    <p class="party-detail"><strong>Contact:</strong> <?php echo htmlspecialchars(trim($invoice["contact_name"])); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["contact_email"])): ?>
                    <p class="party-detail"><strong>Email:</strong> <?php echo htmlspecialchars($invoice["contact_email"]); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice["contact_phone"])): ?>
                    <p class="party-detail"><strong>Phone:</strong> <?php echo htmlspecialchars($invoice["contact_phone"]); ?></p>
                <?php endif; ?>
                <?php if ($contact_address !== ""): ?>
                    <p class="party-detail"><strong>Address:</strong> <?php echo htmlspecialchars($contact_address); ?></p>
                <?php endif; ?>
            </section>
        </section>

        <section class="items-section">
            <h2 class="section-title">Product Details</h2>
            <div class="table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th style="width:4%;">#</th>
                            <th style="width:23%;">Product</th>
                            <th style="width:8%;">SKU</th>
                            <th style="width:25%;">Description</th>
                            <th class="numeric" style="width:8%;">Qty</th>
                            <th class="numeric" style="width:11%;">Unit Price</th>
                            <th class="numeric" style="width:9%;">Tax</th>
                            <th class="numeric" style="width:12%;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="product-name">
                                            <?php echo htmlspecialchars($item["product_name"] ?? "Product"); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item["sku"] ?? "—"); ?></td>
                                    <td>
                                        <div class="product-description">
                                            <?php echo nl2br(htmlspecialchars($item["description"] ?? "")); ?>
                                        </div>
                                    </td>
                                    <td class="numeric"><?php echo number_format((float) $item["quantity"], 2); ?></td>
                                    <td class="numeric">₹<?php echo number_format((float) $item["unit_price"], 2); ?></td>
                                    <td class="numeric">₹<?php echo number_format((float) $item["tax"], 2); ?></td>
                                    <td class="numeric"><strong>₹<?php echo number_format((float) $item["total"], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">No product items are attached to this invoice.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="document-bottom">
            <div>
                <?php if (!empty($invoice["notes"])): ?>
                    <div class="notes-card">
                        <h2>Notes</h2>
                        <p><?php echo nl2br(htmlspecialchars($invoice["notes"])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <table class="totals-table">
                <tbody>
                    <tr>
                        <td>Subtotal</td>
                        <td>₹<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Tax</td>
                        <td>₹<?php echo number_format($tax_amount, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Discount</td>
                        <td>− ₹<?php echo number_format($discount_amount, 2); ?></td>
                    </tr>
                    <tr class="grand-total">
                        <td>Total Due</td>
                        <td>₹<?php echo number_format($total_amount, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <footer class="document-footer">
            <span>Thank you for your business.</span>
            <span>Prepared by <?php echo htmlspecialchars($invoice["created_by_name"] ?? "CRM"); ?></span>
        </footer>
    </article>

</main>

</body>

</html>
