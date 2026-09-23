<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";
$message = "";


/*
|--------------------------------------------------------------------------
| Check Deal ID
|--------------------------------------------------------------------------
*/

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
        deals.amount,
        deals.stage,
        deals.probability
    FROM deals
    WHERE deals.id = :deal_id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":deal_id" => $deal_id
]);

$deal = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$deal) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Add Product To Deal
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = isset($_POST["product_id"])
        ? (int) $_POST["product_id"]
        : 0;

    $quantity = isset($_POST["quantity"])
        ? (float) $_POST["quantity"]
        : 0;


    if ($product_id <= 0) {

        $error = "Please select a product.";

    } elseif ($quantity <= 0) {

        $error = "Quantity must be greater than 0.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get Product
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT
                id,
                name,
                sku,
                price
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

                /*
                |--------------------------------------------------------------------------
                | Insert Deal Product
                |--------------------------------------------------------------------------
                */

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

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

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


        header(
            "Location: deal_products.php?deal_id=" .
            $deal_id
        );

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
| Calculate Products Total
|--------------------------------------------------------------------------
*/

$products_total = 0;

foreach ($deal_products as $item) {

    $products_total +=
        (float) $item["total"];
}


$product_count = count($deal_products);


/*
|--------------------------------------------------------------------------
| Stage Labels
|--------------------------------------------------------------------------
*/

$stageLabels = [
    "new" => "New",
    "prospecting" => "Prospecting",
    "qualification" => "Qualification",
    "proposal" => "Proposal",
    "negotiation" => "Negotiation",
    "closed_won" => "Closed Won",
    "closed_lost" => "Closed Lost"
];

$stage = strtolower(
    trim($deal["stage"] ?? "new")
);

$stageLabel =
    $stageLabels[$stage] ??
    ucfirst(
        str_replace(
            "_",
            " ",
            $stage
        )
    );


/*
|--------------------------------------------------------------------------
| Stage CSS Class
|--------------------------------------------------------------------------
*/

$allowedStageClasses = [
    "new",
    "prospecting",
    "qualification",
    "proposal",
    "negotiation",
    "closed_won",
    "closed_lost"
];

$stageClass = in_array(
    $stage,
    $allowedStageClasses,
    true
)
    ? $stage
    : "new";

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
        Deal Products - CRM
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
            background: #f4f6f9;
        }

        .page-wrapper {
            max-width: 1450px;
            margin: 0 auto;
        }

        /* ------------------------------------------------------------
           Breadcrumb
        ------------------------------------------------------------ */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        /* ------------------------------------------------------------
           Page Header
        ------------------------------------------------------------ */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0;
            color: #172554;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-btn {
            background: #ffffff;
            border: 1px solid #dbe2ea;
            color: #334155;
        }

        .back-btn:hover {
            background: #f8fafc;
        }

        .view-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .view-btn:hover {
            background: #1d4ed8;
        }

        /* ------------------------------------------------------------
           Deal Info Bar
        ------------------------------------------------------------ */

        .deal-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 17px;
            margin-bottom: 22px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
        }

        .deal-left {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .deal-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 19px;
            font-weight: 700;
        }

        .deal-info {
            min-width: 0;
        }

        .deal-info strong {
            display: block;
            color: #172554;
            font-size: 15px;
        }

        .deal-info span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
        }

        .deal-meta {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
        }

        .stage-badge {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stage-badge.new {
            background: #e2e8f0;
            color: #475569;
        }

        .stage-badge.prospecting {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .stage-badge.qualification {
            background: #ede9fe;
            color: #6d28d9;
        }

        .stage-badge.proposal {
            background: #ffedd5;
            color: #c2410c;
        }

        .stage-badge.negotiation {
            background: #fef9c3;
            color: #a16207;
        }

        .stage-badge.closed_won {
            background: #dcfce7;
            color: #15803d;
        }

        .stage-badge.closed_lost {
            background: #fee2e2;
            color: #b91c1c;
        }

        .deal-amount {
            text-align: right;
            flex-shrink: 0;
        }

        .deal-amount-label {
            display: block;
            margin-bottom: 4px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .deal-amount-value {
            color: #15803d;
            font-size: 21px;
            font-weight: 700;
        }

        /* ------------------------------------------------------------
           Messages
        ------------------------------------------------------------ */

        .message {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            color: #166534;
            font-size: 14px;
        }

        .error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            font-size: 14px;
        }

        /* ------------------------------------------------------------
           Main Grid
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 310px;
            gap: 22px;
            align-items: start;
        }

        .card {
            padding: 21px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .card:last-child {
            margin-bottom: 0;
        }

        .card-title {
            margin-bottom: 17px;
        }

        .card-title h2 {
            margin: 0;
            color: #172554;
            font-size: 17px;
        }

        .card-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        /* ------------------------------------------------------------
           Add Product Form
        ------------------------------------------------------------ */

        .form-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 150px 120px;
            gap: 14px;
            align-items: end;
        }

        .form-group {
            min-width: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            height: 43px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 43px;
            padding: 0 14px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        .selected-product-info {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 13px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .selected-product-info strong {
            color: #334155;
        }

        /* ------------------------------------------------------------
           Product Table
        ------------------------------------------------------------ */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .products-table th,
        .products-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .products-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .products-table td {
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .products-table tbody tr:hover {
            background: #f8fafc;
        }

        .serial {
            width: 60px;
            color: #64748b;
            font-weight: 600;
        }

        .product-name {
            color: #172554;
            font-weight: 600;
        }

        .product-sku {
            color: #64748b;
        }

        .quantity {
            font-weight: 600;
        }

        .price {
            white-space: nowrap;
        }

        .total {
            color: #15803d;
            font-weight: 700;
            white-space: nowrap;
        }

        .action-cell {
            width: 110px;
            white-space: nowrap;
        }

        .remove-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 7px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .remove-btn:hover {
            background: #fecaca;
        }

        .products-total {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .total-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .total-value {
            color: #15803d;
            font-size: 20px;
            font-weight: 700;
        }

        .no-products {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
            color: #64748b;
            font-size: 13px;
        }

        .no-products-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ------------------------------------------------------------
           Side Cards
        ------------------------------------------------------------ */

        .side-card {
            padding: 20px;
            margin-bottom: 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .side-title {
            margin-bottom: 16px;
        }

        .side-title h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .side-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ------------------------------------------------------------
           Stats
        ------------------------------------------------------------ */

        .stat-item {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .stat-item:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .stat-label {
            display: block;
            margin-bottom: 4px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stat-value {
            display: block;
            color: #334155;
            font-size: 15px;
            font-weight: 700;
        }

        /* ------------------------------------------------------------
           Quick Actions
        ------------------------------------------------------------ */

        .quick-action {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            min-height: 41px;
            padding: 0 11px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .quick-action:last-child {
            margin-bottom: 0;
        }

        .quick-action:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .quick-icon {
            width: 27px;
            height: 27px;
            border-radius: 7px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ------------------------------------------------------------
           Tip
        ------------------------------------------------------------ */

        .tip-box {
            padding: 13px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            color: #1e40af;
            font-size: 12px;
            line-height: 1.55;
        }

        .tip-box strong {
            display: block;
            margin-bottom: 3px;
        }

        /* ------------------------------------------------------------
           Dark Mode
        ------------------------------------------------------------ */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .deal-info strong,
        html.dark-mode .card-title h2,
        html.dark-mode .side-title h3,
        html.dark-mode .stat-value {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .deal-info span,
        html.dark-mode .card-title p,
        html.dark-mode .side-title p,
        html.dark-mode .deal-amount-label {
            color: #94a3b8;
        }

        html.dark-mode .deal-bar,
        html.dark-mode .card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .deal-amount-value {
            color: #86efac;
        }

        html.dark-mode .form-group label {
            color: #e2e8f0;
        }

        html.dark-mode .form-group select,
        html.dark-mode .form-group input {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark-mode .form-group select:focus,
        html.dark-mode .form-group input:focus {
            border-color: #60a5fa;
        }

        html.dark-mode .selected-product-info {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .selected-product-info strong {
            color: #cbd5e1;
        }

        html.dark-mode .selected-product-info {
            color: #64748b;
        }

        html.dark-mode .back-btn {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .back-btn:hover {
            background: #1e293b;
        }

        html.dark-mode .products-table th {
            background: #0f172a;
            color: #64748b;
        }

        html.dark-mode .products-table th,
        html.dark-mode .products-table td,
        html.dark-mode .products-total {
            border-color: #1f2937;
        }

        html.dark-mode .products-table td {
            color: #cbd5e1;
        }

        html.dark-mode .products-table tbody tr:hover {
            background: #1e293b;
        }

        html.dark-mode .product-name {
            color: #f8fafc;
        }

        html.dark-mode .product-sku,
        html.dark-mode .total-label {
            color: #94a3b8;
        }

        html.dark-mode .remove-btn {
            background: #450a0a;
            border-color: #7f1d1d;
            color: #fca5a5;
        }

        html.dark-mode .remove-btn:hover {
            background: #7f1d1d;
        }

        html.dark-mode .total-value {
            color: #86efac;
        }

        html.dark-mode .no-products {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }

        html.dark-mode .no-products-icon {
            background: #1e293b;
        }

        html.dark-mode .stat-item {
            border-color: #1f2937;
        }

        html.dark-mode .stat-label {
            color: #64748b;
        }

        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #1e293b;
        }

        html.dark-mode .quick-icon {
            background: #1e293b;
        }

        html.dark-mode .tip-box {
            background: #172554;
            border-color: #1e3a8a;
            color: #bfdbfe;
        }

        /* ------------------------------------------------------------
           Responsive
        ------------------------------------------------------------ */

        @media (max-width: 1050px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 800px) {

            .page-header {
                flex-direction: column;
            }

            .deal-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .deal-amount {
                text-align: left;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 650px) {

            .main-content {
                padding: 20px;
            }

            .header-actions {
                width: 100%;
            }

            .header-btn {
                flex: 1;
            }

            .deal-left {
                align-items: flex-start;
            }

            .products-total {
                flex-direction: column;
                align-items: flex-end;
                gap: 4px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-wrapper">


        <!-- =========================================================
             Breadcrumb
        ========================================================== -->

        <div class="breadcrumb">

            <a href="index.php">
                Deals
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <a href="view.php?id=<?php echo $deal_id; ?>">
                Deal Details
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                Products
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Deal Products
                </h1>

                <p>
                    Manage products associated with this deal.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="view.php?id=<?php echo $deal_id; ?>"
                    class="header-btn view-btn"
                >
                    👁 View Deal
                </a>

                <a
                    href="index.php"
                    class="header-btn back-btn"
                >
                    ← Deal List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Deal Bar
        ========================================================== -->

        <div class="deal-bar">

            <div class="deal-left">

                <div class="deal-avatar">

                    <?php

                    echo htmlspecialchars(
                        strtoupper(
                            substr(
                                $deal["title"] !== ""
                                    ? $deal["title"]
                                    : "D",
                                0,
                                1
                            )
                        )
                    );

                    ?>

                </div>


                <div class="deal-info">

                    <strong>
                        <?php echo htmlspecialchars($deal["title"]); ?>
                    </strong>

                    <span>

                        Deal #<?php echo (int) $deal["id"]; ?>

                        <span class="deal-meta">

                            <span
                                class="stage-badge <?php echo $stageClass; ?>"
                            >
                                <?php echo htmlspecialchars($stageLabel); ?>
                            </span>

                        </span>

                    </span>

                </div>

            </div>


            <div class="deal-amount">

                <span class="deal-amount-label">
                    Deal Amount
                </span>

                <span class="deal-amount-value">

                    ₹<?php echo number_format(
                        (float) $deal["amount"],
                        2
                    ); ?>

                </span>

            </div>

        </div>


        <?php if ($message !== ""): ?>

            <div class="message">

                <span>
                    ✓
                </span>

                <span>
                    <?php echo htmlspecialchars($message); ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="error">

                <span>
                    ⚠
                </span>

                <span>
                    <?php echo htmlspecialchars($error); ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =========================================================
             Main Grid
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 LEFT
            ====================================================== -->

            <div>


                <!-- Add Product -->

                <div class="card">

                    <div class="card-title">

                        <h2>
                            Add Product
                        </h2>

                        <p>
                            Select an active product and specify the required quantity.
                        </p>

                    </div>


                    <form method="POST">

                        <div class="form-row">


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
                                            value="<?php echo (int) $product["id"]; ?>"
                                            data-price="<?php echo htmlspecialchars($product["price"]); ?>"
                                            data-sku="<?php echo htmlspecialchars($product["sku"] ?? ""); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $product["name"]
                                            );
                                            ?>

                                            <?php if (!empty($product["sku"])): ?>

                                                -
                                                <?php
                                                echo htmlspecialchars(
                                                    $product["sku"]
                                                );
                                                ?>

                                            <?php endif; ?>

                                            -
                                            ₹<?php echo number_format(
                                                (float) $product["price"],
                                                2
                                            ); ?>

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
                                    min="0.01"
                                    step="0.01"
                                    value="1"
                                    required
                                >

                            </div>


                            <div>

                                <button
                                    type="submit"
                                    class="add-btn"
                                >
                                    + Add Product
                                </button>

                            </div>

                        </div>


                        <div
                            class="selected-product-info"
                            id="selectedProductInfo"
                        >

                            <span>
                                Product Price:
                            </span>

                            <strong id="selectedPrice">
                                ₹0.00
                            </strong>

                            <span>
                                |
                            </span>

                            <span>
                                Estimated Total:
                            </span>

                            <strong id="selectedTotal">
                                ₹0.00
                            </strong>

                        </div>

                    </form>

                </div>


                <!-- Product List -->

                <div class="card">

                    <div class="card-title">

                        <h2>
                            Products In This Deal
                        </h2>

                        <p>
                            <?php echo $product_count; ?> product(s) currently added to this deal.
                        </p>

                    </div>


                    <?php if ($product_count > 0): ?>

                        <div class="table-wrapper">

                            <table class="products-table">

                                <thead>

                                    <tr>

                                        <th class="serial">
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
                                            Total
                                        </th>

                                        <th class="action-cell">
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php $serial_no = 1; ?>


                                    <?php foreach ($deal_products as $item): ?>

                                        <tr>


                                            <td class="serial">

                                                <?php
                                                echo $serial_no++;
                                                ?>

                                            </td>


                                            <td class="product-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $item["name"]
                                                );
                                                ?>

                                            </td>


                                            <td class="product-sku">

                                                <?php

                                                echo !empty(
                                                    $item["sku"]
                                                )
                                                    ? htmlspecialchars(
                                                        $item["sku"]
                                                    )
                                                    : "-";

                                                ?>

                                            </td>


                                            <td class="quantity">

                                                <?php
                                                echo number_format(
                                                    (float) $item["quantity"],
                                                    2
                                                );
                                                ?>

                                            </td>


                                            <td class="price">

                                                ₹<?php

                                                echo number_format(
                                                    (float) $item["unit_price"],
                                                    2
                                                );

                                                ?>

                                            </td>


                                            <td class="total">

                                                ₹<?php

                                                echo number_format(
                                                    (float) $item["total"],
                                                    2
                                                );

                                                ?>

                                            </td>


                                            <td class="action-cell">

                                                <a
                                                    href="deal_products.php?deal_id=<?php echo $deal_id; ?>&delete=<?php echo (int) $item["id"]; ?>"
                                                    class="remove-btn"
                                                    onclick="return confirm('Are you sure you want to remove this product from the deal?');"
                                                >
                                                    Remove
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <div class="products-total">

                            <span class="total-label">
                                Products Total
                            </span>

                            <span class="total-value">

                                ₹<?php echo number_format(
                                    $products_total,
                                    2
                                ); ?>

                            </span>

                        </div>

                    <?php else: ?>

                        <div class="no-products">

                            <div class="no-products-icon">
                                📦
                            </div>

                            <span>
                                No products have been added to this deal yet.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDE
            ====================================================== -->

            <div>


                <!-- Product Summary -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Product Summary
                        </h3>

                        <p>
                            Current product information for this deal.
                        </p>

                    </div>


                    <div class="stat-item">

                        <span class="stat-label">
                            Product Count
                        </span>

                        <span class="stat-value">

                            <?php echo $product_count; ?>

                        </span>

                    </div>


                    <div class="stat-item">

                        <span class="stat-label">
                            Products Total
                        </span>

                        <span
                            class="stat-value"
                            style="color:#15803d;font-size:18px;"
                        >

                            ₹<?php echo number_format(
                                $products_total,
                                2
                            ); ?>

                        </span>

                    </div>


                    <div class="stat-item">

                        <span class="stat-label">
                            Deal Amount
                        </span>

                        <span class="stat-value">

                            ₹<?php echo number_format(
                                (float) $deal["amount"],
                                2
                            ); ?>

                        </span>

                    </div>

                </div>


                <!-- Quick Actions -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Quick Actions
                        </h3>

                        <p>
                            Common deal actions.
                        </p>

                    </div>


                    <a
                        href="view.php?id=<?php echo $deal_id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            👁
                        </span>

                        View Deal

                    </a>


                    <a
                        href="edit.php?id=<?php echo $deal_id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✎
                        </span>

                        Edit Deal

                    </a>


                    <a
                        href="index.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ←
                        </span>

                        Back to Deals

                    </a>

                </div>


                <!-- Tip -->

                <div class="side-card">

                    <div class="tip-box">

                        <strong>
                            💡 Quick Tip
                        </strong>

                        Select the product and quantity carefully. The product unit price is taken from the current active product price.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const productSelect =
        document.getElementById("product_id");

    const quantityInput =
        document.getElementById("quantity");

    const selectedPrice =
        document.getElementById("selectedPrice");

    const selectedTotal =
        document.getElementById("selectedTotal");


    function updateProductPreview() {

        let price = 0;

        if (productSelect.value) {

            const selectedOption =
                productSelect.options[
                    productSelect.selectedIndex
                ];

            price =
                parseFloat(
                    selectedOption.dataset.price
                ) || 0;
        }


        let quantity =
            parseFloat(
                quantityInput.value
            ) || 0;


        const total =
            price * quantity;


        selectedPrice.textContent =
            "₹" +
            price.toLocaleString(
                "en-IN",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );


        selectedTotal.textContent =
            "₹" +
            total.toLocaleString(
                "en-IN",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

    }


    productSelect.addEventListener(
        "change",
        updateProductPreview
    );


    quantityInput.addEventListener(
        "input",
        updateProductPreview
    );


    updateProductPreview();

});

</script>

</body>

</html>