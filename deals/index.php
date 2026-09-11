<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


// Get Deals

$sql = "SELECT
            deals.*,
            companies.company_name,
            contacts.first_name,
            contacts.last_name,
            customers.customer_code,
            users.name AS assigned_user

        FROM deals

        LEFT JOIN companies
            ON deals.company_id = companies.id

        LEFT JOIN contacts
            ON deals.contact_id = contacts.id

        LEFT JOIN customers
            ON deals.customer_id = customers.id

        LEFT JOIN users
            ON deals.assigned_to = users.id

        ORDER BY deals.id ASC";

$stmt = $conn->query($sql);

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Deals - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

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

        .deals-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .deals-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .deals-table th,
        .deals-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .deals-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        /* ID */
        .deals-table th:nth-child(1),
        .deals-table td:nth-child(1) {
            width: 4%;
        }

        /* Deal Title */
        .deals-table th:nth-child(2),
        .deals-table td:nth-child(2) {
            width: 12%;
        }

        /* Company */
        .deals-table th:nth-child(3),
        .deals-table td:nth-child(3) {
            width: 9%;
        }

        /* Contact */
        .deals-table th:nth-child(4),
        .deals-table td:nth-child(4) {
            width: 8%;
        }

        /* Amount */
        .deals-table th:nth-child(5),
        .deals-table td:nth-child(5) {
            width: 11%;
            white-space: nowrap;
        }

        /* Stage */
        .deals-table th:nth-child(6),
        .deals-table td:nth-child(6) {
            width: 10%;
        }

        /* Probability */
        .deals-table th:nth-child(7),
        .deals-table td:nth-child(7) {
            width: 9%;
            white-space: nowrap;
        }

        /* Close Date */
        .deals-table th:nth-child(8),
        .deals-table td:nth-child(8) {
            width: 9%;
            white-space: nowrap;
        }

        /* Assigned To */
        .deals-table th:nth-child(9),
        .deals-table td:nth-child(9) {
            width: 10%;
            white-space: normal;
        }

        /* Action */
        .deals-table th:nth-child(10),
        .deals-table td:nth-child(10) {
            width: 18%;
        }

        .action-links {
            white-space: nowrap;
        }

        .action-links a {
            display: inline-block;
            margin-right: 6px;
            text-decoration: none;
        }

        .action-links .delete {
            color: red;
        }

        tr:hover {
            background: #f8fafc;
        }

        a {
            color: #2563eb;
            text-decoration: none;
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

            <h2>Deals</h2>

            <a
                href="add.php"
                class="add-btn"
            >
                + Add Deal
            </a>

        </div>


        <div class="deals-table-container">

        <table class="deals-table">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Deal Title</th>

                    <th>Company</th>

                    <th>Contact</th>

                    <th>Amount</th>

                    <th>Stage</th>

                    <th>Probability</th>

                    <th>Close Date</th>

                    <th>Assigned To</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($deals) > 0): ?>

                <?php foreach ($deals as $deal): ?>

                    <tr>

                        <!-- ID -->

                        <td>
                            <?php echo $deal["id"]; ?>
                        </td>


                        <!-- Deal Title -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $deal["title"]
                            );

                            ?>

                        </td>


                        <!-- Company -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $deal["company_name"] ?? "-"
                            );

                            ?>

                        </td>


                        <!-- Contact -->

                        <td>

                            <?php

                            if (!empty($deal["first_name"])) {

                                echo htmlspecialchars(
                                    $deal["first_name"] . " " .
                                    ($deal["last_name"] ?? "")
                                );

                            } else {

                                echo "-";

                            }

                            ?>

                        </td>


                        <!-- Amount -->

                        <td>

                            ₹ <?php

                            echo number_format(
                                (float) $deal["amount"],
                                2
                            );

                            ?>

                        </td>


                        <!-- Stage -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $deal["stage"]
                            );

                            ?>

                        </td>


                        <!-- Probability -->

                        <td>

                            <?php

                            echo (int) $deal["probability"];

                            ?>%

                        </td>


                        <!-- Close Date -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $deal["expected_close_date"] ?? "-"
                            );

                            ?>

                        </td>


                        <!-- Assigned User -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $deal["assigned_user"] ?? "-"
                            );

                            ?>

                        </td>


                        <!-- Actions -->

                        <td class="action-links">

                            <a href="view.php?id=<?php echo $deal["id"]; ?>">
                                View
                            </a>

                            |

                            <a href="edit.php?id=<?php echo $deal["id"]; ?>">
                                Edit
                            </a>

                            |

                            <a
                                href="delete.php?id=<?php echo $deal["id"]; ?>"
                                class="delete"
                                onclick="return confirm('Are you sure you want to delete this deal?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="10"
                        class="no-data"
                    >
                        No deals found.
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