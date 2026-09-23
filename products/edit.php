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

$error = "";

/*
|--------------------------------------------------------------------------
| Get Existing Product
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM products WHERE id = :id";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id" => $id
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Update Product
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $sku = trim($_POST["sku"] ?? "");
    $type = trim($_POST["type"] ?? "product");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "0");
    $tax_rate = trim($_POST["tax_rate"] ?? "0");
    $status = isset($_POST["status"]) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === "") {

        $error = "Product name is required.";

    } elseif (!is_numeric($price) || (float)$price < 0) {

        $error = "Price must be a valid non-negative number.";

    } elseif (!is_numeric($tax_rate)) {

        $error = "Tax rate must be a valid number.";

    } elseif ((float)$tax_rate < 0 || (float)$tax_rate > 100) {

        $error = "Tax rate must be between 0 and 100.";

    } elseif (!in_array($type, ["product", "service"], true)) {

        $error = "Invalid product type.";

    } else {

        try {

            $sql = "UPDATE products SET
                        name = :name,
                        sku = :sku,
                        type = :type,
                        description = :description,
                        price = :price,
                        tax_rate = :tax_rate,
                        status = :status
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":name" => $name,
                ":sku" => $sku !== "" ? $sku : null,
                ":type" => $type,
                ":description" => $description !== "" ? $description : null,
                ":price" => (float)$price,
                ":tax_rate" => (float)$tax_rate,
                ":status" => $status,
                ":id" => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error = "SKU already exists. Please use a different SKU.";

            } else {

                $error = "Unable to update product. Please try again.";

            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Keep Entered Values After Validation Error
    |--------------------------------------------------------------------------
    */

    $product["name"] = $name;
    $product["sku"] = $sku;
    $product["type"] = $type;
    $product["description"] = $description;
    $product["price"] = $price;
    $product["tax_rate"] = $tax_rate;
    $product["status"] = $status;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

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

        /* Header */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }

        .page-title h1 {
            margin: 0 0 6px;
            font-size: 27px;
            font-weight: 700;
            color: #0f172a;
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
            padding: 10px 16px;
            min-height: 40px;
            border: none;
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
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #dbe2ea;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        /* Current Product Bar */

        .record-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 18px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .record-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .record-avatar {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
        }

        .record-text {
            min-width: 0;
        }

        .record-text strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .record-text span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
        }

        .record-status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Summary Cards */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .summary-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 7px;
        }

        .summary-value {
            color: #0f172a;
            font-size: 17px;
            font-weight: 700;
        }

        /* Layout */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Form Card */

        .form-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0 0 5px;
            font-size: 17px;
            color: #0f172a;
        }

        .card-header p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .form-body {
            padding: 22px;
        }

        /* Sections */

        .form-section {
            margin-bottom: 25px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eef2f7;
        }

        .section-number {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .section-title h3 {
            margin: 0;
            font-size: 14px;
            color: #1e293b;
        }

        /* Form */

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .required {
            color: #dc2626;
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
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        textarea.form-control {
            height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-help {
            margin-top: 6px;
            font-size: 11px;
            color: #94a3b8;
        }

        /* Status */

        .status-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
        }

        .status-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #334155;
            font-weight: 600;
        }

        .switch {
            position: relative;
            width: 42px;
            height: 23px;
            display: inline-block;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            background: #cbd5e1;
            border-radius: 30px;
            transition: 0.2s;
        }

        .slider:before {
            content: "";
            position: absolute;
            width: 17px;
            height: 17px;
            left: 3px;
            top: 3px;
            background: #ffffff;
            border-radius: 50%;
            transition: 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.20);
        }

        .switch input:checked + .slider {
            background: #2563eb;
        }

        .switch input:checked + .slider:before {
            transform: translateX(19px);
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

        /* Footer */

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            margin-top: 22px;
            border-top: 1px solid #e5e7eb;
        }

        /* Side Panel */

        .side-panel {
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
            margin: 0 0 14px;
            font-size: 15px;
            color: #0f172a;
        }

        /* Product Preview */

        .product-preview {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }

        .preview-top {
            padding: 18px;
            background: linear-gradient(135deg, #eff6ff, #f8fafc);
            border-bottom: 1px solid #e2e8f0;
        }

        .preview-avatar {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .preview-name {
            margin: 0 0 5px;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .preview-sku {
            color: #64748b;
            font-size: 12px;
        }

        .preview-body {
            padding: 15px 18px;
        }

        .preview-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 12px;
        }

        .preview-row:last-child {
            border-bottom: none;
        }

        .preview-row span:first-child {
            color: #64748b;
        }

        .preview-row strong {
            color: #1e293b;
            text-align: right;
        }

        .preview-price {
            color: #16a34a !important;
            font-size: 16px;
        }

        /* Editing Guide */

        .guide-item {
            display: flex;
            gap: 10px;
            margin-bottom: 13px;
        }

        .guide-item:last-child {
            margin-bottom: 0;
        }

        .guide-icon {
            flex: 0 0 26px;
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .guide-text {
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Dark Mode */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .card-header h2,
        html.dark-mode .section-title h3,
        html.dark-mode .side-card h3,
        html.dark-mode .summary-value,
        html.dark-mode .preview-name,
        html.dark-mode .record-text strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .page-title .breadcrumb,
        html.dark-mode .breadcrumb,
        html.dark-mode .card-header p,
        html.dark-mode .preview-sku,
        html.dark-mode .form-help,
        html.dark-mode .guide-text,
        html.dark-mode .preview-row span:first-child,
        html.dark-mode .record-text span {
            color: #94a3b8;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .record-bar,
        html.dark-mode .summary-card,
        html.dark-mode .product-preview {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .form-footer,
        html.dark-mode .section-title,
        html.dark-mode .preview-row {
            border-color: #1f2937;
        }

        html.dark-mode .form-control {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark-mode .form-control:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.10);
        }

        html.dark-mode .status-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .status-label {
            color: #cbd5e1;
        }

        html.dark-mode .preview-top {
            background: linear-gradient(135deg, #172554, #111827);
            border-color: #1f2937;
        }

        html.dark-mode .preview-row strong {
            color: #e2e8f0;
        }

        html.dark-mode .guide-icon,
        html.dark-mode .section-number {
            background: #172554;
            color: #93c5fd;
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

        @media (max-width: 1024px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

            .side-panel {
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

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .side-panel {
                grid-template-columns: 1fr;
            }

            .record-bar {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 480px) {

            .main-content {
                padding: 15px;
            }

            .form-body,
            .card-header,
            .side-card {
                padding: 16px;
            }

            .form-footer {
                flex-direction: column;
            }

            .form-footer .btn {
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

        <a href="../dashboard/index.php">Dashboard</a>

        <span>/</span>

        <a href="index.php">Products</a>

        <span>/</span>

        <span>Edit Product</span>

    </div>

    <!-- Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>Edit Product</h1>

            <p>Update the product or service information below.</p>

        </div>

        <div class="header-actions">

            <a
                href="view.php?id=<?php echo $id; ?>"
                class="btn btn-secondary"
            >
                View Product
            </a>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                Product List
            </a>

        </div>

    </div>

    <!-- Current Record -->

    <div class="record-bar">

        <div class="record-info">

            <div class="record-avatar" id="recordAvatar">
                <?php echo htmlspecialchars(strtoupper(substr($product["name"], 0, 1))); ?>
            </div>

            <div class="record-text">

                <strong id="recordName">
                    <?php echo htmlspecialchars($product["name"]); ?>
                </strong>

                <span>
                    Product ID #<?php echo $id; ?>
                    <?php if (!empty($product["sku"])): ?>
                        • SKU: <?php echo htmlspecialchars($product["sku"]); ?>
                    <?php endif; ?>
                </span>

            </div>

        </div>

        <div
            class="record-status <?php echo ((int)$product["status"] === 1) ? "status-active" : "status-inactive"; ?>"
            id="recordStatus"
        >
            <?php echo ((int)$product["status"] === 1) ? "Active" : "Inactive"; ?>
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
                Product Type
            </div>

            <div class="summary-value" id="summaryType">
                <?php echo ucfirst(htmlspecialchars($product["type"])); ?>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-label">
                Current Price
            </div>

            <div class="summary-value" id="summaryPrice">
                <?php echo number_format((float)$product["price"], 2); ?>
            </div>

        </div>

    </div>

    <!-- Main Content -->

    <div class="content-grid">

        <!-- Form -->

        <div class="form-card">

            <div class="card-header">

                <h2>Product Information</h2>

                <p>Modify the fields below and save your changes.</p>

            </div>

            <div class="form-body">

                <?php if ($error !== ""): ?>

                    <div class="error-box">

                        <span>⚠️</span>

                        <div>
                            <?php echo htmlspecialchars($error); ?>
                        </div>

                    </div>

                <?php endif; ?>

                <form method="POST" autocomplete="off">

                    <!-- Basic Information -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">1</div>

                            <h3>Basic Information</h3>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="name">
                                    Product Name <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($product["name"]); ?>"
                                    placeholder="Enter product name"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label for="sku">
                                    SKU
                                </label>

                                <input
                                    type="text"
                                    id="sku"
                                    name="sku"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($product["sku"] ?? ""); ?>"
                                    placeholder="Example: PROD-001"
                                >

                                <div class="form-help">
                                    SKU must be unique when provided.
                                </div>

                            </div>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="type">
                                    Type
                                </label>

                                <select
                                    id="type"
                                    name="type"
                                    class="form-control"
                                >

                                    <option
                                        value="product"
                                        <?php echo ($product["type"] === "product") ? "selected" : ""; ?>
                                    >
                                        Product
                                    </option>

                                    <option
                                        value="service"
                                        <?php echo ($product["type"] === "service") ? "selected" : ""; ?>
                                    >
                                        Service
                                    </option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label for="price">
                                    Price <span class="required">*</span>
                                </label>

                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    value="<?php echo htmlspecialchars($product["price"]); ?>"
                                    placeholder="0.00"
                                    required
                                >

                            </div>

                        </div>

                    </div>

                    <!-- Pricing & Tax -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">2</div>

                            <h3>Pricing & Tax</h3>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="tax_rate">
                                    Tax Rate (%)
                                </label>

                                <input
                                    type="number"
                                    id="tax_rate"
                                    name="tax_rate"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value="<?php echo htmlspecialchars($product["tax_rate"]); ?>"
                                    placeholder="0"
                                >

                                <div class="form-help">
                                    Enter a value between 0 and 100.
                                </div>

                            </div>

                            <div class="form-group">

                                <label>
                                    Status
                                </label>

                                <div class="status-box">

                                    <div class="status-label" id="statusText">

                                        <?php if ((int)$product["status"] === 1): ?>

                                            Product is active

                                        <?php else: ?>

                                            Product is inactive

                                        <?php endif; ?>

                                    </div>

                                    <label class="switch">

                                        <input
                                            type="checkbox"
                                            name="status"
                                            value="1"
                                            id="status"
                                            <?php echo ((int)$product["status"] === 1) ? "checked" : ""; ?>
                                        >

                                        <span class="slider"></span>

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Description -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">3</div>

                            <h3>Description</h3>

                        </div>

                        <div class="form-group">

                            <label for="description">
                                Product Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                placeholder="Enter product or service description..."
                            ><?php echo htmlspecialchars($product["description"] ?? ""); ?></textarea>

                            <div class="form-help">
                                Update the description whenever the product information changes.
                            </div>

                        </div>

                    </div>

                    <!-- Footer -->

                    <div class="form-footer">

                        <a
                            href="view.php?id=<?php echo $id; ?>"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            ✓ Update Product
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- Right Panel -->

        <div class="side-panel">

            <!-- Live Preview -->

            <div class="side-card">

                <h3>Product Preview</h3>

                <div class="product-preview">

                    <div class="preview-top">

                        <div class="preview-avatar" id="previewAvatar">
                            <?php echo htmlspecialchars(strtoupper(substr($product["name"], 0, 1))); ?>
                        </div>

                        <div class="preview-name" id="previewName">
                            <?php echo htmlspecialchars($product["name"]); ?>
                        </div>

                        <div class="preview-sku" id="previewSku">

                            <?php if (!empty($product["sku"])): ?>

                                SKU: <?php echo htmlspecialchars($product["sku"]); ?>

                            <?php else: ?>

                                SKU not provided

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="preview-body">

                        <div class="preview-row">

                            <span>Type</span>

                            <strong id="previewType">
                                <?php echo ucfirst(htmlspecialchars($product["type"])); ?>
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>Price</span>

                            <strong
                                class="preview-price"
                                id="previewPrice"
                            >
                                <?php echo number_format((float)$product["price"], 2); ?>
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>Tax Rate</span>

                            <strong id="previewTax">
                                <?php echo number_format((float)$product["tax_rate"], 2); ?>%
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>Status</span>

                            <strong id="previewStatus">
                                <?php echo ((int)$product["status"] === 1) ? "Active" : "Inactive"; ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Editing Guide -->

            <div class="side-card">

                <h3>Editing Guide</h3>

                <div class="guide-item">

                    <div class="guide-icon">1</div>

                    <div class="guide-text">
                        Keep the product name clear and consistent.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">2</div>

                    <div class="guide-text">
                        Make sure the SKU is unique before saving.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">3</div>

                    <div class="guide-text">
                        Review price and tax values carefully.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">4</div>

                    <div class="guide-text">
                        Set the status to inactive when the product should no longer be used.
                    </div>

                </div>

            </div>

            <!-- Quick Tip -->

            <div class="side-card">

                <h3>Quick Tip</h3>

                <div class="guide-text">
                    Changes made here will be reflected wherever this product is used in deals, quotes and sales.
                </div>

            </div>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const nameInput = document.getElementById("name");
    const skuInput = document.getElementById("sku");
    const typeInput = document.getElementById("type");
    const priceInput = document.getElementById("price");
    const taxInput = document.getElementById("tax_rate");
    const statusInput = document.getElementById("status");

    const recordAvatar = document.getElementById("recordAvatar");
    const recordName = document.getElementById("recordName");
    const recordStatus = document.getElementById("recordStatus");
    const statusText = document.getElementById("statusText");

    const summaryType = document.getElementById("summaryType");
    const summaryPrice = document.getElementById("summaryPrice");

    const previewAvatar = document.getElementById("previewAvatar");
    const previewName = document.getElementById("previewName");
    const previewSku = document.getElementById("previewSku");
    const previewType = document.getElementById("previewType");
    const previewPrice = document.getElementById("previewPrice");
    const previewTax = document.getElementById("previewTax");
    const previewStatus = document.getElementById("previewStatus");

    function formatNumber(value) {

        const number = parseFloat(value);

        if (isNaN(number)) {
            return "0.00";
        }

        return number.toFixed(2);

    }

    function updatePreview() {

        const name = nameInput.value.trim();
        const sku = skuInput.value.trim();
        const type = typeInput.value;
        const price = priceInput.value;
        const tax = taxInput.value;
        const active = statusInput.checked;

        const displayName = name !== ""
            ? name
            : "New Product";

        const initial = displayName
            .charAt(0)
            .toUpperCase();

        /* Record Bar */

        recordAvatar.textContent = initial;
        recordName.textContent = displayName;

        recordStatus.textContent = active
            ? "Active"
            : "Inactive";

        recordStatus.classList.toggle(
            "status-active",
            active
        );

        recordStatus.classList.toggle(
            "status-inactive",
            !active
        );

        /* Status */

        statusText.textContent = active
            ? "Product is active"
            : "Product is inactive";

        /* Summary */

        summaryType.textContent =
            type === "service"
                ? "Service"
                : "Product";

        summaryPrice.textContent =
            formatNumber(price);

        /* Preview */

        previewAvatar.textContent = initial;

        previewName.textContent = displayName;

        previewSku.textContent = sku !== ""
            ? "SKU: " + sku
            : "SKU not provided";

        previewType.textContent =
            type === "service"
                ? "Service"
                : "Product";

        previewPrice.textContent =
            formatNumber(price);

        previewTax.textContent =
            formatNumber(tax) + "%";

        previewStatus.textContent =
            active
                ? "Active"
                : "Inactive";
    }

    nameInput.addEventListener(
        "input",
        updatePreview
    );

    skuInput.addEventListener(
        "input",
        updatePreview
    );

    typeInput.addEventListener(
        "change",
        updatePreview
    );

    priceInput.addEventListener(
        "input",
        updatePreview
    );

    taxInput.addEventListener(
        "input",
        updatePreview
    );

    statusInput.addEventListener(
        "change",
        updatePreview
    );

    updatePreview();

});

</script>

</body>

</html>