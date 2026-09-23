<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

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
| Load Task
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        tasks.*,
        users.name AS assigned_name,
        contacts.first_name,
        contacts.last_name,
        contacts.email AS contact_email,
        contacts.phone AS contact_phone,
        customers.customer_code,
        deals.title AS deal_title
    FROM tasks
    LEFT JOIN users
        ON tasks.assigned_to = users.id
    LEFT JOIN contacts
        ON tasks.contact_id = contacts.id
    LEFT JOIN customers
        ON tasks.customer_id = customers.id
    LEFT JOIN deals
        ON tasks.deal_id = deals.id
    WHERE tasks.id = :id
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id" => $id
]);

$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Prepare Display Values
|--------------------------------------------------------------------------
*/
$contact_name = trim(
    ($task["first_name"] ?? "") . " " .
    ($task["last_name"] ?? "")
);

if ($contact_name === "") {
    $contact_name = "";
}

$task_title = trim($task["title"] ?? "");

if ($task_title === "") {
    $task_title = "Untitled Task";
}

$priority = strtolower(trim($task["priority"] ?? "medium"));
$status = strtolower(trim($task["status"] ?? "pending"));

$priority_labels = [
    "low"    => "Low",
    "medium" => "Medium",
    "high"   => "High",
    "urgent" => "Urgent"
];

$status_labels = [
    "pending"     => "Pending",
    "in-progress" => "In Progress",
    "completed"   => "Completed",
    "cancelled"   => "Cancelled"
];

$priority_label = $priority_labels[$priority] ?? ucfirst($priority);
$status_label = $status_labels[$status] ?? ucfirst($status);

$status_class = "status-" . preg_replace(
    "/[^a-z0-9\-]/i",
    "-",
    $status
);

/*
|--------------------------------------------------------------------------
| Format Dates
|--------------------------------------------------------------------------
*/
function formatTaskDate($date)
{
    if (empty($date)) {
        return "Not set";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    return date("d M Y", $timestamp);
}

function formatTaskDateTime($date)
{
    if (empty($date)) {
        return "Not available";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    return date("d M Y, h:i A", $timestamp);
}

$due_date_display = formatTaskDate($task["due_date"] ?? null);
$created_display = formatTaskDateTime($task["created_at"] ?? null);
$updated_display = formatTaskDateTime($task["updated_at"] ?? null);

/*
|--------------------------------------------------------------------------
| Initial
|--------------------------------------------------------------------------
*/
$initial = strtoupper(substr($task_title, 0, 1));

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
        <?php echo htmlspecialchars($task_title); ?> | CRM
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
            margin-bottom: 24px;
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

        /* Hero */

        .task-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .task-avatar {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #60a5fa
            );
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            box-shadow: 0 7px 18px rgba(37, 99, 235, 0.20);
        }

        .hero-info {
            min-width: 0;
        }

        .hero-info h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 21px;
            font-weight: 700;
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
            gap: 9px;
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

        /* Main Grid */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 20px;
            align-items: start;
        }

        .main-column,
        .side-column {
            min-width: 0;
        }

        /* Cards */

        .card {
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .card:last-child {
            margin-bottom: 0;
        }

        .card-header {
            padding: 17px 19px;
            border-bottom: 1px solid #eef2f7;
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
            padding: 19px;
        }

        /* Summary Cards */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-card {
            padding: 16px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 2px 7px rgba(15, 23, 42, 0.03);
        }

        .summary-label {
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }

        .summary-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.35;
            word-break: break-word;
        }

        /* Detail Grid */

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
        }

        .detail-item {
            min-width: 0;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-item:nth-last-child(-n+2) {
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

        /* Description */

        .description-box {
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
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

        /* Contact */

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
            line-height: 1.5;
        }

        .contact-details a {
            color: #2563eb;
            text-decoration: none;
        }

        /* Side Info */

        .side-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .side-stat:last-child {
            border-bottom: none;
        }

        .side-stat-label {
            color: #64748b;
            font-size: 11px;
        }

        .side-stat-value {
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }

        /* Quick Actions */

        .quick-actions {
            display: grid;
            gap: 8px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 12px;
            border: 1px solid #e2e8f0;
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
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
        }

        /* Timeline */

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
            margin-bottom: 17px;
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
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 1px #bfdbfe;
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

        /* Bottom Actions */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 9px;
            margin-top: 20px;
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
        html.dark-mode .timeline-value,
        html.dark-mode .side-stat-label {
            color: #94a3b8;
        }

        html.dark-mode .task-hero,
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
            color: #e2e8f0;
            border-color: #334155;
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

        /* Responsive */

        @media (max-width: 1050px) {

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .side-column {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

            .task-hero {
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

            .detail-item:nth-last-child(-n+2) {
                border-bottom: 1px solid #f1f5f9;
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

        @media (max-width: 500px) {

            .hero-left {
                align-items: flex-start;
            }

            .task-avatar {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
                font-size: 20px;
            }

            .hero-info h2 {
                font-size: 18px;
            }

            .hero-right {
                gap: 6px;
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
                View Task
            </span>

        </div>

        <!-- Header -->

        <div class="page-header">

            <div class="page-header-left">

                <h1>Task Details</h1>

                <p>
                    View task information, assignment and related CRM records.
                </p>

            </div>

            <div class="header-actions">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    ← Task List
                </a>

                <a
                    href="edit.php?id=<?php echo (int) $task["id"]; ?>"
                    class="btn btn-primary"
                >
                    ✎ Edit Task
                </a>

            </div>

        </div>

        <!-- Hero -->

        <div class="task-hero">

            <div class="hero-left">

                <div class="task-avatar">
                    <?php echo htmlspecialchars($initial); ?>
                </div>

                <div class="hero-info">

                    <h2>
                        <?php echo htmlspecialchars($task_title); ?>
                    </h2>

                    <div class="hero-meta">

                        <span class="hero-meta-item">
                            ✓ Task #<?php echo (int) $task["id"]; ?>
                        </span>

                        <span>•</span>

                        <span class="hero-meta-item">
                            📅 <?php echo htmlspecialchars($due_date_display); ?>
                        </span>

                        <?php if (!empty($task["assigned_name"])): ?>

                            <span>•</span>

                            <span class="hero-meta-item">
                                👤
                                <?php echo htmlspecialchars($task["assigned_name"]); ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <div class="hero-right">

                <span
                    class="badge <?php echo htmlspecialchars($status_class); ?>"
                >
                    <?php echo htmlspecialchars($status_label); ?>
                </span>

                <span
                    class="badge priority-<?php echo htmlspecialchars($priority); ?>"
                >
                    <?php echo htmlspecialchars($priority_label); ?>
                </span>

            </div>

        </div>

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
                    Priority
                </div>

                <div class="summary-value">

                    <span
                        class="badge priority-<?php echo htmlspecialchars($priority); ?>"
                    >
                        <?php echo htmlspecialchars($priority_label); ?>
                    </span>

                </div>

            </div>

            <div class="summary-card">

                <div class="summary-label">
                    Due Date
                </div>

                <div class="summary-value">
                    <?php echo htmlspecialchars($due_date_display); ?>
                </div>

            </div>

            <div class="summary-card">

                <div class="summary-label">
                    Assigned To
                </div>

                <div class="summary-value">

                    <?php
                    echo !empty($task["assigned_name"])
                        ? htmlspecialchars($task["assigned_name"])
                        : "Unassigned";
                    ?>

                </div>

            </div>

        </div>

        <!-- Content -->

        <div class="content-grid">

            <!-- Main Column -->

            <div class="main-column">

                <!-- Task Information -->

                <div class="card">

                    <div class="card-header">

                        <h3>Task Information</h3>

                        <p>
                            Main task details and scheduling information.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="details-grid">

                            <div class="detail-item">

                                <div class="detail-label">
                                    Task ID
                                </div>

                                <div class="detail-value">
                                    #<?php echo (int) $task["id"]; ?>
                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Task Title
                                </div>

                                <div class="detail-value">
                                    <?php echo htmlspecialchars($task_title); ?>
                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Priority
                                </div>

                                <div class="detail-value">

                                    <span
                                        class="badge priority-<?php echo htmlspecialchars($priority); ?>"
                                    >
                                        <?php echo htmlspecialchars($priority_label); ?>
                                    </span>

                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Status
                                </div>

                                <div class="detail-value">

                                    <span
                                        class="badge <?php echo htmlspecialchars($status_class); ?>"
                                    >
                                        <?php echo htmlspecialchars($status_label); ?>
                                    </span>

                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Due Date
                                </div>

                                <div class="detail-value">
                                    <?php echo htmlspecialchars($due_date_display); ?>
                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Assigned To
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo !empty($task["assigned_name"])
                                        ? htmlspecialchars($task["assigned_name"])
                                        : "Unassigned";
                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Description -->

                <div class="card">

                    <div class="card-header">

                        <h3>Description</h3>

                        <p>
                            Task instructions and additional information.
                        </p>

                    </div>

                    <div class="card-body">

                        <?php if (!empty($task["description"])): ?>

                            <div class="description-box">
                                <?php
                                echo htmlspecialchars(
                                    $task["description"]
                                );
                                ?>
                            </div>

                        <?php else: ?>

                            <div class="empty-text">
                                No description has been added to this task.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- Related Records -->

                <div class="card">

                    <div class="card-header">

                        <h3>Related Records</h3>

                        <p>
                            CRM records associated with this task.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="details-grid">

                            <div class="detail-item">

                                <div class="detail-label">
                                    Contact
                                </div>

                                <div class="detail-value">

                                    <?php if ($contact_name !== ""): ?>

                                        <?php echo htmlspecialchars($contact_name); ?>

                                    <?php else: ?>

                                        Not linked

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Customer
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo !empty($task["customer_code"])
                                        ? htmlspecialchars($task["customer_code"])
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
                                    echo !empty($task["deal_title"])
                                        ? htmlspecialchars($task["deal_title"])
                                        : "Not linked";
                                    ?>

                                </div>

                            </div>

                            <div class="detail-item">

                                <div class="detail-label">
                                    Assigned User
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo !empty($task["assigned_name"])
                                        ? htmlspecialchars($task["assigned_name"])
                                        : "Not assigned";
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
                                        substr($contact_name, 0, 1)
                                    );
                                    ?>
                                </div>

                                <div>

                                    <div class="contact-name">
                                        <?php echo htmlspecialchars($contact_name); ?>
                                    </div>

                                    <div class="contact-details">

                                        <?php if (!empty($task["contact_email"])): ?>

                                            <a
                                                href="mailto:<?php echo htmlspecialchars($task["contact_email"]); ?>"
                                            >
                                                <?php
                                                echo htmlspecialchars(
                                                    $task["contact_email"]
                                                );
                                                ?>
                                            </a>

                                        <?php endif; ?>

                                        <?php if (!empty($task["contact_email"]) && !empty($task["contact_phone"])): ?>

                                            &nbsp;•&nbsp;

                                        <?php endif; ?>

                                        <?php if (!empty($task["contact_phone"])): ?>

                                            <a
                                                href="tel:<?php echo htmlspecialchars($task["contact_phone"]); ?>"
                                            >
                                                <?php
                                                echo htmlspecialchars(
                                                    $task["contact_phone"]
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

            <!-- Side Column -->

            <div class="side-column">

                <!-- Task Summary -->

                <div class="card">

                    <div class="card-header">

                        <h3>Task Summary</h3>

                    </div>

                    <div class="card-body">

                        <div class="side-stat">

                            <span class="side-stat-label">
                                Status
                            </span>

                            <span
                                class="badge <?php echo htmlspecialchars($status_class); ?>"
                            >
                                <?php echo htmlspecialchars($status_label); ?>
                            </span>

                        </div>

                        <div class="side-stat">

                            <span class="side-stat-label">
                                Priority
                            </span>

                            <span
                                class="badge priority-<?php echo htmlspecialchars($priority); ?>"
                            >
                                <?php echo htmlspecialchars($priority_label); ?>
                            </span>

                        </div>

                        <div class="side-stat">

                            <span class="side-stat-label">
                                Due Date
                            </span>

                            <span class="side-stat-value">
                                <?php echo htmlspecialchars($due_date_display); ?>
                            </span>

                        </div>

                        <div class="side-stat">

                            <span class="side-stat-label">
                                Customer
                            </span>

                            <span class="side-stat-value">

                                <?php
                                echo !empty($task["customer_code"])
                                    ? htmlspecialchars($task["customer_code"])
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
                                echo !empty($task["deal_title"])
                                    ? htmlspecialchars($task["deal_title"])
                                    : "Not linked";
                                ?>

                            </span>

                        </div>

                    </div>

                </div>

                <!-- Contact Methods -->

                <div class="card">

                    <div class="card-header">

                        <h3>Contact Methods</h3>

                    </div>

                    <div class="card-body">

                        <?php if ($contact_name !== ""): ?>

                            <div class="quick-actions">

                                <?php if (!empty($task["contact_email"])): ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($task["contact_email"]); ?>"
                                        class="quick-action"
                                    >

                                        <span class="action-icon">
                                            ✉
                                        </span>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $task["contact_email"]
                                            );
                                            ?>
                                        </span>

                                    </a>

                                <?php endif; ?>

                                <?php if (!empty($task["contact_phone"])): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($task["contact_phone"]); ?>"
                                        class="quick-action"
                                    >

                                        <span class="action-icon">
                                            ☎
                                        </span>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $task["contact_phone"]
                                            );
                                            ?>
                                        </span>

                                    </a>

                                <?php endif; ?>

                                <?php if (empty($task["contact_email"]) && empty($task["contact_phone"])): ?>

                                    <div class="empty-text">
                                        No contact methods available.
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php else: ?>

                            <div class="empty-text">
                                No contact is linked to this task.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- Quick Actions -->

                <div class="card">

                    <div class="card-header">

                        <h3>Quick Actions</h3>

                    </div>

                    <div class="card-body">

                        <div class="quick-actions">

                            <a
                                href="edit.php?id=<?php echo (int) $task["id"]; ?>"
                                class="quick-action"
                            >

                                <span class="action-icon">
                                    ✎
                                </span>

                                Edit Task

                            </a>

                            <a
                                href="index.php"
                                class="quick-action"
                            >

                                <span class="action-icon">
                                    ←
                                </span>

                                Back to Tasks

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

                        <h3>Record Timeline</h3>

                    </div>

                    <div class="card-body">

                        <div class="timeline">

                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Task Created
                                </div>

                                <div class="timeline-value">
                                    <?php echo htmlspecialchars($created_display); ?>
                                </div>

                            </div>

                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Last Updated
                                </div>

                                <div class="timeline-value">
                                    <?php echo htmlspecialchars($updated_display); ?>
                                </div>

                            </div>

                            <div class="timeline-item">

                                <span class="timeline-dot"></span>

                                <div class="timeline-title">
                                    Current Status
                                </div>

                                <div class="timeline-value">
                                    <?php echo htmlspecialchars($status_label); ?>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Bottom Actions -->

        <div class="bottom-actions">

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Back to Tasks
            </a>

            <a
                href="edit.php?id=<?php echo (int) $task["id"]; ?>"
                class="btn btn-primary"
            >
                ✎ Edit Task
            </a>

        </div>

    </div>

</div>

</body>

</html>