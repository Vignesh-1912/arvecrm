<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

$name = trim($_POST["name"] ?? "");
$sku = trim($_POST["sku"] ?? "");
$type = trim($_POST["type"] ?? "product");
$description = trim($_POST["description"] ?? "");
$price = trim($_POST["price"] ?? "0");
$tax_rate = trim($_POST["tax_rate"] ?? "0");
$status = isset($_POST["status"]) ? 1 : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

            $sql = "INSERT INTO products
                    (
                        name,
                        sku,
                        type,
                        description,
                        price,
                        tax_rate,
                        status,
                        created_by
                    )
                    VALUES
                    (
                        :name,
                        :sku,
                        :type,
                        :description,
                        :price,
                        :tax_rate,
                        :status,
                        :created_by
                    )";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":name" => $name,
                ":sku" => $sku !== "" ? $sku : null,
                ":type" => $type,
                ":description" => $description !== "" ? $description : null,
                ":price" => (float)$price,
                ":tax_rate" => (float)$tax_rate,
                ":status" => $status,
                ":created_by" => $_SESSION["user_id"]
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $error = "SKU already exists. Please use a different SKU.";
            } else {
                $error = "Unable to add product. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product - CRM</title>

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
            margin-bottom: 24px;
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

        /* Layout */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }

        /* Main card */

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
            box-shadow: 0 1px 3px rgba(0,0,0,0.20);
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

        /* Right panel */

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

        /* Guide */

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

        /* Dark mode */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .card-header h2,
        html.dark-mode .section-title h3,
        html.dark-mode .side-card h3,
        html.dark-mode .preview-name {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .card-header p,
        html.dark-mode .breadcrumb,
        html.dark-mode .preview-sku,
        html.dark-mode .form-help,
        html.dark-mode .guide-text,
        html.dark-mode .preview-row span:first-child {
            color: #94a3b8;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card,
        html.dark-mode .product-preview {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .card-header,
        html.dark-mode .form-footer {
            border-color: #1f2937;
        }

        html.dark-mode .section-title {
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

        html.dark-mode .preview-body {
            background: #111827;
        }

        html.dark-mode .preview-row {
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

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .side-panel {
                grid-template-columns: 1fr;
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

    <div class="breadcrumb">
        <a href="../dashboard/index.php">Dashboard</a>
        <span>/</span>
        <a href="index.php">Products</a>
        <span>/</span>
        <span>Add Product</span>
    </div>

    <div class="page-header">

        <div class="page-title">

            <h1>Add Product</h1>

            <p>Create a new product or service for your CRM.</p>

        </div>

        <div class="header-actions">

            <a href="index.php" class="btn btn-secondary">
                ← Back to Products
            </a>

        </div>

    </div>

    <div class="content-grid">

        <!-- Main Form -->

        <div class="form-card">

            <div class="card-header">

                <h2>Product Information</h2>

                <p>Enter the product details below and save the record.</p>

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
                                    value="<?php echo htmlspecialchars($name); ?>"
                                    placeholder="Enter product name"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label for="sku">SKU</label>

                                <input
                                    type="text"
                                    id="sku"
                                    name="sku"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($sku); ?>"
                                    placeholder="Example: PROD-001"
                                >

                                <div class="form-help">
                                    SKU must be unique when provided.
                                </div>

                            </div>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="type">Type</label>

                                <select
                                    id="type"
                                    name="type"
                                    class="form-control"
                                >

                                    <option value="product" <?php echo ($type === "product") ? "selected" : ""; ?>>
                                        Product
                                    </option>

                                    <option value="service" <?php echo ($type === "service") ? "selected" : ""; ?>>
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
                                    value="<?php echo htmlspecialchars($price); ?>"
                                    placeholder="0.00"
                                    required
                                >

                            </div>

                        </div>

                    </div>

                    <!-- Pricing -->

                    <div class="form-section">

                        <div class="section-title">

                            <div class="section-number">2</div>

                            <h3>Pricing & Tax</h3>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="tax_rate">Tax Rate (%)</label>

                                <input
                                    type="number"
                                    id="tax_rate"
                                    name="tax_rate"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value="<?php echo htmlspecialchars($tax_rate); ?>"
                                    placeholder="0"
                                >

                                <div class="form-help">
                                    Enter a value between 0 and 100.
                                </div>

                            </div>

                            <div class="form-group">

                                <label>Status</label>

                                <div class="status-box">

                                    <div class="status-label">
                                        <span>Product is active</span>
                                    </div>

                                    <label class="switch">

                                        <input
                                            type="checkbox"
                                            name="status"
                                            value="1"
                                            <?php echo ($status == 1) ? "checked" : ""; ?>
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

                            <label for="description">Product Description</label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                placeholder="Enter product or service description..."
                            ><?php echo htmlspecialchars($description); ?></textarea>

                            <div class="form-help">
                                Add useful information that will help your team identify this product.
                            </div>

                        </div>

                    </div>

                    <!-- Footer -->

                    <div class="form-footer">

                        <a href="index.php" class="btn btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-primary">
                            + Save Product
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- Side Panel -->

        <div class="side-panel">

            <!-- Preview -->

            <div class="side-card">

                <h3>Product Preview</h3>

                <div class="product-preview">

                    <div class="preview-top">

                        <div class="preview-avatar" id="previewAvatar">
                            P
                        </div>

                        <div class="preview-name" id="previewName">
                            New Product
                        </div>

                        <div class="preview-sku" id="previewSku">
                            SKU not provided
                        </div>

                    </div>

                    <div class="preview-body">

                        <div class="preview-row">

                            <span>Type</span>

                            <strong id="previewType">
                                Product
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>Price</span>

                            <strong class="preview-price" id="previewPrice">
                                0.00
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>Tax Rate</span>

                            <strong id="previewTax">
                                0%
                            </strong>

                        </div>

                        <div class="preview-row">

                            <span>Status</span>

                            <strong id="previewStatus">
                                Active
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Setup Guide -->

            <div class="side-card">

                <h3>Setup Guide</h3>

                <div class="guide-item">

                    <div class="guide-icon">1</div>

                    <div class="guide-text">
                        Enter a clear product or service name.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">2</div>

                    <div class="guide-text">
                        Add a unique SKU if your business uses product codes.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">3</div>

                    <div class="guide-text">
                        Set the price and applicable tax rate.
                    </div>

                </div>

                <div class="guide-item">

                    <div class="guide-icon">4</div>

                    <div class="guide-text">
                        Keep the product active to make it available throughout the CRM.
                    </div>

                </div>

            </div>

            <!-- Tip -->

            <div class="side-card">

                <h3>Quick Tip</h3>

                <div class="guide-text">
                    Keep product names and SKUs consistent so they are easier to find when creating deals, quotes and sales.
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
    const statusInput = document.querySelector('input[name="status"]');

    const previewAvatar = document.getElementById("previewAvatar");
    const previewName = document.getElementById("previewName");
    const previewSku = document.getElementById("previewSku");
    const previewType = document.getElementById("previewType");
    const previewPrice = document.getElementById("previewPrice");
    const previewTax = document.getElementById("previewTax");
    const previewStatus = document.getElementById("previewStatus");

    function updatePreview() {

        const name = nameInput.value.trim();
        const sku = skuInput.value.trim();

        previewName.textContent = name !== "" ? name : "New Product";

        previewAvatar.textContent = name !== ""
            ? name.charAt(0).toUpperCase()
            : "P";

        previewSku.textContent = sku !== ""
            ? "SKU: " + sku
            : "SKU not provided";

        previewType.textContent =
            typeInput.value === "service"
                ? "Service"
                : "Product";

        const price = parseFloat(priceInput.value);

        previewPrice.textContent =
            !isNaN(price)
                ? price.toFixed(2)
                : "0.00";

        const tax = parseFloat(taxInput.value);

        previewTax.textContent =
            !isNaN(tax)
                ? tax.toFixed(2).replace(/\.00$/, "") + "%"
                : "0%";

        previewStatus.textContent =
            statusInput.checked
                ? "Active"
                : "Inactive";
    }

    nameInput.addEventListener("input", updatePreview);
    skuInput.addEventListener("input", updatePreview);
    typeInput.addEventListener("change", updatePreview);
    priceInput.addEventListener("input", updatePreview);
    taxInput.addEventListener("input", updatePreview);
    statusInput.addEventListener("change", updatePreview);

    updatePreview();

});

</script>

</body>

</html>