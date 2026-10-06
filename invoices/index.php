<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$stmt = $conn->query("
    SELECT
        i.id,
        i.invoice_number,
        i.total_amount,
        i.invoice_date,
        i.invoice_status,
        c.customer_code,
        co.company_name,
        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name
    FROM invoices i
    LEFT JOIN customers c
        ON c.id = i.customer_id
    LEFT JOIN companies co
        ON co.id = i.company_id
    LEFT JOIN contacts ct
        ON ct.id = i.contact_id
    ORDER BY i.id DESC
");

$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_invoices = count($invoices);
$draft_invoices = 0;
$paid_invoices = 0;
$total_invoice_value = 0;
$invoice_statuses = [];

foreach ($invoices as $invoice) {
    $status = strtolower(trim($invoice["invoice_status"] ?? ""));
    $total_invoice_value += (float) ($invoice["total_amount"] ?? 0);

    if ($status === "draft") {
        $draft_invoices++;
    }

    if ($status === "paid") {
        $paid_invoices++;
    }

    if ($status !== "" && !in_array($status, $invoice_statuses, true)) {
        $invoice_statuses[] = $status;
    }
}

sort($invoice_statuses);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Invoices - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

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
            width: calc(100% - 250px);
            min-height: 100vh;
            margin-left: 250px;
            padding: 28px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
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
            flex: 0 0 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #dbeafe;
            font-size: 23px;
        }

        .page-header h1 {
            margin: 0;
            color: #0f172a;
            font-size: 24px;
            line-height: 1.2;
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
            min-height: 40px;
            padding: 0 16px;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .add-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .summary-card {
            position: relative;
            overflow: hidden;
            padding: 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }

        .summary-card::before {
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: #2563eb;
            content: "";
        }

        .summary-card.draft::before {
            background: #f59e0b;
        }

        .summary-card.value::before {
            background: #16a34a;
        }

        .summary-card.paid::before {
            background: #0d9488;
        }

        .summary-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 26px;
            font-weight: 700;
        }

        .summary-small {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
        }

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
            top: 11px;
            left: 13px;
            color: #94a3b8;
            pointer-events: none;
        }

        .search-wrapper input,
        .filter-select {
            height: 40px;
            border: 1px solid #dbe3ee;
            border-radius: 9px;
            outline: none;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
        }

        .search-wrapper input {
            width: 300px;
            padding: 0 14px 0 37px;
        }

        .search-wrapper input:focus,
        .filter-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-select {
            min-width: 145px;
            padding: 0 12px;
            cursor: pointer;
        }

        .records-label {
            color: #94a3b8;
            font-size: 12px;
            white-space: nowrap;
        }

        .table-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .invoices-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .invoices-table th {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .invoices-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #eef2f7;
            color: #334155;
            vertical-align: middle;
            font-size: 12px;
        }

        .invoices-table tbody tr:hover {
            background: #f8fbff;
        }

        .invoices-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .serial-cell {
            width: 5%;
            color: #64748b !important;
            font-weight: 700;
        }

        .invoice-cell {
            width: 15%;
        }

        .company-cell {
            width: 13%;
        }

        .customer-cell {
            width: 10%;
        }

        .contact-cell {
            width: 13%;
        }

        .date-cell {
            width: 11%;
        }

        .amount-cell {
            width: 11%;
        }

        .status-cell {
            width: 11%;
        }

        .action-cell {
            width: 11%;
            text-align: center !important;
        }

        .invoice-number {
            display: block;
            overflow: hidden;
            color: #0f172a;
            font-weight: 700;
            text-decoration: none;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .invoice-number:hover {
            color: #2563eb;
        }

        .invoice-id {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
        }

        .data-text {
            display: block;
            overflow: hidden;
            color: #475569;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .muted-text {
            color: #94a3b8;
        }

        .amount-text {
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-draft {
            background: #fef3c7;
            color: #b45309;
        }

        .status-paid,
        .status-sent,
        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-overdue,
        .status-cancelled,
        .status-canceled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-links {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            white-space: nowrap;
        }

        .action-links a {
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .action-links a.items-link {
            color: #059669;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .empty-state {
            padding: 54px 20px !important;
            text-align: center;
        }

        .empty-icon {
            margin-bottom: 10px;
            font-size: 38px;
        }

        .empty-state h3 {
            margin: 0;
            color: #334155;
            font-size: 16px;
        }

        .empty-state p {
            margin: 8px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 16px;
            border-top: 1px solid #eef2f7;
            color: #94a3b8;
            font-size: 11px;
        }

        .no-results {
            display: none;
            padding: 24px;
            color: #64748b;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 1200px) {
            .main-content {
                padding: 22px;
            }

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-content {
                width: calc(100% - 220px);
                margin-left: 220px;
                padding: 16px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .add-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .toolbar-left {
                align-items: stretch;
                flex-direction: column;
            }

            .search-wrapper input,
            .filter-select {
                width: 100%;
            }

            .search-wrapper {
                width: 100%;
            }

            .records-label {
                text-align: right;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<main class="main-content">

    <header class="page-header">
        <div class="title-area">
            <div class="title-icon">🧾</div>
            <div>
                <h1>Invoices</h1>
                <p>Manage customer billing, invoice statuses and payment totals</p>
            </div>
        </div>

        <a href="add.php" class="add-btn">
            + Create Invoice
        </a>
    </header>

    <section class="summary-grid" aria-label="Invoice summary">
        <article class="summary-card">
            <div class="summary-label">Total Invoices</div>
            <div class="summary-value"><?php echo number_format($total_invoices); ?></div>
            <div class="summary-small">All invoice records</div>
        </article>

        <article class="summary-card draft">
            <div class="summary-label">Draft Invoices</div>
            <div class="summary-value"><?php echo number_format($draft_invoices); ?></div>
            <div class="summary-small">Invoices still being prepared</div>
        </article>

        <article class="summary-card paid">
            <div class="summary-label">Paid Invoices</div>
            <div class="summary-value"><?php echo number_format($paid_invoices); ?></div>
            <div class="summary-small">Invoices marked as paid</div>
        </article>

        <article class="summary-card value">
            <div class="summary-label">Invoice Value</div>
            <div class="summary-value">₹<?php echo number_format($total_invoice_value, 2); ?></div>
            <div class="summary-small">Combined total of all invoices</div>
        </article>
    </section>

    <section class="toolbar" aria-label="Invoice list controls">
        <div class="toolbar-left">
            <label class="search-wrapper" for="invoiceSearch">
                <span aria-hidden="true">🔎</span>
                <input
                    type="search"
                    id="invoiceSearch"
                    placeholder="Search invoices..."
                    autocomplete="off"
                >
            </label>

            <select id="statusFilter" class="filter-select" aria-label="Filter invoices by status">
                <option value="">All statuses</option>
                <?php foreach ($invoice_statuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, "UTF-8"); ?>">
                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <span class="records-label">
            <span id="visibleCount"><?php echo number_format($total_invoices); ?></span>
            <?php echo $total_invoices === 1 ? "invoice" : "invoices"; ?>
        </span>
    </section>

    <section class="table-card">
        <div class="table-wrap">
            <table class="invoices-table" id="invoicesTable">
                <thead>
                    <tr>
                        <th class="serial-cell">S.No</th>
                        <th class="invoice-cell">Invoice</th>
                        <th class="company-cell">Company</th>
                        <th class="customer-cell">Customer</th>
                        <th class="contact-cell">Contact</th>
                        <th class="date-cell">Invoice Date</th>
                        <th class="amount-cell">Total</th>
                        <th class="status-cell">Status</th>
                        <th class="action-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_invoices > 0): ?>
                        <?php foreach ($invoices as $index => $invoice): ?>
                            <?php
                            $status = strtolower(trim($invoice["invoice_status"] ?? ""));
                            $status_class = preg_match("/^[a-z0-9_-]+$/", $status)
                                ? "status-" . $status
                                : "";
                            ?>
                            <tr class="invoice-row" data-status="<?php echo htmlspecialchars($status, ENT_QUOTES, "UTF-8"); ?>">
                                <td class="serial-cell"><?php echo $index + 1; ?></td>
                                <td class="invoice-cell">
                                    <a class="invoice-number" href="view.php?id=<?php echo (int) $invoice["id"]; ?>">
                                        <?php echo htmlspecialchars($invoice["invoice_number"] ?? ""); ?>
                                    </a>
                                    <span class="invoice-id">Invoice #<?php echo (int) $invoice["id"]; ?></span>
                                </td>
                                <td class="company-cell">
                                    <span class="data-text <?php echo empty($invoice["company_name"]) ? "muted-text" : ""; ?>">
                                        <?php echo htmlspecialchars($invoice["company_name"] ?: "Not linked"); ?>
                                    </span>
                                </td>
                                <td class="customer-cell">
                                    <span class="data-text <?php echo empty($invoice["customer_code"]) ? "muted-text" : ""; ?>">
                                        <?php echo htmlspecialchars($invoice["customer_code"] ?: "Not linked"); ?>
                                    </span>
                                </td>
                                <td class="contact-cell">
                                    <span class="data-text <?php echo trim($invoice["contact_name"] ?? "") === "" ? "muted-text" : ""; ?>">
                                        <?php echo htmlspecialchars(trim($invoice["contact_name"] ?? "") ?: "Not linked"); ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <?php
                                    echo !empty($invoice["invoice_date"])
                                        ? htmlspecialchars(date("d M Y", strtotime($invoice["invoice_date"])))
                                        : '<span class="muted-text">—</span>';
                                    ?>
                                </td>
                                <td class="amount-cell">
                                    <span class="amount-text">₹<?php echo number_format((float) ($invoice["total_amount"] ?? 0), 2); ?></span>
                                </td>
                                <td class="status-cell">
                                    <span class="status-badge <?php echo htmlspecialchars($status_class); ?>">
                                        <?php echo htmlspecialchars(ucfirst($status ?: "unknown")); ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <span class="action-links">
                                        <a href="view.php?id=<?php echo (int) $invoice["id"]; ?>">View</a>
                                        <a class="items-link" href="invoice_items.php?invoice_id=<?php echo (int) $invoice["id"]; ?>">Items</a>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="empty-icon">🧾</div>
                                <h3>No invoices yet</h3>
                                <p>Create your first invoice to get started.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="no-results" id="noResults">No invoices match your search.</div>
        </div>

        <?php if ($total_invoices > 0): ?>
            <footer class="table-footer">
                <span>Showing <span id="footerVisibleCount"><?php echo number_format($total_invoices); ?></span> of <?php echo number_format($total_invoices); ?> invoices</span>
                <span>Sorted by newest first</span>
            </footer>
        <?php endif; ?>
    </section>

</main>

<script>
    (function () {
        const searchInput = document.getElementById("invoiceSearch");
        const statusFilter = document.getElementById("statusFilter");
        const rows = Array.from(document.querySelectorAll(".invoice-row"));
        const visibleCount = document.getElementById("visibleCount");
        const footerVisibleCount = document.getElementById("footerVisibleCount");
        const noResults = document.getElementById("noResults");

        function filterInvoices() {
            const search = searchInput.value.trim().toLowerCase();
            const selectedStatus = statusFilter.value;
            let visible = 0;

            rows.forEach(function (row) {
                const matchesSearch = row.textContent.toLowerCase().includes(search);
                const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
                const show = matchesSearch && matchesStatus;

                row.hidden = !show;
                if (show) {
                    visible++;
                }
            });

            visibleCount.textContent = visible.toLocaleString();
            if (footerVisibleCount) {
                footerVisibleCount.textContent = visible.toLocaleString();
            }
            noResults.style.display = rows.length > 0 && visible === 0 ? "block" : "none";
        }

        searchInput.addEventListener("input", filterInvoices);
        statusFilter.addEventListener("change", filterInvoices);
    })();
</script>

</body>

</html>
