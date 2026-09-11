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

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "DATE(deals.expected_close_date) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(deals.expected_close_date) <= :to_date";
    $params[":to_date"] = $to_date;
}

$where_sql = "";

if (!empty($where)) {
    $where_sql = " WHERE " . implode(" AND ", $where);
}


/* =========================
   GET DEALS
========================= */

$sql = "
    SELECT
        deals.id,
        deals.title,
        deals.amount,
        deals.stage,
        deals.probability,
        deals.expected_close_date,
        companies.company_name,
        contacts.first_name,
        contacts.last_name,
        users.name AS assigned_name
    FROM deals

    LEFT JOIN companies
        ON deals.company_id = companies.id

    LEFT JOIN contacts
        ON deals.contact_id = contacts.id

    LEFT JOIN users
        ON deals.assigned_to = users.id

    $where_sql

    ORDER BY deals.expected_close_date ASC, deals.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   SUMMARY
========================= */

$total_deals = count($deals);

$total_value = 0;
$weighted_value = 0;
$won_value = 0;

foreach ($deals as $deal) {

    $amount = (float) $deal["amount"];
    $probability = (int) $deal["probability"];

    $total_value += $amount;

    $weighted_value += $amount * $probability / 100;

    if (strtolower($deal["stage"]) == "won") {
        $won_value += $amount;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Deals Report</title>

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

        /* Export Buttons */
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

        .stage {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            background: #dbeafe;
            color: #1e40af;
            font-size: 12px;
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

            <h1>💼 Deals Report</h1>

            <p>
                Deal pipeline and value overview
            </p>

        </div>

        <a href="index.php" class="back-button">
            ← Back to Reports
        </a>

    </div>


    <!-- DATE FILTER -->

    <div class="filter-box">

        <h3>📅 Filter Deals by Expected Close Date</h3>

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


            <div class="filter-actions">

                <button type="submit" class="btn btn-primary">
                    Apply Filter
                </button>

                <a href="deals.php" class="btn btn-secondary">
                    Clear
                </a>

                <a
                    href="../exports/deals_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export CSV
                </a>

                <a
                    href="../exports/deals_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                    class="btn btn-success"
                >
                    Export Excel
                </a>

                <a
                    href="../exports/deals_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
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

            <h3>Total Deals</h3>

            <div class="value">
                <?php echo $total_deals; ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Total Deal Value</h3>

            <div class="value">
                ₹<?php echo number_format($total_value, 2); ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Weighted Deal Value</h3>

            <div class="value">
                ₹<?php echo number_format($weighted_value, 2); ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>Won Deal Value</h3>

            <div class="value">
                ₹<?php echo number_format($won_value, 2); ?>
            </div>

        </div>

    </div>


    <!-- DEALS TABLE -->

    <div class="table-container">

        <h2>Deals Details</h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Deal</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Amount</th>
                    <th>Stage</th>
                    <th>Probability</th>
                    <th>Expected Close</th>
                    <th>Assigned To</th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($deals)): ?>

                    <tr>

                        <td colspan="9" class="empty">
                            No deals found for the selected date range.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($deals as $deal): ?>

                        <tr>

                            <td>
                                <?php echo $deal["id"]; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $deal["title"]
                                );
                                ?>
                            </td>

                            <td>

                                <?php

                                echo !empty($deal["company_name"])
                                    ? htmlspecialchars(
                                        $deal["company_name"]
                                    )
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                if (
                                    !empty($deal["first_name"]) ||
                                    !empty($deal["last_name"])
                                ) {

                                    echo htmlspecialchars(
                                        trim(
                                            $deal["first_name"] . " " .
                                            $deal["last_name"]
                                        )
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>

                            <td>

                                ₹<?php

                                echo number_format(
                                    $deal["amount"],
                                    2
                                );

                                ?>

                            </td>

                            <td>

                                <span class="stage">

                                    <?php

                                    echo htmlspecialchars(
                                        ucfirst(
                                            $deal["stage"]
                                        )
                                    );

                                    ?>

                                </span>

                            </td>

                            <td>

                                <?php
                                echo (int) $deal["probability"];
                                ?>%

                            </td>

                            <td>

                                <?php

                                echo !empty(
                                    $deal["expected_close_date"]
                                )
                                    ? date(
                                        "d-m-Y",
                                        strtotime(
                                            $deal["expected_close_date"]
                                        )
                                    )
                                    : "-";

                                ?>

                            </td>

                            <td>

                                <?php

                                echo !empty(
                                    $deal["assigned_name"]
                                )
                                    ? htmlspecialchars(
                                        $deal["assigned_name"]
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