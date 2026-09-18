<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   QUOTES
============================== */

$stmt = $conn->query("
    SELECT
        q.id,
        q.quote_number,
        q.company_id,
        q.contact_id,
        q.customer_id,
        q.deal_id,
        q.subtotal,
        q.tax_amount,
        q.discount_amount,
        q.total_amount,
        q.status,
        q.valid_until,
        q.notes,
        q.created_by,
        q.created_at,

        co.company_name,

        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name,

        cu.customer_code,

        d.title AS deal_title,

        u.name AS created_by_name

    FROM quotes q

    LEFT JOIN companies co
        ON co.id = q.company_id

    LEFT JOIN contacts ct
        ON ct.id = q.contact_id

    LEFT JOIN customers cu
        ON cu.id = q.customer_id

    LEFT JOIN deals d
        ON d.id = q.deal_id

    LEFT JOIN users u
        ON u.id = q.created_by

    ORDER BY q.id DESC
");

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_quotes = count($quotes);

$draft_quotes = 0;
$sent_quotes = 0;
$accepted_quotes = 0;
$total_quote_value = 0;

foreach ($quotes as $quote) {

    $status = strtolower(
        trim($quote["status"] ?? "")
    );

    $total = (float) (
        $quote["total_amount"] ?? 0
    );

    $total_quote_value += $total;

    switch ($status) {

        case "draft":
            $draft_quotes++;
            break;

        case "sent":
            $sent_quotes++;
            break;

        case "accepted":
        case "approved":
            $accepted_quotes++;
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

    <title>Quotes - CRM</title>

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

        .quotes-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .quotes-table th {
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

        .quotes-table td {
            padding: 16px 12px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 13px;

            color: #334155;

            vertical-align: middle;
        }

        .quotes-table tbody tr:hover {
            background: #f8fbff;
        }

        .quotes-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* =========================
           COLUMN SIZING
           TOTAL = 100%
        ========================= */

        .sno-column {
            width: 5%;
        }

        .quote-column {
            width: 14%;
        }

        .company-column {
            width: 11%;
        }

        .contact-column {
            width: 11%;
        }

        .customer-column {
            width: 9%;
        }

        .deal-column {
            width: 10%;
        }

        .amount-column {
            width: 10%;
        }

        .status-column {
            width: 9%;
        }

        .valid-column {
            width: 8%;
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

        .quote-cell {
            min-width: 0;
        }

        .quote-number {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .quote-id {
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
           AMOUNT
        ========================= */

        .amount-text {
            color: #0f172a;

            font-weight: 700;

            white-space: nowrap;
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

        .status-draft {
            background: #f1f5f9;

            color: #475569;
        }

        .status-sent {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;

            color: #15803d;
        }

        .status-rejected,
        .status-declined {
            background: #fee2e2;

            color: #b91c1c;
        }

        .status-expired {
            background: #fef3c7;

            color: #b45309;
        }

        .status-cancelled {
            background: #ffedd5;

            color: #c2410c;
        }

        .status-default {
            background: #f1f5f9;

            color: #475569;
        }

        /* =========================
           ACTION
        ========================= */

        .quotes-table th.action-column {
            text-align: center;
        }

        .quotes-table td.action-column {
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

            .quotes-table th {
                padding: 13px 9px;

                font-size: 10px;
            }

            .quotes-table td {
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

            .quotes-table th {
                padding: 12px 7px;

                font-size: 9px;
            }

            .quotes-table td {
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
                📄
            </div>

            <div>

                <h1>
                    Quotes
                </h1>

                <p>
                    Manage quotations, pricing and customer proposals
                </p>

            </div>

        </div>

        <a
            href="add.php"
            class="add-btn"
        >
            + Add Quote
        </a>

    </div>

    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Quotes
            </div>

            <div class="summary-value">
                <?= number_format($total_quotes); ?>
            </div>

            <div class="summary-small">
                All quote records
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Draft Quotes
            </div>

            <div class="summary-value">
                <?= number_format($draft_quotes); ?>
            </div>

            <div class="summary-small">
                Quotes in draft
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Sent Quotes
            </div>

            <div class="summary-value">
                <?= number_format($sent_quotes); ?>
            </div>

            <div class="summary-small">
                Quotes sent to customers
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Quote Value
            </div>

            <div class="summary-value">
                ₹<?= number_format($total_quote_value, 0); ?>
            </div>

            <div class="summary-small">
                Total quote value
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
                    id="quoteSearch"
                    placeholder="Search quotes..."
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

                <option value="draft">
                    Draft
                </option>

                <option value="sent">
                    Sent
                </option>

                <option value="accepted">
                    Accepted
                </option>

                <option value="rejected">
                    Rejected
                </option>

                <option value="expired">
                    Expired
                </option>

                <option value="cancelled">
                    Cancelled
                </option>

            </select>

        </div>

        <a
            href="../exports/quotes_csv.php"
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
                class="quotes-table"
                id="quotesTable"
            >

                <thead>

                    <tr>

                        <th class="sno-column">
                            S.No
                        </th>

                        <th class="quote-column">
                            Quote
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

                        <th class="deal-column">
                            Deal
                        </th>

                        <th class="amount-column">
                            Total
                        </th>

                        <th class="status-column">
                            Status
                        </th>

                        <th class="valid-column">
                            Valid Until
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($quotes) > 0): ?>

                    <?php foreach (
                        $quotes
                        as $index => $quote
                    ): ?>

                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $quote["status"] ?? ""
                                )
                            );

                        switch ($status) {

                            case "draft":

                                $status_class =
                                    "status-draft";

                                break;

                            case "sent":

                                $status_class =
                                    "status-sent";

                                break;

                            case "accepted":
                            case "approved":

                                $status_class =
                                    "status-accepted";

                                break;

                            case "rejected":
                            case "declined":

                                $status_class =
                                    "status-rejected";

                                break;

                            case "expired":

                                $status_class =
                                    "status-expired";

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

                        $status_label =
                            $quote["status"] ?? "Unknown";

                        $contact_name =
                            trim(
                                $quote["contact_name"] ?? ""
                            );

                        ?>

                        <tr
                            data-status="<?= htmlspecialchars($status); ?>"
                        >

                            <!-- S.NO -->

                            <td class="serial">

                                <?= $index + 1; ?>

                            </td>

                            <!-- QUOTE -->

                            <td class="quote-cell">

                                <div class="quote-number">

                                    <?= htmlspecialchars(
                                        $quote["quote_number"]
                                            ?? "—"
                                    ); ?>

                                </div>

                                <div class="quote-id">

                                    Quote ID:
                                    <?= (int) $quote["id"]; ?>

                                </div>

                            </td>

                            <!-- COMPANY -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $quote["company_name"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $quote["company_name"]
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
                                        $quote["customer_code"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $quote[
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
                                        $quote["deal_title"]
                                    )
                                ): ?>

                                    <span class="data-text">

                                        <?= htmlspecialchars(
                                            $quote["deal_title"]
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- TOTAL -->

                            <td>

                                <span class="amount-text">

                                    ₹<?= number_format(
                                        (float) (
                                            $quote[
                                                "total_amount"
                                            ] ?? 0
                                        ),
                                        2
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
                                        $status_label
                                    ); ?>

                                </span>

                            </td>

                            <!-- VALID UNTIL -->

                            <td class="secondary">

                                <?php if (
                                    !empty(
                                        $quote["valid_until"]
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $quote[
                                                    "valid_until"
                                                ]
                                            )
                                        )
                                    ); ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <!-- ACTION -->

                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?= (int) $quote["id"]; ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?= (int) $quote["id"]; ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?= (int) $quote["id"]; ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this quote?'
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
                                    📄
                                </div>

                                <h3>
                                    No quotes found
                                </h3>

                                <p>
                                    Add your first quote
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
                    <?= count($quotes); ?>
                </strong>

                quotes

            </span>

            <span>
                CRM Quote Management
            </span>

        </div>

    </div>

</div>

<script>

/* =========================
   SEARCH + FILTER
========================= */

const quoteSearch =
    document.getElementById(
        "quoteSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const quoteRows =
    document.querySelectorAll(
        "#quotesTable tbody tr[data-status]"
    );

function filterQuotes() {

    const searchValue =
        quoteSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value
            .toLowerCase()
            .trim();

    quoteRows.forEach(function(row) {

        const rowText =
            row.textContent
                .toLowerCase();

        const rowStatus =
            row.dataset.status
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
                statusValue === "accepted"
                &&
                rowStatus === "approved"
            );

        row.style.display =
            matchesSearch &&
            matchesStatus
                ? ""
                : "none";

    });
}

quoteSearch.addEventListener(
    "input",
    filterQuotes
);

statusFilter.addEventListener(
    "change",
    filterQuotes
);

</script>

</body>

</html>