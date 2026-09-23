<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   LEADS
============================== */

$stmt = $conn->query("
    SELECT
        l.id,
        l.lead_name,
        l.email,
        l.phone,
        l.source,
        l.status,
        l.lead_value,
        l.notes,
        l.created_at,
        co.company_name,
        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,
        u.name AS assigned_name
    FROM leads l
    LEFT JOIN companies co
        ON co.id = l.company_id
    LEFT JOIN contacts ct
        ON ct.id = l.contact_id
    LEFT JOIN users u
        ON u.id = l.assigned_to
    ORDER BY l.id DESC
");

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_leads = count($leads);

$new_leads = 0;
$qualified_leads = 0;
$converted_leads = 0;
$total_lead_value = 0;

foreach ($leads as $lead) {

    $status = strtolower(
        trim($lead["status"] ?? "")
    );

    if (
        $status === "new"
        || $status === "lead"
    ) {
        $new_leads++;
    }

    if (
        $status === "qualified"
        || $status === "qualification"
    ) {
        $qualified_leads++;
    }

    if (
        $status === "converted"
        || $status === "closed"
        || $status === "closed won"
        || $status === "won"
    ) {
        $converted_leads++;
    }

    $total_lead_value +=
        (float) ($lead["lead_value"] ?? 0);
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

    <title>Leads - CRM</title>

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
            background: #f8fafc;
            font-family: Arial, sans-serif;
            color: #0f172a;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
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
        }

        .title-icon {
            width: 48px;
            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #dbeafe;

            font-size: 23px;
        }

        .page-header h1 {
            margin: 0;

            font-size: 24px;

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

            gap: 7px;

            padding: 11px 17px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            transition: 0.2s;

            white-space: nowrap;
        }

        .add-btn:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }


        /* =========================
           SUMMARY CARDS
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

            justify-content: space-between;

            align-items: center;

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
        }

        .search-wrapper input {
            width: 280px;

            height: 40px;

            padding:
                0 14px 0 37px;

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
                rgba(
                    37,
                    99,
                    235,
                    0.10
                );
        }

        .filter-select {
            height: 40px;

            padding:
                0 12px;

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
           TABLE
        ========================= */

        .table-card {
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

        .leads-table {
            width: 100%;

            border-collapse: collapse;
        }

        .leads-table th {
            padding: 15px 16px;

            text-align: left;

            background: #f8fafc;

            border-bottom:
                1px solid #e2e8f0;

            color: #475569;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.05em;

            white-space: nowrap;
        }

        .leads-table td {
            padding: 16px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .leads-table tbody tr:hover {
            background: #f8fbff;
        }

        .leads-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =========================
           COLUMN SIZING
        ========================= */

        .sno-column {
            width: 6%;
        }

        .lead-column {
            width: 16%;
        }

        .company-column {
            width: 13%;
        }

        .contact-column {
            width: 13%;
        }

        .source-column {
            width: 10%;
        }

        .status-column {
            width: 10%;
        }

        .value-column {
            width: 10%;
        }

        .assigned-column {
            width: 10%;
        }

        .action-column {
            width: 12%;

            min-width: 145px;

            white-space: nowrap;
        }


        /* =========================
           LEAD DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;
        }

        .lead-name {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .lead-id {
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
           BADGES
        ========================= */

        .badge {
            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            max-width: 100%;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .badge-new {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .badge-contacted {
            background: #e0f2fe;

            color: #0369a1;
        }

        .badge-qualified {
            background: #dcfce7;

            color: #15803d;
        }

        .badge-proposal {
            background: #fef3c7;

            color: #b45309;
        }

        .badge-negotiation {
            background: #ffedd5;

            color: #c2410c;
        }

        .badge-won {
            background: #dcfce7;

            color: #15803d;
        }

        .badge-lost {
            background: #fee2e2;

            color: #b91c1c;
        }

        .badge-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================
           ACTIONS
        ========================= */

        .action-links {
            display: flex;

            align-items: center;

            gap: 8px;

            white-space: nowrap;
        }

        .action-links a {
            text-decoration: none;

            font-size: 12px;

            font-weight: 700;
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

        .action-links span {
            color: #cbd5e1;
        }


        /* =========================
           FOOTER
        ========================= */

        .table-footer {
            display: flex;

            justify-content: space-between;

            padding: 14px 16px;

            border-top:
                1px solid #eef2f7;

            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================
           EMPTY
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
           RESPONSIVE
        ========================= */

        @media (max-width: 1200px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .leads-table th,
            .leads-table td {
                padding:
                    12px 10px;
            }
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;

                padding: 18px;
            }

            .page-header {
                flex-direction: column;

                align-items: flex-start;
            }

            .toolbar {
                flex-direction: column;

                align-items: stretch;
            }

            .search-wrapper input {
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
                🎯
            </div>

            <div>

                <h1>
                    Leads
                </h1>

                <p>
                    Manage and track your sales leads and opportunities
                </p>

            </div>

        </div>


        <a
            href="add.php"
            class="add-btn"
        >
            + Add Lead
        </a>

    </div>


    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">


        <div class="summary-card">

            <div class="summary-label">
                Total Leads
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $total_leads
                );
                ?>

            </div>

            <div class="summary-small">
                All lead records
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                New Leads
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $new_leads
                );
                ?>

            </div>

            <div class="summary-small">
                New leads
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Qualified
            </div>

            <div class="summary-value">

                <?php
                echo number_format(
                    $qualified_leads
                );
                ?>

            </div>

            <div class="summary-small">
                Qualified leads
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Lead Value
            </div>

            <div class="summary-value">

                ₹<?php
                echo number_format(
                    $total_lead_value,
                    0
                );
                ?>

            </div>

            <div class="summary-small">
                Total lead value
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
                    id="leadSearch"
                    placeholder="Search leads..."
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

                <option value="new">
                    New
                </option>

                <option value="contacted">
                    Contacted
                </option>

                <option value="qualified">
                    Qualified
                </option>

                <option value="proposal">
                    Proposal
                </option>

                <option value="negotiation">
                    Negotiation
                </option>

                <option value="won">
                    Won
                </option>

                <option value="lost">
                    Lost
                </option>

            </select>


        </div>


        <a
            href="../exports/leads_csv.php"
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
                class="leads-table"
                id="leadsTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="lead-column">
                            Lead
                        </th>

                        <th class="company-column">
                            Company
                        </th>

                        <th class="contact-column">
                            Contact
                        </th>

                        <th class="source-column">
                            Source
                        </th>

                        <th class="status-column">
                            Status
                        </th>

                        <th class="value-column">
                            Lead Value
                        </th>

                        <th class="assigned-column">
                            Assigned To
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count($leads) > 0
                ): ?>


                    <?php foreach (
                        $leads
                        as $index => $lead
                    ): ?>


                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $lead["status"] ?? ""
                                )
                            );

                        switch ($status) {

                            case "new":
                            case "lead":
                                $badge_class =
                                    "badge-new";
                                break;

                            case "contacted":
                                $badge_class =
                                    "badge-contacted";
                                break;

                            case "qualified":
                            case "qualification":
                                $badge_class =
                                    "badge-qualified";
                                break;

                            case "proposal":
                                $badge_class =
                                    "badge-proposal";
                                break;

                            case "negotiation":
                                $badge_class =
                                    "badge-negotiation";
                                break;

                            case "won":
                            case "closed":
                            case "closed won":
                            case "converted":
                                $badge_class =
                                    "badge-won";
                                break;

                            case "lost":
                            case "closed lost":
                                $badge_class =
                                    "badge-lost";
                                break;

                            default:
                                $badge_class =
                                    "badge-default";
                                break;
                        }

                        $status_label =
                            $lead["status"] ?? "Unknown";

                        ?>


                        <tr
                            data-status="<?php
                                echo htmlspecialchars(
                                    $status
                                );
                            ?>"
                        >


                            <!-- S.NO -->

                            <td class="serial">

                                <?php
                                echo $index + 1;
                                ?>

                            </td>


                            <!-- LEAD -->

                            <td>

                                <div class="lead-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $lead[
                                            "lead_name"
                                        ] ?? "—"
                                    );

                                    ?>

                                </div>

                                <div class="lead-id">

                                    Lead ID:
                                    <?php
                                    echo (int)
                                        $lead["id"];
                                    ?>

                                </div>

                            </td>


                            <!-- COMPANY -->

                            <td
                                class="secondary"
                            >

                                <?php if (
                                    !empty(
                                        $lead[
                                            "company_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead[
                                                "company_name"
                                            ]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- CONTACT -->

                            <td
                                class="secondary"
                            >

                                <?php if (
                                    !empty(
                                        trim(
                                            $lead[
                                                "contact_name"
                                            ] ?? ""
                                        )
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            trim(
                                                $lead[
                                                    "contact_name"
                                                ]
                                            )
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- SOURCE -->

                            <td
                                class="secondary"
                            >

                                <?php if (
                                    !empty(
                                        $lead["source"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead["source"]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        badge
                                        <?php
                                        echo $badge_class;
                                        ?>
                                    "
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $status_label
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- LEAD VALUE -->

                            <td>

                                ₹<?php

                                echo number_format(
                                    (float) (
                                        $lead[
                                            "lead_value"
                                        ] ?? 0
                                    ),
                                    0
                                );

                                ?>

                            </td>


                            <!-- ASSIGNED -->

                            <td
                                class="secondary"
                            >

                                <?php if (
                                    !empty(
                                        $lead[
                                            "assigned_name"
                                        ]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?php

                                        echo htmlspecialchars(
                                            $lead[
                                                "assigned_name"
                                            ]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?php
                                        echo (int)
                                            $lead["id"];
                                        ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>
                                        |
                                    </span>

                                    <a
                                        href="edit.php?id=<?php
                                        echo (int)
                                            $lead["id"];
                                        ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>
                                        |
                                    </span>

                                    <a
                                        href="delete.php?id=<?php
                                        echo (int)
                                            $lead["id"];
                                        ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this lead?'
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
                                    🎯
                                </div>

                                <h3>
                                    No leads found
                                </h3>

                                <p>
                                    Add your first lead
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
                    <?php
                    echo count($leads);
                    ?>
                </strong>

                leads

            </span>


            <span>
                CRM Lead Management
            </span>

        </div>


    </div>


</div>


<script>

const leadSearch =
    document.getElementById(
        "leadSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const leadRows =
    document.querySelectorAll(
        "#leadsTable tbody tr[data-status]"
    );


function filterLeads() {

    const searchValue =
        leadSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value;


    leadRows.forEach(
        function (row) {

            const rowText =
                row.textContent
                    .toLowerCase();

            const rowStatus =
                row.dataset.status;


            const matchesSearch =
                rowText.includes(
                    searchValue
                );


            const matchesStatus =
                statusValue === ""
                ||
                rowStatus === statusValue;


            row.style.display =
                matchesSearch &&
                matchesStatus
                    ? ""
                    : "none";

        }
    );
}


leadSearch.addEventListener(
    "input",
    filterLeads
);


statusFilter.addEventListener(
    "change",
    filterLeads
);

</script>


</body>

</html>