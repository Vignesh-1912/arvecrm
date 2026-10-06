<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$error = "";
$invoice_number = "INV-" . date("Ymd-His");
$company_id = "";
$customer_id = "";
$contact_id = "";
$invoice_date = date("Y-m-d");
$invoice_status = "draft";
$tax_amount = "0.00";
$discount_amount = "0.00";
$notes = "";
$line_items = [
    [
        "product_id" => "",
        "quantity" => "1"
    ]
];

$companies = $conn->query("
    SELECT
        id,
        company_name,
        phone,
        email,
        website,
        address,
        city,
        state,
        country,
        postal_code
    FROM companies
    ORDER BY company_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$customers = $conn->query("
    SELECT
        id,
        customer_code,
        customer_type,
        status,
        company_id,
        contact_id
    FROM customers
    ORDER BY customer_code ASC
")->fetchAll(PDO::FETCH_ASSOC);

$contacts = $conn->query("
    SELECT
        id,
        first_name,
        last_name,
        email,
        phone,
        company_id,
        address,
        city,
        state,
        country,
        postal_code
    FROM contacts
    ORDER BY first_name ASC, last_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$products = $conn->query("
    SELECT
        id,
        name,
        sku,
        description,
        price
    FROM products
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $post_text = static function (string $key, string $default = ""): string {
        $value = $_POST[$key] ?? $default;
        return is_scalar($value) ? trim((string) $value) : "";
    };

    $invoice_number = $post_text("invoice_number");
    $company_id = $post_text("company_id");
    $customer_id = $post_text("customer_id");
    $contact_id = $post_text("contact_id");
    $invoice_date = $post_text("invoice_date");
    $invoice_status = $post_text("invoice_status", "draft");
    $tax_amount_input = $post_text("tax_amount");
    $discount_amount_input = $post_text("discount_amount");
    $tax_amount = is_numeric($tax_amount_input)
        ? (float) $tax_amount_input
        : 0;
    $discount_amount = is_numeric($discount_amount_input)
        ? (float) $discount_amount_input
        : 0;
    $notes = $post_text("notes");

    $posted_product_ids = $_POST["product_id"] ?? [];
    $posted_quantities = $_POST["quantity"] ?? [];

    if (!is_array($posted_product_ids) || !is_array($posted_quantities)) {
        $posted_product_ids = [];
        $posted_quantities = [];
    }

    $line_items = [];
    $item_count = max(
        count($posted_product_ids),
        count($posted_quantities)
    );

    for ($index = 0; $index < min($item_count, 100); $index++) {
        $product_id = $posted_product_ids[$index] ?? "";
        $quantity = $posted_quantities[$index] ?? "";

        $line_items[] = [
            "product_id" => is_scalar($product_id)
                ? trim((string) $product_id)
                : "invalid",
            "quantity" => is_scalar($quantity)
                ? trim((string) $quantity)
                : "invalid"
        ];
    }

    if (count($line_items) === 0) {
        $line_items[] = [
            "product_id" => "",
            "quantity" => "1"
        ];
    }

    $allowed_statuses = [
        "draft",
        "sent",
        "paid",
        "overdue",
        "cancelled"
    ];

    $valid_date = DateTime::createFromFormat("!Y-m-d", $invoice_date);

    if ($invoice_number === "") {
        $error = "Invoice number is required.";
    } elseif ($company_id === "") {
        $error = "Select the company for this invoice.";
    } elseif ($customer_id === "") {
        $error = "Select the customer for this invoice.";
    } elseif (!in_array($invoice_status, $allowed_statuses, true)) {
        $error = "Please select a valid invoice status.";
    } elseif (
        !$valid_date ||
        $valid_date->format("Y-m-d") !== $invoice_date
    ) {
        $error = "Please enter a valid invoice date.";
    } elseif (!is_numeric($tax_amount_input) || $tax_amount < 0) {
        $error = "Tax amount must be a valid non-negative amount.";
    } elseif (
        !is_numeric($discount_amount_input) ||
        $discount_amount < 0
    ) {
        $error = "Discount amount must be a valid non-negative amount.";
    } elseif (
        ($company_id !== "" && !ctype_digit($company_id)) ||
        ($customer_id !== "" && !ctype_digit($customer_id)) ||
        ($contact_id !== "" && !ctype_digit($contact_id))
    ) {
        $error = "Please select a valid company, customer and contact.";
    } elseif ($item_count > 100) {
        $error = "An invoice can contain no more than 100 products.";
    } else {
        $company_id = $company_id !== "" && ctype_digit($company_id)
            ? (int) $company_id
            : null;
        $customer_id = $customer_id !== "" && ctype_digit($customer_id)
            ? (int) $customer_id
            : null;
        $contact_id = $contact_id !== "" && ctype_digit($contact_id)
            ? (int) $contact_id
            : null;

        $validated_items = [];
        $subtotal = 0;

        foreach ($line_items as $item) {
            $product_id = $item["product_id"];
            $quantity_input = $item["quantity"];

            if ($product_id === "" && $quantity_input === "") {
                continue;
            }

            if (!ctype_digit($product_id) || (int) $product_id <= 0) {
                $error = "Choose a product for every invoice line.";
                break;
            }

            if (!is_numeric($quantity_input) || (float) $quantity_input <= 0) {
                $error = "Each product quantity must be greater than zero.";
                break;
            }

            $product_stmt = $conn->prepare("
                SELECT
                    id,
                    name,
                    description,
                    price
                FROM products
                WHERE id = :id
                AND status = 1
            ");
            $product_stmt->execute([
                ":id" => (int) $product_id
            ]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $error = "A selected product is unavailable or inactive.";
                break;
            }

            $quantity = (float) $quantity_input;
            $unit_price = (float) $product["price"];
            $line_total = round($quantity * $unit_price, 2);
            $subtotal += $line_total;

            $validated_items[] = [
                "product_id" => (int) $product["id"],
                "description" => trim($product["description"] ?? "") !== ""
                    ? $product["description"]
                    : $product["name"],
                "quantity" => $quantity,
                "unit_price" => $unit_price,
                "total" => $line_total
            ];
        }

        if ($error === "" && count($validated_items) === 0) {
            $error = "Add at least one product to the invoice.";
        }

        $total_amount = round(
            max(0, $subtotal + $tax_amount - $discount_amount),
            2
        );

        if ($error === "" && $company_id !== null) {
            $company_stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM companies
                WHERE id = :id
            ");
            $company_stmt->execute([":id" => $company_id]);

            if ((int) $company_stmt->fetchColumn() === 0) {
                $error = "The selected company could not be found.";
            }
        }

        if ($error === "" && $customer_id !== null) {
            $customer_stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM customers
                WHERE id = :id
            ");
            $customer_stmt->execute([":id" => $customer_id]);

            if ((int) $customer_stmt->fetchColumn() === 0) {
                $error = "The selected customer could not be found.";
            }
        }

        if ($error === "" && $contact_id !== null) {
            $contact_stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM contacts
                WHERE id = :id
            ");
            $contact_stmt->execute([":id" => $contact_id]);

            if ((int) $contact_stmt->fetchColumn() === 0) {
                $error = "The selected contact could not be found.";
            }
        }

        if ($error === "") {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("
                    INSERT INTO invoices (
                        invoice_number,
                        company_id,
                        customer_id,
                        contact_id,
                        subtotal,
                        tax_amount,
                        discount_amount,
                        total_amount,
                        invoice_status,
                        invoice_date,
                        notes,
                        created_by
                    )
                    VALUES (
                        :invoice_number,
                        :company_id,
                        :customer_id,
                        :contact_id,
                        :subtotal,
                        :tax_amount,
                        :discount_amount,
                        :total_amount,
                        :invoice_status,
                        :invoice_date,
                        :notes,
                        :created_by
                    )
                ");
                $stmt->execute([
                    ":invoice_number" => $invoice_number,
                    ":company_id" => $company_id,
                    ":customer_id" => $customer_id,
                    ":contact_id" => $contact_id,
                    ":subtotal" => $subtotal,
                    ":tax_amount" => $tax_amount,
                    ":discount_amount" => $discount_amount,
                    ":total_amount" => $total_amount,
                    ":invoice_status" => $invoice_status,
                    ":invoice_date" => $invoice_date,
                    ":notes" => $notes,
                    ":created_by" => $_SESSION["user_id"]
                ]);

                $invoice_id = (int) $conn->lastInsertId();
                $item_stmt = $conn->prepare("
                    INSERT INTO invoice_items (
                        invoice_id,
                        product_id,
                        description,
                        quantity,
                        unit_price,
                        discount,
                        tax,
                        total
                    )
                    VALUES (
                        :invoice_id,
                        :product_id,
                        :description,
                        :quantity,
                        :unit_price,
                        0,
                        0,
                        :total
                    )
                ");

                foreach ($validated_items as $item) {
                    $item_stmt->execute([
                        ":invoice_id" => $invoice_id,
                        ":product_id" => $item["product_id"],
                        ":description" => $item["description"],
                        ":quantity" => $item["quantity"],
                        ":unit_price" => $item["unit_price"],
                        ":total" => $item["total"]
                    ]);
                }

                $conn->commit();

                header("Location: view.php?id=" . $invoice_id);
                exit;
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                if ($e->getCode() === "23000") {
                    $error = "Invoice number already exists. Please choose another.";
                } else {
                    error_log("Unable to create invoice: " . $e->getMessage());
                    $error = "Unable to create invoice. Please try again.";
                }
            }
        }
    }
}

$company_json = json_encode(
    $companies,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
$customer_json = json_encode(
    $customers,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
$contact_json = json_encode(
    $contacts,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
$product_json = json_encode(
    $products,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Invoice - CRM</title>

    <link
        rel="stylesheet"
        href="/crm/assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }

        .main-content {
            width: calc(100% - 250px);
            min-height: 100vh;
            margin-left: 250px;
            padding: 28px;
        }

        .breadcrumb {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
            padding: 20px 24px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .title-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .title-icon {
            width: 48px;
            height: 48px;
            display: flex;
            flex: 0 0 48px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #dbeafe;
            font-size: 23px;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        .page-header h1 {
            margin-bottom: 5px;
            color: #0f172a;
            font-size: 23px;
        }

        .page-header p {
            margin-bottom: 0;
            color: #64748b;
            font-size: 12px;
        }

        .page-actions {
            display: flex;
            gap: 9px;
        }

        .btn {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .btn:hover {
            background: #f8fafc;
            border-color: #bfdbfe;
        }

        .btn-primary {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            border-color: #1d4ed8;
            background: #1d4ed8;
            color: #ffffff;
        }

        .error-banner {
            margin-bottom: 16px;
            padding: 12px 15px;
            border: 1px solid #fecaca;
            border-radius: 9px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }

        .form-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 290px;
            gap: 18px;
            align-items: start;
        }

        .form-main {
            display: grid;
            gap: 18px;
            min-width: 0;
        }

        .card,
        .summary-card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.035);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .card-header h2 {
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 15px;
        }

        .card-header p {
            margin-bottom: 0;
            color: #64748b;
            font-size: 11px;
        }

        .card-body {
            padding: 18px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .field-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .field {
            min-width: 0;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            min-height: 39px;
            padding: 9px 11px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            outline: none;
            background: #ffffff;
            color: #0f172a;
            font: inherit;
            font-size: 12px;
        }

        .field textarea {
            min-height: 78px;
            resize: vertical;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .field-hint {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 10px;
        }

        .party-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .party-card {
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .party-heading {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 11px;
            color: #172554;
            font-size: 12px;
            font-weight: 700;
        }

        .party-heading span {
            width: 27px;
            height: 27px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #dbeafe;
        }

        .party-details {
            min-height: 58px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .party-details strong {
            color: #0f172a;
            font-size: 12px;
        }

        .items-card {
            min-width: 0;
        }

        .items-scroll {
            overflow-x: auto;
        }

        .items-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
        }

        .items-table th {
            padding: 10px 11px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            text-align: left;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 10px 11px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .items-table select,
        .items-table input {
            width: 100%;
            min-height: 36px;
            padding: 7px 9px;
            border: 1px solid #dbe3ee;
            border-radius: 7px;
            background: #ffffff;
            color: #334155;
            font: inherit;
            font-size: 11px;
        }

        .items-table input[readonly] {
            background: #f8fafc;
            color: #64748b;
        }

        .line-description {
            max-width: 200px;
            color: #64748b;
            font-size: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .line-total {
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .remove-row {
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 7px;
            background: #fee2e2;
            color: #b91c1c;
            cursor: pointer;
            font-size: 16px;
        }

        .items-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 16px;
            border-top: 1px solid #eef2f7;
        }

        .summary-card {
            position: sticky;
            top: 18px;
        }

        .summary-top {
            padding: 17px 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .summary-top h2 {
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 15px;
        }

        .summary-top p {
            margin-bottom: 0;
            color: #64748b;
            font-size: 11px;
        }

        .summary-content {
            padding: 16px 18px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 11px;
            color: #64748b;
            font-size: 12px;
        }

        .summary-line strong {
            color: #334155;
            font-weight: 700;
        }

        .summary-adjustment {
            width: 112px;
            min-height: 31px;
            padding: 5px 8px;
            border: 1px solid #dbe3ee;
            border-radius: 7px;
            text-align: right;
            font-size: 11px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 15px;
            padding-top: 13px;
            border-top: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
        }

        .summary-total strong {
            color: #15803d;
            font-size: 17px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            padding: 4px 0 20px;
        }

        @media (max-width: 1100px) {
            .form-layout {
                grid-template-columns: minmax(0, 1fr) 250px;
            }
        }

        @media (max-width: 900px) {
            .form-layout {
                grid-template-columns: 1fr;
            }

            .summary-card {
                position: static;
                grid-row: 1;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                width: calc(100% - 220px);
                margin-left: 220px;
                padding: 16px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-actions {
                width: 100%;
            }

            .page-actions .btn {
                flex: 1;
            }

            .party-grid,
            .field-grid,
            .field-grid.three {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<main class="main-content">

    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard/index.php">Dashboard</a>
        <span>/</span>
        <a href="index.php">Invoices</a>
        <span>/</span>
        <span>Create</span>
    </nav>

    <header class="page-header">
        <div class="title-area">
            <div class="title-icon">🧾</div>
            <div>
                <h1>Create Invoice</h1>
                <p>Enter customer details, add products and review the invoice total.</p>
            </div>
        </div>
        <div class="page-actions">
            <a class="btn" href="index.php">Cancel</a>
        </div>
    </header>

    <?php if ($error !== ""): ?>
        <div class="error-banner" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="add.php" id="invoiceForm">
        <div class="form-layout">
            <div class="form-main">

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Invoice Information</h2>
                            <p>Set the invoice reference, date and current status.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="field-grid three">
                            <div class="field">
                                <label for="invoice_number">Invoice Number</label>
                                <input
                                    type="text"
                                    id="invoice_number"
                                    name="invoice_number"
                                    maxlength="100"
                                    required
                                    value="<?php echo htmlspecialchars($invoice_number, ENT_QUOTES, "UTF-8"); ?>"
                                >
                            </div>

                            <div class="field">
                                <label for="invoice_date">Invoice Date</label>
                                <input
                                    type="date"
                                    id="invoice_date"
                                    name="invoice_date"
                                    required
                                    value="<?php echo htmlspecialchars($invoice_date, ENT_QUOTES, "UTF-8"); ?>"
                                >
                            </div>

                            <div class="field">
                                <label for="invoice_status">Status</label>
                                <select id="invoice_status" name="invoice_status">
                                    <?php foreach (["draft", "sent", "paid", "overdue", "cancelled"] as $status): ?>
                                        <option
                                            value="<?php echo $status; ?>"
                                            <?php echo $invoice_status === $status ? "selected" : ""; ?>
                                        >
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Company & Customer Information</h2>
                            <p>Select the company, customer and billing contact for this invoice.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="field-grid three" style="margin-bottom:16px;">
                            <div class="field">
                                <label for="company_id">Company</label>
                                <select id="company_id" name="company_id" required>
                                    <option value="">Select a company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option
                                            value="<?php echo (int) $company["id"]; ?>"
                                            <?php echo (string) $company_id === (string) $company["id"] ? "selected" : ""; ?>
                                        >
                                            <?php echo htmlspecialchars($company["company_name"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-hint">Required billing company</span>
                            </div>

                            <div class="field">
                                <label for="customer_id">Customer</label>
                                <select id="customer_id" name="customer_id" required>
                                    <option value="">Select a customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option
                                            value="<?php echo (int) $customer["id"]; ?>"
                                            <?php echo (string) $customer_id === (string) $customer["id"] ? "selected" : ""; ?>
                                        >
                                            <?php echo htmlspecialchars($customer["customer_code"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-hint">Required customer record</span>
                            </div>

                            <div class="field">
                                <label for="contact_id">Billing Contact</label>
                                <select id="contact_id" name="contact_id">
                                    <option value="">Select a contact</option>
                                    <?php foreach ($contacts as $contact): ?>
                                        <?php $contact_label = trim($contact["first_name"] . " " . ($contact["last_name"] ?? "")); ?>
                                        <option
                                            value="<?php echo (int) $contact["id"]; ?>"
                                            <?php echo (string) $contact_id === (string) $contact["id"] ? "selected" : ""; ?>
                                        >
                                            <?php echo htmlspecialchars($contact_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-hint">Optional</span>
                            </div>
                        </div>

                        <div class="party-grid">
                            <article class="party-card">
                                <div class="party-heading">
                                    <span>🏢</span>
                                    Company Information
                                </div>
                                <div class="party-details" id="companyDetails">
                                    Select a company to preview its details.
                                </div>
                            </article>

                            <article class="party-card">
                                <div class="party-heading">
                                    <span>👤</span>
                                    Customer Information
                                </div>
                                <div class="party-details" id="customerDetails">
                                    Select a customer or billing contact to preview details.
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="card items-card">
                    <div class="card-header">
                        <div>
                            <h2>Product Details</h2>
                            <p>Add one or more active products to this invoice.</p>
                        </div>
                        <button class="btn" type="button" id="addProductButton">+ Add Product</button>
                    </div>

                    <div class="items-scroll">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th style="width:29%;">Product</th>
                                    <th style="width:24%;">Description</th>
                                    <th style="width:13%;">Quantity</th>
                                    <th style="width:14%;">Unit Price</th>
                                    <th style="width:14%;">Line Total</th>
                                    <th style="width:6%;"></th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItems">
                                <?php foreach ($line_items as $line_item): ?>
                                    <tr class="invoice-item-row">
                                        <td>
                                            <select name="product_id[]" class="product-select" required>
                                                <option value="">Select a product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option
                                                        value="<?php echo (int) $product["id"]; ?>"
                                                        <?php echo $line_item["product_id"] === (string) $product["id"] ? "selected" : ""; ?>
                                                    >
                                                        <?php echo htmlspecialchars($product["name"] . (!empty($product["sku"]) ? " · " . $product["sku"] : "")); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="line-description">Product description</div>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="quantity[]"
                                                class="quantity-input"
                                                min="0.01"
                                                step="0.01"
                                                required
                                                value="<?php echo htmlspecialchars($line_item["quantity"], ENT_QUOTES, "UTF-8"); ?>"
                                            >
                                        </td>
                                        <td>
                                            <input type="text" class="unit-price" readonly value="₹0.00" aria-label="Unit price">
                                        </td>
                                        <td>
                                            <span class="line-total">₹0.00</span>
                                        </td>
                                        <td>
                                            <button class="remove-row" type="button" aria-label="Remove product row">×</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="items-footer">
                        <span class="field-hint">Unit prices are taken from the selected product records.</span>
                        <button class="btn" type="button" id="addProductButtonBottom">+ Add another</button>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Notes</h2>
                            <p>Add payment instructions or other invoice information.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="field">
                            <label for="notes">Invoice Notes</label>
                            <textarea id="notes" name="notes" maxlength="5000"><?php echo htmlspecialchars($notes, ENT_QUOTES, "UTF-8"); ?></textarea>
                        </div>
                    </div>
                </section>

            </div>

            <aside class="summary-card">
                <div class="summary-top">
                    <h2>Invoice Summary</h2>
                    <p>Totals update as products are added.</p>
                </div>
                <div class="summary-content">
                    <div class="summary-line">
                        <span>Products</span>
                        <strong id="itemCount">0</strong>
                    </div>
                    <div class="summary-line">
                        <span>Subtotal</span>
                        <strong id="subtotalDisplay">₹0.00</strong>
                    </div>
                    <div class="summary-line">
                        <label for="tax_amount">Tax amount</label>
                        <input
                            type="number"
                            id="tax_amount"
                            name="tax_amount"
                            class="summary-adjustment"
                            min="0"
                            step="0.01"
                            value="<?php echo htmlspecialchars((string) $tax_amount, ENT_QUOTES, "UTF-8"); ?>"
                        >
                    </div>
                    <div class="summary-line">
                        <label for="discount_amount">Discount</label>
                        <input
                            type="number"
                            id="discount_amount"
                            name="discount_amount"
                            class="summary-adjustment"
                            min="0"
                            step="0.01"
                            value="<?php echo htmlspecialchars((string) $discount_amount, ENT_QUOTES, "UTF-8"); ?>"
                        >
                    </div>
                    <div class="summary-total">
                        <span>Total Due</span>
                        <strong id="totalDisplay">₹0.00</strong>
                    </div>
                </div>
                <div style="padding:0 18px 18px;">
                    <button class="btn btn-primary" type="submit" style="width:100%; min-height:42px;">
                        Create Invoice
                    </button>
                </div>
            </aside>
        </div>
    </form>

</main>

<template id="invoiceItemTemplate">
    <tr class="invoice-item-row">
        <td>
            <select name="product_id[]" class="product-select" required>
                <option value="">Select a product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int) $product["id"]; ?>">
                        <?php echo htmlspecialchars($product["name"] . (!empty($product["sku"]) ? " · " . $product["sku"] : "")); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><div class="line-description">Product description</div></td>
        <td>
            <input
                type="number"
                name="quantity[]"
                class="quantity-input"
                min="0.01"
                step="0.01"
                required
                value="1"
            >
        </td>
        <td><input type="text" class="unit-price" readonly value="₹0.00" aria-label="Unit price"></td>
        <td><span class="line-total">₹0.00</span></td>
        <td><button class="remove-row" type="button" aria-label="Remove product row">×</button></td>
    </tr>
</template>

<script>
    (function () {
        const companies = <?php echo $company_json ?: "[]"; ?>;
        const customers = <?php echo $customer_json ?: "[]"; ?>;
        const contacts = <?php echo $contact_json ?: "[]"; ?>;
        const products = <?php echo $product_json ?: "[]"; ?>;

        const companyById = new Map(companies.map(function (company) {
            return [String(company.id), company];
        }));
        const customerById = new Map(customers.map(function (customer) {
            return [String(customer.id), customer];
        }));
        const contactById = new Map(contacts.map(function (contact) {
            return [String(contact.id), contact];
        }));
        const productById = new Map(products.map(function (product) {
            return [String(product.id), product];
        }));

        const companySelect = document.getElementById("company_id");
        const customerSelect = document.getElementById("customer_id");
        const contactSelect = document.getElementById("contact_id");
        const itemsContainer = document.getElementById("invoiceItems");
        const itemTemplate = document.getElementById("invoiceItemTemplate");
        const taxInput = document.getElementById("tax_amount");
        const discountInput = document.getElementById("discount_amount");

        function escapeHtml(value) {
            return String(value || "").replace(/[&<>"']/g, function (character) {
                return {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#039;"
                }[character];
            });
        }

        function formatMoney(value) {
            return "₹" + Number(value || 0).toLocaleString("en-IN", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function joinedAddress(record) {
            return [
                record.address,
                record.city,
                record.state,
                record.country,
                record.postal_code
            ].filter(Boolean).join(", ");
        }

        function updateCompanyDetails() {
            const company = companyById.get(companySelect.value);
            const target = document.getElementById("companyDetails");

            if (!company) {
                target.textContent = "Select a company to preview its details.";
                return;
            }

            const details = [
                "<strong>" + escapeHtml(company.company_name) + "</strong>",
                joinedAddress(company),
                company.phone,
                company.email,
                company.website
            ].filter(Boolean);

            target.innerHTML = "<strong>" + escapeHtml(company.company_name) +
                "</strong>" + (details.length > 1
                    ? "<br>" + details.slice(1).map(escapeHtml).join("<br>")
                    : "");
        }

        function updateCustomerDetails() {
            const customer = customerById.get(customerSelect.value);
            const contact = contactById.get(contactSelect.value);
            const target = document.getElementById("customerDetails");
            const details = [];

            if (customer) {
                details.push("<strong>" + escapeHtml(customer.customer_code) + "</strong>");
                if (customer.customer_type) {
                    details.push("Type: " + escapeHtml(customer.customer_type));
                }
                if (customer.status) {
                    details.push("Status: " + escapeHtml(customer.status));
                }
            }

            if (contact) {
                const contactName = [contact.first_name, contact.last_name]
                    .filter(Boolean)
                    .join(" ");
                if (contactName) {
                    details.push("Contact: " + escapeHtml(contactName));
                }
                if (contact.email) {
                    details.push(escapeHtml(contact.email));
                }
                if (contact.phone) {
                    details.push(escapeHtml(contact.phone));
                }
                const address = joinedAddress(contact);
                if (address) {
                    details.push(escapeHtml(address));
                }
            }

            target.innerHTML = details.length
                ? details.join("<br>")
                : "Select a customer or billing contact to preview details.";
        }

        function updateLine(row) {
            const product = productById.get(row.querySelector(".product-select").value);
            const quantity = Number(row.querySelector(".quantity-input").value) || 0;
            const description = row.querySelector(".line-description");
            const priceInput = row.querySelector(".unit-price");
            const lineTotal = row.querySelector(".line-total");
            const unitPrice = product ? Number(product.price) || 0 : 0;

            description.textContent = product
                ? (product.description || product.name)
                : "Product description";
            description.title = description.textContent;
            priceInput.value = formatMoney(unitPrice);
            lineTotal.textContent = formatMoney(unitPrice * quantity);
        }

        function updateTotals() {
            const rows = Array.from(itemsContainer.querySelectorAll(".invoice-item-row"));
            const subtotal = rows.reduce(function (sum, row) {
                const product = productById.get(row.querySelector(".product-select").value);
                const quantity = Number(row.querySelector(".quantity-input").value) || 0;
                return sum + (product ? (Number(product.price) || 0) * quantity : 0);
            }, 0);

            const tax = Number(taxInput.value) || 0;
            const discount = Number(discountInput.value) || 0;

            document.getElementById("itemCount").textContent = String(
                rows.filter(function (row) {
                    return row.querySelector(".product-select").value !== "";
                }).length
            );
            document.getElementById("subtotalDisplay").textContent = formatMoney(subtotal);
            document.getElementById("totalDisplay").textContent = formatMoney(
                Math.max(0, subtotal + tax - discount)
            );
        }

        function addItemRow() {
            if (itemsContainer.querySelectorAll(".invoice-item-row").length >= 100) {
                return;
            }

            itemsContainer.appendChild(itemTemplate.content.cloneNode(true));
            updateTotals();
        }

        companySelect.addEventListener("change", updateCompanyDetails);
        customerSelect.addEventListener("change", function () {
            const customer = customerById.get(customerSelect.value);
            if (customer) {
                if (customer.company_id) {
                    companySelect.value = String(customer.company_id);
                }
                if (customer.contact_id) {
                    contactSelect.value = String(customer.contact_id);
                }
                updateCompanyDetails();
            }
            updateCustomerDetails();
        });
        contactSelect.addEventListener("change", function () {
            const contact = contactById.get(contactSelect.value);
            if (contact && contact.company_id) {
                companySelect.value = String(contact.company_id);
                updateCompanyDetails();
            }
            updateCustomerDetails();
        });

        itemsContainer.addEventListener("change", function (event) {
            if (event.target.classList.contains("product-select")) {
                updateLine(event.target.closest(".invoice-item-row"));
                updateTotals();
            }
        });

        itemsContainer.addEventListener("input", function (event) {
            if (event.target.classList.contains("quantity-input")) {
                updateLine(event.target.closest(".invoice-item-row"));
                updateTotals();
            }
        });

        itemsContainer.addEventListener("click", function (event) {
            if (!event.target.classList.contains("remove-row")) {
                return;
            }

            const rows = itemsContainer.querySelectorAll(".invoice-item-row");
            if (rows.length === 1) {
                rows[0].querySelector(".product-select").value = "";
                rows[0].querySelector(".quantity-input").value = "1";
                updateLine(rows[0]);
            } else {
                event.target.closest(".invoice-item-row").remove();
            }
            updateTotals();
        });

        document.getElementById("addProductButton").addEventListener("click", addItemRow);
        document.getElementById("addProductButtonBottom").addEventListener("click", addItemRow);
        taxInput.addEventListener("input", updateTotals);
        discountInput.addEventListener("input", updateTotals);

        itemsContainer.querySelectorAll(".invoice-item-row").forEach(updateLine);
        updateCompanyDetails();
        updateCustomerDetails();
        updateTotals();
    })();
</script>

</body>

</html>
