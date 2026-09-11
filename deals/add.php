<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

$company_id = "";
$contact_id = "";
$customer_id = "";
$title = "";
$amount = "0.00";
$stage = "prospecting";
$probability = "0";
$expected_close_date = "";
$description = "";
$assigned_to = "";


// Get Companies

$sql = "SELECT id, company_name
        FROM companies
        ORDER BY company_name ASC";

$stmt = $conn->query($sql);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Contacts

$sql = "SELECT id, first_name, last_name
        FROM contacts
        ORDER BY first_name ASC";

$stmt = $conn->query($sql);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Customers

$sql = "SELECT
            customers.id,
            customers.customer_code,
            companies.company_name
        FROM customers
        LEFT JOIN companies
            ON customers.company_id = companies.id
        ORDER BY customers.id ASC";

$stmt = $conn->query($sql);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Users

$sql = "SELECT id, name
        FROM users
        WHERE status = 1
        ORDER BY name ASC";

$stmt = $conn->query($sql);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Handle Form Submission

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $company_id = trim($_POST["company_id"] ?? "");
    $contact_id = trim($_POST["contact_id"] ?? "");
    $customer_id = trim($_POST["customer_id"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $amount = trim($_POST["amount"] ?? "0.00");
    $stage = trim($_POST["stage"] ?? "prospecting");
    $probability = trim($_POST["probability"] ?? "0");
    $expected_close_date = trim($_POST["expected_close_date"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $assigned_to = trim($_POST["assigned_to"] ?? "");


    // Validation

    if (empty($title)) {

        $error = "Deal title is required.";

    } elseif (!is_numeric($amount)) {

        $error = "Amount must be a valid number.";

    } elseif (!is_numeric($probability)) {

        $error = "Probability must be a valid number.";

    } elseif ($probability < 0 || $probability > 100) {

        $error = "Probability must be between 0 and 100.";

    } else {

        try {

            $sql = "INSERT INTO deals
                    (
                        company_id,
                        contact_id,
                        customer_id,
                        title,
                        amount,
                        stage,
                        probability,
                        expected_close_date,
                        description,
                        assigned_to
                    )
                    VALUES
                    (
                        :company_id,
                        :contact_id,
                        :customer_id,
                        :title,
                        :amount,
                        :stage,
                        :probability,
                        :expected_close_date,
                        :description,
                        :assigned_to
                    )";

            $stmt = $conn->prepare($sql);

            $stmt->execute([

                ":company_id" => !empty($company_id)
                    ? $company_id
                    : null,

                ":contact_id" => !empty($contact_id)
                    ? $contact_id
                    : null,

                ":customer_id" => !empty($customer_id)
                    ? $customer_id
                    : null,

                ":title" => $title,

                ":amount" => $amount,

                ":stage" => $stage,

                ":probability" => $probability,

                ":expected_close_date" => !empty($expected_close_date)
                    ? $expected_close_date
                    : null,

                ":description" => $description,

                ":assigned_to" => !empty($assigned_to)
                    ? $assigned_to
                    : null

            ]);


            header("Location: index.php");
            exit;


        } catch (PDOException $e) {

            $error = "Unable to add deal: " . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Deal - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .form-container h2 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 18px;
        }

        .form-group {
            flex: 1;
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
            border-radius: 5px;
            font-size: 14px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        .required {
            color: red;
        }

        .button-area {
            margin-top: 25px;
        }

        .save-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .cancel-btn {
            background: #6b7280;
            color: white;
            padding: 11px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 8px;
        }

        .cancel-btn:hover {
            background: #4b5563;
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

    <div class="form-container">

        <h2>Add Deal</h2>


        <?php if (!empty($error)): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- Deal Title -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Deal Title <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="title"
                        value="<?php echo htmlspecialchars($title); ?>"
                        required
                    >

                </div>

            </div>


            <!-- Company / Contact -->

            <div class="form-row">

                <div class="form-group">

                    <label>Company</label>

                    <select name="company_id">

                        <option value="">
                            -- Select Company --
                        </option>

                        <?php foreach ($companies as $company): ?>

                            <option
                                value="<?php echo $company["id"]; ?>"
                                <?php echo ($company_id == $company["id"])
                                    ? "selected"
                                    : ""; ?>
                            >

                                <?php echo htmlspecialchars(
                                    $company["company_name"]
                                ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Contact</label>

                    <select name="contact_id">

                        <option value="">
                            -- Select Contact --
                        </option>

                        <?php foreach ($contacts as $contact): ?>

                            <option
                                value="<?php echo $contact["id"]; ?>"
                                <?php echo ($contact_id == $contact["id"])
                                    ? "selected"
                                    : ""; ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $contact["first_name"] . " " .
                                    ($contact["last_name"] ?? "")
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Customer / Assigned To -->

            <div class="form-row">

                <div class="form-group">

                    <label>Customer</label>

                    <select name="customer_id">

                        <option value="">
                            -- Select Customer --
                        </option>

                        <?php foreach ($customers as $customer): ?>

                            <option
                                value="<?php echo $customer["id"]; ?>"
                                <?php echo ($customer_id == $customer["id"])
                                    ? "selected"
                                    : ""; ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $customer["customer_code"]
                                );

                                if (!empty($customer["company_name"])) {

                                    echo " - " .
                                        htmlspecialchars(
                                            $customer["company_name"]
                                        );

                                }

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Assigned To</label>

                    <select name="assigned_to">

                        <option value="">
                            -- Select User --
                        </option>

                        <?php foreach ($users as $user): ?>

                            <option
                                value="<?php echo $user["id"]; ?>"
                                <?php echo ($assigned_to == $user["id"])
                                    ? "selected"
                                    : ""; ?>
                            >

                                <?php echo htmlspecialchars(
                                    $user["name"]
                                ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Amount / Probability -->

            <div class="form-row">

                <div class="form-group">

                    <label>Deal Amount</label>

                    <input
                        type="number"
                        name="amount"
                        value="<?php echo htmlspecialchars($amount); ?>"
                        step="0.01"
                        min="0"
                    >

                </div>


                <div class="form-group">

                    <label>Probability (%)</label>

                    <input
                        type="number"
                        name="probability"
                        value="<?php echo htmlspecialchars($probability); ?>"
                        min="0"
                        max="100"
                    >

                </div>

            </div>


            <!-- Stage / Close Date -->

            <div class="form-row">

                <div class="form-group">

                    <label>Deal Stage</label>

                    <select name="stage">

                        <option
                            value="prospecting"
                            <?php echo ($stage == "prospecting")
                                ? "selected"
                                : ""; ?>
                        >
                            Prospecting
                        </option>

                        <option
                            value="qualification"
                            <?php echo ($stage == "qualification")
                                ? "selected"
                                : ""; ?>
                        >
                            Qualification
                        </option>

                        <option
                            value="proposal"
                            <?php echo ($stage == "proposal")
                                ? "selected"
                                : ""; ?>
                        >
                            Proposal
                        </option>

                        <option
                            value="negotiation"
                            <?php echo ($stage == "negotiation")
                                ? "selected"
                                : ""; ?>
                        >
                            Negotiation
                        </option>

                        <option
                            value="closed_won"
                            <?php echo ($stage == "closed_won")
                                ? "selected"
                                : ""; ?>
                        >
                            Closed Won
                        </option>

                        <option
                            value="closed_lost"
                            <?php echo ($stage == "closed_lost")
                                ? "selected"
                                : ""; ?>
                        >
                            Closed Lost
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Expected Close Date</label>

                    <input
                        type="date"
                        name="expected_close_date"
                        value="<?php echo htmlspecialchars(
                            $expected_close_date
                        ); ?>"
                    >

                </div>

            </div>


            <!-- Description -->

            <div class="form-row">

                <div class="form-group">

                    <label>Description</label>

                    <textarea name="description"><?php
                        echo htmlspecialchars($description);
                    ?></textarea>

                </div>

            </div>


            <!-- Buttons -->

            <div class="button-area">

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Deal
                </button>

                <a
                    href="index.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>