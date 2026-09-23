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
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Recalculate Sale Totals
|--------------------------------------------------------------------------
*/

function recalculateSaleTotals(PDO $conn, int $sale_id)
{
    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(quantity * unit_price), 0) AS subtotal,
            COALESCE(SUM(discount), 0) AS discount_amount,
            COALESCE(SUM(tax), 0) AS tax_amount,
            COALESCE(SUM(total), 0) AS total_amount
        FROM sale_items
        WHERE sale_id = :sale_id
    ");

    $stmt->execute([
        ":sale_id" => $sale_id
    ]);

    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    $subtotal = (float) ($totals["subtotal"] ?? 0);
    $discount_amount = (float) ($totals["discount_amount"] ?? 0);
    $tax_amount = (float) ($totals["tax_amount"] ?? 0);
    $total_amount = (float) ($totals["total_amount"] ?? 0);

    if ($total_amount < 0) {
        $total_amount = 0;
    }

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
        ":subtotal" => $subtotal,
        ":discount_amount" => $discount_amount,
        ":tax_amount" => $tax_amount,
        ":total_amount" => $total_amount,
        ":id" => $sale_id
    ]);
}

/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = !empty($_POST["product_id"])
        ? (int) $_POST["product_id"]
        : 0;

    $quantity = is_numeric($_POST["quantity"] ?? "")
        ? (float) $_POST["quantity"]
        : 0;

    $discount = is_numeric($_POST["discount"] ?? "")
        ? (float) $_POST["discount"]
        : 0;

    $tax = is_numeric($_POST["tax"] ?? "")
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
            | Get Active Product
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

                $error = "Selected product was not found or is inactive.";

            } else {

                $unit_price = (float) $product["price"];

                $subtotal = $quantity * $unit_price;

                $total =
                    $subtotal
                    - $discount
                    + $tax;

                if ($total < 0) {
                    $total = 0;
                }

                /*
                |--------------------------------------------------------------------------
                | Insert Sale Item
                |--------------------------------------------------------------------------
                */

                $description = "";

                if (!empty($product["description"])) {
                    $description = $product["description"];
                } else {
                    $description = $product["name"];
                }

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
                    ":description" => $description,
                    ":quantity" => $quantity,
                    ":unit_price" => $unit_price,
                    ":discount" => $discount,
                    ":tax" => $tax,
                    ":total" => $total
                ]);

                /*
                |--------------------------------------------------------------------------
                | Recalculate Sale
                |--------------------------------------------------------------------------
                */

                recalculateSaleTotals(
                    $conn,
                    $sale_id
                );

                header(
                    "Location: sale_items.php?sale_id="
                    . $sale_id
                );

                exit;
            }

        } catch (PDOException $e) {

            $error = "Unable to add product. Please try again.";
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

        recalculateSaleTotals(
            $conn,
            $sale_id
        );

        header(
            "Location: sale_items.php?sale_id="
            . $sale_id
        );

        exit;

    } catch (PDOException $e) {

        $error = "Unable to remove product. Please try again.";
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

$subtotal = (float) ($sale_totals["subtotal"] ?? 0);
$discount_amount = (float) ($sale_totals["discount_amount"] ?? 0);
$tax_amount = (float) ($sale_totals["tax_amount"] ?? 0);
$total_amount = (float) ($sale_totals["total_amount"] ?? 0);

$sale_number = $sale["sale_number"] ?? "Sale";

$initial = "S";

if (trim($sale_number) !== "") {
    $initial = strtoupper(
        substr(
            trim($sale_number),
            0,
            1
        )
    );
}

/*
|--------------------------------------------------------------------------
| Calculate Item Display Totals
|--------------------------------------------------------------------------
*/

$items_subtotal = 0;
$items_discount = 0;
$items_tax = 0;
$items_total = 0;

foreach ($sale_items as $item) {

    $items_subtotal +=
        (float) $item["quantity"]
        * (float) $item["unit_price"];

    $items_discount +=
        (float) $item["discount"];

    $items_tax +=
        (float) $item["tax"];

    $items_total +=
        (float) $item["total"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Sale Products - CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .main-content {
            padding: 28px 32px;
        }

        /* Breadcrumb */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Header */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .page-title h1 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 27px;
            font-weight: 700;
        }

        .page-title p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Buttons */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #dbe2ea;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        /* Hero */

        .sale-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .sale-avatar {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #2563eb;
            color: #ffffff;
            font-size: 21px;
            font-weight: 700;
        }

        .hero-info {
            min-width: 0;
        }

        .hero-info h2 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 19px;
            font-weight: 700;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .meta-separator {
            color: #cbd5e1;
        }

        /* Cards */

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .card + .card {
            margin-top: 20px;
        }

        .card-header {
            padding: 20px 21px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 16px;
        }

        .card-header p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .card-body {
            padding: 21px;
        }

        /* Add Product */

        .form-grid {
            display: grid;
            grid-template-columns:
                minmax(260px, 2fr)
                minmax(110px, 0.8fr)
                minmax(110px, 0.8fr)
                minmax(110px, 0.8fr)
                150px;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            min-width: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            height: 42px;
            padding: 10px 12px;
            border: 1px solid #cfd8e3;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            outline: none;
            font-size: 13px;
            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .form-help {
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* Error */

        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 13px;
        }

        /* Live Preview */

        .product-preview {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 17px;
            padding: 14px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f8fafc;
        }

        .preview-label {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .preview-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            word-break: break-word;
        }

        .preview-total {
            color: #16a34a;
            font-size: 16px;
            font-weight: 700;
        }

        /* Items */

        .items-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .items-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            padding: 12px 11px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: middle;
            font-size: 12px;
        }

        .items-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .items-table th:nth-child(1),
        .items-table td:nth-child(1) {
            width: 55px;
            text-align: center;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            width: 19%;
        }

        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            width: 12%;
        }

        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            width: 18%;
        }

        .items-table th:nth-child(5),
        .items-table td:nth-child(5) {
            width: 9%;
        }

        .items-table th:nth-child(6),
        .items-table td:nth-child(6) {
            width: 10%;
        }

        .items-table th:nth-child(7),
        .items-table td:nth-child(7) {
            width: 9%;
        }

        .items-table th:nth-child(8),
        .items-table td:nth-child(8) {
            width: 9%;
        }

        .items-table th:nth-child(9),
        .items-table td:nth-child(9) {
            width: 10%;
        }

        .items-table th:nth-child(10),
        .items-table td:nth-child(10) {
            width: 10%;
            text-align: center;
        }

        .product-name {
            color: #1e293b;
            font-weight: 600;
        }

        .sku-text {
            color: #64748b;
        }

        .amount {
            color: #1e293b;
            font-weight: 600;
            white-space: nowrap;
        }

        .item-total {
            color: #16a34a;
            font-weight: 700;
            white-space: nowrap;
        }

        .action-cell {
            text-align: center !important;
            white-space: nowrap;
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border: 1px solid #fecaca;
            border-radius: 7px;
            background: #ffffff;
            color: #dc2626;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: #fef2f2;
        }

        /* Summary */

        .summary-area {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px;
            padding: 20px 21px;
        }

        .summary-note {
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .summary {
            width: 100%;
            max-width: 360px;
            margin-left: auto;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
            color: #475569;
            font-size: 13px;
        }

        .summary-row span:last-child {
            color: #1e293b;
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-row.total {
            padding-top: 15px;
            border-bottom: none;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
        }

        .summary-row.total span:last-child {
            color: #16a34a;
            font-size: 20px;
        }

        /* Empty */

        .empty-box {
            padding: 35px 20px;
            margin: 20px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            text-align: center;
        }

        .empty-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            border-radius: 10px;
            background: #dbeafe;
            color: #2563eb;
            font-size: 20px;
            font-weight: 700;
        }

        .empty-title {
            margin-bottom: 5px;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        .empty-text {
            color: #94a3b8;
            font-size: 12px;
        }

        /* Bottom */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
        }

        /* Dark Mode */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .hero-info h2,
        html.dark-mode .card-header h2,
        html.dark-mode .items-table th,
        html.dark-mode .items-table td,
        html.dark-mode .product-name,
        html.dark-mode .amount,
        html.dark-mode .preview-value,
        html.dark-mode .summary-row.total,
        html.dark-mode .empty-title {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .hero-meta,
        html.dark-mode .card-header p,
        html.dark-mode .form-help,
        html.dark-mode .preview-label,
        html.dark-mode .sku-text,
        html.dark-mode .summary-row,
        html.dark-mode .summary-note,
        html.dark-mode .empty-text {
            color: #94a3b8;
        }

        html.dark-mode .sale-hero,
        html.dark-mode .card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .form-control {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark-mode .form-control:focus {
            border-color: #60a5fa;
            box-shadow:
                0 0 0 3px rgba(96, 165, 250, 0.10);
        }

        html.dark-mode .card-header,
        html.dark-mode .items-table th,
        html.dark-mode .items-table td,
        html.dark-mode .summary-row,
        html.dark-mode .bottom-actions {
            border-color: #1f2937;
        }

        html.dark-mode .items-table th {
            background: #0f172a;
        }

        html.dark-mode .items-table td {
            background: #111827;
        }

        html.dark-mode .product-preview,
        html.dark-mode .summary-note,
        html.dark-mode .empty-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .delete-btn {
            background: #111827;
            border-color: #7f1d1d;
        }

        html.dark-mode .delete-btn:hover {
            background: #450a0a;
        }

        html.dark-mode .btn-secondary {
            background: #111827;
            color: #cbd5e1;
            border-color: #334155;
        }

        html.dark-mode .btn-secondary:hover {
            background: #1e293b;
        }

        /* Responsive */

        @media (max-width: 1150px) {

            .form-grid {
                grid-template-columns:
                    2fr 1fr 1fr 1fr;
            }

            .add-button-wrap {
                grid-column: 1 / -1;
            }

            .summary-area {
                grid-template-columns: 1fr;
            }

            .summary {
                margin-left: 0;
            }

        }

        @media (max-width: 900px) {

            .main-content {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .product-field {
                grid-column: 1 / -1;
            }

            .add-button-wrap {
                grid-column: 1 / -1;
            }

            .product-preview {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 650px) {

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .sale-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .product-field,
            .add-button-wrap {
                grid-column: auto;
            }

            .product-preview {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 480px) {

            .main-content {
                padding: 15px;
            }

            .header-actions {
                flex-direction: column;
            }

            .header-actions .btn {
                width: 100%;
            }

            .card-body,
            .card-header {
                padding: 16px;
            }

            .items-table {
                min-width: 1000px;
            }

            .items-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .bottom-actions {
                flex-direction: column;
            }

            .bottom-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- Breadcrumb -->

    <div class="breadcrumb">

        <a href="../dashboard/index.php">
            Dashboard
        </a>

        <span>/</span>

        <a href="index.php">
            Sales
        </a>

        <span>/</span>

        <a href="view.php?id=<?php echo $sale_id; ?>">
            <?php echo htmlspecialchars($sale_number); ?>
        </a>

        <span>/</span>

        <span>
            Sale Products
        </span>

    </div>

    <!-- Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Sale Products
            </h1>

            <p>
                Add, review and remove products included in this sale.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="view.php?id=<?php echo $sale_id; ?>"
                class="btn btn-secondary"
            >
                ← Back to Sale
            </a>

            <a
                href="edit.php?id=<?php echo $sale_id; ?>"
                class="btn btn-secondary"
            >
                ✎ Edit Sale
            </a>

        </div>

    </div>

    <!-- Sale Hero -->

    <div class="sale-hero">

        <div class="hero-left">

            <div class="sale-avatar">
                <?php echo htmlspecialchars($initial); ?>
            </div>

            <div class="hero-info">

                <h2>
                    <?php echo htmlspecialchars($sale_number); ?>
                </h2>

                <div class="hero-meta">

                    <span>
                        Sale ID #<?php echo $sale_id; ?>
                    </span>

                    <span class="meta-separator">
                        •
                    </span>

                    <span>
                        Product Count:
                        <?php echo count($sale_items); ?>
                    </span>

                </div>

            </div>

        </div>

    </div>

    <?php if ($error !== ""): ?>

        <div class="error-box">

            <span>
                ⚠️
            </span>

            <div>
                <?php echo htmlspecialchars($error); ?>
            </div>

        </div>

    <?php endif; ?>

    <!-- Add Product -->

    <div class="card">

        <div class="card-header">

            <h2>
                Add Product
            </h2>

            <p>
                Select an active product and enter quantity, discount and tax.
            </p>

        </div>

        <div class="card-body">

            <form method="POST">

                <div class="form-grid">

                    <!-- Product -->

                    <div class="form-group product-field">

                        <label for="product_id">
                            Product
                        </label>

                        <select
                            id="product_id"
                            name="product_id"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                data-price="0"
                                data-name=""
                            >
                                -- Select Product --
                            </option>

                            <?php foreach ($products as $product): ?>

                                <option
                                    value="<?php echo (int)$product["id"]; ?>"
                                    data-price="<?php echo htmlspecialchars($product["price"]); ?>"
                                    data-name="<?php echo htmlspecialchars($product["name"]); ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $product["name"]
                                    );

                                    if (!empty($product["sku"])) {

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

                        <div class="form-help">
                            Only active products are available.
                        </div>

                    </div>

                    <!-- Quantity -->

                    <div class="form-group">

                        <label for="quantity">
                            Quantity
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            class="form-control"
                            value="1"
                            min="0.01"
                            step="0.01"
                            required
                        >

                    </div>

                    <!-- Discount -->

                    <div class="form-group">

                        <label for="discount">
                            Discount
                        </label>

                        <input
                            type="number"
                            id="discount"
                            name="discount"
                            class="form-control"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>

                    <!-- Tax -->

                    <div class="form-group">

                        <label for="tax">
                            Tax
                        </label>

                        <input
                            type="number"
                            id="tax"
                            name="tax"
                            class="form-control"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>

                    <!-- Button -->

                    <div class="form-group add-button-wrap">

                        <button
                            type="submit"
                            class="btn btn-success"
                            style="width:100%;"
                        >
                            + Add Product
                        </button>

                    </div>

                </div>

                <!-- Live Preview -->

                <div class="product-preview">

                    <div>

                        <span class="preview-label">
                            Selected Product
                        </span>

                        <div
                            class="preview-value"
                            id="previewProduct"
                        >
                            No product selected
                        </div>

                    </div>

                    <div>

                        <span class="preview-label">
                            Unit Price
                        </span>

                        <div
                            class="preview-value"
                            id="previewUnitPrice"
                        >
                            ₹ 0.00
                        </div>

                    </div>

                    <div>

                        <span class="preview-label">
                            Quantity
                        </span>

                        <div
                            class="preview-value"
                            id="previewQuantity"
                        >
                            0.00
                        </div>

                    </div>

                    <div>

                        <span class="preview-label">
                            Estimated Total
                        </span>

                        <div
                            class="preview-total"
                            id="previewTotal"
                        >
                            ₹ 0.00
                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- Product List -->

    <div class="card">

        <div class="card-header">

            <h2>
                Products In This Sale
            </h2>

            <p>
                <?php echo count($sale_items); ?>

                product<?php

                if (count($sale_items) !== 1) {
                    echo "s";
                }

                ?>

                currently added to this sale.
            </p>

        </div>

        <div class="card-body">

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

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php $serial_no = 1; ?>

                            <?php foreach ($sale_items as $item): ?>

                                <tr>

                                    <td>
                                        <?php echo $serial_no; ?>
                                    </td>

                                    <td>

                                        <div class="product-name">

                                            <?php

                                            if (!empty($item["product_name"])) {

                                                echo htmlspecialchars(
                                                    $item["product_name"]
                                                );

                                            } elseif (!empty($item["description"])) {

                                                echo htmlspecialchars(
                                                    $item["description"]
                                                );

                                            } else {

                                                echo "-";
                                            }

                                            ?>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="sku-text">

                                            <?php

                                            if (!empty($item["sku"])) {

                                                echo htmlspecialchars(
                                                    $item["sku"]
                                                );

                                            } else {

                                                echo "-";
                                            }

                                            ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php

                                        if (!empty($item["description"])) {

                                            echo nl2br(
                                                htmlspecialchars(
                                                    $item["description"]
                                                )
                                            );

                                        } else {

                                            echo "-";
                                        }

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

                                    <td class="amount">

                                        ₹ <?php

                                        echo number_format(
                                            (float)$item["discount"],
                                            2
                                        );

                                        ?>

                                    </td>

                                    <td class="amount">

                                        ₹ <?php

                                        echo number_format(
                                            (float)$item["tax"],
                                            2
                                        );

                                        ?>

                                    </td>

                                    <td class="item-total">

                                        ₹ <?php

                                        echo number_format(
                                            (float)$item["total"],
                                            2
                                        );

                                        ?>

                                    </td>

                                    <td class="action-cell">

                                        <a
                                            href="sale_items.php?<?php
                                                echo "sale_id="
                                                    . $sale_id
                                                    . "&delete="
                                                    . (int)$item["id"];
                                            ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to remove this product from the sale?');"
                                        >
                                            Delete
                                        </a>

                                    </td>

                                </tr>

                                <?php $serial_no++; ?>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- Summary -->

                <div class="summary-area">

                    <div class="summary-note">

                        Sale totals are recalculated automatically whenever a product is added or removed.

                    </div>

                    <div class="summary">

                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <span>
                                ₹ <?php
                                echo number_format(
                                    $subtotal,
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
                                    $discount_amount,
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
                                    $tax_amount,
                                    2
                                );
                                ?>
                            </span>

                        </div>

                        <div class="summary-row total">

                            <span>
                                Grand Total
                            </span>

                            <span>
                                ₹ <?php
                                echo number_format(
                                    $total_amount,
                                    2
                                );
                                ?>
                            </span>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="empty-box">

                    <div class="empty-icon">
                        +
                    </div>

                    <div class="empty-title">
                        No products added
                    </div>

                    <div class="empty-text">
                        Select an active product above to add the first item to this sale.
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- Bottom Actions -->

    <div class="bottom-actions">

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← Sales List
        </a>

        <a
            href="view.php?id=<?php echo $sale_id; ?>"
            class="btn btn-secondary"
        >
            View Sale
        </a>

        <a
            href="edit.php?id=<?php echo $sale_id; ?>"
            class="btn btn-primary"
        >
            ✎ Edit Sale
        </a>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const productInput =
        document.getElementById("product_id");

    const quantityInput =
        document.getElementById("quantity");

    const discountInput =
        document.getElementById("discount");

    const taxInput =
        document.getElementById("tax");

    const previewProduct =
        document.getElementById("previewProduct");

    const previewUnitPrice =
        document.getElementById("previewUnitPrice");

    const previewQuantity =
        document.getElementById("previewQuantity");

    const previewTotal =
        document.getElementById("previewTotal");

    function formatAmount(value) {

        const number = parseFloat(value);

        if (isNaN(number)) {
            return "0.00";
        }

        return number.toFixed(2);
    }

    function updatePreview() {

        let unitPrice = 0;
        let productName = "No product selected";

        const selectedOption =
            productInput.options[
                productInput.selectedIndex
            ];

        if (
            selectedOption &&
            selectedOption.value !== ""
        ) {

            unitPrice = parseFloat(
                selectedOption.getAttribute(
                    "data-price"
                )
            );

            if (isNaN(unitPrice)) {
                unitPrice = 0;
            }

            productName =
                selectedOption.getAttribute(
                    "data-name"
                );

            if (!productName) {
                productName =
                    selectedOption.textContent.trim();
            }
        }

        const quantity =
            parseFloat(
                quantityInput.value
            ) || 0;

        const discount =
            parseFloat(
                discountInput.value
            ) || 0;

        const tax =
            parseFloat(
                taxInput.value
            ) || 0;

        let total =
            (unitPrice * quantity)
            - discount
            + tax;

        if (total < 0) {
            total = 0;
        }

        previewProduct.textContent =
            productName;

        previewUnitPrice.textContent =
            "₹ " + formatAmount(unitPrice);

        previewQuantity.textContent =
            formatAmount(quantity);

        previewTotal.textContent =
            "₹ " + formatAmount(total);

    }

    productInput.addEventListener(
        "change",
        updatePreview
    );

    quantityInput.addEventListener(
        "input",
        updatePreview
    );

    discountInput.addEventListener(
        "input",
        updatePreview
    );

    taxInput.addEventListener(
        "input",
        updatePreview
    );

    updatePreview();

});

</script>

</body>

</html>