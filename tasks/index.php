<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/
$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");
$priority_filter = trim($_GET["priority"] ?? "");

/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/
$allowed_statuses = [
    "pending",
    "in-progress",
    "completed",
    "cancelled"
];

$allowed_priorities = [
    "low",
    "medium",
    "high",
    "urgent"
];

if (
    $status_filter !== "" &&
    !in_array($status_filter, $allowed_statuses, true)
) {
    $status_filter = "";
}

if (
    $priority_filter !== "" &&
    !in_array($priority_filter, $allowed_priorities, true)
) {
    $priority_filter = "";
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM tasks
");
$stmt->execute();
$total_tasks = (int) $stmt->fetchColumn();


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM tasks
    WHERE status = 'pending'
");
$stmt->execute();
$pending_tasks = (int) $stmt->fetchColumn();


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM tasks
    WHERE status = 'completed'
");
$stmt->execute();
$completed_tasks = (int) $stmt->fetchColumn();


$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM tasks
    WHERE
        due_date IS NOT NULL
        AND due_date < CURDATE()
        AND status NOT IN ('completed', 'cancelled')
");
$stmt->execute();
$overdue_tasks = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Load Tasks
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.due_date,
        tasks.priority,
        tasks.status,
        tasks.assigned_to,
        tasks.contact_id,
        tasks.customer_id,
        tasks.deal_id,
        tasks.created_at,
        tasks.updated_at,

        users.name AS assigned_name,

        contacts.first_name,
        contacts.last_name,

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

    WHERE 1 = 1
";

$params = [];

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/
if ($search !== "") {

    $sql .= "
        AND (
            tasks.title LIKE :search
            OR tasks.description LIKE :search
            OR users.name LIKE :search
            OR contacts.first_name LIKE :search
            OR contacts.last_name LIKE :search
            OR customers.customer_code LIKE :search
            OR deals.title LIKE :search
        )
    ";

    $params[":search"] = "%" . $search . "%";
}

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/
if ($status_filter !== "") {

    $sql .= "
        AND tasks.status = :status
    ";

    $params[":status"] = $status_filter;
}

/*
|--------------------------------------------------------------------------
| Priority
|--------------------------------------------------------------------------
*/
if ($priority_filter !== "") {

    $sql .= "
        AND tasks.priority = :priority
    ";

    $params[":priority"] = $priority_filter;
}

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/
$sql .= "
    ORDER BY
        CASE
            WHEN
                tasks.due_date IS NOT NULL
                AND tasks.due_date < CURDATE()
                AND tasks.status NOT IN ('completed', 'cancelled')
            THEN 0

            WHEN tasks.status = 'pending'
            THEN 1

            WHEN tasks.status = 'in-progress'
            THEN 2

            WHEN tasks.status = 'completed'
            THEN 3

            ELSE 4
        END ASC,

        CASE
            WHEN tasks.due_date IS NULL THEN 1
            ELSE 0
        END ASC,

        tasks.due_date ASC,
        tasks.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/
function getTaskStatusLabel($status)
{
    $labels = [
        "pending" => "Pending",
        "in-progress" => "In Progress",
        "completed" => "Completed",
        "cancelled" => "Cancelled"
    ];

    return $labels[$status] ?? ucfirst($status);
}


function getTaskPriorityLabel($priority)
{
    $labels = [
        "low" => "Low",
        "medium" => "Medium",
        "high" => "High",
        "urgent" => "Urgent"
    ];

    return $labels[$priority] ?? ucfirst($priority);
}


function getTaskStatusClass($status)
{
    $allowed = [
        "pending",
        "in-progress",
        "completed",
        "cancelled"
    ];

    if (!in_array($status, $allowed, true)) {
        return "status-default";
    }

    return "status-" . $status;
}


function getTaskPriorityClass($priority)
{
    $allowed = [
        "low",
        "medium",
        "high",
        "urgent"
    ];

    if (!in_array($priority, $allowed, true)) {
        return "priority-default";
    }

    return "priority-" . $priority;
}


function formatTaskDate($date)
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date("d M Y", $timestamp);
}


function isTaskOverdue($task)
{
    if (empty($task["due_date"])) {
        return false;
    }

    if (
        $task["status"] === "completed" ||
        $task["status"] === "cancelled"
    ) {
        return false;
    }

    return strtotime($task["due_date"]) < strtotime(date("Y-m-d"));
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

    <title>Tasks | CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f9;
        }

        .main-content {
            padding: 28px 30px;
        }

        .page-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            padding: 26px 30px;

            margin-bottom: 26px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 18px;

            min-width: 0;
        }

        .page-icon {
            width: 64px;
            height: 64px;

            flex: 0 0 64px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #dbeafe;

            border-radius: 15px;

            font-size: 30px;
        }

        .page-title {
            min-width: 0;
        }

        .page-title h1 {
            margin: 0 0 5px;

            color: #0f172a;

            font-size: 30px;

            font-weight: 700;

            line-height: 1.1;
        }

        .page-title p {
            margin: 0;

            color: #64748b;

            font-size: 16px;
        }

        .add-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 50px;

            padding: 0 20px;

            border-radius: 11px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.2s ease;
        }

        .add-button:hover {
            background: #1d4ed8;
        }

        /* =========================================================
           STAT CARDS
        ========================================================= */

        .stats-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 20px;

            margin-bottom: 26px;
        }

        .stat-card {
            padding: 24px 26px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 15px;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .stat-title {
            margin-bottom: 10px;

            color: #64748b;

            font-size: 13px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }

        .stat-number {
            margin-bottom: 8px;

            color: #0f172a;

            font-size: 32px;

            line-height: 1;

            font-weight: 700;
        }

        .stat-text {
            color: #94a3b8;

            font-size: 14px;
        }

        /* =========================================================
           TOOLBAR
        ========================================================= */

        .toolbar {
            display: flex;

            align-items: center;

            gap: 14px;

            margin-bottom: 22px;
        }

        .search-box {
            position: relative;

            flex: 1;

            min-width: 0;
        }

        .search-icon {
            position: absolute;

            left: 17px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 19px;

            pointer-events: none;
        }

        .search-box input {
            width: 100%;

            height: 54px;

            padding:
                0
                15px
                0
                48px;

            border:
                1px solid #dbe3ed;

            border-radius: 11px;

            background: #ffffff;

            color: #0f172a;

            font-size: 15px;

            outline: none;
        }

        .search-box input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.08);
        }

        .filter-select {
            width: 145px;

            height: 54px;

            padding:
                0
                14px;

            border:
                1px solid #dbe3ed;

            border-radius: 11px;

            background: #ffffff;

            color: #334155;

            font-size: 14px;

            outline: none;

            cursor: pointer;
        }

        .filter-select:focus {
            border-color: #2563eb;
        }

        .export-button {
            min-height: 54px;

            padding:
                0
                18px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid #dbe3ed;

            border-radius: 11px;

            background: #ffffff;

            color: #1e3a5f;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.2s ease;
        }

        .export-button:hover {
            border-color: #2563eb;

            color: #2563eb;

            background: #f8fbff;
        }

        /* =========================================================
           TABLE CARD
        ========================================================= */

        .table-card {
            width: 100%;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .table-wrapper {
            width: 100%;

            overflow: hidden;
        }

        .tasks-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        /* =========================================================
           TABLE HEADER
        ========================================================= */

        .tasks-table thead th {
            height: 58px;

            padding:
                0
                10px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fafc;

            color: #334155;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.25px;

            text-align: left;

            white-space: nowrap;

            overflow: hidden;
        }

        /* =========================================================
           TABLE CELLS
        ========================================================= */

        .tasks-table tbody td {
            height: 78px;

            padding:
                10px;

            border-bottom:
                1px solid #edf1f5;

            color: #334155;

            font-size: 13px;

            vertical-align: middle;

            overflow: hidden;
        }

        .tasks-table tbody tr:hover {
            background: #f8fbff;
        }

        .tasks-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           COLUMN WIDTHS
           Total = 100%
        ========================================================= */

        .tasks-table th:nth-child(1),
        .tasks-table td:nth-child(1) {
            width: 4%;
        }

        .tasks-table th:nth-child(2),
        .tasks-table td:nth-child(2) {
            width: 20%;
        }

        .tasks-table th:nth-child(3),
        .tasks-table td:nth-child(3) {
            width: 11%;
        }

        .tasks-table th:nth-child(4),
        .tasks-table td:nth-child(4) {
            width: 10%;
        }

        .tasks-table th:nth-child(5),
        .tasks-table td:nth-child(5) {
            width: 8%;
        }

        .tasks-table th:nth-child(6),
        .tasks-table td:nth-child(6) {
            width: 9%;
        }

        .tasks-table th:nth-child(7),
        .tasks-table td:nth-child(7) {
            width: 10%;
        }

        .tasks-table th:nth-child(8),
        .tasks-table td:nth-child(8) {
            width: 8%;
        }

        .tasks-table th:nth-child(9),
        .tasks-table td:nth-child(9) {
            width: 9%;
        }

        .tasks-table th:nth-child(10),
        .tasks-table td:nth-child(10) {
            width: 11%;
        }

        /* =========================================================
           S.NO
        ========================================================= */

        .serial-number {
            color: #0f172a;

            font-size: 13px;

            font-weight: 600;
        }

        /* =========================================================
           TASK
        ========================================================= */

        .task-title {
            display: block;

            margin-bottom: 4px;

            color: #0f172a;

            font-size: 13px;

            font-weight: 700;

            line-height: 1.35;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .task-id {
            color: #94a3b8;

            font-size: 11px;
        }

        /* =========================================================
           TRUNCATED DATA
        ========================================================= */

        .data-text {
            display: block;

            width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #475569;
        }

        .data-empty {
            color: #94a3b8;
        }

        /* =========================================================
           DATE
        ========================================================= */

        .due-date {
            display: block;

            color: #334155;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;
        }

        .due-date.overdue {
            color: #dc2626;

            font-weight: 700;
        }

        /* =========================================================
           BADGES
        ========================================================= */

        .badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                6px
                9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            line-height: 1;

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

        .status-default {
            background: #f1f5f9;

            color: #475569;
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

        .priority-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================================================
           ACTION COLUMN
        ========================================================= */

        .tasks-table th.action-column,
        .tasks-table td.action-column {
            text-align: center;

            white-space: nowrap;

            overflow: visible;
        }

        .action-buttons {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            white-space: nowrap;
        }

        .action-buttons a {
            display: inline-block;

            margin: 0;

            padding: 0;

            border: none;

            background: transparent;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

            line-height: 1;

            white-space: nowrap;
        }

        .action-view {
            color: #2563eb;
        }

        .action-view:hover {
            color: #1d4ed8;
        }

        .action-edit {
            color: #059669;
        }

        .action-edit:hover {
            color: #047857;
        }

        .action-delete {
            color: #dc2626;
        }

        .action-delete:hover {
            color: #b91c1c;
        }

        .action-separator {
            color: #cbd5e1;

            font-size: 11px;

            line-height: 1;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .table-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            min-height: 56px;

            padding:
                0
                22px;

            border-top:
                1px solid #edf1f5;

            color: #94a3b8;

            font-size: 13px;
        }

        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {
            padding:
                55px
                20px;

            text-align: center;
        }

        .empty-icon {
            margin-bottom: 10px;

            font-size: 36px;
        }

        .empty-state h3 {
            margin:
                0
                0
                6px;

            color: #334155;

            font-size: 17px;
        }

        .empty-state p {
            margin: 0;

            color: #94a3b8;

            font-size: 13px;
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-header,
        html.dark-mode .stat-card,
        html.dark-mode .table-card {
            background: #111827;

            border-color: #1f2937;

            box-shadow: none;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .stat-number,
        html.dark-mode .task-title,
        html.dark-mode .serial-number,
        html.dark-mode .empty-state h3 {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .stat-title,
        html.dark-mode .stat-text,
        html.dark-mode .task-id,
        html.dark-mode .data-empty,
        html.dark-mode .table-footer {
            color: #94a3b8;
        }

        html.dark-mode .search-box input,
        html.dark-mode .filter-select,
        html.dark-mode .export-button {
            background: #111827;

            border-color: #334155;

            color: #e2e8f0;
        }

        html.dark-mode .search-box input::placeholder {
            color: #64748b;
        }

        html.dark-mode .export-button:hover {
            background: #172033;

            border-color: #60a5fa;

            color: #60a5fa;
        }

        html.dark-mode .tasks-table thead th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .tasks-table tbody td {
            border-color: #1f2937;

            color: #cbd5e1;
        }

        html.dark-mode .tasks-table tbody tr:hover {
            background: #172033;
        }

        html.dark-mode .data-text,
        html.dark-mode .due-date {
            color: #cbd5e1;
        }

        html.dark-mode .action-separator {
            color: #475569;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {

            .main-content {
                padding: 24px;
            }

            .tasks-table thead th {
                font-size: 10px;
            }

            .tasks-table tbody td {
                padding: 9px 7px;
                font-size: 12px;
            }

            .task-title {
                font-size: 12px;
            }

            .action-buttons a {
                font-size: 10px;
            }

            .action-buttons {
                gap: 4px;
            }

        }

        @media (max-width: 1000px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .toolbar {
                flex-wrap: wrap;
            }

            .search-box {
                flex-basis: 100%;
            }

        }

        @media (max-width: 700px) {

            .main-content {
                padding: 18px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-header-left {
                align-items: flex-start;
            }

            .add-button {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box,
            .filter-select,
            .export-button {
                width: 100%;
            }

            /*
             * Only smaller screens use horizontal scrolling.
             * Desktop/tablet remains fully fitted.
             */
            .table-wrapper {
                overflow-x: auto;
            }

            .tasks-table {
                min-width: 1100px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-container">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="page-header-left">

                <div class="page-icon">
                    ✅
                </div>

                <div class="page-title">

                    <h1>
                        Tasks
                    </h1>

                    <p>
                        Manage tasks, priorities, assignments and due dates
                    </p>

                </div>

            </div>

            <a
                href="add.php"
                class="add-button"
            >
                + Add Task
            </a>

        </div>


        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <div class="stats-grid">

            <div class="stat-card">

                <div class="stat-title">
                    Total Tasks
                </div>

                <div class="stat-number">
                    <?php echo $total_tasks; ?>
                </div>

                <div class="stat-text">
                    All task records
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-title">
                    Pending Tasks
                </div>

                <div class="stat-number">
                    <?php echo $pending_tasks; ?>
                </div>

                <div class="stat-text">
                    Tasks requiring action
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-title">
                    Completed
                </div>

                <div class="stat-number">
                    <?php echo $completed_tasks; ?>
                </div>

                <div class="stat-text">
                    Successfully completed
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-title">
                    Overdue
                </div>

                <div class="stat-number">
                    <?php echo $overdue_tasks; ?>
                </div>

                <div class="stat-text">
                    Tasks past due date
                </div>

            </div>

        </div>


        <!-- =====================================================
             TOOLBAR
        ====================================================== -->

        <form
            method="GET"
            class="toolbar"
        >

            <div class="search-box">

                <span class="search-icon">
                    🔎
                </span>

                <input
                    type="text"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search tasks..."
                >

            </div>


            <select
                name="status"
                class="filter-select"
                onchange="this.form.submit()"
            >

                <option value="">
                    All Status
                </option>

                <option
                    value="pending"
                    <?php
                    echo $status_filter === "pending"
                        ? "selected"
                        : "";
                    ?>
                >
                    Pending
                </option>

                <option
                    value="in-progress"
                    <?php
                    echo $status_filter === "in-progress"
                        ? "selected"
                        : "";
                    ?>
                >
                    In Progress
                </option>

                <option
                    value="completed"
                    <?php
                    echo $status_filter === "completed"
                        ? "selected"
                        : "";
                    ?>
                >
                    Completed
                </option>

                <option
                    value="cancelled"
                    <?php
                    echo $status_filter === "cancelled"
                        ? "selected"
                        : "";
                    ?>
                >
                    Cancelled
                </option>

            </select>


            <select
                name="priority"
                class="filter-select"
                onchange="this.form.submit()"
            >

                <option value="">
                    All Priority
                </option>

                <option
                    value="low"
                    <?php
                    echo $priority_filter === "low"
                        ? "selected"
                        : "";
                    ?>
                >
                    Low
                </option>

                <option
                    value="medium"
                    <?php
                    echo $priority_filter === "medium"
                        ? "selected"
                        : "";
                    ?>
                >
                    Medium
                </option>

                <option
                    value="high"
                    <?php
                    echo $priority_filter === "high"
                        ? "selected"
                        : "";
                    ?>
                >
                    High
                </option>

                <option
                    value="urgent"
                    <?php
                    echo $priority_filter === "urgent"
                        ? "selected"
                        : "";
                    ?>
                >
                    Urgent
                </option>

            </select>


            <a
                href="export_csv.php"
                class="export-button"
            >
                ↓ Export CSV
            </a>

        </form>


        <!-- =====================================================
             TABLE
        ====================================================== -->

        <div class="table-card">

            <div class="table-wrapper">

                <?php if (!empty($tasks)): ?>

                    <table class="tasks-table">

                        <thead>

                            <tr>

                                <th>
                                    S.NO
                                </th>

                                <th>
                                    TASK
                                </th>

                                <th>
                                    ASSIGNED TO
                                </th>

                                <th>
                                    CONTACT
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    DEAL
                                </th>

                                <th>
                                    DUE DATE
                                </th>

                                <th>
                                    PRIORITY
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th class="action-column">
                                    ACTION
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php

                            $serial = 1;

                            foreach ($tasks as $task):

                                $contact_name = trim(
                                    ($task["first_name"] ?? "") .
                                    " " .
                                    ($task["last_name"] ?? "")
                                );

                                $is_overdue = isTaskOverdue($task);

                            ?>

                                <tr>

                                    <!-- S.NO -->

                                    <td>

                                        <span class="serial-number">

                                            <?php
                                            echo $serial;
                                            $serial++;
                                            ?>

                                        </span>

                                    </td>


                                    <!-- TASK -->

                                    <td>

                                        <span
                                            class="task-title"
                                            title="<?php
                                            echo htmlspecialchars(
                                                $task["title"] ?? ""
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $task["title"] ?? "-"
                                            );
                                            ?>

                                        </span>

                                        <span class="task-id">

                                            Task ID:
                                            <?php
                                            echo (int) $task["id"];
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ASSIGNED -->

                                    <td>

                                        <?php if (!empty($task["assigned_name"])): ?>

                                            <span
                                                class="data-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $task["assigned_name"]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $task["assigned_name"]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="data-empty">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CONTACT -->

                                    <td>

                                        <?php if ($contact_name !== ""): ?>

                                            <span
                                                class="data-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $contact_name
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $contact_name
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="data-empty">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <?php if (!empty($task["customer_code"])): ?>

                                            <span class="data-text">

                                                <?php
                                                echo htmlspecialchars(
                                                    $task["customer_code"]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="data-empty">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- DEAL -->

                                    <td>

                                        <?php if (!empty($task["deal_title"])): ?>

                                            <span
                                                class="data-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $task["deal_title"]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $task["deal_title"]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="data-empty">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- DUE DATE -->

                                    <td>

                                        <?php if (!empty($task["due_date"])): ?>

                                            <span
                                                class="due-date <?php
                                                echo $is_overdue
                                                    ? "overdue"
                                                    : "";
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    formatTaskDate(
                                                        $task["due_date"]
                                                    )
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="data-empty">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRIORITY -->

                                    <td>

                                        <span
                                            class="badge <?php
                                            echo htmlspecialchars(
                                                getTaskPriorityClass(
                                                    $task["priority"] ?? ""
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                getTaskPriorityLabel(
                                                    $task["priority"] ?? ""
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="badge <?php
                                            echo htmlspecialchars(
                                                getTaskStatusClass(
                                                    $task["status"] ?? ""
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                getTaskStatusLabel(
                                                    $task["status"] ?? ""
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="action-column">

                                        <div class="action-buttons">

                                            <a
                                                href="view.php?id=<?php
                                                echo (int) $task["id"];
                                                ?>"
                                                class="action-view"
                                            >
                                                View
                                            </a>

                                            <span class="action-separator">
                                                |
                                            </span>

                                            <a
                                                href="edit.php?id=<?php
                                                echo (int) $task["id"];
                                                ?>"
                                                class="action-edit"
                                            >
                                                Edit
                                            </a>

                                            <span class="action-separator">
                                                |
                                            </span>

                                            <a
                                                href="delete.php?id=<?php
                                                echo (int) $task["id"];
                                                ?>"
                                                class="action-delete"
                                                onclick="return confirm('Are you sure you want to delete this task?');"
                                            >
                                                Delete
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            ✅
                        </div>

                        <h3>
                            No tasks found
                        </h3>

                        <p>
                            Try changing your search or filters, or create a new task.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="table-footer">

                <span>
                    Showing
                    <?php echo count($tasks); ?>
                    task<?php echo count($tasks) === 1 ? "" : "s"; ?>
                </span>

                <span>
                    CRM Task Management
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>