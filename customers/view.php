<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Validate Customer ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Get Customer
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM customers
    WHERE id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {

    header("Location: index.php");
    exit;

}

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function formatDateValue($date): string
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return e($date);
    }

    return date("d M Y, h:i A", $timestamp);
}

function formatDateOnly($date): string
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return e($date);
    }

    return date("d M Y", $timestamp);
}

/*
|--------------------------------------------------------------------------
| Customer Data
|--------------------------------------------------------------------------
*/

$customerCode = $customer["customer_code"] ?? "-";

$customerType = $customer["customer_type"] ?? "-";

$status = strtolower(
    trim((string) ($customer["status"] ?? ""))
);

$creditLimit = (float) (
    $customer["credit_limit"] ?? 0
);

$notes = trim(
    (string) ($customer["notes"] ?? "")
);

$customerId = (int) (
    $customer["id"] ?? 0
);

$createdAt = $customer["created_at"] ?? "";

$updatedAt = $customer["updated_at"] ?? "";

$createdBy = $customer["created_by"] ?? "-";

/*
|--------------------------------------------------------------------------
| Status UI
|--------------------------------------------------------------------------
*/

if ($status === "active") {

    $statusLabel = "Active";
    $statusClass = "status-active";

} elseif ($status === "inactive") {

    $statusLabel = "Inactive";
    $statusClass = "status-inactive";

} else {

    $statusLabel = ucfirst(
        str_replace("_", " ", $status ?: "Unknown")
    );

    $statusClass = "status-default";

}

/*
|--------------------------------------------------------------------------
| Customer Type UI
|--------------------------------------------------------------------------
*/

if ($customerType === "business") {

    $typeLabel = "Business";
    $typeIcon = "🏢";

} elseif ($customerType === "individual") {

    $typeLabel = "Individual";
    $typeIcon = "👤";

} else {

    $typeLabel = ucwords(
        str_replace(
            "_",
            " ",
            $customerType ?: "Unknown"
        )
    );

    $typeIcon = "👥";
}

/*
|--------------------------------------------------------------------------
| Customer Initial
|--------------------------------------------------------------------------
*/

$customerInitial = strtoupper(
    substr(
        $customerCode,
        0,
        1
    )
);

if ($customerInitial === "") {
    $customerInitial = "C";
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
        View Customer - CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        .customer-page {
            max-width: 1180px;
            margin: 0 auto;
        }


        /* =========================================================
           BREADCRUMB
        ========================================================= */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            font-size: 12px;
            color: #94a3b8;
        }

        .breadcrumb a {
            color: #64748b;
            transition: 0.2s ease;
        }

        .breadcrumb a:hover {
            color: #2563eb;
        }

        .breadcrumb-current {
            color: #334155;
            font-weight: 600;
        }

        .breadcrumb-arrow {
            color: #cbd5e1;
        }


        /* =========================================================
           HERO CARD
        ========================================================= */

        .customer-hero {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .customer-avatar {
            width: 64px;
            height: 64px;
            flex: 0 0 64px;
            border-radius: 15px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #60a5fa
            );
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: 700;
            box-shadow:
                0 7px 18px
                rgba(37, 99, 235, 0.20);
        }

        .hero-content {
            min-width: 0;
        }

        .hero-content h1 {
            margin: 0;
            color: #172033;
            font-size: 25px;
            font-weight: 700;
            word-break: break-word;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .hero-id {
            color: #64748b;
            font-size: 12px;
        }

        .hero-divider {
            color: #cbd5e1;
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 25px;
            padding: 0 9px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 25px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-active .status-dot {
            background: #22c55e;
        }

        .status-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-inactive .status-dot {
            background: #94a3b8;
        }

        .status-default {
            background: #fef3c7;
            color: #92400e;
        }

        .status-default .status-dot {
            background: #f59e0b;
        }


        /* =========================================================
           HERO ACTIONS
        ========================================================= */

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-shrink: 0;
        }

        .action-button {
            height: 41px;
            padding: 0 14px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .edit-button {
            background: #2563eb;
            color: #ffffff;
            box-shadow:
                0 4px 10px
                rgba(37, 99, 235, 0.18);
        }

        .edit-button:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .back-button {
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #475569;
        }

        .back-button:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }


        /* =========================================================
           SUMMARY CARDS
        ========================================================= */

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 13px;
            padding: 17px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex: 0 0 40px;
        }

        .summary-label {
            color: #64748b;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .summary-value {
            color: #172033;
            font-size: 15px;
            font-weight: 700;
            word-break: break-word;
        }


        /* =========================================================
           MAIN GRID
        ========================================================= */

        .main-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.55fr)
                minmax(300px, 0.85fr);
            gap: 18px;
        }


        /* =========================================================
           CONTENT CARDS
        ========================================================= */

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header {
            padding: 17px 20px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .card-header-left h2 {
            margin: 0;
            color: #172033;
            font-size: 15px;
        }

        .card-header-left p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 10px;
        }

        .card-body {
            padding: 20px;
        }


        /* =========================================================
           INFORMATION GRID
        ========================================================= */

        .information-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 13px;
        }

        .info-box {
            border: 1px solid #e8edf3;
            border-radius: 11px;
            padding: 14px;
            background: #fbfcfe;
            transition: 0.2s ease;
        }

        .info-box:hover {
            border-color: #bfdbfe;
            background: #f8fbff;
        }

        .info-box.full {
            grid-column: 1 / -1;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 10px;
            margin-bottom: 7px;
        }

        .info-label-icon {
            color: #94a3b8;
            font-size: 11px;
        }

        .info-value {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-value-muted {
            color: #94a3b8;
            font-weight: 400;
        }

        .credit-value {
            color: #2563eb;
            font-size: 17px;
        }


        /* =========================================================
           NOTES
        ========================================================= */

        .notes-box {
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 11px;
            padding: 15px;
            min-height: 110px;
        }

        .notes-text {
            margin: 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.7;
            white-space: normal;
            word-break: break-word;
        }

        .no-notes {
            color: #94a3b8;
            font-style: italic;
        }


        /* =========================================================
           SIDE TIMELINE / DETAILS
        ========================================================= */

        .timeline {
            position: relative;
            padding-left: 28px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            top: 7px;
            bottom: 7px;
            left: 8px;
            width: 1px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 24px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -24px;
            top: 2px;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: #dbeafe;
            border: 4px solid #ffffff;
            box-shadow:
                0 0 0 1px #bfdbfe;
        }

        .timeline-title {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
        }

        .timeline-date {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }


        /* =========================================================
           ACCOUNT CARD
        ========================================================= */

        .account-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .account-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .account-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .account-row:first-child {
            padding-top: 0;
        }

        .account-label {
            color: #64748b;
            font-size: 10px;
        }

        .account-value {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            text-align: right;
        }


        /* =========================================================
           QUICK INFO
        ========================================================= */

        .quick-info {
            margin-top: 18px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 15px;
        }

        .quick-info-title {
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .quick-info-text {
            color: #475569;
            font-size: 10px;
            line-height: 1.6;
        }


        /* =========================================================
           DARK MODE
        ========================================================= */

        html.dark-mode body {
            background: #0b1120;
            color: #e5e7eb;
        }

        html.dark-mode .customer-hero,
        html.dark-mode .summary-card,
        html.dark-mode .card {
            background: #111827;
            border-color: #1f2937;
        }

        html.dark-mode .hero-content h1,
        html.dark-mode .summary-value,
        html.dark-mode .card-header-left h2,
        html.dark-mode .info-value,
        html.dark-mode .timeline-title,
        html.dark-mode .account-value {
            color: #f8fafc;
        }

        html.dark-mode .hero-id,
        html.dark-mode .info-label,
        html.dark-mode .summary-label,
        html.dark-mode .card-header-left p,
        html.dark-mode .timeline-date,
        html.dark-mode .account-label {
            color: #94a3b8;
        }

        html.dark-mode .breadcrumb,
        html.dark-mode .breadcrumb a {
            color: #64748b;
        }

        html.dark-mode .breadcrumb-current {
            color: #cbd5e1;
        }

        html.dark-mode .back-button {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .back-button:hover {
            background: #1e293b;
            color: #93c5fd;
            border-color: #3b82f6;
        }

        html.dark-mode .type-badge {
            background: #1e293b;
            color: #cbd5e1;
        }

        html.dark-mode .info-box,
        html.dark-mode .notes-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .info-box:hover {
            background: #111d31;
            border-color: #475569;
        }

        html.dark-mode .notes-text {
            color: #cbd5e1;
        }

        html.dark-mode .card-header {
            border-color: #1f2937;
        }

        html.dark-mode .account-row {
            border-color: #1f2937;
        }

        html.dark-mode .timeline::before {
            background: #334155;
        }

        html.dark-mode .timeline-dot {
            background: #172554;
            border-color: #111827;
        }

        html.dark-mode .quick-info {
            background: #172554;
            border-color: #1e40af;
        }

        html.dark-mode .quick-info-title {
            color: #93c5fd;
        }

        html.dark-mode .quick-info-text {
            color: #cbd5e1;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .main-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 780px) {

            .customer-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-actions {
                width: 100%;
            }

            .action-button {
                flex: 1;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .information-grid {
                grid-template-columns: 1fr;
            }

            .info-box.full {
                grid-column: auto;
            }

        }

        @media (max-width: 600px) {

            .main-content {
                padding: 18px !important;
            }

            .hero-left {
                align-items: flex-start;
            }

            .customer-avatar {
                width: 54px;
                height: 54px;
                flex-basis: 54px;
                font-size: 21px;
            }

            .hero-content h1 {
                font-size: 20px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .action-button {
                width: 100%;
            }

            .card-body {
                padding: 15px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="customer-page">


        <!-- =====================================================
             BREADCRUMB
        ====================================================== -->

        <div class="breadcrumb">

            <a href="../dashboard/index.php">
                Dashboard
            </a>

            <span class="breadcrumb-arrow">
                /
            </span>

            <a href="index.php">
                Customers
            </a>

            <span class="breadcrumb-arrow">
                /
            </span>

            <span class="breadcrumb-current">
                View Customer
            </span>

        </div>


        <!-- =====================================================
             CUSTOMER HERO
        ====================================================== -->

        <div class="customer-hero">

            <div class="hero-left">

                <div class="customer-avatar">

                    <?= e($customerInitial) ?>

                </div>

                <div class="hero-content">

                    <h1>
                        <?= e($customerCode) ?>
                    </h1>

                    <div class="hero-meta">

                        <span class="hero-id">
                            Customer ID:
                            #<?= e($customerId) ?>
                        </span>

                        <span class="hero-divider">
                            •
                        </span>

                        <span class="type-badge">
                            <?= e($typeIcon) ?>
                            <?= e($typeLabel) ?>
                        </span>

                        <span class="status-badge <?= e($statusClass) ?>">

                            <span class="status-dot"></span>

                            <?= e($statusLabel) ?>

                        </span>

                    </div>

                </div>

            </div>


            <div class="hero-actions">

                <a
                    href="index.php"
                    class="action-button back-button"
                >
                    ← Customer List
                </a>

                <a
                    href="edit.php?id=<?= e($customerId) ?>"
                    class="action-button edit-button"
                >
                    ✎ Edit Customer
                </a>

            </div>

        </div>


        <!-- =====================================================
             SUMMARY CARDS
        ====================================================== -->

        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-icon">
                    #
                </div>

                <div>

                    <div class="summary-label">
                        Customer Code
                    </div>

                    <div class="summary-value">
                        <?= e($customerCode) ?>
                    </div>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-icon">
                    <?= e($typeIcon) ?>
                </div>

                <div>

                    <div class="summary-label">
                        Customer Type
                    </div>

                    <div class="summary-value">
                        <?= e($typeLabel) ?>
                    </div>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-icon">
                    ₹
                </div>

                <div>

                    <div class="summary-label">
                        Credit Limit
                    </div>

                    <div class="summary-value">
                        ₹<?= number_format(
                            $creditLimit,
                            2
                        ) ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <div class="main-grid">


            <!-- =================================================
                 CUSTOMER INFORMATION
            ================================================== -->

            <div class="card">

                <div class="card-header">

                    <div class="card-header-left">

                        <h2>
                            Customer Information
                        </h2>

                        <p>
                            Complete details for this customer record
                        </p>

                    </div>

                </div>


                <div class="card-body">

                    <div class="information-grid">


                        <!-- Customer Code -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    #
                                </span>

                                Customer Code

                            </div>

                            <div class="info-value">
                                <?= e($customerCode) ?>
                            </div>

                        </div>


                        <!-- Customer Type -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    👤
                                </span>

                                Customer Type

                            </div>

                            <div class="info-value">
                                <?= e($typeLabel) ?>
                            </div>

                        </div>


                        <!-- Status -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ●
                                </span>

                                Account Status

                            </div>

                            <div class="info-value">

                                <span class="status-badge <?= e($statusClass) ?>">

                                    <span class="status-dot"></span>

                                    <?= e($statusLabel) ?>

                                </span>

                            </div>

                        </div>


                        <!-- Credit Limit -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ₹
                                </span>

                                Credit Limit

                            </div>

                            <div class="info-value credit-value">
                                ₹<?= number_format(
                                    $creditLimit,
                                    2
                                ) ?>
                            </div>

                        </div>


                        <!-- Customer ID -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ID
                                </span>

                                Customer ID

                            </div>

                            <div class="info-value">
                                #<?= e($customerId) ?>
                            </div>

                        </div>


                        <!-- Created By -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    👤
                                </span>

                                Created By

                            </div>

                            <div class="info-value">

                                <?php if (
                                    $createdBy !== "-"
                                    && $createdBy !== ""
                                ): ?>

                                    User #<?= e($createdBy) ?>

                                <?php else: ?>

                                    <span
                                        class="info-value-muted"
                                    >
                                        Not available
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Created Date -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    📅
                                </span>

                                Created Date

                            </div>

                            <div class="info-value">
                                <?= e(
                                    formatDateValue(
                                        $createdAt
                                    )
                                ) ?>
                            </div>

                        </div>


                        <!-- Updated Date -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ↻
                                </span>

                                Last Updated

                            </div>

                            <div class="info-value">
                                <?= e(
                                    formatDateValue(
                                        $updatedAt
                                    )
                                ) ?>
                            </div>

                        </div>


                        <!-- Notes -->

                        <div class="info-box full">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    📝
                                </span>

                                Customer Notes

                            </div>

                            <div class="notes-box">

                                <?php if ($notes !== ""): ?>

                                    <p class="notes-text">

                                        <?= nl2br(
                                            e($notes)
                                        ) ?>

                                    </p>

                                <?php else: ?>

                                    <p class="notes-text no-notes">
                                        No notes available for this customer.
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 RIGHT SIDEBAR
            ================================================== -->

            <div>


                <!-- Account Summary -->

                <div class="card">

                    <div class="card-header">

                        <div class="card-header-left">

                            <h2>
                                Account Summary
                            </h2>

                            <p>
                                Customer record overview
                            </p>

                        </div>

                    </div>

                    <div class="card-body">

                        <div class="account-list">


                            <div class="account-row">

                                <span class="account-label">
                                    Status
                                </span>

                                <span class="account-value">

                                    <span
                                        class="status-badge <?= e($statusClass) ?>"
                                    >

                                        <span class="status-dot"></span>

                                        <?= e($statusLabel) ?>

                                    </span>

                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    Type
                                </span>

                                <span class="account-value">
                                    <?= e($typeLabel) ?>
                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    Credit Limit
                                </span>

                                <span class="account-value">
                                    ₹<?= number_format(
                                        $creditLimit,
                                        2
                                    ) ?>
                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    Customer ID
                                </span>

                                <span class="account-value">
                                    #<?= e($customerId) ?>
                                </span>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- Activity Timeline -->

                <div
                    class="card"
                    style="margin-top:18px;"
                >

                    <div class="card-header">

                        <div class="card-header-left">

                            <h2>
                                Record Timeline
                            </h2>

                            <p>
                                Customer record activity
                            </p>

                        </div>

                    </div>

                    <div class="card-body">

                        <div class="timeline">


                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Customer record created
                                </div>

                                <div class="timeline-date">
                                    <?= e(
                                        formatDateValue(
                                            $createdAt
                                        )
                                    ) ?>
                                </div>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Customer record last updated
                                </div>

                                <div class="timeline-date">
                                    <?= e(
                                        formatDateValue(
                                            $updatedAt
                                        )
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Quick Information -->

                <div class="quick-info">

                    <div class="quick-info-title">
                        💡 Customer Management
                    </div>

                    <div class="quick-info-text">

                        Use the Edit Customer button to update
                        account status, customer type, credit
                        limit, or internal notes.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>