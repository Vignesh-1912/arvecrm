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


/*
|--------------------------------------------------------------------------
| Get Quote Details
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            quotes.*,

            companies.company_name,

            contacts.first_name,
            contacts.last_name,
            contacts.email AS contact_email,
            contacts.phone AS contact_phone,

            customers.customer_code,

            deals.title AS deal_title,

            users.name AS created_by_name

        FROM quotes

        LEFT JOIN companies
            ON quotes.company_id = companies.id

        LEFT JOIN contacts
            ON quotes.contact_id = contacts.id

        LEFT JOIN customers
            ON quotes.customer_id = customers.id

        LEFT JOIN deals
            ON quotes.deal_id = deals.id

        LEFT JOIN users
            ON quotes.created_by = users.id

        WHERE quotes.id = :id";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$quote = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$quote) {
    die("Quote not found.");
}


/*
|--------------------------------------------------------------------------
| Get Quote Items
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            quote_items.*,
            products.name AS product_name,
            products.sku

        FROM quote_items

        LEFT JOIN products
            ON quote_items.product_id = products.id

        WHERE quote_items.quote_id = :quote_id

        ORDER BY quote_items.id ASC";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":quote_id" => $id
]);

$quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Calculate Items Subtotal
|--------------------------------------------------------------------------
*/

$items_subtotal = 0;

foreach ($quote_items as $item) {
    $items_subtotal += (float) $item["total"];
}


/*
|--------------------------------------------------------------------------
| Status Class
|--------------------------------------------------------------------------
*/

$status = strtolower($quote["status"]);

$status_class = "status-" . $status;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        View Quote - CRM
    </title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        .quote-card {

            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.08);

            max-width: 1200px;

            margin-bottom: 30px;

        }


        .quote-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

            gap: 15px;

        }


        .quote-header h1 {

            margin: 0;

        }


        .quote-number {

            color: #6b7280;

            margin-top: 5px;

        }


        .btn {

            display: inline-block;

            padding: 10px 18px;

            border-radius: 6px;

            text-decoration: none;

            color: white;

            margin-left: 5px;

        }


        .btn-edit {

            background: #2563eb;

        }


        .btn-products {

            background: #16a34a;

        }


        .btn-back {

            background: #6b7280;

        }


        .details-table {

            width: 100%;

            border-collapse: collapse;

        }


        .details-table th,
        .details-table td {

            padding: 14px;

            border-bottom: 1px solid #e5e7eb;

            text-align: left;

            vertical-align: top;

        }


        .details-table th {

            width: 30%;

            background: #f8fafc;

            font-weight: bold;

        }


        .amount {

            font-size: 18px;

            font-weight: bold;

        }


        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 15px;

            font-size: 13px;

            text-transform: capitalize;

        }


        .status-draft {

            background: #e5e7eb;

            color: #374151;

        }


        .status-sent {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .status-accepted {

            background: #dcfce7;

            color: #166534;

        }


        .status-rejected {

            background: #fee2e2;

            color: #991b1b;

        }


        .status-expired {

            background: #fef3c7;

            color: #92400e;

        }


        .items-card {

            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.08);

            max-width: 1200px;

        }


        .items-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

        }


        .items-header h2 {

            margin: 0;

        }


        .items-table-container {

            width: 100%;

            overflow-x: auto;

        }


        .items-table {

            width: 100%;

            min-width: 900px;

            border-collapse: collapse;

        }


        .items-table th,
        .items-table td {

            padding: 13px 12px;

            border-bottom: 1px solid #e5e7eb;

            text-align: left;

        }


        .items-table th {

            background: #f1f5f9;

            font-weight: bold;

        }


        .items-table td {

            vertical-align: middle;

        }


        .items-total {

            text-align: right;

            margin-top: 20px;

            font-size: 18px;

            font-weight: bold;

        }


        .no-items {

            padding: 20px;

            background: #f8fafc;

            border-radius: 6px;

            color: #6b7280;

        }


        .summary {

            margin-top: 25px;

            margin-left: auto;

            max-width: 400px;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 10px 0;

            border-bottom: 1px solid #e5e7eb;

        }


        .summary-row.total {

            font-size: 20px;

            font-weight: bold;

            border-bottom: none;

            padding-top: 15px;

        }


        @media (max-width: 768px) {

            .quote-header {

                flex-direction: column;

                align-items: flex-start;

            }


            .quote-header div {

                width: 100%;

            }


            .btn {

                margin-left: 0;

                margin-right: 5px;

                margin-bottom: 5px;

            }

        }

    </style>

</head>


<body>


<?php include "../includes/sidebar.php"; ?>


<div class="main-content">


    <!-- Quote Details -->

    <div class="quote-card">


        <div class="quote-header">


            <div>

                <h1>
                    Quote Details
                </h1>

                <div class="quote-number">

                    <?php
                    echo htmlspecialchars(
                        $quote["quote_number"]
                    );
                    ?>

                </div>

            </div>


            <div>

                <a
                    href="edit.php?id=<?php echo $id; ?>"
                    class="btn btn-edit"
                >
                    Edit Quote
                </a>


                <a
                    href="quote_items.php?quote_id=<?php echo $id; ?>"
                    class="btn btn-products"
                >
                    Manage Products
                </a>


                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    Back
                </a>

            </div>


        </div>


        <table class="details-table">


            <tr>

                <th>
                    Quote ID
                </th>

                <td>
                    <?php echo $quote["id"]; ?>
                </td>

            </tr>


            <tr>

                <th>
                    Quote Number
                </th>

                <td>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $quote["quote_number"]
                        );
                        ?>
                    </strong>

                </td>

            </tr>


            <tr>

                <th>
                    Company
                </th>

                <td>

                    <?php

                    echo $quote["company_name"]
                        ? htmlspecialchars(
                            $quote["company_name"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Contact
                </th>

                <td>

                    <?php

                    if ($quote["first_name"]) {

                        echo htmlspecialchars(
                            trim(
                                $quote["first_name"]
                                . " "
                                . ($quote["last_name"] ?? "")
                            )
                        );

                    } else {

                        echo "-";

                    }

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Contact Email
                </th>

                <td>

                    <?php

                    echo $quote["contact_email"]
                        ? htmlspecialchars(
                            $quote["contact_email"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Contact Phone
                </th>

                <td>

                    <?php

                    echo $quote["contact_phone"]
                        ? htmlspecialchars(
                            $quote["contact_phone"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Customer
                </th>

                <td>

                    <?php

                    echo $quote["customer_code"]
                        ? htmlspecialchars(
                            $quote["customer_code"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Deal
                </th>

                <td>

                    <?php

                    echo $quote["deal_title"]
                        ? htmlspecialchars(
                            $quote["deal_title"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Status
                </th>

                <td>

                    <span
                        class="status <?php echo $status_class; ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $quote["status"]
                        );
                        ?>

                    </span>

                </td>

            </tr>


            <tr>

                <th>
                    Valid Until
                </th>

                <td>

                    <?php

                    echo $quote["valid_until"]
                        ? htmlspecialchars(
                            $quote["valid_until"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Subtotal
                </th>

                <td class="amount">

                    ₹ <?php
                    echo number_format(
                        (float)$quote["subtotal"],
                        2
                    );
                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Tax Amount
                </th>

                <td>

                    ₹ <?php
                    echo number_format(
                        (float)$quote["tax_amount"],
                        2
                    );
                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Discount
                </th>

                <td>

                    ₹ <?php
                    echo number_format(
                        (float)$quote["discount_amount"],
                        2
                    );
                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Total Amount
                </th>

                <td class="amount">

                    ₹ <?php
                    echo number_format(
                        (float)$quote["total_amount"],
                        2
                    );
                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Notes
                </th>

                <td>

                    <?php

                    echo $quote["notes"]
                        ? nl2br(
                            htmlspecialchars(
                                $quote["notes"]
                            )
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Created By
                </th>

                <td>

                    <?php

                    echo $quote["created_by_name"]
                        ? htmlspecialchars(
                            $quote["created_by_name"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Created At
                </th>

                <td>

                    <?php
                    echo htmlspecialchars(
                        $quote["created_at"]
                    );
                    ?>

                </td>

            </tr>


            <tr>

                <th>
                    Updated At
                </th>

                <td>

                    <?php

                    echo $quote["updated_at"]
                        ? htmlspecialchars(
                            $quote["updated_at"]
                        )
                        : "-";

                    ?>

                </td>

            </tr>


        </table>


    </div>


    <!-- Quote Products -->

    <div class="items-card">


        <div class="items-header">

            <h2>
                Products In This Quote
            </h2>


            <a
                href="quote_items.php?quote_id=<?php echo $id; ?>"
                class="btn btn-products"
            >
                + Add Product
            </a>

        </div>


        <?php if (count($quote_items) > 0): ?>


            <div class="items-table-container">


                <table class="items-table">


                    <thead>

                        <tr>

                            <th>
                                S.No.
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                SKU
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Unit Price
                            </th>

                            <th>
                                Discount
                            </th>

                            <th>
                                Tax
                            </th>

                            <th>
                                Total
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php $serial_no = 1; ?>


                        <?php foreach ($quote_items as $item): ?>


                            <tr>


                                <td>

                                    <?php
                                    echo $serial_no++;
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo $item["product_name"]
                                        ? htmlspecialchars(
                                            $item["product_name"]
                                        )
                                        : "-";

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo $item["sku"]
                                        ? htmlspecialchars(
                                            $item["sku"]
                                        )
                                        : "-";

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $item["description"]
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo number_format(
                                        (float)$item["quantity"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    ₹ <?php

                                    echo number_format(
                                        (float)$item["unit_price"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    ₹ <?php

                                    echo number_format(
                                        (float)$item["discount"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    ₹ <?php

                                    echo number_format(
                                        (float)$item["tax"],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    ₹ <?php

                                    echo number_format(
                                        (float)$item["total"],
                                        2
                                    );

                                    ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


            <div class="summary">


                <div class="summary-row">

                    <span>
                        Items Subtotal
                    </span>

                    <span>

                        ₹ <?php
                        echo number_format(
                            $items_subtotal,
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Tax
                    </span>

                    <span>

                        ₹ <?php
                        echo number_format(
                            (float)$quote["tax_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Discount
                    </span>

                    <span>

                        ₹ <?php
                        echo number_format(
                            (float)$quote["discount_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row total">

                    <span>
                        Total
                    </span>

                    <span>

                        ₹ <?php
                        echo number_format(
                            (float)$quote["total_amount"],
                            2
                        );
                        ?>

                    </span>

                </div>


            </div>


        <?php else: ?>


            <div class="no-items">

                No products have been added to this quote yet.


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>