<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   REPORT COUNTS
============================== */

$counts = [
    "customers"  => 0,
    "companies"  => 0,
    "contacts"   => 0,
    "leads"      => 0,
    "deals"      => 0,
    "products"   => 0,
    "quotes"     => 0,
    "sales"      => 0,
    "tasks"      => 0,
    "activities" => 0
];

/* Customers */
$stmt = $conn->query("SELECT COUNT(*) FROM customers");
$counts["customers"] = (int) $stmt->fetchColumn();

/* Companies */
$stmt = $conn->query("SELECT COUNT(*) FROM companies");
$counts["companies"] = (int) $stmt->fetchColumn();

/* Contacts */
$stmt = $conn->query("SELECT COUNT(*) FROM contacts");
$counts["contacts"] = (int) $stmt->fetchColumn();

/* Leads */
$stmt = $conn->query("SELECT COUNT(*) FROM leads");
$counts["leads"] = (int) $stmt->fetchColumn();

/* Deals */
$stmt = $conn->query("SELECT COUNT(*) FROM deals");
$counts["deals"] = (int) $stmt->fetchColumn();

/* Products */
$stmt = $conn->query("SELECT COUNT(*) FROM products");
$counts["products"] = (int) $stmt->fetchColumn();

/* Quotes */
$stmt = $conn->query("SELECT COUNT(*) FROM quotes");
$counts["quotes"] = (int) $stmt->fetchColumn();

/* Sales */
$stmt = $conn->query("SELECT COUNT(*) FROM sales");
$counts["sales"] = (int) $stmt->fetchColumn();

/* Tasks */
$stmt = $conn->query("SELECT COUNT(*) FROM tasks");
$counts["tasks"] = (int) $stmt->fetchColumn();

/* Activities */
$stmt = $conn->query("SELECT COUNT(*) FROM activities");
$counts["activities"] = (int) $stmt->fetchColumn();

/* ==============================
   SALES VALUE
============================== */

$stmt = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM sales
");

$total_sales_value = (float) $stmt->fetchColumn();

/* ==============================
   DEAL VALUE
============================== */

$stmt = $conn->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM deals
");

$total_deal_value = (float) $stmt->fetchColumn();

/* ==============================
   LEAD VALUE
============================== */

$stmt = $conn->query("
    SELECT COALESCE(SUM(lead_value), 0)
    FROM leads
");

$total_lead_value = (float) $stmt->fetchColumn();

/* ==============================
   TOTAL RECORDS
============================== */

$total_records = array_sum($counts);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        /* =========================
           GLOBAL
        ========================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;

            font-family: Arial, sans-serif;

            background: #f8fafc;

            color: #0f172a;

            overflow-x: hidden;
        }

        .main-content {
            margin-left: 250px;

            min-height: 100vh;

            width: calc(100% - 250px);

            padding: 28px;

            overflow-x: hidden;
        }

        /* =========================
           HEADER
        ========================= */

        .page-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 20px 24px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .title-area {
            display: flex;

            align-items: center;

            gap: 14px;

            min-width: 0;
        }

        .title-icon {
            width: 48px;
            height: 48px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: #dbeafe;

            font-size: 23px;
        }

        .page-header h1 {
            margin: 0;

            font-size: 24px;

            line-height: 1.2;

            color: #0f172a;
        }

        .page-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 13px;
        }

        /* =========================
           SUMMARY
        ========================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 22px;
        }

        .summary-card {
            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 18px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }

        .summary-label {
            color: #64748b;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.05em;
        }

        .summary-value {
            margin-top: 8px;

            font-size: 26px;

            font-weight: 700;

            color: #0f172a;
        }

        .summary-small {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 11px;
        }

        /* =========================
           SECTION HEADER
        ========================= */

        .section-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 14px;
        }

        .section-title {
            margin: 0;

            font-size: 18px;

            color: #0f172a;
        }

        .section-subtitle {
            margin: 4px 0 0;

            color: #64748b;

            font-size: 12px;
        }

        /* =========================
           REPORT GRID
        ========================= */

        .report-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;
        }

        .report-card {
            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 18px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);

            border-color: #bfdbfe;

            box-shadow:
                0 8px 20px
                rgba(15, 23, 42, 0.06);
        }

        .report-top {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 14px;
        }

        .report-icon {
            width: 44px;
            height: 44px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            font-size: 21px;

            flex-shrink: 0;
        }

        .report-count {
            color: #64748b;

            font-size: 12px;

            font-weight: 700;
        }

        .report-card h3 {
            margin: 0;

            color: #0f172a;

            font-size: 16px;
        }

        .report-card p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 12px;

            line-height: 1.5;

            min-height: 36px;
        }

        .report-actions {
            display: flex;

            align-items: center;

            gap: 8px;

            margin-top: 16px;
        }

        .view-report-btn,
        .export-report-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 9px 12px;

            border-radius: 8px;

            font-size: 12px;

            font-weight: 700;

            text-decoration: none;

            white-space: nowrap;

            transition: 0.2s;
        }

        .view-report-btn {
            background: #2563eb;

            color: #ffffff;
        }

        .view-report-btn:hover {
            background: #1d4ed8;
        }

        .export-report-btn {
            background: #ffffff;

            color: #475569;

            border: 1px solid #dbe3ee;
        }

        .export-report-btn:hover {
            background: #f8fafc;

            color: #2563eb;
        }

        /* =========================
           FINANCIAL SECTION
        ========================= */

        .financial-section {
            margin-top: 22px;

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;
        }

        .financial-card {
            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 18px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }

        .financial-label {
            color: #64748b;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.05em;
        }

        .financial-value {
            margin-top: 9px;

            font-size: 24px;

            font-weight: 700;

            color: #0f172a;
        }

        .financial-description {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 11px;
        }

        /* =========================
           FOOTER
        ========================= */

        .page-footer {
            margin-top: 22px;

            padding: 16px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            color: #94a3b8;

            font-size: 12px;

            text-align: center;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1200px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .report-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .financial-section {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;

                width: calc(100% - 220px);

                padding: 16px;
            }

            .page-header {
                align-items: flex-start;
            }

            .summary-grid,
            .report-grid,
            .financial-section {
                grid-template-columns: 1fr;
            }

            .report-actions {
                flex-wrap: wrap;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header">

        <div class="title-area">

            <div class="title-icon">
                📊
            </div>

            <div>

                <h1>
                    Reports
                </h1>

                <p>
                    Analyze CRM data, sales activity and business performance
                </p>

            </div>

        </div>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Records
            </div>

            <div class="summary-value">
                <?= number_format($total_records); ?>
            </div>

            <div class="summary-small">
                Across all CRM modules
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Sales Value
            </div>

            <div class="summary-value">
                ₹<?= number_format(
                    $total_sales_value,
                    0
                ); ?>
            </div>

            <div class="summary-small">
                Total recorded sales
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Deal Value
            </div>

            <div class="summary-value">
                ₹<?= number_format(
                    $total_deal_value,
                    0
                ); ?>
            </div>

            <div class="summary-small">
                Total deal pipeline
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Lead Value
            </div>

            <div class="summary-value">
                ₹<?= number_format(
                    $total_lead_value,
                    0
                ); ?>
            </div>

            <div class="summary-small">
                Total lead value
            </div>

        </div>

    </div>

    <!-- =========================
         REPORT MODULES
    ========================= -->

    <div class="section-header">

        <div>

            <h2 class="section-title">
                Module Reports
            </h2>

            <p class="section-subtitle">
                Select a module to view detailed records and export data
            </p>

        </div>

    </div>

    <div class="report-grid">

        <!-- CUSTOMERS -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    👤
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["customers"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Customers
            </h3>

            <p>
                Review customer records, customer types,
                statuses and account information.
            </p>

            <div class="report-actions">

                <a
                    href="customers.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/customers_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- COMPANIES -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    🏢
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["companies"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Companies
            </h3>

            <p>
                Review company accounts, industries,
                contact details and locations.
            </p>

            <div class="report-actions">

                <a
                    href="companies.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/companies_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- CONTACTS -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    📇
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["contacts"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Contacts
            </h3>

            <p>
                Analyze contact records, companies,
                job titles and communication details.
            </p>

            <div class="report-actions">

                <a
                    href="contacts.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/contacts_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- LEADS -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    🎯
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["leads"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Leads
            </h3>

            <p>
                Track lead sources, statuses,
                values and assigned sales representatives.
            </p>

            <div class="report-actions">

                <a
                    href="leads.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/leads_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- DEALS -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    💼
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["deals"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Deals
            </h3>

            <p>
                Review sales opportunities, deal stages,
                values, close dates and assignments.
            </p>

            <div class="report-actions">

                <a
                    href="deals.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/deals_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- PRODUCTS -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    📦
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["products"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Products
            </h3>

            <p>
                Review product catalog, prices,
                tax rates, SKUs and product status.
            </p>

            <div class="report-actions">

                <a
                    href="products.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/products_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- QUOTES -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    📄
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["quotes"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Quotes
            </h3>

            <p>
                Analyze quote values, statuses,
                customers and quotation dates.
            </p>

            <div class="report-actions">

                <a
                    href="quotes.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/quotes_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- SALES -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    💰
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["sales"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Sales
            </h3>

            <p>
                Analyze sales transactions, revenue,
                payment status and sale performance.
            </p>

            <div class="report-actions">

                <a
                    href="sales.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/sales_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- TASKS -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    ✅
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["tasks"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Tasks
            </h3>

            <p>
                Review task status, priorities,
                due dates and assigned users.
            </p>

            <div class="report-actions">

                <a
                    href="tasks_activities.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/tasks_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

        <!-- ACTIVITIES -->

        <div class="report-card">

            <div class="report-top">

                <div class="report-icon">
                    📅
                </div>

                <div class="report-count">
                    <?= number_format(
                        $counts["activities"]
                    ); ?> records
                </div>

            </div>

            <h3>
                Activities
            </h3>

            <p>
                Review calls, meetings, emails
                and customer interaction history.
            </p>

            <div class="report-actions">

                <a
                    href="tasks_activities.php"
                    class="view-report-btn"
                >
                    View Report
                </a>

                <a
                    href="../exports/activities_csv.php"
                    class="export-report-btn"
                >
                    CSV
                </a>

            </div>

        </div>

    </div>

    <!-- =========================
         FINANCIAL SUMMARY
    ========================= -->

    <div class="financial-section">

        <div class="financial-card">

            <div class="financial-label">
                Sales Revenue
            </div>

            <div class="financial-value">
                ₹<?= number_format(
                    $total_sales_value,
                    0
                ); ?>
            </div>

            <div class="financial-description">
                Total amount recorded in sales
            </div>

        </div>

        <div class="financial-card">

            <div class="financial-label">
                Deal Pipeline
            </div>

            <div class="financial-value">
                ₹<?= number_format(
                    $total_deal_value,
                    0
                ); ?>
            </div>

            <div class="financial-description">
                Combined value of deals
            </div>

        </div>

        <div class="financial-card">

            <div class="financial-label">
                Lead Pipeline
            </div>

            <div class="financial-value">
                ₹<?= number_format(
                    $total_lead_value,
                    0
                ); ?>
            </div>

            <div class="financial-description">
                Combined value of leads
            </div>

        </div>

    </div>

    <!-- =========================
         FOOTER
    ========================= -->

    <div class="page-footer">
        CRM Reports &nbsp;•&nbsp;
        <?= date("Y"); ?> &nbsp;•&nbsp;
        Business Analytics Dashboard
    </div>

</div>

</body>

</html>