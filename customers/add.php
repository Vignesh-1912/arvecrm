<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $customer_code = trim($_POST["customer_code"] ?? "");
    $customer_type = trim($_POST["customer_type"] ?? "business");
    $status = trim($_POST["status"] ?? "active");
    $credit_limit = trim($_POST["credit_limit"] ?? "0");
    $notes = trim($_POST["notes"] ?? "");

    if (empty($customer_code)) {

        $error = "Customer code is required.";

    } else {

        try {

            $sql = "INSERT INTO customers
                    (
                        customer_code,
                        customer_type,
                        status,
                        credit_limit,
                        notes,
                        created_by
                    )
                    VALUES
                    (
                        :customer_code,
                        :customer_type,
                        :status,
                        :credit_limit,
                        :notes,
                        :created_by
                    )";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":customer_code" => $customer_code,
                ":customer_type" => $customer_type,
                ":status" => $status,
                ":credit_limit" => $credit_limit,
                ":notes" => $notes,
                ":created_by" => $_SESSION["user_id"]
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $error = "Customer code already exists.";

            } else {

                $error = "Unable to save customer. Please try again.";

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

    <title>Add Customer - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

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


        /* =========================================================
           PAGE
        ========================================================= */

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
            border-radius: 14px;
            padding: 22px 24px;
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
            font-size: 23px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 24px;
            color: #172033;
        }

        .page-header p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #64748b;
        }

        .back-button {
            height: 40px;
            padding: 0 15px;
            border: 1px solid #dbe1ea;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #ffffff;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .back-button:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }


        /* =========================================================
           MAIN LAYOUT
        ========================================================= */

        .form-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
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
            padding: 18px 22px;
            border-bottom: 1px solid #eef2f7;
        }

        .form-card-header h2 {
            margin: 0;
            font-size: 16px;
            color: #172033;
        }

        .form-card-header p {
            margin: 4px 0 0;
            font-size: 11px;
            color: #94a3b8;
        }

        .form-card-body {
            padding: 22px;
        }


        /* =========================================================
           ALERT
        ========================================================= */

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert-icon {
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-error .alert-icon {
            background: #fee2e2;
            color: #b91c1c;
        }


        /* =========================================================
           FORM SECTIONS
        ========================================================= */

        .form-section {
            margin-bottom: 24px;
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
            font-size: 10px;
            color: #94a3b8;
        }


        /* =========================================================
           FORM GRID
        ========================================================= */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full-width {
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

        .field-hint {
            margin-top: 5px;
            font-size: 10px;
            color: #94a3b8;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: #94a3b8;
            pointer-events: none;
        }

        .form-control,
        .form-select,
        .form-textarea {
            width: 100%;
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #172033;
            outline: none;
            border-radius: 9px;
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

        .form-control.with-icon {
            padding-left: 38px;
        }

        .form-textarea {
            min-height: 120px;
            padding: 12px 13px;
            resize: vertical;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-control::placeholder,
        .form-textarea::placeholder {
            color: #b0b8c4;
        }

        .form-control:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }

        .form-control:hover,
        .form-select:hover,
        .form-textarea:hover {
            border-color: #c5ceda;
        }

        .form-select {
            cursor: pointer;
        }


        /* =========================================================
           STATUS SELECT
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            background: #ffffff;
            transition: 0.2s ease;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-active-dot {
            background: #22c55e;
        }

        .status-inactive-dot {
            background: #94a3b8;
        }

        .status-option input:checked + label {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #2563eb;
        }


        /* =========================================================
           FORM FOOTER
        ========================================================= */

        .form-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            height: 42px;
            padding: 0 17px;
            border-radius: 9px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-cancel {
            background: #ffffff;
            border: 1px solid #dbe1ea;
            color: #475569;
        }

        .btn-cancel:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .btn-save {
            background: #2563eb;
            color: #ffffff;
            min-width: 145px;
            box-shadow:
                0 4px 10px
                rgba(37, 99, 235, 0.20);
        }

        .btn-save:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow:
                0 6px 14px
                rgba(37, 99, 235, 0.25);
        }


        /* =========================================================
           SIDE INFORMATION CARD
        ========================================================= */

        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .side-card-header {
            padding: 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .side-card-header h3 {
            margin: 0;
            font-size: 14px;
            color: #172033;
        }

        .side-card-header p {
            margin: 5px 0 0;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .side-card-body {
            padding: 18px;
        }

        .info-item {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            margin-bottom: 17px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 32px;
            font-size: 14px;
        }

        .info-content strong {
            display: block;
            font-size: 11px;
            color: #334155;
            margin-bottom: 3px;
        }

        .info-content span {
            display: block;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }


        /* =========================================================
           TIP CARD
        ========================================================= */

        .tip-card {
            margin-top: 18px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 12px;
            padding: 15px;
        }

        .tip-title {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 6px;
        }

        .tip-text {
            font-size: 10px;
            color: #475569;
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
        html.dark-mode .form-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
        }

        html.dark-mode .page-header h1,
        html.dark-mode .form-card-header h2,
        html.dark-mode .section-heading h3,
        html.dark-mode .side-card-header h3 {
            color: #f8fafc;
        }

        html.dark-mode .page-header p,
        html.dark-mode .form-card-header p,
        html.dark-mode .side-card-header p,
        html.dark-mode .field-hint,
        html.dark-mode .form-label,
        html.dark-mode .info-content strong {
            color: #cbd5e1;
        }

        html.dark-mode .breadcrumb,
        html.dark-mode .breadcrumb a {
            color: #94a3b8;
        }

        html.dark-mode .breadcrumb-current {
            color: #e2e8f0;
        }

        html.dark-mode .back-button,
        html.dark-mode .btn-cancel,
        html.dark-mode .form-control,
        html.dark-mode .form-select,
        html.dark-mode .form-textarea,
        html.dark-mode .status-option label {
            background: #0f172a;
            border-color: #334155;
            color: #e5e7eb;
        }

        html.dark-mode .form-control::placeholder,
        html.dark-mode .form-textarea::placeholder {
            color: #64748b;
        }

        html.dark-mode .back-button:hover,
        html.dark-mode .btn-cancel:hover {
            background: #1e293b;
        }

        html.dark-mode .status-option input:checked + label {
            background: #172554;
            border-color: #3b82f6;
            color: #93c5fd;
        }

        html.dark-mode .form-card-header,
        html.dark-mode .side-card-header,
        html.dark-mode .form-footer {
            border-color: #1f2937;
        }

        html.dark-mode .info-icon {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .info-content span {
            color: #94a3b8;
        }

        html.dark-mode .tip-card {
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

            .side-card {
                order: 2;
            }

        }

        @media (max-width: 760px) {

            .main-content {
                padding: 18px !important;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-header-left {
                width: 100%;
            }

            .back-button {
                width: 100%;
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: auto;
            }

            .form-footer {
                flex-direction: column-reverse;
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
                Add Customer
            </span>

        </div>


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="page-header-left">

                <div class="page-header-icon">
                    👥
                </div>

                <div>

                    <h1>
                        Add Customer
                    </h1>

                    <p>
                        Create a new customer record in your CRM.
                    </p>

                </div>

            </div>

            <a
                href="index.php"
                class="back-button"
            >
                ← Back to Customers
            </a>

        </div>


        <!-- =====================================================
             MAIN FORM LAYOUT
        ====================================================== -->

        <div class="form-layout">


            <!-- =================================================
                 FORM
            ================================================== -->

            <div class="form-card">

                <div class="form-card-header">

                    <h2>
                        Customer Information
                    </h2>

                    <p>
                        Enter the customer details below.
                    </p>

                </div>


                <div class="form-card-body">


                    <?php if (!empty($error)): ?>

                        <div class="alert alert-error">

                            <div class="alert-icon">
                                !
                            </div>

                            <div>
                                <?= htmlspecialchars($error) ?>
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
                                    Required details
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
                                        <span class="required">*</span>
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
                                            placeholder="Example: CUST-001"
                                            maxlength="50"
                                            required
                                            value="<?= htmlspecialchars(
                                                $_POST["customer_code"] ?? ""
                                            ) ?>"
                                        >

                                    </div>

                                    <div class="field-hint">
                                        Use a unique code for this customer.
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
                                                ($_POST["customer_type"] ?? "business")
                                                === "business"
                                            ) ? "selected" : "" ?>
                                        >
                                            Business
                                        </option>

                                        <option
                                            value="individual"
                                            <?= (
                                                ($_POST["customer_type"] ?? "")
                                                === "individual"
                                            ) ? "selected" : "" ?>
                                        >
                                            Individual
                                        </option>

                                    </select>

                                    <div class="field-hint">
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
                                    Customer status
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
                                                    ($_POST["status"] ?? "active")
                                                    === "active"
                                                ) ? "checked" : "" ?>
                                            >

                                            <label
                                                for="status_active"
                                            >

                                                <span class="status-dot status-active-dot"></span>

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
                                                    ($_POST["status"] ?? "")
                                                    === "inactive"
                                                ) ? "checked" : "" ?>
                                            >

                                            <label
                                                for="status_inactive"
                                            >

                                                <span class="status-dot status-inactive-dot"></span>

                                                Inactive

                                            </label>

                                        </div>

                                    </div>

                                    <div class="field-hint">
                                        Inactive customers can be kept for historical records.
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
                                            value="<?= htmlspecialchars(
                                                $_POST["credit_limit"] ?? "0"
                                            ) ?>"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                        >

                                    </div>

                                    <div class="field-hint">
                                        Maximum credit amount allowed for this customer.
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
                                    placeholder="Add any useful notes, account details, payment information, or internal comments..."
                                ><?= htmlspecialchars(
                                    $_POST["notes"] ?? ""
                                ) ?></textarea>

                                <div class="field-hint">
                                    These notes are for internal CRM reference.
                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             FOOTER BUTTONS
                        ====================================== -->

                        <div class="form-footer">

                            <a
                                href="index.php"
                                class="btn btn-cancel"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="btn btn-save"
                            >
                                ✓ Save Customer
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- =================================================
                 SIDE INFORMATION
            ================================================== -->

            <div>


                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Customer Setup
                        </h3>

                        <p>
                            A few things to keep in mind when creating a customer.
                        </p>

                    </div>


                    <div class="side-card-body">


                        <div class="info-item">

                            <div class="info-icon">
                                #
                            </div>

                            <div class="info-content">

                                <strong>
                                    Customer Code
                                </strong>

                                <span>
                                    Keep the code unique so the customer can be identified easily.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                👤
                            </div>

                            <div class="info-content">

                                <strong>
                                    Customer Type
                                </strong>

                                <span>
                                    Choose Business for organizations or Individual for personal customers.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                ✓
                            </div>

                            <div class="info-content">

                                <strong>
                                    Account Status
                                </strong>

                                <span>
                                    Active customers can be used in normal CRM workflows.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                ₹
                            </div>

                            <div class="info-content">

                                <strong>
                                    Credit Limit
                                </strong>

                                <span>
                                    Enter the approved credit amount. Use 0 when no credit limit is required.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                📝
                            </div>

                            <div class="info-content">

                                <strong>
                                    Internal Notes
                                </strong>

                                <span>
                                    Add useful information for your CRM team to reference later.
                                </span>

                            </div>

                        </div>


                    </div>

                </div>


                <div class="tip-card">

                    <div class="tip-title">

                        💡

                        Quick Tip

                    </div>

                    <div class="tip-text">

                        Use a consistent customer-code format such as
                        <strong>CUST-001</strong>,
                        <strong>CUST-002</strong>,
                        and so on.

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