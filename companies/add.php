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
| Form Values
|--------------------------------------------------------------------------
*/

$company_name = "";
$industry = "";
$phone = "";
$email = "";
$website = "";
$address = "";
$city = "";
$state = "";
$country = "";
$postal_code = "";


/*
|--------------------------------------------------------------------------
| Add Company
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $company_name = trim(
        $_POST["company_name"] ?? ""
    );

    $industry = trim(
        $_POST["industry"] ?? ""
    );

    $phone = trim(
        $_POST["phone"] ?? ""
    );

    $email = trim(
        $_POST["email"] ?? ""
    );

    $website = trim(
        $_POST["website"] ?? ""
    );

    $address = trim(
        $_POST["address"] ?? ""
    );

    $city = trim(
        $_POST["city"] ?? ""
    );

    $state = trim(
        $_POST["state"] ?? ""
    );

    $country = trim(
        $_POST["country"] ?? ""
    );

    $postal_code = trim(
        $_POST["postal_code"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($company_name === "") {

        $error = "Company name is required.";

    } elseif (
        $email !== "" &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = "Please enter a valid email address.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert Company
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO companies
                (
                    company_name,
                    industry,
                    phone,
                    email,
                    website,
                    address,
                    city,
                    state,
                    country,
                    postal_code,
                    created_by
                )
                VALUES
                (
                    :company_name,
                    :industry,
                    :phone,
                    :email,
                    :website,
                    :address,
                    :city,
                    :state,
                    :country,
                    :postal_code,
                    :created_by
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":company_name" => $company_name,
                ":industry" => $industry,
                ":phone" => $phone,
                ":email" => $email,
                ":website" => $website,
                ":address" => $address,
                ":city" => $city,
                ":state" => $state,
                ":country" => $country,
                ":postal_code" => $postal_code,
                ":created_by" => $_SESSION["user_id"]
            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error =
                "Unable to add company. Please try again.";

        }

    }

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
        Add Company - CRM
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
        textarea {
            font-family: inherit;
        }

        .company-page {
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
            font-size: 23px;
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

        .back-button {
            height: 40px;
            padding: 0 15px;
            border: 1px solid #dbe1ea;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #ffffff;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            transition: 0.2s ease;
            flex-shrink: 0;
        }

        .back-button:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }


        /* =========================================================
           MAIN LAYOUT
        ========================================================= */

        .main-layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                325px;
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

        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 13px;
            border-radius: 9px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        .error-icon {
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }


        /* =========================================================
           FORM SECTIONS
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

        .textarea-icon {
            position: absolute;
            left: 13px;
            top: 14px;
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }

        .form-control,
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

        .form-control {
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
            font-size: 13px;
            line-height: 1.6;
            resize: vertical;
        }

        .textarea-with-icon {
            padding-left: 38px;
        }

        .form-control::placeholder,
        .form-textarea::placeholder {
            color: #b0b8c4;
        }

        .form-control:hover,
        .form-textarea:hover {
            border-color: #c4ceda;
        }

        .form-control:focus,
        .form-textarea:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }


        /* =========================================================
           CONTACT INFORMATION
        ========================================================= */

        .input-prefix {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 12px;
        }

        .website-preview {
            margin-top: 5px;
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
            align-items: center;
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
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .save-button {
            min-width: 145px;
            border: none;
            background: #2563eb;
            color: #ffffff;
            box-shadow:
                0 4px 10px
                rgba(37, 99, 235, 0.18);
        }

        .save-button:hover {
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
           COMPANY PREVIEW
        ========================================================= */

        .company-preview {
            display: flex;
            align-items: center;
            gap: 11px;
            padding-bottom: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eef2f7;
        }

        .company-avatar {
            width: 46px;
            height: 46px;
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
            font-size: 17px;
            font-weight: 700;
            flex: 0 0 46px;
        }

        .company-preview-name {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            word-break: break-word;
        }

        .company-preview-industry {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
        }


        /* =========================================================
           SIDE DETAIL
        ========================================================= */

        .side-detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            word-break: break-word;
        }


        /* =========================================================
           INFO ITEMS
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
            display: block;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }


        /* =========================================================
           TIP
        ========================================================= */

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
        html.dark-mode .form-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
        }

        html.dark-mode .page-header h1,
        html.dark-mode .form-card-header h2,
        html.dark-mode .section-heading h3,
        html.dark-mode .side-card-header h3,
        html.dark-mode .company-preview-name,
        html.dark-mode .side-value,
        html.dark-mode .info-text strong {
            color: #f8fafc;
        }

        html.dark-mode .page-header p,
        html.dark-mode .form-card-header p,
        html.dark-mode .section-heading span,
        html.dark-mode .form-label,
        html.dark-mode .form-hint,
        html.dark-mode .side-card-header p,
        html.dark-mode .company-preview-industry,
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

        html.dark-mode .back-button,
        html.dark-mode .cancel-button,
        html.dark-mode .form-control,
        html.dark-mode .form-textarea {
            background: #0f172a;
            border-color: #334155;
            color: #e5e7eb;
        }

        html.dark-mode .back-button:hover,
        html.dark-mode .cancel-button:hover {
            background: #1e293b;
            border-color: #475569;
            color: #93c5fd;
        }

        html.dark-mode .form-control::placeholder,
        html.dark-mode .form-textarea::placeholder {
            color: #64748b;
        }

        html.dark-mode .form-card-header,
        html.dark-mode .side-card-header,
        html.dark-mode .form-footer,
        html.dark-mode .company-preview,
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

            .main-layout {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .back-button {
                width: 100%;
            }

        }

        @media (max-width: 700px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

        }

        @media (max-width: 600px) {

            .main-content {
                padding: 18px !important;
            }

            .form-footer {
                flex-direction: column-reverse;
            }

            .form-footer .button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="company-page">


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
                Companies
            </a>

            <span class="breadcrumb-arrow">
                /
            </span>

            <span class="breadcrumb-current">
                Add Company
            </span>

        </div>


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="page-header-left">

                <div class="page-header-icon">
                    🏢
                </div>

                <div>

                    <h1>
                        Add Company
                    </h1>

                    <p>
                        Create a new company profile in your CRM.
                    </p>

                </div>

            </div>

            <a
                href="index.php"
                class="back-button"
            >
                ← Back to Companies
            </a>

        </div>


        <!-- =====================================================
             MAIN LAYOUT
        ====================================================== -->

        <div class="main-layout">


            <!-- =================================================
                 FORM
            ================================================== -->

            <div class="form-card">

                <div class="form-card-header">

                    <h2>
                        Company Information
                    </h2>

                    <p>
                        Enter the company details below.
                    </p>

                </div>


                <div class="form-card-body">


                    <?php if (!empty($error)): ?>

                        <div class="error-box">

                            <div class="error-icon">
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
                                    Company profile
                                </span>

                            </div>


                            <div class="form-grid">


                                <!-- Company Name -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="company_name"
                                    >

                                        Company Name

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            🏢
                                        </span>

                                        <input
                                            type="text"
                                            id="company_name"
                                            name="company_name"
                                            class="form-control with-icon"
                                            placeholder="Enter company name"
                                            value="<?= e($company_name) ?>"
                                            maxlength="150"
                                            required
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Enter the official company or organization name.
                                    </div>

                                </div>


                                <!-- Industry -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="industry"
                                    >
                                        Industry
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            ◈
                                        </span>

                                        <input
                                            type="text"
                                            id="industry"
                                            name="industry"
                                            class="form-control with-icon"
                                            placeholder="Example: Logistics"
                                            value="<?= e($industry) ?>"
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Specify the company's business industry.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             CONTACT INFORMATION
                        ====================================== -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-number">
                                    2
                                </div>

                                <h3>
                                    Contact Information
                                </h3>

                                <span>
                                    Communication details
                                </span>

                            </div>


                            <div class="form-grid">


                                <!-- Phone -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="phone"
                                    >
                                        Phone Number
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            ☎
                                        </span>

                                        <input
                                            type="tel"
                                            id="phone"
                                            name="phone"
                                            class="form-control with-icon"
                                            placeholder="Enter phone number"
                                            value="<?= e($phone) ?>"
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Main company contact number.
                                    </div>

                                </div>


                                <!-- Email -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="email"
                                    >
                                        Email Address
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            @
                                        </span>

                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            class="form-control with-icon"
                                            placeholder="company@example.com"
                                            value="<?= e($email) ?>"
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Main company email address.
                                    </div>

                                </div>


                                <!-- Website -->

                                <div class="form-group full-width">

                                    <label
                                        class="form-label"
                                        for="website"
                                    >
                                        Website
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            🌐
                                        </span>

                                        <input
                                            type="url"
                                            id="website"
                                            name="website"
                                            class="form-control with-icon"
                                            placeholder="https://example.com"
                                            value="<?= e($website) ?>"
                                        >

                                    </div>

                                    <div class="form-hint">
                                        Enter the company's official website URL.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                             ADDRESS
                        ====================================== -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-number">
                                    3
                                </div>

                                <h3>
                                    Business Address
                                </h3>

                                <span>
                                    Location details
                                </span>

                            </div>


                            <div class="form-grid">


                                <!-- Address -->

                                <div class="form-group full-width">

                                    <label
                                        class="form-label"
                                        for="address"
                                    >
                                        Address
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="textarea-icon">
                                            📍
                                        </span>

                                        <textarea
                                            id="address"
                                            name="address"
                                            class="form-textarea textarea-with-icon"
                                            placeholder="Enter company street address..."
                                        ><?= e($address) ?></textarea>

                                    </div>

                                    <div class="form-hint">
                                        Enter the primary business address.
                                    </div>

                                </div>


                                <!-- City -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="city"
                                    >
                                        City
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            ◆
                                        </span>

                                        <input
                                            type="text"
                                            id="city"
                                            name="city"
                                            class="form-control with-icon"
                                            placeholder="Enter city"
                                            value="<?= e($city) ?>"
                                        >

                                    </div>

                                </div>


                                <!-- State -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="state"
                                    >
                                        State
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            ◆
                                        </span>

                                        <input
                                            type="text"
                                            id="state"
                                            name="state"
                                            class="form-control with-icon"
                                            placeholder="Enter state"
                                            value="<?= e($state) ?>"
                                        >

                                    </div>

                                </div>


                                <!-- Country -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="country"
                                    >
                                        Country
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            🌍
                                        </span>

                                        <input
                                            type="text"
                                            id="country"
                                            name="country"
                                            class="form-control with-icon"
                                            placeholder="Enter country"
                                            value="<?= e($country) ?>"
                                        >

                                    </div>

                                </div>


                                <!-- Postal Code -->

                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="postal_code"
                                    >
                                        Postal Code
                                    </label>

                                    <div class="input-wrapper">

                                        <span class="input-icon">
                                            #
                                        </span>

                                        <input
                                            type="text"
                                            id="postal_code"
                                            name="postal_code"
                                            class="form-control with-icon"
                                            placeholder="Enter postal code"
                                            value="<?= e($postal_code) ?>"
                                        >

                                    </div>

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
                                class="button save-button"
                            >
                                ✓ Save Company
                            </button>

                        </div>


                    </form>

                </div>

            </div>


            <!-- =================================================
                 RIGHT SIDE
            ================================================== -->

            <div>


                <!-- Company Preview -->

                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Company Preview
                        </h3>

                        <p>
                            Your company details will appear here after saving.
                        </p>

                    </div>


                    <div class="side-card-body">

                        <div class="company-preview">

                            <div
                                class="company-avatar"
                                id="companyAvatar"
                            >
                                C
                            </div>

                            <div>

                                <div
                                    class="company-preview-name"
                                    id="companyPreviewName"
                                >
                                    Company Name
                                </div>

                                <div
                                    class="company-preview-industry"
                                    id="companyPreviewIndustry"
                                >
                                    Industry not specified
                                </div>

                            </div>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Phone
                            </span>

                            <span
                                class="side-value"
                                id="previewPhone"
                            >
                                Not provided
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Email
                            </span>

                            <span
                                class="side-value"
                                id="previewEmail"
                            >
                                Not provided
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Website
                            </span>

                            <span
                                class="side-value"
                                id="previewWebsite"
                            >
                                Not provided
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                City
                            </span>

                            <span
                                class="side-value"
                                id="previewCity"
                            >
                                Not provided
                            </span>

                        </div>


                        <div class="side-detail">

                            <span class="side-label">
                                Country
                            </span>

                            <span
                                class="side-value"
                                id="previewCountry"
                            >
                                Not provided
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Information -->

                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Company Setup
                        </h3>

                        <p>
                            Helpful information when creating a company.
                        </p>

                    </div>


                    <div class="side-card-body">


                        <div class="info-item">

                            <div class="info-icon">
                                🏢
                            </div>

                            <div class="info-text">

                                <strong>
                                    Company Name
                                </strong>

                                <span>
                                    Use the official business or organization name.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                ◈
                            </div>

                            <div class="info-text">

                                <strong>
                                    Industry
                                </strong>

                                <span>
                                    Add the industry to make companies easier to identify and filter.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                @
                            </div>

                            <div class="info-text">

                                <strong>
                                    Contact Details
                                </strong>

                                <span>
                                    Add a valid phone number and email for customer communication.
                                </span>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-icon">
                                📍
                            </div>

                            <div class="info-text">

                                <strong>
                                    Business Address
                                </strong>

                                <span>
                                    Add the company's main office or business location.
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
                        Complete the company name and key contact
                        information first. You can update the remaining
                        details later from the Edit Company page.
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Live Company Preview
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const companyName =
            document.getElementById(
                "company_name"
            );

        const industry =
            document.getElementById(
                "industry"
            );

        const phone =
            document.getElementById(
                "phone"
            );

        const email =
            document.getElementById(
                "email"
            );

        const website =
            document.getElementById(
                "website"
            );

        const city =
            document.getElementById(
                "city"
            );

        const country =
            document.getElementById(
                "country"
            );

        const previewName =
            document.getElementById(
                "companyPreviewName"
            );

        const previewIndustry =
            document.getElementById(
                "companyPreviewIndustry"
            );

        const previewPhone =
            document.getElementById(
                "previewPhone"
            );

        const previewEmail =
            document.getElementById(
                "previewEmail"
            );

        const previewWebsite =
            document.getElementById(
                "previewWebsite"
            );

        const previewCity =
            document.getElementById(
                "previewCity"
            );

        const previewCountry =
            document.getElementById(
                "previewCountry"
            );

        const avatar =
            document.getElementById(
                "companyAvatar"
            );


        function updatePreview() {

            const name =
                companyName.value.trim();

            const industryValue =
                industry.value.trim();

            const phoneValue =
                phone.value.trim();

            const emailValue =
                email.value.trim();

            const websiteValue =
                website.value.trim();

            const cityValue =
                city.value.trim();

            const countryValue =
                country.value.trim();


            /*
            |--------------------------------------------------------------------------
            | Name
            |--------------------------------------------------------------------------
            */

            previewName.textContent =
                name || "Company Name";


            /*
            |--------------------------------------------------------------------------
            | Avatar
            |--------------------------------------------------------------------------
            */

            avatar.textContent =
                name
                    ? name
                        .charAt(0)
                        .toUpperCase()
                    : "C";


            /*
            |--------------------------------------------------------------------------
            | Industry
            |--------------------------------------------------------------------------
            */

            previewIndustry.textContent =
                industryValue ||
                "Industry not specified";


            /*
            |--------------------------------------------------------------------------
            | Phone
            |--------------------------------------------------------------------------
            */

            previewPhone.textContent =
                phoneValue ||
                "Not provided";


            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */

            previewEmail.textContent =
                emailValue ||
                "Not provided";


            /*
            |--------------------------------------------------------------------------
            | Website
            |--------------------------------------------------------------------------
            */

            previewWebsite.textContent =
                websiteValue ||
                "Not provided";


            /*
            |--------------------------------------------------------------------------
            | City
            |--------------------------------------------------------------------------
            */

            previewCity.textContent =
                cityValue ||
                "Not provided";


            /*
            |--------------------------------------------------------------------------
            | Country
            |--------------------------------------------------------------------------
            */

            previewCountry.textContent =
                countryValue ||
                "Not provided";

        }


        [
            companyName,
            industry,
            phone,
            email,
            website,
            city,
            country
        ].forEach(
            function (field) {

                if (field) {

                    field.addEventListener(
                        "input",
                        updatePreview
                    );

                }

            }
        );


        updatePreview();

    }
);

</script>

</body>

</html>