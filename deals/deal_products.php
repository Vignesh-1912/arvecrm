<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

if (!isset($_GET["deal_id"]) || !is_numeric($_GET["deal_id"])) {
    header("Location: index.php");
    exit;
}

$deal_id = (int) $_GET["deal_id"];

/*
|--------------------------------------------------------------------------
| Get Deal
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        deals.id,
        deals.title,
        deals.amount
    FROM deals
    WHERE deals.id = :deal_id
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":deal_id" => $deal_id
]);

$deal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deal) {
    die("Deal not found.");
}


/*
|--------------------------------------------------------------------------
| Add Product To Deal
|--------------------------------------------------------------------------
*/

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = isset($_POST["product_id"]) ? (int) $_POST["product_id"] : 0;
    $quantity = isset($_POST["quantity"]) ? (float) $_POST["quantity"] : 0;

    if ($product_id <= 0) {

        $error = "Please select a product.";

    } elseif ($quantity <= 0) {

        $error = "Quantity must be greater than 0.";

    } else {

        // Get product price
        $sql = "
            SELECT id, name, price
            FROM products
            WHERE id = :product_id
            AND status = 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":product_id" => $product_id
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {

            $error = "Product not found or inactive.";

        } else {

            $unit_price = (float) $product["price"];
            $total = $quantity * $unit_price;

            try {

                $sql = "
                    INSERT INTO deal_products
                    (
                        deal_id,
                        product_id,
                        quantity,
                        unit_price,
                        total
                    )
                    VALUES
                    (
                        :deal_id,
                        :product_id,
                        :quantity,
                        :unit_price,
                        :total
                    )
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ":deal_id" => $deal_id,
                    ":product_id" => $product_id,
                    ":quantity" => $quantity,
                    ":unit_price" => $unit_price,
                    ":total" => $total
                ]);

                $message = "Product added to deal successfully.";

            } catch (PDOException $e) {

                $error = "Unable to add product: " . $e->getMessage();

            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Delete Product From Deal
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {

    $deal_product_id = (int) $_GET["delete"];

    try {

        $sql = "
            DELETE FROM deal_products
            WHERE id = :id
            AND deal_id = :deal_id
        ";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ":id" => $deal_product_id,
            ":deal_id" => $deal_id
        ]);

        header("Location: deal_products.php?deal_id=" . $deal_id);
        exit;

    } catch (PDOException $e) {

        $error = "Unable to remove product.";

    }
}


/*
|--------------------------------------------------------------------------
| Get Active Products
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        sku,
        price
    FROM products
    WHERE status = 1
    ORDER BY name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Products Added To Deal
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
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

    ORDER BY deal_products.id ASC
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":deal_id" => $deal_id
]);

$deal_products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Calculate Total
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

    <title>Deal Products</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

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

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card h2 {
            margin-top: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group select,
        .form-group input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
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

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .total-box {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        @media (max-width: 768px) {

            .form-row {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-header">

        <div>

            <h1>Deal Products</h1>

            <p>
                Deal:
                <strong>
                    <?php echo htmlspecialchars($deal["title"]); ?>
                </strong>
                (ID: <?php echo $deal["id"]; ?>)
            </p>

        </div>

        <a href="view.php?id=<?php echo $deal_id; ?>"
           class="btn btn-secondary">
            Back to Deal
        </a>

    </div>


    <?php if ($message): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- Add Product -->

    <div class="card">

        <h2>Add Product</h2>

        <form method="POST">

            <div class="form-row">

                <div class="form-group">

                    <label>Product</label>

                    <select name="product_id" required>

                        <option value="">
                            -- Select Product --
                        </option>

                        <?php foreach ($products as $product): ?>

                            <option value="<?php echo $product["id"]; ?>">

                                <?php echo htmlspecialchars($product["name"]); ?>

                                <?php if (!empty($product["sku"])): ?>

                                    - <?php echo htmlspecialchars($product["sku"]); ?>

                                <?php endif; ?>

                                - ₹<?php echo number_format($product["price"], 2); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Quantity</label>

                    <input
                        type="number"
                        name="quantity"
                        min="0.01"
                        step="0.01"
                        value="1"
                        required
                    >

                </div>


                <div>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        Add Product
                    </button>

                </div>

            </div>

        </form>

    </div>


    <!-- Product List -->

    <div class="card">

        <h2>Products In This Deal</h2>

        <?php if (count($deal_products) > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th>S.No.</th>

                        <th>Product</th>

                        <th>SKU</th>

                        <th>Quantity</th>

                        <th>Unit Price</th>

                        <th>Total</th>

                        <th>Action</th>

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
                                <?php echo htmlspecialchars($item["name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($item["sku"] ?? ""); ?>
                            </td>

                            <td>
                                <?php echo number_format($item["quantity"], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($item["unit_price"], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($item["total"], 2); ?>
                            </td>

                            <td>

                                <a
                                    href="deal_products.php?deal_id=<?php echo $deal_id; ?>&delete=<?php echo $item["id"]; ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to remove this product?');"
                                >
                                    Remove
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>


            <div class="total-box">

                Products Total:
                ₹<?php echo number_format($products_total, 2); ?>

            </div>

        <?php else: ?>

            <p>No products have been added to this deal yet.</p>

        <?php endif; ?>

    </div>

</div>

</body>

</html>