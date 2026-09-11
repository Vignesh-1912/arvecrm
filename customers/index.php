<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$sql = "SELECT * FROM customers ORDER BY id DESC";
$stmt = $conn->query($sql);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customers - CRM</title>

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

        .header {
            background: #1e293b;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
        }

        .container {
            padding: 30px;
        }

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 5px;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f1f5f9;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            background: #dcfce7;
            color: #166534;
        }

        .delete-btn {
            color: #dc2626;
            text-decoration: none;
        }

        .back-btn {
            color: white;
            text-decoration: none;
        }

    </style>

</head>

<body>

    <div class="header">

        <h1>Customers</h1>

        <a class="back-btn" href="../dashboard/index.php">
            ← Dashboard
        </a>

    </div>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="container">

        <div class="top-section">

            <h2>Customer List</h2>

            <a class="add-btn" href="add.php">
                + Add Customer
            </a>

        </div>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Customer Code</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Credit Limit</th>
                        <th>Created</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($customers) > 0): ?>

                        <?php foreach ($customers as $customer): ?>

                            <tr>

                                <td>
                                    <?php echo $customer["id"]; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($customer["customer_code"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($customer["customer_type"]); ?>
                                </td>

                                <td>

                                    <span class="status">
                                        <?php echo htmlspecialchars($customer["status"]); ?>
                                    </span>

                                </td>

                                <td>
                                    ₹<?php echo number_format($customer["credit_limit"], 2); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($customer["created_at"]); ?>
                                </td>

                                <td>

    <a href="view.php?id=<?php echo $customer["id"]; ?>">
        View
    </a>

    &nbsp; | &nbsp;

    <a href="edit.php?id=<?php echo $customer["id"]; ?>">
        Edit
    </a>

    &nbsp; | &nbsp;

    <a
        class="delete-btn"
        href="delete.php?id=<?php echo $customer["id"]; ?>"
        onclick="return confirm('Are you sure you want to delete this customer?');"
    >
        Delete
    </a>

</td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" style="text-align:center;">
                                No customers found.
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
