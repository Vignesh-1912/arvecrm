<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   SALES
============================== */

$stmt = $conn->query("
    SELECT
        s.id,
        s.sale_number,
        s.customer_id,
        s.company_id,
        s.contact_id,
        s.quote_id,
        s.deal_id,
        s.subtotal,
        s.tax_amount,
        s.discount_amount,
        s.total_amount,
        s.payment_status,
        s.sale_status,
        s.sale_date,
        s.notes,
        s.created_by,
        s.created_at,

        co.company_name,

        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,

        cu.customer_code,

        q.quote_number,

        d.title AS deal_title,

        u.name AS created_by_name

    FROM sales s

    LEFT JOIN companies co
        ON co.id = s.company_id

    LEFT JOIN contacts ct
        ON ct.id = s.contact_id

    LEFT JOIN customers cu
        ON cu.id = s.customer_id

    LEFT JOIN quotes q
        ON q.id = s.quote_id

    LEFT JOIN deals d
        ON d.id = s.deal_id

    LEFT JOIN users u
        ON u.id = s.created_by

    ORDER BY s.id DESC
");

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_sales = count($sales);

$completed_sales = 0;
$pending_sales = 0;
$cancelled_sales = 0;

$total_sales_value = 0;

foreach ($sales as $sale) {

    $sale_status = strtolower(
        trim(
            $sale["sale_status"] ?? ""
        )
    );

    $payment_status = strtolower(
        trim(
            $sale["payment_status"] ?? ""
        )
    );

    $amount = (float) (
        $sale["total_amount"] ?? 0
    );

    $total_sales_value += $amount;

    if (
        $sale_status === "completed" ||
        $sale_status === "complete" ||
        $sale_status === "delivered" ||
        $sale_status === "closed"
    ) {
        $completed_sales++;
    }

    if (
        $payment_status === "pending" ||
        $payment_status === "unpaid" ||
        $payment_status === "partial"
    ) {
        $pending_sales++;
    }

    if (
        $sale_status === "cancelled" ||
        $sale_status === "canceled"
    ) {
        $cancelled_sales++;
    }
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

    <title>Sales - CRM</title>

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

        .sales-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .sales-table th {
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

        .sales-table td {
            padding: 16px 12px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .sales-table tbody tr:hover {
            background: #f8fbff;
        }

        .sales-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================
           COLUMN SIZING
           TOTAL = 100%
        ========================= */

        .sno-column {
            width: 5%;
        }

        .sale-column {
            width: 13%;
        }

        .customer-column {
            width: 10%;
        }

        .company-column {
            width: 11%;
        }

        .contact-column {
            width: 11%;
        }

        .total-column {
            width: 10%;
        }

        .payment-column {
            width: 10%;
        }

        .status-column {
            width: 9%;
        }

        .date-column {
            width: 8%;
        }

        .created-column {
            width: 7%;
        }

        .action-column {
            width: 16%;
        }

        /* =========================
           DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;

            white-space: nowrap;
        }

        .sale-cell {
            min-width: 0;
        }

        .sale-number {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .sale-id {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;

            white-space: nowrap;
        }

        .secondary {
            color: #64748b;
        }

        .data-text {
            display: block;

            color: #475569;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        /* =========================
           TOTAL
        ========================= */

        .amount-text {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================
           PAYMENT STATUS
        ========================= */

        .payment-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            max-width: 100%;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .payment-paid {
            background: #dcfce7;

            color: #15803d;
        }

        .payment-pending {
            background: #fef3c7;

            color: #b45309;
        }

        .payment-partial {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .payment-unpaid {
            background: #fee2e2;

            color: #b91c1c;
        }

        .payment-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================
           SALE STATUS
        ========================= */

        .sale-status-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            max-width: 100%;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .sale-completed {
            background: #dcfce7;

            color: #15803d;
        }

        .sale-pending {
            background: #fef3c7;

            color: #b45309;
        }

        .sale-processing {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .sale-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        .sale-draft {
            background: #f1f5f9;

            color: #475569;
        }

        .sale-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================
           ACTION
        ========================= */

        .sales-table th.action-column {
            text-align: center;
        }

        .sales-table td.action-column {
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

            .sales-table th {
                padding: 13px 9px;

                font-size: 10px;
            }

            .sales-table td {
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

            .sales-table th {
                padding: 12px 7px;

                font-size: 9px;
            }

            .sales-table td {
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
                💰
            </div>

            <div>

                <h1>
                    Sales
                </h1>

                <p>
                    Manage sales transactions, payments and revenue
                </p>

            </div>

        </div>

        <a
            href="add.php"
            class="add-btn"
        >
            + Add Sale
        </a>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Sales
            </div>

            <div class="summary-value">
                <?= number_format($total_sales); ?>
            </div>

            <div class="summary-small">
                All sales records
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Completed Sales
            </div>

            <div class="summary-value">
                <?= number_format($completed_sales); ?>
            </div>

            <div class="summary-small">
                Successfully completed
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Pending Payments
            </div>

            <div class="summary-value">
                <?= number_format($pending_sales); ?>
            </div>

            <div class="summary-small">
                Payments requiring attention
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Sales Value
            </div>

            <div class="summary-value">
                ₹<?= number_format($total_sales_value, 0); ?>
            </div>

            <div class="summary-small">
                Total sales revenue
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
                    id="saleSearch"
                    placeholder="Search sales..."
                    autocomplete="off"
                >

            </div>

            <select
                id="statusFilter"
                class="filter-select"
            >

                <option value="">
                    All Sale Status
                </option>

                <option value="draft">
                    Draft
                </option>

                <option value="pending">
                    Pending
                </option>

                <option value="processing">
                    Processing
                </option>

                <option value="completed">
                    Completed
                </option>

                <option value="cancelled">
                    Cancelled
                </option>

            </select>

        </div>

        <a
            href="../exports/sales_csv.php"
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
                class="sales-table"
                id="salesTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="sale-column">
                            Sale
                        </th>

                        <th class="customer-column">
                            Customer
                        </th>

                        <th class="company-column">
                            Company
                        </th>

                        <th class="contact-column">
                            Contact
                        </th>

                        <th class="total-column">
                            Total
                        </th>

                        <th class="payment-column">
                            Payment
                        </th>

                        <th class="status-column">
                            Sale Status
                        </th>

                        <th class="date-column">
                            Sale Date
                        </th>

                        <th class="created-column">
                            Created By
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($sales) > 0): ?>

                    <?php foreach (
                        $sales
                        as $index => $sale
                    ): ?>

                        <?php

                        $sale_status =
                            strtolower(
                                trim(
                                    $sale[
                                        "sale_status"
                                    ] ?? ""
                                )
                            );

                        $payment_status =
                            strtolower(
                                trim(
                                    $sale[
                                        "payment_status"
                                    ] ?? ""
                                )
                            );

                        /* Sale Status Class */

                        switch ($sale_status) {

                            case "completed":
                            case "complete":
                            case "delivered":
                            case "closed":

                                $sale_status_class =
                                    "sale-completed";

                                break;

                            case "pending":

                                $sale_status_class =
                                    "sale-pending";

                                break;

                            case "processing":
                            case "shipped":

                                $sale_status_class =
                                    "sale-processing";

                                break;

                            case "cancelled":
                            case "canceled":

                                $sale_status_class =
                                    "sale-cancelled";

                                break;

                            case "draft":

                                $sale_status_class =
                                    "sale-draft";

                                break;

                            default:

                                $sale_status_class =
                                    "sale-default";

                                break;
                        }

                        /* Payment Status Class */

                        switch ($payment_status) {

                            case "paid":
                            case "completed":

                                $payment_class =
                                    "payment-paid";

                                break;

                            case "pending":
                            case "unpaid":

                                $payment_class =
                                    "payment-pending";

                                break;

                            case "partial":
                            case "partially_paid":
                            case "partially paid":

                                $payment_class =
                                    "payment-partial";

                                break;

                            case "failed":
                            case "declined":

                                $payment_class =
                                    "payment-unpaid";

                                break;

                            default:

                                $payment_class =
                                    "payment-default";

                                break;
                        }

                        ?>

                        <tr
                            data-status="<?= htmlspecialchars(
                                $sale_status
                            ); ?>"
                        >

                            <!-- S.NO -->

                            <td class="serial">

                                <?= $index + 1; ?>

                            </td>

                            <!-- SALE -->

                            <td class="sale-cell">

                                <div class="sale-number">

                                    <?= htmlspecialchars(
                                        $sale[
                                            "sale_number"
                                        ] ?? "—"
                                    ); ?>

                                </div>

                                <div class="sale-id">

                                    Sale ID:
                                    <?= (int) $sale["id"]; ?>

                                </div>

                            </td>

                            <!-- CUSTOMER -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $sale[
                                            "customer_code"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $sale[
                                                "customer_code"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- COMPANY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $sale[
                                            "company_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $sale[
                                                "company_name"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CONTACT -->

                            <td class="secondary">

                                <?php

                                $contact_name =
                                    trim(
                                        $sale[
                                            "contact_name"
                                        ] ?? ""
                                    );

                                ?>

                                <?php if (
                                    $contact_name !== ""
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $contact_name
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- TOTAL -->

                            <td>

                                <span class="amount-text">

                                    ₹<?= number_format(
                                        (float) (
                                            $sale[
                                                "total_amount"
                                            ] ?? 0
                                        ),
                                        2
                                    ); ?>

                                </span>

                            </td>

                            <!-- PAYMENT -->

                            <td>

                                <span
                                    class="
                                        payment-badge
                                        <?= $payment_class; ?>
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $sale[
                                            "payment_status"
                                        ] ?? "Unknown"
                                    ); ?>

                                </span>

                            </td>

                            <!-- SALE STATUS -->

                            <td>

                                <span
                                    class="
                                        sale-status-badge
                                        <?= $sale_status_class; ?>
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $sale[
                                            "sale_status"
                                        ] ?? "Unknown"
                                    ); ?>

                                </span>

                            </td>

                            <!-- SALE DATE -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $sale[
                                            "sale_date"
                                        ]
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $sale[
                                                    "sale_date"
                                                ]
                                            )
                                        )
                                    ); ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CREATED BY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $sale[
                                            "created_by_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $sale[
                                                "created_by_name"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?= (int) $sale["id"]; ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?= (int) $sale["id"]; ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?= (int) $sale["id"]; ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this sale?'
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

                        <td colspan="11">

                            <div class="empty-state">

                                <div class="empty-icon">
                                    💰
                                </div>

                                <h3>
                                    No sales found
                                </h3>

                                <p>
                                    Add your first sale
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
                    <?= count($sales); ?>
                </strong>

                sales

            </span>

            <span>
                CRM Sales Management
            </span>

        </div>

    </div>

</div>

<script>

/* =========================
   SEARCH + FILTER
========================= */

const saleSearch =
    document.getElementById(
        "saleSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const saleRows =
    document.querySelectorAll(
        "#salesTable tbody tr[data-status]"
    );

function filterSales() {

    const searchValue =
        saleSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value
            .toLowerCase()
            .trim();

    saleRows.forEach(function(row) {

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

saleSearch.addEventListener(
    "input",
    filterSales
);

statusFilter.addEventListener(
    "change",
    filterSales
);

</script>

</body>

</html>