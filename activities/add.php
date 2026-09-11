<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

// Load contacts
$contact_stmt = $conn->prepare("
    SELECT id, first_name, last_name
    FROM contacts
    ORDER BY first_name ASC
");
$contact_stmt->execute();
$contacts = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);

// Load customers
$customer_stmt = $conn->prepare("
    SELECT id, customer_code
    FROM customers
    ORDER BY id ASC
");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Load deals
$deal_stmt = $conn->prepare("
    SELECT id, title
    FROM deals
    ORDER BY id ASC
");
$deal_stmt->execute();
$deals = $deal_stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $type = trim($_POST["type"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $description = trim($_POST["description"] ?? "");

    $activity_date = !empty($_POST["activity_date"])
        ? $_POST["activity_date"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int) $_POST["contact_id"]
        : null;

    $customer_id = !empty($_POST["customer_id"])
        ? (int) $_POST["customer_id"]
        : null;

    $deal_id = !empty($_POST["deal_id"])
        ? (int) $_POST["deal_id"]
        : null;


    if ($type == "" || $subject == "") {

        $error = "Activity type and subject are required.";

    } else {

        try {

            $sql = "
                INSERT INTO activities
                (
                    contact_id,
                    customer_id,
                    deal_id,
                    type,
                    subject,
                    description,
                    activity_date,
                    created_by
                )
                VALUES
                (
                    :contact_id,
                    :customer_id,
                    :deal_id,
                    :type,
                    :subject,
                    :description,
                    :activity_date,
                    :created_by
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":contact_id" => $contact_id,
                ":customer_id" => $customer_id,
                ":deal_id" => $deal_id,
                ":type" => $type,
                ":subject" => $subject,
                ":description" => $description ?: null,
                ":activity_date" => $activity_date,
                ":created_by" => $_SESSION["user_id"]
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to add activity: " . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Add Activity</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .form-container {
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .buttons {
            margin-top: 25px;
        }

        .btn-save {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        .btn-cancel {
            display: inline-block;
            margin-left: 10px;
            padding: 10px 20px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        @media (max-width: 700px) {

            .form-row {
                flex-direction: column;
                gap: 0;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <h1>Add Activity</h1>

    <br>

    <div class="form-container">

        <?php if ($error): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-row">

                <div class="form-group">

                    <label>Activity Type *</label>

                    <select name="type" required>

                        <option value="">-- Select Type --</option>

                        <option value="Call"
                            <?php echo (($_POST["type"] ?? "") == "Call") ? "selected" : ""; ?>>
                            Call
                        </option>

                        <option value="Meeting"
                            <?php echo (($_POST["type"] ?? "") == "Meeting") ? "selected" : ""; ?>>
                            Meeting
                        </option>

                        <option value="Email"
                            <?php echo (($_POST["type"] ?? "") == "Email") ? "selected" : ""; ?>>
                            Email
                        </option>

                        <option value="Note"
                            <?php echo (($_POST["type"] ?? "") == "Note") ? "selected" : ""; ?>>
                            Note
                        </option>

                        <option value="Follow-up"
                            <?php echo (($_POST["type"] ?? "") == "Follow-up") ? "selected" : ""; ?>>
                            Follow-up
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Activity Date</label>

                    <input
                        type="datetime-local"
                        name="activity_date"
                        value="<?php echo htmlspecialchars($_POST["activity_date"] ?? ""); ?>"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>Subject *</label>

                <input
                    type="text"
                    name="subject"
                    value="<?php echo htmlspecialchars($_POST["subject"] ?? ""); ?>"
                    placeholder="Enter activity subject"
                    required
                >

            </div>


            <div class="form-group">

                <label>Description</label>

                <textarea
                    name="description"
                    placeholder="Enter activity details"
                ><?php echo htmlspecialchars($_POST["description"] ?? ""); ?></textarea>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Contact</label>

                    <select name="contact_id">

                        <option value="">-- Select Contact --</option>

                        <?php foreach ($contacts as $contact): ?>

                            <option
                                value="<?php echo $contact["id"]; ?>"
                                <?php echo (($_POST["contact_id"] ?? "") == $contact["id"]) ? "selected" : ""; ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    trim(
                                        $contact["first_name"] . " " .
                                        $contact["last_name"]
                                    )
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Customer</label>

                    <select name="customer_id">

                        <option value="">-- Select Customer --</option>

                        <?php foreach ($customers as $customer): ?>

                            <option
                                value="<?php echo $customer["id"]; ?>"
                                <?php echo (($_POST["customer_id"] ?? "") == $customer["id"]) ? "selected" : ""; ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $customer["customer_code"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <div class="form-group">

                <label>Deal</label>

                <select name="deal_id">

                    <option value="">-- Select Deal --</option>

                    <?php foreach ($deals as $deal): ?>

                        <option
                            value="<?php echo $deal["id"]; ?>"
                            <?php echo (($_POST["deal_id"] ?? "") == $deal["id"]) ? "selected" : ""; ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $deal["title"]
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="buttons">

                <button type="submit" class="btn-save">
                    Save Activity
                </button>

                <a href="index.php" class="btn-cancel">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>