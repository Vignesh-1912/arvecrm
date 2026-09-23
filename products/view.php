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
| Get Product
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            products.*,
            users.name AS created_by_name
        FROM products
        LEFT JOIN users
            ON products.created_by = users.id
        WHERE products.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

$productName = $product["name"] ?? "Product";
$productSku = $product["sku"] ?? "";
$productType = $product["type"] ?? "product";
$productDescription = $product["description"] ?? "";
$productPrice = (float)($product["price"] ?? 0);
$productTax = (float)($product["tax_rate"] ?? 0);
$productStatus = (int)($product["status"] ?? 0);
$createdBy = $product["created_by_name"] ?? "";
$createdAt = $product["created_at"] ?? "";
$updatedAt = $product["updated_at"] ?? "";

$initial = strtoupper(substr(trim($productName), 0, 1));

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
        <?php echo htmlspecialchars($productName); ?> - Product - CRM
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
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Page Header */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0 0 7px;
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
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid transparent;
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
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #dbe2ea;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .btn-danger {
            background: #ffffff;
            color: #dc2626;
            border-color: #fecaca;
        }

        .btn-danger:hover {
            background: #fef2f2;
        }

        /* Hero */

        .product-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .product-avatar {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            border-radius: 12px;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }

        .hero-info {
            min-width: 0;
        }

        .hero-info h2 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 21px;
            font-weight: 700;
            word-break: break-word;
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

        /* Badges */

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-type {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Summary */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            padding: 17px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .summary-label {
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            word-break: break-word;
        }

        .summary-value.price {
            color: #16a34a;
        }

        /* Main Grid */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Cards */

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .card-header {
            padding: 19px 21px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
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

        /* Information Grid */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
        }

        .info-item {
            padding: 15px 14px;
            border-bottom: 1px solid #eef2f7;
        }

        .info-item:nth-last-child(-n + 2) {
            border-bottom: none;
        }

        .info-label {
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .info-value {
            color: #1e293b;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-value strong {
            color: #0f172a;
            font-weight: 600;
        }

        .info-value a {
            color: #2563eb;
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        /* Description */

        .description-box {
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
            white-space: normal;
            word-break: break-word;
        }

        .empty-description {
            color: #94a3b8;
            font-style: italic;
        }

        /* Side Cards */

        .side-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .side-card h3 {
            margin: 0 0 15px;
            color: #0f172a;
            font-size: 15px;
        }

        /* Price Box */

        .price-box {
            padding: 18px;
            background: linear-gradient(
                135deg,
                #eff6ff,
                #f8fafc
            );
            border: 1px solid #dbeafe;
            border-radius: 10px;
        }

        .price-box-label {
            margin-bottom: 5px;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
        }

        .price-box-value {
            color: #15803d;
            font-size: 25px;
            font-weight: 700;
        }

        .tax-info {
            margin-top: 7px;
            color: #64748b;
            font-size: 12px;
        }

        /* Detail Rows */

        .side-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 12px;
        }

        .side-row:last-child {
            border-bottom: none;
        }

        .side-row span:first-child {
            color: #64748b;
        }

        .side-row strong {
            color: #1e293b;
            text-align: right;
            word-break: break-word;
        }

        /* Quick Actions */

        .quick-actions {
            display: grid;
            gap: 9px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 11px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s;
        }

        .quick-action:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .quick-icon {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 26px;
            font-size: 12px;
        }

        /* Timeline */

        .timeline-item {
            position: relative;
            padding-left: 23px;
            padding-bottom: 17px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 5px;
            top: 6px;
            bottom: -1px;
            width: 1px;
            background: #dbe2ea;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 2px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #2563eb;
            border: 2px solid #dbeafe;
        }

        .timeline-title {
            margin-bottom: 4px;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }

        .timeline-date {
            color: #94a3b8;
            font-size: 11px;
        }

        /* Bottom Actions */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding: 18px 0 0;
            border-top: 1px solid #e5e7eb;
        }

        /* Dark Mode */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .product-hero h2,
        html.dark-mode .summary-value,
        html.dark-mode .card-header h3,
        html.dark-mode .side-card h3,
        html.dark-mode .info-value,
        html.dark-mode .info-value strong,
        html.dark-mode .side-row strong,
        html.dark-mode .timeline-title {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .card-header p,
        html.dark-mode .summary-label,
        html.dark-mode .info-label,
        html.dark-mode .side-row span:first-child,
        html.dark-mode .timeline-date,
        html.dark-mode .tax-info {
            color: #94a3b8;
        }

        html.dark-mode .product-hero,
        html.dark-mode .summary-card,
        html.dark-mode .card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .info-item,
        html.dark-mode .side-row,
        html.dark-mode .bottom-actions {
            border-color: #1f2937;
        }

        html.dark-mode .description-box,
        html.dark-mode .quick-action {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .description-box {
            color: #cbd5e1;
        }

        html.dark-mode .quick-action {
            color: #cbd5e1;
        }

        html.dark-mode .quick-action:hover {
            background: #172554;
            border-color: #1e40af;
            color: #93c5fd;
        }

        html.dark-mode .quick-icon {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .price-box {
            background: linear-gradient(
                135deg,
                #172554,
                #111827
            );
            border-color: #1e3a8a;
        }

        html.dark-mode .btn-secondary {
            background: #111827;
            color: #cbd5e1;
            border-color: #334155;
        }

        html.dark-mode .btn-secondary:hover {
            background: #1e293b;
        }

        html.dark-mode .timeline-item::before {
            background: #334155;
        }

        /* Responsive */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .side-column {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 768px) {

            .main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .product-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-left {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .side-column {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item:nth-last-child(-n + 2) {
                border-bottom: 1px solid #eef2f7;
            }

            .info-item:last-child {
                border-bottom: none;
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

            .product-avatar {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
            }

            .hero-info h2 {
                font-size: 18px;
            }

            .card-body,
            .side-card {
                padding: 16px;
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
            Products
        </a>

        <span>/</span>

        <span>
            View Product
        </span>

    </div>

    <!-- Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                Product Details
            </h1>

            <p>
                View the complete information for this product or service.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="edit.php?id=<?php echo $id; ?>"
                class="btn btn-primary"
            >
                ✎ Edit Product
            </a>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Product List
            </a>

        </div>

    </div>

    <!-- Hero -->

    <div class="product-hero">

        <div class="hero-left">

            <div class="product-avatar">
                <?php echo htmlspecialchars($initial); ?>
            </div>

            <div class="hero-info">

                <h2>
                    <?php echo htmlspecialchars($productName); ?>
                </h2>

                <div class="hero-meta">

                    <span>
                        Product ID #<?php echo $id; ?>
                    </span>

                    <span class="meta-separator">•</span>

                    <?php if ($productSku !== ""): ?>

                        <span>
                            SKU: <?php echo htmlspecialchars($productSku); ?>
                        </span>

                    <?php else: ?>

                        <span>
                            SKU not provided
                        </span>

                    <?php endif; ?>

                    <span class="meta-separator">•</span>

                    <span class="badge badge-type">
                        <?php echo ucfirst(htmlspecialchars($productType)); ?>
                    </span>

                    <?php if ($productStatus === 1): ?>

                        <span class="badge badge-active">
                            Active
                        </span>

                    <?php else: ?>

                        <span class="badge badge-inactive">
                            Inactive
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <!-- Summary -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Product ID
            </div>

            <div class="summary-value">
                #<?php echo $id; ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Type
            </div>

            <div class="summary-value">
                <?php echo ucfirst(htmlspecialchars($productType)); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Price
            </div>

            <div class="summary-value price">
                ₹ <?php echo number_format($productPrice, 2); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Tax Rate
            </div>

            <div class="summary-value">
                <?php echo number_format($productTax, 2); ?>%
            </div>

        </div>

    </div>

    <!-- Content -->

    <div class="content-grid">

        <!-- Main Column -->

        <div>

            <!-- Product Information -->

            <div class="card">

                <div class="card-header">

                    <h3>
                        Product Information
                    </h3>

                    <p>
                        Basic details and business information.
                    </p>

                </div>

                <div class="card-body">

                    <div class="info-grid">

                        <div class="info-item">

                            <div class="info-label">
                                Product ID
                            </div>

                            <div class="info-value">
                                <strong>
                                    #<?php echo $id; ?>
                                </strong>
                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Product Name
                            </div>

                            <div class="info-value">
                                <?php echo htmlspecialchars($productName); ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                SKU
                            </div>

                            <div class="info-value">

                                <?php if ($productSku !== ""): ?>

                                    <?php echo htmlspecialchars($productSku); ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not provided
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Type
                            </div>

                            <div class="info-value">
                                <?php echo ucfirst(htmlspecialchars($productType)); ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Price
                            </div>

                            <div class="info-value">

                                <strong style="color:#16a34a;">
                                    ₹ <?php echo number_format($productPrice, 2); ?>
                                </strong>

                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Tax Rate
                            </div>

                            <div class="info-value">
                                <?php echo number_format($productTax, 2); ?>%
                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Status
                            </div>

                            <div class="info-value">

                                <?php if ($productStatus === 1): ?>

                                    <span class="badge badge-active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Created By
                            </div>

                            <div class="info-value">

                                <?php if ($createdBy !== ""): ?>

                                    <?php echo htmlspecialchars($createdBy); ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not available
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Description -->

            <div class="card" style="margin-top:20px;">

                <div class="card-header">

                    <h3>
                        Description
                    </h3>

                    <p>
                        Product or service description.
                    </p>

                </div>

                <div class="card-body">

                    <div class="description-box">

                        <?php if ($productDescription !== ""): ?>

                            <?php
                            echo nl2br(
                                htmlspecialchars($productDescription)
                            );
                            ?>

                        <?php else: ?>

                            <span class="empty-description">
                                No description has been added for this product.
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- Record Information -->

            <div class="card" style="margin-top:20px;">

                <div class="card-header">

                    <h3>
                        Record Information
                    </h3>

                    <p>
                        Creation and last update information.
                    </p>

                </div>

                <div class="card-body">

                    <div class="info-grid">

                        <div class="info-item">

                            <div class="info-label">
                                Created At
                            </div>

                            <div class="info-value">
                                <?php
                                echo $createdAt !== ""
                                    ? htmlspecialchars($createdAt)
                                    : "-";
                                ?>
                            </div>

                        </div>

                        <div class="info-item">

                            <div class="info-label">
                                Updated At
                            </div>

                            <div class="info-value">
                                <?php
                                echo $updatedAt !== ""
                                    ? htmlspecialchars($updatedAt)
                                    : "-";
                                ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Sidebar -->

        <div class="side-column">

            <!-- Product Value -->

            <div class="side-card">

                <h3>
                    Product Value
                </h3>

                <div class="price-box">

                    <div class="price-box-label">
                        Selling Price
                    </div>

                    <div class="price-box-value">
                        ₹ <?php echo number_format($productPrice, 2); ?>
                    </div>

                    <div class="tax-info">
                        Tax Rate:
                        <?php echo number_format($productTax, 2); ?>%
                    </div>

                </div>

            </div>

            <!-- Product Summary -->

            <div class="side-card">

                <h3>
                    Product Summary
                </h3>

                <div class="side-row">

                    <span>
                        Product ID
                    </span>

                    <strong>
                        #<?php echo $id; ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Type
                    </span>

                    <strong>
                        <?php echo ucfirst(htmlspecialchars($productType)); ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Status
                    </span>

                    <strong>
                        <?php echo $productStatus === 1 ? "Active" : "Inactive"; ?>
                    </strong>

                </div>

                <div class="side-row">

                    <span>
                        Created By
                    </span>

                    <strong>
                        <?php
                        echo $createdBy !== ""
                            ? htmlspecialchars($createdBy)
                            : "-";
                        ?>
                    </strong>

                </div>

            </div>

            <!-- Quick Actions -->

            <div class="side-card">

                <h3>
                    Quick Actions
                </h3>

                <div class="quick-actions">

                    <a
                        href="edit.php?id=<?php echo $id; ?>"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✎
                        </span>

                        Edit Product

                    </a>

                    <a
                        href="index.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ☷
                        </span>

                        Product List

                    </a>

                    <a
                        href="add.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            +
                        </span>

                        Add New Product

                    </a>

                </div>

            </div>

            <!-- Timeline -->

            <div class="side-card">

                <h3>
                    Record Timeline
                </h3>

                <div class="timeline-item">

                    <span class="timeline-dot"></span>

                    <div class="timeline-title">
                        Product Created
                    </div>

                    <div class="timeline-date">
                        <?php
                        echo $createdAt !== ""
                            ? htmlspecialchars($createdAt)
                            : "Date unavailable";
                        ?>
                    </div>

                </div>

                <div class="timeline-item">

                    <span class="timeline-dot"></span>

                    <div class="timeline-title">
                        Last Updated
                    </div>

                    <div class="timeline-date">
                        <?php
                        echo $updatedAt !== ""
                            ? htmlspecialchars($updatedAt)
                            : "No update date available";
                        ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Bottom Actions -->

    <div class="bottom-actions">

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← Back to Products
        </a>

        <a
            href="edit.php?id=<?php echo $id; ?>"
            class="btn btn-primary"
        >
            ✎ Edit Product
        </a>

    </div>

</div>

</body>

</html>