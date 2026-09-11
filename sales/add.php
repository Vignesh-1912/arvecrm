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
| Load Customers
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        customers.id,
        customers.customer_code,
        companies.company_name
    FROM customers
    LEFT JOIN companies
        ON customers.company_id = companies.id
    ORDER BY customers.id ASC
");

$stmt->execute();

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load Companies
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        company_name
    FROM companies
    ORDER BY company_name ASC
");

$stmt->execute();

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load Contacts
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        contacts.id,
        contacts.first_name,
        contacts.last_name,
        companies.company_name
    FROM contacts
    LEFT JOIN companies
        ON contacts.company_id = companies.id
    ORDER BY contacts.id ASC
");

$stmt->execute();

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load Quotes
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        quote_number,
        total_amount
    FROM quotes
    ORDER BY id DESC
");

$stmt->execute();

$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Load Deals
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        amount
    FROM deals
    ORDER BY id DESC
");

$stmt->execute();

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Generate Sale Number
|--------------------------------------------------------------------------
*/

$sale_number = "SALE-" . date("Ymd-His");


/*
|--------------------------------------------------------------------------
| Add Sale
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sale_number = trim($_POST["sale_number"]);
    $customer_id = !empty($_POST["customer_id"])
        ? (int) $_POST["customer_id"]
        : null;

    $company_id = !empty($_POST["company_id"])
        ? (int) $_POST["company_id"]
        : null;

    $contact_id = !empty($_POST["contact_id"])
        ? (int) $_POST["contact_id"]
        : null;

    $quote_id = !empty($_POST["quote_id"])
        ? (int) $_POST["quote_id"]
        : null;

    $deal_id = !empty($_POST["deal_id"])
        ? (int) $_POST["deal_id"]
        : null;

    $payment_status = $_POST["payment_status"] ?? "pending";
    $sale_status = $_POST["sale_status"] ?? "pending";

    $sale_date = !empty($_POST["sale_date"])
        ? $_POST["sale_date"]
        : date("Y-m-d");

    $notes = trim($_POST["notes"]);


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (empty($sale_number)) {

        $error = "Sale number is required.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | If Quote Selected
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $total_amount = 0;

            if ($quote_id) {

                $stmt = $conn->prepare("
                    SELECT
                        subtotal,
                        tax_amount,
                        discount_amount,
                        total_amount
                    FROM quotes
                    WHERE id = :id
                ");

                $stmt->execute([
                    ":id" => $quote_id
                ]);

                $quote = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($quote) {

                    $subtotal = (float) $quote["subtotal"];
                    $tax_amount = (float) $quote["tax_amount"];
                    $discount_amount = (float) $quote["discount_amount"];
                    $total_amount = (float) $quote["total_amount"];

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Insert Sale
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO sales (
                    sale_number,
                    customer_id,
                    company_id,
                    contact_id,
                    quote_id,
                    deal_id,
                    subtotal,
                    tax_amount,
                    discount_amount,
                    total_amount,
                    payment_status,
                    sale_status,
                    sale_date,
                    notes,
                    created_by
                )
                VALUES (
                    :sale_number,
                    :customer_id,
                    :company_id,
                    :contact_id,
                    :quote_id,
                    :deal_id,
                    :subtotal,
                    :tax_amount,
                    :discount_amount,
                    :total_amount,
                    :payment_status,
                    :sale_status,
                    :sale_date,
                    :notes,
                    :created_by
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":sale_number" => $sale_number,
                ":customer_id" => $customer_id,
                ":company_id" => $company_id,
                ":contact_id" => $contact_id,
                ":quote_id" => $quote_id,
                ":deal_id" => $deal_id,
                ":subtotal" => $subtotal,
                ":tax_amount" => $tax_amount,
                ":discount_amount" => $discount_amount,
                ":total_amount" => $total_amount,
                ":payment_status" => $payment_status,
                ":sale_status" => $sale_status,
                ":sale_date" => $sale_date,
                ":notes" => $notes,
                ":created_by" => $_SESSION["user_id"]
            ]);

            $sale_id = $conn->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | Redirect To Sale View
            |--------------------------------------------------------------------------
            */

            header(
                "Location: view.php?id=" . $sale_id
            );

            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $error = "Sale number already exists.";

            } else {

                $error = "Unable to create sale: " . $e->getMessage();

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Add Sale - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        .form-container {
            max-width: 900px;
            background: white;
            padding: 30px;
            border-radius: 8px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
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
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            margin-top: 25px;
        }

        .save-button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .save-button:hover {
            background: #1d4ed8;
        }

        .cancel-button {
            display: inline-block;
            margin-left: 10px;
            padding: 10px 18px;
            background: #e5e7eb;
            color: #111827;
            text-decoration: none;
            border-radius: 6px;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .info-message {
            background: #eff6ff;
            color: #1e40af;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {

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

    <div class="page-header">

        <h1>Add Sale</h1>

        <p>Create a new sales record</p>

    </div>


    <?php if (!empty($error)): ?>

        <div class="error-message">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="info-message">

        If you select a quote, its financial totals will be copied
        into this sale. Products can be managed after creating the sale.

    </div>


    <div class="form-container">

        <form method="POST">


            <!-- Sale Number -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Sale Number *
                    </label>

                    <input
                        type="text"
                        name="sale_number"
                        value="<?php echo htmlspecialchars($sale_number); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Sale Date
                    </label>

                    <input
                        type="date"
                        name="sale_date"
                        value="<?php echo date("Y-m-d"); ?>"
                    >

                </div>

            </div>


            <!-- Customer / Company -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Customer
                    </label>

                    <select name="customer_id">

                        <option value="">
                            -- Select Customer --
                        </option>

                        <?php foreach ($customers as $customer): ?>

                            <option
                                value="<?php echo $customer["id"]; ?>"
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
                            >

                                <?php
                                echo htmlspecialchars(
                                    $company["company_name"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Contact / Quote -->

            <div class="form-row">

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
                            >

                                <?php

                                $contact_name =
                                    trim(
                                        $contact["first_name"] .
                                        " " .
                                        $contact["last_name"]
                                    );

                                echo htmlspecialchars(
                                    $contact_name
                                );

                                if (!empty($contact["company_name"])) {

                                    echo " - " .
                                        htmlspecialchars(
                                            $contact["company_name"]
                                        );

                                }

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Quote
                    </label>

                    <select name="quote_id">

                        <option value="">
                            -- Select Quote --
                        </option>

                        <?php foreach ($quotes as $quote): ?>

                            <option
                                value="<?php echo $quote["id"]; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $quote["quote_number"]
                                );

                                echo " - ₹" .
                                    number_format(
                                        (float) $quote["total_amount"],
                                        2
                                    );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Deal / Payment -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Deal
                    </label>

                    <select name="deal_id">

                        <option value="">
                            -- Select Deal --
                        </option>

                        <?php foreach ($deals as $deal): ?>

                            <option
                                value="<?php echo $deal["id"]; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $deal["title"]
                                );

                                echo " - ₹" .
                                    number_format(
                                        (float) $deal["amount"],
                                        2
                                    );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Payment Status
                    </label>

                    <select name="payment_status">

                        <option value="pending">
                            Pending
                        </option>

                        <option value="partial">
                            Partial
                        </option>

                        <option value="paid">
                            Paid
                        </option>

                    </select>

                </div>

            </div>


            <!-- Sale Status -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Sale Status
                    </label>

                    <select name="sale_status">

                        <option value="pending">
                            Pending
                        </option>

                        <option value="completed">
                            Completed
                        </option>

                        <option value="cancelled">
                            Cancelled
                        </option>

                    </select>

                </div>

            </div>


            <!-- Notes -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        placeholder="Enter sale notes..."
                    ></textarea>

                </div>

            </div>


            <!-- Buttons -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="save-button"
                >
                    Save Sale
                </button>

                <a
                    href="index.php"
                    class="cancel-button"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>

</div>

</body>

</html>