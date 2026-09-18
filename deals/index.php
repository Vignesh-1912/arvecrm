<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   DEALS
============================== */

$stmt = $conn->query("
    SELECT
        d.id,
        d.title,
        d.amount,
        d.stage,
        d.probability,
        d.expected_close_date,
        d.description,
        d.created_at,

        co.company_name,

        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,

        cu.customer_code,

        u.name AS assigned_name

    FROM deals d

    LEFT JOIN companies co
        ON co.id = d.company_id

    LEFT JOIN contacts ct
        ON ct.id = d.contact_id

    LEFT JOIN customers cu
        ON cu.id = d.customer_id

    LEFT JOIN users u
        ON u.id = d.assigned_to

    ORDER BY d.id DESC
");

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_deals = count($deals);

$closed_won_deals = 0;
$open_deals = 0;
$total_deal_value = 0;

foreach ($deals as $deal) {

    $stage = strtolower(
        trim($deal["stage"] ?? "")
    );

    $amount = (float) (
        $deal["amount"] ?? 0
    );

    $total_deal_value += $amount;

    if (
        $stage === "closed_won" ||
        $stage === "closed won" ||
        $stage === "won" ||
        $stage === "closed"
    ) {
        $closed_won_deals++;
    } else {
        $open_deals++;
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

    <title>Deals - CRM</title>

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

        .deals-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .deals-table th {
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

        .deals-table td {
            padding: 16px 12px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .deals-table tbody tr:hover {
            background: #f8fbff;
        }

        .deals-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================
           COLUMN SIZING
           TOTAL = 100%
        ========================= */

        .sno-column {
            width: 5%;
        }

        .deal-column {
            width: 15%;
        }

        .company-column {
            width: 10%;
        }

        .contact-column {
            width: 10%;
        }

        .customer-column {
            width: 8%;
        }

        .amount-column {
            width: 8%;
        }

        .stage-column {
            width: 9%;
        }

        .close-column {
            width: 10%;
        }

        .assigned-column {
            width: 10%;
        }

        .action-column {
            width: 15%;
        }

        /* =========================
           DEAL
        ========================= */

        .deal-cell {
            min-width: 0;
        }

        .deal-title {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .deal-id {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 11px;

            white-space: nowrap;
        }

        /* =========================
           DATA
        ========================= */

        .serial {
            color: #64748b;

            font-weight: 700;

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
           AMOUNT
        ========================= */

        .amount-text {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================
           BADGES
        ========================= */

        .badge {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            max-width: 100%;

            padding: 5px 8px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .badge-prospecting {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .badge-qualification {
            background: #e0f2fe;

            color: #0369a1;
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
           ACTION COLUMN
        ========================= */

        .deals-table th.action-column {
            text-align: center;
        }

        .deals-table td.action-column {
            text-align: center;

            white-space: nowrap;

            overflow: visible;

            padding-left: 8px;

            padding-right: 8px;
        }

        .action-links {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

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

            .deals-table th {
                padding: 13px 9px;

                font-size: 10px;
            }

            .deals-table td {
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

            .deals-table th {
                padding: 12px 7px;

                font-size: 9px;
            }

            .deals-table td {
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
                💼
            </div>

            <div>

                <h1>
                    Deals
                </h1>

                <p>
                    Manage opportunities, stages and deal values
                </p>

            </div>

        </div>

        <a
            href="add.php"
            class="add-btn"
        >
            + Add Deal
        </a>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Deals
            </div>

            <div class="summary-value">
                <?= number_format($total_deals); ?>
            </div>

            <div class="summary-small">
                All deal records
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Open Deals
            </div>

            <div class="summary-value">
                <?= number_format($open_deals); ?>
            </div>

            <div class="summary-small">
                Active opportunities
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Closed Won
            </div>

            <div class="summary-value">
                <?= number_format($closed_won_deals); ?>
            </div>

            <div class="summary-small">
                Successfully closed deals
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Deal Value
            </div>

            <div class="summary-value">
                ₹<?= number_format($total_deal_value, 0); ?>
            </div>

            <div class="summary-small">
                Total pipeline value
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
                    id="dealSearch"
                    placeholder="Search deals..."
                    autocomplete="off"
                >

            </div>

            <select
                id="stageFilter"
                class="filter-select"
            >

                <option value="">
                    All Stages
                </option>

                <option value="prospecting">
                    Prospecting
                </option>

                <option value="qualification">
                    Qualification
                </option>

                <option value="proposal">
                    Proposal
                </option>

                <option value="negotiation">
                    Negotiation
                </option>

                <option value="closed_won">
                    Closed Won
                </option>

                <option value="lost">
                    Lost
                </option>

            </select>

        </div>

        <a
            href="../exports/deals_csv.php"
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
                class="deals-table"
                id="dealsTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="deal-column">
                            Deal
                        </th>

                        <th class="company-column">
                            Company
                        </th>

                        <th class="contact-column">
                            Contact
                        </th>

                        <th class="customer-column">
                            Customer
                        </th>

                        <th class="amount-column">
                            Amount
                        </th>

                        <th class="stage-column">
                            Stage
                        </th>

                        <th class="close-column">
                            Close Date
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

                <?php if (count($deals) > 0): ?>

                    <?php foreach ($deals as $index => $deal): ?>

                        <?php

                        $stage = strtolower(
                            trim(
                                $deal["stage"] ?? ""
                            )
                        );

                        switch ($stage) {

                            case "prospecting":

                                $stage_class =
                                    "badge-prospecting";

                                break;

                            case "qualification":

                                $stage_class =
                                    "badge-qualification";

                                break;

                            case "proposal":

                                $stage_class =
                                    "badge-proposal";

                                break;

                            case "negotiation":

                                $stage_class =
                                    "badge-negotiation";

                                break;

                            case "closed_won":
                            case "closed won":
                            case "won":
                            case "closed":

                                $stage_class =
                                    "badge-won";

                                break;

                            case "lost":
                            case "closed_lost":
                            case "closed lost":

                                $stage_class =
                                    "badge-lost";

                                break;

                            default:

                                $stage_class =
                                    "badge-default";

                                break;
                        }

                        $stage_label =
                            $deal["stage"] ?? "Unknown";

                        $filter_stage = $stage;

                        if (
                            $stage === "closed won" ||
                            $stage === "won" ||
                            $stage === "closed"
                        ) {
                            $filter_stage =
                                "closed_won";
                        }

                        $contact_name = trim(
                            $deal["contact_name"] ?? ""
                        );

                        ?>

                        <tr
                            data-stage="<?= htmlspecialchars($filter_stage); ?>"
                        >

                            <!-- S.NO -->

                            <td class="serial">

                                <?= $index + 1; ?>

                            </td>

                            <!-- DEAL -->

                            <td class="deal-cell">

                                <div class="deal-title">

                                    <?= htmlspecialchars(
                                        $deal["title"] ?? "—"
                                    ); ?>

                                </div>

                                <div class="deal-id">

                                    Deal ID:
                                    <?= (int) $deal["id"]; ?>

                                </div>

                            </td>

                            <!-- COMPANY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $deal["company_name"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $deal["company_name"]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- CONTACT -->

                            <td class="secondary">

                                <?php if ($contact_name !== ""): ?>

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
                                        $deal["customer_code"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $deal["customer_code"]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- AMOUNT -->

                            <td>

                                <span class="amount-text">

                                    ₹<?= number_format(
                                        (float) (
                                            $deal["amount"] ?? 0
                                        ),
                                        0
                                    ); ?>

                                </span>

                            </td>

                            <!-- STAGE -->

                            <td>

                                <span
                                    class="badge <?= $stage_class; ?>"
                                >
                                    <?= htmlspecialchars(
                                        $stage_label
                                    ); ?>
                                </span>

                            </td>

                            <!-- CLOSE DATE -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $deal[
                                            "expected_close_date"
                                        ]
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $deal[
                                                    "expected_close_date"
                                                ]
                                            )
                                        )
                                    ); ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ASSIGNED TO -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $deal["assigned_name"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $deal["assigned_name"]
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
                                        href="view.php?id=<?= (int) $deal["id"]; ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?= (int) $deal["id"]; ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?= (int) $deal["id"]; ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this deal?'
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
                                    💼
                                </div>

                                <h3>
                                    No deals found
                                </h3>

                                <p>
                                    Add your first deal
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
                    <?= count($deals); ?>
                </strong>

                deals

            </span>

            <span>
                CRM Deal Management
            </span>

        </div>

    </div>

</div>

<script>

/* =========================
   SEARCH + FILTER
========================= */

const dealSearch =
    document.getElementById(
        "dealSearch"
    );

const stageFilter =
    document.getElementById(
        "stageFilter"
    );

const dealRows =
    document.querySelectorAll(
        "#dealsTable tbody tr[data-stage]"
    );

function filterDeals() {

    const searchValue =
        dealSearch.value
            .toLowerCase()
            .trim();

    const stageValue =
        stageFilter.value
            .toLowerCase()
            .trim();

    dealRows.forEach(function(row) {

        const rowText =
            row.textContent
                .toLowerCase();

        const rowStage =
            row.dataset.stage
                .toLowerCase();

        const matchesSearch =
            rowText.includes(
                searchValue
            );

        const matchesStage =
            stageValue === ""
            ||
            rowStage === stageValue;

        row.style.display =
            matchesSearch &&
            matchesStage
                ? ""
                : "none";

    });
}

dealSearch.addEventListener(
    "input",
    filterDeals
);

stageFilter.addEventListener(
    "change",
    filterDeals
);

</script>

</body>

</html>