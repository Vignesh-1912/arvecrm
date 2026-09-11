<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$sql = "
    SELECT
        activities.*,
        CONCAT(contacts.first_name, ' ', contacts.last_name) AS contact_name,
        customers.customer_code,
        deals.title AS deal_title,
        users.name AS created_by_name
    FROM activities
    LEFT JOIN contacts ON activities.contact_id = contacts.id
    LEFT JOIN customers ON activities.customer_id = customers.id
    LEFT JOIN deals ON activities.deal_id = deals.id
    LEFT JOIN users ON activities.created_by = users.id
    ORDER BY activities.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Activities</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
        }

        .add-button {
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
        }

        .add-button:hover {
            background: #1d4ed8;
        }

        .activities-table-container {
            width: 100%;
            overflow-x: auto;
            background: white;
            border-radius: 8px;
        }

        .activities-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .activities-table th,
        .activities-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .activities-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .activities-table tr:hover {
            background: #f8fafc;
        }

        .action-links {
            white-space: nowrap;
        }

        .action-links a {
            display: inline-block;
            margin-right: 6px;
            text-decoration: none;
        }

        .action-links .view {
            color: #2563eb;
        }

        .action-links .edit {
            color: #16a34a;
        }

        .action-links .delete {
            color: #dc2626;
        }

        .activity-type {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 13px;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-header">

        <h1>Activities</h1>

        <a href="add.php" class="add-button">
            + Add Activity
        </a>

    </div>

    <div class="activities-table-container">

        <table class="activities-table">

            <thead>

                <tr>

                    <th style="width: 5%;">ID</th>

                    <th style="width: 10%;">Type</th>

                    <th style="width: 18%;">Subject</th>

                    <th style="width: 15%;">Contact</th>

                    <th style="width: 12%;">Customer</th>

                    <th style="width: 12%;">Deal</th>

                    <th style="width: 13%;">Activity Date</th>

                    <th style="width: 15%;">Action</th>

                </tr>

            </thead>

            <tbody>

            <?php if (count($activities) > 0): ?>

                <?php foreach ($activities as $activity): ?>

                    <tr>

                        <td>
                            <?php echo $activity["id"]; ?>
                        </td>

                        <td>

                            <span class="activity-type">

                                <?php
                                echo htmlspecialchars(
                                    $activity["type"] ?? "-"
                                );
                                ?>

                            </span>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $activity["subject"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $activity["contact_name"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $activity["customer_code"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $activity["deal_title"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $activity["activity_date"] ?? "-"
                            );
                            ?>
                        </td>

                        <td class="action-links">

                            <a
                                href="view.php?id=<?php echo $activity["id"]; ?>"
                                class="view"
                            >
                                View
                            </a>

                            <a
                                href="edit.php?id=<?php echo $activity["id"]; ?>"
                                class="edit"
                            >
                                Edit
                            </a>

                            <a
                                href="delete.php?id=<?php echo $activity["id"]; ?>"
                                class="delete"
                                onclick="return confirm('Are you sure you want to delete this activity?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="8"
                        style="text-align:center; padding:30px;"
                    >
                        No activities found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>