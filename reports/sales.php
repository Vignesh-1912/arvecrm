<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================
   DATE FILTER
========================= */

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";


/* =========================
   BUILD FILTER
========================= */

$where = [];
$params = [];

if (!empty($from_date)) {

    $where[] = "DATE(sales.sale_date) >= :from_date";

    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {

    $where[] = "DATE(sales.sale_date) <= :to_date";

    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {

    $where_sql = " WHERE " . implode(" AND ", $where);
}


/* =========================
   GET SALES
========================= */

$sql = "
    SELECT
        sales.id,
        sales.sale_number,
        sales.total_amount,
        sales.payment_status,
        sales.sale_status,
        sales.sale_date,
        customers.customer_code,
        companies.company_name
    FROM sales
    LEFT JOIN customers
        ON sales.customer_id = customers.id
    LEFT JOIN companies
        ON sales.company_id = companies.id
    $where_sql
    ORDER BY sales.sale_date DESC, sales.id DESC
";

$stmt = $conn->prepare($sql);

$stmt->execute($params);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   SUMMARY
========================= */

$total_sales = count($sales);

$total_amount = 0;

$paid_amount = 0;

$pending_amount = 0;


foreach ($sales as $sale) {

    $amount = (float) $sale["total_amount"];

    $total_amount += $amount;

    if (strtolower($sale["payment_status"]) == "paid") {

        $paid_amount += $amount;

    } else {

        $pending_amount += $amount;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Sales Report</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .report-header h1 {
            margin: 0;
        }

        .report-header p {
            color: #6b7280;
            margin-top: 5px;
        }

        .back-button {
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filter-box h3 {
            margin-top: 0;
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
            gap: 6px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: bold;
        }

        .filter-group input {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .filter-button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .filter-button:hover {
            background: #1d4ed8;
        }

        .clear-button {
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .btn-success {
            display: inline-block;
            padding: 10px 20px;
            background: #16a34a;
            color: white !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .summary-card h3 {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .summary-card .value {
            margin-top: 10px;
            font-size: 25px;
            font-weight: bold;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        .status {
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 12px;
        }

        .paid {
            background: #dcfce7;
            color: #166534;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }

        @media (max-width: 1000px) {

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 700px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .report-header {
                display: block;
            }

            .back-button {
                display: inline-block;
                margin-top: 15px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="report-header">

        <div>

            <h1>💰 Sales Report</h1>

            <p>
                Detailed sales and payment overview
            </p>

        </div>

        <a href="index.php" class="back-button">
            ← Back to Reports
        </a>

    </div>


    <!-- DATE FILTER -->

    <div class="filter-box">

        <h3>📅 Filter Sales by Date</h3>

        <form method="GET" class="filter-form">

            <div class="filter-group">

                <label>From Date</label>

                <input
                    type="date"
                    name="from_date"
                    value="<?php echo htmlspecialchars($from_date); ?>"
                >

            </div>


            <div class="filter-group">

                <label>To Date</label>

                <input
                    type="date"
                    name="to_date"
                    value="<?php echo htmlspecialchars($to_date); ?>"
                >

            </div>


            <button type="submit" class="filter-button">
                Apply Filter
            </button>


            <a href="sales.php" class="clear-button">
                Clear
            </a>

            <a
                href="../exports/sales_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn-success"
            >
                Export CSV
            </a>

            <a
                href="../exports/sales_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn-success"
            >
                Export Excel
            </a>

            <a
                href="../exports/sales_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn-success"
            >
                Export PDF
            </a>

        </form>

    </div>


    <!-- SUMMARY -->

    <div class="summary-grid">

        <div class="summary-card">

            <h3>Total Sales</h3>

            <div class="value">
                <?php echo $total_sales; ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Total Sales Amount</h3>

            <div class="value">
                ₹<?php echo number_format($total_amount, 2); ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Paid Amount</h3>

            <div class="value">
                ₹<?php echo number_format($paid_amount, 2); ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Pending Amount</h3>

            <div class="value">
                ₹<?php echo number_format($pending_amount, 2); ?>
            </div>

        </div>

    </div>


    <!-- SALES TABLE -->

    <div class="table-container">

        <h2>Sales Details</h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Sale Number</th>
                    <th>Customer</th>
                    <th>Company</th>
                    <th>Total Amount</th>
                    <th>Payment Status</th>
                    <th>Sale Status</th>
                    <th>Sale Date</th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($sales)): ?>

                    <tr>

                        <td colspan="8" class="empty">
                            No sales found for the selected date range.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($sales as $sale): ?>

                        <tr>

                            <td>
                                <?php echo $sale["id"]; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $sale["sale_number"]
                                );
                                ?>
                            </td>

                            <td>

                                <?php

                                echo !empty($sale["customer_code"])
                                    ? htmlspecialchars(
                                        $sale["customer_code"]
                                    )
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty($sale["company_name"])
                                    ? htmlspecialchars(
                                        $sale["company_name"]
                                    )
                                    : "-";

                                ?>

                            </td>

                            <td>

                                ₹<?php

                                echo number_format(
                                    $sale["total_amount"],
                                    2
                                );

                                ?>

                            </td>

                            <td>

                                <?php

                                if (
                                    strtolower(
                                        $sale["payment_status"]
                                    ) == "paid"
                                ):

                                ?>

                                    <span class="status paid">
                                        Paid
                                    </span>

                                <?php else: ?>

                                    <span class="status pending">

                                        <?php

                                        echo htmlspecialchars(
                                            ucfirst(
                                                $sale["payment_status"]
                                            )
                                        );

                                        ?>

                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    ucfirst(
                                        $sale["sale_status"]
                                    )
                                );

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty($sale["sale_date"])
                                    ? date(
                                        "d-m-Y",
                                        strtotime(
                                            $sale["sale_date"]
                                        )
                                    )
                                    : "-";

                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>