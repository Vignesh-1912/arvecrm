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
        DATE(products.created_at) >= :from_date
    ";

    $params[":from_date"] = $from_date;
}

if ($to_date !== "") {

    $where[] = "
        DATE(products.created_at) <= :to_date
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
| Load Products
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        products.id,
        products.name,
        products.sku,
        products.type,
        products.description,
        products.price,
        products.tax_rate,
        products.status,
        products.created_at

    FROM products

    $where_sql

    ORDER BY products.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/
$total_products = count($products);

$active_products = 0;

$inactive_products = 0;

$total_product_value = 0;

$product_types = [];

foreach ($products as $product) {

    if ((int) ($product["status"] ?? 0) === 1) {

        $active_products++;

    } else {

        $inactive_products++;
    }

    $total_product_value +=
        (float) (
            $product["price"] ?? 0
        );

    $type = trim(
        $product["type"] ?? ""
    );

    if ($type !== "") {

        $product_types[$type] = true;
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


function productInitial($name)
{
    $name = trim($name);

    if ($name === "") {
        return "P";
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
        Products Report | CRM
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
            padding: 17px;

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

        .products-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .products-table th {
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

        .products-table td {
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

        .products-table tbody tr:hover {
            background: #f8fbff;
        }

        .products-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
        ========================================================= */

        .products-table th:nth-child(1),
        .products-table td:nth-child(1) {
            width: 5%;
        }

        .products-table th:nth-child(2),
        .products-table td:nth-child(2) {
            width: 17%;
        }

        .products-table th:nth-child(3),
        .products-table td:nth-child(3) {
            width: 11%;
        }

        .products-table th:nth-child(4),
        .products-table td:nth-child(4) {
            width: 10%;
        }

        .products-table th:nth-child(5),
        .products-table td:nth-child(5) {
            width: 20%;
        }

        .products-table th:nth-child(6),
        .products-table td:nth-child(6) {
            width: 10%;
        }

        .products-table th:nth-child(7),
        .products-table td:nth-child(7) {
            width: 9%;
        }

        .products-table th:nth-child(8),
        .products-table td:nth-child(8) {
            width: 9%;
        }

        .products-table th:nth-child(9),
        .products-table td:nth-child(9) {
            width: 9%;
        }

        /* =========================================================
           PRODUCT CELL
        ========================================================= */

        .product-cell {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }

        .product-avatar {
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

        .product-info {
            min-width: 0;
        }

        .product-name {
            display: block;

            margin-bottom: 3px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .product-id {
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
           SKU
        ========================================================= */

        .sku-badge {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                8px;

            max-width: 100%;

            border-radius: 6px;

            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            color: #475569;

            font-size: 10px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        /* =========================================================
           TYPE
        ========================================================= */

        .type-badge {
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

        /* =========================================================
           PRICE
        ========================================================= */

        .price-value {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================================================
           TAX
        ========================================================= */

        .tax-value {
            color: #475569;

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

        .status-active {
            background: #dcfce7;

            color: #15803d;
        }

        .status-inactive {
            background: #fee2e2;

            color: #b91c1c;
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
        html.dark-mode .product-name,
        html.dark-mode .empty-state h3,
        html.dark-mode .price-value {
            color: #f8fafc;
        }

        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .table-header span,
        html.dark-mode .product-id,
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

        html.dark-mode .products-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .products-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .products-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .product-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .sku-badge,
        html.dark-mode .type-badge {
            background: #1e293b;

            border-color: #334155;

            color: #cbd5e1;
        }

        html.dark-mode .tax-value {
            color: #cbd5e1;
        }

        html.dark-mode .status-active {
            background: #052e16;

            color: #86efac;
        }

        html.dark-mode .status-inactive {
            background: #450a0a;

            color: #fca5a5;
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

            .products-table {
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
                    📦
                </div>

                <div class="report-title">

                    <h1>
                        Products Report
                    </h1>

                    <p>
                        Review products, SKUs, pricing, tax rates and product status.
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
                    Filter products by their creation date.
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
                        href="products.php"
                        class="btn btn-secondary"
                    >
                        Clear
                    </a>


                    <a
                        href="../exports/products_csv.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ CSV
                    </a>


                    <a
                        href="../exports/products_excel.php?from_date=<?php
                        echo urlencode($from_date);
                        ?>&to_date=<?php
                        echo urlencode($to_date);
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Excel
                    </a>


                    <a
                        href="../exports/products_pdf.php?from_date=<?php
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
                    Total Products
                </div>

                <div class="summary-value">

                    <?php
                    echo $total_products;
                    ?>

                </div>

                <div class="summary-description">
                    Products in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Active Products
                </div>

                <div class="summary-value">

                    <?php
                    echo $active_products;
                    ?>

                </div>

                <div class="summary-description">
                    Products currently active
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Inactive Products
                </div>

                <div class="summary-value">

                    <?php
                    echo $inactive_products;
                    ?>

                </div>

                <div class="summary-description">
                    Products currently inactive
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Product Types
                </div>

                <div class="summary-value">

                    <?php
                    echo count($product_types);
                    ?>

                </div>

                <div class="summary-description">
                    Different product types
                </div>

            </div>

        </div>


        <!-- =====================================================
             PRODUCT TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Product Details
                    </h2>

                </div>

                <span>

                    <?php
                    echo $total_products;
                    ?>

                    record<?php

                    echo $total_products === 1
                        ? ""
                        : "s";

                    ?>

                </span>

            </div>


            <div class="table-wrapper">

                <?php if (!empty($products)): ?>

                    <table class="products-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    SKU
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Tax Rate
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($products as $product): ?>

                                <?php

                                $product_name =
                                    trim(
                                        $product[
                                            "name"
                                        ] ?? ""
                                    );

                                $sku =
                                    trim(
                                        $product[
                                            "sku"
                                        ] ?? ""
                                    );

                                $type =
                                    trim(
                                        $product[
                                            "type"
                                        ] ?? ""
                                    );

                                $description =
                                    trim(
                                        $product[
                                            "description"
                                        ] ?? ""
                                    );

                                $is_active =
                                    (int) (
                                        $product[
                                            "status"
                                        ] ?? 0
                                    ) === 1;

                                ?>

                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo (int)
                                            $product["id"];
                                        ?>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="product-cell">

                                            <div class="product-avatar">

                                                <?php
                                                echo htmlspecialchars(
                                                    productInitial(
                                                        $product_name
                                                    )
                                                );
                                                ?>

                                            </div>


                                            <div class="product-info">

                                                <span
                                                    class="product-name"
                                                    title="<?php
                                                    echo htmlspecialchars(
                                                        $product_name
                                                    );
                                                    ?>"
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $product_name !== ""
                                                            ? $product_name
                                                            : "Unnamed Product"
                                                    );

                                                    ?>

                                                </span>


                                                <span class="product-id">

                                                    Product #<?php

                                                    echo (int)
                                                        $product["id"];

                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- SKU -->

                                    <td>

                                        <?php if ($sku !== ""): ?>

                                            <span
                                                class="sku-badge"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $sku
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $sku
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- TYPE -->

                                    <td>

                                        <?php if ($type !== ""): ?>

                                            <span
                                                class="type-badge"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $type
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $type
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <?php if ($description !== ""): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <span class="price-value">

                                            ₹<?php

                                            echo number_format(
                                                (float) (
                                                    $product[
                                                        "price"
                                                    ] ?? 0
                                                ),
                                                2
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- TAX -->

                                    <td>

                                        <span class="tax-value">

                                            <?php

                                            echo number_format(
                                                (float) (
                                                    $product[
                                                        "tax_rate"
                                                    ] ?? 0
                                                ),
                                                2
                                            );

                                            ?>%

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($is_active): ?>

                                            <span class="status-badge status-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge status-inactive">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CREATED -->

                                    <td>

                                        <span class="cell-text">

                                            <?php

                                            echo htmlspecialchars(
                                                formatReportDate(
                                                    $product[
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
                            📦
                        </div>

                        <h3>
                            No Products Found
                        </h3>

                        <p>
                            No product records match the selected date range.
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
                    echo $total_products;
                    ?>
                    product<?php

                    echo $total_products === 1
                        ? ""
                        : "s";

                    ?>

                </span>

                <span>
                    Products Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>