<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


// Check quote ID

if (!isset($_GET["quote_id"]) || !is_numeric($_GET["quote_id"])) {
    header("Location: index.php");
    exit;
}

$quote_id = (int) $_GET["quote_id"];


// Get Quote

$stmt = $conn->prepare("
    SELECT *
    FROM quotes
    WHERE id = :id
");

$stmt->execute([
    ":id" => $quote_id
]);

$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    die("Quote not found.");
}


$error = "";


// Add Product

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = (int) $_POST["product_id"];

    $quantity = is_numeric($_POST["quantity"])
        ? (float) $_POST["quantity"]
        : 0;

    $discount = is_numeric($_POST["discount"])
        ? (float) $_POST["discount"]
        : 0;

    $tax = is_numeric($_POST["tax"])
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

        // Get product

        $stmt = $conn->prepare("
            SELECT id, name, sku, price
            FROM products
            WHERE id = :id
            AND status = 1
        ");

        $stmt->execute([
            ":id" => $product_id
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$product) {

            $error = "Product not found or inactive.";

        } else {

            $unit_price = (float) $product["price"];


            // Calculate total

            $subtotal = $quantity * $unit_price;

            $total = $subtotal - $discount + $tax;


            if ($total < 0) {
                $total = 0;
            }


            // Insert quote item

            $stmt = $conn->prepare("
                INSERT INTO quote_items (
                    quote_id,
                    product_id,
                    description,
                    quantity,
                    unit_price,
                    discount,
                    tax,
                    total
                )
                VALUES (
                    :quote_id,
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

                ":quote_id" => $quote_id,

                ":product_id" => $product_id,

                ":description" => $product["name"],

                ":quantity" => $quantity,

                ":unit_price" => $unit_price,

                ":discount" => $discount,

                ":tax" => $tax,

                ":total" => $total

            ]);


            // Recalculate quote

            $stmt = $conn->prepare("
                SELECT
                    COALESCE(SUM(quantity * unit_price), 0) AS subtotal,
                    COALESCE(SUM(discount), 0) AS discount,
                    COALESCE(SUM(tax), 0) AS tax
                FROM quote_items
                WHERE quote_id = :quote_id
            ");

            $stmt->execute([
                ":quote_id" => $quote_id
            ]);

            $summary = $stmt->fetch(PDO::FETCH_ASSOC);


            $new_subtotal = (float) $summary["subtotal"];

            $new_discount = (float) $summary["discount"];

            $new_tax = (float) $summary["tax"];

            $new_total =
                $new_subtotal
                - $new_discount
                + $new_tax;


            if ($new_total < 0) {
                $new_total = 0;
            }


            // Update quote

            $stmt = $conn->prepare("
                UPDATE quotes
                SET
                    subtotal = :subtotal,
                    discount_amount = :discount,
                    tax_amount = :tax,
                    total_amount = :total
                WHERE id = :id
            ");

            $stmt->execute([

                ":subtotal" => $new_subtotal,

                ":discount" => $new_discount,

                ":tax" => $new_tax,

                ":total" => $new_total,

                ":id" => $quote_id

            ]);


            header(
                "Location: quote_items.php?quote_id="
                . $quote_id
            );

            exit;
        }
    }
}


// Remove Product

if (
    isset($_GET["delete"])
    && is_numeric($_GET["delete"])
) {

    $item_id = (int) $_GET["delete"];


    $stmt = $conn->prepare("
        DELETE FROM quote_items
        WHERE id = :id
        AND quote_id = :quote_id
    ");

    $stmt->execute([

        ":id" => $item_id,

        ":quote_id" => $quote_id

    ]);


    // Recalculate quote

    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(quantity * unit_price), 0) AS subtotal,
            COALESCE(SUM(discount), 0) AS discount,
            COALESCE(SUM(tax), 0) AS tax
        FROM quote_items
        WHERE quote_id = :quote_id
    ");

    $stmt->execute([
        ":quote_id" => $quote_id
    ]);

    $summary = $stmt->fetch(PDO::FETCH_ASSOC);


    $new_subtotal = (float) $summary["subtotal"];

    $new_discount = (float) $summary["discount"];

    $new_tax = (float) $summary["tax"];

    $new_total =
        $new_subtotal
        - $new_discount
        + $new_tax;


    if ($new_total < 0) {
        $new_total = 0;
    }


    $stmt = $conn->prepare("
        UPDATE quotes
        SET
            subtotal = :subtotal,
            discount_amount = :discount,
            tax_amount = :tax,
            total_amount = :total
        WHERE id = :id
    ");

    $stmt->execute([

        ":subtotal" => $new_subtotal,

        ":discount" => $new_discount,

        ":tax" => $new_tax,

        ":total" => $new_total,

        ":id" => $quote_id

    ]);


    header(
        "Location: quote_items.php?quote_id="
        . $quote_id
    );

    exit;
}


// Get Active Products

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


// Get Quote Items

$stmt = $conn->prepare("
    SELECT
        quote_items.*,
        products.name AS product_name,
        products.sku
    FROM quote_items

    LEFT JOIN products
        ON quote_items.product_id = products.id

    WHERE quote_items.quote_id = :quote_id

    ORDER BY quote_items.id ASC
");

$stmt->execute([
    ":quote_id" => $quote_id
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calculate subtotal

$items_subtotal = 0;

foreach ($items as $item) {

    $items_subtotal +=
        (float) $item["quantity"]
        * (float) $item["unit_price"];
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Quote Products - CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        .page-card {

            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.08);

            max-width: 1200px;

            margin-bottom: 30px;

        }


        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin: 0;

        }


        .quote-info {

            color: #6b7280;

            margin-top: 5px;

        }


        .form-grid {

            display: grid;

            grid-template-columns:
                2fr
                1fr
                1fr
                1fr
                auto;

            gap: 15px;

            align-items: end;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        label {

            font-weight: bold;

            margin-bottom: 7px;

        }


        select,
        input {

            padding: 10px;

            border: 1px solid #d1d5db;

            border-radius: 6px;

            font-size: 14px;

        }


        .btn {

            display: inline-block;

            padding: 10px 18px;

            border-radius: 6px;

            text-decoration: none;

            border: none;

            cursor: pointer;

        }


        .btn-add {

            background: #16a34a;

            color: white;

        }


        .btn-back {

            background: #6b7280;

            color: white;

        }


        .btn-delete {

            color: #dc2626;

            text-decoration: none;

        }


        .error {

            background: #fee2e2;

            color: #991b1b;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .table-container {

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

            padding: 13px 12px;

            border-bottom: 1px solid #e5e7eb;

            text-align: left;

        }


        .items-table th {

            background: #f1f5f9;

            font-weight: bold;

        }


        .amount {

            white-space: nowrap;

            font-weight: bold;

        }


        .summary {

            margin-top: 25px;

            margin-left: auto;

            max-width: 400px;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 10px 0;

            border-bottom: 1px solid #e5e7eb;

        }


        .summary-row.total {

            font-size: 20px;

            font-weight: bold;

            border-bottom: none;

        }


        .no-items {

            padding: 20px;

            background: #f8fafc;

            border-radius: 6px;

            color: #6b7280;

        }


        @media (max-width: 900px) {

            .form-grid {

                grid-template-columns: 1fr 1fr;

            }

        }


        @media (max-width: 600px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="main-content">


    <!-- Add Product -->

    <div class="page-card">


        <div class="page-header">

            <div>

                <h1>
                    Quote Products
                </h1>

                <div class="quote-info">

                    Quote:
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $quote["quote_number"]
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div>

                <a
                    href="view.php?id=<?php echo $quote_id; ?>"
                    class="btn btn-back"
                >
                    Back to Quote
                </a>

            </div>

        </div>


        <?php if ($error): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-grid">


                <div class="form-group">

                    <label for="product_id">
                        Product
                    </label>

                    <select
                        name="product_id"
                        id="product_id"
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

                                if ($product["sku"]) {

                                    echo " - "
                                        . htmlspecialchars(
                                            $product["sku"]
                                        );

                                }

                                echo " - ₹"
                                    . number_format(
                                        (float)$product["price"],
                                        2
                                    );

                                ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div class="form-group">

                    <label for="quantity">
                        Quantity
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        value="1"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="discount">
                        Discount
                    </label>

                    <input
                        type="number"
                        name="discount"
                        id="discount"
                        value="0"
                        min="0"
                        step="0.01"
                    >

                </div>


                <div class="form-group">

                    <label for="tax">
                        Tax
                    </label>

                    <input
                        type="number"
                        name="tax"
                        id="tax"
                        value="0"
                        min="0"
                        step="0.01"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-add"
                >
                    + Add Product
                </button>


            </div>


        </form>


    </div>


    <!-- Products List -->

    <div class="page-card">


        <h2>
            Products In This Quote
        </h2>


        <?php if (count($items) > 0): ?>


            <div class="table-container">


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


                        <?php $serial_no = 1; ?>


                        <?php foreach ($items as $item): ?>


                            <tr>


                                <td>

                                    <?php
                                    echo $serial_no++;
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo $item["product_name"]
                                        ? htmlspecialchars(
                                            $item["product_name"]
                                        )
                                        : htmlspecialchars(
                                            $item["description"]
                                        );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo $item["sku"]
                                        ? htmlspecialchars(
                                            $item["sku"]
                                        )
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


                                <td class="amount">

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
                                        (float)$item["discount"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    ₹ <?php

                                    echo number_format(
                                        (float)$item["tax"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td class="amount">

                                    ₹ <?php

                                    echo number_format(
                                        (float)$item["total"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    <a
                                        href="quote_items.php?quote_id=<?php echo $quote_id; ?>&delete=<?php echo $item["id"]; ?>"
                                        class="btn-delete"
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


            <div class="summary">


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <span>

                        ₹ <?php
                        echo number_format(
                            (float)$quote["subtotal"],
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

                        ₹ <?php
                        echo number_format(
                            (float)$quote["discount_amount"],
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

                        ₹ <?php
                        echo number_format(
                            (float)$quote["tax_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row total">

                    <span>
                        Total
                    </span>

                    <span>

                        ₹ <?php
                        echo number_format(
                            (float)$quote["total_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


            </div>


        <?php else: ?>


            <div class="no-items">

                No products have been added to this quote yet.

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>