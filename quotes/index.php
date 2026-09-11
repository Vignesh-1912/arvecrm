<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Get Quotes
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            quotes.id,
            quotes.quote_number,
            quotes.subtotal,
            quotes.tax_amount,
            quotes.discount_amount,
            quotes.total_amount,
            quotes.status,
            quotes.valid_until,
            quotes.created_at,

            companies.company_name,

            contacts.first_name,
            contacts.last_name,

            customers.customer_code,

            deals.title AS deal_title,

            users.name AS created_by_name

        FROM quotes

        LEFT JOIN companies
            ON quotes.company_id = companies.id

        LEFT JOIN contacts
            ON quotes.contact_id = contacts.id

        LEFT JOIN customers
            ON quotes.customer_id = customers.id

        LEFT JOIN deals
            ON quotes.deal_id = deals.id

        LEFT JOIN users
            ON quotes.created_by = users.id

        ORDER BY quotes.id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Quotes - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .page-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
        }

        .btn-add {
            background: #2563eb;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        .quotes-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .quotes-table th,
        .quotes-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        .quotes-table th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .quotes-table th:nth-child(1),
        .quotes-table td:nth-child(1) {
            width: 5%;
        }

        .quotes-table th:nth-child(2),
        .quotes-table td:nth-child(2) {
            width: 11%;
        }

        .quotes-table th:nth-child(3),
        .quotes-table td:nth-child(3) {
            width: 13%;
        }

        .quotes-table th:nth-child(4),
        .quotes-table td:nth-child(4) {
            width: 12%;
        }

        .quotes-table th:nth-child(5),
        .quotes-table td:nth-child(5) {
            width: 11%;
        }

        .quotes-table th:nth-child(6),
        .quotes-table td:nth-child(6) {
            width: 10%;
            white-space: nowrap;
        }

        .quotes-table th:nth-child(7),
        .quotes-table td:nth-child(7) {
            width: 10%;
            white-space: nowrap;
        }

        .quotes-table th:nth-child(8),
        .quotes-table td:nth-child(8) {
            width: 10%;
            white-space: nowrap;
        }

        .quotes-table th:nth-child(9),
        .quotes-table td:nth-child(9) {
            width: 18%;
        }

        .amount {
            font-weight: bold;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 13px;
            text-transform: capitalize;
        }

        .status-draft {
            background: #e5e7eb;
            color: #374151;
        }

        .status-sent {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }

        .action-links {
            white-space: nowrap;
        }

        .action-links a {
            text-decoration: none;
            margin-right: 7px;
        }

        .view-link {
            color: #2563eb;
        }

        .edit-link {
            color: #16a34a;
        }

        .delete-link {
            color: #dc2626;
        }

        .empty-message {
            padding: 20px;
            text-align: center;
            color: #6b7280;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="page-card">

        <div class="page-header">

            <h1>Quotes</h1>

            <a
                href="add.php"
                class="btn btn-add"
            >
                + Add Quote
            </a>

        </div>


        <?php if (count($quotes) > 0): ?>

            <div class="table-container">

                <table class="quotes-table">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Quote Number</th>

                            <th>Company</th>

                            <th>Contact</th>

                            <th>Customer</th>

                            <th>Total Amount</th>

                            <th>Status</th>

                            <th>Valid Until</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($quotes as $quote): ?>

                        <tr>

                            <td>
                                <?php echo $quote["id"]; ?>
                            </td>


                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $quote["quote_number"]
                                    );
                                    ?>
                                </strong>

                            </td>


                            <td>

                                <?php

                                echo $quote["company_name"]
                                    ? htmlspecialchars(
                                        $quote["company_name"]
                                    )
                                    : "-";

                                ?>

                            </td>


                            <td>

                                <?php

                                if ($quote["first_name"]) {

                                    echo htmlspecialchars(
                                        trim(
                                            $quote["first_name"] .
                                            " " .
                                            ($quote["last_name"] ?? "")
                                        )
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>


                            <td>

                                <?php

                                echo $quote["customer_code"]
                                    ? htmlspecialchars(
                                        $quote["customer_code"]
                                    )
                                    : "-";

                                ?>

                            </td>


                            <td class="amount">

                                ₹ <?php

                                echo number_format(
                                    (float)$quote["total_amount"],
                                    2
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                $status = strtolower(
                                    $quote["status"]
                                );

                                $status_class =
                                    "status-" . $status;

                                ?>

                                <span
                                    class="status <?php echo $status_class; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $quote["status"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                echo $quote["valid_until"]
                                    ? htmlspecialchars(
                                        $quote["valid_until"]
                                    )
                                    : "-";

                                ?>

                            </td>


                            <td class="action-links">

                                <a
                                    href="view.php?id=<?php echo $quote["id"]; ?>"
                                    class="view-link"
                                >
                                    View
                                </a>

                                |

                                <a
                                    href="edit.php?id=<?php echo $quote["id"]; ?>"
                                    class="edit-link"
                                >
                                    Edit
                                </a>

                                |

                                <a
                                    href="delete.php?id=<?php echo $quote["id"]; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to delete this quote?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-message">

                No quotes found.

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>