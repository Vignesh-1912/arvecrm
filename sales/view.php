<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Sale Details
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            sales.*,

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

        WHERE sales.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Sale not found.");
}


/*
|--------------------------------------------------------------------------
| Get Sale Items
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            sale_items.id,
            sale_items.description,
            sale_items.quantity,
            sale_items.unit_price,
            sale_items.discount,
            sale_items.tax,
            sale_items.total,
            products.name AS product_name,
            products.sku

        FROM sale_items

        LEFT JOIN products
            ON sale_items.product_id = products.id

        WHERE sale_items.sale_id = :sale_id

        ORDER BY sale_items.id ASC";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":sale_id" => $id
]);

$sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        View Sale - CRM
    </title>

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

        .button {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin-left: 5px;
        }

        .edit-button {
            background: #2563eb;
            color: white;
        }

        .products-button {
            background: #16a34a;
            color: white;
        }

        .back-button {
            background: #e5e7eb;
            color: #111827;
        }

        .details-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .details-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .detail-item {
            padding: 12px;
            background: #f8fafc;
            border-radius: 6px;
        }

        .detail-label {
            display: block;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: bold;
            color: #111827;
        }

        .notes {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 6px;
        }

        .items-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        .items-card h2 {
            margin-top: 0;
        }

        .items-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .items-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .items-table th {
            background: #f1f5f9;
        }

        .amount {
            white-space: nowrap;
        }

        .summary {
            margin-top: 20px;
            margin-left: auto;
            width: 350px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .summary-total {
            font-size: 18px;
            font-weight: bold;
            padding-top: 15px;
        }

        .empty-message {
            padding: 25px;
            text-align: center;
            color: #666;
            background: #f8fafc;
            border-radius: 6px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .paid {
            background: #dcfce7;
            color: #166534;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .partial {
            background: #dbeafe;
            color: #1e40af;
        }

        .completed {
            background: #dcfce7;
            color: #166534;
        }

        .cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">


    <!-- Page Header -->

    <div class="page-header">

        <div>

            <h1>
                Sale Details
            </h1>

            <p>
                <?php
                echo htmlspecialchars(
                    $sale["sale_number"]
                );
                ?>
            </p>

        </div>


        <div>

            <a
                href="edit.php?id=<?php echo $sale["id"]; ?>"
                class="button edit-button"
            >
                Edit Sale
            </a>

            <a
                href="sale_items.php?sale_id=<?php echo $sale["id"]; ?>"
                class="button products-button"
            >
                Manage Products
            </a>

            <a
                href="index.php"
                class="button back-button"
            >
                Back
            </a>

        </div>

    </div>


    <!-- Sale Information -->

    <div class="details-card">

        <h2>
            Sale Information
        </h2>


        <div class="details-grid">


            <div class="detail-item">

                <span class="detail-label">
                    Sale Number
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $sale["sale_number"]
                    );
                    ?>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Sale Date
                </span>

                <span class="detail-value">

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

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Customer
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $sale["customer_code"] ?? "-"
                    );
                    ?>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Company
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $sale["company_name"] ?? "-"
                    );
                    ?>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Contact
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        trim($sale["contact_name"] ?? "-")
                    );
                    ?>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Quote
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $sale["quote_number"] ?? "-"
                    );
                    ?>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Deal
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $sale["deal_title"] ?? "-"
                    );
                    ?>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Payment Status
                </span>

                <span class="detail-value">

                    <?php

                    $payment =
                        strtolower(
                            $sale["payment_status"] ?? ""
                        );

                    ?>

                    <span
                        class="status <?php echo htmlspecialchars($payment); ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            ucfirst(
                                $sale["payment_status"] ?? "-"
                            )
                        );
                        ?>

                    </span>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Sale Status
                </span>

                <span class="detail-value">

                    <?php

                    $sale_status =
                        strtolower(
                            $sale["sale_status"] ?? ""
                        );

                    ?>

                    <span
                        class="status <?php echo htmlspecialchars($sale_status); ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            ucfirst(
                                $sale["sale_status"] ?? "-"
                            )
                        );
                        ?>

                    </span>

                </span>

            </div>


            <div class="detail-item">

                <span class="detail-label">
                    Created By
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $sale["created_by_name"] ?? "-"
                    );
                    ?>

                </span>

            </div>


        </div>


        <?php if (!empty($sale["notes"])): ?>

            <div class="notes">

                <strong>
                    Notes
                </strong>

                <p>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $sale["notes"]
                        )
                    );
                    ?>
                </p>

            </div>

        <?php endif; ?>

    </div>


    <!-- Products -->

    <div class="items-card">

        <h2>
            Sale Products
        </h2>


        <?php if (count($sale_items) > 0): ?>

            <div class="items-table-container">

                <table class="items-table">

                    <thead>

                        <tr>

                            <th>
                                S.No.
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                SKU
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Unit Price
                            </th>

                            <th>
                                Discount
                            </th>

                            <th>
                                Tax
                            </th>

                            <th>
                                Total
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $serial_no = 1;

                    foreach ($sale_items as $item):

                    ?>

                        <tr>

                            <td>
                                <?php echo $serial_no++; ?>
                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $item["product_name"] ?? "-"
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $item["sku"] ?? "-"
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $item["description"] ?? "-"
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo number_format(
                                    (float) $item["quantity"],
                                    2
                                );
                                ?>

                            </td>

                            <td class="amount">

                                ₹
                                <?php
                                echo number_format(
                                    (float) $item["unit_price"],
                                    2
                                );
                                ?>

                            </td>

                            <td class="amount">

                                ₹
                                <?php
                                echo number_format(
                                    (float) $item["discount"],
                                    2
                                );
                                ?>

                            </td>

                            <td class="amount">

                                ₹
                                <?php
                                echo number_format(
                                    (float) $item["tax"],
                                    2
                                );
                                ?>

                            </td>

                            <td class="amount">

                                ₹
                                <?php
                                echo number_format(
                                    (float) $item["total"],
                                    2
                                );
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- Summary -->

            <div class="summary">

                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <span>

                        ₹
                        <?php
                        echo number_format(
                            (float) $sale["subtotal"],
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Discount
                    </span>

                    <span>

                        ₹
                        <?php
                        echo number_format(
                            (float) $sale["discount_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Tax
                    </span>

                    <span>

                        ₹
                        <?php
                        echo number_format(
                            (float) $sale["tax_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row summary-total">

                    <span>
                        Grand Total
                    </span>

                    <span>

                        ₹
                        <?php
                        echo number_format(
                            (float) $sale["total_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>

            </div>


        <?php else: ?>

            <div class="empty-message">

                No products have been added to this sale yet.

                <br><br>

                <a
                    href="sale_items.php?sale_id=<?php echo $sale["id"]; ?>"
                >
                    + Add Products
                </a>

            </div>

        <?php endif; ?>

    </div>


</div>

</body>

</html>