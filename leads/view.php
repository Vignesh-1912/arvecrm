<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


// Check ID

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


// Get Lead

$sql = "SELECT
            leads.*,
            companies.company_name,
            contacts.first_name,
            contacts.last_name,
            users.name AS assigned_user
        FROM leads

        LEFT JOIN companies
            ON leads.company_id = companies.id

        LEFT JOIN contacts
            ON leads.contact_id = contacts.id

        LEFT JOIN users
            ON leads.assigned_to = users.id

        WHERE leads.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$lead = $stmt->fetch(PDO::FETCH_ASSOC);


// Lead not found

if (!$lead) {
    die("Lead not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Lead - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .content-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th,
        .details-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .details-table th {
            width: 30%;
            background: #f8fafc;
            font-weight: bold;
        }

        .button-area {
            margin-top: 25px;
        }

        .edit-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
        }

        .edit-btn:hover {
            background: #1d4ed8;
        }

        .back-btn {
            display: inline-block;
            background: #6b7280;
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 8px;
        }

        .back-btn:hover {
            background: #4b5563;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            background: #dbeafe;
            color: #1e40af;
            font-size: 13px;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="content-container">

        <div class="page-header">

            <h2>Lead Details</h2>

        </div>


        <table class="details-table">


            <!-- ID -->

            <tr>

                <th>Lead ID</th>

                <td>
                    <?php echo $lead["id"]; ?>
                </td>

            </tr>


            <!-- Lead Name -->

            <tr>

                <th>Lead Name</th>

                <td>
                    <?php echo htmlspecialchars($lead["lead_name"]); ?>
                </td>

            </tr>


            <!-- Company -->

            <tr>

                <th>Company</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["company_name"] ?? "-"
                    );
                    ?>
                </td>

            </tr>


            <!-- Contact -->

            <tr>

                <th>Contact</th>

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

            </tr>


            <!-- Email -->

            <tr>

                <th>Email</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["email"] ?? "-"
                    );
                    ?>
                </td>

            </tr>


            <!-- Phone -->

            <tr>

                <th>Phone</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["phone"] ?? "-"
                    );
                    ?>
                </td>

            </tr>


            <!-- Source -->

            <tr>

                <th>Lead Source</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["source"] ?? "-"
                    );
                    ?>
                </td>

            </tr>


            <!-- Status -->

            <tr>

                <th>Status</th>

                <td>

                    <span class="status">

                        <?php
                        echo htmlspecialchars(
                            $lead["status"]
                        );
                        ?>

                    </span>

                </td>

            </tr>


            <!-- Lead Value -->

            <tr>

                <th>Lead Value</th>

                <td>
                    ₹ <?php
                    echo number_format(
                        (float) $lead["lead_value"],
                        2
                    );
                    ?>
                </td>

            </tr>


            <!-- Assigned To -->

            <tr>

                <th>Assigned To</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["assigned_user"] ?? "-"
                    );
                    ?>
                </td>

            </tr>


            <!-- Notes -->

            <tr>

                <th>Notes</th>

                <td>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $lead["notes"] ?? "-"
                        )
                    );
                    ?>
                </td>

            </tr>


            <!-- Created -->

            <tr>

                <th>Created At</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["created_at"]
                    );
                    ?>
                </td>

            </tr>


            <!-- Updated -->

            <tr>

                <th>Updated At</th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $lead["updated_at"] ?? "-"
                    );
                    ?>
                </td>

            </tr>


        </table>


        <div class="button-area">

            <a
                href="edit.php?id=<?php echo $lead["id"]; ?>"
                class="edit-btn"
            >
                Edit Lead
            </a>


            <a
                href="index.php"
                class="back-btn"
            >
                Back to Leads
            </a>

        </div>

    </div>

</div>

</body>

</html>