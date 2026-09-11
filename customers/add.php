<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $customer_code = trim($_POST["customer_code"]);
    $customer_type = trim($_POST["customer_type"]);
    $status = trim($_POST["status"]);
    $credit_limit = trim($_POST["credit_limit"]);
    $notes = trim($_POST["notes"]);

    if (empty($customer_code)) {

        $error = "Customer code is required.";

    } else {

        try {

            $sql = "INSERT INTO customers
                    (customer_code, customer_type, status, credit_limit, notes, created_by)
                    VALUES
                    (:customer_code, :customer_type, :status, :credit_limit, :notes, :created_by)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":customer_code" => $customer_code,
                ":customer_type" => $customer_type,
                ":status" => $status,
                ":credit_limit" => $credit_limit,
                ":notes" => $notes,
                ":created_by" => $_SESSION["user_id"]
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                $error = "Customer code already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
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

    <title>Add Customer - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }

        .container {
            width: 600px;
            max-width: 95%;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .buttons {
            display: flex;
            gap: 10px;
        }

        .save-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .cancel-btn {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 5px;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="container">

        <h1>Add Customer</h1>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>Customer Code *</label>

                <input
                    type="text"
                    name="customer_code"
                    placeholder="Example: CUST-001"
                    required
                >

            </div>

            <div class="form-group">

                <label>Customer Type</label>

                <select name="customer_type">

                    <option value="business">Business</option>

                    <option value="individual">Individual</option>

                </select>

            </div>

            <div class="form-group">

                <label>Status</label>

                <select name="status">

                    <option value="active">Active</option>

                    <option value="inactive">Inactive</option>

                </select>

            </div>

            <div class="form-group">

                <label>Credit Limit</label>

                <input
                    type="number"
                    name="credit_limit"
                    value="0"
                    min="0"
                    step="0.01"
                >

            </div>

            <div class="form-group">

                <label>Notes</label>

                <textarea
                    name="notes"
                    placeholder="Enter customer notes..."
                ></textarea>

            </div>

            <div class="buttons">

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Customer
                </button>

                <a
                    href="index.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>
