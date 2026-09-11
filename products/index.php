<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$sql = "SELECT
            products.*,
            users.name AS created_by_name
        FROM products
        LEFT JOIN users
            ON products.created_by = users.id
        ORDER BY products.id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Products - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .products-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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

        .add-btn {
            background: #2563eb;
            color: white;
            padding: 11px 18px;
            border-radius: 6px;
            text-decoration: none;
        }

        .products-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .products-table th,
        .products-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .products-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .products-table th:nth-child(1),
        .products-table td:nth-child(1) {
            width: 5%;
        }

        .products-table th:nth-child(2),
        .products-table td:nth-child(2) {
            width: 18%;
        }

        .products-table th:nth-child(3),
        .products-table td:nth-child(3) {
            width: 12%;
        }

        .products-table th:nth-child(4),
        .products-table td:nth-child(4) {
            width: 10%;
        }

        .products-table th:nth-child(5),
        .products-table td:nth-child(5) {
            width: 12%;
            white-space: nowrap;
        }

        .products-table th:nth-child(6),
        .products-table td:nth-child(6) {
            width: 10%;
            white-space: nowrap;
        }

        .products-table th:nth-child(7),
        .products-table td:nth-child(7) {
            width: 10%;
        }

        .products-table th:nth-child(8),
        .products-table td:nth-child(8) {
            width: 13%;
            white-space: nowrap;
        }

        .status-active {
            color: #166534;
            font-weight: bold;
        }

        .status-inactive {
            color: #991b1b;
            font-weight: bold;
        }

        .action-links {
            white-space: nowrap;
        }

        .action-links a {
            text-decoration: none;
            margin-right: 6px;
        }

        .delete {
            color: red;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="products-card">

        <div class="page-header">

            <h1>Products</h1>

            <a href="add.php" class="add-btn">
                + Add Product
            </a>

        </div>


        <div class="products-table-container">

            <table class="products-table">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Tax Rate</th>
                        <th>Status</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($products) > 0): ?>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>
                                    <?php echo $product["id"]; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($product["name"]); ?>
                                </td>

                                <td>
                                    <?php
                                    echo $product["sku"]
                                        ? htmlspecialchars($product["sku"])
                                        : "-";
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($product["type"]); ?>
                                </td>

                                <td>
                                    ₹ <?php
                                    echo number_format(
                                        (float)$product["price"],
                                        2
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo number_format(
                                        (float)$product["tax_rate"],
                                        2
                                    ); ?>%
                                </td>

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

                                <td class="action-links">

                                    <a href="view.php?id=<?php echo $product["id"]; ?>">
                                        View
                                    </a>

                                    |

                                    <a href="edit.php?id=<?php echo $product["id"]; ?>">
                                        Edit
                                    </a>

                                    |

                                    <a
                                        href="delete.php?id=<?php echo $product["id"]; ?>"
                                        class="delete"
                                        onclick="return confirm('Are you sure you want to delete this product?');"
                                    >
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" style="text-align:center;">
                                No products found.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>