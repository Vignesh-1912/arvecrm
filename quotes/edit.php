<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

$error = "";

/*
|--------------------------------------------------------------------------
| Get Quote
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT *
    FROM quotes
    WHERE id = :id
");

$stmt->execute([
    ":id" => $id
]);

$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    header("Location: index.php");
    exit;
}

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
| Quote Statuses
|--------------------------------------------------------------------------
*/

$statuses = [
    "draft",
    "sent",
    "accepted",
    "rejected",
    "expired"
];

/*
|--------------------------------------------------------------------------
| Update Quote
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

    if ($quote_number === "") {

        $error = "Quote number is required.";

    } elseif (!in_array($status, $statuses, true)) {

        $error = "Invalid quote status.";

    } else {

        try {

            $sql = "
                UPDATE quotes
                SET
                    quote_number = :quote_number,
                    company_id = :company_id,
                    contact_id = :contact_id,
                    customer_id = :customer_id,
                    deal_id = :deal_id,
                    status = :status,
                    valid_until = :valid_until,
                    notes = :notes
                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":quote_number" => $quote_number,
                ":company_id" => $company_id,
                ":contact_id" => $contact_id,
                ":customer_id" => $customer_id,
                ":deal_id" => $deal_id,
                ":status" => $status,
                ":valid_until" => $valid_until,
                ":notes" => $notes,
                ":id" => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error = "Quote number already exists. Please use a different quote number.";

            } else {

                $error = "Unable to update quote. Please try again.";

            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Keep Entered Values
    |--------------------------------------------------------------------------
    */

    $quote["quote_number"] = $quote_number;
    $quote["company_id"] = $company_id;
    $quote["contact_id"] = $contact_id;
    $quote["customer_id"] = $customer_id;
    $quote["deal_id"] = $deal_id;
    $quote["status"] = $status;
    $quote["valid_until"] = $valid_until;
    $quote["notes"] = $notes;
}

/*
|--------------------------------------------------------------------------
| Display Values
|--------------------------------------------------------------------------
*/

$currentStatus = strtolower($quote["status"] ?? "draft");

if (!in_array($currentStatus, $statuses, true)) {
    $currentStatus = "draft";
}

$statusClass = "status-" . $currentStatus;

$quoteNumber = $quote["quote_number"] ?? "Quote";

$initial = strtoupper(
    substr(
        trim($quoteNumber) !== "" ? $quoteNumber : "Q",
        0,
        1
    )
);

$subtotal = (float)($quote["subtotal"] ?? 0);
$taxAmount = (float)($quote["tax_amount"] ?? 0);
$discountAmount = (float)($quote["discount_amount"] ?? 0);
$totalAmount = (float)($quote["total_amount"] ?? 0);

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
        Edit Quote - CRM
    </title>

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

        /* Header */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
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

        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #15803d;
        }

        /* Record Bar */

        .record-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 18px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .record-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .record-avatar {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
        }

        .record-text {
            min-width: 0;
        }

        .record-text strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .record-text span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
        }

        /* Status */

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
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

        /* Summary */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .summary-card {
            padding: 16px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .summary-label {
            margin-bottom: 7px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-value {
            color: #0f172a;
            font-size: 17px;
            font-weight: 700;
            word-break: break-word;
        }

        .summary-value.green {
            color: #16a34a;
        }

        /* Main Layout */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Form Card */

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
            min-width: 0;
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

        .quote-icon {
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
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
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

        /* Existing Financial Summary */

        .financial-box {
            padding: 15px;
            border-radius: 9px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
            border-bottom: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 12px;
        }

        .financial-row:last-child {
            border-bottom: none;
        }

        .financial-row strong {
            color: #1e293b;
            white-space: nowrap;
        }

        /* Info */

        .info-box {
            padding: 13px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
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
        html.dark-mode .record-text strong,
        html.dark-mode .summary-value,
        html.dark-mode .card-header h2,
        html.dark-mode .section-title h3,
        html.dark-mode .side-card h3,
        html.dark-mode .preview-number,
        html.dark-mode .preview-row strong,
        html.dark-mode .financial-row strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .record-text span,
        html.dark-mode .summary-label,
        html.dark-mode .card-header p,
        html.dark-mode .form-help,
        html.dark-mode .guide-text,
        html.dark-mode .preview-status,
        html.dark-mode .preview-row span:first-child,
        html.dark-mode .financial-row,
        html.dark-mode .info-box {
            color: #94a3b8;
        }

        html.dark-mode .record-bar,
        html.dark-mode .summary-card,
        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .quote-preview {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .section-title,
        html.dark-mode .form-footer,
        html.dark-mode .preview-row,
        html.dark-mode .financial-row {
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

        html.dark-mode .financial-box,
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

        @media (max-width: 1050px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

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

            .record-bar {
                align-items: flex-start;
                flex-direction: column;
            }

            .summary-grid {
                grid-template-columns: 1fr;
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

            .header-actions {
                flex-direction: column;
            }

            .header-actions .btn {
                width: 100%;
            }

            .form-body,
            .card-header,
            .side-card {
                padding: 16px;
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
            Edit Quote
        </span>

    </div>

    <!-- Page Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Edit Quote
            </h1>

            <p>
                Update quote details, related records, status and validity information.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="view.php?id=<?php echo $id; ?>"
                class="btn btn-secondary"
            >
                View Quote
            </a>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                Quote List
            </a>

        </div>

    </div>

    <!-- Current Record -->

    <div class="record-bar">

        <div class="record-info">

            <div
                class="record-avatar"
                id="recordAvatar"
            >
                <?php echo htmlspecialchars($initial); ?>
            </div>

            <div class="record-text">

                <strong id="recordQuoteNumber">
                    <?php echo htmlspecialchars($quoteNumber); ?>
                </strong>

                <span>
                    Quote ID #<?php echo $id; ?>
                </span>

            </div>

        </div>

        <span
            class="status <?php echo $statusClass; ?>"
            id="recordStatus"
        >
            <?php echo ucfirst(htmlspecialchars($currentStatus)); ?>
        </span>

    </div>

    <!-- Summary -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Quote ID
            </div>

            <div class="summary-value">
                #<?php echo $id; ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Current Status
            </div>

            <div
                class="summary-value"
                id="summaryStatus"
            >
                <?php echo ucfirst(htmlspecialchars($currentStatus)); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Subtotal
            </div>

            <div class="summary-value">
                ₹ <?php echo number_format($subtotal, 2); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Total Amount
            </div>

            <div class="summary-value green">
                ₹ <?php echo number_format($totalAmount, 2); ?>
            </div>

        </div>

    </div>

    <!-- Content -->

    <div class="content-grid">

        <!-- Form -->

        <div class="form-card">

            <div class="card-header">

                <h2>
                    Quote Information
                </h2>

                <p>
                    Update the fields below and save your changes.
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
                                        value="<?php echo htmlspecialchars($quote["quote_number"] ?? ""); ?>"
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

                                    <?php foreach ($statuses as $statusOption): ?>

                                        <option
                                            value="<?php echo htmlspecialchars($statusOption); ?>"
                                            <?php echo ($currentStatus === $statusOption) ? "selected" : ""; ?>
                                        >
                                            <?php echo ucfirst($statusOption); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                    <!-- Related Records -->

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
                                            <?php echo ((string)($quote["company_id"] ?? "") === (string)$company["id"]) ? "selected" : ""; ?>
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
                                            <?php echo ((string)($quote["contact_id"] ?? "") === (string)$contact["id"]) ? "selected" : ""; ?>
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
                                            <?php echo ((string)($quote["customer_id"] ?? "") === (string)$customer["id"]) ? "selected" : ""; ?>
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
                                            <?php echo ((string)($quote["deal_id"] ?? "") === (string)$deal["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php echo htmlspecialchars($deal["title"]); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                    <!-- Validity -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                3
                            </div>

                            <h3>
                                Quote Validity
                            </h3>

                        </div>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="valid_until">
                                    Valid Until
                                </label>

                                <input
                                    type="date"
                                    id="valid_until"
                                    name="valid_until"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($quote["valid_until"] ?? ""); ?>"
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
                            ><?php echo htmlspecialchars($quote["notes"] ?? ""); ?></textarea>

                            <div class="form-help">
                                Update any terms, comments or additional information related to this quote.
                            </div>

                        </div>

                    </div>

                    <!-- Footer -->

                    <div class="form-footer">

                        <a
                            href="view.php?id=<?php echo $id; ?>"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            ✓ Update Quote
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- Side Panel -->

        <div class="side-panel">

            <!-- Live Preview -->

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
                            <?php echo htmlspecialchars($quoteNumber); ?>
                        </div>

                        <div
                            class="preview-status"
                            id="previewStatusText"
                        >
                            <?php echo ucfirst(htmlspecialchars($currentStatus)); ?> Quote
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
                                Valid Until
                            </span>

                            <strong id="previewValidUntil">
                                Not specified
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Total
                            </span>

                            <strong
                                class="preview-total"
                                id="previewTotal"
                            >
                                ₹ <?php echo number_format($totalAmount, 2); ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Financial Summary -->

            <div class="side-card">

                <h3>
                    Current Financial Summary
                </h3>

                <div class="financial-box">

                    <div class="financial-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>
                            ₹ <?php
                            echo number_format(
                                $subtotal,
                                2
                            );
                            ?>
                        </strong>

                    </div>

                    <div class="financial-row">

                        <span>
                            Tax
                        </span>

                        <strong>
                            ₹ <?php
                            echo number_format(
                                $taxAmount,
                                2
                            );
                            ?>
                        </strong>

                    </div>

                    <div class="financial-row">

                        <span>
                            Discount
                        </span>

                        <strong>
                            ₹ <?php
                            echo number_format(
                                $discountAmount,
                                2
                            );
                            ?>
                        </strong>

                    </div>

                    <div class="financial-row">

                        <span>
                            Total
                        </span>

                        <strong style="color:#16a34a;">
                            ₹ <?php
                            echo number_format(
                                $totalAmount,
                                2
                            );
                            ?>
                        </strong>

                    </div>

                </div>

            </div>

            <!-- Editing Guide -->

            <div class="side-card">

                <h3>
                    Editing Guide
                </h3>

                <div class="guide-item">

                    <div class="guide-icon">
                        1
                    </div>

                    <div class="guide-text">
                        Keep the quote number unique and consistent.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        2
                    </div>

                    <div class="guide-text">
                        Update the related company, contact, customer or deal when necessary.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        3
                    </div>

                    <div class="guide-text">
                        Change the status as the quote moves through the sales process.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        4
                    </div>

                    <div class="guide-text">
                        Use the validity date and notes to keep quote information current.
                    </div>

                </div>

            </div>

            <!-- Quick Tip -->

            <div class="side-card">

                <h3>
                    Quick Tip
                </h3>

                <div class="info-box">

                    Quote products and financial totals are managed separately from these general quote details. Use <strong>Manage Products</strong> from the quote view when product items need to be changed.

                </div>

                <div style="margin-top:12px;">

                    <a
                        href="quote_items.php?quote_id=<?php echo $id; ?>"
                        class="btn btn-success"
                        style="width:100%;"
                    >
                        Manage Products
                    </a>

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

    const validUntilInput =
        document.getElementById("valid_until");

    const recordAvatar =
        document.getElementById("recordAvatar");

    const recordQuoteNumber =
        document.getElementById("recordQuoteNumber");

    const recordStatus =
        document.getElementById("recordStatus");

    const summaryStatus =
        document.getElementById("summaryStatus");

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

    const previewValidUntil =
        document.getElementById("previewValidUntil");

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

    function formatDate(dateValue) {

        if (!dateValue) {
            return "Not specified";
        }

        const parts = dateValue.split("-");

        if (parts.length !== 3) {
            return dateValue;
        }

        return parts[2] + "/" +
            parts[1] + "/" +
            parts[0];

    }

    function updateStatusClasses(element, status) {

        element.classList.remove(
            "status-draft",
            "status-sent",
            "status-accepted",
            "status-rejected",
            "status-expired"
        );

        element.classList.add(
            "status-" + status
        );

    }

    function updatePreview() {

        const quoteNumber =
            quoteNumberInput.value.trim();

        const status =
            statusInput.value;

        const displayQuoteNumber =
            quoteNumber !== ""
                ? quoteNumber
                : "New Quote";

        const initial =
            displayQuoteNumber
                .charAt(0)
                .toUpperCase();

        /* Record Bar */

        recordAvatar.textContent =
            initial;

        recordQuoteNumber.textContent =
            displayQuoteNumber;

        recordStatus.textContent =
            status.charAt(0).toUpperCase()
            + status.slice(1);

        updateStatusClasses(
            recordStatus,
            status
        );

        /* Summary */

        summaryStatus.textContent =
            status.charAt(0).toUpperCase()
            + status.slice(1);

        /* Preview */

        previewQuoteNumber.textContent =
            displayQuoteNumber;

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

        previewValidUntil.textContent =
            formatDate(
                validUntilInput.value
            );

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

    validUntilInput.addEventListener(
        "change",
        updatePreview
    );

    updatePreview();

});

</script>

</body>

</html>