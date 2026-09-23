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

$sale_number = "SALE-" . date("Ymd-His");
$customer_id = "";
$company_id = "";
$contact_id = "";
$quote_id = "";
$deal_id = "";
$payment_status = "pending";
$sale_status = "pending";
$sale_date = date("Y-m-d");
$notes = "";

/*
|--------------------------------------------------------------------------
| Load Customers
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        customers.id,
        customers.customer_code,
        companies.company_name
    FROM customers
    LEFT JOIN companies
        ON customers.company_id = companies.id
    ORDER BY customers.customer_code ASC
");

$stmt->execute();

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Companies
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        company_name
    FROM companies
    ORDER BY company_name ASC
");

$stmt->execute();

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Contacts
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        contacts.id,
        contacts.first_name,
        contacts.last_name,
        companies.company_name
    FROM contacts
    LEFT JOIN companies
        ON contacts.company_id = companies.id
    ORDER BY contacts.first_name ASC, contacts.last_name ASC
");

$stmt->execute();

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Quotes
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        quote_number,
        total_amount
    FROM quotes
    ORDER BY id DESC
");

$stmt->execute();

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Deals
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        amount
    FROM deals
    ORDER BY id DESC
");

$stmt->execute();

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Allowed Status Values
|--------------------------------------------------------------------------
*/

$allowed_payment_statuses = [
    "pending",
    "partial",
    "paid"
];

$allowed_sale_statuses = [
    "pending",
    "completed",
    "cancelled"
];

/*
|--------------------------------------------------------------------------
| Add Sale
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sale_number = trim($_POST["sale_number"] ?? "");

    $customer_id = !empty($_POST["customer_id"])
        ? (int)$_POST["customer_id"]
        : null;

    $company_id = !empty($_POST["company_id"])
        ? (int)$_POST["company_id"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int)$_POST["contact_id"]
        : null;

    $quote_id = !empty($_POST["quote_id"])
        ? (int)$_POST["quote_id"]
        : null;

    $deal_id = !empty($_POST["deal_id"])
        ? (int)$_POST["deal_id"]
        : null;

    $payment_status = trim(
        $_POST["payment_status"] ?? "pending"
    );

    $sale_status = trim(
        $_POST["sale_status"] ?? "pending"
    );

    $sale_date = !empty($_POST["sale_date"])
        ? $_POST["sale_date"]
        : date("Y-m-d");

    $notes = trim($_POST["notes"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($sale_number === "") {

        $error = "Sale number is required.";

    } elseif (
        !in_array(
            $payment_status,
            $allowed_payment_statuses,
            true
        )
    ) {

        $error = "Invalid payment status.";

    } elseif (
        !in_array(
            $sale_status,
            $allowed_sale_statuses,
            true
        )
    ) {

        $error = "Invalid sale status.";

    } elseif (
        !preg_match(
            "/^\d{4}-\d{2}-\d{2}$/",
            $sale_date
        )
    ) {

        $error = "Please enter a valid sale date.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Default Financial Values
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $total_amount = 0;

            /*
            |--------------------------------------------------------------------------
            | If Quote Selected, Copy Quote Totals
            |--------------------------------------------------------------------------
            */

            if ($quote_id !== null) {

                $stmt = $conn->prepare("
                    SELECT
                        id,
                        subtotal,
                        tax_amount,
                        discount_amount,
                        total_amount
                    FROM quotes
                    WHERE id = :id
                ");

                $stmt->execute([
                    ":id" => $quote_id
                ]);

                $selected_quote =
                    $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$selected_quote) {

                    $error = "Selected quote was not found.";

                } else {

                    $subtotal =
                        (float)$selected_quote["subtotal"];

                    $tax_amount =
                        (float)$selected_quote["tax_amount"];

                    $discount_amount =
                        (float)$selected_quote["discount_amount"];

                    $total_amount =
                        (float)$selected_quote["total_amount"];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Insert Sale
            |--------------------------------------------------------------------------
            */

            if ($error === "") {

                $sql = "
                    INSERT INTO sales (
                        sale_number,
                        customer_id,
                        company_id,
                        contact_id,
                        quote_id,
                        deal_id,
                        subtotal,
                        tax_amount,
                        discount_amount,
                        total_amount,
                        payment_status,
                        sale_status,
                        sale_date,
                        notes,
                        created_by
                    )
                    VALUES (
                        :sale_number,
                        :customer_id,
                        :company_id,
                        :contact_id,
                        :quote_id,
                        :deal_id,
                        :subtotal,
                        :tax_amount,
                        :discount_amount,
                        :total_amount,
                        :payment_status,
                        :sale_status,
                        :sale_date,
                        :notes,
                        :created_by
                    )
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ":sale_number" => $sale_number,
                    ":customer_id" => $customer_id,
                    ":company_id" => $company_id,
                    ":contact_id" => $contact_id,
                    ":quote_id" => $quote_id,
                    ":deal_id" => $deal_id,
                    ":subtotal" => $subtotal,
                    ":tax_amount" => $tax_amount,
                    ":discount_amount" => $discount_amount,
                    ":total_amount" => $total_amount,
                    ":payment_status" => $payment_status,
                    ":sale_status" => $sale_status,
                    ":sale_date" => $sale_date,
                    ":notes" => $notes,
                    ":created_by" => $_SESSION["user_id"]
                ]);

                $sale_id = $conn->lastInsertId();

                header(
                    "Location: view.php?id=" . $sale_id
                );

                exit;
            }

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error =
                    "Sale number already exists. Please use a different sale number.";

            } else {

                $error =
                    "Unable to create sale. Please try again.";
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

    <title>
        Add Sale - CRM
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

        /* Info */

        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 12px;
            line-height: 1.5;
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

        /* Layout */

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
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        textarea.form-control {
            height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-help {
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* Sale Number */

        .sale-number-wrap {
            position: relative;
        }

        .sale-number-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 13px;
        }

        .sale-number-wrap .form-control {
            padding-left: 34px;
        }

        /* Side */

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

        /* Sale Preview */

        .sale-preview {
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
            gap: 14px;
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

        /* Status Badges */

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .payment-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-partial {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .payment-paid {
            background: #dcfce7;
            color: #166534;
        }

        .sale-pending {
            background: #f1f5f9;
            color: #475569;
        }

        .sale-completed {
            background: #dcfce7;
            color: #166534;
        }

        .sale-cancelled {
            background: #fee2e2;
            color: #991b1b;
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

        /* Financial */

        .financial-box {
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f8fafc;
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }

        .financial-row:last-child {
            border-bottom: none;
        }

        .financial-row span:first-child {
            color: #64748b;
        }

        .financial-row strong {
            color: #1e293b;
            white-space: nowrap;
        }

        /* Form Footer */

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            margin-top: 25px;
            border-top: 1px solid #e5e7eb;
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
        html.dark-mode .preview-row strong,
        html.dark-mode .financial-row strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .card-header p,
        html.dark-mode .form-help,
        html.dark-mode .guide-text,
        html.dark-mode .preview-status,
        html.dark-mode .preview-row span:first-child,
        html.dark-mode .financial-row span:first-child {
            color: #94a3b8;
        }

        html.dark-mode .info-box {
            background: #172554;
            border-color: #1e3a8a;
            color: #bfdbfe;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .sale-preview {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .section-title,
        html.dark-mode .form-footer,
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

        html.dark-mode .guide-icon {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .financial-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .financial-row {
            border-color: #1f2937;
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
            Sales
        </a>

        <span>/</span>

        <span>
            Add Sale
        </span>

    </div>

    <!-- Page Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Add Sale
            </h1>

            <p>
                Create a new sales record and connect it with the related CRM records.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Sales List
            </a>

        </div>

    </div>

    <!-- Information -->

    <div class="info-box">

        <span>ℹ️</span>

        <div>
            When a quote is selected, its current subtotal, tax, discount and total amount are copied into the new sale. Sale products can be managed after the sale is created.
        </div>

    </div>

    <!-- Error -->

    <?php if ($error !== ""): ?>

        <div class="error-box">

            <span>⚠️</span>

            <div>
                <?php echo htmlspecialchars($error); ?>
            </div>

        </div>

    <?php endif; ?>

    <!-- Content -->

    <div class="content-grid">

        <!-- Main Form -->

        <div class="form-card">

            <div class="card-header">

                <h2>
                    Sale Information
                </h2>

                <p>
                    Enter the sale details below and create the sales record.
                </p>

            </div>

            <div class="form-body">

                <form
                    method="POST"
                    autocomplete="off"
                >

                    <!-- Sale Details -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                1
                            </div>

                            <h3>
                                Sale Details
                            </h3>

                        </div>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="sale_number">

                                    Sale Number
                                    <span class="required">*</span>

                                </label>

                                <div class="sale-number-wrap">

                                    <span class="sale-number-icon">
                                        #
                                    </span>

                                    <input
                                        type="text"
                                        id="sale_number"
                                        name="sale_number"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($sale_number); ?>"
                                        placeholder="SALE-20260918-001"
                                        required
                                    >

                                </div>

                                <div class="form-help">
                                    Sale number must be unique.
                                </div>

                            </div>

                            <div class="form-group">

                                <label for="sale_date">
                                    Sale Date
                                </label>

                                <input
                                    type="date"
                                    id="sale_date"
                                    name="sale_date"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($sale_date); ?>"
                                >

                            </div>

                            <div class="form-group">

                                <label for="payment_status">
                                    Payment Status
                                </label>

                                <select
                                    id="payment_status"
                                    name="payment_status"
                                    class="form-control"
                                >

                                    <option
                                        value="pending"
                                        <?php echo ($payment_status === "pending") ? "selected" : ""; ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="partial"
                                        <?php echo ($payment_status === "partial") ? "selected" : ""; ?>
                                    >
                                        Partial
                                    </option>

                                    <option
                                        value="paid"
                                        <?php echo ($payment_status === "paid") ? "selected" : ""; ?>
                                    >
                                        Paid
                                    </option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label for="sale_status">
                                    Sale Status
                                </label>

                                <select
                                    id="sale_status"
                                    name="sale_status"
                                    class="form-control"
                                >

                                    <option
                                        value="pending"
                                        <?php echo ($sale_status === "pending") ? "selected" : ""; ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="completed"
                                        <?php echo ($sale_status === "completed") ? "selected" : ""; ?>
                                    >
                                        Completed
                                    </option>

                                    <option
                                        value="cancelled"
                                        <?php echo ($sale_status === "cancelled") ? "selected" : ""; ?>
                                    >
                                        Cancelled
                                    </option>

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

                                            <?php

                                            echo htmlspecialchars(
                                                $customer["customer_code"]
                                            );

                                            if (!empty($customer["company_name"])) {

                                                echo " - "
                                                    . htmlspecialchars(
                                                        $customer["company_name"]
                                                    );
                                            }

                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

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

                                        $contact_name = trim(
                                            $contact["first_name"]
                                            . " "
                                            . ($contact["last_name"] ?? "")
                                        );

                                        ?>

                                        <option
                                            value="<?php echo (int)$contact["id"]; ?>"
                                            <?php echo ((string)$contact_id === (string)$contact["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $contact_name
                                            );

                                            if (!empty($contact["company_name"])) {

                                                echo " - "
                                                    . htmlspecialchars(
                                                        $contact["company_name"]
                                                    );
                                            }

                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- Quote -->

                            <div class="form-group">

                                <label for="quote_id">
                                    Quote
                                </label>

                                <select
                                    id="quote_id"
                                    name="quote_id"
                                    class="form-control"
                                >

                                    <option
                                        value=""
                                        data-total="0"
                                    >
                                        -- Select Quote --
                                    </option>

                                    <?php foreach ($quotes as $quote): ?>

                                        <option
                                            value="<?php echo (int)$quote["id"]; ?>"
                                            data-total="<?php echo htmlspecialchars($quote["total_amount"]); ?>"
                                            <?php echo ((string)$quote_id === (string)$quote["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $quote["quote_number"]
                                            );

                                            echo " - ₹"
                                                . number_format(
                                                    (float)$quote["total_amount"],
                                                    2
                                                );

                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <div class="form-help">
                                    Selecting a quote copies its financial totals into the sale.
                                </div>

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

                                    <option
                                        value=""
                                        data-total="0"
                                    >
                                        -- Select Deal --
                                    </option>

                                    <?php foreach ($deals as $deal): ?>

                                        <option
                                            value="<?php echo (int)$deal["id"]; ?>"
                                            data-total="<?php echo htmlspecialchars($deal["amount"]); ?>"
                                            <?php echo ((string)$deal_id === (string)$deal["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $deal["title"]
                                            );

                                            echo " - ₹"
                                                . number_format(
                                                    (float)$deal["amount"],
                                                    2
                                                );

                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                    <!-- Notes -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">
                                3
                            </div>

                            <h3>
                                Notes
                            </h3>

                        </div>

                        <div class="form-group">

                            <label for="notes">
                                Sale Notes
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                placeholder="Enter sale notes, payment information or additional details..."
                            ><?php echo htmlspecialchars($notes); ?></textarea>

                            <div class="form-help">
                                Add any additional information associated with this sale.
                            </div>

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
                            + Create Sale
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- Side Panel -->

        <div class="side-panel">

            <!-- Sale Preview -->

            <div class="side-card">

                <h3>
                    Sale Preview
                </h3>

                <div class="sale-preview">

                    <div class="preview-top">

                        <div class="preview-icon">
                            S
                        </div>

                        <div
                            class="preview-number"
                            id="previewSaleNumber"
                        >
                            <?php echo htmlspecialchars($sale_number); ?>
                        </div>

                        <div
                            class="preview-status"
                            id="previewSaleStatus"
                        >
                            Pending Sale
                        </div>

                    </div>

                    <div class="preview-body">

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
                                Quote
                            </span>

                            <strong id="previewQuote">
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
                                Sale Date
                            </span>

                            <strong id="previewDate">
                                <?php echo htmlspecialchars($sale_date); ?>
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
                                ₹ 0.00
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Financial Summary -->

            <div class="side-card">

                <h3>
                    Financial Summary
                </h3>

                <div class="financial-box">

                    <div class="financial-row">

                        <span>
                            Subtotal
                        </span>

                        <strong id="financialSubtotal">
                            ₹ 0.00
                        </strong>

                    </div>

                    <div class="financial-row">

                        <span>
                            Tax
                        </span>

                        <strong>
                            Copied from Quote
                        </strong>

                    </div>

                    <div class="financial-row">

                        <span>
                            Discount
                        </span>

                        <strong>
                            Copied from Quote
                        </strong>

                    </div>

                    <div class="financial-row">

                        <span>
                            Total
                        </span>

                        <strong
                            id="financialTotal"
                            style="color:#16a34a;"
                        >
                            ₹ 0.00
                        </strong>

                    </div>

                </div>

            </div>

            <!-- Status Guide -->

            <div class="side-card">

                <h3>
                    Sale Status Guide
                </h3>

                <div class="guide-item">

                    <div class="guide-icon">
                        1
                    </div>

                    <div class="guide-text">
                        Pending means the sale has not yet been completed.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        2
                    </div>

                    <div class="guide-text">
                        Completed indicates that the sale has been completed.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        3
                    </div>

                    <div class="guide-text">
                        Payment status can be Pending, Partial or Paid.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        4
                    </div>

                    <div class="guide-text">
                        Selecting a quote automatically copies its current financial totals.
                    </div>

                </div>

            </div>

            <!-- Quick Tip -->

            <div class="side-card">

                <h3>
                    Quick Tip
                </h3>

                <div class="guide-text">
                    Create the sale first, then use the sale details page to manage individual sale products and items.
                </div>

            </div>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const saleNumberInput =
        document.getElementById("sale_number");

    const saleDateInput =
        document.getElementById("sale_date");

    const paymentStatusInput =
        document.getElementById("payment_status");

    const saleStatusInput =
        document.getElementById("sale_status");

    const customerInput =
        document.getElementById("customer_id");

    const companyInput =
        document.getElementById("company_id");

    const contactInput =
        document.getElementById("contact_id");

    const quoteInput =
        document.getElementById("quote_id");

    const dealInput =
        document.getElementById("deal_id");

    const previewSaleNumber =
        document.getElementById("previewSaleNumber");

    const previewSaleStatus =
        document.getElementById("previewSaleStatus");

    const previewCustomer =
        document.getElementById("previewCustomer");

    const previewCompany =
        document.getElementById("previewCompany");

    const previewContact =
        document.getElementById("previewContact");

    const previewQuote =
        document.getElementById("previewQuote");

    const previewDeal =
        document.getElementById("previewDeal");

    const previewDate =
        document.getElementById("previewDate");

    const previewTotal =
        document.getElementById("previewTotal");

    const financialSubtotal =
        document.getElementById("financialSubtotal");

    const financialTotal =
        document.getElementById("financialTotal");

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

    function getSelectedTotal(selectElement) {

        if (
            !selectElement ||
            selectElement.selectedIndex < 0
        ) {
            return 0;
        }

        const option =
            selectElement.options[
                selectElement.selectedIndex
            ];

        if (!option || option.value === "") {
            return 0;
        }

        const total =
            parseFloat(
                option.getAttribute("data-total")
            );

        return isNaN(total)
            ? 0
            : total;

    }

    function updatePreview() {

        const saleNumber =
            saleNumberInput.value.trim();

        const paymentStatus =
            paymentStatusInput.value;

        const saleStatus =
            saleStatusInput.value;

        const quoteTotal =
            getSelectedTotal(quoteInput);

        const dealTotal =
            getSelectedTotal(dealInput);

        /*
        |--------------------------------------------------------------------------
        | Quote takes priority for financial total
        |--------------------------------------------------------------------------
        */

        let total = quoteTotal;

        if (
            total === 0 &&
            !quoteInput.value &&
            dealInput.value
        ) {
            total = dealTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | Sale Preview
        |--------------------------------------------------------------------------
        */

        previewSaleNumber.textContent =
            saleNumber !== ""
                ? saleNumber
                : "New Sale";

        previewSaleStatus.textContent =
            saleStatus.charAt(0).toUpperCase()
            + saleStatus.slice(1)
            + " Sale";

        previewCustomer.textContent =
            getSelectedText(customerInput);

        previewCompany.textContent =
            getSelectedText(companyInput);

        previewContact.textContent =
            getSelectedText(contactInput);

        previewQuote.textContent =
            getSelectedText(quoteInput);

        previewDeal.textContent =
            getSelectedText(dealInput);

        previewDate.textContent =
            saleDateInput.value !== ""
                ? saleDateInput.value
                : "Not specified";

        previewTotal.textContent =
            "₹ " + formatAmount(total);

        financialSubtotal.textContent =
            "₹ " + formatAmount(total);

        financialTotal.textContent =
            "₹ " + formatAmount(total);

    }

    saleNumberInput.addEventListener(
        "input",
        updatePreview
    );

    saleDateInput.addEventListener(
        "change",
        updatePreview
    );

    paymentStatusInput.addEventListener(
        "change",
        updatePreview
    );

    saleStatusInput.addEventListener(
        "change",
        updatePreview
    );

    customerInput.addEventListener(
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

    quoteInput.addEventListener(
        "change",
        updatePreview
    );

    dealInput.addEventListener(
        "change",
        updatePreview
    );

    updatePreview();

});

</script>

</body>

</html>