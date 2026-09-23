<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| REPORT COUNTS
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| COUNT QUERIES
|--------------------------------------------------------------------------
*/

$count_queries = [
    "customers"  => "SELECT COUNT(*) FROM customers",
    "companies"  => "SELECT COUNT(*) FROM companies",
    "contacts"   => "SELECT COUNT(*) FROM contacts",
    "leads"      => "SELECT COUNT(*) FROM leads",
    "deals"      => "SELECT COUNT(*) FROM deals",
    "products"   => "SELECT COUNT(*) FROM products",
    "quotes"     => "SELECT COUNT(*) FROM quotes",
    "sales"      => "SELECT COUNT(*) FROM sales",
    "tasks"      => "SELECT COUNT(*) FROM tasks",
    "activities" => "SELECT COUNT(*) FROM activities"
];

/*
|--------------------------------------------------------------------------
| LOAD COUNTS
|--------------------------------------------------------------------------
*/

foreach ($count_queries as $module => $query) {

    try {

        $stmt = $conn->query($query);

        $counts[$module] = (int) $stmt->fetchColumn();

    } catch (PDOException $e) {

        $counts[$module] = 0;
    }
}

/*
|--------------------------------------------------------------------------
| SALES VALUE
|--------------------------------------------------------------------------
*/

$total_sales_value = 0;

try {

    $stmt = $conn->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM sales
    ");

    $total_sales_value = (float) $stmt->fetchColumn();

} catch (PDOException $e) {

    $total_sales_value = 0;
}

/*
|--------------------------------------------------------------------------
| DEAL VALUE
|--------------------------------------------------------------------------
*/

$total_deal_value = 0;

try {

    $stmt = $conn->query("
        SELECT COALESCE(SUM(amount), 0)
        FROM deals
    ");

    $total_deal_value = (float) $stmt->fetchColumn();

} catch (PDOException $e) {

    $total_deal_value = 0;
}

/*
|--------------------------------------------------------------------------
| LEAD VALUE
|--------------------------------------------------------------------------
*/

$total_lead_value = 0;

try {

    $stmt = $conn->query("
        SELECT COALESCE(SUM(lead_value), 0)
        FROM leads
    ");

    $total_lead_value = (float) $stmt->fetchColumn();

} catch (PDOException $e) {

    $total_lead_value = 0;
}

/*
|--------------------------------------------------------------------------
| TOTAL RECORDS
|--------------------------------------------------------------------------
|
| Do NOT add tasks_activities here because it is only a combined
| display card for Tasks + Activities. Adding it would double-count
| those records.
|--------------------------------------------------------------------------
*/

$total_records =
    $counts["customers"] +
    $counts["companies"] +
    $counts["contacts"] +
    $counts["leads"] +
    $counts["deals"] +
    $counts["products"] +
    $counts["quotes"] +
    $counts["sales"] +
    $counts["tasks"] +
    $counts["activities"];

/*
|--------------------------------------------------------------------------
| REPORT MODULES
|--------------------------------------------------------------------------
*/

$report_modules = [

    [
        "key" => "customers",
        "title" => "Customers",
        "icon" => "👥",
        "description" =>
            "Review customer records, types, statuses and account information.",
        "url" => "customers.php",
        "export" => "../exports/customers_csv.php",
        "export_label" => "CSV",
        "category" => "data",
        "color" => "blue"
    ],

    [
        "key" => "companies",
        "title" => "Companies",
        "icon" => "🏢",
        "description" =>
            "Review company accounts, industries, locations and contact information.",
        "url" => "companies.php",
        "export" => "../exports/companies_csv.php",
        "export_label" => "CSV",
        "category" => "data",
        "color" => "indigo"
    ],

    [
        "key" => "contacts",
        "title" => "Contacts",
        "icon" => "📇",
        "description" =>
            "Analyze contacts, company associations, job titles and communication details.",
        "url" => "contacts.php",
        "export" => "../exports/contacts_csv.php",
        "export_label" => "CSV",
        "category" => "data",
        "color" => "cyan"
    ],

    [
        "key" => "leads",
        "title" => "Leads",
        "icon" => "🎯",
        "description" =>
            "Track lead sources, statuses, values and assigned sales representatives.",
        "url" => "leads.php",
        "export" => "../exports/leads_csv.php",
        "export_label" => "CSV",
        "category" => "sales",
        "color" => "orange"
    ],

    [
        "key" => "deals",
        "title" => "Deals",
        "icon" => "💼",
        "description" =>
            "Review opportunities, deal stages, values, probabilities and close dates.",
        "url" => "deals.php",
        "export" => "../exports/deals_csv.php",
        "export_label" => "CSV",
        "category" => "sales",
        "color" => "purple"
    ],

    [
        "key" => "products",
        "title" => "Products",
        "icon" => "📦",
        "description" =>
            "Review products, SKUs, pricing, tax rates and product status.",
        "url" => "products.php",
        "export" => "../exports/products_csv.php",
        "export_label" => "CSV",
        "category" => "data",
        "color" => "teal"
    ],

    [
        "key" => "quotes",
        "title" => "Quotes",
        "icon" => "📝",
        "description" =>
            "Analyze quote values, statuses, customers and quotation details.",
        "url" => "quotes.php",
        "export" => "../exports/quotes_csv.php",
        "export_label" => "CSV",
        "category" => "sales",
        "color" => "pink"
    ],

    [
        "key" => "sales",
        "title" => "Sales",
        "icon" => "💰",
        "description" =>
            "Analyze sales transactions, revenue, payments and sale performance.",
        "url" => "sales.php",
        "export" => "../exports/sales_csv.php",
        "export_label" => "CSV",
        "category" => "sales",
        "color" => "green"
    ],

    [
        "key" => "tasks_activities",
        "title" => "Tasks & Activities",
        "icon" => "✅",
        "description" =>
            "Review tasks, priorities, activities, calls, meetings, emails and customer interactions.",
        "url" => "tasks_activities.php",
        "export" => "../exports/tasks_csv.php",
        "export_label" => "Tasks CSV",
        "category" => "productivity",
        "color" => "amber"
    ]

];

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
        Reports | CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;

            background: #f5f7fb;

            color: #0f172a;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        button,
        input {
            font-family: inherit;
        }

        .main-content {
            min-height: 100vh;

            padding: 28px;
        }

        .page-wrap {
            width: 100%;

            max-width: 1500px;

            margin: 0 auto;
        }

        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .page-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;

            padding:
                22px
                24px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 16px;

            box-shadow:
                0 4px 18px
                rgba(15, 23, 42, 0.04);
        }

        .header-left {
            display: flex;

            align-items: center;

            gap: 15px;

            min-width: 0;
        }

        .page-icon {
            width: 54px;
            height: 54px;

            flex: 0 0 54px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #eff6ff
                );

            color: #2563eb;

            font-size: 25px;

            box-shadow:
                inset 0 0 0 1px
                #bfdbfe;
        }

        .header-title {
            min-width: 0;
        }

        .header-title h1 {
            margin: 0;

            color: #0f172a;

            font-size: 25px;

            font-weight: 700;

            line-height: 1.2;
        }

        .header-title p {
            margin:
                5px
                0
                0;

            color: #64748b;

            font-size: 12px;

            line-height: 1.5;
        }

        .header-actions {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-shrink: 0;
        }

        .header-action {
            min-height: 36px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0
                12px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #475569;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .header-action:hover {
            background: #f8fafc;

            color: #2563eb;

            border-color: #bfdbfe;
        }

        .header-action.primary {
            background: #2563eb;

            border-color: #2563eb;

            color: #ffffff;
        }

        .header-action.primary:hover {
            background: #1d4ed8;

            border-color: #1d4ed8;
        }

        /* =========================================================
           KPI GRID
        ========================================================= */

        .kpi-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 14px;

            margin-bottom: 22px;
        }

        .kpi-card {
            position: relative;

            min-width: 0;

            padding: 18px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 3px 14px
                rgba(15, 23, 42, 0.035);

            overflow: hidden;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(15, 23, 42, 0.07);
        }

        .kpi-card::before {
            content: "";

            position: absolute;

            left: 0;
            top: 0;

            width: 4px;
            height: 100%;

            background: #2563eb;
        }

        .kpi-card.green::before {
            background: #16a34a;
        }

        .kpi-card.purple::before {
            background: #7c3aed;
        }

        .kpi-card.orange::before {
            background: #ea580c;
        }

        .kpi-top {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 14px;
        }

        .kpi-icon {
            width: 39px;
            height: 39px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #eff6ff;

            font-size: 18px;
        }

        .kpi-card.green .kpi-icon {
            background: #f0fdf4;
        }

        .kpi-card.purple .kpi-icon {
            background: #f5f3ff;
        }

        .kpi-card.orange .kpi-icon {
            background: #fff7ed;
        }

        .kpi-mini {
            padding:
                5px
                8px;

            border-radius: 999px;

            background: #f8fafc;

            color: #64748b;

            font-size: 9px;

            font-weight: 700;

            white-space: nowrap;
        }

        .kpi-label {
            color: #64748b;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }

        .kpi-value {
            margin-top: 6px;

            color: #0f172a;

            font-size: 25px;

            font-weight: 700;

            line-height: 1.2;
        }

        .kpi-description {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 10px;
        }

        /* =========================================================
           CONTROL PANEL
        ========================================================= */

        .control-panel {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 18px;

            padding:
                14px
                16px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 13px;

            box-shadow:
                0 3px 12px
                rgba(15, 23, 42, 0.03);
        }

        .control-left {
            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: wrap;
        }

        .filter-btn {
            min-height: 32px;

            padding:
                0
                11px;

            border:
                1px solid #dbe3ee;

            background: #ffffff;

            color: #64748b;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .filter-btn:hover {
            background: #f8fafc;

            color: #2563eb;
        }

        .filter-btn.active {
            background: #dbeafe;

            border-color: #bfdbfe;

            color: #2563eb;
        }

        .search-box {
            position: relative;

            width: 260px;

            max-width: 100%;
        }

        .search-box input {
            width: 100%;

            height: 36px;

            padding:
                0
                12px
                0
                36px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #0f172a;

            outline: none;

            font-size: 11px;
        }

        .search-box input:focus {
            border-color: #93c5fd;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.08);
        }

        .search-icon {
            position: absolute;

            left: 12px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 13px;

            pointer-events: none;
        }

        .records-label {
            color: #94a3b8;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;
        }

        /* =========================================================
           SECTION HEADER
        ========================================================= */

        .section-heading {
            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 13px;
        }

        .section-heading h2 {
            margin: 0;

            color: #0f172a;

            font-size: 18px;

            font-weight: 700;
        }

        .section-heading p {
            margin:
                4px
                0
                0;

            color: #64748b;

            font-size: 11px;
        }

        .record-count {
            color: #94a3b8;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================================================
           REPORT GRID
        ========================================================= */

        .report-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 15px;
        }

        .report-card {
            display: flex;

            flex-direction: column;

            min-width: 0;

            padding: 17px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 3px 13px
                rgba(15, 23, 42, 0.03);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .report-card:hover {
            transform: translateY(-3px);

            border-color: #bfdbfe;

            box-shadow:
                0 12px 28px
                rgba(15, 23, 42, 0.08);
        }

        .report-card.hidden {
            display: none;
        }

        .report-card-top {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 13px;
        }

        .report-icon {
            width: 44px;
            height: 44px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 11px;

            font-size: 20px;
        }

        .report-card[data-color="blue"] .report-icon {
            background: #eff6ff;
            color: #2563eb;
        }

        .report-card[data-color="indigo"] .report-icon {
            background: #eef2ff;
            color: #4f46e5;
        }

        .report-card[data-color="cyan"] .report-icon {
            background: #ecfeff;
            color: #0891b2;
        }

        .report-card[data-color="orange"] .report-icon {
            background: #fff7ed;
            color: #ea580c;
        }

        .report-card[data-color="purple"] .report-icon {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .report-card[data-color="teal"] .report-icon {
            background: #f0fdfa;
            color: #0f766e;
        }

        .report-card[data-color="pink"] .report-icon {
            background: #fdf2f8;
            color: #db2777;
        }

        .report-card[data-color="green"] .report-icon {
            background: #f0fdf4;
            color: #16a34a;
        }

        .report-card[data-color="amber"] .report-icon {
            background: #fffbeb;
            color: #d97706;
        }

        .report-category {
            padding:
                5px
                8px;

            border-radius: 999px;

            background: #f8fafc;

            color: #64748b;

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }

        .report-card h3 {
            margin: 0;

            color: #0f172a;

            font-size: 16px;

            line-height: 1.3;
        }

        .report-description {
            margin:
                6px
                0
                0;

            min-height: 40px;

            color: #64748b;

            font-size: 10.5px;

            line-height: 1.55;
        }

        .report-meta {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-top: 13px;

            padding-top: 11px;

            border-top:
                1px solid #eef2f7;
        }

        .report-records {
            color: #94a3b8;

            font-size: 9px;

            font-weight: 600;
        }

        .report-record-number {
            color: #334155;

            font-size: 11px;

            font-weight: 700;
        }

        .report-actions {
            display: flex;

            align-items: center;

            gap: 7px;

            margin-top: 13px;
        }

        .report-btn {
            flex: 1;

            min-height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0
                10px;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.2s ease;
        }

        .report-btn.primary {
            background: #2563eb;

            border:
                1px solid #2563eb;

            color: #ffffff;
        }

        .report-btn.primary:hover {
            background: #1d4ed8;

            border-color: #1d4ed8;
        }

        .report-btn.secondary {
            background: #ffffff;

            border:
                1px solid #dbe3ee;

            color: #475569;
        }

        .report-btn.secondary:hover {
            color: #2563eb;

            background: #f8fafc;

            border-color: #bfdbfe;
        }

        /* =========================================================
           FINANCIAL SECTION
        ========================================================= */

        .financial-section {
            margin-top: 23px;
        }

        .financial-header {
            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 13px;
        }

        .financial-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 18px;

            font-weight: 700;
        }

        .financial-header p {
            margin:
                4px
                0
                0;

            color: #64748b;

            font-size: 11px;
        }

        .financial-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 14px;
        }

        .financial-card {
            position: relative;

            padding: 18px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 3px 13px
                rgba(15, 23, 42, 0.03);

            overflow: hidden;
        }

        .financial-card::after {
            content: "";

            position: absolute;

            right: -25px;

            bottom: -25px;

            width: 90px;

            height: 90px;

            border-radius: 50%;

            background:
                rgba(
                    37,
                    99,
                    235,
                    0.06
                );
        }

        .financial-top {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 14px;
        }

        .financial-icon {
            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #eff6ff;

            font-size: 17px;
        }

        .financial-label {
            color: #64748b;

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }

        .financial-value {
            position: relative;

            z-index: 1;

            color: #0f172a;

            font-size: 24px;

            font-weight: 700;

            line-height: 1.2;
        }

        .financial-description {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 9.5px;
        }

        /* =========================================================
           NO RESULTS
        ========================================================= */

        .no-results {
            display: none;

            padding:
                35px
                20px;

            background: #ffffff;

            border:
                1px dashed #cbd5e1;

            border-radius: 13px;

            text-align: center;

            color: #94a3b8;

            font-size: 12px;
        }

        .no-results.visible {
            display: block;
        }

        .no-results-icon {
            margin-bottom: 7px;

            font-size: 24px;
        }

        .no-results strong {
            display: block;

            margin-bottom: 4px;

            color: #475569;

            font-size: 13px;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .page-footer {
            margin-top: 22px;

            padding: 14px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            text-align: center;

            color: #94a3b8;

            font-size: 10px;
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        html.dark-mode body {
            background: #0f172a;

            color: #e2e8f0;
        }

        html.dark-mode .page-header,
        html.dark-mode .kpi-card,
        html.dark-mode .control-panel,
        html.dark-mode .report-card,
        html.dark-mode .financial-card,
        html.dark-mode .page-footer,
        html.dark-mode .no-results {
            background: #111827;

            border-color: #1f2937;

            box-shadow: none;
        }

        html.dark-mode .header-title h1,
        html.dark-mode .kpi-value,
        html.dark-mode .section-heading h2,
        html.dark-mode .report-card h3,
        html.dark-mode .financial-header h2,
        html.dark-mode .financial-value,
        html.dark-mode .no-results strong {
            color: #f8fafc;
        }

        html.dark-mode .header-title p,
        html.dark-mode .kpi-label,
        html.dark-mode .kpi-description,
        html.dark-mode .section-heading p,
        html.dark-mode .record-count,
        html.dark-mode .report-description,
        html.dark-mode .report-records,
        html.dark-mode .financial-label,
        html.dark-mode .financial-description,
        html.dark-mode .page-footer {
            color: #94a3b8;
        }

        html.dark-mode .page-icon {
            background: #172554;

            color: #60a5fa;

            box-shadow: none;
        }

        html.dark-mode .header-action,
        html.dark-mode .filter-btn,
        html.dark-mode .search-box input,
        html.dark-mode .report-btn.secondary {
            background: #0f172a;

            border-color: #334155;

            color: #cbd5e1;
        }

        html.dark-mode .header-action:hover,
        html.dark-mode .filter-btn:hover,
        html.dark-mode .report-btn.secondary:hover {
            background: #1e293b;

            border-color: #475569;

            color: #60a5fa;
        }

        html.dark-mode .header-action.primary {
            background: #2563eb;

            border-color: #2563eb;

            color: #ffffff;
        }

        html.dark-mode .filter-btn.active {
            background: #172554;

            border-color: #1e40af;

            color: #60a5fa;
        }

        html.dark-mode .kpi-mini,
        html.dark-mode .report-category {
            background: #1e293b;

            color: #94a3b8;
        }

        html.dark-mode .report-meta {
            border-color: #1f2937;
        }

        html.dark-mode .report-record-number {
            color: #cbd5e1;
        }

        html.dark-mode .search-box input {
            color: #f8fafc;
        }

        html.dark-mode .financial-icon,
        html.dark-mode .kpi-icon {
            background: #172554;
        }

        html.dark-mode .no-results {
            border-color: #334155;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1250px) {

            .kpi-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .report-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .financial-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {

            .control-panel {
                align-items: flex-start;

                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .financial-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {

            .main-content {
                padding: 18px;
            }

            .page-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-action {
                flex: 1;
            }

            .kpi-grid,
            .report-grid {
                grid-template-columns: 1fr;
            }

            .section-heading,
            .financial-header {
                align-items: flex-start;

                flex-direction: column;

                gap: 5px;
            }
        }

        @media (max-width: 460px) {

            .page-icon {
                width: 46px;

                height: 46px;

                flex-basis: 46px;

                font-size: 21px;
            }

            .header-title h1 {
                font-size: 21px;
            }

            .report-actions {
                flex-direction: column;
            }

            .report-btn {
                width: 100%;
            }

            .control-left {
                width: 100%;
            }

            .filter-btn {
                flex: 1;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-wrap">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="header-left">

                <div class="page-icon">
                    📊
                </div>

                <div class="header-title">

                    <h1>
                        Reports & Analytics
                    </h1>

                    <p>
                        Monitor CRM records, revenue, pipeline and business activity
                    </p>

                </div>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="header-action"
                    onclick="window.location.reload();"
                >
                    ↻ Refresh
                </button>


                <button
                    type="button"
                    id="themeToggle"
                    class="header-action"
                    title="Switch theme"
                    aria-label="Switch theme"
                >
                    🌙
                </button>


                <a
                    href="../dashboard/index.php"
                    class="header-action primary"
                >
                    ← Dashboard
                </a>

            </div>

        </div>


        <!-- =====================================================
             KPI CARDS
        ====================================================== -->

        <div class="kpi-grid">

            <div class="kpi-card">

                <div class="kpi-top">

                    <div class="kpi-icon">
                        📚
                    </div>

                    <div class="kpi-mini">
                        ALL MODULES
                    </div>

                </div>

                <div class="kpi-label">
                    Total Records
                </div>

                <div class="kpi-value">

                    <?php
                    echo number_format(
                        $total_records
                    );
                    ?>

                </div>

                <div class="kpi-description">
                    Records available across the CRM
                </div>

            </div>


            <div class="kpi-card green">

                <div class="kpi-top">

                    <div class="kpi-icon">
                        💰
                    </div>

                    <div class="kpi-mini">
                        REVENUE
                    </div>

                </div>

                <div class="kpi-label">
                    Sales Value
                </div>

                <div class="kpi-value">

                    ₹<?php
                    echo number_format(
                        $total_sales_value,
                        0
                    );
                    ?>

                </div>

                <div class="kpi-description">
                    Total recorded sales amount
                </div>

            </div>


            <div class="kpi-card purple">

                <div class="kpi-top">

                    <div class="kpi-icon">
                        💼
                    </div>

                    <div class="kpi-mini">
                        PIPELINE
                    </div>

                </div>

                <div class="kpi-label">
                    Deal Value
                </div>

                <div class="kpi-value">

                    ₹<?php
                    echo number_format(
                        $total_deal_value,
                        0
                    );
                    ?>

                </div>

                <div class="kpi-description">
                    Combined value of deals
                </div>

            </div>


            <div class="kpi-card orange">

                <div class="kpi-top">

                    <div class="kpi-icon">
                        🎯
                    </div>

                    <div class="kpi-mini">
                        LEADS
                    </div>

                </div>

                <div class="kpi-label">
                    Lead Value
                </div>

                <div class="kpi-value">

                    ₹<?php
                    echo number_format(
                        $total_lead_value,
                        0
                    );
                    ?>

                </div>

                <div class="kpi-description">
                    Combined lead pipeline value
                </div>

            </div>

        </div>


        <!-- =====================================================
             FILTER / SEARCH
        ====================================================== -->

        <div class="control-panel">

            <div class="control-left">

                <button
                    type="button"
                    class="filter-btn active"
                    data-filter="all"
                >
                    All
                </button>

                <button
                    type="button"
                    class="filter-btn"
                    data-filter="data"
                >
                    Data
                </button>

                <button
                    type="button"
                    class="filter-btn"
                    data-filter="sales"
                >
                    Sales & Revenue
                </button>

                <button
                    type="button"
                    class="filter-btn"
                    data-filter="productivity"
                >
                    Productivity
                </button>

            </div>


            <div class="search-box">

                <span class="search-icon">
                    🔍
                </span>

                <input
                    type="text"
                    id="reportSearch"
                    placeholder="Search reports..."
                    autocomplete="off"
                >

            </div>


            <div class="records-label">

                <?php
                echo number_format(
                    $total_records
                );
                ?>

                total records

            </div>

        </div>


        <!-- =====================================================
             REPORT MODULES
        ====================================================== -->

        <div class="section-heading">

            <div>

                <h2>
                    Report Modules
                </h2>

                <p>
                    Select a module to view detailed records and export data
                </p>

            </div>

            <div class="record-count">
                9 available reports
            </div>

        </div>


        <div
            class="report-grid"
            id="reportGrid"
        >

            <?php foreach ($report_modules as $module): ?>

                <?php

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                |--------------------------------------------------------------------------
                | Do not access $counts["tasks_activities"].
                | Tasks & Activities uses the combined value from the
                | existing tasks and activities counts.
                |--------------------------------------------------------------------------
                */

                $module_count = 0;

                if (
                    $module["key"] ===
                    "tasks_activities"
                ) {

                    $module_count =
                        $counts["tasks"] +
                        $counts["activities"];

                } elseif (
                    isset(
                        $counts[
                            $module["key"]
                        ]
                    )
                ) {

                    $module_count =
                        $counts[
                            $module["key"]
                        ];
                }

                ?>

                <div
                    class="report-card"
                    data-category="<?php
                    echo htmlspecialchars(
                        $module["category"]
                    );
                    ?>"
                    data-color="<?php
                    echo htmlspecialchars(
                        $module["color"]
                    );
                    ?>"
                    data-title="<?php
                    echo htmlspecialchars(
                        strtolower(
                            $module["title"]
                        )
                    );
                    ?>"
                >

                    <div class="report-card-top">

                        <div class="report-icon">

                            <?php
                            echo htmlspecialchars(
                                $module["icon"]
                            );
                            ?>

                        </div>

                        <div class="report-category">

                            <?php

                            if (
                                $module["category"] ===
                                "data"
                            ) {

                                echo "DATA";

                            } elseif (
                                $module["category"] ===
                                "sales"
                            ) {

                                echo "SALES";

                            } else {

                                echo "PRODUCTIVITY";
                            }

                            ?>

                        </div>

                    </div>


                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $module["title"]
                        );
                        ?>

                    </h3>


                    <div class="report-description">

                        <?php
                        echo htmlspecialchars(
                            $module["description"]
                        );
                        ?>

                    </div>


                    <div class="report-meta">

                        <div class="report-records">
                            Records
                        </div>

                        <div class="report-record-number">

                            <?php
                            echo number_format(
                                $module_count
                            );
                            ?>

                        </div>

                    </div>


                    <div class="report-actions">

                        <a
                            href="<?php
                            echo htmlspecialchars(
                                $module["url"]
                            );
                            ?>"
                            class="report-btn primary"
                        >
                            View Report
                        </a>

                        <a
                            href="<?php
                            echo htmlspecialchars(
                                $module["export"]
                            );
                            ?>"
                            class="report-btn secondary"
                        >
                            <?php
                            echo htmlspecialchars(
                                $module["export_label"]
                            );
                            ?>
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


        <div
            class="no-results"
            id="noResults"
        >

            <div class="no-results-icon">
                🔍
            </div>

            <strong>
                No reports found
            </strong>

            Try another search or choose a different category.

        </div>


        <!-- =====================================================
             FINANCIAL OVERVIEW
        ====================================================== -->

        <div class="financial-section">

            <div class="financial-header">

                <div>

                    <h2>
                        Financial Overview
                    </h2>

                    <p>
                        High-level values from sales, deals and leads
                    </p>

                </div>

            </div>


            <div class="financial-grid">

                <div class="financial-card">

                    <div class="financial-top">

                        <div class="financial-icon">
                            💰
                        </div>

                        <div class="financial-label">
                            Sales Revenue
                        </div>

                    </div>

                    <div class="financial-value">

                        ₹<?php
                        echo number_format(
                            $total_sales_value,
                            0
                        );
                        ?>

                    </div>

                    <div class="financial-description">
                        Total amount currently recorded in sales
                    </div>

                </div>


                <div class="financial-card">

                    <div class="financial-top">

                        <div class="financial-icon">
                            💼
                        </div>

                        <div class="financial-label">
                            Deal Pipeline
                        </div>

                    </div>

                    <div class="financial-value">

                        ₹<?php
                        echo number_format(
                            $total_deal_value,
                            0
                        );
                        ?>

                    </div>

                    <div class="financial-description">
                        Combined value of all recorded deals
                    </div>

                </div>


                <div class="financial-card">

                    <div class="financial-top">

                        <div class="financial-icon">
                            🎯
                        </div>

                        <div class="financial-label">
                            Lead Pipeline
                        </div>

                    </div>

                    <div class="financial-value">

                        ₹<?php
                        echo number_format(
                            $total_lead_value,
                            0
                        );
                        ?>

                    </div>

                    <div class="financial-description">
                        Combined value of all recorded leads
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <div class="page-footer">

            CRM Reports
            &nbsp;•&nbsp;
            <?php echo date("Y"); ?>
            &nbsp;•&nbsp;
            Business Analytics

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| REPORT FILTER
|--------------------------------------------------------------------------
*/

(function () {

    const filterButtons =
        document.querySelectorAll(
            ".filter-btn"
        );

    const reportCards =
        document.querySelectorAll(
            ".report-card"
        );

    const searchInput =
        document.getElementById(
            "reportSearch"
        );

    const noResults =
        document.getElementById(
            "noResults"
        );

    let activeFilter = "all";


    function applyFilters() {

        const searchTerm =
            searchInput.value
                .trim()
                .toLowerCase();

        let visibleCount = 0;


        reportCards.forEach(
            function (card) {

                const category =
                    card.dataset.category ||
                    "";

                const title =
                    card.dataset.title ||
                    "";


                const categoryMatch =
                    activeFilter === "all" ||
                    category === activeFilter;


                const searchMatch =
                    title.includes(
                        searchTerm
                    );


                if (
                    categoryMatch &&
                    searchMatch
                ) {

                    card.classList.remove(
                        "hidden"
                    );

                    visibleCount++;

                } else {

                    card.classList.add(
                        "hidden"
                    );
                }

            }
        );


        if (visibleCount === 0) {

            noResults.classList.add(
                "visible"
            );

        } else {

            noResults.classList.remove(
                "visible"
            );
        }
    }


    filterButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    filterButtons.forEach(
                        function (item) {

                            item.classList.remove(
                                "active"
                            );

                        }
                    );


                    button.classList.add(
                        "active"
                    );


                    activeFilter =
                        button.dataset.filter ||
                        "all";


                    applyFilters();

                }
            );

        }
    );


    searchInput.addEventListener(
        "input",
        applyFilters
    );

})();


/*
|--------------------------------------------------------------------------
| THEME TOGGLE
|--------------------------------------------------------------------------
*/

(function () {

    const root =
        document.documentElement;

    const themeToggle =
        document.getElementById(
            "themeToggle"
        );


    if (!themeToggle) {
        return;
    }


    function applyTheme(isDark) {

        root.classList.toggle(
            "dark-mode",
            isDark
        );


        themeToggle.textContent =
            isDark
                ? "☀️"
                : "🌙";


        themeToggle.setAttribute(
            "aria-label",
            isDark
                ? "Switch to light mode"
                : "Switch to dark mode"
        );


        themeToggle.setAttribute(
            "title",
            isDark
                ? "Switch to light mode"
                : "Switch to dark mode"
        );
    }


    let isDark = false;


    try {

        isDark =
            localStorage.getItem(
                "crmTheme"
            ) === "dark";

    } catch (error) {

        isDark =
            root.classList.contains(
                "dark-mode"
            );
    }


    applyTheme(isDark);


    themeToggle.addEventListener(
        "click",
        function () {

            isDark =
                !root.classList.contains(
                    "dark-mode"
                );


            applyTheme(
                isDark
            );


            try {

                localStorage.setItem(
                    "crmTheme",
                    isDark
                        ? "dark"
                        : "light"
                );

            } catch (error) {

                // Ignore localStorage errors.
            }

        }
    );

})();

</script>

</body>

</html>