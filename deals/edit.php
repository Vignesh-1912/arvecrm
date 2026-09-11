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

$error = "";

/* Get existing deal */
$sql = "SELECT * FROM deals WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([":id" => $id]);

$deal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deal) {
    die("Deal not found.");
}

/* Load companies */
$companyStmt = $conn->query(
    "SELECT id, company_name
     FROM companies
     ORDER BY company_name ASC"
);

$companies = $companyStmt->fetchAll(PDO::FETCH_ASSOC);

/* Load contacts */
$contactStmt = $conn->query(
    "SELECT id, first_name, last_name
     FROM contacts
     ORDER BY first_name ASC"
);

$contacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);

/* Load customers */
$customerStmt = $conn->query(
    "SELECT id, customer_code
     FROM customers
     ORDER BY customer_code ASC"
);

$customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

/* Load active users */
$userStmt = $conn->query(
    "SELECT id, name
     FROM users
     WHERE status = 1
     ORDER BY name ASC"
);

$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);


/* Update deal */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $company_id = !empty($_POST["company_id"])
        ? (int) $_POST["company_id"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int) $_POST["contact_id"]
        : null;

    $customer_id = !empty($_POST["customer_id"])
        ? (int) $_POST["customer_id"]
        : null;

    $title = trim($_POST["title"] ?? "");

    $amount = trim($_POST["amount"] ?? "0");

    $stage = trim($_POST["stage"] ?? "new");

    $probability = trim($_POST["probability"] ?? "0");

    $expected_close_date = !empty($_POST["expected_close_date"])
        ? $_POST["expected_close_date"]
        : null;

    $description = trim($_POST["description"] ?? "");

    $assigned_to = !empty($_POST["assigned_to"])
        ? (int) $_POST["assigned_to"]
        : null;


    /* Validation */

    if ($title === "") {

        $error = "Deal title is required.";

    } elseif (!is_numeric($amount)) {

        $error = "Amount must be a valid number.";

    } elseif (!is_numeric($probability)) {

        $error = "Probability must be a valid number.";

    } elseif ((int)$probability < 0 || (int)$probability > 100) {

        $error = "Probability must be between 0 and 100.";

    } else {

        try {

            $sql = "UPDATE deals SET

                        company_id = :company_id,
                        contact_id = :contact_id,
                        customer_id = :customer_id,
                        title = :title,
                        amount = :amount,
                        stage = :stage,
                        probability = :probability,
                        expected_close_date = :expected_close_date,
                        description = :description,
                        assigned_to = :assigned_to

                    WHERE id = :id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":company_id" => $company_id,
                ":contact_id" => $contact_id,
                ":customer_id" => $customer_id,
                ":title" => $title,
                ":amount" => $amount,
                ":stage" => $stage,
                ":probability" => (int)$probability,
                ":expected_close_date" => $expected_close_date,
                ":description" => $description,
                ":assigned_to" => $assigned_to,
                ":id" => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            $error = "Unable to update deal: " . $e->getMessage();

        }
    }

    /* Keep entered values after validation error */

    $deal["company_id"] = $company_id;
    $deal["contact_id"] = $contact_id;
    $deal["customer_id"] = $customer_id;
    $deal["title"] = $title;
    $deal["amount"] = $amount;
    $deal["stage"] = $stage;
    $deal["probability"] = $probability;
    $deal["expected_close_date"] = $expected_close_date;
    $deal["description"] = $description;
    $deal["assigned_to"] = $assigned_to;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Edit Deal - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 1000px;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .form-header h1 {
            margin: 0;
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
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            display: inline-block;
            padding: 11px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-update {
            background: #2563eb;
        }

        .btn-back {
            background: #6b7280;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {

            .form-row {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="form-card">

        <div class="form-header">

            <h1>Edit Deal</h1>

            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-back">
                Back
            </a>

        </div>


        <?php if ($error != ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-row">

                <div class="form-group">

                    <label>Deal Title *</label>

                    <input
                        type="text"
                        name="title"
                        value="<?php echo htmlspecialchars($deal["title"]); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Amount *</label>

                    <input
                        type="number"
                        name="amount"
                        step="0.01"
                        min="0"
                        value="<?php echo htmlspecialchars($deal["amount"]); ?>"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Company</label>

                    <select name="company_id">

                        <option value="">-- Select Company --</option>

                        <?php foreach ($companies as $company): ?>

                            <option
                                value="<?php echo $company["id"]; ?>"
                                <?php
                                echo ($deal["company_id"] == $company["id"])
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php echo htmlspecialchars($company["company_name"]); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Contact</label>

                    <select name="contact_id">

                        <option value="">-- Select Contact --</option>

                        <?php foreach ($contacts as $contact): ?>

                            <option
                                value="<?php echo $contact["id"]; ?>"
                                <?php
                                echo ($deal["contact_id"] == $contact["id"])
                                    ? "selected"
                                    : "";
                                ?>
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

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Customer</label>

                    <select name="customer_id">

                        <option value="">-- Select Customer --</option>

                        <?php foreach ($customers as $customer): ?>

                            <option
                                value="<?php echo $customer["id"]; ?>"
                                <?php
                                echo ($deal["customer_id"] == $customer["id"])
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php echo htmlspecialchars($customer["customer_code"]); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Assigned To</label>

                    <select name="assigned_to">

                        <option value="">-- Select User --</option>

                        <?php foreach ($users as $user): ?>

                            <option
                                value="<?php echo $user["id"]; ?>"
                                <?php
                                echo ($deal["assigned_to"] == $user["id"])
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php echo htmlspecialchars($user["name"]); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Stage</label>

                    <select name="stage">

                        <?php

                        $stages = [
                            "new" => "New",
                            "prospecting" => "Prospecting",
                            "qualification" => "Qualification",
                            "proposal" => "Proposal",
                            "negotiation" => "Negotiation",
                            "closed_won" => "Closed Won",
                            "closed_lost" => "Closed Lost"
                        ];

                        foreach ($stages as $value => $label):

                        ?>

                            <option
                                value="<?php echo $value; ?>"
                                <?php
                                echo ($deal["stage"] == $value)
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php echo $label; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Probability (%)</label>

                    <input
                        type="number"
                        name="probability"
                        min="0"
                        max="100"
                        value="<?php echo htmlspecialchars($deal["probability"]); ?>"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>Expected Close Date</label>

                <input
                    type="date"
                    name="expected_close_date"
                    value="<?php echo htmlspecialchars($deal["expected_close_date"] ?? ""); ?>"
                >

            </div>


            <div class="form-group">

                <label>Description</label>

                <textarea name="description"><?php echo htmlspecialchars($deal["description"] ?? ""); ?></textarea>

            </div>


            <button type="submit" class="btn btn-update">
                Update Deal
            </button>

            <a
                href="view.php?id=<?php echo $id; ?>"
                class="btn btn-back"
            >
                Cancel
            </a>

        </form>

    </div>

</div>

</body>

</html>