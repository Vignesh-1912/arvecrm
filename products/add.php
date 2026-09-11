<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"] ?? "");
    $sku = trim($_POST["sku"] ?? "");
    $type = trim($_POST["type"] ?? "product");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "0");
    $tax_rate = trim($_POST["tax_rate"] ?? "0");
    $status = isset($_POST["status"]) ? 1 : 0;

    if ($name === "") {

        $error = "Product name is required.";

    } elseif (!is_numeric($price)) {

        $error = "Price must be a valid number.";

    } elseif (!is_numeric($tax_rate)) {

        $error = "Tax rate must be a valid number.";

    } elseif ((float)$tax_rate < 0 || (float)$tax_rate > 100) {

        $error = "Tax rate must be between 0 and 100.";

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
                ":price" => $price,
                ":tax_rate" => $tax_rate,
                ":status" => $status,
                ":created_by" => $_SESSION["user_id"]
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                $error = "SKU already exists. Please use a different SKU.";
            } else {
                $error = "Unable to add product: " . $e->getMessage();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Add Product - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 1000px;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .form-header h1 {
            margin: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input {
            width: auto;
        }

        .btn {
            display: inline-block;
            padding: 11px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-save {
            background: #2563eb;
        }

        .btn-back {
            background: #6b7280;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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

    <div class="form-card">

        <div class="form-header">

            <h1>Add Product</h1>

            <a href="index.php" class="btn btn-back">
                Back
            </a>

        </div>

        <?php if ($error != ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-row">

                <div class="form-group">

                    <label>Product Name *</label>

                    <input
                        type="text"
                        name="name"
                        value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>SKU</label>

                    <input
                        type="text"
                        name="sku"
                        value="<?php echo htmlspecialchars($_POST["sku"] ?? ""); ?>"
                        placeholder="Example: PROD-001"
                    >

                </div>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Type</label>

                    <select name="type">

                        <option value="product"
                            <?php echo (($_POST["type"] ?? "product") == "product") ? "selected" : ""; ?>>
                            Product
                        </option>

                        <option value="service"
                            <?php echo (($_POST["type"] ?? "") == "service") ? "selected" : ""; ?>>
                            Service
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Price *</label>

                    <input
                        type="number"
                        name="price"
                        step="0.01"
                        min="0"
                        value="<?php echo htmlspecialchars($_POST["price"] ?? "0"); ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Tax Rate (%)</label>

                    <input
                        type="number"
                        name="tax_rate"
                        step="0.01"
                        min="0"
                        max="100"
                        value="<?php echo htmlspecialchars($_POST["tax_rate"] ?? "0"); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <div class="checkbox-group">

                        <input
                            type="checkbox"
                            name="status"
                            value="1"
                            <?php echo (!isset($_POST["status"]) || $_POST["status"] == 1) ? "checked" : ""; ?>
                        >

                        <span>Active</span>

                    </div>

                </div>

            </div>

            <div class="form-group">

                <label>Description</label>

                <textarea
                    name="description"
                    placeholder="Enter product description..."
                ><?php echo htmlspecialchars($_POST["description"] ?? ""); ?></textarea>

            </div>

            <button type="submit" class="btn btn-save">
                Save Product
            </button>

            <a href="index.php" class="btn btn-back">
                Cancel
            </a>

        </form>

    </div>

</div>

</body>

</html>