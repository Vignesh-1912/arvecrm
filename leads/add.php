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
$lead_name = "";
$email = "";
$phone = "";
$source = "";
$status = "new";
$lead_value = "0.00";
$notes = "";
$assigned_to = "";


// Get Companies

$sql = "SELECT id, company_name
        FROM companies
        ORDER BY company_name ASC";

$stmt = $conn->query($sql);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Contacts

$sql = "SELECT
            id,
            first_name,
            last_name
        FROM contacts
        ORDER BY first_name ASC";

$stmt = $conn->query($sql);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
    $lead_name = trim($_POST["lead_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $source = trim($_POST["source"] ?? "");
    $status = trim($_POST["status"] ?? "new");
    $lead_value = trim($_POST["lead_value"] ?? "0.00");
    $notes = trim($_POST["notes"] ?? "");
    $assigned_to = trim($_POST["assigned_to"] ?? "");


    // Validation

    if (empty($lead_name)) {

        $error = "Lead name is required.";

    } elseif (!is_numeric($lead_value)) {

        $error = "Lead value must be a valid number.";

    } else {

        try {

            $sql = "INSERT INTO leads
                    (
                        company_id,
                        contact_id,
                        lead_name,
                        email,
                        phone,
                        source,
                        status,
                        lead_value,
                        notes,
                        assigned_to
                    )
                    VALUES
                    (
                        :company_id,
                        :contact_id,
                        :lead_name,
                        :email,
                        :phone,
                        :source,
                        :status,
                        :lead_value,
                        :notes,
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

                ":lead_name" => $lead_name,

                ":email" => $email,

                ":phone" => $phone,

                ":source" => $source,

                ":status" => $status,

                ":lead_value" => $lead_value,

                ":notes" => $notes,

                ":assigned_to" => !empty($assigned_to)
                    ? $assigned_to
                    : null
            ]);


            // Redirect

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to add lead: " . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Lead - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

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

        <h2>Add Lead</h2>


        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- Lead Name -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Lead Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="lead_name"
                        value="<?php echo htmlspecialchars($lead_name); ?>"
                        required
                    >

                </div>

            </div>


            <!-- Company / Contact -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Company
                    </label>

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

                    <label>
                        Contact
                    </label>

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


            <!-- Email / Phone -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone); ?>"
                    >

                </div>

            </div>


            <!-- Source / Status -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Lead Source
                    </label>

                    <select name="source">

                        <option value="">
                            -- Select Source --
                        </option>

                        <option
                            value="Website"
                            <?php echo ($source == "Website")
                                ? "selected"
                                : ""; ?>
                        >
                            Website
                        </option>

                        <option
                            value="Referral"
                            <?php echo ($source == "Referral")
                                ? "selected"
                                : ""; ?>
                        >
                            Referral
                        </option>

                        <option
                            value="Social Media"
                            <?php echo ($source == "Social Media")
                                ? "selected"
                                : ""; ?>
                        >
                            Social Media
                        </option>

                        <option
                            value="Email"
                            <?php echo ($source == "Email")
                                ? "selected"
                                : ""; ?>
                        >
                            Email
                        </option>

                        <option
                            value="Phone"
                            <?php echo ($source == "Phone")
                                ? "selected"
                                : ""; ?>
                        >
                            Phone
                        </option>

                        <option
                            value="Other"
                            <?php echo ($source == "Other")
                                ? "selected"
                                : ""; ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option
                            value="new"
                            <?php echo ($status == "new")
                                ? "selected"
                                : ""; ?>
                        >
                            New
                        </option>

                        <option
                            value="contacted"
                            <?php echo ($status == "contacted")
                                ? "selected"
                                : ""; ?>
                        >
                            Contacted
                        </option>

                        <option
                            value="qualified"
                            <?php echo ($status == "qualified")
                                ? "selected"
                                : ""; ?>
                        >
                            Qualified
                        </option>

                        <option
                            value="converted"
                            <?php echo ($status == "converted")
                                ? "selected"
                                : ""; ?>
                        >
                            Converted
                        </option>

                        <option
                            value="lost"
                            <?php echo ($status == "lost")
                                ? "selected"
                                : ""; ?>
                        >
                            Lost
                        </option>

                    </select>

                </div>

            </div>


            <!-- Lead Value / Assigned To -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Lead Value
                    </label>

                    <input
                        type="number"
                        name="lead_value"
                        value="<?php echo htmlspecialchars($lead_value); ?>"
                        step="0.01"
                        min="0"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Assigned To
                    </label>

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

                                <?php echo htmlspecialchars($user["name"]); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Notes -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Notes
                    </label>

                    <textarea name="notes"><?php
                        echo htmlspecialchars($notes);
                    ?></textarea>

                </div>

            </div>


            <!-- Buttons -->

            <div class="button-area">

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Lead
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