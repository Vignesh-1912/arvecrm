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
| Validate Task ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/
$allowed_priorities = [
    "low"    => "Low",
    "medium" => "Medium",
    "high"   => "High",
    "urgent" => "Urgent"
];

$allowed_statuses = [
    "pending"     => "Pending",
    "in-progress" => "In Progress",
    "completed"   => "Completed",
    "cancelled"   => "Cancelled"
];

/*
|--------------------------------------------------------------------------
| Get Task
|--------------------------------------------------------------------------
*/
$task_stmt = $conn->prepare("
    SELECT *
    FROM tasks
    WHERE id = :id
    LIMIT 1
");

$task_stmt->execute([
    ":id" => $id
]);

$task = $task_stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Users
|--------------------------------------------------------------------------
*/
$user_stmt = $conn->prepare("
    SELECT id, name
    FROM users
    WHERE status = 1
    ORDER BY name ASC
");

$user_stmt->execute();

$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Contacts
|--------------------------------------------------------------------------
*/
$contact_stmt = $conn->prepare("
    SELECT id, first_name, last_name
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
    SELECT id, customer_code
    FROM customers
    ORDER BY id ASC
");

$customer_stmt->execute();

$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Deals
|--------------------------------------------------------------------------
*/
$deal_stmt = $conn->prepare("
    SELECT id, title
    FROM deals
    ORDER BY id ASC
");

$deal_stmt->execute();

$deals = $deal_stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");

    $due_date = !empty($_POST["due_date"])
        ? $_POST["due_date"]
        : null;

    $priority = $_POST["priority"] ?? "medium";
    $status = $_POST["status"] ?? "pending";

    $assigned_to = !empty($_POST["assigned_to"])
        ? (int) $_POST["assigned_to"]
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
    if ($title === "") {

        $error = "Task title is required.";

    } elseif (!array_key_exists($priority, $allowed_priorities)) {

        $error = "Invalid task priority.";

    } elseif (!array_key_exists($status, $allowed_statuses)) {

        $error = "Invalid task status.";

    } else {

        try {

            $update_sql = "
                UPDATE tasks
                SET
                    title = :title,
                    description = :description,
                    due_date = :due_date,
                    priority = :priority,
                    status = :status,
                    assigned_to = :assigned_to,
                    contact_id = :contact_id,
                    customer_id = :customer_id,
                    deal_id = :deal_id
                WHERE id = :id
            ";

            $update_stmt = $conn->prepare($update_sql);

            $update_stmt->execute([
                ":title"       => $title,
                ":description" => $description !== "" ? $description : null,
                ":due_date"    => $due_date,
                ":priority"    => $priority,
                ":status"      => $status,
                ":assigned_to" => $assigned_to,
                ":contact_id"  => $contact_id,
                ":customer_id" => $customer_id,
                ":deal_id"     => $deal_id,
                ":id"          => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            $error = "Unable to update task: " . $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/
$form_title = $_POST["title"] ?? $task["title"] ?? "";

$form_description =
    $_POST["description"]
    ?? $task["description"]
    ?? "";

$form_due_date =
    $_POST["due_date"]
    ?? $task["due_date"]
    ?? "";

$form_priority =
    $_POST["priority"]
    ?? $task["priority"]
    ?? "medium";

$form_status =
    $_POST["status"]
    ?? $task["status"]
    ?? "pending";

$form_assigned_to =
    $_POST["assigned_to"]
    ?? $task["assigned_to"]
    ?? "";

$form_contact_id =
    $_POST["contact_id"]
    ?? $task["contact_id"]
    ?? "";

$form_customer_id =
    $_POST["customer_id"]
    ?? $task["customer_id"]
    ?? "";

$form_deal_id =
    $_POST["deal_id"]
    ?? $task["deal_id"]
    ?? "";

/*
|--------------------------------------------------------------------------
| Display Values
|--------------------------------------------------------------------------
*/
$priority_label =
    $allowed_priorities[$form_priority]
    ?? ucfirst($form_priority);

$status_label =
    $allowed_statuses[$form_status]
    ?? ucfirst($form_status);

$task_title_display =
    trim($form_title) !== ""
    ? $form_title
    : "Untitled Task";

$initial = strtoupper(
    substr($task_title_display, 0, 1)
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
        Edit Task | CRM
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

        /* Breadcrumb */

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

        /* Header */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }

        .page-header-left h1 {
            margin: 0 0 7px;
            color: #0f172a;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.3px;
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

        .btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 15px;
            border-radius: 8px;
            border: 1px solid transparent;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #dbe1e8;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        /* Current Record Bar */

        .record-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 15px 18px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
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
            margin: 0 0 3px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
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

        /* Badges */

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-low {
            background: #dcfce7;
            color: #166534;
        }

        .priority-medium {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .priority-high {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-urgent {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Error */

        .error-box {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 9px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 13px;
            font-weight: 500;
        }

        /* Summary */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-card {
            padding: 15px 16px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
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
        }

        /* Layout */

        .edit-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 20px;
            align-items: start;
        }

        .form-card,
        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        /* Sections */

        .form-section {
            padding: 22px;
            border-bottom: 1px solid #eef2f7;
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

        /* Form */

        .form-group {
            margin-bottom: 17px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
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
            padding: 0 12px;
            border: 1px solid #dbe1e8;
            border-radius: 8px;
            background: #ffffff;
            color: #0f172a;
            font-size: 13px;
            outline: none;
            transition: 0.2s ease;
        }

        textarea.form-control {
            min-height: 115px;
            height: auto;
            padding: 11px 12px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        /* Choice Cards */

        .choice-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
        }

        .choice-item {
            position: relative;
        }

        .choice-item input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .choice-label {
            display: block;
            padding: 11px 8px;
            border: 1px solid #dbe1e8;
            border-radius: 8px;
            background: #ffffff;
            text-align: center;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .choice-label strong {
            display: block;
            color: #334155;
            font-size: 12px;
        }

        .choice-label span {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 10px;
        }

        .choice-item input:checked + .choice-label {
            box-shadow: 0 0 0 1px #2563eb;
            border-color: #2563eb;
            background: #eff6ff;
        }

        .priority-low input:checked + .choice-label {
            box-shadow: 0 0 0 1px #16a34a;
            border-color: #16a34a;
            background: #f0fdf4;
        }

        .priority-medium input:checked + .choice-label {
            box-shadow: 0 0 0 1px #2563eb;
            border-color: #2563eb;
            background: #eff6ff;
        }

        .priority-high input:checked + .choice-label {
            box-shadow: 0 0 0 1px #d97706;
            border-color: #d97706;
            background: #fffbeb;
        }

        .priority-urgent input:checked + .choice-label {
            box-shadow: 0 0 0 1px #dc2626;
            border-color: #dc2626;
            background: #fef2f2;
        }

        /* Status */

        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
        }

        .status-item {
            position: relative;
        }

        .status-item input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-label {
            display: block;
            padding: 11px 8px;
            border: 1px solid #dbe1e8;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .status-item input:checked + .status-label {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
            box-shadow: 0 0 0 1px #2563eb;
        }

        /* Footer */

        .form-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 9px;
            padding: 18px 22px;
            background: #f8fafc;
            border-radius: 0 0 12px 12px;
        }

        /* Side */

        .side-card {
            overflow: hidden;
        }

        .side-card-header {
            padding: 18px;
            border-bottom: 1px solid #eef2f7;
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

        /* Preview */

        .task-preview {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .preview-top {
            padding: 16px;
            background: linear-gradient(
                135deg,
                #eff6ff,
                #f8fafc
            );
            border-bottom: 1px solid #e2e8f0;
        }

        .preview-badge-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .preview-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .preview-title {
            min-height: 44px;
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
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
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

        /* Side Info */

        .side-info {
            margin-top: 16px;
            padding: 15px;
            border-radius: 9px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
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
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .side-info-row:last-child {
            border-bottom: none;
        }

        .side-info-label {
            color: #64748b;
            font-size: 11px;
        }

        .side-info-value {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            text-align: right;
        }

        /* Guide */

        .guide-card {
            margin-top: 16px;
            padding: 15px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 9px;
        }

        .guide-card h4 {
            margin: 0 0 8px;
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

        /* Dark Mode */

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
        html.dark-mode .preview-title,
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

        html.dark-mode .btn-secondary {
            background: #1e293b;
            color: #e2e8f0;
            border-color: #334155;
        }

        html.dark-mode .btn-secondary:hover {
            background: #273449;
        }

        html.dark-mode .form-section {
            border-color: #1f2937;
        }

        html.dark-mode .form-group label {
            color: #cbd5e1;
        }

        html.dark-mode .form-control {
            background: #0f172a;
            color: #f8fafc;
            border-color: #334155;
        }

        html.dark-mode .form-control:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.10);
        }

        html.dark-mode .choice-label,
        html.dark-mode .status-label {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .choice-label strong,
        html.dark-mode .status-label {
            color: #cbd5e1;
        }

        html.dark-mode .choice-label span {
            color: #64748b;
        }

        html.dark-mode .priority-low input:checked + .choice-label {
            background: #052e16;
        }

        html.dark-mode .priority-medium input:checked + .choice-label {
            background: #172554;
        }

        html.dark-mode .priority-high input:checked + .choice-label {
            background: #451a03;
        }

        html.dark-mode .priority-urgent input:checked + .choice-label {
            background: #450a0a;
        }

        html.dark-mode .status-item input:checked + .status-label {
            background: #172554;
        }

        html.dark-mode .form-footer {
            background: #0f172a;
        }

        html.dark-mode .task-preview {
            border-color: #334155;
        }

        html.dark-mode .preview-top {
            background: linear-gradient(
                135deg,
                #172554,
                #111827
            );
            border-color: #334155;
        }

        html.dark-mode .preview-value,
        html.dark-mode .side-info-value {
            color: #cbd5e1;
        }

        html.dark-mode .preview-row {
            border-color: #1f2937;
        }

        html.dark-mode .side-info {
            background: #0f172a;
            border-color: #1f2937;
        }

        html.dark-mode .side-info-row {
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

        /* Responsive */

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

            .choice-grid,
            .status-grid {
                grid-template-columns: repeat(
                    2,
                    minmax(0, 1fr)
                );
            }

            .form-footer {
                flex-direction: column;
                align-items: stretch;
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

    <div class="page-wrap">

        <!-- Breadcrumb -->

        <div class="breadcrumb">

            <a href="../dashboard/index.php">
                Dashboard
            </a>

            <span>/</span>

            <a href="index.php">
                Tasks
            </a>

            <span>/</span>

            <span>
                Edit Task
            </span>

        </div>

        <!-- Header -->

        <div class="page-header">

            <div class="page-header-left">

                <h1>Edit Task</h1>

                <p>
                    Update task details, assignment, status and related records.
                </p>

            </div>

            <div class="header-actions">

                <a
                    href="view.php?id=<?php echo $id; ?>"
                    class="btn btn-secondary"
                >
                    View Task
                </a>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Task List
                </a>

            </div>

        </div>

        <!-- Current Record -->

        <div class="record-bar">

            <div class="record-left">

                <div class="record-avatar">

                    <?php echo htmlspecialchars($initial); ?>

                </div>

                <div>

                    <div class="record-title">

                        <?php
                        echo htmlspecialchars(
                            $task_title_display
                        );
                        ?>

                    </div>

                    <div class="record-subtitle">

                        Task #<?php echo (int) $task["id"]; ?>

                        &nbsp;•&nbsp;

                        Editing existing record

                    </div>

                </div>

            </div>

            <div class="record-right">

                <span
                    class="badge status-<?php echo htmlspecialchars(
                        preg_replace(
                            "/[^a-z0-9\-]/i",
                            "-",
                            $form_status
                        )
                    ); ?>"
                >
                    <?php echo htmlspecialchars($status_label); ?>
                </span>

                <span
                    class="badge priority-<?php echo htmlspecialchars($form_priority); ?>"
                >
                    <?php echo htmlspecialchars($priority_label); ?>
                </span>

            </div>

        </div>

        <!-- Error -->

        <?php if ($error !== ""): ?>

            <div class="error-box">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>

        <!-- Summary -->

        <div class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Task ID
                </div>

                <div class="summary-value">
                    #<?php echo (int) $task["id"]; ?>
                </div>

            </div>

            <div class="summary-card">

                <div class="summary-label">
                    Current Status
                </div>

                <div class="summary-value">
                    <?php echo htmlspecialchars($status_label); ?>
                </div>

            </div>

            <div class="summary-card">

                <div class="summary-label">
                    Current Priority
                </div>

                <div class="summary-value">
                    <?php echo htmlspecialchars($priority_label); ?>
                </div>

            </div>

        </div>

        <!-- Main Layout -->

        <div class="edit-layout">

            <!-- Form -->

            <div class="form-card">

                <form method="POST">

                    <!-- Task Details -->

                    <div class="form-section">

                        <div class="section-heading">

                            <div class="section-icon">
                                ✓
                            </div>

                            <div>

                                <h2>
                                    Task Details
                                </h2>

                                <p>
                                    Update the task title and description.
                                </p>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="title">
                                Task Title
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $form_title
                                );
                                ?>"
                                maxlength="255"
                                placeholder="Enter task title"
                                required
                                oninput="updatePreview()"
                            >

                        </div>

                        <div class="form-group">

                            <label for="description">
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                placeholder="Add task details, instructions or notes..."
                            ><?php
                            echo htmlspecialchars(
                                $form_description
                            );
                            ?></textarea>

                            <div class="field-help">
                                Keep the description clear so the assigned user understands what needs to be completed.
                            </div>

                        </div>

                    </div>

                    <!-- Schedule -->

                    <div class="form-section">

                        <div class="section-heading">

                            <div class="section-icon">
                                📅
                            </div>

                            <div>

                                <h2>
                                    Schedule & Priority
                                </h2>

                                <p>
                                    Manage the task deadline and priority.
                                </p>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="due_date">
                                Due Date
                            </label>

                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $form_due_date
                                );
                                ?>"
                                onchange="updatePreview()"
                            >

                        </div>

                        <div class="form-group">

                            <label>
                                Priority
                            </label>

                            <div class="choice-grid">

                                <div class="choice-item priority-low">

                                    <input
                                        type="radio"
                                        id="priority_low"
                                        name="priority"
                                        value="low"
                                        <?php
                                        echo ($form_priority === "low")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="priority_low"
                                        class="choice-label"
                                    >
                                        <strong>Low</strong>
                                        <span>Normal</span>
                                    </label>

                                </div>

                                <div class="choice-item priority-medium">

                                    <input
                                        type="radio"
                                        id="priority_medium"
                                        name="priority"
                                        value="medium"
                                        <?php
                                        echo ($form_priority === "medium")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="priority_medium"
                                        class="choice-label"
                                    >
                                        <strong>Medium</strong>
                                        <span>Standard</span>
                                    </label>

                                </div>

                                <div class="choice-item priority-high">

                                    <input
                                        type="radio"
                                        id="priority_high"
                                        name="priority"
                                        value="high"
                                        <?php
                                        echo ($form_priority === "high")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="priority_high"
                                        class="choice-label"
                                    >
                                        <strong>High</strong>
                                        <span>Important</span>
                                    </label>

                                </div>

                                <div class="choice-item priority-urgent">

                                    <input
                                        type="radio"
                                        id="priority_urgent"
                                        name="priority"
                                        value="urgent"
                                        <?php
                                        echo ($form_priority === "urgent")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="priority_urgent"
                                        class="choice-label"
                                    >
                                        <strong>Urgent</strong>
                                        <span>Immediate</span>
                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Status & Assignment -->

                    <div class="form-section">

                        <div class="section-heading">

                            <div class="section-icon">
                                👤
                            </div>

                            <div>

                                <h2>
                                    Status & Assignment
                                </h2>

                                <p>
                                    Update the workflow status and assigned user.
                                </p>

                            </div>

                        </div>

                        <div class="form-group">

                            <label>
                                Task Status
                            </label>

                            <div class="status-grid">

                                <div class="status-item">

                                    <input
                                        type="radio"
                                        id="status_pending"
                                        name="status"
                                        value="pending"
                                        <?php
                                        echo ($form_status === "pending")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="status_pending"
                                        class="status-label"
                                    >
                                        Pending
                                    </label>

                                </div>

                                <div class="status-item">

                                    <input
                                        type="radio"
                                        id="status_progress"
                                        name="status"
                                        value="in-progress"
                                        <?php
                                        echo ($form_status === "in-progress")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="status_progress"
                                        class="status-label"
                                    >
                                        In Progress
                                    </label>

                                </div>

                                <div class="status-item">

                                    <input
                                        type="radio"
                                        id="status_completed"
                                        name="status"
                                        value="completed"
                                        <?php
                                        echo ($form_status === "completed")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="status_completed"
                                        class="status-label"
                                    >
                                        Completed
                                    </label>

                                </div>

                                <div class="status-item">

                                    <input
                                        type="radio"
                                        id="status_cancelled"
                                        name="status"
                                        value="cancelled"
                                        <?php
                                        echo ($form_status === "cancelled")
                                            ? "checked"
                                            : "";
                                        ?>
                                        onchange="updatePreview()"
                                    >

                                    <label
                                        for="status_cancelled"
                                        class="status-label"
                                    >
                                        Cancelled
                                    </label>

                                </div>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="assigned_to">
                                Assigned To
                            </label>

                            <select
                                id="assigned_to"
                                name="assigned_to"
                                class="form-control"
                                onchange="updatePreview()"
                            >

                                <option value="">
                                    -- Select User --
                                </option>

                                <?php foreach ($users as $user): ?>

                                    <option
                                        value="<?php echo (int) $user["id"]; ?>"
                                        <?php
                                        echo (
                                            (string) $form_assigned_to
                                            ===
                                            (string) $user["id"]
                                        )
                                            ? "selected"
                                            : "";
                                        ?>
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

                    <!-- Related Records -->

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
                                    Connect this task with CRM records.
                                </p>

                            </div>

                        </div>

                        <div class="form-row">

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
                                            value="<?php echo (int) $contact["id"]; ?>"
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
                                            value="<?php echo (int) $customer["id"]; ?>"
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
                                        value="<?php echo (int) $deal["id"]; ?>"
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

                    <!-- Footer -->

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
                            ✓ Update Task
                        </button>

                    </div>

                </form>

            </div>

            <!-- Side Panel -->

            <div>

                <!-- Preview -->

                <div class="side-card">

                    <div class="side-card-header">

                        <h3>
                            Live Task Preview
                        </h3>

                        <p>
                            The preview updates as you change the task.
                        </p>

                    </div>

                    <div class="side-card-body">

                        <div class="task-preview">

                            <div class="preview-top">

                                <div class="preview-badge-row">

                                    <span class="preview-label">
                                        Task
                                    </span>

                                    <span
                                        class="badge priority-<?php echo htmlspecialchars($form_priority); ?>"
                                        id="previewPriority"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $priority_label
                                        );
                                        ?>
                                    </span>

                                </div>

                                <div
                                    class="preview-title"
                                    id="previewTitle"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $task_title_display
                                    );
                                    ?>
                                </div>

                            </div>

                            <div class="preview-body">

                                <div class="preview-row">

                                    <span class="preview-key">
                                        Status
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewStatus"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $status_label
                                        );
                                        ?>
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span class="preview-key">
                                        Due Date
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewDueDate"
                                    >

                                        <?php

                                        if (!empty($form_due_date)) {

                                            $date_timestamp =
                                                strtotime(
                                                    $form_due_date
                                                );

                                            if ($date_timestamp !== false) {

                                                echo htmlspecialchars(
                                                    date(
                                                        "d/m/Y",
                                                        $date_timestamp
                                                    )
                                                );

                                            } else {

                                                echo htmlspecialchars(
                                                    $form_due_date
                                                );
                                            }

                                        } else {

                                            echo "Not set";
                                        }

                                        ?>

                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span class="preview-key">
                                        Assigned To
                                    </span>

                                    <span
                                        class="preview-value"
                                        id="previewAssigned"
                                    >
                                        Not assigned
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

                        <!-- Current Information -->

                        <div class="side-info">

                            <h4>
                                Current Record
                            </h4>

                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Task ID
                                </span>

                                <span class="side-info-value">
                                    #<?php echo (int) $task["id"]; ?>
                                </span>

                            </div>

                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Status
                                </span>

                                <span class="side-info-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $status_label
                                    );
                                    ?>

                                </span>

                            </div>

                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Priority
                                </span>

                                <span class="side-info-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $priority_label
                                    );
                                    ?>

                                </span>

                            </div>

                            <div class="side-info-row">

                                <span class="side-info-label">
                                    Due Date
                                </span>

                                <span class="side-info-value">

                                    <?php

                                    if (!empty($task["due_date"])) {

                                        $original_due_timestamp =
                                            strtotime(
                                                $task["due_date"]
                                            );

                                        if (
                                            $original_due_timestamp
                                            !== false
                                        ) {

                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y",
                                                    $original_due_timestamp
                                                )
                                            );

                                        } else {

                                            echo htmlspecialchars(
                                                $task["due_date"]
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
                                    Update the title when the task requirement changes.
                                </li>

                                <li>
                                    Keep the description clear and actionable.
                                </li>

                                <li>
                                    Change priority when the business urgency changes.
                                </li>

                                <li>
                                    Keep the task status aligned with its actual progress.
                                </li>

                                <li>
                                    Update the assigned user when responsibility changes.
                                </li>

                                <li>
                                    Use related records to maintain CRM context.
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

function getSelectedText(selectId, fallback) {

    const element = document.getElementById(selectId);

    if (!element || !element.value) {
        return fallback;
    }

    const option =
        element.options[element.selectedIndex];

    if (!option) {
        return fallback;
    }

    return option.text.trim();
}


function getSelectedRadio(name, fallback) {

    const element = document.querySelector(
        'input[name="' + name + '"]:checked'
    );

    return element
        ? element.value
        : fallback;
}


function formatPriority(priority) {

    const labels = {
        "low": "Low",
        "medium": "Medium",
        "high": "High",
        "urgent": "Urgent"
    };

    return labels[priority] || "Medium";
}


function formatStatus(status) {

    const labels = {
        "pending": "Pending",
        "in-progress": "In Progress",
        "completed": "Completed",
        "cancelled": "Cancelled"
    };

    return labels[status] || "Pending";
}


function formatDate(dateValue) {

    if (!dateValue) {
        return "Not set";
    }

    const parts = dateValue.split("-");

    if (parts.length !== 3) {
        return dateValue;
    }

    return (
        parts[2] +
        "/" +
        parts[1] +
        "/" +
        parts[0]
    );
}


function updatePreview() {

    const titleElement =
        document.getElementById("title");

    const title =
        titleElement
            ? titleElement.value.trim()
            : "";

    const priority =
        getSelectedRadio(
            "priority",
            "medium"
        );

    const status =
        getSelectedRadio(
            "status",
            "pending"
        );

    const dueDateElement =
        document.getElementById("due_date");

    const dueDate =
        dueDateElement
            ? dueDateElement.value
            : "";

    const previewTitle =
        document.getElementById(
            "previewTitle"
        );

    if (previewTitle) {

        previewTitle.textContent =
            title !== ""
                ? title
                : "Task title";
    }

    const previewPriority =
        document.getElementById(
            "previewPriority"
        );

    if (previewPriority) {

        previewPriority.textContent =
            formatPriority(priority);

        previewPriority.className =
            "badge priority-" + priority;
    }

    const previewStatus =
        document.getElementById(
            "previewStatus"
        );

    if (previewStatus) {

        previewStatus.textContent =
            formatStatus(status);
    }

    const previewDueDate =
        document.getElementById(
            "previewDueDate"
        );

    if (previewDueDate) {

        previewDueDate.textContent =
            formatDate(dueDate);
    }

    const previewAssigned =
        document.getElementById(
            "previewAssigned"
        );

    if (previewAssigned) {

        previewAssigned.textContent =
            getSelectedText(
                "assigned_to",
                "Not assigned"
            );
    }

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


document.addEventListener(
    "DOMContentLoaded",
    function () {
        updatePreview();
    }
);

</script>

</body>

</html>