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


// Get Contact

$sql = "SELECT
            contacts.*,
            companies.company_name
        FROM contacts
        LEFT JOIN companies
            ON contacts.company_id = companies.id
        WHERE contacts.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$contact = $stmt->fetch(PDO::FETCH_ASSOC);


// Contact not found

if (!$contact) {
    die("Contact not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Contact - CRM</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="content-container">

        <div class="page-header">

            <h2>Contact Details</h2>

        </div>


        <table class="details-table">

            <tr>

                <th>Contact ID</th>

                <td>
                    <?php echo $contact["id"]; ?>
                </td>

            </tr>


            <tr>

                <th>First Name</th>

                <td>
                    <?php echo htmlspecialchars($contact["first_name"]); ?>
                </td>

            </tr>


            <tr>

                <th>Last Name</th>

                <td>
                    <?php echo htmlspecialchars($contact["last_name"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Company</th>

                <td>
                    <?php echo htmlspecialchars($contact["company_name"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Email</th>

                <td>
                    <?php echo htmlspecialchars($contact["email"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Phone</th>

                <td>
                    <?php echo htmlspecialchars($contact["phone"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Job Title</th>

                <td>
                    <?php echo htmlspecialchars($contact["job_title"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Status</th>

                <td>
                    <?php echo htmlspecialchars($contact["status"]); ?>
                </td>

            </tr>


            <tr>

                <th>Address</th>

                <td>
                    <?php echo nl2br(htmlspecialchars($contact["address"] ?? "-")); ?>
                </td>

            </tr>


            <tr>

                <th>City</th>

                <td>
                    <?php echo htmlspecialchars($contact["city"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>State</th>

                <td>
                    <?php echo htmlspecialchars($contact["state"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Country</th>

                <td>
                    <?php echo htmlspecialchars($contact["country"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Postal Code</th>

                <td>
                    <?php echo htmlspecialchars($contact["postal_code"] ?? "-"); ?>
                </td>

            </tr>


            <tr>

                <th>Created At</th>

                <td>
                    <?php echo htmlspecialchars($contact["created_at"]); ?>
                </td>

            </tr>


            <tr>

                <th>Updated At</th>

                <td>
                    <?php echo htmlspecialchars($contact["updated_at"] ?? "-"); ?>
                </td>

            </tr>

        </table>


        <div class="button-area">

            <a
                href="edit.php?id=<?php echo $contact["id"]; ?>"
                class="edit-btn"
            >
                Edit Contact
            </a>


            <a
                href="index.php"
                class="back-btn"
            >
                Back to Contacts
            </a>

        </div>

    </div>

</div>

</body>

</html>