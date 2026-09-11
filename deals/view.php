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
| Get Deal Details
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            deals.*,
            companies.company_name,
            contacts.first_name,
            contacts.last_name,
            customers.customer_code,
            users.name AS assigned_name
        FROM deals

        LEFT JOIN companies
            ON deals.company_id = companies.id

        LEFT JOIN contacts
            ON deals.contact_id = contacts.id

        LEFT JOIN customers
            ON deals.customer_id = customers.id

        LEFT JOIN users
            ON deals.assigned_to = users.id

        WHERE deals.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$deal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deal) {
    die("Deal not found.");
}


/*
|--------------------------------------------------------------------------
| Get Products Added To This Deal
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            deal_products.id,
            deal_products.quantity,
            deal_products.unit_price,
            deal_products.total,
            products.name,
            products.sku
        FROM deal_products

        INNER JOIN products
            ON products.id = deal_products.product_id

        WHERE deal_products.deal_id = :deal_id

        ORDER BY deal_products.id ASC";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":deal_id" => $id
]);

$deal_products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Calculate Products Total
|--------------------------------------------------------------------------
*/

$products_total = 0;

foreach ($deal_products as $item) {
    $products_total += (float) $item["total"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>View Deal - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .deal-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 1200px;
            margin-bottom: 30px;
        }

        .deal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .deal-header h1 {
            margin: 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            margin-left: 8px;
        }

        .btn-edit {
            background: #2563eb;
        }

        .btn-products {
            background: #16a34a;
        }

        .btn-back {
            background: #6b7280;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th,
        .details-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .details-table th {
            width: 30%;
            background: #f8fafc;
            font-weight: bold;
        }

        .stage {
            display: inline-block;
            padding: 5px 10px;
            background: #e0e7ff;
            color: #3730a3;
            border-radius: 15px;
            font-size: 13px;
        }

        .amount {
            font-weight: bold;
            font-size: 18px;
        }

        /*
        |--------------------------------------------------------------------------
        | Products Section
        |--------------------------------------------------------------------------
        */

        .products-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 1200px;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .products-header h2 {
            margin: 0;
        }

        .products-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .products-table th,
        .products-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .products-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .products-table td {
            vertical-align: middle;
        }

        .products-total {
            text-align: right;
            margin-top: 20px;
            font-size: 20px;
            font-weight: bold;
        }

        .no-products {
            padding: 20px;
            background: #f8fafc;
            border-radius: 6px;
            color: #6b7280;
        }

        @media (max-width: 768px) {

            .deal-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .deal-header div {
                width: 100%;
            }

            .btn {
                margin-left: 0;
                margin-right: 5px;
                margin-bottom: 5px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">


    <!-- Deal Details -->

    <div class="deal-card">

        <div class="deal-header">

            <h1>Deal Details</h1>

            <div>

                <a
                    href="edit.php?id=<?php echo $deal["id"]; ?>"
                    class="btn btn-edit"
                >
                    Edit Deal
                </a>

                <a
                    href="deal_products.php?deal_id=<?php echo $id; ?>"
                    class="btn btn-products"
                >
                    Manage Products
                </a>

                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    Back
                </a>

            </div>

        </div>


        <table class="details-table">

            <tr>
                <th>Deal ID</th>

                <td>
                    <?php echo $deal["id"]; ?>
                </td>
            </tr>


            <tr>
                <th>Deal Title</th>

                <td>
                    <?php echo htmlspecialchars($deal["title"]); ?>
                </td>
            </tr>


            <tr>
                <th>Company</th>

                <td>

                    <?php

                    echo $deal["company_name"]
                        ? htmlspecialchars($deal["company_name"])
                        : "-";

                    ?>

                </td>
            </tr>


            <tr>
                <th>Contact</th>

                <td>

                    <?php

                    if ($deal["first_name"]) {

                        echo htmlspecialchars(
                            trim(
                                $deal["first_name"] . " " . $deal["last_name"]
                            )
                        );

                    } else {

                        echo "-";

                    }

                    ?>

                </td>
            </tr>


            <tr>
                <th>Customer</th>

                <td>

                    <?php

                    echo $deal["customer_code"]
                        ? htmlspecialchars($deal["customer_code"])
                        : "-";

                    ?>

                </td>
            </tr>


            <tr>
                <th>Amount</th>

                <td class="amount">

                    ₹ <?php echo number_format((float)$deal["amount"], 2); ?>

                </td>
            </tr>


            <tr>
                <th>Stage</th>

                <td>

                    <span class="stage">

                        <?php echo htmlspecialchars($deal["stage"]); ?>

                    </span>

                </td>
            </tr>


            <tr>
                <th>Probability</th>

                <td>

                    <?php echo (int)$deal["probability"]; ?>%

                </td>
            </tr>


            <tr>
                <th>Expected Close Date</th>

                <td>

                    <?php

                    echo $deal["expected_close_date"]
                        ? htmlspecialchars($deal["expected_close_date"])
                        : "-";

                    ?>

                </td>
            </tr>


            <tr>
                <th>Assigned To</th>

                <td>

                    <?php

                    echo $deal["assigned_name"]
                        ? htmlspecialchars($deal["assigned_name"])
                        : "-";

                    ?>

                </td>
            </tr>


            <tr>
                <th>Description</th>

                <td>

                    <?php

                    echo $deal["description"]
                        ? nl2br(htmlspecialchars($deal["description"]))
                        : "-";

                    ?>

                </td>
            </tr>


            <tr>
                <th>Created At</th>

                <td>

                    <?php echo htmlspecialchars($deal["created_at"]); ?>

                </td>
            </tr>


            <tr>
                <th>Updated At</th>

                <td>

                    <?php

                    echo $deal["updated_at"]
                        ? htmlspecialchars($deal["updated_at"])
                        : "-";

                    ?>

                </td>
            </tr>

        </table>

    </div>


    <!-- Products In This Deal -->

    <div class="products-card">

        <div class="products-header">

            <h2>Products In This Deal</h2>

            <a
                href="deal_products.php?deal_id=<?php echo $id; ?>"
                class="btn btn-products"
            >
                + Add Product
            </a>

        </div>


        <?php if (count($deal_products) > 0): ?>

            <div class="products-table-container">

                <table class="products-table">

                    <thead>

                        <tr>

                            <th>S.No.</th>

                            <th>Product</th>

                            <th>SKU</th>

                            <th>Quantity</th>

                            <th>Unit Price</th>

                            <th>Total</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php $serial_no = 1; ?>

                        <?php foreach ($deal_products as $item): ?>

                            <tr>

                                <td>
                                    <?php echo $serial_no++; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($item["name"]);
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo $item["sku"]
                                        ? htmlspecialchars($item["sku"])
                                        : "-";
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo number_format(
                                        (float)$item["quantity"],
                                        2
                                    );
                                    ?>
                                </td>

                                <td>
                                    ₹ <?php
                                    echo number_format(
                                        (float)$item["unit_price"],
                                        2
                                    );
                                    ?>
                                </td>

                                <td>
                                    ₹ <?php
                                    echo number_format(
                                        (float)$item["total"],
                                        2
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <div class="products-total">

                Products Total:
                ₹ <?php echo number_format($products_total, 2); ?>

            </div>

        <?php else: ?>

            <div class="no-products">

                No products have been added to this deal yet.

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>