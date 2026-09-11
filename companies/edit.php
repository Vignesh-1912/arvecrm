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


// Get existing company
$sql = "SELECT * FROM companies WHERE id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die("Company not found.");
}


// Default values
$company_name = $company["company_name"] ?? "";
$industry = $company["industry"] ?? "";
$phone = $company["phone"] ?? "";
$email = $company["email"] ?? "";
$website = $company["website"] ?? "";
$address = $company["address"] ?? "";
$city = $company["city"] ?? "";
$state = $company["state"] ?? "";
$country = $company["country"] ?? "";
$postal_code = $company["postal_code"] ?? "";

$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $company_name = trim($_POST["company_name"] ?? "");
    $industry = trim($_POST["industry"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $website = trim($_POST["website"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $state = trim($_POST["state"] ?? "");
    $country = trim($_POST["country"] ?? "");
    $postal_code = trim($_POST["postal_code"] ?? "");


    if (empty($company_name)) {

        $error = "Company name is required.";

    } else {

        try {

            $sql = "UPDATE companies SET
                        company_name = :company_name,
                        industry = :industry,
                        phone = :phone,
                        email = :email,
                        website = :website,
                        address = :address,
                        city = :city,
                        state = :state,
                        country = :country,
                        postal_code = :postal_code
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":company_name" => $company_name,
                ":industry" => $industry,
                ":phone" => $phone,
                ":email" => $email,
                ":website" => $website,
                ":address" => $address,
                ":city" => $city,
                ":state" => $state,
                ":country" => $country,
                ":postal_code" => $postal_code,
                ":id" => $id
            ]);

            header("Location: view.php?id=" . $id);
            exit;

        } catch (PDOException $e) {

            $error = "Unable to update company: " . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Company - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .company-container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        h2 {
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
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            box-sizing: border-box;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            background: #2563eb;
            color: white;
            padding: 11px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .back-btn {
            background: #6b7280;
            margin-left: 8px;
        }

        .back-btn:hover {
            background: #4b5563;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="company-container">

        <h2>Edit Company</h2>


        <?php if (!empty($error)): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label>Company Name *</label>

                <input
                    type="text"
                    name="company_name"
                    value="<?php echo htmlspecialchars($company_name); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Industry</label>

                <input
                    type="text"
                    name="industry"
                    value="<?php echo htmlspecialchars($industry); ?>"
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


            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                >

            </div>


            <div class="form-group">

                <label>Website</label>

                <input
                    type="text"
                    name="website"
                    value="<?php echo htmlspecialchars($website); ?>"
                >

            </div>


            <div class="form-group">

                <label>Address</label>

                <textarea
                    name="address"
                ><?php echo htmlspecialchars($address); ?></textarea>

            </div>


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


            <button type="submit" class="btn">
                Update Company
            </button>


            <a
                href="view.php?id=<?php echo $id; ?>"
                class="btn back-btn"
            >
                Cancel
            </a>


        </form>

    </div>

</div>

</body>

</html>