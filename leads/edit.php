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
| Check Lead ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Lead
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM leads
    WHERE id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$lead = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Lead Not Found
|--------------------------------------------------------------------------
*/

if (!$lead) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$company_id = $lead["company_id"] ?? "";
$contact_id = $lead["contact_id"] ?? "";
$lead_name = $lead["lead_name"] ?? "";
$email = $lead["email"] ?? "";
$phone = $lead["phone"] ?? "";
$source = $lead["source"] ?? "";
$status = $lead["status"] ?? "new";
$lead_value = $lead["lead_value"] ?? "0.00";
$notes = $lead["notes"] ?? "";
$assigned_to = $lead["assigned_to"] ?? "";


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
| Handle Update
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
            | Update Lead
            |--------------------------------------------------------------------------
            */

            $sql = "
                UPDATE leads SET

                    company_id = :company_id,
                    contact_id = :contact_id,
                    lead_name = :lead_name,
                    email = :email,
                    phone = :phone,
                    source = :source,
                    status = :status,
                    lead_value = :lead_value,
                    notes = :notes,
                    assigned_to = :assigned_to

                WHERE id = :id
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
                    : null,

                ":id" => $id

            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            $error = "Unable to update lead: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Preview Values
|--------------------------------------------------------------------------
*/

$fullLeadName = $lead_name !== ""
    ? $lead_name
    : "Lead";

$initial = strtoupper(
    substr(
        $lead_name !== ""
            ? $lead_name
            : "L",
        0,
        1
    )
);


/*
|--------------------------------------------------------------------------
| Selected Names
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

$allowedStatusClasses = [
    "new",
    "contacted",
    "qualified",
    "converted",
    "lost"
];

$statusClass = in_array(
    $status,
    $allowedStatusClasses,
    true
)
    ? $status
    : "new";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Lead - CRM</title>

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
           Page Header
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

        .header-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .view-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .view-btn:hover {
            background: #1d4ed8;
        }

        .list-btn {
            background: #ffffff;
            color: #334155;
            border: 1px solid #dbe2ea;
        }

        .list-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Record Bar
        ------------------------------------------------------------ */

        .record-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            margin-bottom: 22px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
        }

        .record-avatar {
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

        .record-info strong {
            display: block;
            color: #172554;
            font-size: 15px;
        }

        .record-info span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
        }

        .record-id {
            margin-left: auto;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
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
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 14px;
        }

        .error-icon {
            margin-top: 2px;
        }

        /* ------------------------------------------------------------
           Sections
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
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
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
           Status
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

        .status-option input:checked + .new-status {
            background: #eff6ff;
            color: #2563eb;
            border-color: #93c5fd;
        }

        .status-option input:checked + .contacted-status {
            background: #fef3c7;
            color: #b45309;
            border-color: #fcd34d;
        }

        .status-option input:checked + .qualified-status {
            background: #ecfdf5;
            color: #047857;
            border-color: #6ee7b7;
        }

        .status-option input:checked + .converted-status {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }

        .status-option input:checked + .lost-status {
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
            align-items: center;
            gap: 10px;
            padding-top: 22px;
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .update-btn {
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

        .update-btn:hover {
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
           Side Cards
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
           Preview
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
            font-weight: 700;
            text-transform: uppercase;
        }

        .preview-value {
            display: block;
            color: #334155;
            font-size: 13px;
            word-break: break-word;
        }

        /* ------------------------------------------------------------
           Value Box
        ------------------------------------------------------------ */

        .value-box {
            margin-top: 16px;
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
        }

        .value-label {
            display: block;
            margin-bottom: 4px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
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
        html.dark-mode .record-info strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .card-header p,
        html.dark-mode .side-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .record-info span {
            color: #94a3b8;
        }

        html.dark-mode .record-bar,
        html.dark-mode .form-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .record-id {
            color: #94a3b8;
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

        html.dark-mode .status-option input:checked + .new-status {
            background: #172554;
            color: #93c5fd;
            border-color: #2563eb;
        }

        html.dark-mode .status-option input:checked + .contacted-status {
            background: #451a03;
            color: #fcd34d;
            border-color: #92400e;
        }

        html.dark-mode .status-option input:checked + .qualified-status {
            background: #052e16;
            color: #6ee7b7;
            border-color: #047857;
        }

        html.dark-mode .status-option input:checked + .converted-status {
            background: #052e16;
            color: #86efac;
            border-color: #166534;
        }

        html.dark-mode .status-option input:checked + .lost-status {
            background: #450a0a;
            color: #fca5a5;
            border-color: #991b1b;
        }

        html.dark-mode .list-btn,
        html.dark-mode .cancel-btn {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .list-btn:hover,
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

        @media (max-width: 800px) {

            .page-header {
                flex-direction: column;
            }

            .record-id {
                margin-left: 0;
            }

        }

        @media (max-width: 700px) {

            .main-content {
                padding: 20px;
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

            .record-bar {
                flex-wrap: wrap;
                align-items: flex-start;
            }

            .status-options {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .button-area {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .update-btn,
            .cancel-btn {
                width: 100%;
            }

            .header-actions {
                width: 100%;
            }

            .header-btn {
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

            <a href="view.php?id=<?php echo $id; ?>">
                Lead Details
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                Edit Lead
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Edit Lead
                </h1>

                <p>
                    Update the lead information and sales tracking details.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="view.php?id=<?php echo $id; ?>"
                    class="header-btn view-btn"
                >
                    👁 View Lead
                </a>

                <a
                    href="index.php"
                    class="header-btn list-btn"
                >
                    ← Lead List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Record Bar
        ========================================================== -->

        <div class="record-bar">

            <div
                class="record-avatar"
                id="recordAvatar"
            >
                <?php echo htmlspecialchars($initial); ?>
            </div>


            <div class="record-info">

                <strong id="recordName">
                    <?php echo htmlspecialchars($fullLeadName); ?>
                </strong>

                <span>
                    Editing lead information
                </span>

            </div>


            <div class="record-id">
                Lead #<?php echo $id; ?>
            </div>

        </div>


        <!-- =========================================================
             Main Content
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 LEFT FORM
            ====================================================== -->

            <div class="form-card">

                <div class="card-header">

                    <h2>
                        Lead Information
                    </h2>

                    <p>
                        Update the lead details below and save your changes.
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
                             CONTACT
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
                             STATUS
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
                                href="view.php?id=<?php echo $id; ?>"
                                class="cancel-btn"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="update-btn"
                            >
                                Update Lead
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
                            Live preview of your changes.
                        </p>

                    </div>


                    <div class="preview-profile">

                        <div
                            class="preview-avatar"
                            id="previewAvatar"
                        >
                            <?php echo htmlspecialchars($initial); ?>
                        </div>


                        <h3
                            class="preview-name"
                            id="previewName"
                        >
                            <?php echo htmlspecialchars($fullLeadName); ?>
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
                            class="preview-status <?php echo $statusClass; ?>"
                            id="previewStatus"
                        >
                            <?php echo htmlspecialchars(
                                ucfirst($status)
                            ); ?>
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
                                    <?php echo htmlspecialchars(
                                        $selectedCompanyName
                                    ); ?>
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
                                    <?php echo htmlspecialchars(
                                        $selectedContactName
                                    ); ?>
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


                        <!-- Assigned User -->

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
                                    <?php echo htmlspecialchars(
                                        $selectedUserName
                                    ); ?>
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


                <!-- Editing Guide -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Lead Editing Guide
                        </h3>

                        <p>
                            Keep the sales record accurate.
                        </p>

                    </div>


                    <ul class="guide-list">

                        <li>

                            <span class="guide-number">
                                1
                            </span>

                            <span>
                                Verify the lead name and linked company/contact.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                2
                            </span>

                            <span>
                                Update the lead source when the acquisition source changes.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                3
                            </span>

                            <span>
                                Keep status and estimated lead value current.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                4
                            </span>

                            <span>
                                Make sure the correct sales team member is assigned.
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

                        Review the lead status, value, owner, and notes before clicking Update Lead.

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

    const recordAvatar =
        document.getElementById("recordAvatar");

    const recordName =
        document.getElementById("recordName");


    function updatePreview() {

        /* ------------------------------------------------------------
           Lead Name
        ------------------------------------------------------------ */

        const leadName =
            leadNameInput.value.trim();

        previewName.textContent =
            leadName || "Lead";

        recordName.textContent =
            leadName || "Lead";


        /* ------------------------------------------------------------
           Avatar
        ------------------------------------------------------------ */

        const initial =
            leadName
                ? leadName.charAt(0).toUpperCase()
                : "L";

        previewAvatar.textContent =
            initial;

        recordAvatar.textContent =
            initial;


        /* ------------------------------------------------------------
           Source
        ------------------------------------------------------------ */

        const source =
            sourceInput.value;

        previewSubtitle.textContent =
            source || "Lead source";


        /* ------------------------------------------------------------
           Company
        ------------------------------------------------------------ */

        if (companyInput.value) {

            const companyOption =
                companyInput.options[
                    companyInput.selectedIndex
                ];

            previewCompany.textContent =
                companyOption.text.trim();

        } else {

            previewCompany.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Contact
        ------------------------------------------------------------ */

        if (contactInput.value) {

            const contactOption =
                contactInput.options[
                    contactInput.selectedIndex
                ];

            previewContact.textContent =
                contactOption.text.trim();

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

            const assignedOption =
                assignedInput.options[
                    assignedInput.selectedIndex
                ];

            previewAssigned.textContent =
                assignedOption.text.trim();

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