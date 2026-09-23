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
        DATE(quotes.created_at) >= :from_date
    ";

    $params[":from_date"] = $from_date;
}

if ($to_date !== "") {

    $where[] = "
        DATE(quotes.created_at) <= :to_date
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
| Load Quotes
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        quotes.id,
        quotes.quote_number,
        quotes.company_id,
        quotes.contact_id,
        quotes.customer_id,
        quotes.deal_id,
        quotes.total_amount,
        quotes.status,
        quotes.valid_until,
        quotes.created_at,

        companies.company_name,

        customers.customer_code,

        deals.title AS deal_title

    FROM quotes

    LEFT JOIN companies
        ON quotes.company_id = companies.id

    LEFT JOIN customers
        ON quotes.customer_id = customers.id

    LEFT JOIN deals
        ON quotes.deal_id = deals.id

    $where_sql

    ORDER BY
        quotes.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/
$total_quotes = count($quotes);

$total_quote_value = 0;

$accepted_quote_value = 0;

$draft_quotes = 0;

$sent_quotes = 0;

$rejected_quotes = 0;

$status_counts = [];

foreach ($quotes as $quote) {

    $total_quote_value +=
        (float) (
            $quote["total_amount"] ?? 0
        );

    $status = strtolower(
        trim(
            $quote["status"] ?? ""
        )
    );

    if ($status === "accepted") {

        $accepted_quote_value +=
            (float) (
                $quote["total_amount"] ?? 0
            );
    }

    if ($status === "draft") {
        $draft_quotes++;
    }

    if ($status === "sent") {
        $sent_quotes++;
    }

    if ($status === "rejected") {
        $rejected_quotes++;
    }

    if ($status !== "") {

        if (!isset($status_counts[$status])) {
            $status_counts[$status] = 0;
        }

        $status_counts[$status]++;
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


function quoteInitial($quote_number)
{
    $quote_number = trim($quote_number);

    if ($quote_number === "") {
        return "Q";
    }

    return strtoupper(
        substr($quote_number, 0, 1)
    );
}


function quoteStatusClass($status)
{
    $status = strtolower(
        trim(
            $status ?? ""
        )
    );

    if ($status === "draft") {
        return "status-draft";
    }

    if ($status === "sent") {
        return "status-sent";
    }

    if ($status === "accepted") {
        return "status-accepted";
    }

    if ($status === "rejected") {
        return "status-rejected";
    }

    return "status-default";
}


function quoteStatusLabel($status)
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
        Quotes Report | CRM
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

            padding:
                24px
                28px;

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

        .quotes-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .quotes-table th {
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

        .quotes-table td {
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

        .quotes-table tbody tr:hover {
            background: #f8fbff;
        }

        .quotes-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
        ========================================================= */

        .quotes-table th:nth-child(1),
        .quotes-table td:nth-child(1) {
            width: 5%;
        }

        .quotes-table th:nth-child(2),
        .quotes-table td:nth-child(2) {
            width: 15%;
        }

        .quotes-table th:nth-child(3),
        .quotes-table td:nth-child(3) {
            width: 16%;
        }

        .quotes-table th:nth-child(4),
        .quotes-table td:nth-child(4) {
            width: 11%;
        }

        .quotes-table th:nth-child(5),
        .quotes-table td:nth-child(5) {
            width: 15%;
        }

        .quotes-table th:nth-child(6),
        .quotes-table td:nth-child(6) {
            width: 11%;
        }

        .quotes-table th:nth-child(7),
        .quotes-table td:nth-child(7) {
            width: 9%;
        }

        .quotes-table th:nth-child(8),
        .quotes-table td:nth-child(8) {
            width: 9%;
        }

        .quotes-table th:nth-child(9),
        .quotes-table td:nth-child(9) {
            width: 9%;
        }

        /* =========================================================
           QUOTE CELL
        ========================================================= */

        .quote-cell {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }

        .quote-avatar {
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

        .quote-info {
            min-width: 0;
        }

        .quote-number {
            display: block;

            margin-bottom: 3px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .quote-id {
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
           CUSTOMER / COMPANY / DEAL
        ========================================================= */

        .company-name {
            color: #334155;

            font-weight: 600;
        }

        .customer-badge {
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

            max-width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .deal-link {
            display: block;

            color: #2563eb;

            text-decoration: none;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .deal-link:hover {
            text-decoration: underline;
        }

        /* =========================================================
           TOTAL VALUE
        ========================================================= */

        .quote-value {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
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

        .status-draft {
            background: #fef3c7;

            color: #b45309;
        }

        .status-sent {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;

            color: #15803d;
        }

        .status-rejected {
            background: #fee2e2;

            color: #b91c1c;
        }

        .status-default {
            background: #f1f5f9;

            color: #475569;
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
        html.dark-mode .quote-number,
        html.dark-mode .quote-value,
        html.dark-mode .empty-state h3,
        html.dark-mode .company-name {
            color: #f8fafc;
        }

        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .table-header span,
        html.dark-mode .quote-id,
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

        html.dark-mode .quotes-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .quotes-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .quotes-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .quote-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .customer-badge {
            background: #1e293b;

            color: #cbd5e1;
        }

        html.dark-mode .deal-link {
            color: #60a5fa;
        }

        html.dark-mode .status-draft {
            background: #451a03;

            color: #fcd34d;
        }

        html.dark-mode .status-sent {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .status-accepted {
            background: #052e16;

            color: #86efac;
        }

        html.dark-mode .status-rejected {
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

            .quotes-table {
                min-width: 1150px;
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
                    📝
                </div>

                <div class="report-title">

                    <h1>
                        Quotes Report
                    </h1>

                    <p>
                        Review quotations, values, customers, deals and quote statuses.
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
                    Filter quotes by their creation date.
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
                        href="quotes.php"
                        class="btn btn-secondary"
                    >
                        Clear
                    </a>


                    <a
                        href="../exports/quotes_csv.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ CSV
                    </a>


                    <a
                        href="../exports/quotes_excel.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Excel
                    </a>


                    <a
                        href="../exports/quotes_pdf.php?from_date=<?php
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
                    Total Quotes
                </div>

                <div class="summary-value">

                    <?php
                    echo $total_quotes;
                    ?>

                </div>

                <div class="summary-description">
                    Quotes in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Quote Value
                </div>

                <div class="summary-value">

                    ₹<?php

                    echo number_format(
                        $total_quote_value,
                        2
                    );

                    ?>

                </div>

                <div class="summary-description">
                    Combined value of all quotes
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Accepted Quote Value
                </div>

                <div class="summary-value">

                    ₹<?php

                    echo number_format(
                        $accepted_quote_value,
                        2
                    );

                    ?>

                </div>

                <div class="summary-description">
                    Value of accepted quotes
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Draft Quotes
                </div>

                <div class="summary-value">

                    <?php
                    echo $draft_quotes;
                    ?>

                </div>

                <div class="summary-description">
                    Quotes currently in draft
                </div>

            </div>

        </div>


        <!-- =====================================================
             QUOTE TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Quote Details
                    </h2>

                </div>

                <span>

                    <?php
                    echo $total_quotes;
                    ?>

                    record<?php

                    echo $total_quotes === 1
                        ? ""
                        : "s";

                    ?>

                </span>

            </div>


            <div class="table-wrapper">

                <?php if (!empty($quotes)): ?>

                    <table class="quotes-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Quote
                                </th>

                                <th>
                                    Company
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Deal
                                </th>

                                <th>
                                    Total Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Valid Until
                                </th>

                                <th>
                                    Created
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($quotes as $quote): ?>

                                <?php

                                $quote_number =
                                    trim(
                                        $quote[
                                            "quote_number"
                                        ] ?? ""
                                    );

                                $company_name =
                                    trim(
                                        $quote[
                                            "company_name"
                                        ] ?? ""
                                    );

                                $customer_code =
                                    trim(
                                        $quote[
                                            "customer_code"
                                        ] ?? ""
                                    );

                                $deal_title =
                                    trim(
                                        $quote[
                                            "deal_title"
                                        ] ?? ""
                                    );

                                $status =
                                    trim(
                                        $quote[
                                            "status"
                                        ] ?? ""
                                    );

                                $status_class =
                                    quoteStatusClass(
                                        $status
                                    );

                                $status_label =
                                    quoteStatusLabel(
                                        $status
                                    );

                                ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo (int)
                                            $quote["id"];
                                        ?>

                                    </td>


                                    <!-- QUOTE -->

                                    <td>

                                        <div class="quote-cell">

                                            <div class="quote-avatar">

                                                <?php
                                                echo htmlspecialchars(
                                                    quoteInitial(
                                                        $quote_number
                                                    )
                                                );
                                                ?>

                                            </div>


                                            <div class="quote-info">

                                                <span
                                                    class="quote-number"
                                                    title="<?php
                                                    echo htmlspecialchars(
                                                        $quote_number
                                                    );
                                                    ?>"
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $quote_number !== ""
                                                            ? $quote_number
                                                            : "Unnamed Quote"
                                                    );

                                                    ?>

                                                </span>


                                                <span class="quote-id">

                                                    Quote #<?php

                                                    echo (int)
                                                        $quote["id"];

                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- COMPANY -->

                                    <td>

                                        <?php if ($company_name !== ""): ?>

                                            <span
                                                class="cell-text company-name"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $company_name
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $company_name
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <?php if ($customer_code !== ""): ?>

                                            <span
                                                class="customer-badge"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $customer_code
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $customer_code
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- DEAL -->

                                    <td>

                                        <?php if ($deal_title !== ""): ?>

                                            <span
                                                class="deal-link"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $deal_title
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $deal_title
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- TOTAL -->

                                    <td>

                                        <span class="quote-value">

                                            ₹<?php

                                            echo number_format(
                                                (float) (
                                                    $quote[
                                                        "total_amount"
                                                    ] ?? 0
                                                ),
                                                2
                                            );

                                            ?>

                                        </span>

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


                                    <!-- VALID UNTIL -->

                                    <td>

                                        <span class="cell-text">

                                            <?php

                                            echo htmlspecialchars(
                                                formatReportDate(
                                                    $quote[
                                                        "valid_until"
                                                    ] ?? ""
                                                )
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
                                                    $quote[
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
                            📝
                        </div>

                        <h3>
                            No Quotes Found
                        </h3>

                        <p>
                            No quote records match the selected date range.
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
                    echo $total_quotes;
                    ?>
                    quote<?php

                    echo $total_quotes === 1
                        ? ""
                        : "s";

                    ?>

                </span>

                <span>
                    Quotes Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>