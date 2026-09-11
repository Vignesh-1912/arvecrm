<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";


// Check ID

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];


// Get contact

$sql = "SELECT * FROM contacts WHERE id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$contact = $stmt->fetch(PDO::FETCH_ASSOC);


// Contact not found

if (!$contact) {
    die("Contact not found.");
}


// Default values

$company_id = $contact["company_id"] ?? "";
$first_name = $contact["first_name"] ?? "";
$last_name = $contact["last_name"] ?? "";
$email = $contact["email"] ?? "";
$phone = $contact["phone"] ?? "";
$job_title = $contact["job_title"] ?? "";
$address = $contact["address"] ?? "";
$city = $contact["city"] ?? "";
$state = $contact["state"] ?? "";
$country = $contact["country"] ?? "";
$postal_code = $contact["postal_code"] ?? "";
$status = $contact["status"] ?? "active";


// Get companies

$sql = "SELECT id, company_name
        FROM companies
        ORDER BY company_name ASC";

$stmt = $conn->query($sql);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Update contact

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $company_id = trim($_POST["company_id"] ?? "");
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $job_title = trim($_POST["job_title"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $state = trim($_POST["state"] ?? "");
    $country = trim($_POST["country"] ?? "");
    $postal_code = trim($_POST["postal_code"] ?? "");
    $status = trim($_POST["status"] ?? "active");


    if (empty($first_name)) {

        $error = "First name is required.";

    } else {

        try {

            $sql = "UPDATE contacts SET

                        company_id = :company_id,
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        job_title = :job_title,
                        address = :address,
                        city = :city,
                        state = :state,
                        country = :country,
                        postal_code = :postal_code,
                        status = :status

                    WHERE id = :id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([

                ":company_id" => !empty($company_id)
                    ? $company_id
                    : null,

                ":first_name" => $first_name,

                ":last_name" => $last_name,

                ":email" => $email,

                ":phone" => $phone,

                ":job_title" => $job_title,

                ":address" => $address,

                ":city" => $city,

                ":state" => $state,

                ":country" => $country,

                ":postal_code" => $postal_code,

                ":status" => $status,

                ":id" => $id

            ]);


            // Redirect to view page

            header("Location: view.php?id=" . $id);

            exit;

        } catch (PDOException $e) {

            $error = "Unable to update contact: " . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Contact - CRM</title>

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
            min-height: 100px;
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

        .update-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .update-btn:hover {
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

        <h2>Edit Contact</h2>


        <?php if (!empty($error)): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- Company -->

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

            </div>


            <!-- First / Last Name -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        First Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="first_name"
                        value="<?php echo htmlspecialchars($first_name); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Last Name</label>

                    <input
                        type="text"
                        name="last_name"
                        value="<?php echo htmlspecialchars($last_name); ?>"
                    >

                </div>

            </div>


            <!-- Email / Phone -->

            <div class="form-row">

                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Phone</label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone); ?>"
                    >

                </div>

            </div>


            <!-- Job Title / Status -->

            <div class="form-row">

                <div class="form-group">

                    <label>Job Title</label>

                    <input
                        type="text"
                        name="job_title"
                        value="<?php echo htmlspecialchars($job_title); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option
                            value="active"
                            <?php echo ($status == "active")
                                ? "selected"
                                : ""; ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?php echo ($status == "inactive")
                                ? "selected"
                                : ""; ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

            </div>


            <!-- Address -->

            <div class="form-row">

                <div class="form-group">

                    <label>Address</label>

                    <textarea name="address"><?php
                        echo htmlspecialchars($address);
                    ?></textarea>

                </div>

            </div>


            <!-- City / State -->

            <div class="form-row">

                <div class="form-group">

                    <label>City</label>

                    <input
                        type="text"
                        name="city"
                        value="<?php echo htmlspecialchars($city); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>State</label>

                    <input
                        type="text"
                        name="state"
                        value="<?php echo htmlspecialchars($state); ?>"
                    >

                </div>

            </div>


            <!-- Country / Postal Code -->

            <div class="form-row">

                <div class="form-group">

                    <label>Country</label>

                    <input
                        type="text"
                        name="country"
                        value="<?php echo htmlspecialchars($country); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Postal Code</label>

                    <input
                        type="text"
                        name="postal_code"
                        value="<?php echo htmlspecialchars($postal_code); ?>"
                    >

                </div>

            </div>


            <!-- Buttons -->

            <div class="button-area">

                <button
                    type="submit"
                    class="update-btn"
                >
                    Update Contact
                </button>


                <a
                    href="view.php?id=<?php echo $id; ?>"
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