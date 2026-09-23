<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

$from_date = trim($_GET["from_date"] ?? "");
$to_date   = trim($_GET["to_date"] ?? "");


/*
|--------------------------------------------------------------------------
| TASK FILTER
|--------------------------------------------------------------------------
*/

$task_where = [];
$task_params = [];

if ($from_date !== "") {

    $task_where[] = "
        DATE(tasks.due_date) >= :task_from_date
    ";

    $task_params[":task_from_date"] = $from_date;
}

if ($to_date !== "") {

    $task_where[] = "
        DATE(tasks.due_date) <= :task_to_date
    ";

    $task_params[":task_to_date"] = $to_date;
}

$task_where_sql = "";

if (!empty($task_where)) {

    $task_where_sql =
        " WHERE " .
        implode(
            " AND ",
            $task_where
        );
}


/*
|--------------------------------------------------------------------------
| GET TASKS
|--------------------------------------------------------------------------
*/

$task_sql = "
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.due_date,
        tasks.priority,
        tasks.status,
        tasks.assigned_to,
        tasks.contact_id,

        users.name AS assigned_user,

        contacts.first_name,
        contacts.last_name

    FROM tasks

    LEFT JOIN users
        ON tasks.assigned_to = users.id

    LEFT JOIN contacts
        ON tasks.contact_id = contacts.id

    $task_where_sql

    ORDER BY
        tasks.id DESC
";

$task_stmt = $conn->prepare($task_sql);
$task_stmt->execute($task_params);

$tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| TASK SUMMARY
|--------------------------------------------------------------------------
*/

$total_tasks = count($tasks);

$pending_tasks = 0;

$completed_tasks = 0;

$high_priority_tasks = 0;

$in_progress_tasks = 0;

$cancelled_tasks = 0;

foreach ($tasks as $task) {

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


    if ($status === "completed") {

        $completed_tasks++;

    } else {

        $pending_tasks++;
    }


    if ($status === "in-progress") {
        $in_progress_tasks++;
    }


    if ($status === "cancelled") {
        $cancelled_tasks++;
    }


    if ($priority === "high") {
        $high_priority_tasks++;
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVITY FILTER
|--------------------------------------------------------------------------
*/

$activity_where = [];

$activity_params = [];

if ($from_date !== "") {

    $activity_where[] = "
        DATE(activities.activity_date) >= :activity_from_date
    ";

    $activity_params[
        ":activity_from_date"
    ] = $from_date;
}

if ($to_date !== "") {

    $activity_where[] = "
        DATE(activities.activity_date) <= :activity_to_date
    ";

    $activity_params[
        ":activity_to_date"
    ] = $to_date;
}

$activity_where_sql = "";

if (!empty($activity_where)) {

    $activity_where_sql =
        " WHERE " .
        implode(
            " AND ",
            $activity_where
        );
}


/*
|--------------------------------------------------------------------------
| GET ACTIVITIES
|--------------------------------------------------------------------------
*/

$activity_sql = "
    SELECT
        activities.id,
        activities.type,
        activities.subject,
        activities.description,
        activities.activity_date,
        activities.contact_id,
        activities.customer_id,
        activities.deal_id,

        users.name AS created_by_name,

        contacts.first_name,
        contacts.last_name

    FROM activities

    LEFT JOIN users
        ON activities.created_by = users.id

    LEFT JOIN contacts
        ON activities.contact_id = contacts.id

    $activity_where_sql

    ORDER BY
        activities.id DESC
";

$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->execute($activity_params);

$activities =
    $activity_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ACTIVITY SUMMARY
|--------------------------------------------------------------------------
*/

$total_activities = count($activities);

$calls = 0;

$meetings = 0;

$emails = 0;

$notes = 0;

$followups = 0;

foreach ($activities as $activity) {

    $type =
        strtolower(
            trim(
                $activity["type"] ?? ""
            )
        );


    if (
        $type === "call" ||
        $type === "calls"
    ) {
        $calls++;
    }


    if (
        $type === "meeting" ||
        $type === "meetings"
    ) {
        $meetings++;
    }


    if (
        $type === "email" ||
        $type === "emails"
    ) {
        $emails++;
    }


    if (
        $type === "note" ||
        $type === "notes"
    ) {
        $notes++;
    }


    if (
        $type === "follow-up" ||
        $type === "follow up" ||
        $type === "followup"
    ) {
        $followups++;
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function formatReportDate($date)
{
    if (empty($date)) {
        return "-";
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


function formatReportDateTime($date)
{
    if (empty($date)) {
        return "-";
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


function taskStatusClass($status)
{
    $status =
        strtolower(
            trim(
                $status ?? ""
            )
        );

    if ($status === "completed") {
        return "status-completed";
    }

    if ($status === "in-progress") {
        return "status-in-progress";
    }

    if ($status === "cancelled") {
        return "status-cancelled";
    }

    if ($status === "pending") {
        return "status-pending";
    }

    return "status-default";
}


function taskStatusLabel($status)
{
    $status = trim(
        $status ?? ""
    );

    if ($status === "") {
        return "Unknown";
    }

    return ucfirst(
        str_replace(
            [
                "-",
                "_"
            ],
            " ",
            $status
        )
    );
}


function taskPriorityClass($priority)
{
    $priority =
        strtolower(
            trim(
                $priority ?? ""
            )
        );

    if ($priority === "high") {
        return "priority-high";
    }

    if ($priority === "medium") {
        return "priority-medium";
    }

    if ($priority === "low") {
        return "priority-low";
    }

    if ($priority === "urgent") {
        return "priority-urgent";
    }

    return "priority-default";
}


function taskPriorityLabel($priority)
{
    $priority = trim(
        $priority ?? ""
    );

    if ($priority === "") {
        return "Unknown";
    }

    return ucfirst(
        str_replace(
            [
                "-",
                "_"
            ],
            " ",
            $priority
        )
    );
}


function activityTypeClass($type)
{
    $type =
        strtolower(
            trim(
                $type ?? ""
            )
        );

    if (
        $type === "call" ||
        $type === "calls"
    ) {
        return "activity-call";
    }

    if (
        $type === "meeting" ||
        $type === "meetings"
    ) {
        return "activity-meeting";
    }

    if (
        $type === "email" ||
        $type === "emails"
    ) {
        return "activity-email";
    }

    if (
        $type === "note" ||
        $type === "notes"
    ) {
        return "activity-note";
    }

    if (
        $type === "follow-up" ||
        $type === "follow up" ||
        $type === "followup"
    ) {
        return "activity-followup";
    }

    return "activity-default";
}


function activityTypeLabel($type)
{
    $type = trim(
        $type ?? ""
    );

    if ($type === "") {
        return "Unknown";
    }

    return ucwords(
        str_replace(
            [
                "-",
                "_"
            ],
            " ",
            $type
        )
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
        Tasks & Activities Report | CRM
    </title>


    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >


    <link
        rel="stylesheet"
        href="/crm/assets/css/reports.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        .page-wrap {
            width: 100%;

            max-width: 1500px;

            margin: 0 auto;
        }


        /* =========================================================
           REPORT HEADER
        ========================================================= */

        .report-page-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding:
                24px
                28px;

            margin-bottom: 22px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 2px 8px
                rgba(
                    15,
                    23,
                    42,
                    0.04
                );
        }


        .report-header-left {
            display: flex;

            align-items: center;

            gap: 15px;

            min-width: 0;
        }


        .report-icon {
            width: 52px;

            height: 52px;

            flex: 0 0 52px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: #dbeafe;

            color: #2563eb;

            font-size: 24px;
        }


        .report-title {
            min-width: 0;
        }


        .report-title h1 {
            margin:
                0
                0
                5px;

            color: #0f172a;

            font-size: 25px;

            line-height: 1.2;

            font-weight: 700;
        }


        .report-title p {
            margin: 0;

            color: #64748b;

            font-size: 13px;
        }


        .back-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 40px;

            padding:
                0
                14px;

            border:
                1px solid #dbe3ed;

            border-radius: 8px;

            background: #ffffff;

            color: #334155;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;
        }


        .back-button:hover {
            background: #f8fafc;

            border-color: #cbd5e1;
        }


        /* =========================================================
           FILTER CARD
        ========================================================= */

        .filter-card {
            padding: 20px;

            margin-bottom: 20px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            box-shadow:
                0 2px 8px
                rgba(
                    15,
                    23,
                    42,
                    0.04
                );
        }


        .filter-heading {
            margin-bottom: 15px;
        }


        .filter-heading h3 {
            margin:
                0
                0
                4px;

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;
        }


        .filter-heading p {
            margin: 0;

            color: #64748b;

            font-size: 11px;
        }


        .filter-form {
            display: flex;

            align-items: flex-end;

            gap: 12px;

            flex-wrap: wrap;
        }


        .filter-group {
            display: flex;

            flex-direction: column;

            gap: 6px;

            min-width: 165px;
        }


        .filter-group label {
            color: #334155;

            font-size: 11px;

            font-weight: 700;
        }


        .filter-input {
            width: 100%;

            height: 40px;

            padding:
                0
                11px;

            border:
                1px solid #dbe1e8;

            border-radius: 8px;

            background: #ffffff;

            color: #0f172a;

            font-size: 12px;

            outline: none;
        }


        .filter-input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    0.08
                );
        }


        .filter-actions {
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

            padding:
                0
                14px;

            border:
                1px solid transparent;

            border-radius: 8px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            white-space: nowrap;

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


        .btn-success {
            background: #16a34a;

            border-color: #16a34a;

            color: #ffffff;
        }


        .btn-success:hover {
            background: #15803d;

            border-color: #15803d;
        }


        /* =========================================================
           SECTION HEADING
        ========================================================= */

        .section-heading {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin:
                22px
                0
                12px;
        }


        .section-heading-left h2 {
            margin: 0;

            color: #0f172a;

            font-size: 17px;

            font-weight: 700;
        }


        .section-heading-left p {
            margin:
                4px
                0
                0;

            color: #64748b;

            font-size: 10px;
        }


        .section-count {
            color: #94a3b8;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =========================================================
           SUMMARY
        ========================================================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 14px;

            margin-bottom: 20px;
        }


        .summary-card {
            padding: 17px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 11px;

            box-shadow:
                0 2px 8px
                rgba(
                    15,
                    23,
                    42,
                    0.03
                );
        }


        .summary-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }


        .summary-value {
            color: #0f172a;

            font-size: 23px;

            line-height: 1.2;

            font-weight: 700;
        }


        .summary-description {
            margin-top: 5px;

            color: #94a3b8;

            font-size: 10px;
        }


        /* =========================================================
           TABLE CARD
        ========================================================= */

        .table-card {
            width: 100%;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px
                rgba(
                    15,
                    23,
                    42,
                    0.04
                );

            margin-bottom: 20px;
        }


        .table-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding:
                17px
                19px;

            border-bottom:
                1px solid #edf1f5;
        }


        .table-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;
        }


        .table-header span {
            color: #94a3b8;

            font-size: 11px;

            white-space: nowrap;
        }


        .table-wrapper {
            width: 100%;

            overflow-x: auto;
        }


        /* =========================================================
           TASK TABLE
        ========================================================= */

        .tasks-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        .tasks-table th {
            height: 52px;

            padding:
                0
                10px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fafc;

            color: #334155;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            text-align: left;

            white-space: nowrap;
        }


        .tasks-table td {
            height: 68px;

            padding:
                9px
                10px;

            border-bottom:
                1px solid #edf1f5;

            color: #334155;

            font-size: 12px;

            vertical-align: middle;
        }


        .tasks-table tbody tr:hover {
            background: #f8fbff;
        }


        .tasks-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* =========================================================
           TASK COLUMN WIDTHS
        ========================================================= */

        .tasks-table th:nth-child(1),
        .tasks-table td:nth-child(1) {
            width: 5%;
        }


        .tasks-table th:nth-child(2),
        .tasks-table td:nth-child(2) {
            width: 17%;
        }


        .tasks-table th:nth-child(3),
        .tasks-table td:nth-child(3) {
            width: 18%;
        }


        .tasks-table th:nth-child(4),
        .tasks-table td:nth-child(4) {
            width: 11%;
        }


        .tasks-table th:nth-child(5),
        .tasks-table td:nth-child(5) {
            width: 10%;
        }


        .tasks-table th:nth-child(6),
        .tasks-table td:nth-child(6) {
            width: 10%;
        }


        .tasks-table th:nth-child(7),
        .tasks-table td:nth-child(7) {
            width: 15%;
        }


        .tasks-table th:nth-child(8),
        .tasks-table td:nth-child(8) {
            width: 14%;
        }


        /* =========================================================
           ACTIVITY TABLE
        ========================================================= */

        .activities-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        .activities-table th {
            height: 52px;

            padding:
                0
                10px;

            border-bottom:
                1px solid #e2e8f0;

            background: #f8fafc;

            color: #334155;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            text-align: left;

            white-space: nowrap;
        }


        .activities-table td {
            height: 68px;

            padding:
                9px
                10px;

            border-bottom:
                1px solid #edf1f5;

            color: #334155;

            font-size: 12px;

            vertical-align: middle;
        }


        .activities-table tbody tr:hover {
            background: #f8fbff;
        }


        .activities-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* =========================================================
           ACTIVITY COLUMN WIDTHS
        ========================================================= */

        .activities-table th:nth-child(1),
        .activities-table td:nth-child(1) {
            width: 5%;
        }


        .activities-table th:nth-child(2),
        .activities-table td:nth-child(2) {
            width: 11%;
        }


        .activities-table th:nth-child(3),
        .activities-table td:nth-child(3) {
            width: 15%;
        }


        .activities-table th:nth-child(4),
        .activities-table td:nth-child(4) {
            width: 21%;
        }


        .activities-table th:nth-child(5),
        .activities-table td:nth-child(5) {
            width: 14%;
        }


        .activities-table th:nth-child(6),
        .activities-table td:nth-child(6) {
            width: 15%;
        }


        .activities-table th:nth-child(7),
        .activities-table td:nth-child(7) {
            width: 19%;
        }


        /* =========================================================
           CELLS
        ========================================================= */

        .cell-text {
            display: block;

            width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #475569;
        }


        .cell-muted {
            color: #94a3b8;
        }


        .record-title {
            display: block;

            color: #0f172a;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .record-id {
            display: block;

            margin-top: 3px;

            color: #94a3b8;

            font-size: 10px;
        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status-badge {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                8px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .status-completed {
            background: #dcfce7;

            color: #15803d;
        }


        .status-pending {
            background: #fef3c7;

            color: #b45309;
        }


        .status-in-progress {
            background: #dbeafe;

            color: #1d4ed8;
        }


        .status-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }


        .status-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================================================
           PRIORITY
        ========================================================= */

        .priority-badge {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                8px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .priority-high {
            background: #fee2e2;

            color: #b91c1c;
        }


        .priority-medium {
            background: #fef3c7;

            color: #b45309;
        }


        .priority-low {
            background: #dcfce7;

            color: #15803d;
        }


        .priority-urgent {
            background: #fce7f3;

            color: #be185d;
        }


        .priority-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================================================
           ACTIVITY TYPE
        ========================================================= */

        .activity-type-badge {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                8px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .activity-call {
            background: #dbeafe;

            color: #1d4ed8;
        }


        .activity-meeting {
            background: #ede9fe;

            color: #6d28d9;
        }


        .activity-email {
            background: #cffafe;

            color: #0e7490;
        }


        .activity-note {
            background: #f1f5f9;

            color: #475569;
        }


        .activity-followup {
            background: #dcfce7;

            color: #15803d;
        }


        .activity-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================================================
           TABLE FOOTER
        ========================================================= */

        .table-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            min-height: 52px;

            padding:
                0
                18px;

            border-top:
                1px solid #edf1f5;

            color: #94a3b8;

            font-size: 11px;
        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {
            padding:
                50px
                20px;

            text-align: center;
        }


        .empty-icon {
            margin-bottom: 10px;

            font-size: 34px;
        }


        .empty-state h3 {
            margin:
                0
                0
                6px;

            color: #334155;

            font-size: 16px;
        }


        .empty-state p {
            margin: 0;

            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================================================
           DARK MODE
        ========================================================= */

        html.dark-mode body {
            background: #0f172a;

            color: #e2e8f0;
        }


        html.dark-mode .report-page-header,
        html.dark-mode .filter-card,
        html.dark-mode .summary-card,
        html.dark-mode .table-card {
            background: #111827;

            border-color: #1f2937;

            box-shadow: none;
        }


        html.dark-mode .report-title h1,
        html.dark-mode .filter-heading h3,
        html.dark-mode .summary-value,
        html.dark-mode .section-heading-left h2,
        html.dark-mode .table-header h2,
        html.dark-mode .record-title,
        html.dark-mode .empty-state h3 {
            color: #f8fafc;
        }


        html.dark-mode .report-title p,
        html.dark-mode .filter-heading p,
        html.dark-mode .filter-group label,
        html.dark-mode .summary-label,
        html.dark-mode .summary-description,
        html.dark-mode .section-heading-left p,
        html.dark-mode .section-count,
        html.dark-mode .table-header span,
        html.dark-mode .record-id,
        html.dark-mode .cell-muted,
        html.dark-mode .table-footer {
            color: #94a3b8;
        }


        html.dark-mode .back-button,
        html.dark-mode .filter-input,
        html.dark-mode .btn-secondary {
            background: #1e293b;

            border-color: #334155;

            color: #e2e8f0;
        }


        html.dark-mode .back-button:hover,
        html.dark-mode .btn-secondary:hover {
            background: #273449;
        }


        html.dark-mode .filter-input {
            background: #0f172a;
        }


        html.dark-mode .tasks-table th,
        html.dark-mode .activities-table th {
            background: #0f172a;

            border-color: #1f2937;

            color: #cbd5e1;
        }


        html.dark-mode .tasks-table td,
        html.dark-mode .activities-table td {
            border-color: #1f2937;

            color: #cbd5e1;
        }


        html.dark-mode .tasks-table tbody tr:hover,
        html.dark-mode .activities-table tbody tr:hover {
            background: #172033;
        }


        html.dark-mode .status-completed {
            background: #052e16;

            color: #86efac;
        }


        html.dark-mode .status-pending {
            background: #451a03;

            color: #fcd34d;
        }


        html.dark-mode .status-in-progress {
            background: #172554;

            color: #60a5fa;
        }


        html.dark-mode .status-cancelled {
            background: #450a0a;

            color: #fca5a5;
        }


        html.dark-mode .status-default {
            background: #1e293b;

            color: #cbd5e1;
        }


        html.dark-mode .priority-high {
            background: #450a0a;

            color: #fca5a5;
        }


        html.dark-mode .priority-medium {
            background: #451a03;

            color: #fcd34d;
        }


        html.dark-mode .priority-low {
            background: #052e16;

            color: #86efac;
        }


        html.dark-mode .priority-urgent {
            background: #500724;

            color: #f9a8d4;
        }


        html.dark-mode .priority-default {
            background: #1e293b;

            color: #cbd5e1;
        }


        html.dark-mode .activity-call {
            background: #172554;

            color: #60a5fa;
        }


        html.dark-mode .activity-meeting {
            background: #2e1065;

            color: #c4b5fd;
        }


        html.dark-mode .activity-email {
            background: #083344;

            color: #67e8f9;
        }


        html.dark-mode .activity-note,
        html.dark-mode .activity-default {
            background: #1e293b;

            color: #cbd5e1;
        }


        html.dark-mode .activity-followup {
            background: #052e16;

            color: #86efac;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }


            .filter-form {
                align-items: stretch;
            }

        }


        @media (max-width: 760px) {

            .main-content {
                padding: 20px;
            }


            .report-page-header {
                flex-direction: column;

                align-items: stretch;
            }


            .back-button {
                width: 100%;
            }


            .filter-form {
                flex-direction: column;
            }


            .filter-group {
                width: 100%;
            }


            .filter-actions {
                width: 100%;
            }


            .filter-actions .btn {
                flex: 1;
            }


            .summary-grid {
                grid-template-columns: 1fr;
            }


            .table-wrapper {
                overflow-x: auto;
            }


            .tasks-table {
                min-width: 1050px;
            }


            .activities-table {
                min-width: 1100px;
            }

        }

    </style>

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-wrap">


        <!-- =====================================================
             REPORT HEADER
        ====================================================== -->

        <div class="report-page-header">

            <div class="report-header-left">

                <div class="report-icon">
                    ✅
                </div>


                <div class="report-title">

                    <h1>
                        Tasks & Activities Report
                    </h1>

                    <p>
                        Review tasks, priorities, activities, calls, meetings, emails and customer interactions.
                    </p>

                </div>

            </div>


            <a
                href="index.php"
                class="back-button"
            >
                ← Reports
            </a>

        </div>


        <!-- =====================================================
             FILTERS
        ====================================================== -->

        <div class="filter-card">

            <div class="filter-heading">

                <h3>
                    Report Filters
                </h3>

                <p>
                    Filter tasks by due date and activities by activity date.
                </p>

            </div>


            <form
                method="GET"
                class="filter-form"
            >


                <div class="filter-group">

                    <label for="from_date">
                        From Date
                    </label>

                    <input
                        type="date"
                        id="from_date"
                        name="from_date"
                        class="filter-input"
                        value="<?php
                        echo htmlspecialchars(
                            $from_date
                        );
                        ?>"
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
                        class="filter-input"
                        value="<?php
                        echo htmlspecialchars(
                            $to_date
                        );
                        ?>"
                    >

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Apply Filter
                    </button>


                    <a
                        href="tasks_activities.php"
                        class="btn btn-secondary"
                    >
                        Clear
                    </a>


                    <a
                        href="../exports/tasks_csv.php?from_date=<?php
                        echo urlencode(
                            $from_date
                        );
                        ?>&to_date=<?php
                        echo urlencode(
                            $to_date
                        );
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Tasks CSV
                    </a>


                    <a
                        href="../exports/tasks_excel.php?from_date=<?php
                        echo urlencode(
                            $from_date
                        );
                        ?>&to_date=<?php
                        echo urlencode(
                            $to_date
                        );
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Tasks Excel
                    </a>


                    <a
                        href="../exports/tasks_pdf.php?from_date=<?php
                        echo urlencode(
                            $from_date
                        );
                        ?>&to_date=<?php
                        echo urlencode(
                            $to_date
                        );
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Tasks PDF
                    </a>


                    <a
                        href="../exports/activities_csv.php?from_date=<?php
                        echo urlencode(
                            $from_date
                        );
                        ?>&to_date=<?php
                        echo urlencode(
                            $to_date
                        );
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Activities CSV
                    </a>


                    <a
                        href="../exports/activities_excel.php?from_date=<?php
                        echo urlencode(
                            $from_date
                        );
                        ?>&to_date=<?php
                        echo urlencode(
                            $to_date
                        );
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Activities Excel
                    </a>


                    <a
                        href="../exports/activities_pdf.php?from_date=<?php
                        echo urlencode(
                            $from_date
                        );
                        ?>&to_date=<?php
                        echo urlencode(
                            $to_date
                        );
                        ?>"
                        class="btn btn-success"
                    >
                        ↓ Activities PDF
                    </a>

                </div>

            </form>

        </div>


        <!-- =====================================================
             TASK OVERVIEW
        ====================================================== -->

        <div class="section-heading">

            <div class="section-heading-left">

                <h2>
                    Task Overview
                </h2>

                <p>
                    Task status and priority summary
                </p>

            </div>


            <div class="section-count">

                <?php
                echo $total_tasks;
                ?>

                task<?php
                echo $total_tasks === 1
                    ? ""
                    : "s";
                ?>

            </div>

        </div>


        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Tasks
                </div>

                <div class="summary-value">

                    <?php
                    echo $total_tasks;
                    ?>

                </div>

                <div class="summary-description">
                    Tasks in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Pending Tasks
                </div>

                <div class="summary-value">

                    <?php
                    echo $pending_tasks;
                    ?>

                </div>

                <div class="summary-description">
                    Tasks not yet completed
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Completed Tasks
                </div>

                <div class="summary-value">

                    <?php
                    echo $completed_tasks;
                    ?>

                </div>

                <div class="summary-description">
                    Completed task records
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    High Priority Tasks
                </div>

                <div class="summary-value">

                    <?php
                    echo $high_priority_tasks;
                    ?>

                </div>

                <div class="summary-description">
                    Tasks marked high priority
                </div>

            </div>

        </div>


        <!-- =====================================================
             TASK DETAILS
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <h2>
                    Task Details
                </h2>


                <span>

                    <?php
                    echo $total_tasks;
                    ?>

                    record<?php
                    echo $total_tasks === 1
                        ? ""
                        : "s";
                    ?>

                </span>

            </div>


            <div class="table-wrapper">


                <?php if (!empty($tasks)): ?>


                    <table class="tasks-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Task
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Due Date
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Assigned To
                                </th>

                                <th>
                                    Contact
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($tasks as $task): ?>

                                <?php

                                $task_title =
                                    trim(
                                        $task[
                                            "title"
                                        ] ?? ""
                                    );


                                $description =
                                    trim(
                                        $task[
                                            "description"
                                        ] ?? ""
                                    );


                                $status =
                                    trim(
                                        $task[
                                            "status"
                                        ] ?? ""
                                    );


                                $priority =
                                    trim(
                                        $task[
                                            "priority"
                                        ] ?? ""
                                    );


                                $status_class =
                                    taskStatusClass(
                                        $status
                                    );


                                $status_label =
                                    taskStatusLabel(
                                        $status
                                    );


                                $priority_class =
                                    taskPriorityClass(
                                        $priority
                                    );


                                $priority_label =
                                    taskPriorityLabel(
                                        $priority
                                    );


                                $contact_name = "";


                                if (
                                    !empty(
                                        $task[
                                            "first_name"
                                        ]
                                    ) ||
                                    !empty(
                                        $task[
                                            "last_name"
                                        ]
                                    )
                                ) {

                                    $contact_name =
                                        trim(
                                            (
                                                $task[
                                                    "first_name"
                                                ] ?? ""
                                            )
                                            .
                                            " "
                                            .
                                            (
                                                $task[
                                                    "last_name"
                                                ] ?? ""
                                            )
                                        );
                                }

                                ?>


                                <tr>


                                    <td>

                                        <?php
                                        echo (int)
                                            $task["id"];
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="record-title"
                                            title="<?php
                                            echo htmlspecialchars(
                                                $task_title
                                            );
                                            ?>"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $task_title !== ""
                                                    ? $task_title
                                                    : "Untitled Task"
                                            );

                                            ?>

                                        </span>


                                        <span class="record-id">

                                            Task #<?php
                                            echo (int)
                                                $task["id"];
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if ($description !== ""): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <span class="cell-text">

                                            <?php
                                            echo htmlspecialchars(
                                                formatReportDate(
                                                    $task[
                                                        "due_date"
                                                    ] ?? ""
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="priority-badge <?php
                                            echo htmlspecialchars(
                                                $priority_class
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $priority_label
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge <?php
                                            echo htmlspecialchars(
                                                $status_class
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $status_label
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if (!empty($task["assigned_user"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $task[
                                                        "assigned_user"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $task[
                                                        "assigned_user"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if ($contact_name !== ""): ?>

                                            <span
                                                class="cell-text"
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

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

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
                            No Tasks Found
                        </h3>

                        <p>
                            No task records match the selected date range.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


            <div class="table-footer">

                <span>

                    Showing
                    <?php
                    echo $total_tasks;
                    ?>
                    task<?php
                    echo $total_tasks === 1
                        ? ""
                        : "s";
                    ?>

                </span>


                <span>
                    Tasks Report
                </span>

            </div>

        </div>


        <!-- =====================================================
             ACTIVITY OVERVIEW
        ====================================================== -->

        <div class="section-heading">

            <div class="section-heading-left">

                <h2>
                    Activity Overview
                </h2>

                <p>
                    Customer interaction and communication summary
                </p>

            </div>


            <div class="section-count">

                <?php
                echo $total_activities;
                ?>

                activit<?php
                echo $total_activities === 1
                    ? "y"
                    : "ies";
                ?>

            </div>

        </div>


        <div class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Activities
                </div>

                <div class="summary-value">

                    <?php
                    echo $total_activities;
                    ?>

                </div>

                <div class="summary-description">
                    Activities in selected period
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Calls
                </div>

                <div class="summary-value">

                    <?php
                    echo $calls;
                    ?>

                </div>

                <div class="summary-description">
                    Call interactions recorded
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Meetings
                </div>

                <div class="summary-value">

                    <?php
                    echo $meetings;
                    ?>

                </div>

                <div class="summary-description">
                    Meeting activities recorded
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Emails
                </div>

                <div class="summary-value">

                    <?php
                    echo $emails;
                    ?>

                </div>

                <div class="summary-description">
                    Email interactions recorded
                </div>

            </div>

        </div>


        <!-- =====================================================
             ACTIVITY DETAILS
        ====================================================== -->

        <div class="table-card">

            <div class="table-header">

                <h2>
                    Activity Details
                </h2>


                <span>

                    <?php
                    echo $total_activities;
                    ?>

                    record<?php
                    echo $total_activities === 1
                        ? ""
                        : "s";
                    ?>

                </span>

            </div>


            <div class="table-wrapper">


                <?php if (!empty($activities)): ?>


                    <table class="activities-table">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Subject
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Activity Date
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Created By
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($activities as $activity): ?>

                                <?php

                                $activity_type =
                                    trim(
                                        $activity[
                                            "type"
                                        ] ?? ""
                                    );


                                $subject =
                                    trim(
                                        $activity[
                                            "subject"
                                        ] ?? ""
                                    );


                                $description =
                                    trim(
                                        $activity[
                                            "description"
                                        ] ?? ""
                                    );


                                $type_class =
                                    activityTypeClass(
                                        $activity_type
                                    );


                                $type_label =
                                    activityTypeLabel(
                                        $activity_type
                                    );


                                $activity_contact = "";


                                if (
                                    !empty(
                                        $activity[
                                            "first_name"
                                        ]
                                    ) ||
                                    !empty(
                                        $activity[
                                            "last_name"
                                        ]
                                    )
                                ) {

                                    $activity_contact =
                                        trim(
                                            (
                                                $activity[
                                                    "first_name"
                                                ] ?? ""
                                            )
                                            .
                                            " "
                                            .
                                            (
                                                $activity[
                                                    "last_name"
                                                ] ?? ""
                                            )
                                        );
                                }

                                ?>


                                <tr>


                                    <td>

                                        <?php
                                        echo (int)
                                            $activity["id"];
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="activity-type-badge <?php
                                            echo htmlspecialchars(
                                                $type_class
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $type_label
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if ($subject !== ""): ?>

                                            <span
                                                class="record-title"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $subject
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $subject
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if ($description !== ""): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <span class="cell-text">

                                            <?php
                                            echo htmlspecialchars(
                                                formatReportDateTime(
                                                    $activity[
                                                        "activity_date"
                                                    ] ?? ""
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if ($activity_contact !== ""): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $activity_contact
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $activity_contact
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (!empty($activity["created_by_name"])): ?>

                                            <span
                                                class="cell-text"
                                                title="<?php
                                                echo htmlspecialchars(
                                                    $activity[
                                                        "created_by_name"
                                                    ]
                                                );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $activity[
                                                        "created_by_name"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="cell-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>


                <?php else: ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            📅
                        </div>

                        <h3>
                            No Activities Found
                        </h3>

                        <p>
                            No activity records match the selected date range.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


            <div class="table-footer">

                <span>

                    Showing
                    <?php
                    echo $total_activities;
                    ?>

                    activit<?php

                    echo $total_activities === 1
                        ? "y"
                        : "ies";

                    ?>

                </span>


                <span>
                    Activities Report
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>