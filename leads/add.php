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

$company_id = "";
$contact_id = "";
$lead_name = "";
$email = "";
$phone = "";
$source = "";
$status = "new";
$lead_value = "0.00";
$notes = "";
$assigned_to = "";


/*
|--------------------------------------------------------------------------
| Get Companies
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id, company_name
    FROM companies
    ORDER BY company_name ASC
";

$stmt = $conn->query($sql);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Contacts
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        first_name,
        last_name
    FROM contacts
    ORDER BY first_name ASC, last_name ASC
";

$stmt = $conn->query($sql);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id, name
    FROM users
    WHERE status = 1
    ORDER BY name ASC
";

$stmt = $conn->query($sql);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $company_id = trim($_POST["company_id"] ?? "");
    $contact_id = trim($_POST["contact_id"] ?? "");
    $lead_name = trim($_POST["lead_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $source = trim($_POST["source"] ?? "");
    $status = trim($_POST["status"] ?? "new");
    $lead_value = trim($_POST["lead_value"] ?? "0.00");
    $notes = trim($_POST["notes"] ?? "");
    $assigned_to = trim($_POST["assigned_to"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $allowedStatuses = [
        "new",
        "contacted",
        "qualified",
        "converted",
        "lost"
    ];

    $allowedSources = [
        "",
        "Website",
        "Referral",
        "Social Media",
        "Email",
        "Phone",
        "Other"
    ];


    if (empty($lead_name)) {

        $error = "Lead name is required.";

    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (!is_numeric($lead_value) || $lead_value < 0) {

        $error = "Lead value must be a valid positive number.";

    } elseif (!in_array($status, $allowedStatuses, true)) {

        $error = "Invalid lead status.";

    } elseif (!in_array($source, $allowedSources, true)) {

        $error = "Invalid lead source.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert Lead
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO leads
                (
                    company_id,
                    contact_id,
                    lead_name,
                    email,
                    phone,
                    source,
                    status,
                    lead_value,
                    notes,
                    assigned_to
                )
                VALUES
                (
                    :company_id,
                    :contact_id,
                    :lead_name,
                    :email,
                    :phone,
                    :source,
                    :status,
                    :lead_value,
                    :notes,
                    :assigned_to
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":company_id" => !empty($company_id)
                    ? $company_id
                    : null,

                ":contact_id" => !empty($contact_id)
                    ? $contact_id
                    : null,

                ":lead_name" => $lead_name,

                ":email" => $email,

                ":phone" => $phone,

                ":source" => $source,

                ":status" => $status,

                ":lead_value" => $lead_value,

                ":notes" => $notes,

                ":assigned_to" => !empty($assigned_to)
                    ? $assigned_to
                    : null
            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to add lead: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Initial Preview Values
|--------------------------------------------------------------------------
*/

$previewInitial = !empty($lead_name)
    ? strtoupper(substr($lead_name, 0, 1))
    : "L";

$selectedCompanyName = "Not selected";
$selectedContactName = "Not selected";
$selectedUserName = "Not assigned";


foreach ($companies as $company) {

    if ((string) $company["id"] === (string) $company_id) {

        $selectedCompanyName = $company["company_name"];

        break;
    }
}


foreach ($contacts as $contact) {

    if ((string) $contact["id"] === (string) $contact_id) {

        $selectedContactName = trim(
            $contact["first_name"] . " " .
            ($contact["last_name"] ?? "")
        );

        break;
    }
}


foreach ($users as $user) {

    if ((string) $user["id"] === (string) $assigned_to) {

        $selectedUserName = $user["name"];

        break;
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

    <title>Add Lead - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
        }

        .page-wrapper {
            max-width: 1450px;
            margin: 0 auto;
        }

        /* ------------------------------------------------------------
           Breadcrumb
        ------------------------------------------------------------ */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #64748b;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        /* ------------------------------------------------------------
           Header
        ------------------------------------------------------------ */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0;
            font-size: 28px;
            color: #172554;
            font-weight: 700;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            background: #ffffff;
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Main Layout
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            align-items: start;
        }

        .form-card,
        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .form-card {
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0;
            color: #172554;
            font-size: 17px;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .form-body {
            padding: 24px;
        }

        /* ------------------------------------------------------------
           Error
        ------------------------------------------------------------ */

        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 22px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            font-size: 14px;
        }

        .error-icon {
            margin-top: 1px;
        }

        /* ------------------------------------------------------------
           Lead Summary
        ------------------------------------------------------------ */

        .lead-summary {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .lead-avatar-small {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .summary-text strong {
            display: block;
            color: #172554;
            font-size: 15px;
        }

        .summary-text span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 13px;
        }

        /* ------------------------------------------------------------
           Form Sections
        ------------------------------------------------------------ */

        .form-section {
            margin-bottom: 28px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 16px;
        }

        .section-icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 14px;
        }

        .section-title h3 {
            margin: 0;
            color: #1e293b;
            font-size: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 17px;
        }

        .form-row:last-child {
            margin-bottom: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .required {
            color: #dc2626;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        input,
        select {
            height: 43px;
        }

        textarea {
            min-height: 115px;
            resize: vertical;
            line-height: 1.5;
        }

        input::placeholder,
        textarea::placeholder {
            color: #94a3b8;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        /* ------------------------------------------------------------
           Status Options
        ------------------------------------------------------------ */

        .status-options {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .status-option {
            position: relative;
        }

        .status-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-label {
            min-height: 40px;
            padding: 0 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-label:hover {
            background: #f8fafc;
            border-color: #93c5fd;
        }

        .status-option input:checked + .status-label.new-status {
            background: #eff6ff;
            color: #2563eb;
            border-color: #93c5fd;
        }

        .status-option input:checked + .status-label.contacted-status {
            background: #fef3c7;
            color: #b45309;
            border-color: #fcd34d;
        }

        .status-option input:checked + .status-label.qualified-status {
            background: #ecfdf5;
            color: #047857;
            border-color: #6ee7b7;
        }

        .status-option input:checked + .status-label.converted-status {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }

        .status-option input:checked + .status-label.lost-status {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        /* ------------------------------------------------------------
           Buttons
        ------------------------------------------------------------ */

        .button-area {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .save-btn {
            min-height: 42px;
            padding: 0 20px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .cancel-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Side Card
        ------------------------------------------------------------ */

        .side-card {
            padding: 20px;
            margin-bottom: 18px;
        }

        .side-title {
            margin-bottom: 17px;
        }

        .side-title h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .side-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ------------------------------------------------------------
           Lead Preview
        ------------------------------------------------------------ */

        .preview-profile {
            text-align: center;
            padding: 7px 0 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .preview-avatar {
            width: 74px;
            height: 74px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            font-weight: 700;
        }

        .preview-name {
            margin: 0;
            color: #172554;
            font-size: 18px;
            font-weight: 700;
        }

        .preview-subtitle {
            margin-top: 5px;
            color: #64748b;
            font-size: 13px;
        }

        .preview-status {
            display: inline-flex;
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .preview-status.new {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .preview-status.contacted {
            background: #fef3c7;
            color: #92400e;
        }

        .preview-status.qualified {
            background: #d1fae5;
            color: #047857;
        }

        .preview-status.converted {
            background: #dcfce7;
            color: #15803d;
        }

        .preview-status.lost {
            background: #fee2e2;
            color: #b91c1c;
        }

        .preview-details {
            padding-top: 17px;
        }

        .preview-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 13px;
        }

        .preview-item:last-child {
            margin-bottom: 0;
        }

        .preview-icon {
            width: 31px;
            height: 31px;
            border-radius: 7px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }

        .preview-text {
            min-width: 0;
        }

        .preview-label {
            display: block;
            margin-bottom: 2px;
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .preview-value {
            display: block;
            color: #334155;
            font-size: 13px;
            word-break: break-word;
        }

        /* ------------------------------------------------------------
           Value Card
        ------------------------------------------------------------ */

        .value-box {
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            margin-top: 16px;
        }

        .value-label {
            display: block;
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .value-amount {
            display: block;
            color: #172554;
            font-size: 20px;
            font-weight: 700;
        }

        /* ------------------------------------------------------------
           Guide
        ------------------------------------------------------------ */

        .guide-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .guide-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 13px;
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
        }

        .guide-list li:last-child {
            margin-bottom: 0;
        }

        .guide-number {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ------------------------------------------------------------
           Tip
        ------------------------------------------------------------ */

        .tip-box {
            padding: 13px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            color: #1e40af;
            font-size: 12px;
            line-height: 1.55;
        }

        .tip-box strong {
            display: block;
            margin-bottom: 3px;
        }

        /* ------------------------------------------------------------
           Dark Mode
        ------------------------------------------------------------ */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .card-header h2,
        html.dark-mode .section-title h3,
        html.dark-mode .side-title h3,
        html.dark-mode .preview-name,
        html.dark-mode .value-amount,
        html.dark-mode .summary-text strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .card-header p,
        html.dark-mode .side-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .summary-text span {
            color: #94a3b8;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .lead-summary {
            background: #0f172a;
            border-color: #1f2937;
        }

        html.dark-mode .section-icon {
            background: #1e3a8a;
            color: #bfdbfe;
        }

        html.dark-mode .card-header,
        html.dark-mode .preview-profile,
        html.dark-mode .button-area {
            border-color: #1f2937;
        }

        html.dark-mode label {
            color: #e2e8f0;
        }

        html.dark-mode input,
        html.dark-mode select,
        html.dark-mode textarea {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark-mode input::placeholder,
        html.dark-mode textarea::placeholder {
            color: #64748b;
        }

        html.dark-mode input:focus,
        html.dark-mode select:focus,
        html.dark-mode textarea:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.12);
        }

        html.dark-mode .status-label {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .status-label:hover {
            background: #1e293b;
        }

        html.dark-mode .status-option input:checked + .status-label.new-status {
            background: #172554;
            color: #93c5fd;
            border-color: #2563eb;
        }

        html.dark-mode .status-option input:checked + .status-label.contacted-status {
            background: #451a03;
            color: #fcd34d;
            border-color: #92400e;
        }

        html.dark-mode .status-option input:checked + .status-label.qualified-status {
            background: #052e16;
            color: #6ee7b7;
            border-color: #047857;
        }

        html.dark-mode .status-option input:checked + .status-label.converted-status {
            background: #052e16;
            color: #86efac;
            border-color: #166534;
        }

        html.dark-mode .status-option input:checked + .status-label.lost-status {
            background: #450a0a;
            color: #fca5a5;
            border-color: #991b1b;
        }

        html.dark-mode .back-btn,
        html.dark-mode .cancel-btn {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .back-btn:hover,
        html.dark-mode .cancel-btn:hover {
            background: #1e293b;
        }

        html.dark-mode .preview-icon,
        html.dark-mode .value-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .preview-value {
            color: #cbd5e1;
        }

        html.dark-mode .value-label {
            color: #64748b;
        }

        html.dark-mode .guide-list li {
            color: #cbd5e1;
        }

        html.dark-mode .tip-box {
            background: #172554;
            border-color: #1e3a8a;
            color: #bfdbfe;
        }

        /* ------------------------------------------------------------
           Responsive
        ------------------------------------------------------------ */

        @media (max-width: 1080px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .form-group.full {
                grid-column: auto;
            }

            .form-body {
                padding: 18px;
            }

            .card-header {
                padding: 18px;
            }

            .status-options {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .button-area {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .save-btn,
            .cancel-btn {
                width: 100%;
            }

            .header-actions {
                width: 100%;
            }

            .back-btn {
                flex: 1;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-wrapper">

        <!-- =========================================================
             Breadcrumb
        ========================================================== -->

        <div class="breadcrumb">

            <a href="index.php">
                Leads
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                Add Lead
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Add Lead
                </h1>

                <p>
                    Create a new lead and assign it to your sales team.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="index.php"
                    class="back-btn"
                >
                    ← Lead List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Main Grid
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 FORM
            ====================================================== -->

            <div class="form-card">

                <div class="card-header">

                    <h2>
                        Lead Information
                    </h2>

                    <p>
                        Enter the lead details below.
                    </p>

                </div>


                <div class="form-body">


                    <?php if (!empty($error)): ?>

                        <div class="error-box">

                            <span class="error-icon">
                                ⚠
                            </span>

                            <span>
                                <?php echo htmlspecialchars($error); ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- Lead Summary -->

                    <div class="lead-summary">

                        <div
                            class="lead-avatar-small"
                            id="summaryAvatar"
                        >
                            <?php echo htmlspecialchars($previewInitial); ?>
                        </div>


                        <div class="summary-text">

                            <strong id="summaryName">

                                <?php
                                echo $lead_name !== ""
                                    ? htmlspecialchars($lead_name)
                                    : "New Lead";
                                ?>

                            </strong>


                            <span id="summarySubtitle">

                                <?php
                                echo $source !== ""
                                    ? htmlspecialchars($source)
                                    : "Lead information";
                                ?>

                            </span>

                        </div>

                    </div>


                    <form method="POST">


                        <!-- =================================================
                             LEAD DETAILS
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    🎯
                                </div>

                                <h3>
                                    Lead Details
                                </h3>

                            </div>


                            <!-- Lead Name -->

                            <div class="form-row">

                                <div class="form-group full">

                                    <label for="lead_name">

                                        Lead Name

                                        <span class="required">*</span>

                                    </label>

                                    <input
                                        type="text"
                                        name="lead_name"
                                        id="lead_name"
                                        placeholder="Enter lead name"
                                        value="<?php echo htmlspecialchars($lead_name); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- Company / Contact -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="company_id">
                                        Company
                                    </label>

                                    <select
                                        name="company_id"
                                        id="company_id"
                                    >

                                        <option value="">
                                            -- Select Company --
                                        </option>

                                        <?php foreach ($companies as $company): ?>

                                            <option
                                                value="<?php echo (int) $company["id"]; ?>"
                                                <?php echo (
                                                    (string) $company_id ===
                                                    (string) $company["id"]
                                                ) ? "selected" : ""; ?>
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


                                <div class="form-group">

                                    <label for="contact_id">
                                        Contact
                                    </label>

                                    <select
                                        name="contact_id"
                                        id="contact_id"
                                    >

                                        <option value="">
                                            -- Select Contact --
                                        </option>

                                        <?php foreach ($contacts as $contact): ?>

                                            <option
                                                value="<?php echo (int) $contact["id"]; ?>"
                                                <?php echo (
                                                    (string) $contact_id ===
                                                    (string) $contact["id"]
                                                ) ? "selected" : ""; ?>
                                            >

                                                <?php

                                                $contactName = trim(
                                                    $contact["first_name"] .
                                                    " " .
                                                    ($contact["last_name"] ?? "")
                                                );

                                                echo htmlspecialchars(
                                                    $contactName
                                                );

                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             CONTACT INFORMATION
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    ☎
                                </div>

                                <h3>
                                    Contact Information
                                </h3>

                            </div>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="email">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        id="email"
                                        placeholder="name@example.com"
                                        value="<?php echo htmlspecialchars($email); ?>"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="phone">
                                        Phone
                                    </label>

                                    <input
                                        type="text"
                                        name="phone"
                                        id="phone"
                                        placeholder="+91 98765 43210"
                                        value="<?php echo htmlspecialchars($phone); ?>"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             LEAD STATUS
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    📊
                                </div>

                                <h3>
                                    Lead Status & Source
                                </h3>

                            </div>


                            <!-- Source / Status -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="source">
                                        Lead Source
                                    </label>

                                    <select
                                        name="source"
                                        id="source"
                                    >

                                        <option value="">
                                            -- Select Source --
                                        </option>

                                        <option
                                            value="Website"
                                            <?php echo (
                                                $source === "Website"
                                            ) ? "selected" : ""; ?>
                                        >
                                            Website
                                        </option>

                                        <option
                                            value="Referral"
                                            <?php echo (
                                                $source === "Referral"
                                            ) ? "selected" : ""; ?>
                                        >
                                            Referral
                                        </option>

                                        <option
                                            value="Social Media"
                                            <?php echo (
                                                $source === "Social Media"
                                            ) ? "selected" : ""; ?>
                                        >
                                            Social Media
                                        </option>

                                        <option
                                            value="Email"
                                            <?php echo (
                                                $source === "Email"
                                            ) ? "selected" : ""; ?>
                                        >
                                            Email
                                        </option>

                                        <option
                                            value="Phone"
                                            <?php echo (
                                                $source === "Phone"
                                            ) ? "selected" : ""; ?>
                                        >
                                            Phone
                                        </option>

                                        <option
                                            value="Other"
                                            <?php echo (
                                                $source === "Other"
                                            ) ? "selected" : ""; ?>
                                        >
                                            Other
                                        </option>

                                    </select>

                                </div>


                                <div class="form-group">

                                    <label>
                                        Status
                                    </label>

                                    <div class="status-options">


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_new"
                                                value="new"
                                                <?php echo (
                                                    $status === "new"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_new"
                                                class="status-label new-status"
                                            >
                                                New
                                            </label>

                                        </div>


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_contacted"
                                                value="contacted"
                                                <?php echo (
                                                    $status === "contacted"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_contacted"
                                                class="status-label contacted-status"
                                            >
                                                Contacted
                                            </label>

                                        </div>


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_qualified"
                                                value="qualified"
                                                <?php echo (
                                                    $status === "qualified"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_qualified"
                                                class="status-label qualified-status"
                                            >
                                                Qualified
                                            </label>

                                        </div>


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_converted"
                                                value="converted"
                                                <?php echo (
                                                    $status === "converted"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_converted"
                                                class="status-label converted-status"
                                            >
                                                Converted
                                            </label>

                                        </div>


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_lost"
                                                value="lost"
                                                <?php echo (
                                                    $status === "lost"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_lost"
                                                class="status-label lost-status"
                                            >
                                                Lost
                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- Value / Assigned -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="lead_value">
                                        Lead Value
                                    </label>

                                    <input
                                        type="number"
                                        name="lead_value"
                                        id="lead_value"
                                        value="<?php echo htmlspecialchars($lead_value); ?>"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="assigned_to">
                                        Assigned To
                                    </label>

                                    <select
                                        name="assigned_to"
                                        id="assigned_to"
                                    >

                                        <option value="">
                                            -- Select User --
                                        </option>

                                        <?php foreach ($users as $user): ?>

                                            <option
                                                value="<?php echo (int) $user["id"]; ?>"
                                                <?php echo (
                                                    (string) $assigned_to ===
                                                    (string) $user["id"]
                                                ) ? "selected" : ""; ?>
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $user["name"]
                                                );
                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             NOTES
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    📝
                                </div>

                                <h3>
                                    Notes
                                </h3>

                            </div>


                            <div class="form-row">

                                <div class="form-group full">

                                    <label for="notes">
                                        Lead Notes
                                    </label>

                                    <textarea
                                        name="notes"
                                        id="notes"
                                        placeholder="Add additional information about this lead..."
                                    ><?php echo htmlspecialchars($notes); ?></textarea>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             BUTTONS
                        ================================================== -->

                        <div class="button-area">

                            <a
                                href="index.php"
                                class="cancel-btn"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="save-btn"
                            >
                                Save Lead
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDE
            ====================================================== -->

            <div>


                <!-- Lead Preview -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Lead Preview
                        </h3>

                        <p>
                            Preview how the lead will appear in your CRM.
                        </p>

                    </div>


                    <div class="preview-profile">

                        <div
                            class="preview-avatar"
                            id="previewAvatar"
                        >
                            <?php echo htmlspecialchars($previewInitial); ?>
                        </div>


                        <h3
                            class="preview-name"
                            id="previewName"
                        >

                            <?php
                            echo $lead_name !== ""
                                ? htmlspecialchars($lead_name)
                                : "New Lead";
                            ?>

                        </h3>


                        <div
                            class="preview-subtitle"
                            id="previewSubtitle"
                        >

                            <?php
                            echo $source !== ""
                                ? htmlspecialchars($source)
                                : "Lead source";
                            ?>

                        </div>


                        <span
                            class="preview-status <?php echo htmlspecialchars($status); ?>"
                            id="previewStatus"
                        >

                            <?php
                            echo htmlspecialchars(
                                ucfirst($status)
                            );
                            ?>

                        </span>

                    </div>


                    <div class="preview-details">


                        <!-- Company -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                🏢
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Company
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewCompany"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $selectedCompanyName
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>


                        <!-- Contact -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                👤
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Contact
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewContact"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $selectedContactName
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>


                        <!-- Email -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                ✉
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Email
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewEmail"
                                >

                                    <?php
                                    echo $email !== ""
                                        ? htmlspecialchars($email)
                                        : "Not provided";
                                    ?>

                                </span>

                            </div>

                        </div>


                        <!-- Phone -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                ☎
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Phone
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewPhone"
                                >

                                    <?php
                                    echo $phone !== ""
                                        ? htmlspecialchars($phone)
                                        : "Not provided";
                                    ?>

                                </span>

                            </div>

                        </div>


                        <!-- Assigned -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                👨‍💼
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Assigned To
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewAssigned"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $selectedUserName
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Lead Value -->

                    <div class="value-box">

                        <span class="value-label">
                            Estimated Lead Value
                        </span>

                        <span
                            class="value-amount"
                            id="previewValue"
                        >
                            ₹<?php echo number_format(
                                (float) $lead_value,
                                2
                            ); ?>
                        </span>

                    </div>

                </div>


                <!-- Setup Guide -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Lead Setup Guide
                        </h3>

                        <p>
                            Recommended information for a complete lead record.
                        </p>

                    </div>


                    <ul class="guide-list">

                        <li>

                            <span class="guide-number">
                                1
                            </span>

                            <span>
                                Enter a clear and recognizable lead name.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                2
                            </span>

                            <span>
                                Link the lead with the relevant company and contact.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                3
                            </span>

                            <span>
                                Select the source and current lead status.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                4
                            </span>

                            <span>
                                Assign the lead to a sales team member.
                            </span>

                        </li>

                    </ul>

                </div>


                <!-- Quick Tip -->

                <div class="side-card">

                    <div class="tip-box">

                        <strong>
                            💡 Quick Tip
                        </strong>

                        Keep lead source, status, owner, and estimated value updated so your sales team can track the opportunity clearly.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const leadNameInput =
        document.getElementById("lead_name");

    const companyInput =
        document.getElementById("company_id");

    const contactInput =
        document.getElementById("contact_id");

    const emailInput =
        document.getElementById("email");

    const phoneInput =
        document.getElementById("phone");

    const sourceInput =
        document.getElementById("source");

    const leadValueInput =
        document.getElementById("lead_value");

    const assignedInput =
        document.getElementById("assigned_to");

    const statusInputs =
        document.querySelectorAll(
            'input[name="status"]'
        );


    const previewAvatar =
        document.getElementById("previewAvatar");

    const previewName =
        document.getElementById("previewName");

    const previewSubtitle =
        document.getElementById("previewSubtitle");

    const previewStatus =
        document.getElementById("previewStatus");

    const previewCompany =
        document.getElementById("previewCompany");

    const previewContact =
        document.getElementById("previewContact");

    const previewEmail =
        document.getElementById("previewEmail");

    const previewPhone =
        document.getElementById("previewPhone");

    const previewAssigned =
        document.getElementById("previewAssigned");

    const previewValue =
        document.getElementById("previewValue");

    const summaryAvatar =
        document.getElementById("summaryAvatar");

    const summaryName =
        document.getElementById("summaryName");

    const summarySubtitle =
        document.getElementById("summarySubtitle");


    function updatePreview() {

        /* ------------------------------------------------------------
           Lead Name
        ------------------------------------------------------------ */

        const leadName =
            leadNameInput.value.trim();

        previewName.textContent =
            leadName || "New Lead";

        summaryName.textContent =
            leadName || "New Lead";


        /* ------------------------------------------------------------
           Avatar
        ------------------------------------------------------------ */

        const initial =
            leadName
                ? leadName.charAt(0).toUpperCase()
                : "L";

        previewAvatar.textContent =
            initial;

        summaryAvatar.textContent =
            initial;


        /* ------------------------------------------------------------
           Source
        ------------------------------------------------------------ */

        const source =
            sourceInput.value;

        previewSubtitle.textContent =
            source || "Lead source";

        summarySubtitle.textContent =
            source || "Lead information";


        /* ------------------------------------------------------------
           Company
        ------------------------------------------------------------ */

        if (companyInput.value) {

            const option =
                companyInput.options[
                    companyInput.selectedIndex
                ];

            previewCompany.textContent =
                option.text.trim();

        } else {

            previewCompany.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Contact
        ------------------------------------------------------------ */

        if (contactInput.value) {

            const option =
                contactInput.options[
                    contactInput.selectedIndex
                ];

            previewContact.textContent =
                option.text.trim();

        } else {

            previewContact.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Email
        ------------------------------------------------------------ */

        const email =
            emailInput.value.trim();

        previewEmail.textContent =
            email || "Not provided";


        /* ------------------------------------------------------------
           Phone
        ------------------------------------------------------------ */

        const phone =
            phoneInput.value.trim();

        previewPhone.textContent =
            phone || "Not provided";


        /* ------------------------------------------------------------
           Assigned User
        ------------------------------------------------------------ */

        if (assignedInput.value) {

            const option =
                assignedInput.options[
                    assignedInput.selectedIndex
                ];

            previewAssigned.textContent =
                option.text.trim();

        } else {

            previewAssigned.textContent =
                "Not assigned";
        }


        /* ------------------------------------------------------------
           Lead Value
        ------------------------------------------------------------ */

        let numericValue =
            parseFloat(leadValueInput.value);

        if (isNaN(numericValue)) {
            numericValue = 0;
        }

        previewValue.textContent =
            "₹" +
            numericValue.toLocaleString(
                "en-IN",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );


        /* ------------------------------------------------------------
           Status
        ------------------------------------------------------------ */

        let selectedStatus =
            "new";

        statusInputs.forEach(function (input) {

            if (input.checked) {
                selectedStatus =
                    input.value;
            }

        });


        previewStatus.textContent =
            selectedStatus
                .charAt(0)
                .toUpperCase() +
            selectedStatus.slice(1);


        previewStatus.classList.remove(
            "new",
            "contacted",
            "qualified",
            "converted",
            "lost"
        );

        previewStatus.classList.add(
            selectedStatus
        );

    }


    /* ------------------------------------------------------------
       Event Listeners
    ------------------------------------------------------------ */

    leadNameInput.addEventListener(
        "input",
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

    emailInput.addEventListener(
        "input",
        updatePreview
    );

    phoneInput.addEventListener(
        "input",
        updatePreview
    );

    sourceInput.addEventListener(
        "change",
        updatePreview
    );

    leadValueInput.addEventListener(
        "input",
        updatePreview
    );

    assignedInput.addEventListener(
        "change",
        updatePreview
    );

    statusInputs.forEach(function (input) {

        input.addEventListener(
            "change",
            updatePreview
        );

    });


    updatePreview();

});

</script>

</body>

</html>