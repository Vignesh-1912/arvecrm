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
        activities.*,
        CONCAT(contacts.first_name, ' ', contacts.last_name) AS contact_name,
        contacts.email AS contact_email,
        contacts.phone AS contact_phone,
        customers.customer_code,
        deals.title AS deal_title,
        users.name AS created_by_name
    FROM activities
    LEFT JOIN contacts ON activities.contact_id = contacts.id
    LEFT JOIN customers ON activities.customer_id = customers.id
    LEFT JOIN deals ON activities.deal_id = deals.id
    LEFT JOIN users ON activities.created_by = users.id
    WHERE activities.id = :id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    die("Activity not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>View Activity</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .page-header {
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

        .activity-type {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 13px;
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

        <h1>View Activity</h1>

    </div>

    <div class="details-container">

        <table class="details-table">

            <tr>
                <th>Activity ID</th>

                <td>
                    <?php echo $activity["id"]; ?>
                </td>
            </tr>

            <tr>
                <th>Activity Type</th>

                <td>

                    <span class="activity-type">

                        <?php
                        echo htmlspecialchars(
                            $activity["type"] ?? "-"
                        );
                        ?>

                    </span>

                </td>
            </tr>

            <tr>
                <th>Subject</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $activity["subject"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Description</th>

                <td class="description">
                    <?php
                    echo htmlspecialchars(
                        $activity["description"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Activity Date</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $activity["activity_date"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Contact</th>

                <td>

                    <?php
                    echo htmlspecialchars(
                        $activity["contact_name"] ?? "-"
                    );
                    ?>

                    <?php if (!empty($activity["contact_email"])): ?>

                        <br>

                        Email:
                        <?php
                        echo htmlspecialchars(
                            $activity["contact_email"]
                        );
                        ?>

                    <?php endif; ?>

                    <?php if (!empty($activity["contact_phone"])): ?>

                        <br>

                        Phone:
                        <?php
                        echo htmlspecialchars(
                            $activity["contact_phone"]
                        );
                        ?>

                    <?php endif; ?>

                </td>
            </tr>

            <tr>
                <th>Customer</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $activity["customer_code"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Deal</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $activity["deal_title"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Created By</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $activity["created_by_name"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Created At</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $activity["created_at"] ?? "-"
                    );
                    ?>
                </td>
            </tr>

        </table>


        <div class="actions">

            <a
                href="edit.php?id=<?php echo $activity["id"]; ?>"
                class="btn btn-edit"
            >
                Edit Activity
            </a>

            <a
                href="index.php"
                class="btn btn-back"
            >
                Back to Activities
            </a>

        </div>

    </div>

</div>

</body>

</html>