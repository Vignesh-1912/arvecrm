<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$sql = "SELECT
            contacts.*,
            companies.company_name
        FROM contacts
        LEFT JOIN companies
            ON contacts.company_id = companies.id
        ORDER BY contacts.id DESC";

$stmt = $conn->query($sql);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contacts - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .content-container {
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

        .top-bar h2 {
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

        a {
            color: #2563eb;
            text-decoration: none;
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

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="content-container">

        <div class="top-bar">

            <h2>Contacts</h2>

            <a href="add.php" class="add-btn">
                + Add Contact
            </a>

        </div>

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Job Title</th>
                    <th>Status</th>
                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

            <?php if (count($contacts) > 0): ?>

                <?php foreach ($contacts as $contact): ?>

                    <tr>

                        <td>
                            <?php echo $contact["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $contact["first_name"] . " " . ($contact["last_name"] ?? "")
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($contact["company_name"] ?? "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($contact["email"] ?? "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($contact["phone"] ?? "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($contact["job_title"] ?? "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($contact["status"]); ?>
                        </td>

                        <td>

                            <a href="view.php?id=<?php echo $contact["id"]; ?>">
                                View
                            </a>

                            &nbsp; | &nbsp;

                            <a href="edit.php?id=<?php echo $contact["id"]; ?>">
                                Edit
                            </a>

                            &nbsp; | &nbsp;

                            <a
                                class="delete-link"
                                href="delete.php?id=<?php echo $contact["id"]; ?>"
                                onclick="return confirm('Are you sure you want to delete this contact?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8" class="no-data">
                        No contacts found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>