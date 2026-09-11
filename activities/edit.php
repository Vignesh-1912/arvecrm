<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

$error = "";

/* Get Activity */
$sql = "
    SELECT *
    FROM activities
    WHERE id = :id
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id" => $id
]);

$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    header("Location: index.php");
    exit;
}

/* Get Contacts */
$contacts = $conn->query("
    SELECT id, first_name, last_name
    FROM contacts
    ORDER BY first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* Get Customers */
$customers = $conn->query("
    SELECT id, customer_code
    FROM customers
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* Get Deals */
$deals = $conn->query("
    SELECT id, title
    FROM deals
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);


/* Update Activity */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $type = trim($_POST["type"]);
    $subject = trim($_POST["subject"]);
    $description = trim($_POST["description"]);

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


    if (empty($type) || empty($subject)) {

        $error = "Please enter Activity Type and Subject.";

    } else {

        try {

            $sql = "
                UPDATE activities
                SET
                    contact_id = :contact_id,
                    customer_id = :customer_id,
                    deal_id = :deal_id,
                    type = :type,
                    subject = :subject,
                    description = :description,
                    activity_date = :activity_date
                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":contact_id" => $contact_id,
                ":customer_id" => $customer_id,
                ":deal_id" => $deal_id,
                ":type" => $type,
                ":subject" => $subject,
                ":description" => $description,
                ":activity_date" => $activity_date,
                ":id" => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            $error = "Unable to update activity: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>

    <title>Edit Activity</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .form-container {
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .button-group {
            margin-top: 25px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .back-button {
            display: inline-block;
            margin-left: 10px;
            padding: 12px 22px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .back-button:hover {
            background: #4b5563;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="form-container">

        <h1>✏️ Edit Activity</h1>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <!-- Activity Type -->

            <div class="form-group">

                <label>Activity Type *</label>

                <select name="type" required>

                    <option value="">Select Activity Type</option>

                    <option value="Call"
                        <?php echo ($activity["type"] == "Call") ? "selected" : ""; ?>>
                        Call
                    </option>

                    <option value="Meeting"
                        <?php echo ($activity["type"] == "Meeting") ? "selected" : ""; ?>>
                        Meeting
                    </option>

                    <option value="Email"
                        <?php echo ($activity["type"] == "Email") ? "selected" : ""; ?>>
                        Email
                    </option>

                    <option value="Note"
                        <?php echo ($activity["type"] == "Note") ? "selected" : ""; ?>>
                        Note
                    </option>

                    <option value="Follow-up"
                        <?php echo ($activity["type"] == "Follow-up") ? "selected" : ""; ?>>
                        Follow-up
                    </option>

                </select>

            </div>


            <!-- Subject -->

            <div class="form-group">

                <label>Subject *</label>

                <input
                    type="text"
                    name="subject"
                    value="<?php echo htmlspecialchars($activity["subject"]); ?>"
                    required
                >

            </div>


            <!-- Description -->

            <div class="form-group">

                <label>Description</label>

                <textarea name="description"><?php
                    echo htmlspecialchars($activity["description"] ?? "");
                ?></textarea>

            </div>


            <!-- Activity Date -->

            <div class="form-group">

                <label>Activity Date</label>

                <input
                    type="datetime-local"
                    name="activity_date"
                    value="<?php
                        echo !empty($activity["activity_date"])
                            ? date("Y-m-d\TH:i", strtotime($activity["activity_date"]))
                            : "";
                    ?>"
                >

            </div>


            <!-- Contact -->

            <div class="form-group">

                <label>Contact</label>

                <select name="contact_id">

                    <option value="">Select Contact</option>

                    <?php foreach ($contacts as $contact): ?>

                        <option
                            value="<?php echo $contact["id"]; ?>"
                            <?php
                                echo (
                                    $activity["contact_id"] == $contact["id"]
                                ) ? "selected" : "";
                            ?>
                        >

                            <?php
                                echo htmlspecialchars(
                                    $contact["first_name"] . " " . $contact["last_name"]
                                );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Customer -->

            <div class="form-group">

                <label>Customer</label>

                <select name="customer_id">

                    <option value="">Select Customer</option>

                    <?php foreach ($customers as $customer): ?>

                        <option
                            value="<?php echo $customer["id"]; ?>"
                            <?php
                                echo (
                                    $activity["customer_id"] == $customer["id"]
                                ) ? "selected" : "";
                            ?>
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


            <!-- Deal -->

            <div class="form-group">

                <label>Deal</label>

                <select name="deal_id">

                    <option value="">Select Deal</option>

                    <?php foreach ($deals as $deal): ?>

                        <option
                            value="<?php echo $deal["id"]; ?>"
                            <?php
                                echo (
                                    $activity["deal_id"] == $deal["id"]
                                ) ? "selected" : "";
                            ?>
                        >

                            <?php echo htmlspecialchars($deal["title"]); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Buttons -->

            <div class="button-group">

                <button type="submit">
                    Update Activity
                </button>

                <a
                    href="view.php?id=<?php echo $id; ?>"
                    class="back-button"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>