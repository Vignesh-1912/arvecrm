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

$sql = "SELECT * FROM customers WHERE id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo "Customer not found.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Customer - CRM</title>

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
            width: 800px;
            max-width: 95%;
            margin: 40px auto;
        }

        .header {
            background: #1e293b;
            color: white;
            padding: 20px 25px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
        }

        .back-btn {
            color: white;
            text-decoration: none;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .customer-title {
            margin-bottom: 25px;
        }

        .customer-title h2 {
            margin: 0 0 5px 0;
        }

        .customer-title p {
            margin: 0;
            color: #64748b;
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-box {
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 6px;
        }

        .detail-box.full {
            grid-column: 1 / -1;
        }

        .label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
        }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            background: #dcfce7;
            color: #166534;
        }

        .actions {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        .edit-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 11px 20px;
            border-radius: 5px;
        }

        .list-btn {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 11px 20px;
            border-radius: 5px;
        }

        @media (max-width: 600px) {

            .details {
                grid-template-columns: 1fr;
            }

            .detail-box.full {
                grid-column: auto;
            }

        }

    </style>

</head>

<body>

    <div class="container">

        <div class="header">

            <h1>Customer Details</h1>

            <a href="index.php" class="back-btn">
                ← Customer List
            </a>

        </div>

        <div class="card">

            <div class="customer-title">

                <h2>
                    <?php echo htmlspecialchars($customer["customer_code"]); ?>
                </h2>

                <p>
                    Customer ID:
                    <?php echo $customer["id"]; ?>
                </p>

            </div>

            <div class="details">

                <div class="detail-box">

                    <div class="label">
                        Customer Code
                    </div>

                    <div class="value">
                        <?php echo htmlspecialchars($customer["customer_code"]); ?>
                    </div>

                </div>

                <div class="detail-box">

                    <div class="label">
                        Customer Type
                    </div>

                    <div class="value">
                        <?php echo htmlspecialchars($customer["customer_type"]); ?>
                    </div>

                </div>

                <div class="detail-box">

                    <div class="label">
                        Status
                    </div>

                    <div class="value">

                        <span class="status">
                            <?php echo htmlspecialchars($customer["status"]); ?>
                        </span>

                    </div>

                </div>

                <div class="detail-box">

                    <div class="label">
                        Credit Limit
                    </div>

                    <div class="value">
                        ₹<?php echo number_format($customer["credit_limit"], 2); ?>
                    </div>

                </div>

                <div class="detail-box">

                    <div class="label">
                        Created Date
                    </div>

                    <div class="value">
                        <?php echo htmlspecialchars($customer["created_at"]); ?>
                    </div>

                </div>

                <div class="detail-box">

                    <div class="label">
                        Updated Date
                    </div>

                    <div class="value">
                        <?php echo htmlspecialchars($customer["updated_at"]); ?>
                    </div>

                </div>

                <div class="detail-box full">

                    <div class="label">
                        Notes
                    </div>

                    <div class="value">

                        <?php

                        if (!empty($customer["notes"])) {

                            echo nl2br(
                                htmlspecialchars($customer["notes"])
                            );

                        } else {

                            echo "No notes available.";

                        }

                        ?>

                    </div>

                </div>

            </div>

            <div class="actions">

                <a
                    href="edit.php?id=<?php echo $customer["id"]; ?>"
                    class="edit-btn"
                >
                    Edit Customer
                </a>

                <a href="index.php" class="list-btn">
                    Back to List
                </a>

            </div>

        </div>

    </div>

</body>

</html>
