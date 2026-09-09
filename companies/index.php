<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$sql = "SELECT * FROM companies ORDER BY id DESC";
$stmt = $conn->query($sql);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Companies - CRM</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        h2 {
            margin: 0;
        }

        .add-btn {
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 5px;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        tr:hover {
            background: #f8fafc;
        }

        .action-link {
            text-decoration: none;
            color: #2563eb;
        }

        .delete-link {
            color: #dc2626;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="top-bar">

        <h2>Companies</h2>

        <a href="add.php" class="add-btn">
            + Add Company
        </a>

    </div>

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Company Name</th>

                <th>Industry</th>

                <th>Phone</th>

                <th>Email</th>

                <th>Website</th>

                <th>City</th>

                <th>Country</th>

                <th>Action</th>

            </tr>

        </thead>

        <tbody>

        <?php if (count($companies) > 0): ?>

            <?php foreach ($companies as $company): ?>

                <tr>

                    <td>
                        <?php echo $company["id"]; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["company_name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["industry"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["phone"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["email"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["website"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["city"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($company["country"] ?? ""); ?>
                    </td>

                    <td>

                        <a
                            class="action-link"
                            href="view.php?id=<?php echo $company["id"]; ?>"
                        >
                            View
                        </a>

                        &nbsp; | &nbsp;

                        <a
                            class="action-link"
                            href="edit.php?id=<?php echo $company["id"]; ?>"
                        >
                            Edit
                        </a>

                        &nbsp; | &nbsp;

                        <a
                            class="action-link delete-link"
                            href="delete.php?id=<?php echo $company["id"]; ?>"
                            onclick="return confirm('Are you sure you want to delete this company?');"
                        >
                            Delete
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>

                <td colspan="9" class="no-data">
                    No companies found.
                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>

</body>

</html>