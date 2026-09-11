<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

// Load users
$user_stmt = $conn->prepare("
    SELECT id, name
    FROM users
    WHERE status = 1
    ORDER BY name ASC
");
$user_stmt->execute();
$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

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

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_date = !empty($_POST["due_date"]) ? $_POST["due_date"] : null;
    $priority = trim($_POST["priority"] ?? "medium");
    $status = trim($_POST["status"] ?? "pending");

    $assigned_to = !empty($_POST["assigned_to"])
        ? (int) $_POST["assigned_to"]
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


    if ($title == "") {

        $error = "Task title is required.";

    } else {

        try {

            $sql = "
                INSERT INTO tasks
                (
                    title,
                    description,
                    due_date,
                    priority,
                    status,
                    assigned_to,
                    contact_id,
                    customer_id,
                    deal_id
                )
                VALUES
                (
                    :title,
                    :description,
                    :due_date,
                    :priority,
                    :status,
                    :assigned_to,
                    :contact_id,
                    :customer_id,
                    :deal_id
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":title" => $title,
                ":description" => $description ?: null,
                ":due_date" => $due_date,
                ":priority" => $priority,
                ":status" => $status,
                ":assigned_to" => $assigned_to,
                ":contact_id" => $contact_id,
                ":customer_id" => $customer_id,
                ":deal_id" => $deal_id
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to add task: " . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Add Task</title>

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

    <h1>Add Task</h1>

    <br>

    <div class="form-container">

        <?php if ($error): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label>Task Title *</label>

                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($_POST["title"] ?? ""); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Description</label>

                <textarea name="description"><?php
                    echo htmlspecialchars($_POST["description"] ?? "");
                ?></textarea>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Due Date</label>

                    <input
                        type="date"
                        name="due_date"
                        value="<?php echo htmlspecialchars($_POST["due_date"] ?? ""); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Priority</label>

                    <select name="priority">

                        <option value="low"
                            <?php echo (($_POST["priority"] ?? "") == "low") ? "selected" : ""; ?>>
                            Low
                        </option>

                        <option value="medium"
                            <?php echo (($_POST["priority"] ?? "medium") == "medium") ? "selected" : ""; ?>>
                            Medium
                        </option>

                        <option value="high"
                            <?php echo (($_POST["priority"] ?? "") == "high") ? "selected" : ""; ?>>
                            High
                        </option>

                        <option value="urgent"
                            <?php echo (($_POST["priority"] ?? "") == "urgent") ? "selected" : ""; ?>>
                            Urgent
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="pending">Pending</option>

                        <option value="in-progress">In Progress</option>

                        <option value="completed">Completed</option>

                        <option value="cancelled">Cancelled</option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Assigned To</label>

                    <select name="assigned_to">

                        <option value="">-- Select User --</option>

                        <?php foreach ($users as $user): ?>

                            <option
                                value="<?php echo $user["id"]; ?>"
                                <?php echo (($_POST["assigned_to"] ?? "") == $user["id"]) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($user["name"]); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

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
                                <?php echo htmlspecialchars($customer["customer_code"]); ?>
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
                            <?php echo htmlspecialchars($deal["title"]); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="buttons">

                <button type="submit" class="btn-save">
                    Save Task
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