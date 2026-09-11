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


/*
|--------------------------------------------------------------------------
| Get Quote
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT *
    FROM quotes
    WHERE id = :id
");

$stmt->execute([
    ":id" => $id
]);

$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    die("Quote not found.");
}


/*
|--------------------------------------------------------------------------
| Get Companies
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, company_name
    FROM companies
    ORDER BY company_name ASC
");

$stmt->execute();

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Contacts
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, first_name, last_name
    FROM contacts
    ORDER BY first_name ASC
");

$stmt->execute();

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Customers
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, customer_code
    FROM customers
    ORDER BY customer_code ASC
");

$stmt->execute();

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Deals
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, title
    FROM deals
    ORDER BY title ASC
");

$stmt->execute();

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Update Quote
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quote_number = trim($_POST["quote_number"]);

    $company_id = !empty($_POST["company_id"])
        ? (int) $_POST["company_id"]
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

    $status = trim($_POST["status"]);

    $valid_until = !empty($_POST["valid_until"])
        ? $_POST["valid_until"]
        : null;

    $notes = trim($_POST["notes"]);


    if ($quote_number === "") {

        $error = "Quote number is required.";

    } else {

        try {

            $sql = "
                UPDATE quotes
                SET
                    quote_number = :quote_number,
                    company_id = :company_id,
                    contact_id = :contact_id,
                    customer_id = :customer_id,
                    deal_id = :deal_id,
                    status = :status,
                    valid_until = :valid_until,
                    notes = :notes
                WHERE id = :id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([

                ":quote_number" => $quote_number,

                ":company_id" => $company_id,

                ":contact_id" => $contact_id,

                ":customer_id" => $customer_id,

                ":deal_id" => $deal_id,

                ":status" => $status,

                ":valid_until" => $valid_until,

                ":notes" => $notes,

                ":id" => $id

            ]);


            header("Location: view.php?id=" . $id);
            exit;


        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $error = "Quote number already exists.";

            } else {

                $error = "Unable to update quote: "
                    . $e->getMessage();

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Edit Quote - CRM</title>

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

            <h1>
                Edit Quote
            </h1>

        </div>


        <?php if ($error): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-grid">


                <!-- Quote Number -->

                <div class="form-group">

                    <label>
                        Quote Number *
                    </label>

                    <input
                        type="text"
                        name="quote_number"
                        value="<?php
                        echo htmlspecialchars(
                            $quote["quote_number"]
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- Status -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <?php

                        $statuses = [
                            "draft",
                            "sent",
                            "accepted",
                            "rejected",
                            "expired"
                        ];

                        ?>

                        <?php foreach ($statuses as $status): ?>

                            <option
                                value="<?php echo $status; ?>"
                                <?php
                                echo (
                                    $quote["status"]
                                    === $status
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo ucfirst($status);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Company -->

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
                                value="<?php
                                echo $company["id"];
                                ?>"
                                <?php
                                echo (
                                    $quote["company_id"]
                                    == $company["id"]
                                )
                                    ? "selected"
                                    : "";
                                ?>
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

                    <label>
                        Contact
                    </label>

                    <select name="contact_id">

                        <option value="">
                            -- Select Contact --
                        </option>

                        <?php foreach ($contacts as $contact): ?>

                            <option
                                value="<?php
                                echo $contact["id"];
                                ?>"
                                <?php
                                echo (
                                    $quote["contact_id"]
                                    == $contact["id"]
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    trim(
                                        $contact["first_name"]
                                        . " "
                                        . (
                                            $contact["last_name"]
                                            ?? ""
                                        )
                                    )
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Customer -->

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
                                value="<?php
                                echo $customer["id"];
                                ?>"
                                <?php
                                echo (
                                    $quote["customer_id"]
                                    == $customer["id"]
                                )
                                    ? "selected"
                                    : "";
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

                    <label>
                        Deal
                    </label>

                    <select name="deal_id">

                        <option value="">
                            -- Select Deal --
                        </option>

                        <?php foreach ($deals as $deal): ?>

                            <option
                                value="<?php
                                echo $deal["id"];
                                ?>"
                                <?php
                                echo (
                                    $quote["deal_id"]
                                    == $deal["id"]
                                )
                                    ? "selected"
                                    : "";
                                ?>
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


                <!-- Valid Until -->

                <div class="form-group">

                    <label>
                        Valid Until
                    </label>

                    <input
                        type="date"
                        name="valid_until"
                        value="<?php
                        echo htmlspecialchars(
                            $quote["valid_until"]
                            ?? ""
                        );
                        ?>"
                    >

                </div>


                <!-- Notes -->

                <div class="form-group full">

                    <label>
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        placeholder="Enter quote notes..."
                    ><?php
                    echo htmlspecialchars(
                        $quote["notes"] ?? ""
                    );
                    ?></textarea>

                </div>


            </div>


            <div class="form-actions">


                <button
                    type="submit"
                    class="btn btn-save"
                >
                    Update Quote
                </button>


                <a
                    href="view.php?id=<?php echo $id; ?>"
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