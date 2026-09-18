<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$from_date = $_GET["from_date"] ?? "";
$to_date = $_GET["to_date"] ?? "";

$sql = "
    SELECT
        id,
        name,
        sku,
        type,
        description,
        price,
        tax_rate,
        status,
        created_at
    FROM products
    WHERE 1=1
";

$params = [];

if (!empty($from_date)) {
    $sql .= " AND DATE(created_at) >= :from_date";
    $params[":from_date"] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(created_at) <= :to_date";
    $params[":to_date"] = $to_date;
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Products Report</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">
    <link rel="stylesheet" href="/crm/assets/css/reports.css">

    <style>

        .report-container {
            padding: 30px;
        }

        .report-header {
            margin-bottom: 25px;
        }

        .report-header h1 {
            margin-bottom: 5px;
        }

        .report-header p {
            color: #6b7280;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            padding: 10px;
            width: 180px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .filter-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

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

        .btn-primary {
            background: #2563eb !important;
            color: white !important;
        }

        .btn-secondary {
            background: #6b7280 !important;
            color: white !important;
        }

        .btn-success {
            background: #16a34a !important;
            color: white !important;
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
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
        }

        .total-count {
            margin-bottom: 15px;
            color: #374151;
            font-weight: 600;
        }

        .active {
            color: #16a34a;
            font-weight: 600;
        }

        .inactive {
            color: #dc2626;
            font-weight: 600;
        }

        @media (max-width: 700px) {

            .report-container {
                padding: 15px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group input {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="report-container">

        <?php
        $page_title = "Products Report";
        include "../includes/report_header.php";
        ?>

        <div class="filter-box">

            <form method="GET" class="filter-form">

                <div class="form-group">

                    <label>From Date</label>

                    <input
                        type="date"
                        name="from_date"
                        value="<?php echo htmlspecialchars($from_date); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>To Date</label>

                    <input
                        type="date"
                        name="to_date"
                        value="<?php echo htmlspecialchars($to_date); ?>"
                    >

                </div>

                <div class="filter-actions">

                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="products.php" class="btn btn-secondary">
                        Reset
                    </a>

                    <a
                        href="../exports/products_csv.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                        class="btn btn-success"
                    >
                        Export CSV
                    </a>

                    <a
                        href="../exports/products_excel.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                        class="btn btn-success"
                    >
                        Export Excel
                    </a>

                    <a
                        href="../exports/products_pdf.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"
                        class="btn btn-success"
                    >
                        Export PDF
                    </a>

                </div>

            </form>

        </div>

        <div class="table-container">

            <div class="total-count">
                Total Products: <?php echo count($products); ?>
            </div>

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Tax Rate</th>
                        <th>Status</th>
                        <th>Created Date</th>
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
                                <?php echo htmlspecialchars($product["sku"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product["type"] ?? ""); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product["description"] ?? ""); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format(
                                    (float)$product["price"],
                                    2
                                ); ?>
                            </td>

                            <td>
                                <?php echo number_format(
                                    (float)$product["tax_rate"],
                                    2
                                ); ?>%
                            </td>

                            <td>

                                <?php if ($product["status"] == 1): ?>

                                    <span class="active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php echo date(
                                    "d-m-Y",
                                    strtotime($product["created_at"])
                                ); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9" style="text-align:center;">
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
