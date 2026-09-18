<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   TASKS
============================== */

$stmt = $conn->query("
    SELECT
        t.id,
        t.title,
        t.description,
        t.due_date,
        t.priority,
        t.status,
        t.assigned_to,
        t.contact_id,
        t.customer_id,
        t.deal_id,
        t.created_at,

        u.name AS assigned_name,

        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,

        cu.customer_code,

        d.title AS deal_title

    FROM tasks t

    LEFT JOIN users u
        ON u.id = t.assigned_to

    LEFT JOIN contacts ct
        ON ct.id = t.contact_id

    LEFT JOIN customers cu
        ON cu.id = t.customer_id

    LEFT JOIN deals d
        ON d.id = t.deal_id

    ORDER BY t.id DESC
");

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_tasks = count($tasks);

$pending_tasks = 0;
$completed_tasks = 0;
$overdue_tasks = 0;
$high_priority_tasks = 0;

$today = date("Y-m-d");

foreach ($tasks as $task) {

    $status = strtolower(
        trim(
            $task["status"] ?? ""
        )
    );

    $priority = strtolower(
        trim(
            $task["priority"] ?? ""
        )
    );

    $due_date = $task["due_date"] ?? "";

    /* Pending */

    if (
        $status === "pending" ||
        $status === "open" ||
        $status === "in progress" ||
        $status === "in_progress"
    ) {
        $pending_tasks++;
    }

    /* Completed */

    if (
        $status === "completed" ||
        $status === "complete" ||
        $status === "done"
    ) {
        $completed_tasks++;
    }

    /* Overdue */

    if (
        !empty($due_date) &&
        $due_date < $today &&
        !in_array(
            $status,
            [
                "completed",
                "complete",
                "done",
                "cancelled",
                "canceled"
            ],
            true
        )
    ) {
        $overdue_tasks++;
    }

    /* High Priority */

    if (
        $priority === "high" ||
        $priority === "urgent"
    ) {
        $high_priority_tasks++;
    }
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

    <title>Tasks - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        /* =========================
           GLOBAL
        ========================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;

            font-family: Arial, sans-serif;

            background: #f8fafc;

            color: #0f172a;

            overflow-x: hidden;
        }

        .main-content {
            margin-left: 250px;

            min-height: 100vh;

            width: calc(100% - 250px);

            padding: 28px;

            overflow-x: hidden;
        }

        /* =========================
           HEADER
        ========================= */

        .page-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 20px 24px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .title-area {
            display: flex;

            align-items: center;

            gap: 14px;

            min-width: 0;
        }

        .title-icon {
            width: 48px;
            height: 48px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: #dbeafe;

            font-size: 23px;
        }

        .page-header h1 {
            margin: 0;

            font-size: 24px;

            line-height: 1.2;

            color: #0f172a;
        }

        .page-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 13px;
        }

        .add-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 17px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.2s;
        }

        .add-btn:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }

        /* =========================
           SUMMARY
        ========================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 20px;
        }

        .summary-card {
            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 18px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }

        .summary-label {
            color: #64748b;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.05em;
        }

        .summary-value {
            margin-top: 8px;

            font-size: 26px;

            font-weight: 700;

            color: #0f172a;
        }

        .summary-small {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 11px;
        }

        /* =========================
           TOOLBAR
        ========================= */

        .toolbar {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 16px;
        }

        .toolbar-left {
            display: flex;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper span {
            position: absolute;

            left: 13px;

            top: 11px;

            color: #94a3b8;

            pointer-events: none;
        }

        .search-wrapper input {
            width: 300px;

            height: 40px;

            padding: 0 14px 0 37px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            font-size: 13px;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }

        .filter-select {
            height: 40px;

            padding: 0 12px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #475569;

            outline: none;

            font-size: 13px;

            cursor: pointer;
        }

        .export-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 10px 15px;

            border:
                1px solid #dbe3ee;

            border-radius: 9px;

            background: #ffffff;

            color: #334155;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;
        }

        .export-btn:hover {
            background: #f8fafc;
        }

        /* =========================
           TABLE CARD
        ========================= */

        .table-card {
            width: 100%;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            overflow: hidden;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.04);
        }

        .table-wrap {
            width: 100%;

            overflow: hidden;
        }

        /* =========================
           TABLE
        ========================= */

        .tasks-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .tasks-table th {
            padding: 15px 12px;

            text-align: left;

            background: #f8fafc;

            border-bottom:
                1px solid #e2e8f0;

            color: #475569;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.04em;

            white-space: nowrap;
        }

        .tasks-table td {
            padding: 16px 12px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .tasks-table tbody tr:hover {
            background: #f8fbff;
        }

        .tasks-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================
           COLUMN SIZING
           TOTAL = 100%
        ========================= */

        .sno-column {
            width: 5%;
        }

        .task-column {
            width: 17%;
        }

        .assigned-column {
            width: 11%;
        }

        .contact-column {
            width: 11%;
        }

        .customer-column {
            width: 9%;
        }

        .deal-column {
            width: 9%;
        }

        .due-column {
            width: 10%;
        }

        .priority-column {
            width: 8%;
        }

        .status-column {
            width: 9%;
        }

        .action-column {
            width: 11%;
        }

        /* =========================
           DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;

            white-space: nowrap;
        }

        .task-cell {
            min-width: 0;
        }

        .task-title {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .task-id {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;

            white-space: nowrap;
        }

        .secondary {
            color: #64748b;
        }

        .data-text {
            display: block;

            color: #475569;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        /* =========================
           PRIORITY
        ========================= */

        .priority-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            max-width: 100%;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .priority-low {
            background: #dcfce7;

            color: #15803d;
        }

        .priority-medium {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .priority-high {
            background: #fef3c7;

            color: #b45309;
        }

        .priority-urgent {
            background: #fee2e2;

            color: #b91c1c;
        }

        .priority-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================
           STATUS
        ========================= */

        .status-badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            max-width: 100%;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .status-pending {
            background: #fef3c7;

            color: #b45309;
        }

        .status-progress {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .status-completed {
            background: #dcfce7;

            color: #15803d;
        }

        .status-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        .status-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================
           DUE DATE
        ========================= */

        .due-date {
            white-space: nowrap;

            color: #475569;

            font-size: 12px;
        }

        .due-overdue {
            color: #dc2626;

            font-weight: 700;
        }

        /* =========================
           ACTION
        ========================= */

        .tasks-table th.action-column {
            text-align: center;
        }

        .tasks-table td.action-column {
            text-align: center;

            white-space: nowrap;

            overflow: visible;

            padding-left: 6px;

            padding-right: 6px;
        }

        .action-links {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            white-space: nowrap;
        }

        .action-links a {
            display: inline-block;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;

            flex-shrink: 0;
        }

        .action-links span {
            display: inline-block;

            color: #cbd5e1;

            font-size: 12px;

            flex-shrink: 0;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .view-link {
            color: #2563eb;
        }

        .edit-link {
            color: #059669;
        }

        .delete-link {
            color: #dc2626;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            text-align: center;

            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 42px;

            margin-bottom: 10px;
        }

        .empty-state h3 {
            margin: 0;

            color: #334155;
        }

        .empty-state p {
            margin: 8px 0 0;

            color: #94a3b8;

            font-size: 13px;
        }

        /* =========================
           FOOTER
        ========================= */

        .table-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 14px 16px;

            border-top:
                1px solid #eef2f7;

            color: #94a3b8;

            font-size: 12px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1400px) {

            .main-content {
                padding: 22px;
            }

            .tasks-table th {
                padding: 13px 9px;

                font-size: 10px;
            }

            .tasks-table td {
                padding: 14px 9px;
            }

            .action-links {
                gap: 5px;
            }

            .action-links a,
            .action-links span {
                font-size: 11px;
            }
        }

        @media (max-width: 1200px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .search-wrapper input {
                width: 260px;
            }
        }

        @media (max-width: 1000px) {

            .main-content {
                padding: 18px;
            }

            .tasks-table th {
                padding: 12px 7px;

                font-size: 9px;
            }

            .tasks-table td {
                padding: 12px 7px;

                font-size: 12px;
            }

            .action-links {
                gap: 4px;
            }

            .action-links a,
            .action-links span {
                font-size: 10px;
            }
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;

                width: calc(100% - 220px);

                padding: 16px;
            }

            .page-header {
                flex-direction: column;

                align-items: flex-start;
            }

            .add-btn {
                width: 100%;
            }

            .toolbar {
                flex-direction: column;

                align-items: stretch;
            }

            .toolbar-left {
                width: 100%;
            }

            .search-wrapper {
                width: 100%;
            }

            .search-wrapper input {
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .export-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header">

        <div class="title-area">

            <div class="title-icon">
                ✅
            </div>

            <div>

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
            class="add-btn"
        >
            + Add Task
        </a>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Tasks
            </div>

            <div class="summary-value">
                <?= number_format($total_tasks); ?>
            </div>

            <div class="summary-small">
                All task records
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Pending Tasks
            </div>

            <div class="summary-value">
                <?= number_format($pending_tasks); ?>
            </div>

            <div class="summary-small">
                Tasks requiring action
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Completed
            </div>

            <div class="summary-value">
                <?= number_format($completed_tasks); ?>
            </div>

            <div class="summary-small">
                Successfully completed
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Overdue
            </div>

            <div class="summary-value">
                <?= number_format($overdue_tasks); ?>
            </div>

            <div class="summary-small">
                Tasks past due date
            </div>

        </div>

    </div>

    <!-- =========================
         TOOLBAR
    ========================= -->

    <div class="toolbar">

        <div class="toolbar-left">

            <div class="search-wrapper">

                <span>
                    🔎
                </span>

                <input
                    type="text"
                    id="taskSearch"
                    placeholder="Search tasks..."
                    autocomplete="off"
                >

            </div>

            <select
                id="statusFilter"
                class="filter-select"
            >

                <option value="">
                    All Status
                </option>

                <option value="pending">
                    Pending
                </option>

                <option value="in_progress">
                    In Progress
                </option>

                <option value="completed">
                    Completed
                </option>

                <option value="cancelled">
                    Cancelled
                </option>

            </select>

            <select
                id="priorityFilter"
                class="filter-select"
            >

                <option value="">
                    All Priority
                </option>

                <option value="low">
                    Low
                </option>

                <option value="medium">
                    Medium
                </option>

                <option value="high">
                    High
                </option>

                <option value="urgent">
                    Urgent
                </option>

            </select>

        </div>

        <a
            href="../exports/tasks_csv.php"
            class="export-btn"
        >
            ↓ Export CSV
        </a>

    </div>

    <!-- =========================
         TABLE
    ========================= -->

    <div class="table-card">

        <div class="table-wrap">

            <table
                class="tasks-table"
                id="tasksTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="task-column">
                            Task
                        </th>

                        <th class="assigned-column">
                            Assigned To
                        </th>

                        <th class="contact-column">
                            Contact
                        </th>

                        <th class="customer-column">
                            Customer
                        </th>

                        <th class="deal-column">
                            Deal
                        </th>

                        <th class="due-column">
                            Due Date
                        </th>

                        <th class="priority-column">
                            Priority
                        </th>

                        <th class="status-column">
                            Status
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($tasks) > 0): ?>

                    <?php foreach (
                        $tasks
                        as $index => $task
                    ): ?>

                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $task["status"] ?? ""
                                )
                            );

                        $priority =
                            strtolower(
                                trim(
                                    $task["priority"] ?? ""
                                )
                            );

                        /* Status */

                        switch ($status) {

                            case "pending":
                            case "open":

                                $status_class =
                                    "status-pending";

                                break;

                            case "in_progress":
                            case "in progress":
                            case "processing":

                                $status_class =
                                    "status-progress";

                                break;

                            case "completed":
                            case "complete":
                            case "done":

                                $status_class =
                                    "status-completed";

                                break;

                            case "cancelled":
                            case "canceled":

                                $status_class =
                                    "status-cancelled";

                                break;

                            default:

                                $status_class =
                                    "status-default";

                                break;
                        }

                        /* Priority */

                        switch ($priority) {

                            case "low":

                                $priority_class =
                                    "priority-low";

                                break;

                            case "medium":

                                $priority_class =
                                    "priority-medium";

                                break;

                            case "high":

                                $priority_class =
                                    "priority-high";

                                break;

                            case "urgent":

                                $priority_class =
                                    "priority-urgent";

                                break;

                            default:

                                $priority_class =
                                    "priority-default";

                                break;
                        }

                        $contact_name =
                            trim(
                                $task["contact_name"] ?? ""
                            );

                        $is_overdue = false;

                        if (
                            !empty(
                                $task["due_date"]
                            ) &&
                            $task["due_date"] < $today &&
                            !in_array(
                                $status,
                                [
                                    "completed",
                                    "complete",
                                    "done",
                                    "cancelled",
                                    "canceled"
                                ],
                                true
                            )
                        ) {
                            $is_overdue = true;
                        }

                        ?>

                        <tr
                            data-status="<?= htmlspecialchars($status); ?>"
                            data-priority="<?= htmlspecialchars($priority); ?>"
                        >

                            <!-- S.NO -->

                            <td class="serial">

                                <?= $index + 1; ?>

                            </td>

                            <!-- TASK -->

                            <td class="task-cell">

                                <div class="task-title">

                                    <?= htmlspecialchars(
                                        $task["title"] ?? "—"
                                    ); ?>

                                </div>

                                <div class="task-id">

                                    Task ID:
                                    <?= (int) $task["id"]; ?>

                                </div>

                            </td>

                            <!-- ASSIGNED -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $task["assigned_name"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $task[
                                                "assigned_name"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CONTACT -->

                            <td class="secondary">

                                <?php if (
                                    $contact_name !== ""
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $contact_name
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CUSTOMER -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $task[
                                            "customer_code"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $task[
                                                "customer_code"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- DEAL -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $task["deal_title"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $task[
                                                "deal_title"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- DUE DATE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $task["due_date"]
                                    )
                                ): ?>

                                    <span
                                        class="
                                            due-date
                                            <?= $is_overdue
                                                ? 'due-overdue'
                                                : '';
                                            ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            date(
                                                "d M Y",
                                                strtotime(
                                                    $task[
                                                        "due_date"
                                                    ]
                                                )
                                            )
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- PRIORITY -->

                            <td>

                                <span
                                    class="
                                        priority-badge
                                        <?= $priority_class; ?>
                                    "
                                >
                                    <?= htmlspecialchars(
                                        $task[
                                            "priority"
                                        ] ?? "Unknown"
                                    ); ?>
                                </span>

                            </td>

                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        status-badge
                                        <?= $status_class; ?>
                                    "
                                >
                                    <?= htmlspecialchars(
                                        $task[
                                            "status"
                                        ] ?? "Unknown"
                                    ); ?>
                                </span>

                            </td>

                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?= (int) $task["id"]; ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?= (int) $task["id"]; ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?= (int) $task["id"]; ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this task?'
                                            );
                                        "
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="10">

                            <div class="empty-state">

                                <div class="empty-icon">
                                    ✅
                                </div>

                                <h3>
                                    No tasks found
                                </h3>

                                <p>
                                    Add your first task
                                    to get started.
                                </p>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- =========================
             FOOTER
        ========================= -->

        <div class="table-footer">

            <span>

                Showing

                <strong>
                    <?= count($tasks); ?>
                </strong>

                tasks

            </span>

            <span>
                CRM Task Management
            </span>

        </div>

    </div>

</div>

<script>

/* =========================
   SEARCH + FILTER
========================= */

const taskSearch =
    document.getElementById(
        "taskSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const priorityFilter =
    document.getElementById(
        "priorityFilter"
    );

const taskRows =
    document.querySelectorAll(
        "#tasksTable tbody tr[data-status]"
    );

function filterTasks() {

    const searchValue =
        taskSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value
            .toLowerCase()
            .trim();

    const priorityValue =
        priorityFilter.value
            .toLowerCase()
            .trim();

    taskRows.forEach(function(row) {

        const rowText =
            row.textContent
                .toLowerCase();

        const rowStatus =
            row.dataset.status
                .toLowerCase();

        const rowPriority =
            row.dataset.priority
                .toLowerCase();

        const matchesSearch =
            rowText.includes(
                searchValue
            );

        const matchesStatus =
            statusValue === ""
            ||
            rowStatus === statusValue
            ||
            (
                statusValue === "pending" &&
                rowStatus === "open"
            )
            ||
            (
                statusValue === "in_progress" &&
                (
                    rowStatus === "in progress" ||
                    rowStatus === "processing"
                )
            );

        const matchesPriority =
            priorityValue === ""
            ||
            rowPriority === priorityValue;

        row.style.display =
            matchesSearch &&
            matchesStatus &&
            matchesPriority
                ? ""
                : "none";

    });
}

taskSearch.addEventListener(
    "input",
    filterTasks
);

statusFilter.addEventListener(
    "change",
    filterTasks
);

priorityFilter.addEventListener(
    "change",
    filterTasks
);

</script>

</body>

</html>