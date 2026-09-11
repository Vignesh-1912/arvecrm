<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "DATE(quotes.created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(quotes.created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}

/* Get Quotes */

$sql = "
    SELECT
        quotes.*,
        companies.company_name,
        customers.customer_code,
        deals.title AS deal_title
    FROM quotes
    LEFT JOIN companies
        ON quotes.company_id = companies.id
    LEFT JOIN customers
        ON quotes.customer_id = customers.id
    LEFT JOIN deals
        ON quotes.deal_id = deals.id
    $where_sql
    ORDER BY quotes.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Summary */

$total_quotes = count($quotes);
$total_quote_value = 0;
$accepted_quote_value = 0;
$draft_quotes = 0;

foreach ($quotes as $quote) {

    $total_quote_value += (float)$quote["total_amount"];

    if ($quote["status"] == "accepted") {
        $accepted_quote_value += (float)$quote["total_amount"];
    }

    if ($quote["status"] == "draft") {
        $draft_quotes++;
    }
}

?>

<!DOCTYPE html>
<html>
<head>

    <title>Quotes Report</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>
        .btn-success {
    background: #16a34a;
    color: white;
}

.btn-success:hover {
    background: #15803d;
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
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

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
            font-size: 24px;
            font-weight: bold;
        }

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f9fafb;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .status-draft {
            background: #fef3c7;
        }

        .status-accepted {
            background: #dcfce7;
        }

        .status-rejected {
            background: #fee2e2;
        }

        .status-sent {
            background: #dbeafe;
        }

        @media (max-width: 1000px) {

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="report-header">

        <h1>Quotes Report</h1>

    </div>

    <!-- Date Filter -->

    <div class="filter-box">

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

            <button type="submit" class="btn btn-primary">
                Filter 
            </button> 

            <a href="quotes.php" class="btn btn-secondary">
                Reset
            </a>

            <a
                href="../exports/quotes_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn btn-success"
            >
                Export CSV
            </a>

            <a
                href="../exports/quotes_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                class="btn btn-success"
            >
                Export Excel
            </a>

        </form>

    </div>

    <!-- Summary -->

    <div class="summary-grid">

        <div class="summary-card">

            <h3>Total Quotes</h3>

            <p><?php echo $total_quotes; ?></p>

        </div>

        <div class="summary-card">

            <h3>Total Quote Value</h3>

            <p>
                ₹<?php echo number_format($total_quote_value, 2); ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Accepted Quote Value</h3>

            <p>
                ₹<?php echo number_format($accepted_quote_value, 2); ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Draft Quotes</h3>

            <p><?php echo $draft_quotes; ?></p>

        </div>

    </div>

    <!-- Quote Details -->

    <div class="table-box">

        <h2>Quote Details</h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Quote Number</th>
                    <th>Company</th>
                    <th>Customer</th>
                    <th>Deal</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Valid Until</th>
                    <th>Created Date</th>

                </tr>

            </thead>

            <tbody>

            <?php if (count($quotes) > 0): ?>

                <?php foreach ($quotes as $quote): ?>

                    <tr>

                        <td>
                            <?php echo $quote["id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($quote["quote_number"]); ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $quote["company_name"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $quote["customer_code"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $quote["deal_title"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            ₹<?php
                            echo number_format(
                                (float)$quote["total_amount"],
                                2
                            );
                            ?>
                        </td>

                        <td>

                            <span class="status status-<?php
                                echo htmlspecialchars(
                                    strtolower($quote["status"])
                                );
                            ?>">

                                <?php
                                echo htmlspecialchars($quote["status"]);
                                ?>

                            </span>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $quote["valid_until"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $quote["created_at"]
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="9" style="text-align:center;">
                        No quotes found for the selected date range.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>