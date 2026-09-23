<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   DASHBOARD STATISTICS
============================== */

function getCount($conn, $table)
{
    $stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
    return (int) $stmt->fetchColumn();
}

$contact_count  = getCount($conn, "contacts");
$lead_count     = getCount($conn, "leads");
$deal_count     = getCount($conn, "deals");
$product_count  = getCount($conn, "products");

/* ==============================
   TOTAL REVENUE
============================== */

$stmt = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM sales
");

$total_revenue = (float) $stmt->fetchColumn();

/* ==============================
   CLOSED DEALS
============================== */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM deals
    WHERE LOWER(REPLACE(stage, '_', ' ')) IN ('closed won', 'won', 'closed')
");

$closed_deals = (int) $stmt->fetchColumn();

/* ==============================
   PENDING TASK NOTIFICATIONS
============================== */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM tasks
    WHERE status NOT IN ('Completed', 'completed')
");

$notification_count = (int) $stmt->fetchColumn();

/* ==============================
   DEAL STAGES
============================== */

$stage_names = [
    "Prospecting",
    "Qualification",
    "Proposal",
    "Negotiation",
    "Closed Won"
];

$stage_counts = [];

foreach ($stage_names as $stage) {

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM deals
        WHERE LOWER(REPLACE(stage, '_', ' ')) = LOWER(:stage)
    ");

    $stmt->execute([
        ":stage" => $stage
    ]);

    $stage_counts[$stage] = (int) $stmt->fetchColumn();
}

/* ==============================
   TOTAL DEALS
============================== */

$total_deals = array_sum($stage_counts);

if ($total_deals == 0) {
    $total_deals = $deal_count;
}

/* ==============================
   RECENT CONTACTS
============================== */

$stmt = $conn->query("
    SELECT
        first_name,
        last_name,
        email,
        phone,
        job_title,
        created_at
    FROM contacts
    ORDER BY created_at DESC
    LIMIT 4
");

$recent_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   UPCOMING TASKS
============================== */

$stmt = $conn->query("
    SELECT
        title,
        due_date,
        priority,
        status
    FROM tasks
    WHERE due_date >= CURDATE()
    ORDER BY due_date ASC
    LIMIT 4
");

$upcoming_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SALES LAST 30 DAYS
============================== */

$stmt = $conn->query("
    SELECT
        DATE(sale_date) AS sale_day,
        COALESCE(SUM(total_amount), 0) AS revenue
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY sale_day ASC
");

$sales_chart = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   PREPARE REVENUE CHART DATA
============================== */

$sales_by_date = [];

foreach ($sales_chart as $row) {

    $sales_by_date[$row["sale_day"]] = (float) $row["revenue"];

}

$chart_values = [];

for ($i = 29; $i >= 0; $i--) {

    $date = date(
        "Y-m-d",
        strtotime("-" . $i . " days")
    );

    $chart_values[] = $sales_by_date[$date] ?? 0;

}

$max_revenue = max($chart_values);

if ($max_revenue <= 0) {

    $max_revenue = 1;

}

$chart_points = [];

foreach ($chart_values as $index => $revenue) {

    $x = ($index / 29) * 500;

    $y = 210 - (
        ($revenue / $max_revenue) * 190
    );

    $chart_points[] =
        round($x, 2) . "," .
        round($y, 2);

}

$points_string = implode(
    " ",
    $chart_points
);

/* ==============================
   FORMAT REVENUE
============================== */

function formatRevenue($amount)
{
    if ($amount >= 10000000) {

        return "₹" .
            number_format(
                $amount / 10000000,
                1
            ) .
            "Cr";
    }

    if ($amount >= 100000) {

        return "₹" .
            number_format(
                $amount / 100000,
                1
            ) .
            "L";
    }

    if ($amount >= 1000) {

        return "₹" .
            number_format(
                $amount / 1000,
                1
            ) .
            "K";
    }

    return "₹" .
        number_format(
            $amount,
            0
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

    <title>CRM Dashboard</title>

    <script>
        (function () {
            try {
                if (localStorage.getItem("crmTheme") === "dark") {
                    document.documentElement.classList.add("dark-mode");
                }
            } catch (error) {
                /* Ignore storage access errors and keep the default theme. */
            }
        })();
    </script>

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
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 0;
            background: #f5f7fb;
        }

        /* ==============================
           TOP BAR
        ============================== */

        .dashboard-topbar {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .search-box {
            width: 470px;
            height: 42px;
            border: 1px solid #dce1e8;
            border-radius: 8px;
            display: flex;
            align-items: center;
            padding: 0 14px;
            color: #9ca3af;
            background: #ffffff;
        }

        .search-box span {
            font-size: 18px;
            margin-right: 10px;
        }

        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
            color: #374151;
        }

        .search-box input::placeholder {
            color: #9ca3af;
        }

        .search-wrapper {
            position: relative;
            width: 470px;
        }

        .search-wrapper .search-box {
            width: 100%;
        }

        .search-results {
            position: absolute;
            top: 48px;
            left: 0;
            width: 100%;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, 0.10);
            display: none;
            z-index: 2000;
            overflow: hidden;
        }

        .search-result {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: #172033;
            border-bottom: 1px solid #f1f3f5;
        }

        .search-result:hover {
            background: #f8fafc;
        }

        .search-result-type {
            font-size: 10px;
            color: #2563eb;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .search-result-title {
            font-size: 13px;
            font-weight: bold;
        }

        .search-result-description {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }

        .search-no-result {
            padding: 15px;
            color: #94a3b8;
            font-size: 12px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .topbar-icon {
            position: relative;
            font-size: 21px;
            color: #475569;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 18px;
            border-left: 1px solid #e5e7eb;
        }

        .profile-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .profile-name {
            font-size: 14px;
            font-weight: bold;
        }

        .profile-role {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ==============================
           DASHBOARD CONTENT
        ============================== */

        .dashboard-container {
            padding: 25px;
        }

        .dashboard-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .dashboard-heading h1 {
            margin: 0;
            font-size: 30px;
            color: #172033;
        }

        .dashboard-heading p {
            margin: 5px 0 0;
            color: #7b8798;
            font-size: 14px;
        }

        .date-filter {
            background: white;
            border: 1px solid #dce1e8;
            border-radius: 8px;
            padding: 11px 15px;
            color: #475569;
            font-size: 13px;
        }

        /* ==============================
           STAT CARDS
        ============================== */

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e7eaf0;
            border-radius: 9px;
            padding: 20px;
            min-height: 135px;
            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.03);
            text-decoration: none;
            color: inherit;
            display: block;
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow:
                0 5px 15px
                rgba(0, 0, 0, 0.08);
        }

        .stat-top {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            flex-shrink: 0;
        }

        .icon-blue {
            background: #dbeafe;
        }

        .icon-green {
            background: #d1fae5;
        }

        .icon-purple {
            background: #ede9fe;
        }

        .icon-orange {
            background: #fef3c7;
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
            margin-top: 3px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-top: 7px;
            color: #172033;
        }

        .stat-bottom {
            margin-top: 13px;
            font-size: 12px;
            color: #94a3b8;
        }

        .growth {
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }

        /* ==============================
           MIDDLE GRID
        ============================== */

        .middle-grid {
            display: grid;
            grid-template-columns:
                1.15fr 1.35fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }

        .dashboard-card {
            background: white;
            border: 1px solid #e7eaf0;
            border-radius: 9px;
            padding: 18px;
            min-height: 300px;
            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.03);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 16px;
            color: #172033;
        }

        .card-link {
            color: #2563eb;
            font-size: 12px;
            text-decoration: none;
        }

        /* ==============================
           SALES PIPELINE
        ============================== */

        .pipeline-container {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .funnel {
            width: 175px;
        }

        .funnel-row {
            height: 38px;
            margin-bottom: 5px;
            clip-path:
                polygon(
                    8% 0,
                    92% 0,
                    82% 100%,
                    18% 100%
                );
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }

        .funnel-1 {
            background: #3b82f6;
        }

        .funnel-2 {
            background: #14b8a6;
        }

        .funnel-3 {
            background: #84cc16;
        }

        .funnel-4 {
            background: #f59e0b;
        }

        .funnel-5 {
            background: #f97316;
        }

        .funnel-6 {
            background: #ef4444;
        }

        .pipeline-list {
            flex: 1;
        }

        .pipeline-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 17px;
        }

        .pipeline-name {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #64748b;
        }

        .pipeline-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .pipeline-value {
            font-weight: bold;
            color: #475569;
        }

        /* ==============================
           REVENUE CHART
        ============================== */

        .revenue-total {
            text-align: right;
        }

        .revenue-total strong {
            font-size: 15px;
        }

        .revenue-total span {
            font-size: 11px;
            color: #10b981;
            margin-left: 5px;
        }

        .chart {
            position: relative;
            height: 220px;
            margin-top: 10px;
            border-bottom: 1px solid #e5e7eb;
            border-left: 1px solid #e5e7eb;
            background-image:
                linear-gradient(
                    #eef1f5 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    #eef1f5 1px,
                    transparent 1px
                );
            background-size: 25% 25%;
        }

        .chart svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        .chart-labels {
            display: flex;
            justify-content: space-between;
            color: #94a3b8;
            font-size: 10px;
            margin-top: 8px;
        }

        /* ==============================
           DEALS BY STAGE
        ============================== */

        .donut-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            margin-top: 25px;
        }

        .donut {
            width: 155px;
            height: 155px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .donut-inner {
            width: 93px;
            height: 93px;
            background: white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .donut-inner strong {
            font-size: 22px;
        }

        .donut-inner span {
            color: #94a3b8;
            font-size: 10px;
        }

        .stage-list {
            flex: 1;
        }

        .stage-item {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 13px;
            font-size: 11px;
            color: #64748b;
        }

        .stage-color {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .stage-item strong {
            margin-left: auto;
            color: #475569;
        }

        /* ==============================
           BOTTOM GRID
        ============================== */

        .bottom-grid {
            display: grid;
            grid-template-columns:
                1.2fr 1fr 1fr;
            gap: 18px;
        }

        .bottom-card {
            background: white;
            border: 1px solid #e7eaf0;
            border-radius: 9px;
            padding: 18px;
            min-height: 285px;
            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.03);
        }

        /* ==============================
           CONTACTS
        ============================== */

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .contact-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: bold;
        }

        .contact-info {
            flex: 1;
        }

        .contact-name {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
        }

        .contact-email {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 3px;
        }

        .contact-time {
            font-size: 9px;
            color: #94a3b8;
        }

        /* ==============================
           TASKS
        ============================== */

        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .task-check {
            width: 17px;
            height: 17px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .task-info {
            flex: 1;
        }

        .task-title {
            font-size: 12px;
            color: #334155;
        }

        .task-meta {
            margin-top: 5px;
            font-size: 9px;
            color: #94a3b8;
        }

        .task-priority {
            font-size: 9px;
            color: #ef4444;
        }

        /* ==============================
           SALES REPS
        ============================== */

        .rep-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .rep-rank {
            width: 20px;
            font-size: 12px;
            color: #475569;
        }

        .rep-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #ede9fe;
            color: #7c3aed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 11px;
        }

        .rep-info {
            flex: 1;
        }

        .rep-name {
            font-size: 11px;
            font-weight: bold;
        }

        .rep-bar {
            height: 5px;
            background: #e5e7eb;
            border-radius: 10px;
            margin-top: 7px;
            overflow: hidden;
        }

        .rep-bar span {
            display: block;
            height: 100%;
            background: #3b82f6;
            border-radius: 10px;
        }

        .rep-sales {
            font-size: 10px;
            color: #64748b;
            text-align: right;
        }

        /* ==============================
           RESPONSIVE
        ============================== */

        @media (max-width: 1200px) {

            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .middle-grid {
                grid-template-columns: 1fr;
            }

            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .search-box {
                width: 350px;
            }
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;
            }

            .dashboard-topbar {
                padding: 0 15px;
            }

            .search-box {
                width: 250px;
            }

            .dashboard-container {
                padding: 15px;
            }

            .dashboard-heading {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stat-grid {
                grid-template-columns: 1fr;
            }
        }


        /* ==============================
           DARK / LIGHT MODE
        ============================== */

        .theme-toggle {
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #ffffff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .theme-toggle:hover {
            background: #f8fafc;
            color: #2563eb;
            transform: translateY(-1px);
        }

        /* Keep sidebar visually consistent with the selected theme. */
        html.dark-mode .sidebar {
            background: #0f172a;
            color: #e2e8f0;
            border-right-color: #1e293b;
        }

        html.dark-mode .sidebar-logo {
            border-bottom-color: #1e293b;
        }

        html.dark-mode .sidebar-logo h2 {
            color: #f8fafc;
        }

        html.dark-mode .sidebar-logo p {
            color: #94a3b8;
        }

        html.dark-mode .sidebar-menu a,
        html.dark-mode .sidebar-bottom a {
            color: #cbd5e1;
        }

        html.dark-mode .sidebar-menu a:hover,
        html.dark-mode .sidebar-bottom a:hover {
            background: #1e293b;
            color: #60a5fa;
        }

        html.dark-mode .sidebar-menu a.active,
        html.dark-mode .sidebar-menu a.active:hover {
            background: #1e3a8a;
            color: #93c5fd;
        }

        html.dark-mode .sidebar-bottom {
            border-top-color: #1e293b;
        }

        html.dark-mode body,
        html.dark-mode .main-content {
            background: #0b1120;
            color: #e5e7eb;
        }

        html.dark-mode .dashboard-topbar {
            background: #111827;
            border-bottom-color: #1f2937;
        }

        html.dark-mode .search-box {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }

        html.dark-mode .search-box input {
            background: transparent;
            color: #e2e8f0;
        }

        html.dark-mode .search-box input::placeholder {
            color: #64748b;
        }

        html.dark-mode .search-results {
            background: #111827;
            border-color: #334155;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }

        html.dark-mode .search-result {
            color: #e5e7eb;
            border-bottom-color: #1f2937;
        }

        html.dark-mode .search-result:hover {
            background: #1f2937;
        }

        html.dark-mode .search-result-description,
        html.dark-mode .search-no-result {
            color: #64748b;
        }

        html.dark-mode .topbar-icon {
            color: #cbd5e1;
        }

        html.dark-mode .profile {
            border-left-color: #334155;
            color: #e5e7eb;
        }

        html.dark-mode .profile-name {
            color: #f8fafc;
        }

        html.dark-mode .profile-role {
            color: #94a3b8;
        }

        html.dark-mode .theme-toggle {
            background: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }

        html.dark-mode .theme-toggle:hover {
            background: #1e293b;
            color: #fbbf24;
        }

        html.dark-mode .dashboard-heading h1,
        html.dark-mode .card-header h2,
        html.dark-mode .stat-number,
        html.dark-mode .dashboard-card h2,
        html.dark-mode .section-title,
        html.dark-mode .rep-name,
        html.dark-mode .contact-name,
        html.dark-mode .task-title {
            color: #f8fafc;
        }

        html.dark-mode .dashboard-heading p,
        html.dark-mode .stat-label,
        html.dark-mode .stat-bottom,
        html.dark-mode .pipeline-name,
        html.dark-mode .pipeline-value,
        html.dark-mode .stage-item,
        html.dark-mode .contact-email,
        html.dark-mode .contact-time,
        html.dark-mode .task-meta,
        html.dark-mode .rep-sales {
            color: #94a3b8;
        }

        html.dark-mode .date-filter,
        html.dark-mode .stat-card,
        html.dark-mode .dashboard-card,
        html.dark-mode .bottom-card {
            background: #111827;
            border-color: #1f2937;
            color: #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.20);
        }

        html.dark-mode .stat-card:hover,
        html.dark-mode .report-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.28);
        }

        html.dark-mode .date-filter {
            color: #cbd5e1;
        }

        html.dark-mode .chart {
            border-color: #334155;
            background-image:
                linear-gradient(#1f2937 1px, transparent 1px),
                linear-gradient(90deg, #1f2937 1px, transparent 1px);
        }

        html.dark-mode .donut-inner {
            background: #111827;
        }

        html.dark-mode .donut-inner span {
            color: #64748b;
        }

        html.dark-mode .chart-labels {
            color: #64748b;
        }

        html.dark-mode .contact-item,
        html.dark-mode .task-item,
        html.dark-mode .rep-item {
            border-bottom-color: #1f2937;
        }

        html.dark-mode .contact-avatar {
            background: #1e3a8a;
            color: #93c5fd;
        }

        html.dark-mode .task-check {
            border-color: #475569;
        }

        html.dark-mode .rep-avatar {
            background: #312e81;
            color: #c4b5fd;
        }

        html.dark-mode .rep-bar {
            background: #1f2937;
        }

        html.dark-mode .growth {
            color: #34d399;
        }

        @media (max-width: 768px) {
            .theme-toggle {
                width: 38px;
                height: 38px;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- TOP BAR -->

    <div class="dashboard-topbar">

        <div class="search-wrapper">

            <div class="search-box">

                <span>⌕</span>

                <input
                    type="text"
                    id="dashboardSearch"
                    placeholder="Search contacts, deals, or anything..."
                    autocomplete="off"
                >

            </div>

            <div
                id="searchResults"
                class="search-results"
            ></div>

        </div>

        <div class="topbar-right">

            <a
                href="../tasks/index.php"
                class="topbar-icon"
                style="text-decoration:none;"
                title="Notifications"
            >

                🔔

                <span class="notification-badge">
                    <?php
                    echo $notification_count;
                    ?>
                </span>

            </a>

            <button
                type="button"
                id="themeToggle"
                class="theme-toggle"
                aria-label="Switch to dark mode"
                title="Switch to dark mode"
            >
                🌙
            </button>

            <a
                href="../activities/index.php"
                class="topbar-icon"
                style="text-decoration:none;"
            >
                📅
            </a>

            <a
                href="../profile/index.php"
                class="profile"
                style="text-decoration: none; color: inherit;"
            >

                <div class="profile-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $_SESSION["user_name"],
                            0,
                            1
                        )
                    );

                    ?>

                </div>

                <div>

                    <div class="profile-name">

                        <?php

                        echo htmlspecialchars(
                            $_SESSION["user_name"]
                        );

                        ?>

                    </div>

                    <div class="profile-role">

                        <?php

                        echo htmlspecialchars(
                            $_SESSION["user_role"] ?? "User"
                        );

                        ?>

                    </div>

                </div>

                <span>⌄</span>

            </a>

        </div>

    </div>

    <div class="dashboard-container">

        <!-- DASHBOARD HEADING -->

        <div class="dashboard-heading">

            <div>

                <h1>Dashboard</h1>

                <p>

                    Welcome back,

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["user_name"]
                    );

                    ?>!

                    Here's what's happening with
                    your CRM today.

                </p>

            </div>

            <div class="date-filter">

                📅 Last 30 Days &nbsp;⌄

            </div>

        </div>

        <!-- STATISTICS -->

        <div class="stat-grid">

            <a
                href="../contacts/index.php"
                class="stat-card"
            >

                <div class="stat-top">

                    <div class="stat-icon icon-blue">
                        👥
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Contacts
                        </div>

                        <div class="stat-number">

                            <?php

                            echo number_format(
                                $contact_count
                            );

                            ?>

                        </div>

                    </div>

                </div>

                <div class="stat-bottom">

                    <span class="growth">
                        ↑ Active
                    </span>

                    CRM contacts

                </div>

            </a>

            <a
                href="../leads/index.php"
                class="stat-card"
            >

                <div class="stat-top">

                    <div class="stat-icon icon-green">
                        🎯
                    </div>

                    <div>

                        <div class="stat-label">
                            New Leads
                        </div>

                        <div class="stat-number">

                            <?php

                            echo number_format(
                                $lead_count
                            );

                            ?>

                        </div>

                    </div>

                </div>

                <div class="stat-bottom">

                    <span class="growth">
                        ↑ Leads
                    </span>

                    Total leads

                </div>

            </a>

            <a
                href="../deals/index.php"
                class="stat-card"
            >

                <div class="stat-top">

                    <div class="stat-icon icon-purple">
                        💼
                    </div>

                    <div>

                        <div class="stat-label">
                            Deals Closed
                        </div>

                        <div class="stat-number">

                            <?php

                            echo number_format(
                                $closed_deals
                            );

                            ?>

                        </div>

                    </div>

                </div>

                <div class="stat-bottom">

                    <span class="growth">
                        ↑ Deals
                    </span>

                    Closed deals

                </div>

            </a>

            <a
                href="../sales/index.php"
                class="stat-card"
            >

                <div class="stat-top">

                    <div class="stat-icon icon-orange">
                        ₹
                    </div>

                    <div>

                        <div class="stat-label">
                            Revenue
                        </div>

                        <div class="stat-number">

                            <?php

                            echo formatRevenue(
                                $total_revenue
                            );

                            ?>

                        </div>

                    </div>

                </div>

                <div class="stat-bottom">

                    <span class="growth">
                        ↑ Sales
                    </span>

                    Total revenue

                </div>

            </a>

        </div>

        <!-- MIDDLE SECTION -->

        <div class="middle-grid">

            <!-- SALES PIPELINE -->

            <div class="dashboard-card">

                <div class="card-header">

                    <h2>
                        Sales Pipeline
                    </h2>

                </div>

                <div class="pipeline-container">

                    <div class="funnel">

                        <div class="funnel-row funnel-1">
                            Lead
                        </div>

                        <div class="funnel-row funnel-2">
                            Contacted
                        </div>

                        <div class="funnel-row funnel-3">
                            Qualified
                        </div>

                        <div class="funnel-row funnel-4">
                            Proposal
                        </div>

                        <div class="funnel-row funnel-5">
                            Negotiation
                        </div>

                        <div class="funnel-row funnel-6">
                            Won
                        </div>

                    </div>

                    <div class="pipeline-list">

                        <div class="pipeline-item">

                            <span class="pipeline-name">

                                <span
                                    class="pipeline-dot"
                                    style="background:#3b82f6"
                                ></span>

                                Lead

                            </span>

                            <span class="pipeline-value">

                                <?php

                                echo $lead_count;

                                ?>

                            </span>

                        </div>

                        <div class="pipeline-item">

                            <span class="pipeline-name">

                                <span
                                    class="pipeline-dot"
                                    style="background:#14b8a6"
                                ></span>

                                Contacted

                            </span>

                            <span class="pipeline-value">

                                <?php

                                echo $stage_counts[
                                    "Qualification"
                                ];

                                ?>

                            </span>

                        </div>

                        <div class="pipeline-item">

                            <span class="pipeline-name">

                                <span
                                    class="pipeline-dot"
                                    style="background:#84cc16"
                                ></span>

                                Qualified

                            </span>

                            <span class="pipeline-value">

                                <?php

                                echo $stage_counts[
                                    "Qualification"
                                ];

                                ?>

                            </span>

                        </div>

                        <div class="pipeline-item">

                            <span class="pipeline-name">

                                <span
                                    class="pipeline-dot"
                                    style="background:#f59e0b"
                                ></span>

                                Proposal

                            </span>

                            <span class="pipeline-value">

                                <?php

                                echo $stage_counts[
                                    "Proposal"
                                ];

                                ?>

                            </span>

                        </div>

                        <div class="pipeline-item">

                            <span class="pipeline-name">

                                <span
                                    class="pipeline-dot"
                                    style="background:#f97316"
                                ></span>

                                Negotiation

                            </span>

                            <span class="pipeline-value">

                                <?php

                                echo $stage_counts[
                                    "Negotiation"
                                ];

                                ?>

                            </span>

                        </div>

                        <div class="pipeline-item">

                            <span class="pipeline-name">

                                <span
                                    class="pipeline-dot"
                                    style="background:#ef4444"
                                ></span>

                                Won

                            </span>

                            <span class="pipeline-value">

                                <?php

                                echo $closed_deals;

                                ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- REVENUE OVERVIEW -->

            <div class="dashboard-card">

                <div class="card-header">

                    <h2>
                        Revenue Overview
                    </h2>

                    <div class="revenue-total">

                        <strong>

                            <?php

                            echo formatRevenue(
                                $total_revenue
                            );

                            ?>

                        </strong>

                        <span>
                            ↑ Sales
                        </span>

                    </div>

                </div>

                <div class="chart">

                    <svg
                        viewBox="0 0 500 220"
                        preserveAspectRatio="none"
                    >

                        <polyline
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="3"
                            points="<?php

                            echo htmlspecialchars(
                                $points_string
                            );

                            ?>"
                        />

                        <polygon
                            fill="rgba(59,130,246,0.08)"
                            points="<?php

                            echo htmlspecialchars(
                                $points_string
                            );

                            ?> 500,220 0,220"
                        />

                    </svg>

                </div>

                <div class="chart-labels">

                    <span>
                        30 Days Ago
                    </span>

                    <span>
                        Today
                    </span>

                </div>

            </div>

            <!-- DEALS BY STAGE -->

            <div class="dashboard-card">

                <div class="card-header">

                    <h2>
                        Deals by Stage
                    </h2>

                </div>

                <div class="donut-area">

                    <?php
                    $donut_colors = [
                        "Prospecting"   => "#3b82f6",
                        "Qualification" => "#14b8a6",
                        "Proposal"      => "#84cc16",
                        "Negotiation"   => "#f59e0b",
                        "Closed Won"    => "#8b5cf6"
                    ];

                    $donut_total = array_sum($stage_counts);

                    $gradient_parts = [];
                    $current_angle = 0;

                    if ($donut_total > 0) {

                        foreach ($donut_colors as $stage => $color) {

                            $count = $stage_counts[$stage] ?? 0;

                            if ($count <= 0) {
                                continue;
                            }

                            $percentage =
                                $count / $donut_total;

                            $next_angle =
                                $current_angle +
                                ($percentage * 360);

                            $gradient_parts[] =
                                $color .
                                " " .
                                $current_angle .
                                "deg " .
                                $next_angle .
                                "deg";

                            $current_angle = $next_angle;
                        }

                        $donut_gradient =
                            "conic-gradient(" .
                            implode(", ", $gradient_parts) .
                            ")";

                    } else {

                        $donut_gradient =
                            "conic-gradient(#e5e7eb 0deg 360deg)";
                    }

                    ?>

                    <div
                        class="donut"
                        style="background: <?php echo htmlspecialchars($donut_gradient, ENT_QUOTES, 'UTF-8'); ?>;"
                    >

                        <div class="donut-inner">

                            <strong>

                                <?php

                                echo $deal_count;

                                ?>

                            </strong>

                            <span>
                                Total Deals
                            </span>

                        </div>

                    </div>

                    <div class="stage-list">

                        <div class="stage-item">

                            <span
                                class="stage-color"
                                style="background:#3b82f6"
                            ></span>

                            Prospecting

                            <strong>

                                <?php

                                echo $stage_counts[
                                    "Prospecting"
                                ];

                                ?>

                            </strong>

                        </div>

                        <div class="stage-item">

                            <span
                                class="stage-color"
                                style="background:#14b8a6"
                            ></span>

                            Qualification

                            <strong>

                                <?php

                                echo $stage_counts[
                                    "Qualification"
                                ];

                                ?>

                            </strong>

                        </div>

                        <div class="stage-item">

                            <span
                                class="stage-color"
                                style="background:#84cc16"
                            ></span>

                            Proposal

                            <strong>

                                <?php

                                echo $stage_counts[
                                    "Proposal"
                                ];

                                ?>

                            </strong>

                        </div>

                        <div class="stage-item">

                            <span
                                class="stage-color"
                                style="background:#f59e0b"
                            ></span>

                            Negotiation

                            <strong>

                                <?php

                                echo $stage_counts[
                                    "Negotiation"
                                ];

                                ?>

                            </strong>

                        </div>

                        <div class="stage-item">

                            <span
                                class="stage-color"
                                style="background:#8b5cf6"
                            ></span>

                            Closed Won

                            <strong>

                                <?php

                                echo $stage_counts[
                                    "Closed Won"
                                ];

                                ?>

                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- BOTTOM SECTION -->

        <div class="bottom-grid">

            <!-- RECENT CONTACTS -->

            <div class="bottom-card">

                <div class="card-header">

                    <h2>
                        Recent Contacts
                    </h2>

                    <a
                        href="../contacts/index.php"
                        class="card-link"
                    >
                        View All
                    </a>

                </div>

                <?php if (
                    count($recent_contacts) > 0
                ): ?>

                    <?php foreach (
                        $recent_contacts
                        as $contact
                    ): ?>

                        <div class="contact-item">

                            <div class="contact-avatar">

                                <?php

                                echo strtoupper(
                                    substr(
                                        $contact[
                                            "first_name"
                                        ] ?? "C",
                                        0,
                                        1
                                    )
                                );

                                ?>

                            </div>

                            <div class="contact-info">

                                <div class="contact-name">

                                    <?php

                                    echo htmlspecialchars(
                                        trim(
                                            (
                                                $contact[
                                                    "first_name"
                                                ] ?? ""
                                            ) .
                                            " " .
                                            (
                                                $contact[
                                                    "last_name"
                                                ] ?? ""
                                            )
                                        )
                                    );

                                    ?>

                                </div>

                                <div class="contact-email">

                                    <?php

                                    echo htmlspecialchars(
                                        $contact[
                                            "email"
                                        ] ?? ""
                                    );

                                    ?>

                                </div>

                            </div>

                            <div class="contact-time">
                                Recent
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p
                        style="
                            color:#94a3b8;
                            font-size:12px;
                        "
                    >
                        No contacts found.
                    </p>

                <?php endif; ?>

            </div>

            <!-- UPCOMING TASKS -->

            <div class="bottom-card">

                <div class="card-header">

                    <h2>
                        Upcoming Tasks
                    </h2>

                    <a
                        href="../tasks/index.php"
                        class="card-link"
                    >
                        View All
                    </a>

                </div>

                <?php if (
                    count($upcoming_tasks) > 0
                ): ?>

                    <?php foreach (
                        $upcoming_tasks
                        as $task
                    ): ?>

                        <div class="task-item">

                            <div class="task-check"></div>

                            <div class="task-info">

                                <div class="task-title">

                                    <?php

                                    echo htmlspecialchars(
                                        $task["title"]
                                    );

                                    ?>

                                </div>

                                <div class="task-meta">

                                    <?php

                                    echo htmlspecialchars(
                                        $task["due_date"]
                                    );

                                    ?>

                                    &nbsp; • &nbsp;

                                    <span
                                        class="task-priority"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $task[
                                                "priority"
                                            ] ?? "Normal"
                                        );

                                        ?>

                                    </span>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p
                        style="
                            color:#94a3b8;
                            font-size:12px;
                        "
                    >
                        No upcoming tasks.
                    </p>

                <?php endif; ?>

            </div>

            <!-- TOP PERFORMING SALES REPS -->

            <div class="bottom-card">

                <div class="card-header">

                    <h2>
                        Top Performing Sales Reps
                    </h2>

                    <a
                        href="../reports/sales.php"
                        class="card-link"
                    >
                        View All
                    </a>

                </div>

                <?php

                $stmt = $conn->query("
                    SELECT
                        u.id,
                        u.name,
                        COALESCE(SUM(s.total_amount), 0) AS total_sales
                    FROM users u
                    LEFT JOIN sales s
                        ON s.created_by = u.id
                    GROUP BY u.id, u.name
                    ORDER BY total_sales DESC
                    LIMIT 5
                ");

                $top_sales_reps = $stmt->fetchAll(PDO::FETCH_ASSOC);

                ?>

                <?php if (count($top_sales_reps) > 0): ?>

                    <?php

                    $max_sales = max(
                        array_column(
                            $top_sales_reps,
                            "total_sales"
                        )
                    );

                    if ($max_sales <= 0) {
                        $max_sales = 1;
                    }

                    ?>

                    <?php foreach (
                        $top_sales_reps
                        as $index => $rep
                    ): ?>

                        <?php

                        $percentage =
                            ($rep["total_sales"] / $max_sales) * 100;

                        $percentage =
                            max(
                                0,
                                min(
                                    100,
                                    $percentage
                                )
                            );

                        ?>

                        <div class="rep-item">

                            <div class="rep-rank">
                                <?php echo $index + 1; ?>
                            </div>

                            <div class="rep-avatar">

                                <?php

                                echo strtoupper(
                                    substr(
                                        $rep["name"],
                                        0,
                                        1
                                    )
                                );

                                ?>

                            </div>

                            <div class="rep-info">

                                <div class="rep-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $rep["name"]
                                    );

                                    ?>

                                </div>

                                <div class="rep-bar">

                                    <span
                                        style="
                                            width:
                                            <?php
                                            echo $percentage;
                                            ?>%;
                                        "
                                    ></span>

                                </div>

                            </div>

                            <div class="rep-sales">

                                <?php

                                echo formatRevenue(
                                    (float) $rep["total_sales"]
                                );

                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p
                        style="
                            color:#94a3b8;
                            font-size:12px;
                        "
                    >
                        No sales representatives found.
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<script>

const dashboardSearch =
    document.getElementById(
        "dashboardSearch"
    );

const searchResults =
    document.getElementById(
        "searchResults"
    );

let searchTimer;

dashboardSearch.addEventListener(
    "input",
    function () {

        clearTimeout(searchTimer);

        const query =
            this.value.trim();

        if (query.length === 0) {

            searchResults.style.display =
                "none";

            searchResults.innerHTML =
                "";

            return;

        }

        searchTimer = setTimeout(
            function () {

                fetch(
                    "search.php?q=" +
                    encodeURIComponent(query)
                )

                .then(
                    response =>
                        response.json()
                )

                .then(
                    data => {

                        searchResults.innerHTML =
                            "";

                        if (
                            data.length === 0
                        ) {

                            searchResults.innerHTML =
                                '<div class="search-no-result">' +
                                'No results found' +
                                '</div>';

                            searchResults.style.display =
                                "block";

                            return;

                        }

                        data.forEach(
                            function (item) {

                                const result =
                                    document.createElement(
                                        "a"
                                    );

                                result.href =
                                    item.url;

                                result.className =
                                    "search-result";

                                result.innerHTML = `
                                    <div class="search-result-type">
                                        ${item.type}
                                    </div>

                                    <div class="search-result-title">
                                        ${escapeHtml(
                                            item.title
                                        )}
                                    </div>

                                    <div class="search-result-description">
                                        ${escapeHtml(
                                            item.description ||
                                            ""
                                        )}
                                    </div>
                                `;

                                searchResults.appendChild(
                                    result
                                );

                            }
                        );

                        searchResults.style.display =
                            "block";

                    }
                )

                .catch(
                    function () {

                        searchResults.innerHTML =
                            '<div class="search-no-result">' +
                            'Unable to search' +
                            '</div>';

                        searchResults.style.display =
                            "block";

                    }
                );

            },
            300
        );

    }
);

/* ==============================
   DARK / LIGHT MODE
============================== */

(function () {

    const root = document.documentElement;
    const themeToggle = document.getElementById("themeToggle");

    function applyTheme(isDark) {

        root.classList.toggle("dark-mode", isDark);

        if (!themeToggle) {
            return;
        }

        themeToggle.textContent = isDark ? "☀️" : "🌙";

        themeToggle.setAttribute(
            "aria-label",
            isDark
                ? "Switch to light mode"
                : "Switch to dark mode"
        );

        themeToggle.setAttribute(
            "title",
            isDark
                ? "Switch to light mode"
                : "Switch to dark mode"
        );
    }

    let isDark = false;

    try {
        isDark = localStorage.getItem("crmTheme") === "dark";
    } catch (error) {
        isDark = root.classList.contains("dark-mode");
    }

    applyTheme(isDark);

    if (themeToggle) {

        themeToggle.addEventListener(
            "click",
            function () {

                isDark = !root.classList.contains("dark-mode");

                applyTheme(isDark);

                try {
                    localStorage.setItem(
                        "crmTheme",
                        isDark ? "dark" : "light"
                    );
                } catch (error) {
                    /* Ignore storage errors. */
                }
            }
        );
    }

})();

function escapeHtml(value) {

    const div =
        document.createElement("div");

    div.textContent =
        value;

    return div.innerHTML;

}

document.addEventListener(
    "click",
    function (event) {

        if (
            !event.target.closest(
                ".search-wrapper"
            )
        ) {

            searchResults.style.display =
                "none";

        }

    }
);

</script>

</body>

</html>