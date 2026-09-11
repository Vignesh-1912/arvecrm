<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Get Sales
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            sales.id,
            sales.sale_number,
            sales.subtotal,
            sales.tax_amount,
            sales.discount_amount,
            sales.total_amount,
            sales.payment_status,
            sales.sale_status,
            sales.sale_date,

            customers.customer_code,

            companies.company_name,

            CONCAT(
                contacts.first_name,
                ' ',
                contacts.last_name
            ) AS contact_name,

            quotes.quote_number,

            deals.title AS deal_title,

            users.name AS created_by_name

        FROM sales

        LEFT JOIN customers
            ON sales.customer_id = customers.id

        LEFT JOIN companies
            ON sales.company_id = companies.id

        LEFT JOIN contacts
            ON sales.contact_id = contacts.id

        LEFT JOIN quotes
            ON sales.quote_id = quotes.id

        LEFT JOIN deals
            ON sales.deal_id = deals.id

        LEFT JOIN users
            ON sales.created_by = users.id

        ORDER BY sales.id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Sales - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
        }

        .add-button {
            background: #2563eb;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }

        .add-button:hover {
            background: #1d4ed8;
        }

        .sales-table-container {
            width: 100%;
            overflow-x: auto;
            background: white;
            border-radius: 8px;
        }

        .sales-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .sales-table th,
        .sales-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .sales-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .sales-table tr:hover {
            background: #f8fafc;
        }

        .amount {
            white-space: nowrap;
        }

        .date {
            white-space: nowrap;
        }

        .action-links {
            white-space: nowrap;
        }

        .action-links a {
            display: inline-block;
            margin-right: 6px;
            text-decoration: none;
        }

        .view {
            color: #2563eb;
        }

        .edit {
            color: #16a34a;
        }

        .delete {
            color: red;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .payment-paid {
            background: #dcfce7;
            color: #166534;
        }

        .payment-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-partial {
            background: #dbeafe;
            color: #1e40af;
        }

        .sale-completed {
            background: #dcfce7;
            color: #166534;
        }

        .sale-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .sale-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-message {
            padding: 30px;
            text-align: center;
            color: #666;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-header">

        <div>
            <h1>Sales</h1>
            <p>Manage your sales records</p>
        </div>

        <a
            href="add.php"
            class="add-button"
        >
            + Add Sale
        </a>

    </div>


    <div class="sales-table-container">

        <?php if (count($sales) > 0): ?>

            <table class="sales-table">

                <thead>

                    <tr>

                        <th style="width: 5%;">ID</th>

                        <th style="width: 11%;">
                            Sale Number
                        </th>

                        <th style="width: 10%;">
                            Customer
                        </th>

                        <th style="width: 12%;">
                            Company
                        </th>

                        <th style="width: 11%;">
                            Contact
                        </th>

                        <th style="width: 10%;">
                            Quote
                        </th>

                        <th style="width: 11%;">
                            Deal
                        </th>

                        <th style="width: 10%;">
                            Total Amount
                        </th>

                        <th style="width: 9%;">
                            Payment
                        </th>

                        <th style="width: 9%;">
                            Status
                        </th>

                        <th style="width: 10%;">
                            Sale Date
                        </th>

                        <th style="width: 14%;">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($sales as $sale): ?>

                    <tr>

                        <td>
                            <?php echo $sale["id"]; ?>
                        </td>


                        <td>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $sale["sale_number"]
                                );
                                ?>
                            </strong>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale["customer_code"] ?? "-"
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale["company_name"] ?? "-"
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                trim($sale["contact_name"] ?? "-")
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale["quote_number"] ?? "-"
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale["deal_title"] ?? "-"
                            );
                            ?>
                        </td>


                        <td class="amount">

                            ₹
                            <?php
                            echo number_format(
                                (float) $sale["total_amount"],
                                2
                            );
                            ?>

                        </td>


                        <td>

                            <?php

                            $payment_status =
                                strtolower(
                                    $sale["payment_status"] ?? ""
                                );

                            if ($payment_status == "paid") {

                                $payment_class = "payment-paid";

                            } elseif ($payment_status == "partial") {

                                $payment_class = "payment-partial";

                            } else {

                                $payment_class = "payment-pending";

                            }

                            ?>

                            <span
                                class="status <?php echo $payment_class; ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $sale["payment_status"] ?? "-"
                                    )
                                );
                                ?>
                            </span>

                        </td>


                        <td>

                            <?php

                            $sale_status =
                                strtolower(
                                    $sale["sale_status"] ?? ""
                                );

                            if ($sale_status == "completed") {

                                $sale_class = "sale-completed";

                            } elseif ($sale_status == "cancelled") {

                                $sale_class = "sale-cancelled";

                            } else {

                                $sale_class = "sale-pending";

                            }

                            ?>

                            <span
                                class="status <?php echo $sale_class; ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $sale["sale_status"] ?? "-"
                                    )
                                );
                                ?>
                            </span>

                        </td>


                        <td class="date">

                            <?php

                            if (!empty($sale["sale_date"])) {

                                echo date(
                                    "d-m-Y",
                                    strtotime($sale["sale_date"])
                                );

                            } else {

                                echo "-";

                            }

                            ?>

                        </td>


                        <td class="action-links">

                            <a
                                href="view.php?id=<?php echo $sale["id"]; ?>"
                                class="view"
                            >
                                View
                            </a>

                            <a
                                href="edit.php?id=<?php echo $sale["id"]; ?>"
                                class="edit"
                            >
                                Edit
                            </a>

                            <a
                                href="delete.php?id=<?php echo $sale["id"]; ?>"
                                class="delete"
                                onclick="return confirm('Are you sure you want to delete this sale?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty-message">

                No sales found.

                <br><br>

                <a href="add.php">
                    Create your first sale
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>