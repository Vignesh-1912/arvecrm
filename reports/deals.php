<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/
$from_date = trim($_GET["from_date"] ?? "");
$to_date   = trim($_GET["to_date"] ?? "");

$where = [];
$params = [];

if ($from_date !== "") {

    $where[] = "
        DATE(deals.expected_close_date) >= :from_date
    ";

    $params[":from_date"] = $from_date;
}

if ($to_date !== "") {

    $where[] = "
        DATE(deals.expected_close_date) <= :to_date
    ";

    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/*
|--------------------------------------------------------------------------
| GET DEALS
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        deals.id,
        deals.title,
        deals.amount,
        deals.stage,
        deals.probability,
        deals.expected_close_date,

        companies.company_name,

        contacts.first_name,
        contacts.last_name,

        users.name AS assigned_name

    FROM deals

    LEFT JOIN companies
        ON deals.company_id = companies.id

    LEFT JOIN contacts
        ON deals.contact_id = contacts.id

    LEFT JOIN users
        ON deals.assigned_to = users.id

    $where_sql

    ORDER BY
        deals.expected_close_date ASC,
        deals.id DESC
";

$stmt = $conn->prepare($sql);

$stmt->execute($params);

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/
$total_deals = count($deals);

$total_value = 0;

$weighted_value = 0;

$won_value = 0;

$lost_value = 0;

$open_value = 0;

$stages = [];

foreach ($deals as $deal) {

    $amount = (float) (
        $deal["amount"] ?? 0
    );

    $probability = (float) (
        $deal["probability"] ?? 0
    );

    $stage = strtolower(
        trim(
            $deal["stage"] ?? ""
        )
    );

    $total_value += $amount;

    $weighted_value +=
        $amount *
        $probability /
        100;

    if (
        $stage === "closed_won" ||
        $stage === "won"
    ) {

        $won_value += $amount;

    } elseif (
        $stage === "closed_lost" ||
        $stage === "lost"
    ) {

        $lost_value += $amount;

    } else {

        $open_value += $amount;
    }

    if ($stage !== "") {
        $stages[$stage] = true;
    }
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function formatDealDate($date)
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


function formatDealStage($stage)
{
    $stage = strtolower(
        trim($stage)
    );

    $labels = [
        "new" => "New",
        "prospecting" => "Prospecting",
        "qualification" => "Qualification",
        "proposal" => "Proposal",
        "negotiation" => "Negotiation",
        "closed_won" => "Closed Won",
        "closed_lost" => "Closed Lost",
        "won" => "Won",
        "lost" => "Lost"
    ];

    return $labels[$stage]
        ?? ucwords(
            str_replace(
                "_",
                " ",
                $stage
            )
        );
}


function dealStageClass($stage)
{
    $stage = strtolower(
        trim($stage)
    );

    if (
        $stage === "closed_won" ||
        $stage === "won"
    ) {
        return "stage-won";
    }

    if (
        $stage === "closed_lost" ||
        $stage === "lost"
    ) {
        return "stage-lost";
    }

    if ($stage === "negotiation") {
        return "stage-negotiation";
    }

    if ($stage === "proposal") {
        return "stage-proposal";
    }

    if ($stage === "qualification") {
        return "stage-qualification";
    }

    if ($stage === "prospecting") {
        return "stage-prospecting";
    }

    if ($stage === "new") {
        return "stage-new";
    }

    return "stage-default";
}


function dealInitial($title)
{
    $title = trim($title);

    if ($title === "") {
        return "D";
    }

    return strtoupper(
        substr(
            $title,
            0,
            1
        )
    );
}


function isDealOverdue($date, $stage)
{
    if (empty($date)) {
        return false;
    }

    $stage = strtolower(
        trim($stage)
    );

    if (
        $stage === "closed_won" ||
        $stage === "closed_lost" ||
        $stage === "won" ||
        $stage === "lost"
    ) {
        return false;
    }

    return strtotime($date) < strtotime(date("Y-m-d"));
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
        Deals Report | CRM
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
            padding: 20px;

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

            font-size: 21px;

            line-height: 1.2;

            font-weight: 700;

            word-break: break-word;
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

        .deals-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .deals-table th {
            height: 52px;

            padding:
                0
                9px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fafc;

            color: #334155;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.25px;

            text-align: left;

            white-space: nowrap;

            overflow: hidden;
        }

        .deals-table td {
            height: 68px;

            padding:
                9px;

            border-bottom:
                1px solid #edf1f5;

            color: #334155;

            font-size: 12px;

            vertical-align: middle;

            overflow: hidden;
        }

        .deals-table tbody tr:hover {
            background: #f8fbff;
        }

        .deals-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
        ========================================================= */

        .deals-table th:nth-child(1),
        .deals-table td:nth-child(1) {
            width: 5%;
        }

        .deals-table th:nth-child(2),
        .deals-table td:nth-child(2) {
            width: 18%;
        }

        .deals-table th:nth-child(3),
        .deals-table td:nth-child(3) {
            width: 14%;
        }

        .deals-table th:nth-child(4),
        .deals-table td:nth-child(4) {
            width: 13%;
        }

        .deals-table th:nth-child(5),
        .deals-table td:nth-child(5) {
            width: 11%;
        }

        .deals-table th:nth-child(6),
        .deals-table td:nth-child(6) {
            width: 12%;
        }

        .deals-table th:nth-child(7),
        .deals-table td:nth-child(7) {
            width: 9%;
        }

        .deals-table th:nth-child(8),
        .deals-table td:nth-child(8) {
            width: 10%;
        }

        .deals-table th:nth-child(9),
        .deals-table td:nth-child(9) {
            width: 8%;
        }

        /* =========================================================
           DEAL CELL
        ========================================================= */

        .deal-cell {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }

        .deal-avatar {
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

        .deal-info {
            min-width: 0;
        }

        .deal-title {
            display: block;

            margin-bottom: 3px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .deal-id {
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
           AMOUNT
        ========================================================= */

        .amount-value {
            display: block;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================================================
           STAGE
        ========================================================= */

        .stage-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                6px
                9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            max-width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .stage-new {
            background: #e0f2fe;

            color: #075985;
        }

        .stage-prospecting {
            background: #f1f5f9;

            color: #475569;
        }

        .stage-qualification {
            background: #ede9fe;

            color: #6d28d9;
        }

        .stage-proposal {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .stage-negotiation {
            background: #fef3c7;

            color: #92400e;
        }

        .stage-won {
            background: #dcfce7;

            color: #166534;
        }

        .stage-lost {
            background: #fee2e2;

            color: #991b1b;
        }

        .stage-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================================================
           PROBABILITY
        ========================================================= */

        .probability-wrap {
            display: flex;

            align-items: center;

            gap: 6px;

            min-width: 65px;
        }

        .probability-track {
            width: 38px;

            height: 5px;

            overflow: hidden;

            border-radius: 99px;

            background: #e2e8f0;
        }

        .probability-bar {
            height: 100%;

            border-radius: 99px;

            background: #2563eb;
        }

        .probability-text {
            color: #475569;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================================================
           EXPECTED CLOSE
        ========================================================= */

        .close-date {
            display: block;

            color: #475569;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }

        .close-date.overdue {
            color: #dc2626;

            font-weight: 700;
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
           EMPTY
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
        html.dark-mode .deal-title,
        html.dark-mode .amount-value,
        html.dark-mode .empty-state h3 {
            color: #f8fafc;
        }

        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .table-header span,
        html.dark-mode .deal-id,
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

        html.dark-mode .deals-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .deals-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .deals-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .deal-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .amount-value,
        html.dark-mode .cell-text {
            color: #cbd5e1;
        }

        html.dark-mode .probability-track {
            background: #334155;
        }

        html.dark-mode .probability-text,
        html.dark-mode .close-date {
            color: #cbd5e1;
        }

        html.dark-mode .stage-new {
            background: #082f49;

            color: #7dd3fc;
        }

        html.dark-mode .stage-prospecting {
            background: #1e293b;

            color: #cbd5e1;
        }

        html.dark-mode .stage-qualification {
            background: #2e1065;

            color: #c4b5fd;
        }

        html.dark-mode .stage-proposal {
            background: #172554;

            color: #93c5fd;
        }

        html.dark-mode .stage-negotiation {
            background: #451a03;

            color: #fde68a;
        }

        html.dark-mode .stage-won {
            background: #052e16;

            color: #86efac;
        }

        html.dark-mode .stage-lost {
            background: #450a0a;

            color: #fca5a5;
        }

        html.dark-mode .stage-default {
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

            .deals-table {
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
                    💼
                </div>

                <div class="report-title">

                    <h1>
                        Deals Report
                    </h1>

                    <p>
                        Review pipeline value, deal stages, probabilities and expected close dates.
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
                    Expected Close Date Filter
                </h3>

                <p>
                    Filter deals by their expected close date.
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
                        href="deals.php"
                        class="btn btn-secondary"
                    >
                        Clear
                    </a>


                    <a
                        href="../exports/deals_csv.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ CSV
                    </a>


                    <a
                        href="../exports/deals_excel.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Excel
                    </a>


                    <a
                        href="../exports/deals_pdf.php?from_date=<?php
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
                    Total Deals
                </div>

                <div class="summary-value">
                    <?php
                    echo $total_deals;
                    ?>
                </div>

                <div class="summary-description">
                    Deals in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Deal Value
                </div>

                <div class="summary-value">
                    ₹<?php
                    echo number_format(
                        $total_value,
                        2
                    );
                    ?>
                </div>

                <div class="summary-description">
                    Combined deal amount
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Weighted Deal Value
                </div>

                <div class="summary-value">
                    ₹<?php
                    echo number_format(
                        $weighted_value,
                        2
                    );
                    ?>
                </div>

                <div class="summary-description">
                    Probability-adjusted pipeline
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Won Deal Value
                </div>

                <div class="summary-value">
                    ₹<?php
                    echo number_format(
                        $won_value,
                        2
                    );
                    ?>
                </div>

                <div class="summary-description">
                    Closed won deal value
                </div>

            </div>

        </div>


        <!-- =====================================================
             DEAL TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Deal Details
                    </h2>

                </div>

                <span>

                    <?php
                    echo $total_deals;
                    ?>

                    deal<?php
                    echo $total_deals === 1
                        ? ""
                        : "s";
                    ?>

                </span>

            </div>


            <div class="table-wrapper">

                <?php if (!empty($deals)): ?>

                    <table class="deals-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Deal
                                </th>

                                <th>
                                    Company
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Stage
                                </th>

                                <th>
                                    Probability
                                </th>

                                <th>
                                    Expected Close
                                </th>

                                <th>
                                    Assigned To
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($deals as $deal): ?>

                                <?php

                                $deal_title = trim(
                                    $deal["title"] ?? ""
                                );

                                $company_name = trim(
                                    $deal[
                                        "company_name"
                                    ] ?? ""
                                );

                                $first_name = trim(
                                    $deal[
                                        "first_name"
                                    ] ?? ""
                                );

                                $last_name = trim(
                                    $deal[
                                        "last_name"
                                    ] ?? ""
                                );

                                $contact_name = trim(
                                    $first_name .
                                    " " .
                                    $last_name
                                );

                                $stage = strtolower(
                                    trim(
                                        $deal[
                                            "stage"
                                        ] ?? ""
                                    )
                                );

                                $probability = max(
                                    0,
                                    min(
                                        100,
                                        (int) (
                                            $deal[
                                                "probability"
                                            ] ?? 0
                                        )
                                    )
                                );

                                $is_overdue =
                                    isDealOverdue(
                                        $deal[
                                            "expected_close_date"
                                        ] ?? "",
                                        $stage
                                    );

                                ?>

                                <tr>

                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo (int)
                                            $deal["id"];
                                        ?>

                                    </td>


                                    <!-- DEAL -->

                                    <td>

                                        <div class="deal-cell">

                                            <div class="deal-avatar">

                                                <?php
                                                echo htmlspecialchars(
                                                    dealInitial(
                                                        $deal_title
                                                    )
                                                );
                                                ?>

                                            </div>


                                            <div class="deal-info">

                                                <span
                                                    class="deal-title"
                                                    title="<?php
                                                    echo htmlspecialchars(
                                                        $deal_title
                                                    );
                                                    ?>"
                                                >

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $deal_title !== ""
                                                            ? $deal_title
                                                            : "Untitled Deal"
                                                    );
                                                    ?>

                                                </span>


                                                <span class="deal-id">

                                                    Deal #<?php
                                                    echo (int)
                                                        $deal["id"];
                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- COMPANY -->

                                    <td>

                                        <?php if ($company_name !== ""): ?>

                                            <span
                                                class="cell-text"
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


                                    <!-- AMOUNT -->

                                    <td>

                                        <span class="amount-value">

                                            ₹<?php
                                            echo number_format(
                                                (float) (
                                                    $deal[
                                                        "amount"
                                                    ] ?? 0
                                                ),
                                                2
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- STAGE -->

                                    <td>

                                        <span
                                            class="stage-badge <?php
                                            echo htmlspecialchars(
                                                dealStageClass(
                                                    $stage
                                                )
                                            );
                                            ?>"
                                            title="<?php
                                            echo htmlspecialchars(
                                                formatDealStage(
                                                    $stage
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                formatDealStage(
                                                    $stage
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- PROBABILITY -->

                                    <td>

                                        <div class="probability-wrap">

                                            <div class="probability-track">

                                                <div
                                                    class="probability-bar"
                                                    style="width: <?php
                                                    echo $probability;
                                                    ?>%;"
                                                ></div>

                                            </div>

                                            <span class="probability-text">

                                                <?php
                                                echo $probability;
                                                ?>%

                                            </span>

                                        </div>

                                    </td>


                                    <!-- EXPECTED CLOSE -->

                                    <td>

                                        <?php if (!empty($deal["expected_close_date"])): ?>

                                            <span
                                                class="close-date <?php
                                                echo $is_overdue
                                                    ? "overdue"
                                                    : "";
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    formatDealDate(
                                                        $deal[
                                                            "expected_close_date"
                                                        ]
                                                    )
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ASSIGNED -->

                                    <td>

                                        <?php if (!empty($deal["assigned_name"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $deal[
                                                        "assigned_name"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $deal[
                                                        "assigned_name"
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

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            💼
                        </div>

                        <h3>
                            No Deals Found
                        </h3>

                        <p>
                            No deals match the selected expected close date range.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="table-footer">

                <span>

                    Showing

                    <?php
                    echo $total_deals;
                    ?>

                    deal<?php
                    echo $total_deals === 1
                        ? ""
                        : "s";
                    ?>

                </span>

                <span>
                    Deals Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>