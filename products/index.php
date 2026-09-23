<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   PRODUCTS
============================== */

$stmt = $conn->query("
    SELECT
        p.id,
        p.name,
        p.sku,
        p.type,
        p.description,
        p.price,
        p.tax_rate,
        p.status,
        p.created_at,
        u.name AS created_by_name

    FROM products p

    LEFT JOIN users u
        ON u.id = p.created_by

    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_products = count($products);

$active_products = 0;
$inactive_products = 0;
$total_product_value = 0;

foreach ($products as $product) {

    $status = (int) (
        $product["status"] ?? 0
    );

    $price = (float) (
        $product["price"] ?? 0
    );

    $total_product_value += $price;

    if ($status === 1) {
        $active_products++;
    } else {
        $inactive_products++;
    }
}

$average_price =
    $total_products > 0
        ? $total_product_value / $total_products
        : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products - CRM</title>

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
           PAGE HEADER
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

        .add-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 17px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.2s;
        }

        .add-btn:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }

        /* =========================
           SUMMARY
        ========================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 20px;
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
           TOOLBAR
        ========================= */

        .toolbar {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 16px;
        }

        .toolbar-left {
            display: flex;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper span {
            position: absolute;

            left: 13px;

            top: 11px;

            color: #94a3b8;

            pointer-events: none;
        }

        .search-wrapper input {
            width: 300px;

            height: 40px;

            padding: 0 14px 0 37px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            font-size: 13px;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }

        .filter-select {
            height: 40px;

            padding: 0 12px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #475569;

            outline: none;

            font-size: 13px;

            cursor: pointer;
        }

        .export-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 10px 15px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #334155;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;
        }

        .export-btn:hover {
            background: #f8fafc;
        }

        /* =========================
           TABLE CARD
        ========================= */

        .table-card {
            width: 100%;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            overflow: hidden;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .table-wrap {
            width: 100%;

            overflow: hidden;
        }

        /* =========================
           TABLE
        ========================= */

        .products-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .products-table th {
            padding: 15px 12px;

            text-align: left;

            background: #f8fafc;

            border-bottom:
                1px solid #e2e8f0;

            color: #475569;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.04em;

            white-space: nowrap;
        }

        .products-table td {
            padding: 16px 12px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .products-table tbody tr:hover {
            background: #f8fbff;
        }

        .products-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================
           COLUMN SIZING
           TOTAL = 100%
        ========================= */

        .sno-column {
            width: 5%;
        }

        .product-column {
            width: 17%;
        }

        .sku-column {
            width: 11%;
        }

        .type-column {
            width: 9%;
        }

        .price-column {
            width: 9%;
        }

        .tax-column {
            width: 8%;
        }

        .status-column {
            width: 9%;
        }

        .created-column {
            width: 9%;
        }

        .date-column {
            width: 8%;
        }

        .action-column {
            width: 15%;
        }

        /* =========================
           PRODUCT DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;

            white-space: nowrap;
        }

        .product-cell {
            min-width: 0;
        }

        .product-title {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .product-id {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;

            white-space: nowrap;
        }

        .data-text {
            display: block;

            color: #475569;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .secondary {
            color: #64748b;
        }

        /* =========================
           PRICE
        ========================= */

        .price-text {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================
           STATUS
        ========================= */

        .status-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 5px 9px;

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

        /* =========================
           ACTION
        ========================= */

        .products-table th.action-column {
            text-align: center;
        }

        .products-table td.action-column {
            text-align: center;

            white-space: nowrap;

            overflow: visible;

            padding-left: 8px;

            padding-right: 8px;
        }

        .action-links {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            white-space: nowrap;
        }

        .action-links a {
            display: inline-block;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;

            flex-shrink: 0;
        }

        .action-links span {
            display: inline-block;

            color: #cbd5e1;

            font-size: 12px;

            flex-shrink: 0;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .view-link {
            color: #2563eb;
        }

        .edit-link {
            color: #059669;
        }

        .delete-link {
            color: #dc2626;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            text-align: center;

            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 42px;

            margin-bottom: 10px;
        }

        .empty-state h3 {
            margin: 0;

            color: #334155;
        }

        .empty-state p {
            margin: 8px 0 0;

            color: #94a3b8;

            font-size: 13px;
        }

        /* =========================
           FOOTER
        ========================= */

        .table-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 14px 16px;

            border-top:
                1px solid #eef2f7;

            color: #94a3b8;

            font-size: 12px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1400px) {

            .main-content {
                padding: 22px;
            }

            .products-table th {
                padding: 13px 9px;

                font-size: 10px;
            }

            .products-table td {
                padding: 14px 9px;
            }

            .action-links {
                gap: 5px;
            }

            .action-links a,
            .action-links span {
                font-size: 11px;
            }
        }

        @media (max-width: 1200px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .search-wrapper input {
                width: 260px;
            }
        }

        @media (max-width: 1000px) {

            .main-content {
                padding: 18px;
            }

            .products-table th {
                padding: 12px 7px;

                font-size: 9px;
            }

            .products-table td {
                padding: 12px 7px;

                font-size: 12px;
            }

            .action-links {
                gap: 4px;
            }

            .action-links a,
            .action-links span {
                font-size: 10px;
            }
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;

                width: calc(100% - 220px);

                padding: 16px;
            }

            .page-header {
                flex-direction: column;

                align-items: flex-start;
            }

            .add-btn {
                width: 100%;
            }

            .toolbar {
                flex-direction: column;

                align-items: stretch;
            }

            .toolbar-left {
                width: 100%;
            }

            .search-wrapper {
                width: 100%;
            }

            .search-wrapper input {
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .export-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
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
                📦
            </div>

            <div>

                <h1>
                    Products
                </h1>

                <p>
                    Manage products, pricing and inventory information
                </p>

            </div>

        </div>

        <a
            href="add.php"
            class="add-btn"
        >
            + Add Product
        </a>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Products
            </div>

            <div class="summary-value">
                <?= number_format($total_products); ?>
            </div>

            <div class="summary-small">
                All product records
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Active Products
            </div>

            <div class="summary-value">
                <?= number_format($active_products); ?>
            </div>

            <div class="summary-small">
                Currently available
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Inactive Products
            </div>

            <div class="summary-value">
                <?= number_format($inactive_products); ?>
            </div>

            <div class="summary-small">
                Currently inactive
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Average Price
            </div>

            <div class="summary-value">
                ₹<?= number_format($average_price, 0); ?>
            </div>

            <div class="summary-small">
                Average product price
            </div>

        </div>

    </div>

    <!-- =========================
         TOOLBAR
    ========================= -->

    <div class="toolbar">

        <div class="toolbar-left">

            <div class="search-wrapper">

                <span>
                    🔎
                </span>

                <input
                    type="text"
                    id="productSearch"
                    placeholder="Search products..."
                    autocomplete="off"
                >

            </div>

            <select
                id="statusFilter"
                class="filter-select"
            >

                <option value="">
                    All Status
                </option>

                <option value="1">
                    Active
                </option>

                <option value="0">
                    Inactive
                </option>

            </select>

        </div>

        <a
            href="../exports/products_csv.php"
            class="export-btn"
        >
            ↓ Export CSV
        </a>

    </div>

    <!-- =========================
         TABLE
    ========================= -->

    <div class="table-card">

        <div class="table-wrap">

            <table
                class="products-table"
                id="productsTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="product-column">
                            Product
                        </th>

                        <th class="sku-column">
                            SKU
                        </th>

                        <th class="type-column">
                            Type
                        </th>

                        <th class="price-column">
                            Price
                        </th>

                        <th class="tax-column">
                            Tax Rate
                        </th>

                        <th class="status-column">
                            Status
                        </th>

                        <th class="created-column">
                            Created By
                        </th>

                        <th class="date-column">
                            Created
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($products) > 0): ?>

                    <?php foreach (
                        $products
                        as $index => $product
                    ): ?>

                        <?php

                        $status =
                            (int) (
                                $product["status"] ?? 0
                            );

                        ?>

                        <tr
                            data-status="<?= $status; ?>"
                        >

                            <!-- S.NO -->

                            <td class="serial">

                                <?= $index + 1; ?>

                            </td>

                            <!-- PRODUCT -->

                            <td class="product-cell">

                                <div class="product-title">

                                    <?= htmlspecialchars(
                                        $product["name"] ?? "—"
                                    ); ?>

                                </div>

                                <div class="product-id">

                                    Product ID:
                                    <?= (int) $product["id"]; ?>

                                </div>

                            </td>

                            <!-- SKU -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $product["sku"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $product["sku"]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- TYPE -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $product["type"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $product["type"]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- PRICE -->

                            <td>

                                <span class="price-text">

                                    ₹<?= number_format(
                                        (float) (
                                            $product["price"] ?? 0
                                        ),
                                        2
                                    ); ?>

                                </span>

                            </td>

                            <!-- TAX -->

                            <td class="secondary">

                                <?= number_format(
                                    (float) (
                                        $product["tax_rate"] ?? 0
                                    ),
                                    2
                                ); ?>%

                            </td>

                            <!-- STATUS -->

                            <td>

                                <?php if ($status === 1): ?>

                                    <span
                                        class="status-badge status-active"
                                    >
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status-badge status-inactive"
                                    >
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- CREATED BY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $product[
                                            "created_by_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $product[
                                                "created_by_name"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CREATED DATE -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $product["created_at"]
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $product["created_at"]
                                            )
                                        )
                                    ); ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?= (int) $product["id"]; ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?= (int) $product["id"]; ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?= (int) $product["id"]; ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this product?'
                                            );
                                        "
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="10">

                            <div class="empty-state">

                                <div class="empty-icon">
                                    📦
                                </div>

                                <h3>
                                    No products found
                                </h3>

                                <p>
                                    Add your first product
                                    to get started.
                                </p>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- =========================
             FOOTER
        ========================= -->

        <div class="table-footer">

            <span>

                Showing

                <strong>
                    <?= count($products); ?>
                </strong>

                products

            </span>

            <span>
                CRM Product Management
            </span>

        </div>

    </div>

</div>

<script>

/* =========================
   SEARCH + FILTER
========================= */

const productSearch =
    document.getElementById(
        "productSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const productRows =
    document.querySelectorAll(
        "#productsTable tbody tr[data-status]"
    );

function filterProducts() {

    const searchValue =
        productSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value
            .toLowerCase()
            .trim();

    productRows.forEach(function(row) {

        const rowText =
            row.textContent
                .toLowerCase();

        const rowStatus =
            row.dataset.status
                .toLowerCase();

        const matchesSearch =
            rowText.includes(
                searchValue
            );

        const matchesStatus =
            statusValue === ""
            ||
            rowStatus === statusValue;

        row.style.display =
            matchesSearch &&
            matchesStatus
                ? ""
                : "none";

    });
}

productSearch.addEventListener(
    "input",
    filterProducts
);

statusFilter.addEventListener(
    "change",
    filterProducts
);

</script>

</body>

</html>