<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/
$from_date = trim($_GET["from_date"] ?? "");
$to_date = trim($_GET["to_date"] ?? "");

$where = [];
$params = [];

/*
|--------------------------------------------------------------------------
| Date Filters
|--------------------------------------------------------------------------
*/
if ($from_date !== "") {

    $where[] = "
        DATE(leads.created_at) >= :from_date
    ";

    $params[":from_date"] = $from_date;
}

if ($to_date !== "") {

    $where[] = "
        DATE(leads.created_at) <= :to_date
    ";

    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {

    $where_sql =
        " WHERE " .
        implode(" AND ", $where);
}

/*
|--------------------------------------------------------------------------
| Load Leads
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        leads.id,
        leads.lead_name,
        leads.email,
        leads.phone,
        leads.source,
        leads.status,
        leads.lead_value,
        leads.created_at,

        companies.company_name,

        contacts.first_name,
        contacts.last_name,

        users.name AS assigned_name

    FROM leads

    LEFT JOIN companies
        ON leads.company_id = companies.id

    LEFT JOIN contacts
        ON leads.contact_id = contacts.id

    LEFT JOIN users
        ON leads.assigned_to = users.id

    $where_sql

    ORDER BY
        leads.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/
$total_leads = count($leads);

$total_lead_value = 0;

$new_leads = 0;

$qualified_leads = 0;

$converted_leads = 0;

$contacted_leads = 0;

$lost_leads = 0;

$sources = [];

$companies = [];

foreach ($leads as $lead) {

    $total_lead_value +=
        (float) (
            $lead["lead_value"] ?? 0
        );

    $status = strtolower(
        trim(
            $lead["status"] ?? ""
        )
    );

    if ($status === "new") {
        $new_leads++;
    }

    if ($status === "qualified") {
        $qualified_leads++;
    }

    if ($status === "converted") {
        $converted_leads++;
    }

    if ($status === "contacted") {
        $contacted_leads++;
    }

    if ($status === "lost") {
        $lost_leads++;
    }

    $source = trim(
        $lead["source"] ?? ""
    );

    if ($source !== "") {
        $sources[$source] = true;
    }

    $company_name = trim(
        $lead["company_name"] ?? ""
    );

    if ($company_name !== "") {
        $companies[$company_name] = true;
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
function formatReportDate($date)
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date(
        "d M Y",
        $timestamp
    );
}


function formatReportDateTime($date)
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date(
        "d M Y, h:i A",
        $timestamp
    );
}


function leadInitial($name)
{
    $name = trim($name);

    if ($name === "") {
        return "L";
    }

    return strtoupper(
        substr($name, 0, 1)
    );
}


function leadStatusClass($status)
{
    $status = strtolower(
        trim(
            $status ?? ""
        )
    );

    if ($status === "new") {
        return "status-new";
    }

    if ($status === "contacted") {
        return "status-contacted";
    }

    if ($status === "qualified") {
        return "status-qualified";
    }

    if ($status === "converted") {
        return "status-converted";
    }

    if ($status === "lost") {
        return "status-lost";
    }

    return "status-default";
}


function leadStatusLabel($status)
{
    $status = trim(
        $status ?? ""
    );

    if ($status === "") {
        return "Unknown";
    }

    return ucfirst(
        str_replace(
            [
                "-",
                "_"
            ],
            " ",
            $status
        )
    );
}

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
        Leads Report | CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="/crm/assets/css/reports.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        .page-wrap {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .report-page-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 24px 28px;

            margin-bottom: 22px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .report-header-left {
            display: flex;

            align-items: center;

            gap: 15px;

            min-width: 0;
        }

        .report-icon {
            width: 52px;
            height: 52px;

            flex: 0 0 52px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: #dbeafe;

            color: #2563eb;

            font-size: 24px;
        }

        .report-title {
            min-width: 0;
        }

        .report-title h1 {
            margin: 0 0 5px;

            color: #0f172a;

            font-size: 25px;

            line-height: 1.2;

            font-weight: 700;
        }

        .report-title p {
            margin: 0;

            color: #64748b;

            font-size: 13px;
        }

        .back-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 40px;

            padding:
                0
                14px;

            border:
                1px solid #dbe3ed;

            border-radius: 8px;

            background: #ffffff;

            color: #334155;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;
        }

        .back-button:hover {
            background: #f8fafc;

            border-color: #cbd5e1;
        }

        /* =========================================================
           FILTER CARD
        ========================================================= */

        .filter-card {
            padding:
                20px;

            margin-bottom: 20px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .filter-heading {
            margin-bottom: 15px;
        }

        .filter-heading h3 {
            margin: 0 0 4px;

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;
        }

        .filter-heading p {
            margin: 0;

            color: #64748b;

            font-size: 11px;
        }

        .filter-form {
            display: flex;

            align-items: flex-end;

            gap: 12px;

            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;

            flex-direction: column;

            gap: 6px;

            min-width: 165px;
        }

        .filter-group label {
            color: #334155;

            font-size: 11px;

            font-weight: 700;
        }

        .filter-input {
            width: 100%;

            height: 40px;

            padding:
                0
                11px;

            border:
                1px solid #dbe1e8;

            border-radius: 8px;

            background: #ffffff;

            color: #0f172a;

            font-size: 12px;

            outline: none;
        }

        .filter-input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.08);
        }

        .filter-actions {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }

        .btn {
            min-height: 40px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0
                14px;

            border:
                1px solid transparent;

            border-radius: 8px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            white-space: nowrap;

            transition: 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;

            border-color: #2563eb;

            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;

            border-color: #1d4ed8;
        }

        .btn-secondary {
            background: #ffffff;

            border-color: #dbe1e8;

            color: #334155;
        }

        .btn-secondary:hover {
            background: #f8fafc;

            border-color: #cbd5e1;
        }

        .btn-success {
            background: #16a34a;

            border-color: #16a34a;

            color: #ffffff;
        }

        .btn-success:hover {
            background: #15803d;

            border-color: #15803d;
        }

        /* =========================================================
           SUMMARY
        ========================================================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 14px;

            margin-bottom: 20px;
        }

        .summary-card {
            padding:
                17px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 11px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }

        .summary-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }

        .summary-value {
            color: #0f172a;

            font-size: 23px;

            line-height: 1.2;

            font-weight: 700;
        }

        .summary-description {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 10px;
        }

        /* =========================================================
           TABLE CARD
        ========================================================= */

        .table-card {
            width: 100%;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .table-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding:
                17px
                19px;

            border-bottom:
                1px solid #edf1f5;
        }

        .table-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;
        }

        .table-header span {
            color: #94a3b8;

            font-size: 11px;
        }

        .table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        .leads-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .leads-table th {
            height: 52px;

            padding:
                0
                10px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fafc;

            color: #334155;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            text-align: left;

            white-space: nowrap;
        }

        .leads-table td {
            height: 68px;

            padding:
                9px
                10px;

            border-bottom:
                1px solid #edf1f5;

            color: #334155;

            font-size: 12px;

            vertical-align: middle;
        }

        .leads-table tbody tr:hover {
            background: #f8fbff;
        }

        .leads-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
        ========================================================= */

        .leads-table th:nth-child(1),
        .leads-table td:nth-child(1) {
            width: 5%;
        }

        .leads-table th:nth-child(2),
        .leads-table td:nth-child(2) {
            width: 15%;
        }

        .leads-table th:nth-child(3),
        .leads-table td:nth-child(3) {
            width: 14%;
        }

        .leads-table th:nth-child(4),
        .leads-table td:nth-child(4) {
            width: 10%;
        }

        .leads-table th:nth-child(5),
        .leads-table td:nth-child(5) {
            width: 12%;
        }

        .leads-table th:nth-child(6),
        .leads-table td:nth-child(6) {
            width: 12%;
        }

        .leads-table th:nth-child(7),
        .leads-table td:nth-child(7) {
            width: 9%;
        }

        .leads-table th:nth-child(8),
        .leads-table td:nth-child(8) {
            width: 9%;
        }

        .leads-table th:nth-child(9),
        .leads-table td:nth-child(9) {
            width: 8%;
        }

        .leads-table th:nth-child(10),
        .leads-table td:nth-child(10) {
            width: 8%;
        }

        /* =========================================================
           LEAD CELL
        ========================================================= */

        .lead-cell {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }

        .lead-avatar {
            width: 34px;
            height: 34px;

            flex: 0 0 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 12px;

            font-weight: 700;
        }

        .lead-info {
            min-width: 0;
        }

        .lead-name {
            display: block;

            margin-bottom: 3px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .lead-id {
            color: #94a3b8;

            font-size: 10px;
        }

        .cell-text {
            display: block;

            width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #475569;
        }

        .cell-muted {
            color: #94a3b8;
        }

        /* =========================================================
           LINKS
        ========================================================= */

        .email-link {
            color: #2563eb;

            text-decoration: none;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .email-link:hover {
            text-decoration: underline;
        }

        /* =========================================================
           SOURCE
        ========================================================= */

        .source-badge {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                8px;

            border-radius: 999px;

            background: #f1f5f9;

            color: #475569;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            max-width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        /* =========================================================
           STATUS
        ========================================================= */

        .status-badge {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                8px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }

        .status-new {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .status-contacted {
            background: #cffafe;

            color: #0e7490;
        }

        .status-qualified {
            background: #ede9fe;

            color: #6d28d9;
        }

        .status-converted {
            background: #dcfce7;

            color: #15803d;
        }

        .status-lost {
            background: #fee2e2;

            color: #b91c1c;
        }

        .status-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================================================
           VALUE
        ========================================================= */

        .lead-value {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .table-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            min-height: 52px;

            padding:
                0
                18px;

            border-top:
                1px solid #edf1f5;

            color: #94a3b8;

            font-size: 11px;
        }

        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {
            padding:
                50px
                20px;

            text-align: center;
        }

        .empty-icon {
            margin-bottom: 10px;

            font-size: 34px;
        }

        .empty-state h3 {
            margin:
                0
                0
                6px;

            color: #334155;

            font-size: 16px;
        }

        .empty-state p {
            margin: 0;

            color: #94a3b8;

            font-size: 12px;
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        html.dark-mode body {
            background: #0f172a;

            color: #e2e8f0;
        }

        html.dark-mode .report-page-header,
        html.dark-mode .filter-card,
        html.dark-mode .summary-card,
        html.dark-mode .table-card {
            background: #111827;

            border-color: #1f2937;

            box-shadow: none;
        }

        html.dark-mode .report-title h1,
        html.dark-mode .filter-heading h3,
        html.dark-mode .summary-value,
        html.dark-mode .table-header h2,
        html.dark-mode .lead-name,
        html.dark-mode .empty-state h3,
        html.dark-mode .lead-value {
            color: #f8fafc;
        }

        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .table-header span,
        html.dark-mode .lead-id,
        html.dark-mode .cell-muted,
        html.dark-mode .table-footer {
            color: #94a3b8;
        }

        html.dark-mode .back-button,
        html.dark-mode .filter-input,
        html.dark-mode .btn-secondary {
            background: #1e293b;

            border-color: #334155;

            color: #e2e8f0;
        }

        html.dark-mode .back-button:hover,
        html.dark-mode .btn-secondary:hover {
            background: #273449;
        }

        html.dark-mode .filter-input {
            background: #0f172a;
        }

        html.dark-mode .leads-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .leads-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .leads-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .lead-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .source-badge {
            background: #1e293b;

            color: #cbd5e1;
        }

        html.dark-mode .email-link {
            color: #60a5fa;
        }

        html.dark-mode .status-new {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .status-contacted {
            background: #083344;

            color: #67e8f9;
        }

        html.dark-mode .status-qualified {
            background: #2e1065;

            color: #c4b5fd;
        }

        html.dark-mode .status-converted {
            background: #052e16;

            color: #86efac;
        }

        html.dark-mode .status-lost {
            background: #450a0a;

            color: #fca5a5;
        }

        html.dark-mode .status-default {
            background: #1e293b;

            color: #cbd5e1;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .filter-form {
                align-items: stretch;
            }

        }

        @media (max-width: 760px) {

            .main-content {
                padding: 20px;
            }

            .report-page-header {
                flex-direction: column;

                align-items: stretch;
            }

            .back-button {
                width: 100%;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .filter-actions {
                width: 100%;
            }

            .filter-actions .btn {
                flex: 1;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .table-wrapper {
                overflow-x: auto;
            }

            .leads-table {
                min-width: 1200px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-wrap">


        <!-- =====================================================
             REPORT HEADER
        ====================================================== -->

        <div class="report-page-header">

            <div class="report-header-left">

                <div class="report-icon">
                    🎯
                </div>

                <div class="report-title">

                    <h1>
                        Leads Report
                    </h1>

                    <p>
                        Review lead records, sources, statuses, values and assignments.
                    </p>

                </div>

            </div>

            <a
                href="index.php"
                class="back-button"
            >
                ← Reports
            </a>

        </div>


        <!-- =====================================================
             FILTERS
        ====================================================== -->

        <div class="filter-card">

            <div class="filter-heading">

                <h3>
                    Report Filters
                </h3>

                <p>
                    Filter leads by their creation date.
                </p>

            </div>


            <form
                method="GET"
                class="filter-form"
            >

                <div class="filter-group">

                    <label for="from_date">
                        From Date
                    </label>

                    <input
                        type="date"
                        id="from_date"
                        name="from_date"
                        class="filter-input"
                        value="<?php
                        echo htmlspecialchars(
                            $from_date
                        );
                        ?>"
                    >

                </div>


                <div class="filter-group">

                    <label for="to_date">
                        To Date
                    </label>

                    <input
                        type="date"
                        id="to_date"
                        name="to_date"
                        class="filter-input"
                        value="<?php
                        echo htmlspecialchars(
                            $to_date
                        );
                        ?>"
                    >

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Apply Filter
                    </button>


                    <a
                        href="leads.php"
                        class="btn btn-secondary"
                    >
                        Clear
                    </a>


                    <a
                        href="../exports/leads_csv.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ CSV
                    </a>


                    <a
                        href="../exports/leads_excel.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Excel
                    </a>


                    <a
                        href="../exports/leads_pdf.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ PDF
                    </a>

                </div>

            </form>

        </div>


        <!-- =====================================================
             SUMMARY
        ====================================================== -->

        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Leads
                </div>

                <div class="summary-value">

                    <?php
                    echo $total_leads;
                    ?>

                </div>

                <div class="summary-description">
                    Leads in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Lead Value
                </div>

                <div class="summary-value">

                    ₹<?php
                    echo number_format(
                        $total_lead_value,
                        2
                    );
                    ?>

                </div>

                <div class="summary-description">
                    Combined value of all leads
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Qualified Leads
                </div>

                <div class="summary-value">

                    <?php
                    echo $qualified_leads;
                    ?>

                </div>

                <div class="summary-description">
                    Leads marked as qualified
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Converted Leads
                </div>

                <div class="summary-value">

                    <?php
                    echo $converted_leads;
                    ?>

                </div>

                <div class="summary-description">
                    Converted lead records
                </div>

            </div>

        </div>


        <!-- =====================================================
             LEAD TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Lead Details
                    </h2>

                </div>

                <span>

                    <?php
                    echo $total_leads;
                    ?>

                    record<?php

                    echo $total_leads === 1
                        ? ""
                        : "s";

                    ?>

                </span>

            </div>


            <div class="table-wrapper">

                <?php if (!empty($leads)): ?>

                    <table class="leads-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Lead
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Company
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Source
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Lead Value
                                </th>

                                <th>
                                    Created
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($leads as $lead): ?>

                                <?php

                                $lead_name =
                                    trim(
                                        $lead[
                                            "lead_name"
                                        ] ?? ""
                                    );

                                $contact_name =
                                    trim(
                                        ($lead[
                                            "first_name"
                                        ] ?? "") .
                                        " " .
                                        ($lead[
                                            "last_name"
                                        ] ?? "")
                                    );

                                $source =
                                    trim(
                                        $lead[
                                            "source"
                                        ] ?? ""
                                    );

                                $status =
                                    trim(
                                        $lead[
                                            "status"
                                        ] ?? ""
                                    );

                                $status_class =
                                    leadStatusClass(
                                        $status
                                    );

                                $status_label =
                                    leadStatusLabel(
                                        $status
                                    );

                                ?>

                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo (int)
                                            $lead["id"];
                                        ?>

                                    </td>


                                    <!-- LEAD -->

                                    <td>

                                        <div class="lead-cell">

                                            <div class="lead-avatar">

                                                <?php
                                                echo htmlspecialchars(
                                                    leadInitial(
                                                        $lead_name
                                                    )
                                                );
                                                ?>

                                            </div>


                                            <div class="lead-info">

                                                <span
                                                    class="lead-name"
                                                    title="<?php
                                                    echo htmlspecialchars(
                                                        $lead_name
                                                    );
                                                    ?>"
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $lead_name !== ""
                                                            ? $lead_name
                                                            : "Unnamed Lead"
                                                    );

                                                    ?>

                                                </span>


                                                <span class="lead-id">

                                                    Lead #<?php

                                                    echo (int)
                                                        $lead["id"];

                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <?php if (!empty($lead["email"])): ?>

                                            <a
                                                href="mailto:<?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "email"
                                                    ]
                                                );
                                                ?>"
                                                class="email-link"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "email"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "email"
                                                    ]
                                                );
                                                ?>

                                            </a>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?php if (!empty($lead["phone"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "phone"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "phone"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- COMPANY -->

                                    <td>

                                        <?php if (!empty($lead["company_name"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "company_name"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        "company_name"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CONTACT -->

                                    <td>

                                        <?php if ($contact_name !== ""): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $contact_name
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $contact_name
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- SOURCE -->

                                    <td>

                                        <?php if ($source !== ""): ?>

                                            <span
                                                class="source-badge"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $source
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $source
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status-badge <?php
                                            echo htmlspecialchars(
                                                $status_class
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $status_label
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- VALUE -->

                                    <td>

                                        <span class="lead-value">

                                            ₹<?php

                                            echo number_format(
                                                (float) (
                                                    $lead[
                                                        "lead_value"
                                                    ] ?? 0
                                                ),
                                                2
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- CREATED -->

                                    <td>

                                        <span class="cell-text">

                                            <?php

                                            echo htmlspecialchars(
                                                formatReportDate(
                                                    $lead[
                                                        "created_at"
                                                    ] ?? ""
                                                )
                                            );

                                            ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            🎯
                        </div>

                        <h3>
                            No Leads Found
                        </h3>

                        <p>
                            No lead records match the selected date range.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 TABLE FOOTER
            ================================================== -->

            <div class="table-footer">

                <span>

                    Showing
                    <?php
                    echo $total_leads;
                    ?>
                    lead<?php

                    echo $total_leads === 1
                        ? ""
                        : "s";

                    ?>

                </span>

                <span>
                    Leads Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>