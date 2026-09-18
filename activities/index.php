<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   ACTIVITIES
============================== */

$stmt = $conn->query("
    SELECT
        a.id,
        a.contact_id,
        a.customer_id,
        a.deal_id,
        a.type,
        a.subject,
        a.description,
        a.activity_date,
        a.created_by,
        a.created_at,

        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,

        cu.customer_code,

        d.title AS deal_title,

        u.name AS created_by_name

    FROM activities a

    LEFT JOIN contacts ct
        ON ct.id = a.contact_id

    LEFT JOIN customers cu
        ON cu.id = a.customer_id

    LEFT JOIN deals d
        ON d.id = a.deal_id

    LEFT JOIN users u
        ON u.id = a.created_by

    ORDER BY a.id DESC
");

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_activities = count($activities);

$call_activities = 0;
$meeting_activities = 0;
$email_activities = 0;
$other_activities = 0;

foreach ($activities as $activity) {

    $type = strtolower(
        trim(
            $activity["type"] ?? ""
        )
    );

    switch ($type) {

        case "call":
        case "phone":
        case "phone call":

            $call_activities++;
            break;

        case "meeting":
        case "meet":

            $meeting_activities++;
            break;

        case "email":
        case "mail":

            $email_activities++;
            break;

        default:

            $other_activities++;
            break;
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

    <title>Activities - CRM</title>

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
           PAGE HEADER
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

        .activities-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .activities-table th {
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

        .activities-table td {
            padding: 16px 12px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .activities-table tbody tr:hover {
            background: #f8fbff;
        }

        .activities-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================
           COLUMN SIZING
           TOTAL = 100%
        ========================= */

        .sno-column {
            width: 5%;
        }

        .activity-column {
            width: 17%;
        }

        .type-column {
            width: 10%;
        }

        .contact-column {
            width: 12%;
        }

        .customer-column {
            width: 10%;
        }

        .deal-column {
            width: 11%;
        }

        .date-column {
            width: 12%;
        }

        .created-column {
            width: 10%;
        }

        .action-column {
            width: 13%;
        }

        /* =========================
           DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;

            white-space: nowrap;
        }

        .activity-cell {
            min-width: 0;
        }

        .activity-subject {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .activity-id {
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
           ACTIVITY TYPE
        ========================= */

        .type-badge {
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

        .type-call {
            background: #dcfce7;

            color: #15803d;
        }

        .type-meeting {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .type-email {
            background: #fef3c7;

            color: #b45309;
        }

        .type-note {
            background: #f1f5f9;

            color: #475569;
        }

        .type-task {
            background: #ede9fe;

            color: #6d28d9;
        }

        .type-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================
           ACTIVITY DATE
        ========================= */

        .activity-date {
            white-space: nowrap;

            color: #475569;

            font-size: 12px;
        }

        .date-past {
            color: #64748b;
        }

        .date-today {
            color: #2563eb;

            font-weight: 700;
        }

        /* =========================
           ACTION
        ========================= */

        .activities-table th.action-column {
            text-align: center;
        }

        .activities-table td.action-column {
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

            .activities-table th {
                padding: 13px 9px;

                font-size: 10px;
            }

            .activities-table td {
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

            .activities-table th {
                padding: 12px 7px;

                font-size: 9px;
            }

            .activities-table td {
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
                📅
            </div>

            <div>

                <h1>
                    Activities
                </h1>

                <p>
                    Manage calls, meetings, emails and customer interactions
                </p>

            </div>

        </div>

        <a
            href="add.php"
            class="add-btn"
        >
            + Add Activity
        </a>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Activities
            </div>

            <div class="summary-value">
                <?= number_format($total_activities); ?>
            </div>

            <div class="summary-small">
                All activity records
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Calls
            </div>

            <div class="summary-value">
                <?= number_format($call_activities); ?>
            </div>

            <div class="summary-small">
                Phone interactions
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Meetings
            </div>

            <div class="summary-value">
                <?= number_format($meeting_activities); ?>
            </div>

            <div class="summary-small">
                Customer meetings
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Emails
            </div>

            <div class="summary-value">
                <?= number_format($email_activities); ?>
            </div>

            <div class="summary-small">
                Email interactions
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
                    id="activitySearch"
                    placeholder="Search activities..."
                    autocomplete="off"
                >

            </div>

            <select
                id="typeFilter"
                class="filter-select"
            >

                <option value="">
                    All Types
                </option>

                <option value="call">
                    Call
                </option>

                <option value="meeting">
                    Meeting
                </option>

                <option value="email">
                    Email
                </option>

                <option value="note">
                    Note
                </option>

                <option value="task">
                    Task
                </option>

            </select>

        </div>

        <a
            href="../exports/activities_csv.php"
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
                class="activities-table"
                id="activitiesTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="activity-column">
                            Activity
                        </th>

                        <th class="type-column">
                            Type
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

                        <th class="date-column">
                            Activity Date
                        </th>

                        <th class="created-column">
                            Created By
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($activities) > 0): ?>

                    <?php foreach (
                        $activities
                        as $index => $activity
                    ): ?>

                        <?php

                        $type =
                            strtolower(
                                trim(
                                    $activity["type"] ?? ""
                                )
                            );

                        switch ($type) {

                            case "call":
                            case "phone":
                            case "phone call":

                                $type_class =
                                    "type-call";

                                break;

                            case "meeting":
                            case "meet":

                                $type_class =
                                    "type-meeting";

                                break;

                            case "email":
                            case "mail":

                                $type_class =
                                    "type-email";

                                break;

                            case "note":

                                $type_class =
                                    "type-note";

                                break;

                            case "task":

                                $type_class =
                                    "type-task";

                                break;

                            default:

                                $type_class =
                                    "type-default";

                                break;
                        }

                        $type_label =
                            $activity["type"]
                            ?? "Unknown";

                        $contact_name =
                            trim(
                                $activity[
                                    "contact_name"
                                ] ?? ""
                            );

                        $activity_date =
                            $activity[
                                "activity_date"
                            ] ?? "";

                        $date_class = "";

                        if (
                            !empty(
                                $activity_date
                            )
                        ) {

                            $activity_date_only =
                                date(
                                    "Y-m-d",
                                    strtotime(
                                        $activity_date
                                    )
                                );

                            if (
                                $activity_date_only
                                === date("Y-m-d")
                            ) {

                                $date_class =
                                    "date-today";

                            } elseif (
                                $activity_date_only
                                < date("Y-m-d")
                            ) {

                                $date_class =
                                    "date-past";
                            }
                        }

                        ?>

                        <tr
                            data-type="<?= htmlspecialchars($type); ?>"
                        >

                            <!-- S.NO -->

                            <td class="serial">

                                <?= $index + 1; ?>

                            </td>

                            <!-- ACTIVITY -->

                            <td class="activity-cell">

                                <div class="activity-subject">

                                    <?= htmlspecialchars(
                                        $activity[
                                            "subject"
                                        ] ?? "—"
                                    ); ?>

                                </div>

                                <div class="activity-id">

                                    Activity ID:
                                    <?= (int) $activity["id"]; ?>

                                </div>

                            </td>

                            <!-- TYPE -->

                            <td>

                                <span
                                    class="
                                        type-badge
                                        <?= $type_class; ?>
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $type_label
                                    ); ?>

                                </span>

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
                                        $activity[
                                            "customer_code"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $activity[
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
                                        $activity[
                                            "deal_title"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $activity[
                                                "deal_title"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ACTIVITY DATE -->

                            <td>

                                <?php if (
                                    !empty(
                                        $activity_date
                                    )
                                ): ?>

                                    <span
                                        class="
                                            activity-date
                                            <?= $date_class; ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            date(
                                                "d M Y H:i",
                                                strtotime(
                                                    $activity_date
                                                )
                                            )
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CREATED BY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $activity[
                                            "created_by_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $activity[
                                                "created_by_name"
                                            ]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?= (int) $activity["id"]; ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?= (int) $activity["id"]; ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?= (int) $activity["id"]; ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this activity?'
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

                        <td colspan="9">

                            <div class="empty-state">

                                <div class="empty-icon">
                                    📅
                                </div>

                                <h3>
                                    No activities found
                                </h3>

                                <p>
                                    Add your first activity
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
                    <?= count($activities); ?>
                </strong>

                activities

            </span>

            <span>
                CRM Activity Management
            </span>

        </div>

    </div>

</div>

<script>

/* =========================
   SEARCH + FILTER
========================= */

const activitySearch =
    document.getElementById(
        "activitySearch"
    );

const typeFilter =
    document.getElementById(
        "typeFilter"
    );

const activityRows =
    document.querySelectorAll(
        "#activitiesTable tbody tr[data-type]"
    );

function filterActivities() {

    const searchValue =
        activitySearch.value
            .toLowerCase()
            .trim();

    const typeValue =
        typeFilter.value
            .toLowerCase()
            .trim();

    activityRows.forEach(function(row) {

        const rowText =
            row.textContent
                .toLowerCase();

        const rowType =
            row.dataset.type
                .toLowerCase();

        const matchesSearch =
            rowText.includes(
                searchValue
            );

        const matchesType =
            typeValue === ""
            ||
            rowType === typeValue
            ||
            (
                typeValue === "call" &&
                (
                    rowType === "phone" ||
                    rowType === "phone call"
                )
            )
            ||
            (
                typeValue === "meeting" &&
                rowType === "meet"
            )
            ||
            (
                typeValue === "email" &&
                rowType === "mail"
            );

        row.style.display =
            matchesSearch &&
            matchesType
                ? ""
                : "none";

    });
}

activitySearch.addEventListener(
    "input",
    filterActivities
);

typeFilter.addEventListener(
    "change",
    filterActivities
);

</script>

</body>

</html>