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

$sql = "
    SELECT
        tasks.*,
        users.name AS assigned_name,
        contacts.first_name,
        contacts.last_name,
        contacts.email AS contact_email,
        contacts.phone AS contact_phone,
        customers.customer_code,
        deals.title AS deal_title
    FROM tasks
    LEFT JOIN users ON tasks.assigned_to = users.id
    LEFT JOIN contacts ON tasks.contact_id = contacts.id
    LEFT JOIN customers ON tasks.customer_id = customers.id
    LEFT JOIN deals ON tasks.deal_id = deals.id
    WHERE tasks.id = :id
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id" => $id
]);

$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Task not found.");
}

$contact_name = trim(
    ($task["first_name"] ?? "") . " " .
    ($task["last_name"] ?? "")
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>View Task</title>

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

        .details-container {
            max-width: 900px;
            background: white;
            padding: 30px;
            border-radius: 8px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th,
        .details-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        .details-table th {
            width: 220px;
            background: #f8fafc;
            font-weight: bold;
        }

        .description {
            white-space: pre-wrap;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
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

        .actions {
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            margin-right: 8px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
        }

        .btn-edit {
            background: #2563eb;
        }

        .btn-edit:hover {
            background: #1d4ed8;
        }

        .btn-back {
            background: #6b7280;
        }

        .btn-back:hover {
            background: #4b5563;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-header">

        <h1>View Task</h1>

    </div>

    <div class="details-container">

        <table class="details-table">

            <tr>
                <th>Task ID</th>
                <td>
                    <?php echo $task["id"]; ?>
                </td>
            </tr>

            <tr>
                <th>Task Title</th>
                <td>
                    <?php echo htmlspecialchars($task["title"]); ?>
                </td>
            </tr>

            <tr>
                <th>Description</th>
                <td class="description">
                    <?php
                    echo htmlspecialchars(
                        $task["description"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Due Date</th>
                <td>
                    <?php echo htmlspecialchars($task["due_date"] ?? "-"); ?>
                </td>
            </tr>

            <tr>
                <th>Priority</th>
                <td>
                    <?php echo htmlspecialchars($task["priority"] ?? "-"); ?>
                </td>
            </tr>

            <tr>
                <th>Status</th>
                <td>

                    <?php

                    $status = strtolower($task["status"]);
                    $status_class =
                        "status-" . str_replace(" ", "-", $status);

                    ?>

                    <span class="status <?php echo $status_class; ?>">

                        <?php
                        echo htmlspecialchars($task["status"]);
                        ?>

                    </span>

                </td>
            </tr>

            <tr>
                <th>Assigned To</th>
                <td>
                    <?php
                    echo htmlspecialchars(
                        $task["assigned_name"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Contact</th>
                <td>

                    <?php if ($contact_name): ?>

                        <?php echo htmlspecialchars($contact_name); ?>

                        <?php if (!empty($task["contact_email"])): ?>

                            <br>
                            Email:
                            <?php
                            echo htmlspecialchars(
                                $task["contact_email"]
                            );
                            ?>

                        <?php endif; ?>

                        <?php if (!empty($task["contact_phone"])): ?>

                            <br>
                            Phone:
                            <?php
                            echo htmlspecialchars(
                                $task["contact_phone"]
                            );
                            ?>

                        <?php endif; ?>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>
            </tr>

            <tr>
                <th>Customer</th>
                <td>
                    <?php
                    echo htmlspecialchars(
                        $task["customer_code"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Deal</th>
                <td>
                    <?php
                    echo htmlspecialchars(
                        $task["deal_title"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Created At</th>
                <td>
                    <?php
                    echo htmlspecialchars(
                        $task["created_at"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Updated At</th>
                <td>
                    <?php
                    echo htmlspecialchars(
                        $task["updated_at"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

        </table>


        <div class="actions">

            <a
                href="edit.php?id=<?php echo $task["id"]; ?>"
                class="btn btn-edit"
            >
                Edit Task
            </a>

            <a
                href="index.php"
                class="btn btn-back"
            >
                Back to Tasks
            </a>

        </div>

    </div>

</div>

</body>

</html>