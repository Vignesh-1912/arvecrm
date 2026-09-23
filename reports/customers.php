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
        DATE(customers.created_at) >= :from_date
    ";

    $params[":from_date"] = $from_date;
}

if ($to_date !== "") {

    $where[] = "
        DATE(customers.created_at) <= :to_date
    ";

    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/*
|--------------------------------------------------------------------------
| GET CUSTOMERS
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        customers.*,

        companies.company_name,

        contacts.first_name,
        contacts.last_name,
        contacts.email AS contact_email,
        contacts.phone AS contact_phone

    FROM customers

    LEFT JOIN companies
        ON customers.company_id = companies.id

    LEFT JOIN contacts
        ON customers.contact_id = contacts.id

    $where_sql

    ORDER BY customers.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CUSTOMER SUMMARY
|--------------------------------------------------------------------------
*/
$total_customers = count($customers);

$active_customers = 0;
$inactive_customers = 0;
$other_status_customers = 0;

$total_credit_limit = 0;

$customer_types = [];
$companies = [];

$customers_with_company = 0;
$customers_with_contact = 0;

foreach ($customers as $customer) {

    $status = strtolower(
        trim($customer["status"] ?? "")
    );

    if ($status === "active") {

        $active_customers++;

    } elseif ($status === "inactive") {

        $inactive_customers++;

    } elseif ($status !== "") {

        $other_status_customers++;
    }

    $total_credit_limit +=
        (float) ($customer["credit_limit"] ?? 0);

    $customer_type = trim(
        $customer["customer_type"] ?? ""
    );

    if ($customer_type !== "") {
        $customer_types[$customer_type] = true;
    }

    $company_name = trim(
        $customer["company_name"] ?? ""
    );

    if ($company_name !== "") {

        $companies[$company_name] = true;

        $customers_with_company++;
    }

    if (
        !empty($customer["first_name"]) ||
        !empty($customer["last_name"])
    ) {
        $customers_with_contact++;
    }
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function formatCustomerDate($date)
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


function customerStatusClass($status)
{
    $status = strtolower(
        trim($status)
    );

    if ($status === "active") {
        return "status-active";
    }

    if ($status === "inactive") {
        return "status-inactive";
    }

    if ($status === "pending") {
        return "status-pending";
    }

    return "status-default";
}


function customerInitial($code, $company)
{
    $code = trim($code);
    $company = trim($company);

    if ($code !== "") {

        return strtoupper(
            substr(
                $code,
                0,
                1
            )
        );
    }

    if ($company !== "") {

        return strtoupper(
            substr(
                $company,
                0,
                1
            )
        );
    }

    return "C";
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
        Customers Report | CRM
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
           FILTER
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

        .customers-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .customers-table th {
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

        .customers-table td {
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

        .customers-table tbody tr:hover {
            background: #f8fbff;
        }

        .customers-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
        ========================================================= */

        .customers-table th:nth-child(1),
        .customers-table td:nth-child(1) {
            width: 5%;
        }

        .customers-table th:nth-child(2),
        .customers-table td:nth-child(2) {
            width: 13%;
        }

        .customers-table th:nth-child(3),
        .customers-table td:nth-child(3) {
            width: 10%;
        }

        .customers-table th:nth-child(4),
        .customers-table td:nth-child(4) {
            width: 15%;
        }

        .customers-table th:nth-child(5),
        .customers-table td:nth-child(5) {
            width: 12%;
        }

        .customers-table th:nth-child(6),
        .customers-table td:nth-child(6) {
            width: 15%;
        }

        .customers-table th:nth-child(7),
        .customers-table td:nth-child(7) {
            width: 11%;
        }

        .customers-table th:nth-child(8),
        .customers-table td:nth-child(8) {
            width: 8%;
        }

        .customers-table th:nth-child(9),
        .customers-table td:nth-child(9) {
            width: 10%;
        }

        .customers-table th:nth-child(10),
        .customers-table td:nth-child(10) {
            width: 11%;
        }

        /* =========================================================
           CUSTOMER CELL
        ========================================================= */

        .customer-cell {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }

        .customer-avatar {
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

        .customer-info {
            min-width: 0;
        }

        .customer-code {
            display: block;

            margin-bottom: 3px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .customer-id {
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
           STATUS
        ========================================================= */

        .status-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                6px
                9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            text-transform: capitalize;

            white-space: nowrap;
        }

        .status-active {
            background: #dcfce7;

            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;

            color: #991b1b;
        }

        .status-pending {
            background: #fef3c7;

            color: #92400e;
        }

        .status-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================================================
           CUSTOMER TYPE
        ========================================================= */

        .type-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

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
           CREDIT
        ========================================================= */

        .credit-value {
            display: block;

            color: #0f172a;

            font-size: 12px;

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
        html.dark-mode .customer-code,
        html.dark-mode .credit-value,
        html.dark-mode .empty-state h3 {
            color: #f8fafc;
        }

        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .table-header span,
        html.dark-mode .customer-id,
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

        html.dark-mode .customers-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .customers-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .customers-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .customer-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .type-badge {
            background: #1e293b;

            color: #cbd5e1;
        }

        html.dark-mode .credit-value {
            color: #f8fafc;
        }

        html.dark-mode .status-active {
            background: #052e16;

            color: #86efac;
        }

        html.dark-mode .status-inactive {
            background: #450a0a;

            color: #fca5a5;
        }

        html.dark-mode .status-pending {
            background: #451a03;

            color: #fde68a;
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

            .customers-table {
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
                    👥
                </div>

                <div class="report-title">

                    <h1>
                        Customers Report
                    </h1>

                    <p>
                        Review customer records, company associations, credit limits and status.
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
                    Filter customer records by their creation date.
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
                        href="customers.php"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>


                    <a
                        href="../exports/customers_csv.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ CSV
                    </a>


                    <a
                        href="../exports/customers_excel.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Excel
                    </a>


                    <a
                        href="../exports/customers_pdf.php?from_date=<?php
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
                    Total Customers
                </div>

                <div class="summary-value">
                    <?php
                    echo $total_customers;
                    ?>
                </div>

                <div class="summary-description">
                    Customers in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Active Customers
                </div>

                <div class="summary-value">
                    <?php
                    echo $active_customers;
                    ?>
                </div>

                <div class="summary-description">
                    Currently active customers
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Customer Types
                </div>

                <div class="summary-value">
                    <?php
                    echo count($customer_types);
                    ?>
                </div>

                <div class="summary-description">
                    Different customer types
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Credit Limit
                </div>

                <div class="summary-value">
                    ₹<?php
                    echo number_format(
                        $total_credit_limit,
                        2
                    );
                    ?>
                </div>

                <div class="summary-description">
                    Combined credit limit
                </div>

            </div>

        </div>


        <!-- =====================================================
             CUSTOMER TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Customer Details
                    </h2>

                </div>

                <span>

                    <?php
                    echo $total_customers;
                    ?>

                    record<?php
                    echo $total_customers === 1
                        ? ""
                        : "s";
                    ?>

                </span>

            </div>


            <div class="table-wrapper">

                <?php if (!empty($customers)): ?>

                    <table class="customers-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Company
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Credit Limit
                                </th>

                                <th>
                                    Created
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($customers as $customer): ?>

                                <?php

                                $customer_code = trim(
                                    $customer[
                                        "customer_code"
                                    ] ?? ""
                                );

                                $company_name = trim(
                                    $customer[
                                        "company_name"
                                    ] ?? ""
                                );

                                $first_name = trim(
                                    $customer[
                                        "first_name"
                                    ] ?? ""
                                );

                                $last_name = trim(
                                    $customer[
                                        "last_name"
                                    ] ?? ""
                                );

                                $contact_name = trim(
                                    $first_name .
                                    " " .
                                    $last_name
                                );

                                $status = strtolower(
                                    trim(
                                        $customer[
                                            "status"
                                        ] ?? ""
                                    )
                                );

                                $customer_type = trim(
                                    $customer[
                                        "customer_type"
                                    ] ?? ""
                                );

                                ?>

                                <tr>

                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo (int)
                                            $customer["id"];
                                        ?>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <div class="customer-cell">

                                            <div class="customer-avatar">

                                                <?php
                                                echo htmlspecialchars(
                                                    customerInitial(
                                                        $customer_code,
                                                        $company_name
                                                    )
                                                );
                                                ?>

                                            </div>


                                            <div class="customer-info">

                                                <span
                                                    class="customer-code"
                                                    title="<?php
                                                    echo htmlspecialchars(
                                                        $customer_code
                                                    );
                                                    ?>"
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $customer_code !== ""
                                                            ? $customer_code
                                                            : "Customer"
                                                    );

                                                    ?>

                                                </span>


                                                <span class="customer-id">

                                                    Customer #<?php
                                                    echo (int)
                                                        $customer["id"];
                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- TYPE -->

                                    <td>

                                        <?php if ($customer_type !== ""): ?>

                                            <span
                                                class="type-badge"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $customer_type
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $customer_type
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


                                    <!-- EMAIL -->

                                    <td>

                                        <?php if (!empty($customer["contact_email"])): ?>

                                            <a
                                                href="mailto:<?php
                                                echo htmlspecialchars(
                                                    $customer[
                                                        "contact_email"
                                                    ]
                                                );
                                                ?>"
                                                class="cell-text"
                                                style="color:#2563eb;text-decoration:none;"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $customer[
                                                        "contact_email"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $customer[
                                                        "contact_email"
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

                                        <?php if (!empty($customer["contact_phone"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $customer[
                                                        "contact_phone"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $customer[
                                                        "contact_phone"
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


                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($status !== ""): ?>

                                            <span
                                                class="status-badge <?php
                                                echo htmlspecialchars(
                                                    customerStatusClass(
                                                        $status
                                                    )
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $customer["status"]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CREDIT LIMIT -->

                                    <td>

                                        <span class="credit-value">

                                            ₹<?php
                                            echo number_format(
                                                (float) (
                                                    $customer[
                                                        "credit_limit"
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
                                                formatCustomerDate(
                                                    $customer[
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
                            👥
                        </div>

                        <h3>
                            No Customers Found
                        </h3>

                        <p>
                            No customer records match the selected date range.
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
                    echo $total_customers;
                    ?>

                    customer<?php
                    echo $total_customers === 1
                        ? ""
                        : "s";
                    ?>

                </span>

                <span>
                    Customers Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>