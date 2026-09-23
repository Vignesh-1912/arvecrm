<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Validate Company ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Company
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM companies
    WHERE id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Company Not Found
|--------------------------------------------------------------------------
*/

if (!$company) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Helper Functions
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
| Company Data
|--------------------------------------------------------------------------
*/

$companyId =
    (int) ($company["id"] ?? 0);

$companyName =
    trim(
        (string) (
            $company["company_name"] ?? "Company"
        )
    );

$industry =
    trim(
        (string) (
            $company["industry"] ?? ""
        )
    );

$phone =
    trim(
        (string) (
            $company["phone"] ?? ""
        )
    );

$email =
    trim(
        (string) (
            $company["email"] ?? ""
        )
    );

$website =
    trim(
        (string) (
            $company["website"] ?? ""
        )
    );

$address =
    trim(
        (string) (
            $company["address"] ?? ""
        )
    );

$city =
    trim(
        (string) (
            $company["city"] ?? ""
        )
    );

$state =
    trim(
        (string) (
            $company["state"] ?? ""
        )
    );

$country =
    trim(
        (string) (
            $company["country"] ?? ""
        )
    );

$postalCode =
    trim(
        (string) (
            $company["postal_code"] ?? ""
        )
    );

$createdAt =
    $company["created_at"] ?? "";

$updatedAt =
    $company["updated_at"] ?? "";

$createdBy =
    $company["created_by"] ?? "";


/*
|--------------------------------------------------------------------------
| Company Initial
|--------------------------------------------------------------------------
*/

$companyInitial =
    strtoupper(
        substr(
            trim($companyName),
            0,
            1
        )
    );

if ($companyInitial === "") {
    $companyInitial = "C";
}


/*
|--------------------------------------------------------------------------
| Company Location
|--------------------------------------------------------------------------
*/

$locationParts = [];

if ($city !== "") {
    $locationParts[] = $city;
}

if ($state !== "") {
    $locationParts[] = $state;
}

if ($country !== "") {
    $locationParts[] = $country;
}

$locationText =
    count($locationParts) > 0
    ? implode(", ", $locationParts)
    : "Location not available";

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
        View Company - CRM
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
           HERO
        ========================================================= */

        .company-hero {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .company-avatar {
            width: 64px;
            height: 64px;
            flex: 0 0 64px;
            border-radius: 15px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #60a5fa
            );
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: 700;
            box-shadow:
                0 7px 18px
                rgba(37, 99, 235, 0.20);
        }

        .hero-content {
            min-width: 0;
        }

        .hero-content h1 {
            margin: 0;
            font-size: 25px;
            font-weight: 700;
            color: #172033;
            word-break: break-word;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .hero-id {
            color: #64748b;
            font-size: 12px;
        }

        .hero-divider {
            color: #cbd5e1;
        }

        .industry-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 25px;
            padding: 0 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 10px;
            font-weight: 700;
        }


        /* =========================================================
           HERO ACTIONS
        ========================================================= */

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-shrink: 0;
        }

        .action-button {
            height: 41px;
            padding: 0 14px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .edit-button {
            background: #2563eb;
            color: #ffffff;
            box-shadow:
                0 4px 10px
                rgba(37, 99, 235, 0.18);
        }

        .edit-button:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .back-button {
            border: 1px solid #dbe1ea;
            background: #ffffff;
            color: #475569;
        }

        .back-button:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }


        /* =========================================================
           SUMMARY
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
            padding: 17px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 40px;
            font-size: 17px;
        }

        .summary-label {
            color: #64748b;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .summary-value {
            color: #172033;
            font-size: 14px;
            font-weight: 700;
            word-break: break-word;
        }


        /* =========================================================
           MAIN GRID
        ========================================================= */

        .main-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.55fr)
                minmax(300px, 0.85fr);
            gap: 18px;
        }


        /* =========================================================
           CARD
        ========================================================= */

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header {
            padding: 17px 20px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .card-header-left h2 {
            margin: 0;
            color: #172033;
            font-size: 15px;
        }

        .card-header-left p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 10px;
        }

        .card-body {
            padding: 20px;
        }


        /* =========================================================
           INFORMATION GRID
        ========================================================= */

        .information-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 13px;
        }

        .info-box {
            border: 1px solid #e8edf3;
            border-radius: 11px;
            padding: 14px;
            background: #fbfcfe;
            transition: 0.2s ease;
        }

        .info-box:hover {
            border-color: #bfdbfe;
            background: #f8fbff;
        }

        .info-box.full {
            grid-column: 1 / -1;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 10px;
            margin-bottom: 7px;
        }

        .info-label-icon {
            color: #94a3b8;
            font-size: 11px;
        }

        .info-value {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-value-muted {
            color: #94a3b8;
            font-weight: 400;
        }


        /* =========================================================
           CONTACT LINKS
        ========================================================= */

        .contact-link {
            color: #2563eb;
            transition: 0.2s ease;
        }

        .contact-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }


        /* =========================================================
           WEBSITE
        ========================================================= */

        .website-box {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            word-break: break-all;
        }

        .website-box:hover {
            text-decoration: underline;
        }


        /* =========================================================
           ADDRESS
        ========================================================= */

        .address-box {
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 11px;
            padding: 14px;
            color: #475569;
            font-size: 12px;
            line-height: 1.7;
            min-height: 85px;
        }

        .location-line {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 8px;
            color: #64748b;
            font-size: 11px;
        }


        /* =========================================================
           TIMELINE
        ========================================================= */

        .timeline {
            position: relative;
            padding-left: 28px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            top: 7px;
            bottom: 7px;
            left: 8px;
            width: 1px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 24px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -24px;
            top: 2px;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: #dbeafe;
            border: 4px solid #ffffff;
            box-shadow:
                0 0 0 1px #bfdbfe;
        }

        .timeline-title {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
        }

        .timeline-date {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }


        /* =========================================================
           ACCOUNT SUMMARY
        ========================================================= */

        .account-list {
            display: flex;
            flex-direction: column;
        }

        .account-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .account-row:first-child {
            padding-top: 0;
        }

        .account-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .account-label {
            color: #64748b;
            font-size: 10px;
        }

        .account-value {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }


        /* =========================================================
           QUICK ACTIONS
        ========================================================= */

        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .quick-action {
            min-height: 42px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #ffffff;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 0 12px;
            font-size: 11px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .quick-action:hover {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #2563eb;
        }

        .quick-action-icon {
            width: 27px;
            height: 27px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }


        /* =========================================================
           INFO BOX
        ========================================================= */

        .tip-box {
            margin-top: 18px;
            padding: 14px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 11px;
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

        html.dark-mode .company-hero,
        html.dark-mode .summary-card,
        html.dark-mode .card {
            background: #111827;
            border-color: #1f2937;
        }

        html.dark-mode .hero-content h1,
        html.dark-mode .summary-value,
        html.dark-mode .card-header-left h2,
        html.dark-mode .info-value,
        html.dark-mode .timeline-title,
        html.dark-mode .account-value {
            color: #f8fafc;
        }

        html.dark-mode .hero-id,
        html.dark-mode .summary-label,
        html.dark-mode .card-header-left p,
        html.dark-mode .info-label,
        html.dark-mode .timeline-date,
        html.dark-mode .account-label {
            color: #94a3b8;
        }

        html.dark-mode .breadcrumb,
        html.dark-mode .breadcrumb a {
            color: #64748b;
        }

        html.dark-mode .breadcrumb-current {
            color: #cbd5e1;
        }

        html.dark-mode .back-button {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .back-button:hover {
            background: #1e293b;
            border-color: #3b82f6;
            color: #93c5fd;
        }

        html.dark-mode .industry-badge {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .info-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .info-box:hover {
            background: #111d31;
            border-color: #475569;
        }

        html.dark-mode .address-box {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .location-line {
            color: #94a3b8;
        }

        html.dark-mode .card-header,
        html.dark-mode .account-row {
            border-color: #1f2937;
        }

        html.dark-mode .timeline::before {
            background: #334155;
        }

        html.dark-mode .timeline-dot {
            background: #172554;
            border-color: #111827;
        }

        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #172033;
            border-color: #475569;
            color: #93c5fd;
        }

        html.dark-mode .quick-action-icon {
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

        @media (max-width: 1000px) {

            .main-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 780px) {

            .company-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-actions {
                width: 100%;
            }

            .action-button {
                flex: 1;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .information-grid {
                grid-template-columns: 1fr;
            }

            .info-box.full {
                grid-column: auto;
            }

        }

        @media (max-width: 600px) {

            .main-content {
                padding: 18px !important;
            }

            .hero-left {
                align-items: flex-start;
            }

            .company-avatar {
                width: 54px;
                height: 54px;
                flex-basis: 54px;
                font-size: 21px;
            }

            .hero-content h1 {
                font-size: 20px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .action-button {
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
                View Company
            </span>

        </div>


        <!-- =====================================================
             COMPANY HERO
        ====================================================== -->

        <div class="company-hero">

            <div class="hero-left">

                <div class="company-avatar">
                    <?= e($companyInitial) ?>
                </div>

                <div class="hero-content">

                    <h1>
                        <?= e($companyName) ?>
                    </h1>

                    <div class="hero-meta">

                        <span class="hero-id">
                            Company ID:
                            #<?= e($companyId) ?>
                        </span>

                        <?php if ($industry !== ""): ?>

                            <span class="hero-divider">
                                •
                            </span>

                            <span class="industry-badge">
                                ◈
                                <?= e($industry) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <div class="hero-actions">

                <a
                    href="index.php"
                    class="action-button back-button"
                >
                    ← Company List
                </a>

                <a
                    href="edit.php?id=<?= e($companyId) ?>"
                    class="action-button edit-button"
                >
                    ✎ Edit Company
                </a>

            </div>

        </div>


        <!-- =====================================================
             SUMMARY CARDS
        ====================================================== -->

        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-icon">
                    🏢
                </div>

                <div>

                    <div class="summary-label">
                        Industry
                    </div>

                    <div class="summary-value">
                        <?= e(
                            $industry !== ""
                                ? $industry
                                : "Not specified"
                        ) ?>
                    </div>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-icon">
                    📍
                </div>

                <div>

                    <div class="summary-label">
                        Location
                    </div>

                    <div class="summary-value">
                        <?= e($locationText) ?>
                    </div>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-icon">
                    📅
                </div>

                <div>

                    <div class="summary-label">
                        Company Since
                    </div>

                    <div class="summary-value">
                        <?= e(
                            formatDateValue(
                                $createdAt
                            )
                        ) ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <div class="main-grid">


            <!-- =================================================
                 COMPANY INFORMATION
            ================================================== -->

            <div class="card">

                <div class="card-header">

                    <div class="card-header-left">

                        <h2>
                            Company Information
                        </h2>

                        <p>
                            Complete details for this company record
                        </p>

                    </div>

                </div>


                <div class="card-body">

                    <div class="information-grid">


                        <!-- Company ID -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ID
                                </span>

                                Company ID

                            </div>

                            <div class="info-value">
                                #<?= e($companyId) ?>
                            </div>

                        </div>


                        <!-- Company Name -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    🏢
                                </span>

                                Company Name

                            </div>

                            <div class="info-value">
                                <?= e($companyName) ?>
                            </div>

                        </div>


                        <!-- Industry -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ◈
                                </span>

                                Industry

                            </div>

                            <div class="info-value">

                                <?php if ($industry !== ""): ?>

                                    <?= e($industry) ?>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Phone -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ☎
                                </span>

                                Phone

                            </div>

                            <div class="info-value">

                                <?php if ($phone !== ""): ?>

                                    <a
                                        href="tel:<?= e($phone) ?>"
                                        class="contact-link"
                                    >
                                        <?= e($phone) ?>
                                    </a>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Email -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    @
                                </span>

                                Email

                            </div>

                            <div class="info-value">

                                <?php if ($email !== ""): ?>

                                    <a
                                        href="mailto:<?= e($email) ?>"
                                        class="contact-link"
                                    >
                                        <?= e($email) ?>
                                    </a>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Website -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    🌐
                                </span>

                                Website

                            </div>

                            <div class="info-value">

                                <?php if ($website !== ""): ?>

                                    <?php

                                    $websiteUrl =
                                        $website;

                                    if (
                                        !preg_match(
                                            "/^https?:\\/\\//i",
                                            $websiteUrl
                                        )
                                    ) {
                                        $websiteUrl =
                                            "https://" .
                                            $websiteUrl;
                                    }

                                    ?>

                                    <a
                                        href="<?= e($websiteUrl) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="website-box"
                                    >
                                        🌐
                                        <?= e($website) ?>
                                        ↗
                                    </a>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Address -->

                        <div class="info-box full">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    📍
                                </span>

                                Business Address

                            </div>

                            <div class="address-box">

                                <?php if ($address !== ""): ?>

                                    <?= nl2br(
                                        e($address)
                                    ) ?>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Address not provided.
                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    $locationText !==
                                    "Location not available"
                                ): ?>

                                    <div class="location-line">

                                        📍

                                        <?= e(
                                            $locationText
                                        ) ?>

                                        <?php if ($postalCode !== ""): ?>

                                            -
                                            <?= e(
                                                $postalCode
                                            ) ?>

                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- City -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ◆
                                </span>

                                City

                            </div>

                            <div class="info-value">

                                <?php if ($city !== ""): ?>

                                    <?= e($city) ?>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- State -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    ◆
                                </span>

                                State

                            </div>

                            <div class="info-value">

                                <?php if ($state !== ""): ?>

                                    <?= e($state) ?>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Country -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    🌍
                                </span>

                                Country

                            </div>

                            <div class="info-value">

                                <?php if ($country !== ""): ?>

                                    <?= e($country) ?>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Postal Code -->

                        <div class="info-box">

                            <div class="info-label">

                                <span class="info-label-icon">
                                    #
                                </span>

                                Postal Code

                            </div>

                            <div class="info-value">

                                <?php if ($postalCode !== ""): ?>

                                    <?= e($postalCode) ?>

                                <?php else: ?>

                                    <span class="info-value-muted">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 RIGHT SIDE
            ================================================== -->

            <div>


                <!-- Account Summary -->

                <div class="card">

                    <div class="card-header">

                        <div class="card-header-left">

                            <h2>
                                Account Summary
                            </h2>

                            <p>
                                Company record overview
                            </p>

                        </div>

                    </div>

                    <div class="card-body">

                        <div class="account-list">


                            <div class="account-row">

                                <span class="account-label">
                                    Company ID
                                </span>

                                <span class="account-value">
                                    #<?= e(
                                        $companyId
                                    ) ?>
                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    Industry
                                </span>

                                <span class="account-value">

                                    <?= e(
                                        $industry !== ""
                                            ? $industry
                                            : "Not specified"
                                    ) ?>

                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    City
                                </span>

                                <span class="account-value">

                                    <?= e(
                                        $city !== ""
                                            ? $city
                                            : "-"
                                    ) ?>

                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    State
                                </span>

                                <span class="account-value">

                                    <?= e(
                                        $state !== ""
                                            ? $state
                                            : "-"
                                    ) ?>

                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    Country
                                </span>

                                <span class="account-value">

                                    <?= e(
                                        $country !== ""
                                            ? $country
                                            : "-"
                                    ) ?>

                                </span>

                            </div>


                            <div class="account-row">

                                <span class="account-label">
                                    Postal Code
                                </span>

                                <span class="account-value">

                                    <?= e(
                                        $postalCode !== ""
                                            ? $postalCode
                                            : "-"
                                    ) ?>

                                </span>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- Record Timeline -->

                <div
                    class="card"
                    style="margin-top:18px;"
                >

                    <div class="card-header">

                        <div class="card-header-left">

                            <h2>
                                Record Timeline
                            </h2>

                            <p>
                                Company record activity
                            </p>

                        </div>

                    </div>

                    <div class="card-body">

                        <div class="timeline">


                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Company record created
                                </div>

                                <div class="timeline-date">

                                    <?= e(
                                        formatDateValue(
                                            $createdAt
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Company record last updated
                                </div>

                                <div class="timeline-date">

                                    <?= e(
                                        formatDateValue(
                                            $updatedAt
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Quick Actions -->

                <div
                    class="card"
                    style="margin-top:18px;"
                >

                    <div class="card-header">

                        <div class="card-header-left">

                            <h2>
                                Quick Actions
                            </h2>

                            <p>
                                Manage this company
                            </p>

                        </div>

                    </div>

                    <div class="card-body">

                        <div class="quick-actions">

                            <a
                                href="edit.php?id=<?= e($companyId) ?>"
                                class="quick-action"
                            >

                                <span class="quick-action-icon">
                                    ✎
                                </span>

                                Edit Company

                            </a>


                            <?php if ($phone !== ""): ?>

                                <a
                                    href="tel:<?= e($phone) ?>"
                                    class="quick-action"
                                >

                                    <span class="quick-action-icon">
                                        ☎
                                    </span>

                                    Call Company

                                </a>

                            <?php endif; ?>


                            <?php if ($email !== ""): ?>

                                <a
                                    href="mailto:<?= e($email) ?>"
                                    class="quick-action"
                                >

                                    <span class="quick-action-icon">
                                        @
                                    </span>

                                    Send Email

                                </a>

                            <?php endif; ?>


                            <?php if ($website !== ""): ?>

                                <a
                                    href="<?= e($websiteUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="quick-action"
                                >

                                    <span class="quick-action-icon">
                                        🌐
                                    </span>

                                    Open Website

                                </a>

                            <?php endif; ?>


                            <a
                                href="index.php"
                                class="quick-action"
                            >

                                <span class="quick-action-icon">
                                    ←
                                </span>

                                Back to Companies

                            </a>

                        </div>

                    </div>

                </div>


                <!-- Tip -->

                <div class="tip-box">

                    <div class="tip-title">
                        💡 Company Management
                    </div>

                    <div class="tip-text">

                        Keep the company's contact information
                        and business address up to date so your
                        CRM team can quickly reach the organization.

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>

</body>

</html>