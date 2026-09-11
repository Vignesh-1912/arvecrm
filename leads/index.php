<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


// Get Leads

$sql = "SELECT
            leads.*,
            companies.company_name,
            contacts.first_name,
            contacts.last_name
        FROM leads
        LEFT JOIN companies
            ON leads.company_id = companies.id
        LEFT JOIN contacts
            ON leads.contact_id = contacts.id
        ORDER BY leads.id ASC";

$stmt = $conn->query($sql);

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Leads - CRM</title>

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

            <h2>Leads</h2>

            <a href="add.php" class="add-btn">
                + Add Lead
            </a>

        </div>


        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Lead Name</th>

                    <th>Company</th>

                    <th>Contact</th>

                    <th>Email</th>

                    <th>Phone</th>

                    <th>Source</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($leads) > 0): ?>

                <?php foreach ($leads as $lead): ?>

                    <tr>

                        <!-- ID -->

                        <td>
                            <?php echo $lead["id"]; ?>
                        </td>


                        <!-- Lead Name -->

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $lead["lead_name"]
                            );
                            ?>
                        </td>


                        <!-- Company -->

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $lead["company_name"] ?? "-"
                            );
                            ?>
                        </td>


                        <!-- Contact -->

                        <td>

                            <?php

                            if (!empty($lead["first_name"])) {

                                echo htmlspecialchars(
                                    $lead["first_name"] . " " .
                                    ($lead["last_name"] ?? "")
                                );

                            } else {

                                echo "-";

                            }

                            ?>

                        </td>


                        <!-- Email -->

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $lead["email"] ?? "-"
                            );
                            ?>
                        </td>


                        <!-- Phone -->

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $lead["phone"] ?? "-"
                            );
                            ?>
                        </td>


                        <!-- Source -->

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $lead["source"] ?? "-"
                            );
                            ?>
                        </td>


                        <!-- Status -->

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $lead["status"] ?? "-"
                            );
                            ?>
                        </td>


                        <!-- Actions -->

                        <td>

                            <a
                                href="view.php?id=<?php echo $lead["id"]; ?>"
                            >
                                View
                            </a>

                            &nbsp; | &nbsp;

                            <a
                                href="edit.php?id=<?php echo $lead["id"]; ?>"
                            >
                                Edit
                            </a>

                            &nbsp; | &nbsp;

                            <a
                                href="delete.php?id=<?php echo $lead["id"]; ?>"
                                class="delete-link"
                                onclick="return confirm('Are you sure you want to delete this lead?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="9"
                        class="no-data"
                    >
                        No leads found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>