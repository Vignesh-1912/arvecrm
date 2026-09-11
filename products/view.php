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

$sql = "SELECT
            products.*,
            users.name AS created_by_name
        FROM products
        LEFT JOIN users
            ON products.created_by = users.id
        WHERE products.id = :id";

$stmt = $conn->prepare($sql);
$stmt->execute([":id" => $id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>View Product - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .product-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 1000px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .product-header h1 {
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
            vertical-align: middle;
        }

        .details-table th {
            width: 30%;
            background: #f8fafc;
            font-weight: bold;
        }

        .price {
            font-weight: bold;
            font-size: 18px;
        }

        .status-active {
            display: inline-block;
            padding: 5px 10px;
            background: #dcfce7;
            color: #166534;
            border-radius: 15px;
            font-size: 13px;
        }

        .status-inactive {
            display: inline-block;
            padding: 5px 10px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 15px;
            font-size: 13px;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="product-card">

        <div class="product-header">

            <h1>Product Details</h1>

            <div>

                <a
                    href="edit.php?id=<?php echo $product["id"]; ?>"
                    class="btn btn-edit"
                >
                    Edit Product
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
                <th>Product ID</th>
                <td><?php echo $product["id"]; ?></td>
            </tr>

            <tr>
                <th>Product Name</th>
                <td>
                    <?php echo htmlspecialchars($product["name"]); ?>
                </td>
            </tr>

            <tr>
                <th>SKU</th>
                <td>
                    <?php
                    echo $product["sku"]
                        ? htmlspecialchars($product["sku"])
                        : "-";
                    ?>
                </td>
            </tr>

            <tr>
                <th>Type</th>
                <td>
                    <?php echo htmlspecialchars($product["type"]); ?>
                </td>
            </tr>

            <tr>
                <th>Price</th>
                <td class="price">
                    ₹ <?php
                    echo number_format(
                        (float)$product["price"],
                        2
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Tax Rate</th>
                <td>
                    <?php
                    echo number_format(
                        (float)$product["tax_rate"],
                        2
                    );
                    ?>%
                </td>
            </tr>

            <tr>
                <th>Status</th>
                <td>

                    <?php if ($product["status"] == 1): ?>

                        <span class="status-active">
                            Active
                        </span>

                    <?php else: ?>

                        <span class="status-inactive">
                            Inactive
                        </span>

                    <?php endif; ?>

                </td>
            </tr>

            <tr>
                <th>Description</th>
                <td>
                    <?php
                    echo $product["description"]
                        ? nl2br(htmlspecialchars($product["description"]))
                        : "-";
                    ?>
                </td>
            </tr>

            <tr>
                <th>Created By</th>
                <td>
                    <?php
                    echo $product["created_by_name"]
                        ? htmlspecialchars($product["created_by_name"])
                        : "-";
                    ?>
                </td>
            </tr>

            <tr>
                <th>Created At</th>
                <td>
                    <?php echo htmlspecialchars($product["created_at"]); ?>
                </td>
            </tr>

            <tr>
                <th>Updated At</th>
                <td>
                    <?php
                    echo $product["updated_at"]
                        ? htmlspecialchars($product["updated_at"])
                        : "-";
                    ?>
                </td>
            </tr>

        </table>

    </div>

</div>

</body>

</html>