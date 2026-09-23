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
        DATE(companies.created_at) >= :from_date
    ";

    $params[":from_date"] = $from_date;
}

if ($to_date !== "") {

    $where[] = "
        DATE(companies.created_at) <= :to_date
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
| Load Companies
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        companies.id,
        companies.company_name,
        companies.industry,
        companies.phone,
        companies.email,
        companies.website,
        companies.city,
        companies.state,
        companies.country,
        companies.created_at,
        users.name AS created_by

    FROM companies

    LEFT JOIN users
        ON companies.created_by = users.id

    $where_sql

    ORDER BY companies.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/
$total_companies = count($companies);

$industries = [];

$countries = [];

$companies_with_email = 0;

$companies_with_website = 0;

foreach ($companies as $company) {

    $industry = trim(
        $company["industry"] ?? ""
    );

    if ($industry !== "") {
        $industries[$industry] = true;
    }

    $country = trim(
        $company["country"] ?? ""
    );

    if ($country !== "") {
        $countries[$country] = true;
    }

    if (!empty($company["email"])) {
        $companies_with_email++;
    }

    if (!empty($company["website"])) {
        $companies_with_website++;
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


function companyInitial($name)
{
    $name = trim($name);

    if ($name === "") {
        return "C";
    }

    return strtoupper(
        substr($name, 0, 1)
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
        Companies Report | CRM
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

        .companies-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .companies-table th {
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

        .companies-table td {
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

        .companies-table tbody tr:hover {
            background: #f8fbff;
        }

        .companies-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
        ========================================================= */

        .companies-table th:nth-child(1),
        .companies-table td:nth-child(1) {
            width: 5%;
        }

        .companies-table th:nth-child(2),
        .companies-table td:nth-child(2) {
            width: 17%;
        }

        .companies-table th:nth-child(3),
        .companies-table td:nth-child(3) {
            width: 11%;
        }

        .companies-table th:nth-child(4),
        .companies-table td:nth-child(4) {
            width: 10%;
        }

        .companies-table th:nth-child(5),
        .companies-table td:nth-child(5) {
            width: 15%;
        }

        .companies-table th:nth-child(6),
        .companies-table td:nth-child(6) {
            width: 13%;
        }

        .companies-table th:nth-child(7),
        .companies-table td:nth-child(7) {
            width: 12%;
        }

        .companies-table th:nth-child(8),
        .companies-table td:nth-child(8) {
            width: 9%;
        }

        .companies-table th:nth-child(9),
        .companies-table td:nth-child(9) {
            width: 8%;
        }

        /* =========================================================
           COMPANY CELL
        ========================================================= */

        .company-cell {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }

        .company-avatar {
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

        .company-info {
            min-width: 0;
        }

        .company-name {
            display: block;

            margin-bottom: 3px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .company-id {
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
        }

        .email-link:hover {
            text-decoration: underline;
        }

        .website-link {
            color: #2563eb;

            text-decoration: none;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .website-link:hover {
            text-decoration: underline;
        }

        /* =========================================================
           INDUSTRY
        ========================================================= */

        .industry-badge {
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
        html.dark-mode .company-name,
        html.dark-mode .empty-state h3 {
            color: #f8fafc;
        }

        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .table-header span,
        html.dark-mode .company-id,
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

        html.dark-mode .companies-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .companies-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .companies-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .company-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .industry-badge {
            background: #1e293b;

            color: #cbd5e1;
        }

        html.dark-mode .email-link,
        html.dark-mode .website-link {
            color: #60a5fa;
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

            .companies-table {
                min-width: 1050px;
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
                    🏢
                </div>

                <div class="report-title">

                    <h1>
                        Companies Report
                    </h1>

                    <p>
                        Review company records, industries, locations and contact information.
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
                    Filter companies by their creation date.
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
                        href="companies.php"
                        class="btn btn-secondary"
                    >
                        Clear
                    </a>

                    <a
                        href="../exports/companies_csv.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ CSV
                    </a>

                    <a
                        href="../exports/companies_excel.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Excel
                    </a>

                    <a
                        href="../exports/companies_pdf.php?from_date=<?php
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
                    Total Companies
                </div>

                <div class="summary-value">
                    <?php
                    echo $total_companies;
                    ?>
                </div>

                <div class="summary-description">
                    Companies in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Industries
                </div>

                <div class="summary-value">
                    <?php
                    echo count($industries);
                    ?>
                </div>

                <div class="summary-description">
                    Different industries represented
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Countries
                </div>

                <div class="summary-value">
                    <?php
                    echo count($countries);
                    ?>
                </div>

                <div class="summary-description">
                    Countries represented
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    With Website
                </div>

                <div class="summary-value">
                    <?php
                    echo $companies_with_website;
                    ?>
                </div>

                <div class="summary-description">
                    Companies with website details
                </div>

            </div>

        </div>


        <!-- =====================================================
             COMPANY TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Company Details
                    </h2>

                </div>

                <span>
                    <?php echo $total_companies; ?>
                    record<?php
                    echo $total_companies === 1
                        ? ""
                        : "s";
                    ?>
                </span>

            </div>


            <div class="table-wrapper">

                <?php if (!empty($companies)): ?>

                    <table class="companies-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Company
                                </th>

                                <th>
                                    Industry
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Website
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Created By
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($companies as $company): ?>

                                <?php

                                $company_name =
                                    trim(
                                        $company[
                                            "company_name"
                                        ] ?? ""
                                    );

                                $industry =
                                    trim(
                                        $company[
                                            "industry"
                                        ] ?? ""
                                    );

                                $location_parts =
                                    array_filter([
                                        trim(
                                            $company[
                                                "city"
                                            ] ?? ""
                                        ),
                                        trim(
                                            $company[
                                                "state"
                                            ] ?? ""
                                        ),
                                        trim(
                                            $company[
                                                "country"
                                            ] ?? ""
                                        )
                                    ]);

                                $location =
                                    implode(
                                        ", ",
                                        $location_parts
                                    );

                                $website =
                                    trim(
                                        $company[
                                            "website"
                                        ] ?? ""
                                    );

                                ?>

                                <tr>

                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo (int)
                                            $company["id"];
                                        ?>

                                    </td>


                                    <!-- COMPANY -->

                                    <td>

                                        <div class="company-cell">

                                            <div class="company-avatar">

                                                <?php
                                                echo htmlspecialchars(
                                                    companyInitial(
                                                        $company_name
                                                    )
                                                );
                                                ?>

                                            </div>

                                            <div class="company-info">

                                                <span
                                                    class="company-name"
                                                    title="<?php
                                                    echo htmlspecialchars(
                                                        $company_name
                                                    );
                                                    ?>"
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $company_name !== ""
                                                            ? $company_name
                                                            : "Unnamed Company"
                                                    );

                                                    ?>

                                                </span>

                                                <span class="company-id">

                                                    Company #<?php
                                                    echo (int)
                                                        $company[
                                                            "id"
                                                        ];
                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- INDUSTRY -->

                                    <td>

                                        <?php if ($industry !== ""): ?>

                                            <span
                                                class="industry-badge"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $industry
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $industry
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?php if (!empty($company["phone"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $company[
                                                        "phone"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $company[
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


                                    <!-- EMAIL -->

                                    <td>

                                        <?php if (!empty($company["email"])): ?>

                                            <a
                                                href="mailto:<?php
                                                echo htmlspecialchars(
                                                    $company[
                                                        "email"
                                                    ]
                                                );
                                                ?>"
                                                class="email-link"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $company[
                                                        "email"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $company[
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


                                    <!-- WEBSITE -->

                                    <td>

                                        <?php if ($website !== ""): ?>

                                            <?php

                                            $website_url =
                                                $website;

                                            if (
                                                !preg_match(
                                                    "/^https?:\\/\\//i",
                                                    $website_url
                                                )
                                            ) {

                                                $website_url =
                                                    "https://" .
                                                    $website_url;
                                            }

                                            ?>

                                            <a
                                                href="<?php
                                                echo htmlspecialchars(
                                                    $website_url
                                                );
                                                ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="website-link"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $website
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $website
                                                );
                                                ?>

                                            </a>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- LOCATION -->

                                    <td>

                                        <?php if ($location !== ""): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $location
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $location
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CREATED -->

                                    <td>

                                        <span class="cell-text">

                                            <?php
                                            echo htmlspecialchars(
                                                formatReportDate(
                                                    $company[
                                                        "created_at"
                                                    ] ?? ""
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- CREATED BY -->

                                    <td>

                                        <?php if (!empty($company["created_by"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $company[
                                                        "created_by"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $company[
                                                        "created_by"
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
                            🏢
                        </div>

                        <h3>
                            No Companies Found
                        </h3>

                        <p>
                            No company records match the selected date range.
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
                    <?php echo $total_companies; ?>
                    company<?php
                    echo $total_companies === 1
                        ? ""
                        : "ies";
                    ?>
                </span>

                <span>
                    Companies Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>