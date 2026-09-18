<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* =========================
   DATE FILTER
========================= */

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

/* =========================
   TASK FILTER
========================= */

$task_where = [];
$task_params = [];

if (!empty($from_date)) {
    $task_where[] = "DATE(tasks.due_date) >= :task_from_date";
    $task_params[":task_from_date"] = $from_date;
}

if (!empty($to_date)) {
    $task_where[] = "DATE(tasks.due_date) <= :task_to_date";
    $task_params[":task_to_date"] = $to_date;
}

$task_where_sql = "";

if (!empty($task_where)) {
    $task_where_sql = " WHERE " . implode(" AND ", $task_where);
}

/* =========================
   GET TASKS
========================= */

$task_sql = "
    SELECT
        tasks.*,
        users.name AS assigned_user,
        contacts.first_name,
        contacts.last_name
    FROM tasks
    LEFT JOIN users
        ON tasks.assigned_to = users.id
    LEFT JOIN contacts
        ON tasks.contact_id = contacts.id
    $task_where_sql
    ORDER BY tasks.id DESC
";

$task_stmt = $conn->prepare($task_sql);
$task_stmt->execute($task_params);

$tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TASK SUMMARY
========================= */

$total_tasks = count($tasks);
$pending_tasks = 0;
$completed_tasks = 0;
$high_priority_tasks = 0;

foreach ($tasks as $task) {

    $status = strtolower(trim($task["status"] ?? ""));
    $priority = strtolower(trim($task["priority"] ?? ""));

    if ($status != "completed") {
        $pending_tasks++;
    }

    if ($status == "completed") {
        $completed_tasks++;
    }

    if ($priority == "high") {
        $high_priority_tasks++;
    }
}

/* =========================
   ACTIVITY FILTER
========================= */

$activity_where = [];
$activity_params = [];

if (!empty($from_date)) {
    $activity_where[] = "DATE(activities.activity_date) >= :activity_from_date";
    $activity_params[":activity_from_date"] = $from_date;
}

if (!empty($to_date)) {
    $activity_where[] = "DATE(activities.activity_date) <= :activity_to_date";
    $activity_params[":activity_to_date"] = $to_date;
}

$activity_where_sql = "";

if (!empty($activity_where)) {
    $activity_where_sql = " WHERE " . implode(" AND ", $activity_where);
}

/* =========================
   GET ACTIVITIES
========================= */

$activity_sql = "
    SELECT
        activities.*,
        users.name AS created_by_name,
        contacts.first_name,
        contacts.last_name
    FROM activities
    LEFT JOIN users
        ON activities.created_by = users.id
    LEFT JOIN contacts
        ON activities.contact_id = contacts.id
    $activity_where_sql
    ORDER BY activities.id DESC
";

$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->execute($activity_params);

$activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   ACTIVITY SUMMARY
========================= */

$total_activities = count($activities);
$calls = 0;
$meetings = 0;
$emails = 0;

foreach ($activities as $activity) {

    $type = strtolower(trim($activity["type"] ?? ""));

    if ($type == "call" || $type == "calls") {
        $calls++;
    }

    if ($type == "meeting" || $type == "meetings") {
        $meetings++;
    }

    if ($type == "email" || $type == "emails") {
        $emails++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tasks & Activities Report</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <link rel="stylesheet" href="/crm/assets/css/reports.css">

    <style>
        * {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

.main-content {
    margin-left: 250px;
    min-height: 100vh;
    padding: 30px;
}

/* Page Header */
.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    margin: 0;
    font-size: 28px;
    color: #111827;
}

.page-header p {
    margin-top: 8px;
    color: #6b7280;
}

/* Report Section */
.report-section {
    background: white;
    padding: 25px;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.report-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #111827;
    font-size: 22px;
}

/* Filter */
.filter-form {
    display: flex;
    align-items: end;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 14px;
    font-weight: bold;
    color: #374151;
}

.filter-group input {
    width: 180px;
    height: 48px;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 15px;
}

/* Filter Actions */
.filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Common Button */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    height: 48px;
    border-radius: 6px;
    font-size: 16px;
    font-family: Arial, sans-serif;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
    box-sizing: border-box;
}

/* Apply Filter */
.btn-primary {
    background: #2563eb !important;
    color: white !important;
}

.btn-primary:hover {
    background: #1d4ed8 !important;
}

/* Clear */
.btn-secondary {
    background: #6b7280 !important;
    color: white !important;
}

.btn-secondary:hover {
    background: #4b5563 !important;
}

/* Export Buttons */
.btn-success {
    background: #16a34a !important;
    color: white !important;
    text-decoration: none !important;
}

.btn-success:hover {
    background: #15803d !important;
    color: white !important;
    text-decoration: none !important;
}

/* Table */
.table-container {
    width: 100%;
    overflow-x: auto;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: white;
}

.report-table th {
    background: #111827;
    color: white;
    padding: 12px;
    text-align: left;
    font-size: 14px;
    white-space: nowrap;
}

.report-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    color: #374151;
    font-size: 14px;
}

.report-table tr:hover {
    background: #f9fafb;
}

/* Empty Message */
.no-data {
    padding: 20px;
    text-align: center;
    color: #6b7280;
    background: #f9fafb;
    border-radius: 6px;
}

/* Responsive */
@media (max-width: 768px) {

    .main-content {
        margin-left: 220px;
        padding: 20px;
    }

    .filter-form {
        align-items: stretch;
        flex-direction: column;
    }

    .filter-group input {
        width: 100%;
    }

    .filter-actions {
        width: 100%;
    }

    .btn {
        width: 100%;
    }

    .report-section {
        padding: 15px;
    }
}
        .btn-success {
    background: #16a34a;
    color: white;
}

.btn-success:hover {
    background: #15803d;
}
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .report-header h1 {
            margin: 0;
        }

        /* FILTER */

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 6px;
            font-weight: bold;
        }

        .filter-group input {
            padding: 9px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* SUMMARY */

        .section-title {
            margin: 30px 0 15px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .summary-card h3 {
            margin: 0 0 10px;
            color: #6b7280;
            font-size: 14px;
        }

        .summary-card p {
            margin: 0;
            font-size: 26px;
            font-weight: bold;
        }

        /* TABLE */

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .table-box h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f9fafb;
            font-weight: bold;
        }

        /* STATUS */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        /* PRIORITY */

        .priority {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .priority-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-low {
            background: #dcfce7;
            color: #166534;
        }

        /* ACTIVITY TYPE */

        .activity-type {
            text-transform: capitalize;
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            padding: 25px;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

    <link rel="stylesheet" href="tasks_activities.css">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <?php
    $page_title = "Tasks & Activities Report";
    include "../includes/report_header.php";
    ?>

    <!-- DATE FILTER -->

    <div class="filter-box">

        <form method="GET" class="filter-form">

            <div class="filter-group">

                <label for="from_date">
                    From Date
                </label>

                <input
                    type="date"
                    id="from_date"
                    name="from_date"
                    value="<?php echo htmlspecialchars($from_date); ?>"
                >

            </div>

            <div class="filter-group">

                <label for="to_date">
                    To Date
                </label>

                <input
                    type="date"
                    id="to_date"
                    name="to_date"
                    value="<?php echo htmlspecialchars($to_date); ?>"
                >

            </div>

            <div class="filter-actions">

                <button type="submit" class="btn btn-primary">
                    Apply Filter
                </button>

                <a href="tasks_activities.php" class="btn btn-secondary">
                    Clear
                </a>

                <a
                    href="../exports/tasks_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Tasks CSV
                </a>

                <a
                    href="../exports/tasks_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Tasks Excel
                </a>

                <a
                    href="../exports/tasks_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export PDF
                </a>

                <a
                    href="../exports/activities_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Activities CSV
                </a>

                <a
                    href="../exports/activities_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Activities Excel
                </a>

                <a
                    href="../exports/activities_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export PDF
                </a>

            </div>

        </form>

    </div>


    <!-- =========================
         TASK OVERVIEW
    ========================== -->

    <h2 class="section-title">
        Task Overview
    </h2>

    <div class="summary-grid">

        <div class="summary-card">

            <h3>Total Tasks</h3>

            <p>
                <?php echo $total_tasks; ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Pending Tasks</h3>

            <p>
                <?php echo $pending_tasks; ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Completed Tasks</h3>

            <p>
                <?php echo $completed_tasks; ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>High Priority Tasks</h3>

            <p>
                <?php echo $high_priority_tasks; ?>
            </p>

        </div>

    </div>


    <!-- =========================
         ACTIVITY OVERVIEW
    ========================== -->

    <h2 class="section-title">
        Activity Overview
    </h2>

    <div class="summary-grid">

        <div class="summary-card">

            <h3>Total Activities</h3>

            <p>
                <?php echo $total_activities; ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Calls</h3>

            <p>
                <?php echo $calls; ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Meetings</h3>

            <p>
                <?php echo $meetings; ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Emails</h3>

            <p>
                <?php echo $emails; ?>
            </p>

        </div>

    </div>


    <!-- =========================
         TASK DETAILS
    ========================== -->

    <div class="table-box">

        <h2>
            Task Details
        </h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Contact</th>

                </tr>

            </thead>

            <tbody>

            <?php if (!empty($tasks)): ?>

                <?php foreach ($tasks as $task): ?>

                    <?php

                    $task_status =
                        strtolower(
                            trim(
                                $task["status"] ?? ""
                            )
                        );

                    $task_priority =
                        strtolower(
                            trim(
                                $task["priority"] ?? ""
                            )
                        );

                    $contact_name = "-";

                    if (!empty($task["first_name"])) {

                        $contact_name =
                            trim(
                                $task["first_name"] .
                                " " .
                                ($task["last_name"] ?? "")
                            );

                    }

                    ?>

                    <tr>

                        <td>
                            <?php echo $task["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $task["title"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php

                            $description =
                                $task["description"] ?? "-";

                            echo htmlspecialchars(
                                $description
                            );

                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $task["due_date"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>

                            <span class="priority priority-<?php
                                echo htmlspecialchars(
                                    $task_priority
                                );
                            ?>">

                                <?php
                                echo htmlspecialchars(
                                    $task["priority"] ?? "-"
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <span class="status status-<?php
                                echo htmlspecialchars(
                                    str_replace(
                                        " ",
                                        "-",
                                        $task_status
                                    )
                                );
                            ?>">

                                <?php
                                echo htmlspecialchars(
                                    $task["status"] ?? "-"
                                );
                                ?>

                            </span>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $task["assigned_user"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $contact_name
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="8"
                        class="no-data"
                    >
                        No tasks found for the selected date range.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- =========================
         ACTIVITY DETAILS
    ========================== -->

    <div class="table-box">

        <h2>
            Activity Details
        </h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Description</th>
                    <th>Activity Date</th>
                    <th>Contact</th>
                    <th>Created By</th>

                </tr>

            </thead>

            <tbody>

            <?php if (!empty($activities)): ?>

                <?php foreach ($activities as $activity): ?>

                    <?php

                    $activity_contact = "-";

                    if (!empty($activity["first_name"])) {

                        $activity_contact =
                            trim(
                                $activity["first_name"] .
                                " " .
                                ($activity["last_name"] ?? "")
                            );

                    }

                    ?>

                    <tr>

                        <td>
                            <?php echo $activity["id"]; ?>
                        </td>

                        <td class="activity-type">

                            <?php
                            echo htmlspecialchars(
                                $activity["type"] ?? "-"
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $activity["subject"] ?? "-"
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $activity["description"] ?? "-"
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $activity["activity_date"] ?? "-"
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $activity_contact
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $activity["created_by_name"] ?? "-"
                            );
                            ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="7"
                        class="no-data"
                    >
                        No activities found for the selected date range.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>