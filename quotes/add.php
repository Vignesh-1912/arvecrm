<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$quote_number = "QT-" . date("Ymd-His");
$status = "draft";

$company_id = "";
$contact_id = "";
$customer_id = "";
$deal_id = "";

$tax_amount = "0";
$discount_amount = "0";
$valid_until = "";
$notes = "";

/*
|--------------------------------------------------------------------------
| Get Companies
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, company_name
    FROM companies
    ORDER BY company_name ASC
");

$stmt->execute();

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Get Contacts
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, first_name, last_name
    FROM contacts
    ORDER BY first_name ASC, last_name ASC
");

$stmt->execute();

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Get Customers
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, customer_code
    FROM customers
    ORDER BY customer_code ASC
");

$stmt->execute();

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Get Deals
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, title
    FROM deals
    ORDER BY title ASC
");

$stmt->execute();

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Save Quote
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quote_number = trim($_POST["quote_number"] ?? "");
    $company_id = !empty($_POST["company_id"])
        ? (int)$_POST["company_id"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int)$_POST["contact_id"]
        : null;

    $customer_id = !empty($_POST["customer_id"])
        ? (int)$_POST["customer_id"]
        : null;

    $deal_id = !empty($_POST["deal_id"])
        ? (int)$_POST["deal_id"]
        : null;

    $discount_amount = is_numeric($_POST["discount_amount"] ?? "")
        ? (float)$_POST["discount_amount"]
        : 0;

    $tax_amount = is_numeric($_POST["tax_amount"] ?? "")
        ? (float)$_POST["tax_amount"]
        : 0;

    $status = trim($_POST["status"] ?? "draft");

    $valid_until = !empty($_POST["valid_until"])
        ? $_POST["valid_until"]
        : null;

    $notes = trim($_POST["notes"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $allowed_statuses = [
        "draft",
        "sent",
        "accepted",
        "rejected",
        "expired"
    ];

    if ($quote_number === "") {

        $error = "Quote number is required.";

    } elseif (!in_array($status, $allowed_statuses, true)) {

        $error = "Invalid quote status.";

    } elseif ($discount_amount < 0) {

        $error = "Discount cannot be negative.";

    } elseif ($tax_amount < 0) {

        $error = "Tax cannot be negative.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Initial Quote Values
            |--------------------------------------------------------------------------
            |
            | Quote items can be added after the quote is created.
            |
            */

            $subtotal = 0;

            $total_amount =
                $subtotal
                + $tax_amount
                - $discount_amount;

            if ($total_amount < 0) {
                $total_amount = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Insert Quote
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO quotes (
                    quote_number,
                    company_id,
                    contact_id,
                    customer_id,
                    deal_id,
                    subtotal,
                    tax_amount,
                    discount_amount,
                    total_amount,
                    status,
                    valid_until,
                    notes,
                    created_by
                )
                VALUES (
                    :quote_number,
                    :company_id,
                    :contact_id,
                    :customer_id,
                    :deal_id,
                    :subtotal,
                    :tax_amount,
                    :discount_amount,
                    :total_amount,
                    :status,
                    :valid_until,
                    :notes,
                    :created_by
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":quote_number" => $quote_number,
                ":company_id" => $company_id,
                ":contact_id" => $contact_id,
                ":customer_id" => $customer_id,
                ":deal_id" => $deal_id,
                ":subtotal" => $subtotal,
                ":tax_amount" => $tax_amount,
                ":discount_amount" => $discount_amount,
                ":total_amount" => $total_amount,
                ":status" => $status,
                ":valid_until" => $valid_until,
                ":notes" => $notes,
                ":created_by" => $_SESSION["user_id"]
            ]);

            $quote_id = $conn->lastInsertId();

            header("Location: view.php?id=" . $quote_id);
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error = "Quote number already exists. Please use a different quote number.";

            } else {

                $error = "Unable to create quote. Please try again.";

            }
        }
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

    <title>Add Quote - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .main-content {
            padding: 28px 32px;
        }

        /* Breadcrumb */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Page Header */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 27px;
            font-weight: 700;
        }

        .page-title p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Buttons */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #dbe2ea;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        /* Layout */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Main Card */

        .form-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }

        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 17px;
        }

        .card-header p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .form-body {
            padding: 22px;
        }

        /* Sections */

        .form-section {
            margin-bottom: 26px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            padding-bottom: 10px;
            margin-bottom: 17px;
            border-bottom: 1px solid #eef2f7;
        }

        .section-number {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .section-title h3 {
            margin: 0;
            color: #1e293b;
            font-size: 14px;
        }

        /* Form */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .required {
            color: #dc2626;
        }

        .form-control {
            width: 100%;
            height: 42px;
            padding: 10px 12px;
            border: 1px solid #cfd8e3;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            outline: none;
            font-size: 13px;
            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        textarea.form-control {
            height: 125px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-help {
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* Quote Number */

        .quote-number-wrap {
            position: relative;
        }

        .quote-number-wrap .quote-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 13px;
        }

        .quote-number-wrap .form-control {
            padding-left: 34px;
        }

        /* Error */

        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 13px;
        }

        /* Footer */

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            margin-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        /* Side Panel */

        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .side-card {
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow:
                0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .side-card h3 {
            margin: 0 0 15px;
            color: #0f172a;
            font-size: 15px;
        }

        /* Quote Preview */

        .quote-preview {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            overflow: hidden;
        }

        .preview-top {
            padding: 18px;
            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );
            border-bottom: 1px solid #e2e8f0;
        }

        .preview-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            font-size: 19px;
            font-weight: 700;
        }

        .preview-number {
            margin-bottom: 5px;
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            word-break: break-word;
        }

        .preview-status {
            color: #64748b;
            font-size: 12px;
        }

        .preview-body {
            padding: 15px 18px;
        }

        .preview-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 9px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 12px;
        }

        .preview-row:last-child {
            border-bottom: none;
        }

        .preview-row span:first-child {
            color: #64748b;
        }

        .preview-row strong {
            color: #1e293b;
            text-align: right;
            word-break: break-word;
        }

        .preview-total {
            color: #16a34a !important;
            font-size: 17px !important;
        }

        /* Status Badge */

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
        }

        .status-sent {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }

        /* Guide */

        .guide-item {
            display: flex;
            gap: 10px;
            margin-bottom: 13px;
        }

        .guide-item:last-child {
            margin-bottom: 0;
        }

        .guide-icon {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .guide-text {
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Quick Info */

        .info-box {
            padding: 13px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        /* Dark Mode */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .card-header h2,
        html.dark-mode .section-title h3,
        html.dark-mode .side-card h3,
        html.dark-mode .preview-number,
        html.dark-mode .preview-row strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .card-header p,
        html.dark-mode .form-help,
        html.dark-mode .guide-text,
        html.dark-mode .preview-status,
        html.dark-mode .preview-row span:first-child,
        html.dark-mode .info-box {
            color: #94a3b8;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .quote-preview {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .form-footer,
        html.dark-mode .section-title,
        html.dark-mode .preview-row {
            border-color: #1f2937;
        }

        html.dark-mode .form-control {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark-mode .form-control:focus {
            border-color: #60a5fa;
            box-shadow:
                0 0 0 3px rgba(96, 165, 250, 0.10);
        }

        html.dark-mode .preview-top {
            background:
                linear-gradient(
                    135deg,
                    #172554,
                    #111827
                );
            border-color: #1f2937;
        }

        html.dark-mode .preview-body {
            background: #111827;
        }

        html.dark-mode .guide-icon,
        html.dark-mode .section-number {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .info-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .btn-secondary {
            background: #111827;
            color: #cbd5e1;
            border-color: #334155;
        }

        html.dark-mode .btn-secondary:hover {
            background: #1e293b;
        }

        /* Responsive */

        @media (max-width: 1024px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

            .side-panel {
                display: grid;
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 768px) {

            .main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .side-panel {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 480px) {

            .main-content {
                padding: 15px;
            }

            .form-body,
            .card-header,
            .side-card {
                padding: 16px;
            }

            .header-actions {
                flex-direction: column;
            }

            .header-actions .btn {
                width: 100%;
            }

            .form-footer {
                flex-direction: column;
            }

            .form-footer .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- Breadcrumb -->

    <div class="breadcrumb">

        <a href="../dashboard/index.php">
            Dashboard
        </a>

        <span>/</span>

        <a href="index.php">
            Quotes
        </a>

        <span>/</span>

        <span>
            Add Quote
        </span>

    </div>

    <!-- Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Add Quote
            </h1>

            <p>
                Create a new quote and connect it with your company, customer, contact or deal.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Quote List
            </a>

        </div>

    </div>

    <!-- Content -->

    <div class="content-grid">

        <!-- Main Form -->

        <div class="form-card">

            <div class="card-header">

                <h2>
                    Quote Information
                </h2>

                <p>
                    Enter the quote details below. Products and quote items can be added after creating the quote.
                </p>

            </div>

            <div class="form-body">

                <?php if ($error !== ""): ?>

                    <div class="error-box">

                        <span>⚠️</span>

                        <div>
                            <?php echo htmlspecialchars($error); ?>
                        </div>

                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    autocomplete="off"
                >

                    <!-- Quote Details -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                1
                            </div>

                            <h3>
                                Quote Details
                            </h3>

                        </div>

                        <div class="form-grid">

                            <!-- Quote Number -->

                            <div class="form-group">

                                <label for="quote_number">

                                    Quote Number
                                    <span class="required">*</span>

                                </label>

                                <div class="quote-number-wrap">

                                    <span class="quote-icon">
                                        #
                                    </span>

                                    <input
                                        type="text"
                                        id="quote_number"
                                        name="quote_number"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($quote_number); ?>"
                                        placeholder="QT-20260918-001"
                                        required
                                    >

                                </div>

                                <div class="form-help">
                                    Quote number must be unique.
                                </div>

                            </div>

                            <!-- Status -->

                            <div class="form-group">

                                <label for="status">
                                    Status
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                    class="form-control"
                                >

                                    <option
                                        value="draft"
                                        <?php echo ($status === "draft") ? "selected" : ""; ?>
                                    >
                                        Draft
                                    </option>

                                    <option
                                        value="sent"
                                        <?php echo ($status === "sent") ? "selected" : ""; ?>
                                    >
                                        Sent
                                    </option>

                                    <option
                                        value="accepted"
                                        <?php echo ($status === "accepted") ? "selected" : ""; ?>
                                    >
                                        Accepted
                                    </option>

                                    <option
                                        value="rejected"
                                        <?php echo ($status === "rejected") ? "selected" : ""; ?>
                                    >
                                        Rejected
                                    </option>

                                    <option
                                        value="expired"
                                        <?php echo ($status === "expired") ? "selected" : ""; ?>
                                    >
                                        Expired
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                    <!-- Relationships -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                2
                            </div>

                            <h3>
                                Related Records
                            </h3>

                        </div>

                        <div class="form-grid">

                            <!-- Company -->

                            <div class="form-group">

                                <label for="company_id">
                                    Company
                                </label>

                                <select
                                    id="company_id"
                                    name="company_id"
                                    class="form-control"
                                >

                                    <option value="">
                                        -- Select Company --
                                    </option>

                                    <?php foreach ($companies as $company): ?>

                                        <option
                                            value="<?php echo (int)$company["id"]; ?>"
                                            <?php echo ((string)$company_id === (string)$company["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $company["company_name"]
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- Contact -->

                            <div class="form-group">

                                <label for="contact_id">
                                    Contact
                                </label>

                                <select
                                    id="contact_id"
                                    name="contact_id"
                                    class="form-control"
                                >

                                    <option value="">
                                        -- Select Contact --
                                    </option>

                                    <?php foreach ($contacts as $contact): ?>

                                        <?php
                                        $contactName = trim(
                                            $contact["first_name"]
                                            . " "
                                            . ($contact["last_name"] ?? "")
                                        );
                                        ?>

                                        <option
                                            value="<?php echo (int)$contact["id"]; ?>"
                                            <?php echo ((string)$contact_id === (string)$contact["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php echo htmlspecialchars($contactName); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- Customer -->

                            <div class="form-group">

                                <label for="customer_id">
                                    Customer
                                </label>

                                <select
                                    id="customer_id"
                                    name="customer_id"
                                    class="form-control"
                                >

                                    <option value="">
                                        -- Select Customer --
                                    </option>

                                    <?php foreach ($customers as $customer): ?>

                                        <option
                                            value="<?php echo (int)$customer["id"]; ?>"
                                            <?php echo ((string)$customer_id === (string)$customer["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php echo htmlspecialchars($customer["customer_code"]); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- Deal -->

                            <div class="form-group">

                                <label for="deal_id">
                                    Deal
                                </label>

                                <select
                                    id="deal_id"
                                    name="deal_id"
                                    class="form-control"
                                >

                                    <option value="">
                                        -- Select Deal --
                                    </option>

                                    <?php foreach ($deals as $deal): ?>

                                        <option
                                            value="<?php echo (int)$deal["id"]; ?>"
                                            <?php echo ((string)$deal_id === (string)$deal["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php echo htmlspecialchars($deal["title"]); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                    <!-- Financial Information -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                3
                            </div>

                            <h3>
                                Financial Information
                            </h3>

                        </div>

                        <div class="form-grid">

                            <!-- Tax -->

                            <div class="form-group">

                                <label for="tax_amount">
                                    Tax Amount
                                </label>

                                <input
                                    type="number"
                                    id="tax_amount"
                                    name="tax_amount"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($tax_amount); ?>"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                >

                                <div class="form-help">
                                    Tax amount can be updated later when quote items are added.
                                </div>

                            </div>

                            <!-- Discount -->

                            <div class="form-group">

                                <label for="discount_amount">
                                    Discount Amount
                                </label>

                                <input
                                    type="number"
                                    id="discount_amount"
                                    name="discount_amount"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($discount_amount); ?>"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                >

                                <div class="form-help">
                                    Enter zero when no discount is applicable.
                                </div>

                            </div>

                            <!-- Valid Until -->

                            <div class="form-group">

                                <label for="valid_until">
                                    Valid Until
                                </label>

                                <input
                                    type="date"
                                    id="valid_until"
                                    name="valid_until"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($valid_until); ?>"
                                >

                                <div class="form-help">
                                    Set the date until which this quote remains valid.
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Notes -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                4
                            </div>

                            <h3>
                                Notes
                            </h3>

                        </div>

                        <div class="form-group">

                            <label for="notes">
                                Quote Notes
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                placeholder="Enter quote notes, terms or additional information..."
                            ><?php echo htmlspecialchars($notes); ?></textarea>

                        </div>

                    </div>

                    <!-- Footer -->

                    <div class="form-footer">

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            + Create Quote
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- Side Panel -->

        <div class="side-panel">

            <!-- Quote Preview -->

            <div class="side-card">

                <h3>
                    Quote Preview
                </h3>

                <div class="quote-preview">

                    <div class="preview-top">

                        <div class="preview-icon">
                            Q
                        </div>

                        <div
                            class="preview-number"
                            id="previewQuoteNumber"
                        >
                            <?php echo htmlspecialchars($quote_number); ?>
                        </div>

                        <div
                            class="preview-status"
                            id="previewStatusText"
                        >
                            Draft Quote
                        </div>

                    </div>

                    <div class="preview-body">

                        <div class="preview-row">

                            <span>
                                Company
                            </span>

                            <strong id="previewCompany">
                                Not selected
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Contact
                            </span>

                            <strong id="previewContact">
                                Not selected
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Customer
                            </span>

                            <strong id="previewCustomer">
                                Not selected
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Deal
                            </span>

                            <strong id="previewDeal">
                                Not selected
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Tax
                            </span>

                            <strong id="previewTax">
                                0.00
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Discount
                            </span>

                            <strong id="previewDiscount">
                                0.00
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Initial Total
                            </span>

                            <strong
                                class="preview-total"
                                id="previewTotal"
                            >
                                0.00
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Setup Guide -->

            <div class="side-card">

                <h3>
                    Setup Guide
                </h3>

                <div class="guide-item">

                    <div class="guide-icon">
                        1
                    </div>

                    <div class="guide-text">
                        Enter or confirm the automatically generated quote number.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        2
                    </div>

                    <div class="guide-text">
                        Select the company, contact, customer and deal related to this quote.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        3
                    </div>

                    <div class="guide-text">
                        Add tax, discount and validity information.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        4
                    </div>

                    <div class="guide-text">
                        Create the quote and add products or quote items from the quote details page.
                    </div>

                </div>

            </div>

            <!-- Information -->

            <div class="side-card">

                <h3>
                    Quick Tip
                </h3>

                <div class="info-box">
                    The quote is initially created with a subtotal of
                    <strong>0.00</strong>. Products and quote items can be added after the quote is created, and the totals can then be updated.
                </div>

            </div>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const quoteNumberInput =
        document.getElementById("quote_number");

    const statusInput =
        document.getElementById("status");

    const companyInput =
        document.getElementById("company_id");

    const contactInput =
        document.getElementById("contact_id");

    const customerInput =
        document.getElementById("customer_id");

    const dealInput =
        document.getElementById("deal_id");

    const taxInput =
        document.getElementById("tax_amount");

    const discountInput =
        document.getElementById("discount_amount");

    const previewQuoteNumber =
        document.getElementById("previewQuoteNumber");

    const previewStatusText =
        document.getElementById("previewStatusText");

    const previewCompany =
        document.getElementById("previewCompany");

    const previewContact =
        document.getElementById("previewContact");

    const previewCustomer =
        document.getElementById("previewCustomer");

    const previewDeal =
        document.getElementById("previewDeal");

    const previewTax =
        document.getElementById("previewTax");

    const previewDiscount =
        document.getElementById("previewDiscount");

    const previewTotal =
        document.getElementById("previewTotal");

    function formatAmount(value) {

        const number = parseFloat(value);

        if (isNaN(number)) {
            return "0.00";
        }

        return number.toFixed(2);

    }

    function getSelectedText(selectElement) {

        if (
            !selectElement ||
            selectElement.selectedIndex < 0
        ) {
            return "Not selected";
        }

        const option =
            selectElement.options[
                selectElement.selectedIndex
            ];

        if (!option || option.value === "") {
            return "Not selected";
        }

        return option.textContent.trim();

    }

    function updatePreview() {

        const quoteNumber =
            quoteNumberInput.value.trim();

        const status =
            statusInput.value;

        const tax =
            parseFloat(taxInput.value) || 0;

        const discount =
            parseFloat(discountInput.value) || 0;

        let total =
            tax - discount;

        if (total < 0) {
            total = 0;
        }

        previewQuoteNumber.textContent =
            quoteNumber !== ""
                ? quoteNumber
                : "New Quote";

        previewStatusText.textContent =
            status.charAt(0).toUpperCase()
            + status.slice(1)
            + " Quote";

        previewCompany.textContent =
            getSelectedText(companyInput);

        previewContact.textContent =
            getSelectedText(contactInput);

        previewCustomer.textContent =
            getSelectedText(customerInput);

        previewDeal.textContent =
            getSelectedText(dealInput);

        previewTax.textContent =
            formatAmount(tax);

        previewDiscount.textContent =
            formatAmount(discount);

        previewTotal.textContent =
            formatAmount(total);

    }

    quoteNumberInput.addEventListener(
        "input",
        updatePreview
    );

    statusInput.addEventListener(
        "change",
        updatePreview
    );

    companyInput.addEventListener(
        "change",
        updatePreview
    );

    contactInput.addEventListener(
        "change",
        updatePreview
    );

    customerInput.addEventListener(
        "change",
        updatePreview
    );

    dealInput.addEventListener(
        "change",
        updatePreview
    );

    taxInput.addEventListener(
        "input",
        updatePreview
    );

    discountInput.addEventListener(
        "input",
        updatePreview
    );

    updatePreview();

});

</script>

</body>

</html>