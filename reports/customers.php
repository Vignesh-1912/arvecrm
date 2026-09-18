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

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "DATE(customers.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(customers.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/* =========================
   GET CUSTOMERS
========================= */

$sql = "
    SELECT
        customers.*,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        contacts.email AS contact_email,
        contacts.phone AS contact_phone
    FROM customers
    LEFT JOIN companies
        ON customers.company_id = companies.id
    LEFT JOIN contacts
        ON customers.contact_id = contacts.id
    $where_sql
    ORDER BY customers.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   CUSTOMER SUMMARY
========================= */

$total_customers = count($customers);

$active_customers = 0;
$inactive_customers = 0;
$total_credit_limit = 0;

foreach ($customers as $customer) {

    $status = strtolower(
        trim($customer["status"] ?? "")
    );

    if ($status == "active") {
        $active_customers++;
    }

    if ($status == "inactive") {
        $inactive_customers++;
    }

    $total_credit_limit +=
        (float)($customer["credit_limit"] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customers Report</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <link rel="stylesheet" href="/crm/assets/css/reports.css">

    <style>
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

/* Export CSV / Excel */
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

        /* SUMMARY */

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
            overflow-x: auto;
        }

        .table-box h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
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

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
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

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- HEADER -->

    <?php
    $page_title = "Customers Report";
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

                <a href="customers.php" class="btn btn-secondary">
                    Reset
                </a>

                <a
                    href="../exports/customers_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export CSV
                </a>

                <a
                    href="../exports/customers_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Excel
                </a>

                <a
                    href="../exports/customers_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export PDF
                </a>

            </div>

        </form>

    </div>


    <!-- SUMMARY -->

    <div class="summary-grid">

        <div class="summary-card">

            <h3>Total Customers</h3>

            <p>
                <?php echo $total_customers; ?>
            </p>

        </div>


        <div class="summary-card">

            <h3>Active Customers</h3>

            <p>
                <?php echo $active_customers; ?>
            </p>

        </div>


        <div class="summary-card">

            <h3>Inactive Customers</h3>

            <p>
                <?php echo $inactive_customers; ?>
            </p>

        </div>


        <div class="summary-card">

            <h3>Total Credit Limit</h3>

            <p>
                ₹<?php echo number_format(
                    $total_credit_limit,
                    2
                ); ?>
            </p>

        </div>

    </div>


    <!-- CUSTOMER DETAILS -->

    <div class="table-box">

        <h2>
            Customer Details
        </h2>


        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Customer Code</th>

                    <th>Customer Type</th>

                    <th>Company</th>

                    <th>Contact</th>

                    <th>Email</th>

                    <th>Phone</th>

                    <th>Status</th>

                    <th>Credit Limit</th>

                    <th>Created Date</th>

                </tr>

            </thead>


            <tbody>

            <?php if (!empty($customers)): ?>

                <?php foreach ($customers as $customer): ?>

                    <?php

                    $status = strtolower(
                        trim(
                            $customer["status"] ?? ""
                        )
                    );

                    $contact_name = "-";

                    if (!empty($customer["first_name"])) {

                        $contact_name =
                            trim(
                                $customer["first_name"] .
                                " " .
                                ($customer["last_name"] ?? "")
                            );

                    }

                    ?>

                    <tr>

                        <td>
                            <?php echo $customer["id"]; ?>
                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $customer["customer_code"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $customer["customer_type"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $customer["company_name"] ?? "-"
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


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $customer["contact_email"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $customer["contact_phone"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <span class="status status-<?php
                                echo htmlspecialchars(
                                    $status
                                );
                            ?>">

                                <?php
                                echo htmlspecialchars(
                                    $customer["status"] ?? "-"
                                );
                                ?>

                            </span>

                        </td>


                        <td>

                            ₹<?php
                            echo number_format(
                                (float)(
                                    $customer["credit_limit"] ?? 0
                                ),
                                2
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $customer["created_at"] ?? "-"
                            );
                            ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="10"
                        class="no-data"
                    >
                        No customers found for the selected date range.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>