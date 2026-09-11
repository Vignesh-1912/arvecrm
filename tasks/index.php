<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$sql = "
    SELECT 
        tasks.*,
        users.name AS assigned_name,
        contacts.first_name,
        contacts.last_name,
        customers.customer_code
    FROM tasks
    LEFT JOIN users ON tasks.assigned_to = users.id
    LEFT JOIN contacts ON tasks.contact_id = contacts.id
    LEFT JOIN customers ON tasks.customer_id = customers.id
    ORDER BY tasks.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Tasks</title>

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

        .tasks-table-container {
            width: 100%;
            overflow-x: auto;
            background: white;
            border-radius: 8px;
        }

        .tasks-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tasks-table th,
        .tasks-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .tasks-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .tasks-table tr:hover {
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

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 13px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-header">

        <h1>Tasks</h1>

        <a href="add.php" class="add-button">
            + Add Task
        </a>

    </div>

    <div class="tasks-table-container">

        <table class="tasks-table">

            <thead>

                <tr>

                    <th style="width: 5%;">ID</th>

                    <th style="width: 18%;">Task Title</th>

                    <th style="width: 15%;">Assigned To</th>

                    <th style="width: 15%;">Contact</th>

                    <th style="width: 12%;">Customer</th>

                    <th style="width: 10%;">Priority</th>

                    <th style="width: 10%;">Status</th>

                    <th style="width: 10%;">Due Date</th>

                    <th style="width: 15%;">Action</th>

                </tr>

            </thead>

            <tbody>

            <?php if (count($tasks) > 0): ?>

                <?php foreach ($tasks as $task): ?>

                    <?php

                    $status = strtolower($task["status"]);

                    $status_class = "status-" . str_replace(" ", "-", $status);

                    $contact_name = trim(
                        ($task["first_name"] ?? "") . " " .
                        ($task["last_name"] ?? "")
                    );

                    ?>

                    <tr>

                        <td>
                            <?php echo $task["id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($task["title"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($task["assigned_name"] ?? "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($contact_name ?: "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($task["customer_code"] ?? "-"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($task["priority"]); ?>
                        </td>

                        <td>

                            <span class="status <?php echo $status_class; ?>">

                                <?php echo htmlspecialchars($task["status"]); ?>

                            </span>

                        </td>

                        <td>
                            <?php echo htmlspecialchars($task["due_date"] ?? "-"); ?>
                        </td>

                        <td class="action-links">

                            <a
                                href="view.php?id=<?php echo $task["id"]; ?>"
                                class="view"
                            >
                                View
                            </a>

                            <a
                                href="edit.php?id=<?php echo $task["id"]; ?>"
                                class="edit"
                            >
                                Edit
                            </a>

                            <a
                                href="delete.php?id=<?php echo $task["id"]; ?>"
                                class="delete"
                                onclick="return confirm('Are you sure you want to delete this task?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="9" style="text-align:center; padding:30px;">
                        No tasks found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>