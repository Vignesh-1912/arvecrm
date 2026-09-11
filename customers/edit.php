<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";

/*
|--------------------------------------------------------------------------
| Check Customer ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;

}

$id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Customer Details
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM customers WHERE id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Customer Not Found
|--------------------------------------------------------------------------
*/

if (!$customer) {

    echo "Customer not found.";
    exit;

}


/*
|--------------------------------------------------------------------------
| Update Customer
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $customer_code = trim($_POST["customer_code"]);
    $customer_type = trim($_POST["customer_type"]);
    $status = trim($_POST["status"]);
    $credit_limit = trim($_POST["credit_limit"]);
    $notes = trim($_POST["notes"]);


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (empty($customer_code)) {

        $error = "Customer code is required.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Update Query
            |--------------------------------------------------------------------------
            */

            $sql = "UPDATE customers SET
                        customer_code = :customer_code,
                        customer_type = :customer_type,
                        status = :status,
                        credit_limit = :credit_limit,
                        notes = :notes
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":customer_code" => $customer_code,
                ":customer_type" => $customer_type,
                ":status" => $status,
                ":credit_limit" => $credit_limit,
                ":notes" => $notes,
                ":id" => $id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect After Successful Update
            |--------------------------------------------------------------------------
            */

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Duplicate Customer Code
            |--------------------------------------------------------------------------
            */

            if ($e->getCode() == 23000) {

                $error = "Customer code already exists.";

            } else {

                $error = "Error: " . $e->getMessage();

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Customer - CRM</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f4f6f9;

        }


        .container {

            width: 600px;

            max-width: 95%;

            margin: 50px auto;

            background: white;

            padding: 30px;

            border-radius: 8px;

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

            font-weight: bold;

            margin-bottom: 7px;

        }


        input,
        select,
        textarea {

            width: 100%;

            padding: 11px;

            border: 1px solid #ccc;

            border-radius: 5px;

            font-size: 15px;

        }


        textarea {

            height: 100px;

            resize: vertical;

        }


        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 25px;

        }


        .update-btn {

            background: #2563eb;

            color: white;

            border: none;

            padding: 12px 20px;

            border-radius: 5px;

            cursor: pointer;

            font-size: 15px;

        }


        .update-btn:hover {

            background: #1d4ed8;

        }


        .cancel-btn {

            background: #64748b;

            color: white;

            text-decoration: none;

            padding: 12px 20px;

            border-radius: 5px;

        }


        .error {

            background: #fee2e2;

            color: #b91c1c;

            padding: 10px;

            border-radius: 5px;

            margin-bottom: 15px;

        }


        .customer-id {

            background: #f1f5f9;

            padding: 10px;

            border-radius: 5px;

            margin-bottom: 20px;

            color: #475569;

        }

    </style>

</head>


<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">


    <div class="container">


        <h1>Edit Customer</h1>


        <div class="customer-id">

            Customer ID:
            <strong>
                <?php echo $customer["id"]; ?>
            </strong>

        </div>


        <?php if (!empty($error)): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- Customer Code -->

            <div class="form-group">

                <label>
                    Customer Code *
                </label>

                <input
                    type="text"
                    name="customer_code"
                    value="<?php echo htmlspecialchars($customer["customer_code"]); ?>"
                    placeholder="Example: CUST-001"
                    required
                >

            </div>


            <!-- Customer Type -->

            <div class="form-group">

                <label>
                    Customer Type
                </label>

                <select name="customer_type">

                    <option
                        value="business"
                        <?php
                        if ($customer["customer_type"] == "business") {
                            echo "selected";
                        }
                        ?>
                    >
                        Business
                    </option>

                    <option
                        value="individual"
                        <?php
                        if ($customer["customer_type"] == "individual") {
                            echo "selected";
                        }
                        ?>
                    >
                        Individual
                    </option>

                </select>

            </div>


            <!-- Status -->

            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option
                        value="active"
                        <?php
                        if ($customer["status"] == "active") {
                            echo "selected";
                        }
                        ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?php
                        if ($customer["status"] == "inactive") {
                            echo "selected";
                        }
                        ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- Credit Limit -->

            <div class="form-group">

                <label>
                    Credit Limit
                </label>

                <input
                    type="number"
                    name="credit_limit"
                    value="<?php echo htmlspecialchars($customer["credit_limit"]); ?>"
                    min="0"
                    step="0.01"
                >

            </div>


            <!-- Notes -->

            <div class="form-group">

                <label>
                    Notes
                </label>

                <textarea
                    name="notes"
                    placeholder="Enter customer notes..."
                ><?php echo htmlspecialchars($customer["notes"] ?? ""); ?></textarea>

            </div>


            <!-- Buttons -->

            <div class="buttons">

                <button
                    type="submit"
                    class="update-btn"
                >
                    Update Customer
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