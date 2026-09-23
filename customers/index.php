<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/* ==============================
   CUSTOMERS
============================== */

$stmt = $conn->query("
    SELECT
        c.id,
        c.customer_code,
        c.customer_type,
        c.status,
        c.credit_limit,
        c.created_at,
        co.company_name,
        CONCAT(
            COALESCE(ct.first_name, ''),
            ' ',
            COALESCE(ct.last_name, '')
        ) AS contact_name
    FROM customers c
    LEFT JOIN companies co
        ON co.id = c.company_id
    LEFT JOIN contacts ct
        ON ct.id = c.contact_id
    ORDER BY c.id DESC
");

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   SUMMARY
============================== */

$total_customers = count($customers);

$active_customers = 0;
$inactive_customers = 0;
$total_credit = 0;

foreach ($customers as $customer) {

    $status = strtolower(
        trim($customer["status"] ?? "")
    );

    if (
        $status === "active" ||
        $status === "1"
    ) {
        $active_customers++;
    } else {
        $inactive_customers++;
    }

    $total_credit +=
        (float) ($customer["credit_limit"] ?? 0);
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

    <title>Customers - CRM</title>

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
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 28px;
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
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
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
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
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
            padding: 0 14px 0 37px;
            border: 1px solid #dbe3ee;
            border-radius: 9px;
            outline: none;
            background: #ffffff;
            font-size: 13px;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .filter-select {
            height: 40px;
            padding: 0 12px;
            border: 1px solid #dbe3ee;
            border-radius: 9px;
            background: #ffffff;
            color: #475569;
            outline: none;
            font-size: 13px;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border: 1px solid #dbe3ee;
            border-radius: 9px;
            background: #ffffff;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .export-btn:hover {
            background: #f8fafc;
        }

        /* =========================
           TABLE
        ========================= */

        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        .customers-table th {
            padding: 15px 16px;
            text-align: left;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .customers-table td {
            padding: 16px;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .customers-table tbody tr:hover {
            background: #f8fbff;
        }

        .customers-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .serial {
            color: #64748b;
            font-weight: 700;
        }

        .customer-name {
            color: #0f172a;
            font-weight: 700;
        }

        .customer-code {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
        }

        .secondary {
            color: #64748b;
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
        }

        .badge-active {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-type {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* =========================
           ACTIONS
        ========================= */

        .action-column {
            width: 180px;
            white-space: nowrap;
        }

        .action-links {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .action-links a {
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
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
            border-top: 1px solid #eef2f7;
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
                grid-template-columns: repeat(2, 1fr);
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

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="title-area">

            <div class="title-icon">
                👥
            </div>

            <div>

                <h1>
                    Customers
                </h1>

                <p>
                    Manage your customers and relationships
                </p>

            </div>

        </div>

        <a
            href="add.php"
            class="add-btn"
        >
            + Add Customer
        </a>

    </div>


    <!-- SUMMARY -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Customers
            </div>

            <div class="summary-value">
                <?php
                echo number_format(
                    $total_customers
                );
                ?>
            </div>

            <div class="summary-small">
                All customer records
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Active
            </div>

            <div class="summary-value">
                <?php
                echo number_format(
                    $active_customers
                );
                ?>
            </div>

            <div class="summary-small">
                Active customers
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Inactive
            </div>

            <div class="summary-value">
                <?php
                echo number_format(
                    $inactive_customers
                );
                ?>
            </div>

            <div class="summary-small">
                Inactive customers
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Credit Limit
            </div>

            <div class="summary-value">
                ₹<?php
                echo number_format(
                    $total_credit,
                    0
                );
                ?>
            </div>

            <div class="summary-small">
                Total credit limit
            </div>

        </div>

    </div>


    <!-- TOOLBAR -->

    <div class="toolbar">

        <div class="toolbar-left">

            <div class="search-wrapper">

                <span>
                    🔎
                </span>

                <input
                    type="text"
                    id="customerSearch"
                    placeholder="Search customers..."
                >

            </div>


            <select
                id="statusFilter"
                class="filter-select"
            >

                <option value="">
                    All Status
                </option>

                <option value="active">
                    Active
                </option>

                <option value="inactive">
                    Inactive
                </option>

            </select>

        </div>


        <a
            href="../exports/customers_csv.php"
            class="export-btn"
        >
            ↓ Export CSV
        </a>

    </div>


    <!-- TABLE -->

    <div class="table-card">

        <div class="table-wrap">

            <table
                class="customers-table"
                id="customersTable"
            >

                <thead>

                    <tr>

                        <th>
                            S.No
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Company
                        </th>

                        <th>
                            Contact
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Credit Limit
                        </th>

                        <th>
                            Created
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (
                    count($customers) > 0
                ): ?>

                    <?php foreach (
                        $customers
                        as $index => $customer
                    ): ?>

                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $customer["status"] ?? ""
                                )
                            );

                        $status_class =
                            (
                                $status === "active" ||
                                $status === "1"
                            )
                                ? "badge-active"
                                : "badge-inactive";

                        $status_text =
                            (
                                $status === "active" ||
                                $status === "1"
                            )
                                ? "Active"
                                : "Inactive";

                        ?>

                        <tr
                            data-status="<?php
                                echo $status_text === "Active"
                                    ? "active"
                                    : "inactive";
                            ?>"
                        >

                            <td class="serial">

                                <?php
                                echo $index + 1;
                                ?>

                            </td>


                            <td>

                                <div class="customer-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $customer[
                                            "customer_code"
                                        ] ??
                                        "Customer"
                                    );

                                    ?>

                                </div>

                                <div class="customer-code">

                                    Customer ID:
                                    <?php
                                    echo $customer["id"];
                                    ?>

                                </div>

                            </td>


                            <td class="secondary">

                                <?php

                                echo htmlspecialchars(
                                    $customer[
                                        "company_name"
                                    ] ??
                                    "—"
                                );

                                ?>

                            </td>


                            <td class="secondary">

                                <?php

                                echo htmlspecialchars(
                                    trim(
                                        $customer[
                                            "contact_name"
                                        ] ?? ""
                                    ) ?: "—"
                                );

                                ?>

                            </td>


                            <td>

                                <?php if (
                                    !empty(
                                        $customer[
                                            "customer_type"
                                        ]
                                    )
                                ): ?>

                                    <span
                                        class="badge badge-type"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $customer[
                                                "customer_type"
                                            ]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="
                                        badge
                                        <?php
                                        echo $status_class;
                                        ?>
                                    "
                                >

                                    <?php
                                    echo $status_text;
                                    ?>

                                </span>

                            </td>


                            <td>

                                ₹<?php

                                echo number_format(
                                    (float) (
                                        $customer[
                                            "credit_limit"
                                        ] ?? 0
                                    ),
                                    0
                                );

                                ?>

                            </td>


                            <td class="secondary">

                                <?php

                                echo !empty(
                                    $customer["created_at"]
                                )
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $customer[
                                                "created_at"
                                            ]
                                        )
                                    )
                                    : "—";

                                ?>

                            </td>


                            <td class="action-column">

                                <div class="action-links">

                                    <a
                                        href="view.php?id=<?php
                                        echo $customer["id"];
                                        ?>"
                                        class="view-link"
                                    >
                                        View
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="edit.php?id=<?php
                                        echo $customer["id"];
                                        ?>"
                                        class="edit-link"
                                    >
                                        Edit
                                    </a>

                                    <span>|</span>

                                    <a
                                        href="delete.php?id=<?php
                                        echo $customer["id"];
                                        ?>"
                                        class="delete-link"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this customer?'
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
                                    👥
                                </div>

                                <h3>
                                    No customers found
                                </h3>

                                <p>
                                    Add your first customer
                                    to get started.
                                </p>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="table-footer">

            <span>
                Showing
                <?php echo count($customers); ?>
                customers
            </span>

            <span>
                CRM Customer Management
            </span>

        </div>

    </div>

</div>


<script>

const customerSearch =
    document.getElementById(
        "customerSearch"
    );

const statusFilter =
    document.getElementById(
        "statusFilter"
    );

const customerRows =
    document.querySelectorAll(
        "#customersTable tbody tr[data-status]"
    );

function filterCustomers() {

    const searchValue =
        customerSearch.value
            .toLowerCase()
            .trim();

    const statusValue =
        statusFilter.value;

    customerRows.forEach(
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
                statusValue === "" ||
                rowStatus === statusValue;

            row.style.display =
                matchesSearch &&
                matchesStatus
                    ? ""
                    : "none";

        }
    );
}

customerSearch.addEventListener(
    "input",
    filterCustomers
);

statusFilter.addEventListener(
    "change",
    filterCustomers
);

</script>

</body>

</html>