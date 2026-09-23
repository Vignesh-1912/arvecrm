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
| Check Customer ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;

}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Customer Details
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


/*
|--------------------------------------------------------------------------
| Customer Not Found
|--------------------------------------------------------------------------
*/

if (!$customer) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Update Customer
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $customer_code = trim(
        $_POST["customer_code"] ?? ""
    );

    $customer_type = trim(
        $_POST["customer_type"] ?? "business"
    );

    $status = trim(
        $_POST["status"] ?? "active"
    );

    $credit_limit = trim(
        $_POST["credit_limit"] ?? "0"
    );

    $notes = trim(
        $_POST["notes"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($customer_code === "") {

        $error = "Customer code is required.";

    } elseif (
        $customer_type !== "business" &&
        $customer_type !== "individual"
    ) {

        $error = "Invalid customer type.";

    } elseif (
        $status !== "active" &&
        $status !== "inactive"
    ) {

        $error = "Invalid customer status.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Update Query
            |--------------------------------------------------------------------------
            */

            $sql = "
                UPDATE customers SET
                    customer_code = :customer_code,
                    customer_type = :customer_type,
                    status = :status,
                    credit_limit = :credit_limit,
                    notes = :notes
                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":customer_code" => $customer_code,
                ":customer_type" => $customer_type,
                ":status" => $status,
                ":credit_limit" => $credit_limit,
                ":notes" => $notes,
                ":id" => $id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect After Successful Update
            |--------------------------------------------------------------------------
            */

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Duplicate Customer Code
            |--------------------------------------------------------------------------
            */

            if ($e->getCode() === "23000") {

                $error =
                    "Customer code already exists.";

            } else {

                $error =
                    "Unable to update customer. Please try again.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Preserve Entered Values After Validation Error
    |--------------------------------------------------------------------------
    */

    $customer["customer_code"] =
        $customer_code;

    $customer["customer_type"] =
        $customer_type;

    $customer["status"] =
        $status;

    $customer["credit_limit"] =
        $credit_limit;

    $customer["notes"] =
        $notes;
}


/*
|--------------------------------------------------------------------------
| Helper
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

    return date(
        "d M Y, h:i A",
        $timestamp
    );
}


/*
|--------------------------------------------------------------------------
| Customer Display Data
|--------------------------------------------------------------------------
*/

$customerId =
    (int) $customer["id"];

$customerCode =
    $customer["customer_code"] ?? "";

$customerType =
    $customer["customer_type"] ?? "business";

$status =
    strtolower(
        trim(
            (string) (
                $customer["status"] ?? "active"
            )
        )
    );

$creditLimit =
    (float) (
        $customer["credit_limit"] ?? 0
    );

$notes =
    $customer["notes"] ?? "";

$createdAt =
    $customer["created_at"] ?? "";

$updatedAt =
    $customer["updated_at"] ?? "";

$createdBy =
    $customer["created_by"] ?? "";


/*
|--------------------------------------------------------------------------
| Customer Type
|--------------------------------------------------------------------------
*/

if ($customerType === "business") {

    $typeLabel = "Business";
    $typeIcon = "🏢";

} else {

    $typeLabel = "Individual";
    $typeIcon = "👤";

}


/*
|--------------------------------------------------------------------------
| Customer Status
|--------------------------------------------------------------------------
*/

if ($status === "active") {

    $statusLabel = "Active";
    $statusClass = "status-active";

} else {

    $statusLabel = "Inactive";
    $statusClass = "status-inactive";

}


/*
|--------------------------------------------------------------------------
| Customer Initial
|--------------------------------------------------------------------------
*/

$customerInitial =
    strtoupper(
        substr(
            trim($customerCode),
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
        Edit Customer - CRM
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

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        .edit-page {
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
           PAGE HEADER
        ========================================================= */

        .page-header {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            padding: 21px 23px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .page-header-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex: 0 0 50px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 24px;
            color: #172033;
        }

        .page-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .header-actions {
            display: flex;
            gap: 9px;
            flex-shrink: 0;
        }

        .header-button {
            height: 40px;
            padding: 0 14px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .view-button {
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #475569;
        }

        .view-button:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
        }

        .list-button {
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #475569;
        }

        .list-button:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
        }


        /* =========================================================
           CUSTOMER SUMMARY
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
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .summary-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex: 0 0 40px;
        }

        .summary-label {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: 700;
            color: #172033;
            word-break: break-word;
        }


        /* =========================================================
           MAIN LAYOUT
        ========================================================= */

        .form-layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                330px;
            gap: 18px;
            align-items: start;
        }


        /* =========================================================
           FORM CARD
        ========================================================= */

        .form-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .form-card-header {
            padding: 18px 21px;
            border-bottom: 1px solid #eef2f7;
        }

        .form-card-header h2 {
            margin: 0;
            color: #172033;
            font-size: 16px;
        }

        .form-card-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 11px;
        }

        .form-card-body {
            padding: 21px;
        }


        /* =========================================================
           ERROR
        ========================================================= */

        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 13px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert-icon {
            width: 22px;
            height: 22px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 22px;
            font-weight: 700;
        }


        /* =========================================================
           FORM SECTION
        ========================================================= */

        .form-section {
            margin-bottom: 25px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 15px;
        }

        .section-number {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .section-heading h3 {
            margin: 0;
            font-size: 13px;
            color: #334155;
        }

        .section-heading span {
            margin-left: auto;
            color: #94a3b8;
            font-size: 10px;
        }


        /* =========================================================
           FORM GRID
        ========================================================= */

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .form-group {
            min-width: 0;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .required {
            color: #ef4444;
        }

        .form-hint {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }

        .form-control,
        .form-select,
        .form-textarea {
            width: 100%;
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #172033;
            border-radius: 9px;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .form-control,
        .form-select {
            height: 43px;
            padding: 0 13px;
            font-size: 13px;
        }

        .with-icon {
            padding-left: 38px;
        }

        .form-textarea {
            min-height: 120px;
            padding: 12px 13px;
            resize: vertical;
            line-height: 1.6;
            font-size: 13px;
        }

        .form-control::placeholder,
        .form-textarea::placeholder {
            color: #b0b8c4;
        }

        .form-control:hover,
        .form-select:hover,
        .form-textarea:hover {
            border-color: #c4ceda;
        }

        .form-control:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }

        .form-select {
            cursor: pointer;
        }


        /* =========================================================
           STATUS OPTIONS
        ========================================================= */

        .status-row {
            display: flex;
            gap: 10px;
        }

        .status-option {
            flex: 1;
            position: relative;
        }

        .status-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-option label {
            height: 43px;
            border: 1px solid #dbe1ea;
            border-radius: 9px;
            background: #ffffff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .status-option label:hover {
            border-color: #c4ceda;
        }

        .status-option input:checked + label {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #2563eb;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .active-dot {
            background: #22c55e;
        }

        .inactive-dot {
            background: #94a3b8;
        }


        /* =========================================================
           CURRENT RECORD BAR
        ========================================================= */

        .current-record {
            margin-bottom: 20px;
            padding: 13px 15px;
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .current-record-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .record-avatar {
            width: 35px;
            height: 35px;
            border-radius: 9px;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }

        .record-title {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
        }

        .record-id {
            margin-top: 3px;
            font-size: 10px;
            color: #94a3b8;
        }


        /* =========================================================
           FORM FOOTER
        ========================================================= */

        .form-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eef2f7;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .button {
            height: 42px;
            padding: 0 17px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .cancel-button {
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #475569;
        }

        .cancel-button:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .update-button {
            min-width: 145px;
            border: none;
            background: #2563eb;
            color: #ffffff;
            box-shadow:
                0 4px 10px
                rgba(37, 99, 235, 0.18);
        }

        .update-button:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow:
                0 6px 14px
                rgba(37, 99, 235, 0.24);
        }


        /* =========================================================
           SIDE CARDS
        ========================================================= */

        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .side-card + .side-card {
            margin-top: 18px;
        }

        .side-card-header {
            padding: 17px 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .side-card-header h3 {
            margin: 0;
            color: #172033;
            font-size: 14px;
        }

        .side-card-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .side-card-body {
            padding: 18px;
        }


        /* =========================================================
           CUSTOMER SUMMARY SIDE
        ========================================================= */

        .profile-box {
            display: flex;
            align-items: center;
            gap: 11px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eef2f7;
            margin-bottom: 15px;
        }

        .profile-avatar {
            width: 44px;
            height: 44px;
            border-radius: 11px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #60a5fa
            );
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .profile-code {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        .profile-type {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .side-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .side-detail:last-child {
            border-bottom: none;
        }

        .side-label {
            color: #64748b;
            font-size: 10px;
        }

        .side-value {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            text-align: right;
        }

        .mini-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
        }

        .mini-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .mini-active {
            color: #166534;
        }

        .mini-active .mini-dot {
            background: #22c55e;
        }

        .mini-inactive {
            color: #64748b;
        }

        .mini-inactive .mini-dot {
            background: #94a3b8;
        }


        /* =========================================================
           INFO / TIPS
        ========================================================= */

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 16px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            width: 31px;
            height: 31px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex: 0 0 31px;
        }

        .info-text strong {
            display: block;
            font-size: 11px;
            color: #334155;
            margin-bottom: 3px;
        }

        .info-text span {
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .tip-box {
            margin-top: 18px;
            padding: 14px;
            border-radius: 11px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .tip-title {
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .tip-text {
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

        html.dark-mode .page-header,
        html.dark-mode .summary-card,
        html.dark-mode .form-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
        }

        html.dark-mode .page-header h1,
        html.dark-mode .form-card-header h2,
        html.dark-mode .section-heading h3,
        html.dark-mode .side-card-header h3,
        html.dark-mode .record-title,
        html.dark-mode .summary-value,
        html.dark-mode .profile-code,
        html.dark-mode .info-text strong {
            color: #f8fafc;
        }

        html.dark-mode .page-header p,
        html.dark-mode .form-card-header p,
        html.dark-mode .section-heading span,
        html.dark-mode .form-hint,
        html.dark-mode .side-card-header p,
        html.dark-mode .profile-type,
        html.dark-mode .side-label,
        html.dark-mode .info-text span {
            color: #94a3b8;
        }

        html.dark-mode .breadcrumb,
        html.dark-mode .breadcrumb a {
            color: #64748b;
        }

        html.dark-mode .breadcrumb-current {
            color: #cbd5e1;
        }

        html.dark-mode .header-button,
        html.dark-mode .cancel-button,
        html.dark-mode .form-control,
        html.dark-mode .form-select,
        html.dark-mode .form-textarea,
        html.dark-mode .status-option label {
            background: #0f172a;
            border-color: #334155;
            color: #e5e7eb;
        }

        html.dark-mode .view-button:hover,
        html.dark-mode .list-button:hover,
        html.dark-mode .cancel-button:hover {
            background: #1e293b;
            border-color: #475569;
            color: #93c5fd;
        }

        html.dark-mode .form-control::placeholder,
        html.dark-mode .form-textarea::placeholder {
            color: #64748b;
        }

        html.dark-mode .status-option input:checked + label {
            background: #172554;
            border-color: #3b82f6;
            color: #93c5fd;
        }

        html.dark-mode .current-record {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .record-id {
            color: #64748b;
        }

        html.dark-mode .form-card-header,
        html.dark-mode .side-card-header,
        html.dark-mode .form-footer,
        html.dark-mode .profile-box,
        html.dark-mode .side-detail {
            border-color: #1f2937;
        }

        html.dark-mode .info-icon {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .tip-box {
            background: #172554;
            border-color: #1e40af;
        }

        html.dark-mode .tip-title {
            color: #93c5fd;
        }

        html.dark-mode .tip-text {
            color: #cbd5e1;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1050px) {

            .form-layout {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-button {
                flex: 1;
            }

        }

        @media (max-width: 650px) {

            .main-content {
                padding: 18px !important;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

            .status-row {
                flex-direction: column;
            }

            .form-footer {
                flex-direction: column-reverse;
            }

            .form-footer .button {
                width: 100%;
            }

            .header-actions {
                flex-direction: column;
            }

            .header-button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="edit-page">


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
                Edit Customer
            </span>

        </div>


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="page-header-left">

                <div class="page-header-icon">
                    ✎
                </div>

                <div>

                    <h1>
                        Edit Customer
                    </h1>

                    <p>
                        Update customer information and account settings.
                    </p>

                </div>

            </div>

            <div class="header-actions">

                <a
                    href="view.php?id=<?= e($customerId) ?>"
                    class="header-button view-button"
                >
                    👁 View Customer
                </a>

                <a
                    href="index.php"
                    class="header-button list-button"
                >
                    ← Customer List
                </a>

            </div>

        </div>


        <!-- =====================================================
             CUSTOMER SUMMARY
        ====================================================== -->

        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-avatar">
                    #
                </div>

                <div>

                    <div class="summary-label">
                        Customer ID
                    </div>

                    <div class="summary-value">
                        #<?= e($customerId) ?>
                    </div>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-avatar">
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

                <div class="summary-avatar">
                    ₹
                </div>

                <div>

                    <div class="summary-label">
                        Current Credit Limit
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
             FORM LAYOUT
        ====================================================== -->

        <div class="form-layout">


            <!-- =================================================
                 EDIT FORM
            ================================================== -->

            <div class="form-card">

                <div class="form-card-header">

                    <h2>
                        Customer Information
                    </h2>

                    <p>
                        Modify the customer details below and save your changes.
                    </p>

                </div>


                <div class="form-card-body">


                    <!-- Current Record -->

                    <div class="current-record">

                        <div class="current-record-left">

                            <div class="record-avatar">

                                <?= e($customerInitial) ?>

                            </div>

                            <div>

                                <div class="record-title">
                                    <?= e($customerCode) ?>
                                </div>

                                <div class="record-id">
                                    Customer ID #<?= e($customerId) ?>
                                </div>

                            </div>

                        </div>

                        <div>

                            <span
                                class="mini-status <?= (
                                    $status === "active"
                                )
                                ? "mini-active"
                                : "mini-inactive" ?>"
                            >

                                <span
                                    class="mini-dot"
                                ></span>

                                <?= e($statusLabel) ?>

                            </span>

                        </div>

                    </div>


                    <?php if (!empty($error)): ?>

                        <div class="alert-error">

                            <div class="alert-icon">
                                !
                            </div>

                            <div>
                                <?= e($error) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        autocomplete="off"
                    >


                        <!-- =====================================
                             BASIC INFORMATION
                        ====================================== -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-number">
                                    1
                                </div>

                                <h3>
                                    Basic Information
                                </h3>

                                <span>
                                    Customer identity
                                </span>

                            </div>


                            <div class="form-grid">


                                <!-- Customer Code -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="customer_code"
                                    >

                                        Customer Code

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            #
                                        </span>

                                        <input
                                            type="text"
                                            id="customer_code"
                                            name="customer_code"
                                            class="form-control with-icon"
                                            value="<?= e(
                                                $customer["customer_code"] ?? ""
                                            ) ?>"
                                            placeholder="Example: CUST-001"
                                            maxlength="50"
                                            required
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Customer code must be unique.
                                    </div>

                                </div>


                                <!-- Customer Type -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="customer_type"
                                    >
                                        Customer Type
                                    </label>

                                    <select
                                        id="customer_type"
                                        name="customer_type"
                                        class="form-select"
                                    >

                                        <option
                                            value="business"
                                            <?= (
                                                $customerType
                                                === "business"
                                            )
                                            ? "selected"
                                            : "" ?>
                                        >
                                            Business
                                        </option>

                                        <option
                                            value="individual"
                                            <?= (
                                                $customerType
                                                === "individual"
                                            )
                                            ? "selected"
                                            : "" ?>
                                        >
                                            Individual
                                        </option>

                                    </select>

                                    <div class="form-hint">
                                        Select the type of customer account.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             ACCOUNT SETTINGS
                        ====================================== -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-number">
                                    2
                                </div>

                                <h3>
                                    Account Settings
                                </h3>

                                <span>
                                    Status & financial settings
                                </span>

                            </div>


                            <div class="form-grid">


                                <!-- Status -->

                                <div class="form-group">

                                    <label class="form-label">
                                        Account Status
                                    </label>

                                    <div class="status-row">


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_active"
                                                value="active"
                                                <?= (
                                                    $status === "active"
                                                )
                                                ? "checked"
                                                : "" ?>
                                            >

                                            <label
                                                for="status_active"
                                            >

                                                <span
                                                    class="status-dot active-dot"
                                                ></span>

                                                Active

                                            </label>

                                        </div>


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_inactive"
                                                value="inactive"
                                                <?= (
                                                    $status === "inactive"
                                                )
                                                ? "checked"
                                                : "" ?>
                                            >

                                            <label
                                                for="status_inactive"
                                            >

                                                <span
                                                    class="status-dot inactive-dot"
                                                ></span>

                                                Inactive

                                            </label>

                                        </div>


                                    </div>

                                    <div class="form-hint">
                                        Set inactive when the account should no longer be treated as active.
                                    </div>

                                </div>


                                <!-- Credit Limit -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="credit_limit"
                                    >
                                        Credit Limit
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            ₹
                                        </span>

                                        <input
                                            type="number"
                                            id="credit_limit"
                                            name="credit_limit"
                                            class="form-control with-icon"
                                            value="<?= e(
                                                $customer["credit_limit"] ?? "0"
                                            ) ?>"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Enter the approved credit amount for this customer.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             NOTES
                        ====================================== -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-number">
                                    3
                                </div>

                                <h3>
                                    Additional Information
                                </h3>

                                <span>
                                    Optional
                                </span>

                            </div>


                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="notes"
                                >
                                    Customer Notes
                                </label>

                                <textarea
                                    id="notes"
                                    name="notes"
                                    class="form-textarea"
                                    placeholder="Add customer notes, account information, payment details, or internal comments..."
                                ><?= e(
                                    $customer["notes"] ?? ""
                                ) ?></textarea>

                                <div class="form-hint">
                                    Notes are stored as internal CRM information.
                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             BUTTONS
                        ====================================== -->

                        <div class="form-footer">

                            <a
                                href="index.php"
                                class="button cancel-button"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="button update-button"
                            >
                                ✓ Update Customer
                            </button>

                        </div>


                    </form>

                </div>

            </div>


            <!-- =================================================
                 RIGHT SIDE
            ================================================== -->

            <div>


                <!-- Customer Summary -->

                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Customer Summary
                        </h3>

                        <p>
                            Current information for this customer record.
                        </p>

                    </div>


                    <div class="side-card-body">


                        <div class="profile-box">

                            <div class="profile-avatar">
                                <?= e($customerInitial) ?>
                            </div>

                            <div>

                                <div class="profile-code">
                                    <?= e($customerCode) ?>
                                </div>

                                <div class="profile-type">
                                    <?= e($typeLabel) ?> Customer
                                </div>

                            </div>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Customer ID
                            </span>

                            <span class="side-value">
                                #<?= e($customerId) ?>
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Status
                            </span>

                            <span class="side-value">

                                <span
                                    class="mini-status <?= (
                                        $status === "active"
                                    )
                                    ? "mini-active"
                                    : "mini-inactive" ?>"
                                >

                                    <span class="mini-dot"></span>

                                    <?= e($statusLabel) ?>

                                </span>

                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Type
                            </span>

                            <span class="side-value">
                                <?= e($typeLabel) ?>
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Credit Limit
                            </span>

                            <span class="side-value">
                                ₹<?= number_format(
                                    $creditLimit,
                                    2
                                ) ?>
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Created
                            </span>

                            <span class="side-value">
                                <?= e(
                                    formatDateValue(
                                        $createdAt
                                    )
                                ) ?>
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Updated
                            </span>

                            <span class="side-value">
                                <?= e(
                                    formatDateValue(
                                        $updatedAt
                                    )
                                ) ?>
                            </span>

                        </div>


                    </div>

                </div>


                <!-- Editing Guide -->

                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Editing Guide
                        </h3>

                        <p>
                            Information about updating this record.
                        </p>

                    </div>


                    <div class="side-card-body">


                        <div class="info-item">

                            <div class="info-icon">
                                #
                            </div>

                            <div class="info-text">

                                <strong>
                                    Customer Code
                                </strong>

                                <span>
                                    Keep customer codes unique and consistent.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                👤
                            </div>

                            <div class="info-text">

                                <strong>
                                    Customer Type
                                </strong>

                                <span>
                                    Use Business for organizations and Individual for personal customers.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                ✓
                            </div>

                            <div class="info-text">

                                <strong>
                                    Account Status
                                </strong>

                                <span>
                                    Inactive customers remain in the CRM for historical reference.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                ₹
                            </div>

                            <div class="info-text">

                                <strong>
                                    Credit Limit
                                </strong>

                                <span>
                                    Update the credit limit only when the approved amount changes.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                📝
                            </div>

                            <div class="info-text">

                                <strong>
                                    Notes
                                </strong>

                                <span>
                                    Keep important internal customer information here.
                                </span>

                            </div>

                        </div>


                    </div>

                </div>


                <!-- Tip -->

                <div class="tip-box">

                    <div class="tip-title">
                        💡 Quick Tip
                    </div>

                    <div class="tip-text">
                        Review the customer information before clicking
                        <strong>Update Customer</strong>.
                        Changes are saved immediately.
                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const codeInput =
            document.getElementById(
                "customer_code"
            );

        if (codeInput) {

            codeInput.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.toUpperCase();

                }
            );

        }

    }
);

</script>

</body>

</html>