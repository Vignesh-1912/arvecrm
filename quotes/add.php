<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";


// Generate quote number
$quote_number = "QT-" . date("Ymd-His");


// Get Companies
$stmt = $conn->prepare("
    SELECT id, company_name
    FROM companies
    ORDER BY company_name ASC
");
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Contacts
$stmt = $conn->prepare("
    SELECT id, first_name, last_name
    FROM contacts
    ORDER BY first_name ASC
");
$stmt->execute();
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Customers
$stmt = $conn->prepare("
    SELECT id, customer_code
    FROM customers
    ORDER BY customer_code ASC
");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get Deals
$stmt = $conn->prepare("
    SELECT id, title
    FROM deals
    ORDER BY title ASC
");
$stmt->execute();
$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Save Quote
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quote_number = trim($_POST["quote_number"]);
    $company_id = !empty($_POST["company_id"]) ? (int)$_POST["company_id"] : null;
    $contact_id = !empty($_POST["contact_id"]) ? (int)$_POST["contact_id"] : null;
    $customer_id = !empty($_POST["customer_id"]) ? (int)$_POST["customer_id"] : null;
    $deal_id = !empty($_POST["deal_id"]) ? (int)$_POST["deal_id"] : null;

    $discount_amount = is_numeric($_POST["discount_amount"])
        ? (float)$_POST["discount_amount"]
        : 0;

    $tax_amount = is_numeric($_POST["tax_amount"])
        ? (float)$_POST["tax_amount"]
        : 0;

    $status = trim($_POST["status"]);
    $valid_until = !empty($_POST["valid_until"])
        ? $_POST["valid_until"]
        : null;

    $notes = trim($_POST["notes"]);


    // Validation
    if ($quote_number === "") {

        $error = "Quote number is required.";

    } elseif ($discount_amount < 0) {

        $error = "Discount cannot be negative.";

    } elseif ($tax_amount < 0) {

        $error = "Tax cannot be negative.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Initial quote values
            |--------------------------------------------------------------------------
            |
            | Products/items will be added later.
            |
            */

            $subtotal = 0;

            $total_amount = $subtotal + $tax_amount - $discount_amount;

            if ($total_amount < 0) {
                $total_amount = 0;
            }


            $sql = "
                INSERT INTO quotes (
                    quote_number,
                    company_id,
                    contact_id,
                    customer_id,
                    deal_id,
                    subtotal,
                    tax_amount,
                    discount_amount,
                    total_amount,
                    status,
                    valid_until,
                    notes,
                    created_by
                )
                VALUES (
                    :quote_number,
                    :company_id,
                    :contact_id,
                    :customer_id,
                    :deal_id,
                    :subtotal,
                    :tax_amount,
                    :discount_amount,
                    :total_amount,
                    :status,
                    :valid_until,
                    :notes,
                    :created_by
                )
            ";


            $stmt = $conn->prepare($sql);

            $stmt->execute([

                ":quote_number" => $quote_number,

                ":company_id" => $company_id,

                ":contact_id" => $contact_id,

                ":customer_id" => $customer_id,

                ":deal_id" => $deal_id,

                ":subtotal" => $subtotal,

                ":tax_amount" => $tax_amount,

                ":discount_amount" => $discount_amount,

                ":total_amount" => $total_amount,

                ":status" => $status,

                ":valid_until" => $valid_until,

                ":notes" => $notes,

                ":created_by" => $_SESSION["user_id"]

            ]);


            $quote_id = $conn->lastInsertId();


            // Redirect to quote view
            header("Location: view.php?id=" . $quote_id);
            exit;


        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $error = "Quote number already exists. Please use a different quote number.";

            } else {

                $error = "Unable to create quote: " . $e->getMessage();

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Add Quote - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

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


        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group.full {

            grid-column: 1 / -1;

        }


        label {

            font-weight: bold;

            margin-bottom: 7px;

        }


        input,
        select,
        textarea {

            padding: 11px;

            border: 1px solid #d1d5db;

            border-radius: 6px;

            font-size: 14px;

        }


        textarea {

            min-height: 120px;

            resize: vertical;

        }


        .error {

            background: #fee2e2;

            color: #991b1b;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .form-actions {

            margin-top: 25px;

            display: flex;

            gap: 10px;

        }


        .btn {

            display: inline-block;

            padding: 11px 20px;

            border-radius: 6px;

            text-decoration: none;

            border: none;

            cursor: pointer;

            font-size: 14px;

        }


        .btn-save {

            background: #2563eb;

            color: white;

        }


        .btn-back {

            background: #6b7280;

            color: white;

        }


        .btn-save:hover {

            background: #1d4ed8;

        }


        .btn-back:hover {

            background: #4b5563;

        }


        @media (max-width: 768px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

            .form-group.full {

                grid-column: auto;

            }

        }

    </style>

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="main-content">


    <div class="form-card">


        <div class="form-header">

            <h1>Add Quote</h1>

        </div>


        <?php if ($error): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-grid">


                <!-- Quote Number -->

                <div class="form-group">

                    <label for="quote_number">
                        Quote Number *
                    </label>

                    <input
                        type="text"
                        id="quote_number"
                        name="quote_number"
                        value="<?php echo htmlspecialchars($quote_number); ?>"
                        required
                    >

                </div>


                <!-- Status -->

                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="draft">
                            Draft
                        </option>

                        <option value="sent">
                            Sent
                        </option>

                        <option value="accepted">
                            Accepted
                        </option>

                        <option value="rejected">
                            Rejected
                        </option>

                        <option value="expired">
                            Expired
                        </option>

                    </select>

                </div>


                <!-- Company -->

                <div class="form-group">

                    <label for="company_id">
                        Company
                    </label>

                    <select
                        id="company_id"
                        name="company_id"
                    >

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


                <!-- Contact -->

                <div class="form-group">

                    <label for="contact_id">
                        Contact
                    </label>

                    <select
                        id="contact_id"
                        name="contact_id"
                    >

                        <option value="">
                            -- Select Contact --
                        </option>

                        <?php foreach ($contacts as $contact): ?>

                            <option
                                value="<?php echo $contact["id"]; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    trim(
                                        $contact["first_name"]
                                        . " "
                                        . ($contact["last_name"] ?? "")
                                    )
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Customer -->

                <div class="form-group">

                    <label for="customer_id">
                        Customer
                    </label>

                    <select
                        id="customer_id"
                        name="customer_id"
                    >

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
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Deal -->

                <div class="form-group">

                    <label for="deal_id">
                        Deal
                    </label>

                    <select
                        id="deal_id"
                        name="deal_id"
                    >

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
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Tax -->

                <div class="form-group">

                    <label for="tax_amount">
                        Tax Amount
                    </label>

                    <input
                        type="number"
                        id="tax_amount"
                        name="tax_amount"
                        value="0"
                        min="0"
                        step="0.01"
                    >

                </div>


                <!-- Discount -->

                <div class="form-group">

                    <label for="discount_amount">
                        Discount Amount
                    </label>

                    <input
                        type="number"
                        id="discount_amount"
                        name="discount_amount"
                        value="0"
                        min="0"
                        step="0.01"
                    >

                </div>


                <!-- Valid Until -->

                <div class="form-group">

                    <label for="valid_until">
                        Valid Until
                    </label>

                    <input
                        type="date"
                        id="valid_until"
                        name="valid_until"
                    >

                </div>


                <!-- Notes -->

                <div class="form-group full">

                    <label for="notes">
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        placeholder="Enter quote notes..."
                    ></textarea>

                </div>


            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-save"
                >
                    Save Quote
                </button>


                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    Cancel
                </a>

            </div>


        </form>


    </div>


</div>


</body>

</html>