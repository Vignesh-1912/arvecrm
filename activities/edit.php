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
| Allowed Activity Types
|--------------------------------------------------------------------------
*/
$allowed_types = [
    "Call",
    "Meeting",
    "Email",
    "Note",
    "Follow-up"
];

/*
|--------------------------------------------------------------------------
| Get Activity
|--------------------------------------------------------------------------
*/
$activity_stmt = $conn->prepare("
    SELECT *
    FROM activities
    WHERE id = :id
    LIMIT 1
");

$activity_stmt->execute([
    ":id" => $id
]);

$activity = $activity_stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Contacts
|--------------------------------------------------------------------------
*/
$contact_stmt = $conn->prepare("
    SELECT
        id,
        first_name,
        last_name
    FROM contacts
    ORDER BY first_name ASC, last_name ASC
");

$contact_stmt->execute();

$contacts = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Customers
|--------------------------------------------------------------------------
*/
$customer_stmt = $conn->prepare("
    SELECT
        id,
        customer_code
    FROM customers
    ORDER BY id DESC
");

$customer_stmt->execute();

$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Deals
|--------------------------------------------------------------------------
*/
$deal_stmt = $conn->prepare("
    SELECT
        id,
        title
    FROM deals
    ORDER BY id DESC
");

$deal_stmt->execute();

$deals = $deal_stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Update Activity
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $type = trim($_POST["type"] ?? "");

    $subject = trim(
        $_POST["subject"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $activity_date = !empty($_POST["activity_date"])
        ? $_POST["activity_date"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int) $_POST["contact_id"]
        : null;

    $customer_id = !empty($_POST["customer_id"])
        ? (int) $_POST["customer_id"]
        : null;

    $deal_id = !empty($_POST["deal_id"])
        ? (int) $_POST["deal_id"]
        : null;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    if ($type === "") {

        $error = "Activity type is required.";

    } elseif (!in_array($type, $allowed_types, true)) {

        $error = "Invalid activity type.";

    } elseif ($subject === "") {

        $error = "Activity subject is required.";

    } else {

        try {

            $update_sql = "
                UPDATE activities
                SET
                    contact_id = :contact_id,
                    customer_id = :customer_id,
                    deal_id = :deal_id,
                    type = :type,
                    subject = :subject,
                    description = :description,
                    activity_date = :activity_date
                WHERE id = :id
            ";

            $update_stmt = $conn->prepare($update_sql);

            $update_stmt->execute([
                ":contact_id"    => $contact_id,
                ":customer_id"   => $customer_id,
                ":deal_id"       => $deal_id,
                ":type"          => $type,
                ":subject"       => $subject,
                ":description"   => $description !== ""
                    ? $description
                    : null,
                ":activity_date" => $activity_date,
                ":id"            => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            $error =
                "Unable to update activity: " .
                $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/
$form_type = $_POST["type"]
    ?? $activity["type"]
    ?? "";

$form_subject = $_POST["subject"]
    ?? $activity["subject"]
    ?? "";

$form_description = $_POST["description"]
    ?? $activity["description"]
    ?? "";

$form_contact_id = $_POST["contact_id"]
    ?? $activity["contact_id"]
    ?? "";

$form_customer_id = $_POST["customer_id"]
    ?? $activity["customer_id"]
    ?? "";

$form_deal_id = $_POST["deal_id"]
    ?? $activity["deal_id"]
    ?? "";

/*
|--------------------------------------------------------------------------
| Activity Date For datetime-local
|--------------------------------------------------------------------------
*/
if (isset($_POST["activity_date"])) {

    $form_activity_date = $_POST["activity_date"];

} elseif (!empty($activity["activity_date"])) {

    $activity_timestamp = strtotime(
        $activity["activity_date"]
    );

    if ($activity_timestamp !== false) {

        $form_activity_date = date(
            "Y-m-d\TH:i",
            $activity_timestamp
        );

    } else {

        $form_activity_date = "";
    }

} else {

    $form_activity_date = "";
}

/*
|--------------------------------------------------------------------------
| Display Values
|--------------------------------------------------------------------------
*/
$activity_type_label = $form_type !== ""
    ? $form_type
    : "Activity";

$activity_subject_display = trim($form_subject) !== ""
    ? $form_subject
    : "Activity subject";

$initial = strtoupper(
    substr(
        $activity_subject_display,
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
        Edit Activity | CRM
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
           PAGE HEADER
        ========================================================= */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;
        }

        .page-header-left h1 {
            margin: 0 0 7px;

            color: #0f172a;

            font-size: 28px;

            line-height: 1.1;

            font-weight: 700;
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

            cursor: pointer;

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
           RECORD BAR
        ========================================================= */

        .record-bar {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 18px;

            padding:
                15px
                18px;

            margin-bottom: 20px;

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }

        .record-left {
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

            background: #eff6ff;

            color: #2563eb;

            font-size: 15px;

            font-weight: 700;
        }

        .record-title {
            margin-bottom: 3px;

            color: #0f172a;

            font-size: 13px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .record-subtitle {
            color: #64748b;

            font-size: 11px;
        }

        .record-right {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }

        /* =========================================================
           ACTIVITY TYPE BADGE
        ========================================================= */

        .activity-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                6px
                10px;

            border-radius: 999px;

            background: #dbeafe;

            color: #1d4ed8;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================================================
           ERROR
        ========================================================= */

        .error-box {
            margin-bottom: 20px;

            padding:
                13px
                15px;

            border:
                1px solid #fecaca;

            border-radius: 9px;

            background: #fef2f2;

            color: #991b1b;

            font-size: 13px;

            font-weight: 500;
        }

        /* =========================================================
           SUMMARY
        ========================================================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 20px;
        }

        .summary-card {
            padding:
                15px
                16px;

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 10px;
        }

        .summary-label {
            margin-bottom: 6px;

            color: #64748b;

            font-size: 11px;

            font-weight: 600;
        }

        .summary-value {
            color: #0f172a;

            font-size: 15px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        /* =========================================================
           MAIN LAYOUT
        ========================================================= */

        .edit-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                330px;

            gap: 20px;

            align-items: start;
        }

        .form-card,
        .side-card {
            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        /* =========================================================
           FORM SECTIONS
        ========================================================= */

        .form-section {
            padding: 22px;

            border-bottom:
                1px solid #eef2f7;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-heading {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 18px;
        }

        .section-icon {
            width: 34px;
            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 16px;
        }

        .section-heading h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;

            font-weight: 700;
        }

        .section-heading p {
            margin: 3px 0 0;

            color: #64748b;

            font-size: 12px;
        }

        /* =========================================================
           FORM
        ========================================================= */

        .form-group {
            margin-bottom: 17px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-row {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 16px;
        }

        .form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;

            font-weight: 700;
        }

        .required {
            color: #dc2626;
        }

        .field-help {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 11px;
        }

        .form-control {
            width: 100%;

            height: 42px;

            padding:
                0
                12px;

            border:
                1px solid #dbe1e8;

            border-radius: 8px;

            background: #ffffff;

            color: #0f172a;

            font-size: 13px;

            outline: none;

            transition: 0.2s ease;
        }

        textarea.form-control {
            min-height: 125px;

            height: auto;

            padding:
                11px
                12px;

            resize: vertical;

            line-height: 1.5;
        }

        .form-control:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }

        /* =========================================================
           ACTIVITY TYPE CARDS
        ========================================================= */

        .type-grid {
            display: grid;

            grid-template-columns:
                repeat(5, minmax(0, 1fr));

            gap: 9px;
        }

        .type-item {
            position: relative;
        }

        .type-item input {
            position: absolute;

            opacity: 0;

            pointer-events: none;
        }

        .type-label {
            display: block;

            padding:
                13px
                8px;

            border:
                1px solid #dbe1e8;

            border-radius: 8px;

            background: #ffffff;

            text-align: center;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .type-icon {
            display: block;

            margin-bottom: 5px;

            font-size: 17px;
        }

        .type-label strong {
            display: block;

            color: #334155;

            font-size: 11px;
        }

        .type-item input:checked + .type-label {
            border-color: #2563eb;

            background: #eff6ff;

            box-shadow:
                0 0 0 1px #2563eb;
        }

        .type-item input:checked + .type-label strong {
            color: #2563eb;
        }

        /* =========================================================
           FORM FOOTER
        ========================================================= */

        .form-footer {
            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 9px;

            padding:
                18px
                22px;

            background: #f8fafc;

            border-radius:
                0
                0
                12px
                12px;
        }

        /* =========================================================
           SIDE CARD
        ========================================================= */

        .side-card {
            overflow: hidden;
        }

        .side-card-header {
            padding: 18px;

            border-bottom:
                1px solid #eef2f7;
        }

        .side-card-header h3 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;
        }

        .side-card-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 12px;

            line-height: 1.5;
        }

        .side-card-body {
            padding: 18px;
        }

        /* =========================================================
           PREVIEW
        ========================================================= */

        .activity-preview {
            border:
                1px solid #e2e8f0;

            border-radius: 10px;

            overflow: hidden;
        }

        .preview-top {
            padding: 17px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );

            border-bottom:
                1px solid #e2e8f0;
        }

        .preview-top-row {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 13px;
        }

        .preview-icon {
            width: 40px;
            height: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #2563eb;

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;
        }

        .preview-type {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                6px
                10px;

            border-radius: 999px;

            background: #ffffff;

            border:
                1px solid #bfdbfe;

            color: #2563eb;

            font-size: 10px;

            font-weight: 700;
        }

        .preview-subject {
            min-height: 45px;

            color: #0f172a;

            font-size: 16px;

            font-weight: 700;

            line-height: 1.4;

            word-break: break-word;
        }

        .preview-body {
            padding: 15px;
        }

        .preview-row {
            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 12px;

            padding:
                8px
                0;

            border-bottom:
                1px solid #f1f5f9;
        }

        .preview-row:last-child {
            border-bottom: none;
        }

        .preview-key {
            color: #64748b;

            font-size: 11px;
        }

        .preview-value {
            max-width: 175px;

            color: #334155;

            font-size: 11px;

            font-weight: 600;

            text-align: right;

            word-break: break-word;
        }

        /* =========================================================
           CURRENT RECORD
        ========================================================= */

        .side-info {
            margin-top: 16px;

            padding: 15px;

            border:
                1px solid #e2e8f0;

            border-radius: 9px;

            background: #f8fafc;
        }

        .side-info h4 {
            margin: 0 0 10px;

            color: #334155;

            font-size: 12px;
        }

        .side-info-row {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            padding:
                7px
                0;

            border-bottom:
                1px solid #e5e7eb;
        }

        .side-info-row:last-child {
            border-bottom: none;
        }

        .side-info-label {
            color: #64748b;

            font-size: 11px;
        }

        .side-info-value {
            max-width: 175px;

            color: #334155;

            font-size: 11px;

            font-weight: 700;

            text-align: right;

            word-break: break-word;
        }

        /* =========================================================
           GUIDE
        ========================================================= */

        .guide-card {
            margin-top: 16px;

            padding: 15px;

            border:
                1px solid #dbeafe;

            border-radius: 9px;

            background: #eff6ff;
        }

        .guide-card h4 {
            margin: 0 0 9px;

            color: #1e40af;

            font-size: 12px;
        }

        .guide-list {
            margin: 0;

            padding-left: 17px;

            color: #475569;

            font-size: 11px;

            line-height: 1.7;
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
        html.dark-mode .section-heading h2,
        html.dark-mode .side-card-header h3,
        html.dark-mode .preview-subject,
        html.dark-mode .summary-value,
        html.dark-mode .record-title,
        html.dark-mode .side-info h4 {
            color: #f8fafc;
        }

        html.dark-mode .page-header-left p,
        html.dark-mode .section-heading p,
        html.dark-mode .field-help,
        html.dark-mode .side-card-header p,
        html.dark-mode .preview-key,
        html.dark-mode .summary-label,
        html.dark-mode .record-subtitle,
        html.dark-mode .side-info-label {
            color: #94a3b8;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .record-bar,
        html.dark-mode .summary-card {
            background: #111827;

            border-color: #1f2937;

            box-shadow: none;
        }

        html.dark-mode .record-avatar,
        html.dark-mode .section-icon {
            background: #172554;

            color: #60a5fa;
        }

        html.dark-mode .activity-badge {
            background: #172554;

            color: #93c5fd;
        }

        html.dark-mode .btn-secondary {
            background: #1e293b;

            border-color: #334155;

            color: #e2e8f0;
        }

        html.dark-mode .btn-secondary:hover {
            background: #273449;
        }

        html.dark-mode .form-section,
        html.dark-mode .side-card-header {
            border-color: #1f2937;
        }

        html.dark-mode .form-group label {
            color: #cbd5e1;
        }

        html.dark-mode .form-control {
            background: #0f172a;

            border-color: #334155;

            color: #f8fafc;
        }

        html.dark-mode .form-control:focus {
            border-color: #60a5fa;

            box-shadow:
                0 0 0 3px
                rgba(96, 165, 250, 0.10);
        }

        html.dark-mode .type-label {
            background: #0f172a;

            border-color: #334155;
        }

        html.dark-mode .type-label strong {
            color: #cbd5e1;
        }

        html.dark-mode .type-item input:checked + .type-label {
            background: #172554;

            border-color: #60a5fa;

            box-shadow:
                0 0 0 1px #60a5fa;
        }

        html.dark-mode .type-item input:checked + .type-label strong {
            color: #93c5fd;
        }

        html.dark-mode .form-footer {
            background: #0f172a;
        }

        html.dark-mode .activity-preview {
            border-color: #334155;
        }

        html.dark-mode .preview-top {
            background:
                linear-gradient(
                    135deg,
                    #172554,
                    #111827
                );

            border-color: #334155;
        }

        html.dark-mode .preview-type {
            background: #0f172a;

            border-color: #334155;

            color: #60a5fa;
        }

        html.dark-mode .preview-value,
        html.dark-mode .side-info-value {
            color: #cbd5e1;
        }

        html.dark-mode .preview-row,
        html.dark-mode .side-info-row {
            border-color: #1f2937;
        }

        html.dark-mode .side-info {
            background: #0f172a;

            border-color: #1f2937;
        }

        html.dark-mode .guide-card {
            background: #172554;

            border-color: #1e3a8a;
        }

        html.dark-mode .guide-card h4 {
            color: #93c5fd;
        }

        html.dark-mode .guide-list {
            color: #cbd5e1;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .edit-layout {
                grid-template-columns: 1fr;
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

            .record-bar {
                flex-direction: column;

                align-items: flex-start;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;

                gap: 0;
            }

            .type-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .form-footer {
                flex-direction: column;

                align-items: stretch;
            }

            .form-footer .btn {
                width: 100%;
            }

        }

        @media (max-width: 480px) {

            .type-grid {
                grid-template-columns: 1fr;
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
                Edit Activity
            </span>

        </div>


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="page-header-left">

                <h1>
                    Edit Activity
                </h1>

                <p>
                    Update the activity type, details, date and related CRM records.
                </p>

            </div>

            <div class="header-actions">

                <a
                    href="view.php?id=<?php echo $id; ?>"
                    class="btn btn-secondary"
                >
                    View Activity
                </a>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Activity List
                </a>

            </div>

        </div>


        <!-- =====================================================
             RECORD BAR
        ====================================================== -->

        <div class="record-bar">

            <div class="record-left">

                <div class="record-avatar">

                    <?php
                    echo htmlspecialchars(
                        $initial
                    );
                    ?>

                </div>

                <div>

                    <div
                        class="record-title"
                        title="<?php
                        echo htmlspecialchars(
                            $activity_subject_display
                        );
                        ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $activity_subject_display
                        );
                        ?>

                    </div>

                    <div class="record-subtitle">

                        Activity #<?php echo (int) $activity["id"]; ?>

                        &nbsp;•&nbsp;

                        Editing existing record

                    </div>

                </div>

            </div>


            <div class="record-right">

                <span
                    class="activity-badge"
                    id="recordTypeBadge"
                >

                    <?php
                    echo htmlspecialchars(
                        $activity_type_label
                    );
                    ?>

                </span>

            </div>

        </div>


        <!-- =====================================================
             ERROR
        ====================================================== -->

        <?php if ($error !== ""): ?>

            <div class="error-box">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             SUMMARY
        ====================================================== -->

        <div class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Activity ID
                </div>

                <div class="summary-value">
                    #<?php echo (int) $activity["id"]; ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Activity Type
                </div>

                <div
                    class="summary-value"
                    id="summaryType"
                >

                    <?php
                    echo htmlspecialchars(
                        $activity_type_label
                    );
                    ?>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Created By
                </div>

                <div class="summary-value">

                    User #<?php
                    echo !empty($activity["created_by"])
                        ? (int) $activity["created_by"]
                        : "-";
                    ?>

                </div>

            </div>

        </div>


        <!-- =====================================================
             EDIT LAYOUT
        ====================================================== -->

        <div class="edit-layout">

            <!-- =================================================
                 FORM CARD
            ================================================== -->

            <div class="form-card">

                <form method="POST">

                    <!-- =========================================
                         ACTIVITY DETAILS
                    ========================================== -->

                    <div class="form-section">

                        <div class="section-heading">

                            <div class="section-icon">
                                📅
                            </div>

                            <div>

                                <h2>
                                    Activity Details
                                </h2>

                                <p>
                                    Update the activity type and subject.
                                </p>

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Activity Type
                                <span class="required">*</span>
                            </label>


                            <div class="type-grid">

                                <!-- Call -->

                                <div class="type-item">

                                    <input
                                        type="radio"
                                        id="type_call"
                                        name="type"
                                        value="Call"
                                        <?php

                                        echo $form_type === "Call"
                                            ? "checked"
                                            : "";

                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="type_call"
                                        class="type-label"
                                    >

                                        <span class="type-icon">
                                            ☎
                                        </span>

                                        <strong>
                                            Call
                                        </strong>

                                    </label>

                                </div>


                                <!-- Meeting -->

                                <div class="type-item">

                                    <input
                                        type="radio"
                                        id="type_meeting"
                                        name="type"
                                        value="Meeting"
                                        <?php

                                        echo $form_type === "Meeting"
                                            ? "checked"
                                            : "";

                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="type_meeting"
                                        class="type-label"
                                    >

                                        <span class="type-icon">
                                            👥
                                        </span>

                                        <strong>
                                            Meeting
                                        </strong>

                                    </label>

                                </div>


                                <!-- Email -->

                                <div class="type-item">

                                    <input
                                        type="radio"
                                        id="type_email"
                                        name="type"
                                        value="Email"
                                        <?php

                                        echo $form_type === "Email"
                                            ? "checked"
                                            : "";

                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="type_email"
                                        class="type-label"
                                    >

                                        <span class="type-icon">
                                            ✉
                                        </span>

                                        <strong>
                                            Email
                                        </strong>

                                    </label>

                                </div>


                                <!-- Note -->

                                <div class="type-item">

                                    <input
                                        type="radio"
                                        id="type_note"
                                        name="type"
                                        value="Note"
                                        <?php

                                        echo $form_type === "Note"
                                            ? "checked"
                                            : "";

                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="type_note"
                                        class="type-label"
                                    >

                                        <span class="type-icon">
                                            📝
                                        </span>

                                        <strong>
                                            Note
                                        </strong>

                                    </label>

                                </div>


                                <!-- Follow-up -->

                                <div class="type-item">

                                    <input
                                        type="radio"
                                        id="type_followup"
                                        name="type"
                                        value="Follow-up"
                                        <?php

                                        echo $form_type === "Follow-up"
                                            ? "checked"
                                            : "";

                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="type_followup"
                                        class="type-label"
                                    >

                                        <span class="type-icon">
                                            🔄
                                        </span>

                                        <strong>
                                            Follow-up
                                        </strong>

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="subject">

                                Subject

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $form_subject
                                );
                                ?>"
                                maxlength="255"
                                placeholder="Enter activity subject"
                                required
                                oninput="updatePreview()"
                            >

                        </div>

                    </div>


                    <!-- =========================================
                         DATE & DESCRIPTION
                    ========================================== -->

                    <div class="form-section">

                        <div class="section-heading">

                            <div class="section-icon">
                                🕒
                            </div>

                            <div>

                                <h2>
                                    Schedule & Description
                                </h2>

                                <p>
                                    Update the activity date and description.
                                </p>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="activity_date">
                                Activity Date
                            </label>

                            <input
                                type="datetime-local"
                                id="activity_date"
                                name="activity_date"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $form_activity_date
                                );
                                ?>"
                                onchange="updatePreview()"
                            >

                            <div class="field-help">
                                Update the date and time associated with this activity.
                            </div>

                        </div>


                        <div class="form-group">

                            <label for="description">
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                placeholder="Enter activity details..."
                            ><?php
                            echo htmlspecialchars(
                                $form_description
                            );
                            ?></textarea>

                        </div>

                    </div>


                    <!-- =========================================
                         RELATED RECORDS
                    ========================================== -->

                    <div class="form-section">

                        <div class="section-heading">

                            <div class="section-icon">
                                🔗
                            </div>

                            <div>

                                <h2>
                                    Related Records
                                </h2>

                                <p>
                                    Update the CRM records linked to this activity.
                                </p>

                            </div>

                        </div>


                        <div class="form-row">

                            <!-- Contact -->

                            <div class="form-group">

                                <label for="contact_id">
                                    Contact
                                </label>

                                <select
                                    id="contact_id"
                                    name="contact_id"
                                    class="form-control"
                                    onchange="updatePreview()"
                                >

                                    <option value="">
                                        -- Select Contact --
                                    </option>

                                    <?php foreach ($contacts as $contact): ?>

                                        <?php

                                        $contact_name = trim(
                                            ($contact["first_name"] ?? "")
                                            . " "
                                            . ($contact["last_name"] ?? "")
                                        );

                                        if ($contact_name === "") {

                                            $contact_name =
                                                "Contact #" .
                                                $contact["id"];
                                        }

                                        ?>

                                        <option
                                            value="<?php
                                            echo (int) $contact["id"];
                                            ?>"
                                            <?php

                                            echo (
                                                (string) $form_contact_id
                                                ===
                                                (string) $contact["id"]
                                            )
                                                ? "selected"
                                                : "";

                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $contact_name
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- Customer -->

                            <div class="form-group">

                                <label for="customer_id">
                                    Customer
                                </label>

                                <select
                                    id="customer_id"
                                    name="customer_id"
                                    class="form-control"
                                    onchange="updatePreview()"
                                >

                                    <option value="">
                                        -- Select Customer --
                                    </option>

                                    <?php foreach ($customers as $customer): ?>

                                        <option
                                            value="<?php
                                            echo (int) $customer["id"];
                                            ?>"
                                            <?php

                                            echo (
                                                (string) $form_customer_id
                                                ===
                                                (string) $customer["id"]
                                            )
                                                ? "selected"
                                                : "";

                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $customer["customer_code"]
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

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
                                onchange="updatePreview()"
                            >

                                <option value="">
                                    -- Select Deal --
                                </option>

                                <?php foreach ($deals as $deal): ?>

                                    <option
                                        value="<?php
                                        echo (int) $deal["id"];
                                        ?>"
                                        <?php

                                        echo (
                                            (string) $form_deal_id
                                            ===
                                            (string) $deal["id"]
                                        )
                                            ? "selected"
                                            : "";

                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $deal["title"]
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>


                    <!-- =========================================
                         FOOTER
                    ========================================== -->

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
                            ✓ Update Activity
                        </button>

                    </div>

                </form>

            </div>


            <!-- =================================================
                 SIDE PANEL
            ================================================== -->

            <div>

                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Live Activity Preview
                        </h3>

                        <p>
                            Review the updated activity before saving.
                        </p>

                    </div>


                    <div class="side-card-body">

                        <div class="activity-preview">

                            <div class="preview-top">

                                <div class="preview-top-row">

                                    <div
                                        class="preview-icon"
                                        id="previewIcon"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $initial
                                        );
                                        ?>
                                    </div>

                                    <span
                                        class="preview-type"
                                        id="previewType"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $activity_type_label
                                        );
                                        ?>
                                    </span>

                                </div>


                                <div
                                    class="preview-subject"
                                    id="previewSubject"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $activity_subject_display
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="preview-body">

                                <div class="preview-row">

                                    <span class="preview-key">
                                        Type
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewTypeText"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $activity_type_label
                                        );
                                        ?>

                                    </span>

                                </div>


                                <div class="preview-row">

                                    <span class="preview-key">
                                        Activity Date
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewDate"
                                    >

                                        <?php

                                        if (!empty($form_activity_date)) {

                                            echo htmlspecialchars(
                                                formatDateForPreview(
                                                    $form_activity_date
                                                )
                                            );

                                        } else {

                                            echo "Not set";

                                        }

                                        ?>

                                    </span>

                                </div>


                                <div class="preview-row">

                                    <span class="preview-key">
                                        Contact
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewContact"
                                    >
                                        Not linked
                                    </span>

                                </div>


                                <div class="preview-row">

                                    <span class="preview-key">
                                        Customer
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewCustomer"
                                    >
                                        Not linked
                                    </span>

                                </div>


                                <div class="preview-row">

                                    <span class="preview-key">
                                        Deal
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewDeal"
                                    >
                                        Not linked
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- Current Record -->

                        <div class="side-info">

                            <h4>
                                Current Record
                            </h4>


                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Activity ID
                                </span>

                                <span class="side-info-value">
                                    #<?php echo (int) $activity["id"]; ?>
                                </span>

                            </div>


                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Type
                                </span>

                                <span
                                    class="side-info-value"
                                    id="sideCurrentType"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $activity_type_label
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Created By
                                </span>

                                <span class="side-info-value">

                                    User #<?php
                                    echo !empty($activity["created_by"])
                                        ? (int) $activity["created_by"]
                                        : "-";
                                    ?>

                                </span>

                            </div>


                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Activity Date
                                </span>

                                <span class="side-info-value">

                                    <?php

                                    if (!empty($activity["activity_date"])) {

                                        $original_timestamp =
                                            strtotime(
                                                $activity["activity_date"]
                                            );

                                        if ($original_timestamp !== false) {

                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y, h:i A",
                                                    $original_timestamp
                                                )
                                            );

                                        } else {

                                            echo htmlspecialchars(
                                                $activity["activity_date"]
                                            );

                                        }

                                    } else {

                                        echo "Not set";

                                    }

                                    ?>

                                </span>

                            </div>

                        </div>


                        <!-- Guide -->

                        <div class="guide-card">

                            <h4>
                                Editing Guide
                            </h4>

                            <ul class="guide-list">

                                <li>
                                    Keep the activity type consistent with the actual interaction.
                                </li>

                                <li>
                                    Use a clear subject that explains the activity.
                                </li>

                                <li>
                                    Update the date and time when the activity schedule changes.
                                </li>

                                <li>
                                    Keep the description useful for future reference.
                                </li>

                                <li>
                                    Update linked Contact, Customer and Deal records when necessary.
                                </li>

                            </ul>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Get Selected Activity Type
|--------------------------------------------------------------------------
*/
function getSelectedActivityType() {

    const selected =
        document.querySelector(
            'input[name="type"]:checked'
        );

    if (!selected) {
        return "";
    }

    return selected.value;
}


/*
|--------------------------------------------------------------------------
| Get Selected Text
|--------------------------------------------------------------------------
*/
function getSelectedText(
    selectId,
    fallback
) {

    const element =
        document.getElementById(
            selectId
        );

    if (
        !element ||
        !element.value
    ) {
        return fallback;
    }

    const option =
        element.options[
            element.selectedIndex
        ];

    if (!option) {
        return fallback;
    }

    return option.text.trim();
}


/*
|--------------------------------------------------------------------------
| Format Date
|--------------------------------------------------------------------------
*/
function formatDateForPreview(
    value
) {

    if (!value) {
        return "Not set";
    }

    const parts =
        value.split("T");

    if (parts.length !== 2) {
        return value;
    }

    const datePart =
        parts[0];

    const timePart =
        parts[1];

    const datePieces =
        datePart.split("-");

    if (datePieces.length !== 3) {
        return value;
    }

    return (
        datePieces[2] +
        "/" +
        datePieces[1] +
        "/" +
        datePieces[0] +
        " " +
        timePart
    );
}


/*
|--------------------------------------------------------------------------
| Activity Icon
|--------------------------------------------------------------------------
*/
function getActivityIcon(
    type
) {

    const icons = {
        "Call": "☎",
        "Meeting": "👥",
        "Email": "✉",
        "Note": "📝",
        "Follow-up": "🔄"
    };

    return icons[type] || "A";
}


/*
|--------------------------------------------------------------------------
| Update Preview
|--------------------------------------------------------------------------
*/
function updatePreview() {

    const type =
        getSelectedActivityType();

    const subjectElement =
        document.getElementById(
            "subject"
        );

    const subject =
        subjectElement
            ? subjectElement.value.trim()
            : "";

    const activityDateElement =
        document.getElementById(
            "activity_date"
        );

    const activityDate =
        activityDateElement
            ? activityDateElement.value
            : "";


    /*
    |--------------------------------------------------------------------------
    | Subject
    |--------------------------------------------------------------------------
    */

    const previewSubject =
        document.getElementById(
            "previewSubject"
        );

    if (previewSubject) {

        previewSubject.textContent =
            subject !== ""
                ? subject
                : "Activity subject";
    }


    /*
    |--------------------------------------------------------------------------
    | Type
    |--------------------------------------------------------------------------
    */

    const displayType =
        type !== ""
            ? type
            : "Activity";

    const previewType =
        document.getElementById(
            "previewType"
        );

    if (previewType) {

        previewType.textContent =
            displayType;
    }


    const previewTypeText =
        document.getElementById(
            "previewTypeText"
        );

    if (previewTypeText) {

        previewTypeText.textContent =
            displayType;
    }


    const summaryType =
        document.getElementById(
            "summaryType"
        );

    if (summaryType) {

        summaryType.textContent =
            displayType;
    }


    const sideCurrentType =
        document.getElementById(
            "sideCurrentType"
        );

    if (sideCurrentType) {

        sideCurrentType.textContent =
            displayType;
    }


    const recordTypeBadge =
        document.getElementById(
            "recordTypeBadge"
        );

    if (recordTypeBadge) {

        recordTypeBadge.textContent =
            displayType;
    }


    /*
    |--------------------------------------------------------------------------
    | Icon
    |--------------------------------------------------------------------------
    */

    const previewIcon =
        document.getElementById(
            "previewIcon"
        );

    if (previewIcon) {

        previewIcon.textContent =
            getActivityIcon(type);
    }


    /*
    |--------------------------------------------------------------------------
    | Date
    |--------------------------------------------------------------------------
    */

    const previewDate =
        document.getElementById(
            "previewDate"
        );

    if (previewDate) {

        previewDate.textContent =
            formatDateForPreview(
                activityDate
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    */

    const previewContact =
        document.getElementById(
            "previewContact"
        );

    if (previewContact) {

        previewContact.textContent =
            getSelectedText(
                "contact_id",
                "Not linked"
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    const previewCustomer =
        document.getElementById(
            "previewCustomer"
        );

    if (previewCustomer) {

        previewCustomer.textContent =
            getSelectedText(
                "customer_id",
                "Not linked"
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Deal
    |--------------------------------------------------------------------------
    */

    const previewDeal =
        document.getElementById(
            "previewDeal"
        );

    if (previewDeal) {

        previewDeal.textContent =
            getSelectedText(
                "deal_id",
                "Not linked"
            );
    }

}


/*
|--------------------------------------------------------------------------
| Initial Preview
|--------------------------------------------------------------------------
*/
document.addEventListener(
    "DOMContentLoaded",
    function () {

        updatePreview();

    }
);

</script>

</body>

</html>