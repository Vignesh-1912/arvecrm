<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Check Lead ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Lead
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        leads.*,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        users.name AS assigned_user
    FROM leads

    LEFT JOIN companies
        ON leads.company_id = companies.id

    LEFT JOIN contacts
        ON leads.contact_id = contacts.id

    LEFT JOIN users
        ON leads.assigned_to = users.id

    WHERE leads.id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$lead = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Lead Not Found
|--------------------------------------------------------------------------
*/

if (!$lead) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Lead Values
|--------------------------------------------------------------------------
*/

$lead_name = trim($lead["lead_name"] ?? "");
$company_name = trim($lead["company_name"] ?? "");

$contact_name = trim(
    ($lead["first_name"] ?? "") . " " .
    ($lead["last_name"] ?? "")
);

$email = trim($lead["email"] ?? "");
$phone = trim($lead["phone"] ?? "");
$source = trim($lead["source"] ?? "");

$status = strtolower(
    trim($lead["status"] ?? "new")
);

$lead_value = (float) ($lead["lead_value"] ?? 0);

$assigned_user = trim(
    $lead["assigned_user"] ?? ""
);

$notes = trim(
    $lead["notes"] ?? ""
);

$created_at = $lead["created_at"] ?? "";
$updated_at = $lead["updated_at"] ?? "";


/*
|--------------------------------------------------------------------------
| Initial
|--------------------------------------------------------------------------
*/

$initial = !empty($lead_name)
    ? strtoupper(substr($lead_name, 0, 1))
    : "L";


/*
|--------------------------------------------------------------------------
| Status Display
|--------------------------------------------------------------------------
*/

$statusLabels = [
    "new" => "New",
    "contacted" => "Contacted",
    "qualified" => "Qualified",
    "converted" => "Converted",
    "lost" => "Lost"
];

$statusLabel = $statusLabels[$status] ?? ucfirst($status);


/*
|--------------------------------------------------------------------------
| Status CSS Class
|--------------------------------------------------------------------------
*/

$allowedStatusClasses = [
    "new",
    "contacted",
    "qualified",
    "converted",
    "lost"
];

$statusClass = in_array(
    $status,
    $allowedStatusClasses,
    true
)
    ? $status
    : "new";

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
        <?php echo htmlspecialchars($lead_name); ?> - Lead - CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
        }

        .page-wrapper {
            max-width: 1450px;
            margin: 0 auto;
        }

        /* ------------------------------------------------------------
           Breadcrumb
        ------------------------------------------------------------ */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        /* ------------------------------------------------------------
           Page Header
        ------------------------------------------------------------ */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0;
            color: #172554;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .edit-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .edit-btn:hover {
            background: #1d4ed8;
        }

        .list-btn {
            background: #ffffff;
            border: 1px solid #dbe2ea;
            color: #334155;
        }

        .list-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Lead Hero
        ------------------------------------------------------------ */

        .lead-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 24px;
            margin-bottom: 22px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .lead-main {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .lead-avatar {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 29px;
            font-weight: 700;
        }

        .lead-info {
            min-width: 0;
        }

        .lead-info h2 {
            margin: 0;
            color: #172554;
            font-size: 23px;
            font-weight: 700;
        }

        .lead-subtitle {
            margin-top: 5px;
            color: #64748b;
            font-size: 14px;
        }

        .lead-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .lead-id {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .company-tag,
        .source-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .company-tag {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .source-tag {
            background: #f1f5f9;
            color: #475569;
        }

        /* ------------------------------------------------------------
           Status
        ------------------------------------------------------------ */

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.new {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-badge.contacted {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.qualified {
            background: #d1fae5;
            color: #047857;
        }

        .status-badge.converted {
            background: #dcfce7;
            color: #15803d;
        }

        .status-badge.lost {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* ------------------------------------------------------------
           Summary Cards
        ------------------------------------------------------------ */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            padding: 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
        }

        .summary-label {
            display: block;
            margin-bottom: 7px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .summary-value {
            display: block;
            color: #1e293b;
            font-size: 15px;
            font-weight: 700;
            word-break: break-word;
        }

        .summary-value.amount {
            color: #15803d;
            font-size: 19px;
        }

        .summary-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .summary-value a:hover {
            text-decoration: underline;
        }

        .empty-value {
            color: #94a3b8;
            font-weight: 500;
        }

        /* ------------------------------------------------------------
           Main Content
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 22px;
            align-items: start;
        }

        .info-card,
        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .info-card {
            overflow: hidden;
        }

        .card-header {
            padding: 19px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        /* ------------------------------------------------------------
           Information Grid
        ------------------------------------------------------------ */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .info-item {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-item:nth-child(odd) {
            border-right: 1px solid #e5e7eb;
        }

        .info-item.full {
            grid-column: 1 / -1;
            border-right: none;
        }

        .info-label {
            display: block;
            margin-bottom: 7px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .info-value {
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        .notes-value {
            white-space: pre-line;
        }

        /* ------------------------------------------------------------
           Side Cards
        ------------------------------------------------------------ */

        .side-card {
            padding: 20px;
            margin-bottom: 18px;
        }

        .side-title {
            margin-bottom: 17px;
        }

        .side-title h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .side-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ------------------------------------------------------------
           Contact Methods
        ------------------------------------------------------------ */

        .method-box {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .method-box:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .method-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .method-text {
            min-width: 0;
        }

        .method-label {
            display: block;
            margin-bottom: 3px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .method-value {
            display: block;
            color: #334155;
            font-size: 13px;
            word-break: break-word;
        }

        .method-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .method-value a:hover {
            text-decoration: underline;
        }

        /* ------------------------------------------------------------
           Quick Actions
        ------------------------------------------------------------ */

        .quick-action {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            min-height: 42px;
            padding: 0 12px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .quick-action:last-child {
            margin-bottom: 0;
        }

        .quick-action:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .quick-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ------------------------------------------------------------
           Timeline
        ------------------------------------------------------------ */

        .timeline-item {
            position: relative;
            padding-left: 28px;
            margin-bottom: 20px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 19px;
            width: 1px;
            height: calc(100% + 7px);
            background: #dbe2ea;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 3px;
            width: 15px;
            height: 15px;
            border: 3px solid #2563eb;
            border-radius: 50%;
            background: #dbeafe;
        }

        .timeline-label {
            display: block;
            margin-bottom: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .timeline-value {
            display: block;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        /* ------------------------------------------------------------
           Assignment
        ------------------------------------------------------------ */

        .assigned-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .assigned-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .assigned-text strong {
            display: block;
            color: #334155;
            font-size: 13px;
        }

        .assigned-text span {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* ------------------------------------------------------------
           Lead Value Side Box
        ------------------------------------------------------------ */

        .value-highlight {
            padding: 15px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 9px;
        }

        .value-highlight-label {
            display: block;
            margin-bottom: 4px;
            color: #15803d;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value-highlight-amount {
            display: block;
            color: #166534;
            font-size: 23px;
            font-weight: 700;
        }

        /* ------------------------------------------------------------
           Bottom Actions
        ------------------------------------------------------------ */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .bottom-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .bottom-edit {
            background: #2563eb;
            color: #ffffff;
        }

        .bottom-edit:hover {
            background: #1d4ed8;
        }

        .bottom-back {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #475569;
        }

        .bottom-back:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Dark Mode
        ------------------------------------------------------------ */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .lead-info h2,
        html.dark-mode .card-header h3,
        html.dark-mode .side-title h3,
        html.dark-mode .summary-value,
        html.dark-mode .assigned-text strong,
        html.dark-mode .timeline-value {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .lead-subtitle,
        html.dark-mode .lead-id,
        html.dark-mode .card-header p,
        html.dark-mode .side-title p {
            color: #94a3b8;
        }

        html.dark-mode .lead-hero,
        html.dark-mode .summary-card,
        html.dark-mode .info-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .company-tag {
            background: #172554;
            color: #bfdbfe;
        }

        html.dark-mode .source-tag {
            background: #1e293b;
            color: #cbd5e1;
        }

        html.dark-mode .info-item,
        html.dark-mode .method-box {
            border-color: #1f2937;
        }

        html.dark-mode .info-label,
        html.dark-mode .method-label,
        html.dark-mode .timeline-label,
        html.dark-mode .summary-label {
            color: #64748b;
        }

        html.dark-mode .info-value,
        html.dark-mode .method-value {
            color: #cbd5e1;
        }

        html.dark-mode .list-btn,
        html.dark-mode .bottom-back {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .list-btn:hover,
        html.dark-mode .bottom-back:hover {
            background: #1e293b;
        }

        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #1e293b;
        }

        html.dark-mode .quick-icon {
            background: #1e293b;
        }

        html.dark-mode .method-icon {
            background: #172554;
        }

        html.dark-mode .assigned-text span {
            color: #64748b;
        }

        html.dark-mode .timeline-item::before {
            background: #334155;
        }

        html.dark-mode .value-highlight {
            background: #052e16;
            border-color: #166534;
        }

        html.dark-mode .value-highlight-label {
            color: #86efac;
        }

        html.dark-mode .value-highlight-amount {
            color: #bbf7d0;
        }

        /* ------------------------------------------------------------
           Responsive
        ------------------------------------------------------------ */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .page-header {
                flex-direction: column;
            }

            .lead-hero {
                flex-direction: column;
                align-items: flex-start;
            }

        }

        @media (max-width: 650px) {

            .main-content {
                padding: 20px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item:nth-child(odd) {
                border-right: none;
            }

            .info-item.full {
                grid-column: auto;
            }

            .header-actions,
            .bottom-actions {
                width: 100%;
            }

            .header-btn,
            .bottom-btn {
                flex: 1;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-wrapper">


        <!-- =========================================================
             Breadcrumb
        ========================================================== -->

        <div class="breadcrumb">

            <a href="index.php">
                Leads
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                View Lead
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Lead Details
                </h1>

                <p>
                    View complete lead information and sales tracking details.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="edit.php?id=<?php echo (int) $lead["id"]; ?>"
                    class="header-btn edit-btn"
                >
                    ✎ Edit Lead
                </a>

                <a
                    href="index.php"
                    class="header-btn list-btn"
                >
                    ← Lead List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Lead Hero
        ========================================================== -->

        <div class="lead-hero">

            <div class="lead-main">

                <div class="lead-avatar">

                    <?php echo htmlspecialchars($initial); ?>

                </div>


                <div class="lead-info">

                    <h2>
                        <?php echo htmlspecialchars($lead_name); ?>
                    </h2>


                    <div class="lead-subtitle">

                        <?php if ($company_name !== ""): ?>

                            <?php echo htmlspecialchars($company_name); ?>

                        <?php elseif ($contact_name !== ""): ?>

                            <?php echo htmlspecialchars($contact_name); ?>

                        <?php else: ?>

                            Lead record

                        <?php endif; ?>

                    </div>


                    <div class="lead-meta">

                        <span class="lead-id">
                            Lead #<?php echo (int) $lead["id"]; ?>
                        </span>


                        <span
                            class="status-badge <?php echo $statusClass; ?>"
                        >
                            <?php echo htmlspecialchars($statusLabel); ?>
                        </span>


                        <?php if ($company_name !== ""): ?>

                            <span class="company-tag">
                                🏢
                                <?php echo htmlspecialchars($company_name); ?>
                            </span>

                        <?php endif; ?>


                        <?php if ($source !== ""): ?>

                            <span class="source-tag">
                                🎯
                                <?php echo htmlspecialchars($source); ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================================
             Summary Cards
        ========================================================== -->

        <div class="summary-grid">


            <!-- Lead Value -->

            <div class="summary-card">

                <span class="summary-label">
                    Lead Value
                </span>

                <span class="summary-value amount">

                    ₹<?php echo number_format(
                        $lead_value,
                        2
                    ); ?>

                </span>

            </div>


            <!-- Company -->

            <div class="summary-card">

                <span class="summary-label">
                    Company
                </span>

                <span class="summary-value">

                    <?php

                    echo $company_name !== ""
                        ? htmlspecialchars($company_name)
                        : '<span class="empty-value">Not assigned</span>';

                    ?>

                </span>

            </div>


            <!-- Contact -->

            <div class="summary-card">

                <span class="summary-label">
                    Contact
                </span>

                <span class="summary-value">

                    <?php

                    echo $contact_name !== ""
                        ? htmlspecialchars($contact_name)
                        : '<span class="empty-value">Not assigned</span>';

                    ?>

                </span>

            </div>


            <!-- Assigned -->

            <div class="summary-card">

                <span class="summary-label">
                    Assigned To
                </span>

                <span class="summary-value">

                    <?php

                    echo $assigned_user !== ""
                        ? htmlspecialchars($assigned_user)
                        : '<span class="empty-value">Not assigned</span>';

                    ?>

                </span>

            </div>

        </div>


        <!-- =========================================================
             Main Content
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 LEFT SIDE
            ====================================================== -->

            <div>


                <!-- Lead Information -->

                <div class="info-card">

                    <div class="card-header">

                        <h3>
                            Lead Information
                        </h3>

                        <p>
                            Basic lead details and sales tracking information.
                        </p>

                    </div>


                    <div class="info-grid">


                        <!-- Lead ID -->

                        <div class="info-item">

                            <span class="info-label">
                                Lead ID
                            </span>

                            <div class="info-value">
                                #<?php echo (int) $lead["id"]; ?>
                            </div>

                        </div>


                        <!-- Status -->

                        <div class="info-item">

                            <span class="info-label">
                                Status
                            </span>

                            <div class="info-value">

                                <span
                                    class="status-badge <?php echo $statusClass; ?>"
                                >
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </span>

                            </div>

                        </div>


                        <!-- Lead Name -->

                        <div class="info-item">

                            <span class="info-label">
                                Lead Name
                            </span>

                            <div class="info-value">
                                <?php echo htmlspecialchars($lead_name); ?>
                            </div>

                        </div>


                        <!-- Lead Source -->

                        <div class="info-item">

                            <span class="info-label">
                                Lead Source
                            </span>

                            <div class="info-value">

                                <?php

                                echo $source !== ""
                                    ? htmlspecialchars($source)
                                    : '<span class="empty-value">Not provided</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Company -->

                        <div class="info-item">

                            <span class="info-label">
                                Company
                            </span>

                            <div class="info-value">

                                <?php

                                echo $company_name !== ""
                                    ? htmlspecialchars($company_name)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Contact -->

                        <div class="info-item">

                            <span class="info-label">
                                Contact
                            </span>

                            <div class="info-value">

                                <?php

                                echo $contact_name !== ""
                                    ? htmlspecialchars($contact_name)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Assigned -->

                        <div class="info-item">

                            <span class="info-label">
                                Assigned To
                            </span>

                            <div class="info-value">

                                <?php

                                echo $assigned_user !== ""
                                    ? htmlspecialchars($assigned_user)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>


                        <!-- Lead Value -->

                        <div class="info-item">

                            <span class="info-label">
                                Lead Value
                            </span>

                            <div class="info-value">

                                ₹<?php echo number_format(
                                    $lead_value,
                                    2
                                ); ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Contact Information -->

                <div
                    class="info-card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <h3>
                            Contact Information
                        </h3>

                        <p>
                            Communication details connected to the lead.
                        </p>

                    </div>


                    <div class="info-grid">


                        <!-- Email -->

                        <div class="info-item">

                            <span class="info-label">
                                Email
                            </span>

                            <div class="info-value">

                                <?php if ($email !== ""): ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($email); ?>"
                                    >
                                        <?php echo htmlspecialchars($email); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Phone -->

                        <div class="info-item">

                            <span class="info-label">
                                Phone
                            </span>

                            <div class="info-value">

                                <?php if ($phone !== ""): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($phone); ?>"
                                    >
                                        <?php echo htmlspecialchars($phone); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- Contact Name -->

                        <div class="info-item full">

                            <span class="info-label">
                                Contact Name
                            </span>

                            <div class="info-value">

                                <?php

                                echo $contact_name !== ""
                                    ? htmlspecialchars($contact_name)
                                    : '<span class="empty-value">Not assigned</span>';

                                ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Notes -->

                <div
                    class="info-card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <h3>
                            Lead Notes
                        </h3>

                        <p>
                            Additional information saved with this lead.
                        </p>

                    </div>


                    <div class="info-grid">

                        <div class="info-item full">

                            <div class="info-value notes-value">

                                <?php if ($notes !== ""): ?>

                                    <?php echo htmlspecialchars($notes); ?>

                                <?php else: ?>

                                    <span class="empty-value">
                                        No notes have been added.
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Bottom Actions -->

                <div class="bottom-actions">

                    <a
                        href="edit.php?id=<?php echo (int) $lead["id"]; ?>"
                        class="bottom-btn bottom-edit"
                    >
                        ✎ Edit Lead
                    </a>

                    <a
                        href="index.php"
                        class="bottom-btn bottom-back"
                    >
                        ← Back to Leads
                    </a>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDE
            ====================================================== -->

            <div>


                <!-- Lead Value -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Lead Value
                        </h3>

                        <p>
                            Current estimated opportunity value.
                        </p>

                    </div>


                    <div class="value-highlight">

                        <span class="value-highlight-label">
                            Estimated Value
                        </span>

                        <span class="value-highlight-amount">

                            ₹<?php echo number_format(
                                $lead_value,
                                2
                            ); ?>

                        </span>

                    </div>

                </div>


                <!-- Contact Methods -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Contact Methods
                        </h3>

                        <p>
                            Quick access to lead communication details.
                        </p>

                    </div>


                    <div class="method-box">

                        <div class="method-icon">
                            ✉
                        </div>

                        <div class="method-text">

                            <span class="method-label">
                                Email
                            </span>

                            <span class="method-value">

                                <?php if ($email !== ""): ?>

                                    <a
                                        href="mailto:<?php echo htmlspecialchars($email); ?>"
                                    >
                                        <?php echo htmlspecialchars($email); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </span>

                        </div>

                    </div>


                    <div class="method-box">

                        <div class="method-icon">
                            ☎
                        </div>

                        <div class="method-text">

                            <span class="method-label">
                                Phone
                            </span>

                            <span class="method-value">

                                <?php if ($phone !== ""): ?>

                                    <a
                                        href="tel:<?php echo htmlspecialchars($phone); ?>"
                                    >
                                        <?php echo htmlspecialchars($phone); ?>
                                    </a>

                                <?php else: ?>

                                    <span class="empty-value">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- Assigned User -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Assigned To
                        </h3>

                        <p>
                            Team member responsible for this lead.
                        </p>

                    </div>


                    <div class="assigned-box">

                        <div class="assigned-avatar">

                            <?php

                            echo $assigned_user !== ""
                                ? strtoupper(
                                    substr(
                                        $assigned_user,
                                        0,
                                        1
                                    )
                                )
                                : "?";

                            ?>

                        </div>


                        <div class="assigned-text">

                            <strong>

                                <?php

                                echo $assigned_user !== ""
                                    ? htmlspecialchars($assigned_user)
                                    : "Not assigned";

                                ?>

                            </strong>

                            <span>
                                Lead owner
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Quick Actions -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Quick Actions
                        </h3>

                        <p>
                            Common actions for this lead.
                        </p>

                    </div>


                    <a
                        href="edit.php?id=<?php echo (int) $lead["id"]; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✎
                        </span>

                        Edit Lead

                    </a>


                    <?php if ($email !== ""): ?>

                        <a
                            href="mailto:<?php echo htmlspecialchars($email); ?>"
                            class="quick-action"
                        >

                            <span class="quick-icon">
                                ✉
                            </span>

                            Send Email

                        </a>

                    <?php endif; ?>


                    <?php if ($phone !== ""): ?>

                        <a
                            href="tel:<?php echo htmlspecialchars($phone); ?>"
                            class="quick-action"
                        >

                            <span class="quick-icon">
                                ☎
                            </span>

                            Call Lead

                        </a>

                    <?php endif; ?>


                    <a
                        href="index.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ←
                        </span>

                        Back to Leads

                    </a>

                </div>


                <!-- Record Timeline -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Record Timeline
                        </h3>

                        <p>
                            Lead record history.
                        </p>

                    </div>


                    <div class="timeline-item">

                        <span class="timeline-dot"></span>

                        <span class="timeline-label">
                            Created
                        </span>

                        <span class="timeline-value">

                            <?php

                            echo $created_at !== ""
                                ? htmlspecialchars($created_at)
                                : "Not available";

                            ?>

                        </span>

                    </div>


                    <div class="timeline-item">

                        <span class="timeline-dot"></span>

                        <span class="timeline-label">
                            Last Updated
                        </span>

                        <span class="timeline-value">

                            <?php

                            echo $updated_at !== ""
                                ? htmlspecialchars($updated_at)
                                : "Not available";

                            ?>

                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>