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
| Get Sale
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT *
    FROM sales
    WHERE id = :id
");

$stmt->execute([
    ":id" => $id
]);

$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header("Location: index.php");
    exit;
}

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
| Allowed Statuses
|--------------------------------------------------------------------------
*/

$payment_statuses = [
    "pending",
    "partial",
    "paid"
];

$sale_statuses = [
    "pending",
    "completed",
    "cancelled"
];

/*
|--------------------------------------------------------------------------
| Update Sale
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sale_number = trim($_POST["sale_number"] ?? "");

    $customer_id = !empty($_POST["customer_id"])
        ? (int) $_POST["customer_id"]
        : null;

    $company_id = !empty($_POST["company_id"])
        ? (int) $_POST["company_id"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int) $_POST["contact_id"]
        : null;

    $quote_id = !empty($_POST["quote_id"])
        ? (int) $_POST["quote_id"]
        : null;

    $deal_id = !empty($_POST["deal_id"])
        ? (int) $_POST["deal_id"]
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

    $notes = trim(
        $_POST["notes"] ?? ""
    );

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
            $payment_statuses,
            true
        )
    ) {

        $error = "Invalid payment status.";

    } elseif (
        !in_array(
            $sale_status,
            $sale_statuses,
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
            | Update Sale
            |--------------------------------------------------------------------------
            |
            | Financial totals are intentionally not changed here.
            | Sale item totals should be managed from sale_items.php.
            |
            */

            $sql = "
                UPDATE sales
                SET
                    sale_number = :sale_number,
                    customer_id = :customer_id,
                    company_id = :company_id,
                    contact_id = :contact_id,
                    quote_id = :quote_id,
                    deal_id = :deal_id,
                    payment_status = :payment_status,
                    sale_status = :sale_status,
                    sale_date = :sale_date,
                    notes = :notes
                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":sale_number" => $sale_number,
                ":customer_id" => $customer_id,
                ":company_id" => $company_id,
                ":contact_id" => $contact_id,
                ":quote_id" => $quote_id,
                ":deal_id" => $deal_id,
                ":payment_status" => $payment_status,
                ":sale_status" => $sale_status,
                ":sale_date" => $sale_date,
                ":notes" => $notes,
                ":id" => $id
            ]);

            header(
                "Location: view.php?id=" . $id
            );

            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error =
                    "Sale number already exists. Please use a different sale number.";

            } else {

                $error =
                    "Unable to update sale. Please try again.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Keep Entered Values After Validation Error
    |--------------------------------------------------------------------------
    */

    $sale["sale_number"] = $sale_number;
    $sale["customer_id"] = $customer_id;
    $sale["company_id"] = $company_id;
    $sale["contact_id"] = $contact_id;
    $sale["quote_id"] = $quote_id;
    $sale["deal_id"] = $deal_id;
    $sale["payment_status"] = $payment_status;
    $sale["sale_status"] = $sale_status;
    $sale["sale_date"] = $sale_date;
    $sale["notes"] = $notes;
}

/*
|--------------------------------------------------------------------------
| Display Values
|--------------------------------------------------------------------------
*/

$current_payment_status = strtolower(
    $sale["payment_status"] ?? "pending"
);

if (
    !in_array(
        $current_payment_status,
        $payment_statuses,
        true
    )
) {
    $current_payment_status = "pending";
}

$current_sale_status = strtolower(
    $sale["sale_status"] ?? "pending"
);

if (
    !in_array(
        $current_sale_status,
        $sale_statuses,
        true
    )
) {
    $current_sale_status = "pending";
}

$sale_number_display =
    $sale["sale_number"] ?? "Sale";

$initial = strtoupper(
    substr(
        trim($sale_number_display) !== ""
            ? $sale_number_display
            : "S",
        0,
        1
    )
);

$subtotal = (float) (
    $sale["subtotal"] ?? 0
);

$tax_amount = (float) (
    $sale["tax_amount"] ?? 0
);

$discount_amount = (float) (
    $sale["discount_amount"] ?? 0
);

$total_amount = (float) (
    $sale["total_amount"] ?? 0
);

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
        Edit Sale - CRM
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

        /* Current Record */

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

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
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

        /* Main Grid */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Form */

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

        /* Fields */

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

        /* Footer */

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
        html.dark-mode .financial-row span:first-child {
            color: #94a3b8;
        }

        html.dark-mode .info-box {
            background: #172554;
            border-color: #1e3a8a;
            color: #bfdbfe;
        }

        html.dark-mode .record-bar,
        html.dark-mode .summary-card,
        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .sale-preview {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
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

        html.dark-mode .card-header,
        html.dark-mode .section-title,
        html.dark-mode .form-footer,
        html.dark-mode .preview-row,
        html.dark-mode .financial-row {
            border-color: #1f2937;
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

        html.dark-mode .financial-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .guide-icon {
            background: #172554;
            color: #93c5fd;
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
            Sales
        </a>

        <span>/</span>

        <span>
            Edit Sale
        </span>

    </div>

    <!-- Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Edit Sale
            </h1>

            <p>
                Update sale information and related CRM records.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="view.php?id=<?php echo $id; ?>"
                class="btn btn-secondary"
            >
                View Sale
            </a>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                Sales List
            </a>

        </div>

    </div>

    <!-- Information -->

    <div class="info-box">

        <span>ℹ️</span>

        <div>
            Product quantities and financial totals are managed separately from this page through the sale products/items section.
        </div>

    </div>

    <?php if ($error !== ""): ?>

        <div class="error-box">

            <span>⚠️</span>

            <div>
                <?php echo htmlspecialchars($error); ?>
            </div>

        </div>

    <?php endif; ?>

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

                <strong id="recordSaleNumber">
                    <?php echo htmlspecialchars($sale_number_display); ?>
                </strong>

                <span>
                    Sale ID #<?php echo $id; ?>
                </span>

            </div>

        </div>

        <div
            class="status-badge <?php echo "sale-" . $current_sale_status; ?>"
            id="recordSaleStatus"
        >
            <?php echo ucfirst($current_sale_status); ?>
        </div>

    </div>

    <!-- Summary -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Sale ID
            </div>

            <div class="summary-value">
                #<?php echo $id; ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Sale Status
            </div>

            <div
                class="summary-value"
                id="summarySaleStatus"
            >
                <?php echo ucfirst($current_sale_status); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Payment Status
            </div>

            <div
                class="summary-value"
                id="summaryPaymentStatus"
            >
                <?php echo ucfirst($current_payment_status); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Total Amount
            </div>

            <div class="summary-value green">
                ₹ <?php echo number_format($total_amount, 2); ?>
            </div>

        </div>

    </div>

    <!-- Main -->

    <div class="content-grid">

        <!-- Form -->

        <div class="form-card">

            <div class="card-header">

                <h2>
                    Sale Information
                </h2>

                <p>
                    Update the sale details below and save your changes.
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
                                        value="<?php echo htmlspecialchars($sale["sale_number"] ?? ""); ?>"
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
                                    value="<?php echo htmlspecialchars($sale["sale_date"] ?? ""); ?>"
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
                                        <?php echo ($current_payment_status === "pending") ? "selected" : ""; ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="partial"
                                        <?php echo ($current_payment_status === "partial") ? "selected" : ""; ?>
                                    >
                                        Partial
                                    </option>

                                    <option
                                        value="paid"
                                        <?php echo ($current_payment_status === "paid") ? "selected" : ""; ?>
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
                                        <?php echo ($current_sale_status === "pending") ? "selected" : ""; ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="completed"
                                        <?php echo ($current_sale_status === "completed") ? "selected" : ""; ?>
                                    >
                                        Completed
                                    </option>

                                    <option
                                        value="cancelled"
                                        <?php echo ($current_sale_status === "cancelled") ? "selected" : ""; ?>
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
                                            <?php echo ((string)($sale["customer_id"] ?? "") === (string)$customer["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $customer["customer_code"]
                                            );

                                            if (
                                                !empty(
                                                    $customer["company_name"]
                                                )
                                            ) {

                                                echo " - "
                                                    . htmlspecialchars(
                                                        $customer[
                                                            "company_name"
                                                        ]
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
                                            <?php echo ((string)($sale["company_id"] ?? "") === (string)$company["id"]) ? "selected" : ""; ?>
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
                                            <?php echo ((string)($sale["contact_id"] ?? "") === (string)$contact["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $contact_name
                                            );

                                            if (
                                                !empty(
                                                    $contact["company_name"]
                                                )
                                            ) {

                                                echo " - "
                                                    . htmlspecialchars(
                                                        $contact[
                                                            "company_name"
                                                        ]
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

                                    <option value="">
                                        -- Select Quote --
                                    </option>

                                    <?php foreach ($quotes as $quote): ?>

                                        <option
                                            value="<?php echo (int)$quote["id"]; ?>"
                                            <?php echo ((string)($sale["quote_id"] ?? "") === (string)$quote["id"]) ? "selected" : ""; ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $quote["quote_number"]
                                            );

                                            echo " - ₹"
                                                . number_format(
                                                    (float)$quote[
                                                        "total_amount"
                                                    ],
                                                    2
                                                );

                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <div class="form-help">
                                    Changing the linked quote here does not automatically overwrite sale item totals.
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

                                    <option value="">
                                        -- Select Deal --
                                    </option>

                                    <?php foreach ($deals as $deal): ?>

                                        <option
                                            value="<?php echo (int)$deal["id"]; ?>"
                                            <?php echo ((string)($sale["deal_id"] ?? "") === (string)$deal["id"]) ? "selected" : ""; ?>
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
                                placeholder="Enter sale notes..."
                            ><?php echo htmlspecialchars($sale["notes"] ?? ""); ?></textarea>

                            <div class="form-help">
                                Update any additional sale information here.
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
                            ✓ Update Sale
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- Side Panel -->

        <div class="side-panel">

            <!-- Preview -->

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
                            <?php echo htmlspecialchars($sale_number_display); ?>
                        </div>

                        <div
                            class="preview-status"
                            id="previewSaleStatus"
                        >
                            <?php echo ucfirst($current_sale_status); ?> Sale
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
                                <?php echo htmlspecialchars($sale["sale_date"] ?? ""); ?>
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>
                                Payment
                            </span>

                            <strong id="previewPayment">
                                <?php echo ucfirst($current_payment_status); ?>
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
                                ₹ <?php echo number_format($total_amount, 2); ?>
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
                                $tax_amount,
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
                                $discount_amount,
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
                                $total_amount,
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
                        Keep the sale number unique and consistent.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        2
                    </div>

                    <div class="guide-text">
                        Update the customer, company, contact, quote or deal when required.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        3
                    </div>

                    <div class="guide-text">
                        Payment status tracks whether the sale is pending, partially paid or paid.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">
                        4
                    </div>

                    <div class="guide-text">
                        Sale item quantities and financial totals should be managed separately.
                    </div>

                </div>

            </div>

            <!-- Manage Products -->

            <div class="side-card">

                <h3>
                    Sale Products
                </h3>

                <div class="guide-text">
                    Use the Manage Products page to add, remove or update products associated with this sale.
                </div>

                <div style="margin-top:12px;">

                    <a
                        href="sale_items.php?sale_id=<?php echo $id; ?>"
                        class="btn btn-success"
                        style="width:100%;"
                    >
                        + Manage Products
                    </a>

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

    const recordAvatar =
        document.getElementById("recordAvatar");

    const recordSaleNumber =
        document.getElementById("recordSaleNumber");

    const recordSaleStatus =
        document.getElementById("recordSaleStatus");

    const summarySaleStatus =
        document.getElementById("summarySaleStatus");

    const summaryPaymentStatus =
        document.getElementById("summaryPaymentStatus");

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

    const previewPayment =
        document.getElementById("previewPayment");

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

    function updateSaleStatusClass(element, status) {

        element.classList.remove(
            "sale-pending",
            "sale-completed",
            "sale-cancelled"
        );

        element.classList.add(
            "sale-" + status
        );

    }

    function updatePreview() {

        const saleNumber =
            saleNumberInput.value.trim();

        const paymentStatus =
            paymentStatusInput.value;

        const saleStatus =
            saleStatusInput.value;

        const displaySaleNumber =
            saleNumber !== ""
                ? saleNumber
                : "New Sale";

        const initial =
            displaySaleNumber
                .charAt(0)
                .toUpperCase();

        /*
        |--------------------------------------------------------------------------
        | Current Record
        |--------------------------------------------------------------------------
        */

        recordAvatar.textContent =
            initial;

        recordSaleNumber.textContent =
            displaySaleNumber;

        recordSaleStatus.textContent =
            saleStatus
                .charAt(0)
                .toUpperCase()
                + saleStatus.slice(1);

        updateSaleStatusClass(
            recordSaleStatus,
            saleStatus
        );

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        summarySaleStatus.textContent =
            saleStatus
                .charAt(0)
                .toUpperCase()
                + saleStatus.slice(1);

        summaryPaymentStatus.textContent =
            paymentStatus
                .charAt(0)
                .toUpperCase()
                + paymentStatus.slice(1);

        /*
        |--------------------------------------------------------------------------
        | Preview
        |--------------------------------------------------------------------------
        */

        previewSaleNumber.textContent =
            displaySaleNumber;

        previewSaleStatus.textContent =
            saleStatus
                .charAt(0)
                .toUpperCase()
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

        previewPayment.textContent =
            paymentStatus
                .charAt(0)
                .toUpperCase()
                + paymentStatus.slice(1);

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