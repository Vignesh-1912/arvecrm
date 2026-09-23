<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


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
    SELECT
        contacts.*,
        companies.company_name
    FROM contacts
    LEFT JOIN companies
        ON contacts.company_id = companies.id
    WHERE contacts.id = :id
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
| Helper Values
|--------------------------------------------------------------------------
*/

$first_name = trim($contact["first_name"] ?? "");
$last_name = trim($contact["last_name"] ?? "");

$full_name = trim($first_name . " " . $last_name);

if ($full_name === "") {
    $full_name = "Unnamed Contact";
}

$initial = strtoupper(substr($first_name !== "" ? $first_name : $full_name, 0, 1));

$company_name = trim($contact["company_name"] ?? "");
$email = trim($contact["email"] ?? "");
$phone = trim($contact["phone"] ?? "");
$job_title = trim($contact["job_title"] ?? "");
$status = strtolower(trim($contact["status"] ?? ""));

if ($status === "") {
    $status = "active";
}

$city = trim($contact["city"] ?? "");
$state = trim($contact["state"] ?? "");
$country = trim($contact["country"] ?? "");
$postal_code = trim($contact["postal_code"] ?? "");
$address = trim($contact["address"] ?? "");

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

    <title>
        <?php echo htmlspecialchars($full_name); ?> - Contact - CRM
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

        .edit-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .edit-btn:hover {
            background: #1d4ed8;
        }

        .list-btn {
            border: 1px solid #dbe2ea;
            background: #ffffff;
            color: #334155;
        }

        .list-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Hero
        ------------------------------------------------------------ */

        .profile-hero {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .profile-main {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .profile-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-info {
            min-width: 0;
        }

        .profile-info h2 {
            margin: 0;
            font-size: 23px;
            color: #172554;
            font-weight: 700;
        }

        .profile-job {
            margin-top: 5px;
            color: #64748b;
            font-size: 14px;
        }

        .profile-meta {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .contact-id {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-badge.inactive {
            background: #fef3c7;
            color: #92400e;
        }

        .company-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ------------------------------------------------------------
           Summary Cards
        ------------------------------------------------------------ */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            padding: 18px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
        }

        .summary-label {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .summary-value {
            display: block;
            color: #1e293b;
            font-size: 15px;
            font-weight: 700;
        }

        .summary-value.link-value a {
            color: #2563eb;
            text-decoration: none;
            word-break: break-word;
        }

        .summary-value.link-value a:hover {
            text-decoration: underline;
        }

        /* ------------------------------------------------------------
           Main Layout
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 22px;
            align-items: start;
        }

        .info-card,
        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .info-card {
            overflow: hidden;
        }

        .card-header {
            padding: 19px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
            margin: 0;
            font-size: 16px;
            color: #172554;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        /* ------------------------------------------------------------
           Information Grid
        ------------------------------------------------------------ */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .info-item {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-item:nth-child(odd) {
            border-right: 1px solid #e5e7eb;
        }

        .info-item.full {
            grid-column: 1 / -1;
            border-right: none;
        }

        .info-label {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .info-value {
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        .empty-value {
            color: #94a3b8;
        }

        .address-value {
            white-space: pre-line;
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
           Quick Actions
        ------------------------------------------------------------ */

        .quick-action {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            min-height: 42px;
            padding: 0 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            text-decoration: none;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            font-weight: 600;
        }

        .quick-action:last-child {
            margin-bottom: 0;
        }

        .quick-action:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .quick-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ------------------------------------------------------------
           Timeline
        ------------------------------------------------------------ */

        .timeline-item {
            position: relative;
            padding-left: 28px;
            margin-bottom: 20px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 20px;
            width: 1px;
            height: calc(100% + 6px);
            background: #dbe2ea;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 3px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #dbeafe;
            border: 3px solid #2563eb;
        }

        .timeline-label {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .timeline-value {
            display: block;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        /* ------------------------------------------------------------
           Contact Methods
        ------------------------------------------------------------ */

        .method-box {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .method-box:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .method-icon {
            width: 34px;
            height: 34px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .method-text {
            min-width: 0;
        }

        .method-label {
            display: block;
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .method-value {
            display: block;
            color: #334155;
            font-size: 13px;
            word-break: break-word;
        }

        .method-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .method-value a:hover {
            text-decoration: underline;
        }

        /* ------------------------------------------------------------
           Bottom Buttons
        ------------------------------------------------------------ */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .bottom-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .bottom-edit {
            background: #2563eb;
            color: #ffffff;
        }

        .bottom-edit:hover {
            background: #1d4ed8;
        }

        .bottom-back {
            border: 1px solid #d1d5db;
            color: #475569;
            background: #ffffff;
        }

        .bottom-back:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Dark Mode
        ------------------------------------------------------------ */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .profile-info h2,
        html.dark-mode .card-header h3,
        html.dark-mode .side-title h3 {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .profile-job,
        html.dark-mode .breadcrumb,
        html.dark-mode .contact-id,
        html.dark-mode .card-header p,
        html.dark-mode .side-title p {
            color: #94a3b8;
        }

        html.dark-mode .profile-hero,
        html.dark-mode .summary-card,
        html.dark-mode .info-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .company-tag {
            background: #172554;
            color: #bfdbfe;
        }

        html.dark-mode .summary-label,
        html.dark-mode .info-label,
        html.dark-mode .method-label,
        html.dark-mode .timeline-label {
            color: #64748b;
        }

        html.dark-mode .summary-value,
        html.dark-mode .info-value,
        html.dark-mode .method-value,
        html.dark-mode .timeline-value {
            color: #cbd5e1;
        }

        html.dark-mode .info-item {
            border-color: #1f2937;
        }

        html.dark-mode .info-item:nth-child(odd) {
            border-color: #1f2937;
        }

        html.dark-mode .list-btn,
        html.dark-mode .bottom-back {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .list-btn:hover,
        html.dark-mode .bottom-back:hover {
            background: #1e293b;
        }

        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #1e293b;
        }

        html.dark-mode .quick-icon {
            background: #1e293b;
        }

        html.dark-mode .method-box {
            border-color: #1f2937;
        }

        html.dark-mode .method-icon {
            background: #172554;
        }

        html.dark-mode .timeline-item::before {
            background: #334155;
        }

        html.dark-mode .empty-value {
            color: #64748b;
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

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
            }

            .profile-hero {
                flex-direction: column;
                align-items: flex-start;
            }

        }

        @media (max-width: 650px) {

            .main-content {
                padding: 20px;
            }

            .profile-main {
                align-items: flex-start;
            }

            .profile-avatar {
                width: 62px;
                height: 62px;
                font-size: 22px;
            }

            .profile-info h2 {
                font-size: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item:nth-child(odd) {
                border-right: none;
            }

            .info-item.full {
                grid-column: auto;
            }

            .header-actions,
            .bottom-actions {
                width: 100%;
            }

            .header-btn,
            .bottom-btn {
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

            <span>
                View Contact
            </span>

        </div>


        <!-- =========================================================
             Page Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Contact Details
                </h1>

                <p>
                    View complete contact information and communication details.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="edit.php?id=<?php echo (int) $contact["id"]; ?>"
                    class="header-btn edit-btn"
                >
                    ✎ Edit Contact
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
             Profile Hero
        ========================================================== -->

        <div class="profile-hero">

            <div class="profile-main">

                <div class="profile-avatar">

                    <?php
                    echo htmlspecialchars($initial);
                    ?>

                </div>


                <div class="profile-info">

                    <h2>
                        <?php echo htmlspecialchars($full_name); ?>
                    </h2>


                    <div class="profile-job">

                        <?php
                        echo $job_title !== ""
                            ? htmlspecialchars($job_title)
                            : "Contact";
                        ?>

                    </div>


                    <div class="profile-meta">

                        <span class="contact-id">

                            Contact #<?php echo (int) $contact["id"]; ?>

                        </span>


                        <span
                            class="status-badge <?php echo ($status === "inactive") ? "inactive" : "active"; ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                ucfirst($status)
                            );
                            ?>

                        </span>


                        <?php if ($company_name !== ""): ?>

                            <span class="company-tag">

                                🏢

                                <?php echo htmlspecialchars($company_name); ?>

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================================
             Summary Cards
        ========================================================== -->

        <div class="summary-grid">


            <!-- Company -->

            <div class="summary-card">

                <span class="summary-label">
                    Company
                </span>

                <span class="summary-value">

                    <?php

                    echo $company_name !== ""
                        ? htmlspecialchars($company_name)
                        : "Not assigned";

                    ?>

                </span>

            </div>


            <!-- Email -->

            <div class="summary-card">

                <span class="summary-label">
                    Email
                </span>

                <span class="summary-value link-value">

                    <?php if ($email !== ""): ?>

                        <a href="mailto:<?php echo htmlspecialchars($email); ?>">
                            <?php echo htmlspecialchars($email); ?>
                        </a>

                    <?php else: ?>

                        <span class="empty-value">
                            Not provided
                        </span>

                    <?php endif; ?>

                </span>

            </div>


            <!-- Phone -->

            <div class="summary-card">

                <span class="summary-label">
                    Phone
                </span>

                <span class="summary-value link-value">

                    <?php if ($phone !== ""): ?>

                        <a href="tel:<?php echo htmlspecialchars($phone); ?>">
                            <?php echo htmlspecialchars($phone); ?>
                        </a>

                    <?php else: ?>

                        <span class="empty-value">
                            Not provided
                        </span>

                    <?php endif; ?>

                </span>

            </div>

        </div>


        <!-- =========================================================
             Main Content
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 LEFT - INFORMATION
            ====================================================== -->

            <div>


                <!-- Personal Information -->

                <div class="info-card">

                    <div class="card-header">

                        <h3>
                            Personal & Contact Information
                        </h3>

                        <p>
                            Basic identity and communication details.
                        </p>

                    </div>


                    <div class="info-grid">


                        <!-- Contact ID -->

                        <div class="info-item">

                            <span class="info-label">
                                Contact ID
                            </span>

                            <div class="info-value">
                                #<?php echo (int) $contact["id"]; ?>
                            </div>

                        </div>


                        <!-- Status -->

                        <div class="info-item">

                            <span class="info-label">
                                Status
                            </span>

                            <div class="info-value">

                                <span
                                    class="status-badge <?php echo ($status === "inactive") ? "inactive" : "active"; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        ucfirst($status)
                                    );
                                    ?>

                                </span>

                            </div>

                        </div>


                        <!-- First Name -->

                        <div class="info-item">

                            <span class="info-label">
                                First Name
                            </span>

                            <div class="info-value">

                                <?php
                                echo $first_name !== ""
                                    ? htmlspecialchars($first_name)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Last Name -->

                        <div class="info-item">

                            <span class="info-label">
                                Last Name
                            </span>

                            <div class="info-value">

                                <?php
                                echo $last_name !== ""
                                    ? htmlspecialchars($last_name)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Job Title -->

                        <div class="info-item">

                            <span class="info-label">
                                Job Title
                            </span>

                            <div class="info-value">

                                <?php
                                echo $job_title !== ""
                                    ? htmlspecialchars($job_title)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Company -->

                        <div class="info-item">

                            <span class="info-label">
                                Company
                            </span>

                            <div class="info-value">

                                <?php
                                echo $company_name !== ""
                                    ? htmlspecialchars($company_name)
                                    : '<span class="empty-value">Not assigned</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Email -->

                        <div class="info-item">

                            <span class="info-label">
                                Email
                            </span>

                            <div class="info-value">

                                <?php if ($email !== ""): ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($email); ?>"
                                    >
                                        <?php echo htmlspecialchars($email); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Phone -->

                        <div class="info-item">

                            <span class="info-label">
                                Phone
                            </span>

                            <div class="info-value">

                                <?php if ($phone !== ""): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($phone); ?>"
                                    >
                                        <?php echo htmlspecialchars($phone); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Address Information -->

                <div
                    class="info-card"
                    style="margin-top: 20px;"
                >

                    <div class="card-header">

                        <h3>
                            Address Information
                        </h3>

                        <p>
                            Location details associated with this contact.
                        </p>

                    </div>


                    <div class="info-grid">


                        <!-- Address -->

                        <div class="info-item full">

                            <span class="info-label">
                                Address
                            </span>

                            <div class="info-value address-value">

                                <?php if ($address !== ""): ?>

                                    <?php echo htmlspecialchars($address); ?>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- City -->

                        <div class="info-item">

                            <span class="info-label">
                                City
                            </span>

                            <div class="info-value">

                                <?php
                                echo $city !== ""
                                    ? htmlspecialchars($city)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- State -->

                        <div class="info-item">

                            <span class="info-label">
                                State
                            </span>

                            <div class="info-value">

                                <?php
                                echo $state !== ""
                                    ? htmlspecialchars($state)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Country -->

                        <div class="info-item">

                            <span class="info-label">
                                Country
                            </span>

                            <div class="info-value">

                                <?php
                                echo $country !== ""
                                    ? htmlspecialchars($country)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Postal -->

                        <div class="info-item">

                            <span class="info-label">
                                Postal Code
                            </span>

                            <div class="info-value">

                                <?php
                                echo $postal_code !== ""
                                    ? htmlspecialchars($postal_code)
                                    : '<span class="empty-value">Not provided</span>';
                                ?>

                            </div>

                        </div>


                        <!-- Location -->

                        <div class="info-item full">

                            <span class="info-label">
                                Location
                            </span>

                            <div class="info-value">

                                <?php echo htmlspecialchars($location); ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Bottom Actions -->

                <div class="bottom-actions">

                    <a
                        href="edit.php?id=<?php echo (int) $contact["id"]; ?>"
                        class="bottom-btn bottom-edit"
                    >
                        ✎ Edit Contact
                    </a>

                    <a
                        href="index.php"
                        class="bottom-btn bottom-back"
                    >
                        ← Back to Contacts
                    </a>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDEBAR
            ====================================================== -->

            <div>


                <!-- Contact Methods -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Contact Methods
                        </h3>

                        <p>
                            Quick access to communication details.
                        </p>

                    </div>


                    <div class="method-box">

                        <div class="method-icon">
                            ✉
                        </div>

                        <div class="method-text">

                            <span class="method-label">
                                Email
                            </span>

                            <span class="method-value">

                                <?php if ($email !== ""): ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($email); ?>"
                                    >
                                        <?php echo htmlspecialchars($email); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </span>

                        </div>

                    </div>


                    <div class="method-box">

                        <div class="method-icon">
                            ☎
                        </div>

                        <div class="method-text">

                            <span class="method-label">
                                Phone
                            </span>

                            <span class="method-value">

                                <?php if ($phone !== ""): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($phone); ?>"
                                    >
                                        <?php echo htmlspecialchars($phone); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </span>

                        </div>

                    </div>


                    <div class="method-box">

                        <div class="method-icon">
                            📍
                        </div>

                        <div class="method-text">

                            <span class="method-label">
                                Location
                            </span>

                            <span class="method-value">
                                <?php echo htmlspecialchars($location); ?>
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Quick Actions -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Quick Actions
                        </h3>

                        <p>
                            Common actions for this contact.
                        </p>

                    </div>


                    <a
                        href="edit.php?id=<?php echo (int) $contact["id"]; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✎
                        </span>

                        Edit Contact

                    </a>


                    <?php if ($phone !== ""): ?>

                        <a
                            href="tel:<?php echo htmlspecialchars($phone); ?>"
                            class="quick-action"
                        >

                            <span class="quick-icon">
                                ☎
                            </span>

                            Call Contact

                        </a>

                    <?php endif; ?>


                    <?php if ($email !== ""): ?>

                        <a
                            href="mailto:<?php echo htmlspecialchars($email); ?>"
                            class="quick-action"
                        >

                            <span class="quick-icon">
                                ✉
                            </span>

                            Send Email

                        </a>

                    <?php endif; ?>


                    <a
                        href="index.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ←
                        </span>

                        Back to Contacts

                    </a>

                </div>


                <!-- Record Timeline -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Record Timeline
                        </h3>

                        <p>
                            Contact record history.
                        </p>

                    </div>


                    <div class="timeline-item">

                        <span class="timeline-dot"></span>

                        <span class="timeline-label">
                            Created
                        </span>

                        <span class="timeline-value">

                            <?php
                            echo !empty($contact["created_at"])
                                ? htmlspecialchars($contact["created_at"])
                                : "Not available";
                            ?>

                        </span>

                    </div>


                    <div class="timeline-item">

                        <span class="timeline-dot"></span>

                        <span class="timeline-label">
                            Last Updated
                        </span>

                        <span class="timeline-value">

                            <?php

                            echo !empty($contact["updated_at"])
                                ? htmlspecialchars($contact["updated_at"])
                                : "Not available";

                            ?>

                        </span>

                    </div>

                </div>


                <!-- Contact Tip -->

                <div class="side-card">

                    <div
                        style="
                            background:#eff6ff;
                            border:1px solid #bfdbfe;
                            border-radius:9px;
                            padding:13px;
                            color:#1e40af;
                            font-size:12px;
                            line-height:1.55;
                        "
                    >

                        <strong
                            style="
                                display:block;
                                margin-bottom:4px;
                            "
                        >
                            💡 Contact Tip
                        </strong>

                        Keep the contact's company, job title, communication details, and location updated so your CRM records remain useful.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>