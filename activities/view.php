<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Validate Activity ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Load Activity
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        activities.*,

        CONCAT(
            contacts.first_name,
            ' ',
            contacts.last_name
        ) AS contact_name,

        contacts.email AS contact_email,
        contacts.phone AS contact_phone,

        customers.customer_code,

        deals.title AS deal_title,

        users.name AS created_by_name

    FROM activities

    LEFT JOIN contacts
        ON activities.contact_id = contacts.id

    LEFT JOIN customers
        ON activities.customer_id = customers.id

    LEFT JOIN deals
        ON activities.deal_id = deals.id

    LEFT JOIN users
        ON activities.created_by = users.id

    WHERE activities.id = :id

    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Display Values
|--------------------------------------------------------------------------
*/
$activity_type = trim($activity["type"] ?? "");
$activity_subject = trim($activity["subject"] ?? "");

if ($activity_type === "") {
    $activity_type = "Activity";
}

if ($activity_subject === "") {
    $activity_subject = "Untitled Activity";
}

$contact_name = trim(
    ($activity["contact_name"] ?? "")
);

if ($contact_name === "") {
    $contact_name = "";
}

/*
|--------------------------------------------------------------------------
| Activity Type Styling
|--------------------------------------------------------------------------
*/
$type_class_map = [
    "Call" => "type-call",
    "Meeting" => "type-meeting",
    "Email" => "type-email",
    "Note" => "type-note",
    "Follow-up" => "type-followup"
];

$type_class =
    $type_class_map[$activity_type]
    ?? "type-default";

/*
|--------------------------------------------------------------------------
| Activity Type Icon
|--------------------------------------------------------------------------
*/
$type_icon_map = [
    "Call" => "☎",
    "Meeting" => "👥",
    "Email" => "✉",
    "Note" => "📝",
    "Follow-up" => "🔄"
];

$type_icon =
    $type_icon_map[$activity_type]
    ?? "📅";

/*
|--------------------------------------------------------------------------
| Date Formatting
|--------------------------------------------------------------------------
*/
function formatActivityDateTime($date)
{
    if (empty($date)) {
        return "Not set";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date(
        "d M Y, h:i A",
        $timestamp
    );
}

function formatActivityDateOnly($date)
{
    if (empty($date)) {
        return "Not set";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date(
        "d M Y",
        $timestamp
    );
}

$activity_date_display =
    formatActivityDateTime(
        $activity["activity_date"] ?? null
    );

$created_at_display =
    formatActivityDateTime(
        $activity["created_at"] ?? null
    );

/*
|--------------------------------------------------------------------------
| Initial
|--------------------------------------------------------------------------
*/
$initial = strtoupper(
    substr(
        $activity_subject,
        0,
        1
    )
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
        <?php echo htmlspecialchars($activity_subject); ?> | CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        .page-wrap {
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

            margin-bottom: 10px;

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

        /* =========================================================
           HEADER
        ========================================================= */

        .page-header {
            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }

        .page-header-left h1 {
            margin: 0 0 7px;

            color: #0f172a;

            font-size: 28px;

            font-weight: 700;

            line-height: 1.1;
        }

        .page-header-left p {
            margin: 0;

            color: #64748b;

            font-size: 14px;
        }

        .header-actions {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .btn {
            min-height: 40px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding:
                0
                15px;

            border:
                1px solid transparent;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;

            border-color: #2563eb;

            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;

            border-color: #1d4ed8;
        }

        .btn-secondary {
            background: #ffffff;

            border-color: #dbe1e8;

            color: #334155;
        }

        .btn-secondary:hover {
            background: #f8fafc;

            border-color: #cbd5e1;
        }

        /* =========================================================
           HERO
        ========================================================= */

        .activity-hero {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding:
                22px;

            margin-bottom: 20px;

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .hero-left {
            display: flex;

            align-items: center;

            gap: 15px;

            min-width: 0;
        }

        .activity-avatar {
            width: 62px;
            height: 62px;

            flex: 0 0 62px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #60a5fa
                );

            color: #ffffff;

            font-size: 24px;

            font-weight: 700;

            box-shadow:
                0 7px 18px
                rgba(37, 99, 235, 0.20);
        }

        .hero-info {
            min-width: 0;
        }

        .hero-info h2 {
            margin: 0 0 6px;

            color: #0f172a;

            font-size: 21px;

            font-weight: 700;

            line-height: 1.35;

            word-break: break-word;
        }

        .hero-meta {
            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            color: #64748b;

            font-size: 12px;
        }

        .hero-meta-item {
            display: inline-flex;

            align-items: center;

            gap: 5px;
        }

        .hero-right {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }

        /* =========================================================
           TYPE BADGES
        ========================================================= */

        .activity-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            padding:
                7px
                11px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }

        .type-call {
            background: #dcfce7;

            color: #166534;
        }

        .type-meeting {
            background: #dbeafe;

            color: #1e40af;
        }

        .type-email {
            background: #fef3c7;

            color: #92400e;
        }

        .type-note {
            background: #ede9fe;

            color: #6d28d9;
        }

        .type-followup {
            background: #fce7f3;

            color: #9d174d;
        }

        .type-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================================================
           SUMMARY CARDS
        ========================================================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 20px;
        }

        .summary-card {
            padding:
                16px;

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 10px;

            box-shadow:
                0 2px 7px
                rgba(15, 23, 42, 0.03);
        }

        .summary-label {
            margin-bottom: 8px;

            color: #64748b;

            font-size: 11px;

            font-weight: 600;
        }

        .summary-value {
            color: #0f172a;

            font-size: 15px;

            font-weight: 700;

            line-height: 1.35;

            word-break: break-word;
        }

        /* =========================================================
           CONTENT GRID
        ========================================================= */

        .content-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                330px;

            gap: 20px;

            align-items: start;
        }

        .main-column,
        .side-column {
            min-width: 0;
        }

        /* =========================================================
           CARD
        ========================================================= */

        .card {
            margin-bottom: 20px;

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);

            overflow: hidden;
        }

        .card:last-child {
            margin-bottom: 0;
        }

        .card-header {
            padding:
                17px
                19px;

            border-bottom:
                1px solid #eef2f7;
        }

        .card-header h3 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;
        }

        .card-header p {
            margin: 4px 0 0;

            color: #64748b;

            font-size: 11px;
        }

        .card-body {
            padding:
                19px;
        }

        /* =========================================================
           DETAIL GRID
        ========================================================= */

        .details-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 0;
        }

        .detail-item {
            min-width: 0;

            padding:
                14px
                0;

            border-bottom:
                1px solid #f1f5f9;
        }

        .detail-item:nth-last-child(-n + 2) {
            border-bottom: none;
        }

        .detail-label {
            margin-bottom: 5px;

            color: #64748b;

            font-size: 11px;

            font-weight: 600;
        }

        .detail-value {
            color: #334155;

            font-size: 13px;

            font-weight: 600;

            word-break: break-word;
        }

        .detail-value a {
            color: #2563eb;

            text-decoration: none;
        }

        .detail-value a:hover {
            text-decoration: underline;
        }

        /* =========================================================
           DESCRIPTION
        ========================================================= */

        .description-box {
            padding:
                15px;

            border:
                1px solid #e2e8f0;

            border-radius: 9px;

            background: #f8fafc;

            color: #475569;

            font-size: 13px;

            line-height: 1.7;

            white-space: pre-wrap;

            word-break: break-word;
        }

        .empty-text {
            color: #94a3b8;

            font-size: 13px;
        }

        /* =========================================================
           CONTACT
        ========================================================= */

        .contact-box {
            display: flex;

            align-items: center;

            gap: 12px;
        }

        .contact-avatar {
            width: 42px;
            height: 42px;

            flex: 0 0 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 15px;

            font-weight: 700;
        }

        .contact-name {
            margin-bottom: 3px;

            color: #0f172a;

            font-size: 13px;

            font-weight: 700;
        }

        .contact-details {
            color: #64748b;

            font-size: 11px;

            line-height: 1.6;
        }

        .contact-details a {
            color: #2563eb;

            text-decoration: none;
        }

        /* =========================================================
           SIDE STAT
        ========================================================= */

        .side-stat {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            padding:
                12px
                0;

            border-bottom:
                1px solid #f1f5f9;
        }

        .side-stat:last-child {
            border-bottom: none;
        }

        .side-stat-label {
            color: #64748b;

            font-size: 11px;
        }

        .side-stat-value {
            max-width: 175px;

            color: #334155;

            font-size: 12px;

            font-weight: 700;

            text-align: right;

            word-break: break-word;
        }

        /* =========================================================
           QUICK ACTIONS
        ========================================================= */

        .quick-actions {
            display: grid;

            gap: 8px;
        }

        .quick-action {
            display: flex;

            align-items: center;

            gap: 9px;

            padding:
                11px
                12px;

            border:
                1px solid #e2e8f0;

            border-radius: 8px;

            background: #ffffff;

            color: #334155;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            transition: 0.2s ease;
        }

        .quick-action:hover {
            background: #f8fafc;

            border-color: #cbd5e1;

            color: #2563eb;
        }

        .action-icon {
            width: 27px;
            height: 27px;

            flex: 0 0 27px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 12px;
        }

        /* =========================================================
           TIMELINE
        ========================================================= */

        .timeline {
            position: relative;

            padding-left: 22px;
        }

        .timeline::before {
            content: "";

            position: absolute;

            left: 5px;

            top: 4px;

            bottom: 4px;

            width: 1px;

            background: #dbe3ec;
        }

        .timeline-item {
            position: relative;

            margin-bottom: 18px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;

            left: -21px;

            top: 2px;

            width: 11px;
            height: 11px;

            border-radius: 50%;

            background: #2563eb;

            border:
                2px solid #ffffff;

            box-shadow:
                0 0 0 1px #bfdbfe;
        }

        .timeline-title {
            margin-bottom: 3px;

            color: #334155;

            font-size: 12px;

            font-weight: 700;
        }

        .timeline-value {
            color: #64748b;

            font-size: 11px;
        }

        /* =========================================================
           BOTTOM ACTIONS
        ========================================================= */

        .bottom-actions {
            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 9px;

            margin-top: 20px;
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        html.dark-mode body {
            background: #0f172a;

            color: #e2e8f0;
        }

        html.dark-mode .breadcrumb {
            color: #94a3b8;
        }

        html.dark-mode .breadcrumb a {
            color: #60a5fa;
        }

        html.dark-mode .page-header-left h1,
        html.dark-mode .hero-info h2,
        html.dark-mode .card-header h3,
        html.dark-mode .summary-value,
        html.dark-mode .detail-value,
        html.dark-mode .contact-name,
        html.dark-mode .side-stat-value,
        html.dark-mode .timeline-title {
            color: #f8fafc;
        }

        html.dark-mode .page-header-left p,
        html.dark-mode .card-header p,
        html.dark-mode .summary-label,
        html.dark-mode .detail-label,
        html.dark-mode .side-stat-label,
        html.dark-mode .timeline-value {
            color: #94a3b8;
        }

        html.dark-mode .activity-hero,
        html.dark-mode .card,
        html.dark-mode .summary-card {
            background: #111827;

            border-color: #1f2937;

            box-shadow: none;
        }

        html.dark-mode .hero-meta {
            color: #94a3b8;
        }

        html.dark-mode .btn-secondary {
            background: #1e293b;

            border-color: #334155;

            color: #e2e8f0;
        }

        html.dark-mode .btn-secondary:hover {
            background: #273449;
        }

        html.dark-mode .card-header {
            border-color: #1f2937;
        }

        html.dark-mode .detail-item,
        html.dark-mode .side-stat {
            border-color: #1f2937;
        }

        html.dark-mode .description-box {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .contact-avatar {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .contact-details {
            color: #94a3b8;
        }

        html.dark-mode .contact-details a,
        html.dark-mode .detail-value a {
            color: #60a5fa;
        }

        html.dark-mode .quick-action {
            background: #0f172a;

            border-color: #334155;

            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #172033;

            border-color: #475569;

            color: #60a5fa;
        }

        html.dark-mode .action-icon {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .timeline::before {
            background: #334155;
        }

        html.dark-mode .timeline-dot {
            border-color: #111827;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1050px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .side-column {
                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 20px;
            }

            .side-column .card {
                margin-bottom: 0;
            }

        }

        @media (max-width: 760px) {

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

            .activity-hero {
                flex-direction: column;

                align-items: flex-start;
            }

            .hero-right {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .detail-item:nth-last-child(-n + 2) {
                border-bottom:
                    1px solid #f1f5f9;
            }

            .detail-item:last-child {
                border-bottom: none;
            }

            .side-column {
                grid-template-columns: 1fr;
            }

            .bottom-actions {
                flex-direction: column;

                align-items: stretch;
            }

            .bottom-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-wrap">

        <!-- =====================================================
             BREADCRUMB
        ====================================================== -->

        <div class="breadcrumb">

            <a href="../dashboard/index.php">
                Dashboard
            </a>

            <span>/</span>

            <a href="index.php">
                Activities
            </a>

            <span>/</span>

            <span>
                View Activity
            </span>

        </div>


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="page-header-left">

                <h1>
                    Activity Details
                </h1>

                <p>
                    View activity information, related records and contact details.
                </p>

            </div>

            <div class="header-actions">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    ← Activity List
                </a>

                <a
                    href="edit.php?id=<?php echo (int) $activity["id"]; ?>"
                    class="btn btn-primary"
                >
                    ✎ Edit Activity
                </a>

            </div>

        </div>


        <!-- =====================================================
             HERO
        ====================================================== -->

        <div class="activity-hero">

            <div class="hero-left">

                <div class="activity-avatar">

                    <?php
                    echo htmlspecialchars($initial);
                    ?>

                </div>

                <div class="hero-info">

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $activity_subject
                        );
                        ?>
                    </h2>

                    <div class="hero-meta">

                        <span class="hero-meta-item">

                            📝 Activity #
                            <?php
                            echo (int) $activity["id"];
                            ?>

                        </span>

                        <span>•</span>

                        <span class="hero-meta-item">

                            <?php
                            echo htmlspecialchars(
                                $type_icon
                            );
                            ?>

                            <?php
                            echo htmlspecialchars(
                                $activity_type
                            );
                            ?>

                        </span>

                        <?php if (!empty($activity["activity_date"])): ?>

                            <span>•</span>

                            <span class="hero-meta-item">

                                🕒

                                <?php
                                echo htmlspecialchars(
                                    $activity_date_display
                                );
                                ?>

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <div class="hero-right">

                <span
                    class="activity-badge <?php
                    echo htmlspecialchars($type_class);
                    ?>"
                >

                    <?php
                    echo htmlspecialchars($type_icon);
                    ?>

                    <?php
                    echo htmlspecialchars(
                        $activity_type
                    );
                    ?>

                </span>

            </div>

        </div>


        <!-- =====================================================
             SUMMARY
        ====================================================== -->

        <div class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Activity ID
                </div>

                <div class="summary-value">
                    #<?php
                    echo (int) $activity["id"];
                    ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Activity Type
                </div>

                <div class="summary-value">

                    <?php
                    echo htmlspecialchars(
                        $activity_type
                    );
                    ?>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Activity Date
                </div>

                <div class="summary-value">

                    <?php
                    echo htmlspecialchars(
                        $activity_date_display
                    );
                    ?>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Created By
                </div>

                <div class="summary-value">

                    <?php

                    if (!empty($activity["created_by_name"])) {

                        echo htmlspecialchars(
                            $activity["created_by_name"]
                        );

                    } else {

                        echo "Unknown";

                    }

                    ?>

                </div>

            </div>

        </div>


        <!-- =====================================================
             CONTENT GRID
        ====================================================== -->

        <div class="content-grid">

            <!-- =================================================
                 MAIN COLUMN
            ================================================== -->

            <div class="main-column">

                <!-- Activity Information -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Activity Information
                        </h3>

                        <p>
                            Main details of this activity record.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="details-grid">

                            <div class="detail-item">

                                <div class="detail-label">
                                    Activity ID
                                </div>

                                <div class="detail-value">
                                    #<?php
                                    echo (int) $activity["id"];
                                    ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Activity Type
                                </div>

                                <div class="detail-value">

                                    <span
                                        class="activity-badge <?php
                                        echo htmlspecialchars(
                                            $type_class
                                        );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $type_icon
                                        );
                                        ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $activity_type
                                        );
                                        ?>

                                    </span>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Subject
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity_subject
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Activity Date
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity_date_display
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Created By
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo !empty(
                                        $activity["created_by_name"]
                                    )
                                        ? htmlspecialchars(
                                            $activity[
                                                "created_by_name"
                                            ]
                                        )
                                        : "Unknown";

                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Created At
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $created_at_display
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Description -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Description
                        </h3>

                        <p>
                            Activity notes and additional information.
                        </p>

                    </div>

                    <div class="card-body">

                        <?php if (!empty($activity["description"])): ?>

                            <div class="description-box">

                                <?php
                                echo htmlspecialchars(
                                    $activity["description"]
                                );
                                ?>

                            </div>

                        <?php else: ?>

                            <div class="empty-text">
                                No description has been added to this activity.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- Related Records -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Related Records
                        </h3>

                        <p>
                            CRM records associated with this activity.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="details-grid">

                            <div class="detail-item">

                                <div class="detail-label">
                                    Contact
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo $contact_name !== ""
                                        ? htmlspecialchars(
                                            $contact_name
                                        )
                                        : "Not linked";

                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Customer
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo !empty(
                                        $activity["customer_code"]
                                    )
                                        ? htmlspecialchars(
                                            $activity[
                                                "customer_code"
                                            ]
                                        )
                                        : "Not linked";

                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Deal
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo !empty(
                                        $activity["deal_title"]
                                    )
                                        ? htmlspecialchars(
                                            $activity[
                                                "deal_title"
                                            ]
                                        )
                                        : "Not linked";

                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Record ID
                                </div>

                                <div class="detail-value">
                                    Activity #<?php
                                    echo (int) $activity["id"];
                                    ?>
                                </div>

                            </div>

                        </div>


                        <?php if ($contact_name !== ""): ?>

                            <div
                                class="contact-box"
                                style="margin-top:18px;"
                            >

                                <div class="contact-avatar">

                                    <?php

                                    echo strtoupper(
                                        substr(
                                            $contact_name,
                                            0,
                                            1
                                        )
                                    );

                                    ?>

                                </div>

                                <div>

                                    <div class="contact-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $contact_name
                                        );
                                        ?>

                                    </div>

                                    <div class="contact-details">

                                        <?php if (!empty($activity["contact_email"])): ?>

                                            <a
                                                href="mailto:<?php
                                                echo htmlspecialchars(
                                                    $activity[
                                                        "contact_email"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $activity[
                                                        "contact_email"
                                                    ]
                                                );
                                                ?>

                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty(
                                                $activity[
                                                    "contact_email"
                                                ]
                                            )
                                            &&
                                            !empty(
                                                $activity[
                                                    "contact_phone"
                                                ]
                                            )
                                        ): ?>

                                            &nbsp;•&nbsp;

                                        <?php endif; ?>


                                        <?php if (!empty($activity["contact_phone"])): ?>

                                            <a
                                                href="tel:<?php
                                                echo htmlspecialchars(
                                                    $activity[
                                                        "contact_phone"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $activity[
                                                        "contact_phone"
                                                    ]
                                                );
                                                ?>

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SIDE COLUMN
            ================================================== -->

            <div class="side-column">

                <!-- Activity Summary -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Activity Summary
                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="side-stat">

                            <span class="side-stat-label">
                                Type
                            </span>

                            <span
                                class="activity-badge <?php
                                echo htmlspecialchars(
                                    $type_class
                                );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $activity_type
                                );
                                ?>

                            </span>

                        </div>


                        <div class="side-stat">

                            <span class="side-stat-label">
                                Date
                            </span>

                            <span class="side-stat-value">

                                <?php
                                echo htmlspecialchars(
                                    $activity_date_display
                                );
                                ?>

                            </span>

                        </div>


                        <div class="side-stat">

                            <span class="side-stat-label">
                                Contact
                            </span>

                            <span class="side-stat-value">

                                <?php

                                echo $contact_name !== ""
                                    ? htmlspecialchars(
                                        $contact_name
                                    )
                                    : "Not linked";

                                ?>

                            </span>

                        </div>


                        <div class="side-stat">

                            <span class="side-stat-label">
                                Customer
                            </span>

                            <span class="side-stat-value">

                                <?php

                                echo !empty(
                                    $activity["customer_code"]
                                )
                                    ? htmlspecialchars(
                                        $activity[
                                            "customer_code"
                                        ]
                                    )
                                    : "Not linked";

                                ?>

                            </span>

                        </div>


                        <div class="side-stat">

                            <span class="side-stat-label">
                                Deal
                            </span>

                            <span class="side-stat-value">

                                <?php

                                echo !empty(
                                    $activity["deal_title"]
                                )
                                    ? htmlspecialchars(
                                        $activity[
                                            "deal_title"
                                        ]
                                    )
                                    : "Not linked";

                                ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- Contact Methods -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Contact Methods
                        </h3>

                    </div>


                    <div class="card-body">

                        <?php if ($contact_name !== ""): ?>

                            <div class="quick-actions">

                                <?php if (!empty($activity["contact_email"])): ?>

                                    <a
                                        href="mailto:<?php
                                        echo htmlspecialchars(
                                            $activity[
                                                "contact_email"
                                            ]
                                        );
                                        ?>"
                                        class="quick-action"
                                    >

                                        <span class="action-icon">
                                            ✉
                                        </span>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $activity[
                                                    "contact_email"
                                                ]
                                            );
                                            ?>
                                        </span>

                                    </a>

                                <?php endif; ?>


                                <?php if (!empty($activity["contact_phone"])): ?>

                                    <a
                                        href="tel:<?php
                                        echo htmlspecialchars(
                                            $activity[
                                                "contact_phone"
                                            ]
                                        );
                                        ?>"
                                        class="quick-action"
                                    >

                                        <span class="action-icon">
                                            ☎
                                        </span>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $activity[
                                                    "contact_phone"
                                                ]
                                            );
                                            ?>
                                        </span>

                                    </a>

                                <?php endif; ?>


                                <?php if (
                                    empty(
                                        $activity[
                                            "contact_email"
                                        ]
                                    )
                                    &&
                                    empty(
                                        $activity[
                                            "contact_phone"
                                        ]
                                    )
                                ): ?>

                                    <div class="empty-text">
                                        No contact methods available.
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php else: ?>

                            <div class="empty-text">
                                No contact is linked to this activity.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- Quick Actions -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="quick-actions">

                            <a
                                href="edit.php?id=<?php
                                echo (int) $activity["id"];
                                ?>"
                                class="quick-action"
                            >

                                <span class="action-icon">
                                    ✎
                                </span>

                                Edit Activity

                            </a>


                            <a
                                href="index.php"
                                class="quick-action"
                            >

                                <span class="action-icon">
                                    ←
                                </span>

                                Back to Activities

                            </a>


                            <a
                                href="../dashboard/index.php"
                                class="quick-action"
                            >

                                <span class="action-icon">
                                    ⌂
                                </span>

                                Dashboard

                            </a>

                        </div>

                    </div>

                </div>


                <!-- Timeline -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Record Timeline
                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="timeline">

                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Activity Created
                                </div>

                                <div class="timeline-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $created_at_display
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Activity Type
                                </div>

                                <div class="timeline-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity_type
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Activity Date
                                </div>

                                <div class="timeline-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity_date_display
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             BOTTOM ACTIONS
        ====================================================== -->

        <div class="bottom-actions">

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Back to Activities
            </a>

            <a
                href="edit.php?id=<?php
                echo (int) $activity["id"];
                ?>"
                class="btn btn-primary"
            >
                ✎ Edit Activity
            </a>

        </div>

    </div>

</div>

</body>

</html>