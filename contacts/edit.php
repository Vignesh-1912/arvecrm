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
| Check Contact ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Contact
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM contacts
    WHERE id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$contact = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Contact Not Found
|--------------------------------------------------------------------------
*/

if (!$contact) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$company_id = $contact["company_id"] ?? "";
$first_name = $contact["first_name"] ?? "";
$last_name = $contact["last_name"] ?? "";
$email = $contact["email"] ?? "";
$phone = $contact["phone"] ?? "";
$job_title = $contact["job_title"] ?? "";
$address = $contact["address"] ?? "";
$city = $contact["city"] ?? "";
$state = $contact["state"] ?? "";
$country = $contact["country"] ?? "";
$postal_code = $contact["postal_code"] ?? "";
$status = $contact["status"] ?? "active";


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
| Update Contact
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $company_id = trim($_POST["company_id"] ?? "");
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $job_title = trim($_POST["job_title"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $state = trim($_POST["state"] ?? "");
    $country = trim($_POST["country"] ?? "");
    $postal_code = trim($_POST["postal_code"] ?? "");
    $status = trim($_POST["status"] ?? "active");


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (empty($first_name)) {

        $error = "First name is required.";

    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (!in_array($status, ["active", "inactive"], true)) {

        $error = "Invalid contact status.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Update Contact
            |--------------------------------------------------------------------------
            */

            $sql = "
                UPDATE contacts SET

                    company_id = :company_id,
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    job_title = :job_title,
                    address = :address,
                    city = :city,
                    state = :state,
                    country = :country,
                    postal_code = :postal_code,
                    status = :status

                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":company_id" => !empty($company_id)
                    ? $company_id
                    : null,

                ":first_name" => $first_name,

                ":last_name" => $last_name,

                ":email" => $email,

                ":phone" => $phone,

                ":job_title" => $job_title,

                ":address" => $address,

                ":city" => $city,

                ":state" => $state,

                ":country" => $country,

                ":postal_code" => $postal_code,

                ":status" => $status,

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

            $error = "Unable to update contact: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Preview Values
|--------------------------------------------------------------------------
*/

$full_name = trim($first_name . " " . $last_name);

if ($full_name === "") {
    $full_name = "Contact";
}

$initial = strtoupper(
    substr(
        $first_name !== "" ? $first_name : $full_name,
        0,
        1
    )
);

$selectedCompanyName = "Not selected";

foreach ($companies as $company) {

    if ((string) $company["id"] === (string) $company_id) {

        $selectedCompanyName = $company["company_name"];

        break;
    }
}

$locationParts = array_filter([
    $city,
    $state,
    $country
]);

$location = !empty($locationParts)
    ? implode(", ", $locationParts)
    : "Not provided";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Contact - CRM</title>

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
            font-weight: 700;
            color: #172554;
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
           Current Record Bar
        ------------------------------------------------------------ */

        .record-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 22px;
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
            font-size: 18px;
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
           Layout
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
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
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .error-icon {
            font-size: 16px;
            line-height: 1;
            margin-top: 2px;
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
            font-size: 15px;
            color: #1e293b;
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
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .required {
            color: #dc2626;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            padding: 11px 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        input,
        select {
            height: 43px;
        }

        textarea {
            min-height: 105px;
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
            display: flex;
            gap: 10px;
        }

        .status-option {
            position: relative;
            flex: 1;
        }

        .status-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-label {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 43px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-label:hover {
            border-color: #93c5fd;
            background: #f8fbff;
        }

        .status-option input:checked + .active-option {
            color: #15803d;
            border-color: #86efac;
            background: #f0fdf4;
        }

        .status-option input:checked + .inactive-option {
            color: #b45309;
            border-color: #fcd34d;
            background: #fffbeb;
        }

        /* ------------------------------------------------------------
           Action Buttons
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
            margin-bottom: 16px;
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
            padding: 6px 0 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .preview-avatar {
            width: 72px;
            height: 72px;
            margin: 0 auto 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #2563eb;
            font-size: 26px;
            font-weight: 700;
        }

        .preview-name {
            margin: 0;
            color: #172554;
            font-size: 17px;
            font-weight: 700;
        }

        .preview-job {
            margin-top: 5px;
            color: #64748b;
            font-size: 13px;
        }

        .preview-status {
            display: inline-flex;
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .preview-status.active {
            background: #dcfce7;
            color: #15803d;
        }

        .preview-status.inactive {
            background: #fef3c7;
            color: #92400e;
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

        .preview-item-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .preview-item-text {
            min-width: 0;
        }

        .preview-item-label {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .preview-item-value {
            display: block;
            color: #334155;
            font-size: 13px;
            word-break: break-word;
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
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            padding: 13px;
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
            color: #e2e8f0;
            border-color: #334155;
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

        html.dark-mode .status-option input:checked + .active-option {
            background: #052e16;
            border-color: #166534;
            color: #86efac;
        }

        html.dark-mode .status-option input:checked + .inactive-option {
            background: #451a03;
            border-color: #92400e;
            color: #fcd34d;
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

        html.dark-mode .preview-details {
            color: #cbd5e1;
        }

        html.dark-mode .preview-item-icon {
            background: #1e293b;
        }

        html.dark-mode .preview-item-value {
            color: #cbd5e1;
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

        @media (max-width: 1050px) {

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
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .button-area {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .update-btn,
            .cancel-btn {
                width: 100%;
            }

            .status-options {
                flex-direction: column;
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
                Contacts
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <a href="view.php?id=<?php echo $id; ?>">
                Contact Details
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                Edit Contact
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Edit Contact
                </h1>

                <p>
                    Update the contact information and account details.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="view.php?id=<?php echo $id; ?>"
                    class="header-btn view-btn"
                >
                    👁 View Contact
                </a>

                <a
                    href="index.php"
                    class="header-btn list-btn"
                >
                    ← Contact List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Current Record
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
                    <?php echo htmlspecialchars($full_name); ?>
                </strong>

                <span>
                    Editing contact information
                </span>

            </div>


            <div class="record-id">

                Contact #<?php echo $id; ?>

            </div>

        </div>


        <!-- =========================================================
             Main Content
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 FORM
            ====================================================== -->

            <div class="form-card">

                <div class="card-header">

                    <h2>
                        Contact Information
                    </h2>

                    <p>
                        Update the information below and save your changes.
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
                             BASIC INFORMATION
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    👤
                                </div>

                                <h3>
                                    Basic Information
                                </h3>

                            </div>


                            <!-- Company -->

                            <div class="form-row">

                                <div class="form-group full">

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

                            </div>


                            <!-- First / Last Name -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="first_name">

                                        First Name

                                        <span class="required">*</span>

                                    </label>

                                    <input
                                        type="text"
                                        name="first_name"
                                        id="first_name"
                                        placeholder="Enter first name"
                                        value="<?php echo htmlspecialchars($first_name); ?>"
                                        required
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="last_name">
                                        Last Name
                                    </label>

                                    <input
                                        type="text"
                                        name="last_name"
                                        id="last_name"
                                        placeholder="Enter last name"
                                        value="<?php echo htmlspecialchars($last_name); ?>"
                                    >

                                </div>

                            </div>


                            <!-- Job / Status -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="job_title">
                                        Job Title
                                    </label>

                                    <input
                                        type="text"
                                        name="job_title"
                                        id="job_title"
                                        placeholder="e.g. Sales Manager"
                                        value="<?php echo htmlspecialchars($job_title); ?>"
                                    >

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
                                                id="status_active"
                                                value="active"
                                                <?php echo (
                                                    $status === "active"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_active"
                                                class="status-label active-option"
                                            >
                                                Active
                                            </label>

                                        </div>


                                        <div class="status-option">

                                            <input
                                                type="radio"
                                                name="status"
                                                id="status_inactive"
                                                value="inactive"
                                                <?php echo (
                                                    $status === "inactive"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="status_inactive"
                                                class="status-label inactive-option"
                                            >
                                                Inactive
                                            </label>

                                        </div>

                                    </div>

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
                             ADDRESS INFORMATION
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    📍
                                </div>

                                <h3>
                                    Address Information
                                </h3>

                            </div>


                            <!-- Address -->

                            <div class="form-row">

                                <div class="form-group full">

                                    <label for="address">
                                        Address
                                    </label>

                                    <textarea
                                        name="address"
                                        id="address"
                                        placeholder="Enter street address"
                                    ><?php echo htmlspecialchars($address); ?></textarea>

                                </div>

                            </div>


                            <!-- City / State -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="city">
                                        City
                                    </label>

                                    <input
                                        type="text"
                                        name="city"
                                        id="city"
                                        placeholder="Enter city"
                                        value="<?php echo htmlspecialchars($city); ?>"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="state">
                                        State
                                    </label>

                                    <input
                                        type="text"
                                        name="state"
                                        id="state"
                                        placeholder="Enter state"
                                        value="<?php echo htmlspecialchars($state); ?>"
                                    >

                                </div>

                            </div>


                            <!-- Country / Postal -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="country">
                                        Country
                                    </label>

                                    <input
                                        type="text"
                                        name="country"
                                        id="country"
                                        placeholder="Enter country"
                                        value="<?php echo htmlspecialchars($country); ?>"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="postal_code">
                                        Postal Code
                                    </label>

                                    <input
                                        type="text"
                                        name="postal_code"
                                        id="postal_code"
                                        placeholder="Enter postal code"
                                        value="<?php echo htmlspecialchars($postal_code); ?>"
                                    >

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
                                Update Contact
                            </button>

                        </div>


                    </form>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDE
            ====================================================== -->

            <div>


                <!-- Contact Preview -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Contact Preview
                        </h3>

                        <p>
                            Live preview of the updated contact information.
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
                            <?php echo htmlspecialchars($full_name); ?>
                        </h3>


                        <div
                            class="preview-job"
                            id="previewJob"
                        >

                            <?php
                            echo $job_title !== ""
                                ? htmlspecialchars($job_title)
                                : "Job Title";
                            ?>

                        </div>


                        <span
                            class="preview-status <?php echo (
                                $status === "inactive"
                            ) ? "inactive" : "active"; ?>"
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

                            <div class="preview-item-icon">
                                🏢
                            </div>

                            <div class="preview-item-text">

                                <span class="preview-item-label">
                                    Company
                                </span>

                                <span
                                    class="preview-item-value"
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


                        <!-- Email -->

                        <div class="preview-item">

                            <div class="preview-item-icon">
                                ✉
                            </div>

                            <div class="preview-item-text">

                                <span class="preview-item-label">
                                    Email
                                </span>

                                <span
                                    class="preview-item-value"
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

                            <div class="preview-item-icon">
                                ☎
                            </div>

                            <div class="preview-item-text">

                                <span class="preview-item-label">
                                    Phone
                                </span>

                                <span
                                    class="preview-item-value"
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


                        <!-- Location -->

                        <div class="preview-item">

                            <div class="preview-item-icon">
                                📍
                            </div>

                            <div class="preview-item-text">

                                <span class="preview-item-label">
                                    Location
                                </span>

                                <span
                                    class="preview-item-value"
                                    id="previewLocation"
                                >
                                    <?php echo htmlspecialchars($location); ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Editing Guide -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Contact Editing Guide
                        </h3>

                        <p>
                            Keep the record complete and accurate.
                        </p>

                    </div>


                    <ul class="guide-list">

                        <li>

                            <span class="guide-number">
                                1
                            </span>

                            <span>
                                Verify the contact's first and last name before saving.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                2
                            </span>

                            <span>
                                Keep the company association current.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                3
                            </span>

                            <span>
                                Update email, phone, and job title when changes occur.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                4
                            </span>

                            <span>
                                Change the status when the contact becomes inactive.
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

                        Review the contact information before clicking Update Contact to keep your CRM data accurate.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const firstNameInput =
        document.getElementById("first_name");

    const lastNameInput =
        document.getElementById("last_name");

    const jobTitleInput =
        document.getElementById("job_title");

    const emailInput =
        document.getElementById("email");

    const phoneInput =
        document.getElementById("phone");

    const companyInput =
        document.getElementById("company_id");

    const cityInput =
        document.getElementById("city");

    const stateInput =
        document.getElementById("state");

    const countryInput =
        document.getElementById("country");

    const previewAvatar =
        document.getElementById("previewAvatar");

    const previewName =
        document.getElementById("previewName");

    const previewJob =
        document.getElementById("previewJob");

    const previewEmail =
        document.getElementById("previewEmail");

    const previewPhone =
        document.getElementById("previewPhone");

    const previewCompany =
        document.getElementById("previewCompany");

    const previewLocation =
        document.getElementById("previewLocation");

    const previewStatus =
        document.getElementById("previewStatus");

    const recordAvatar =
        document.getElementById("recordAvatar");

    const recordName =
        document.getElementById("recordName");

    const statusInputs =
        document.querySelectorAll(
            'input[name="status"]'
        );


    function updatePreview() {

        const firstName =
            firstNameInput.value.trim();

        const lastName =
            lastNameInput.value.trim();

        const jobTitle =
            jobTitleInput.value.trim();

        const email =
            emailInput.value.trim();

        const phone =
            phoneInput.value.trim();


        /* ------------------------------------------------------------
           Full Name
        ------------------------------------------------------------ */

        const fullName = [
            firstName,
            lastName
        ]
            .filter(Boolean)
            .join(" ");


        previewName.textContent =
            fullName || "Contact";

        recordName.textContent =
            fullName || "Contact";


        /* ------------------------------------------------------------
           Initial
        ------------------------------------------------------------ */

        const initial = firstName
            ? firstName.charAt(0).toUpperCase()
            : "C";

        previewAvatar.textContent =
            initial;

        recordAvatar.textContent =
            initial;


        /* ------------------------------------------------------------
           Job
        ------------------------------------------------------------ */

        previewJob.textContent =
            jobTitle || "Job Title";


        /* ------------------------------------------------------------
           Email
        ------------------------------------------------------------ */

        previewEmail.textContent =
            email || "Not provided";


        /* ------------------------------------------------------------
           Phone
        ------------------------------------------------------------ */

        previewPhone.textContent =
            phone || "Not provided";


        /* ------------------------------------------------------------
           Company
        ------------------------------------------------------------ */

        if (companyInput.value) {

            const selectedOption =
                companyInput.options[
                    companyInput.selectedIndex
                ];

            previewCompany.textContent =
                selectedOption.text.trim();

        } else {

            previewCompany.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Location
        ------------------------------------------------------------ */

        const locationParts = [
            cityInput.value.trim(),
            stateInput.value.trim(),
            countryInput.value.trim()
        ]
            .filter(Boolean);

        previewLocation.textContent =
            locationParts.length
                ? locationParts.join(", ")
                : "Not provided";


        /* ------------------------------------------------------------
           Status
        ------------------------------------------------------------ */

        let selectedStatus =
            "active";

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
            "active",
            "inactive"
        );

        previewStatus.classList.add(
            selectedStatus
        );

    }


    /* ------------------------------------------------------------
       Event Listeners
    ------------------------------------------------------------ */

    firstNameInput.addEventListener(
        "input",
        updatePreview
    );

    lastNameInput.addEventListener(
        "input",
        updatePreview
    );

    jobTitleInput.addEventListener(
        "input",
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

    companyInput.addEventListener(
        "change",
        updatePreview
    );

    cityInput.addEventListener(
        "input",
        updatePreview
    );

    stateInput.addEventListener(
        "input",
        updatePreview
    );

    countryInput.addEventListener(
        "input",
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