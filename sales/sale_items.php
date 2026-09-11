<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["sale_id"]) || !is_numeric($_GET["sale_id"])) {
    header("Location: index.php");
    exit;
}

$sale_id = (int) $_GET["sale_id"];

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Get Sale
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        sale_number,
        subtotal,
        tax_amount,
        discount_amount,
        total_amount
    FROM sales
    WHERE id = :id
");

$stmt->execute([
    ":id" => $sale_id
]);

$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Sale not found.");
}


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_id = !empty($_POST["product_id"])
        ? (int) $_POST["product_id"]
        : 0;

    $quantity = isset($_POST["quantity"])
        ? (float) $_POST["quantity"]
        : 0;

    $discount = isset($_POST["discount"])
        ? (float) $_POST["discount"]
        : 0;

    $tax = isset($_POST["tax"])
        ? (float) $_POST["tax"]
        : 0;


    if ($product_id <= 0) {

        $error = "Please select a product.";

    } elseif ($quantity <= 0) {

        $error = "Quantity must be greater than 0.";

    } elseif ($discount < 0) {

        $error = "Discount cannot be negative.";

    } elseif ($tax < 0) {

        $error = "Tax cannot be negative.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Get Product
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT
                    id,
                    name,
                    sku,
                    description,
                    price
                FROM products
                WHERE id = :id
                AND status = 1
            ");

            $stmt->execute([
                ":id" => $product_id
            ]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$product) {

                $error = "Selected product was not found.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Calculate Total
                |--------------------------------------------------------------------------
                */

                $unit_price = (float) $product["price"];

                $subtotal = $quantity * $unit_price;

                $total = $subtotal - $discount + $tax;

                if ($total < 0) {
                    $total = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | Insert Sale Item
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    INSERT INTO sale_items (
                        sale_id,
                        product_id,
                        description,
                        quantity,
                        unit_price,
                        discount,
                        tax,
                        total
                    )
                    VALUES (
                        :sale_id,
                        :product_id,
                        :description,
                        :quantity,
                        :unit_price,
                        :discount,
                        :tax,
                        :total
                    )
                ");

                $stmt->execute([

                    ":sale_id" => $sale_id,

                    ":product_id" => $product_id,

                    ":description" =>
                        $product["description"]
                        ?: $product["name"],

                    ":quantity" => $quantity,

                    ":unit_price" => $unit_price,

                    ":discount" => $discount,

                    ":tax" => $tax,

                    ":total" => $total

                ]);


                /*
                |--------------------------------------------------------------------------
                | Recalculate Sale Totals
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        COALESCE(
                            SUM(quantity * unit_price),
                            0
                        ) AS subtotal,

                        COALESCE(
                            SUM(discount),
                            0
                        ) AS discount_amount,

                        COALESCE(
                            SUM(tax),
                            0
                        ) AS tax_amount,

                        COALESCE(
                            SUM(total),
                            0
                        ) AS total_amount

                    FROM sale_items

                    WHERE sale_id = :sale_id
                ");

                $stmt->execute([
                    ":sale_id" => $sale_id
                ]);

                $totals = $stmt->fetch(PDO::FETCH_ASSOC);


                /*
                |--------------------------------------------------------------------------
                | Update Sale
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    UPDATE sales
                    SET
                        subtotal = :subtotal,
                        discount_amount = :discount_amount,
                        tax_amount = :tax_amount,
                        total_amount = :total_amount
                    WHERE id = :id
                ");

                $stmt->execute([

                    ":subtotal" =>
                        $totals["subtotal"],

                    ":discount_amount" =>
                        $totals["discount_amount"],

                    ":tax_amount" =>
                        $totals["tax_amount"],

                    ":total_amount" =>
                        $totals["total_amount"],

                    ":id" => $sale_id

                ]);


                header(
                    "Location: sale_items.php?sale_id="
                    . $sale_id
                );

                exit;
            }

        } catch (PDOException $e) {

            $error =
                "Unable to add product: "
                . $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| Delete Sale Item
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["delete"])
    && is_numeric($_GET["delete"])
) {

    $item_id = (int) $_GET["delete"];

    try {

        $stmt = $conn->prepare("
            DELETE FROM sale_items
            WHERE id = :id
            AND sale_id = :sale_id
        ");

        $stmt->execute([

            ":id" => $item_id,

            ":sale_id" => $sale_id

        ]);


        /*
        |--------------------------------------------------------------------------
        | Recalculate Totals
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                COALESCE(
                    SUM(quantity * unit_price),
                    0
                ) AS subtotal,

                COALESCE(
                    SUM(discount),
                    0
                ) AS discount_amount,

                COALESCE(
                    SUM(tax),
                    0
                ) AS tax_amount,

                COALESCE(
                    SUM(total),
                    0
                ) AS total_amount

            FROM sale_items

            WHERE sale_id = :sale_id
        ");

        $stmt->execute([
            ":sale_id" => $sale_id
        ]);

        $totals = $stmt->fetch(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | Update Sale
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            UPDATE sales
            SET
                subtotal = :subtotal,
                discount_amount = :discount_amount,
                tax_amount = :tax_amount,
                total_amount = :total_amount
            WHERE id = :id
        ");

        $stmt->execute([

            ":subtotal" =>
                $totals["subtotal"],

            ":discount_amount" =>
                $totals["discount_amount"],

            ":tax_amount" =>
                $totals["tax_amount"],

            ":total_amount" =>
                $totals["total_amount"],

            ":id" => $sale_id

        ]);


        header(
            "Location: sale_items.php?sale_id="
            . $sale_id
        );

        exit;

    } catch (PDOException $e) {

        $error =
            "Unable to delete item: "
            . $e->getMessage();

    }

}


/*
|--------------------------------------------------------------------------
| Load Active Products
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        sku,
        price
    FROM products
    WHERE status = 1
    ORDER BY name ASC
");

$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load Sale Items
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        sale_items.*,

        products.name AS product_name,

        products.sku

    FROM sale_items

    LEFT JOIN products
        ON sale_items.product_id = products.id

    WHERE sale_items.sale_id = :sale_id

    ORDER BY sale_items.id ASC
");

$stmt->execute([
    ":sale_id" => $sale_id
]);

$sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Refresh Sale Totals
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        subtotal,
        discount_amount,
        tax_amount,
        total_amount
    FROM sales
    WHERE id = :id
");

$stmt->execute([
    ":id" => $sale_id
]);

$sale_totals = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Sale Products - CRM
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

        .back-button {
            background: #e5e7eb;
            color: #111827;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .card h2 {
            margin-top: 0;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .add-button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .add-button:hover {
            background: #1d4ed8;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .items-container {
            width: 100%;
            overflow-x: auto;
        }

        .items-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .items-table th {
            background: #f1f5f9;
        }

        .amount {
            white-space: nowrap;
        }

        .delete-link {
            color: red;
            text-decoration: none;
        }

        .summary {
            width: 350px;
            margin-left: auto;
            margin-top: 25px;
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
            background: #f8fafc;
            color: #666;
            border-radius: 6px;
        }

        @media (max-width: 768px) {

            .form-row {
                flex-direction: column;
                gap: 0;
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


    <!-- Header -->

    <div class="page-header">

        <div>

            <h1>
                Sale Products
            </h1>

            <p>

                Sale:
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $sale["sale_number"]
                    );
                    ?>
                </strong>

            </p>

        </div>


        <a
            href="view.php?id=<?php echo $sale_id; ?>"
            class="back-button"
        >
            Back to Sale
        </a>

    </div>


    <?php if (!empty($error)): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- Add Product -->

    <div class="card">

        <h2>
            Add Product
        </h2>


        <form method="POST">


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Product *
                    </label>

                    <select
                        name="product_id"
                        required
                    >

                        <option value="">
                            -- Select Product --
                        </option>

                        <?php foreach ($products as $product): ?>

                            <option
                                value="<?php echo $product["id"]; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $product["name"]
                                );

                                if (!empty($product["sku"])) {

                                    echo " ("
                                        . htmlspecialchars(
                                            $product["sku"]
                                        )
                                        . ")";

                                }

                                echo " - ₹"
                                    . number_format(
                                        (float) $product["price"],
                                        2
                                    );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Quantity *
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Discount
                    </label>

                    <input
                        type="number"
                        name="discount"
                        value="0"
                        min="0"
                        step="0.01"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Tax
                    </label>

                    <input
                        type="number"
                        name="tax"
                        value="0"
                        min="0"
                        step="0.01"
                    >

                </div>


            </div>


            <button
                type="submit"
                class="add-button"
            >
                + Add Product
            </button>


        </form>

    </div>


    <!-- Sale Items -->

    <div class="card">

        <h2>
            Products in Sale
        </h2>


        <?php if (count($sale_items) > 0): ?>

            <div class="items-container">

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

                            <th>
                                Action
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
                                    $item["product_name"]
                                    ?? "-"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $item["sku"]
                                    ?? "-"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $item["description"]
                                    ?? "-"
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


                            <td>

                                <a
                                    href="sale_items.php?sale_id=<?php echo $sale_id; ?>&delete=<?php echo $item["id"]; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to remove this product?');"
                                >
                                    Delete
                                </a>

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
                            (float) $sale_totals["subtotal"],
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
                            (float) $sale_totals["discount_amount"],
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
                            (float) $sale_totals["tax_amount"],
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
                            (float) $sale_totals["total_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


            </div>


        <?php else: ?>

            <div class="empty-message">

                No products have been added to this sale yet.

            </div>

        <?php endif; ?>


    </div>


</div>

</body>

</html>