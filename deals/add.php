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
| Default Values
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Get Companies
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id, company_name
    FROM companies
    ORDER BY company_name ASC
";

$stmt = $conn->query($sql);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Contacts
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        first_name,
        last_name
    FROM contacts
    ORDER BY first_name ASC, last_name ASC
";

$stmt = $conn->query($sql);

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Customers
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        customers.id,
        customers.customer_code,
        companies.company_name
    FROM customers
    LEFT JOIN companies
        ON customers.company_id = companies.id
    ORDER BY customers.id ASC
";

$stmt = $conn->query($sql);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id, name
    FROM users
    WHERE status = 1
    ORDER BY name ASC
";

$stmt = $conn->query($sql);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $allowedStages = [
        "prospecting",
        "qualification",
        "proposal",
        "negotiation",
        "closed_won",
        "closed_lost"
    ];


    if (empty($title)) {

        $error = "Deal title is required.";

    } elseif (!is_numeric($amount) || $amount < 0) {

        $error = "Amount must be a valid positive number.";

    } elseif (!is_numeric($probability)) {

        $error = "Probability must be a valid number.";

    } elseif ($probability < 0 || $probability > 100) {

        $error = "Probability must be between 0 and 100.";

    } elseif (!in_array($stage, $allowedStages, true)) {

        $error = "Invalid deal stage.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert Deal
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO deals
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
                )
            ";

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


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to add deal: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Preview Values
|--------------------------------------------------------------------------
*/

$previewInitial = !empty($title)
    ? strtoupper(substr($title, 0, 1))
    : "D";


/*
|--------------------------------------------------------------------------
| Selected Names
|--------------------------------------------------------------------------
*/

$selectedCompanyName = "Not selected";
$selectedContactName = "Not selected";
$selectedCustomerName = "Not selected";
$selectedUserName = "Not assigned";


foreach ($companies as $company) {

    if ((string) $company["id"] === (string) $company_id) {

        $selectedCompanyName = $company["company_name"];

        break;
    }
}


foreach ($contacts as $contact) {

    if ((string) $contact["id"] === (string) $contact_id) {

        $selectedContactName = trim(
            $contact["first_name"] . " " .
            ($contact["last_name"] ?? "")
        );

        break;
    }
}


foreach ($customers as $customer) {

    if ((string) $customer["id"] === (string) $customer_id) {

        $selectedCustomerName = $customer["customer_code"];

        if (!empty($customer["company_name"])) {

            $selectedCustomerName .=
                " - " . $customer["company_name"];
        }

        break;
    }
}


foreach ($users as $user) {

    if ((string) $user["id"] === (string) $assigned_to) {

        $selectedUserName = $user["name"];

        break;
    }
}


/*
|--------------------------------------------------------------------------
| Stage Labels
|--------------------------------------------------------------------------
*/

$stageLabels = [
    "prospecting" => "Prospecting",
    "qualification" => "Qualification",
    "proposal" => "Proposal",
    "negotiation" => "Negotiation",
    "closed_won" => "Closed Won",
    "closed_lost" => "Closed Lost"
];

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

        * {
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
        }

        .page-wrapper {
            max-width: 1450px;
            margin: 0 auto;
        }

        /* ------------------------------------------------------------
           Breadcrumb
        ------------------------------------------------------------ */

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: #64748b;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #94a3b8;
        }

        /* ------------------------------------------------------------
           Header
        ------------------------------------------------------------ */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0;
            color: #172554;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Layout
        ------------------------------------------------------------ */

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            align-items: start;
        }

        .form-card,
        .side-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .form-card {
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h2 {
            margin: 0;
            color: #172554;
            font-size: 17px;
        }

        .card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .form-body {
            padding: 24px;
        }

        /* ------------------------------------------------------------
           Error
        ------------------------------------------------------------ */

        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            margin-bottom: 22px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            font-size: 14px;
        }

        .error-icon {
            margin-top: 2px;
        }

        /* ------------------------------------------------------------
           Summary
        ------------------------------------------------------------ */

        .deal-summary {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .deal-avatar-small {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 19px;
            font-weight: 700;
        }

        .summary-text strong {
            display: block;
            color: #172554;
            font-size: 15px;
        }

        .summary-text span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 13px;
        }

        /* ------------------------------------------------------------
           Sections
        ------------------------------------------------------------ */

        .form-section {
            margin-bottom: 28px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 16px;
        }

        .section-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .section-title h3 {
            margin: 0;
            color: #1e293b;
            font-size: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 17px;
        }

        .form-row:last-child {
            margin-bottom: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .required {
            color: #dc2626;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #1e293b;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        input,
        select {
            height: 43px;
        }

        textarea {
            min-height: 115px;
            resize: vertical;
            line-height: 1.5;
        }

        input::placeholder,
        textarea::placeholder {
            color: #94a3b8;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        /* ------------------------------------------------------------
           Stage
        ------------------------------------------------------------ */

        .stage-options {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .stage-option {
            position: relative;
        }

        .stage-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .stage-label {
            min-height: 40px;
            padding: 0 8px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .stage-label:hover {
            background: #f8fafc;
            border-color: #93c5fd;
        }

        .stage-option input:checked + .prospecting-stage {
            background: #eff6ff;
            color: #2563eb;
            border-color: #93c5fd;
        }

        .stage-option input:checked + .qualification-stage {
            background: #f5f3ff;
            color: #6d28d9;
            border-color: #c4b5fd;
        }

        .stage-option input:checked + .proposal-stage {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fdba74;
        }

        .stage-option input:checked + .negotiation-stage {
            background: #fefce8;
            color: #a16207;
            border-color: #fde047;
        }

        .stage-option input:checked + .won-stage {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }

        .stage-option input:checked + .lost-stage {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        /* ------------------------------------------------------------
           Probability
        ------------------------------------------------------------ */

        .probability-wrapper {
            position: relative;
        }

        .probability-wrapper input {
            padding-right: 40px;
        }

        .probability-symbol {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        /* ------------------------------------------------------------
           Buttons
        ------------------------------------------------------------ */

        .button-area {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .save-btn {
            min-height: 42px;
            padding: 0 20px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .cancel-btn:hover {
            background: #f8fafc;
        }

        /* ------------------------------------------------------------
           Side Cards
        ------------------------------------------------------------ */

        .side-card {
            padding: 20px;
            margin-bottom: 18px;
        }

        .side-title {
            margin-bottom: 17px;
        }

        .side-title h3 {
            margin: 0;
            color: #172554;
            font-size: 16px;
        }

        .side-title p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ------------------------------------------------------------
           Preview
        ------------------------------------------------------------ */

        .preview-profile {
            text-align: center;
            padding: 7px 0 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .preview-avatar {
            width: 74px;
            height: 74px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            font-weight: 700;
        }

        .preview-name {
            margin: 0;
            color: #172554;
            font-size: 18px;
            font-weight: 700;
        }

        .preview-subtitle {
            margin-top: 5px;
            color: #64748b;
            font-size: 13px;
        }

        .preview-stage {
            display: inline-flex;
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .preview-stage.prospecting {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .preview-stage.qualification {
            background: #ede9fe;
            color: #6d28d9;
        }

        .preview-stage.proposal {
            background: #ffedd5;
            color: #c2410c;
        }

        .preview-stage.negotiation {
            background: #fef9c3;
            color: #a16207;
        }

        .preview-stage.closed_won {
            background: #dcfce7;
            color: #15803d;
        }

        .preview-stage.closed_lost {
            background: #fee2e2;
            color: #b91c1c;
        }

        .preview-details {
            padding-top: 17px;
        }

        .preview-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 13px;
        }

        .preview-item:last-child {
            margin-bottom: 0;
        }

        .preview-icon {
            width: 31px;
            height: 31px;
            border-radius: 7px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }

        .preview-text {
            min-width: 0;
        }

        .preview-label {
            display: block;
            margin-bottom: 2px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .preview-value {
            display: block;
            color: #334155;
            font-size: 13px;
            word-break: break-word;
        }

        /* ------------------------------------------------------------
           Amount Box
        ------------------------------------------------------------ */

        .amount-box {
            margin-top: 16px;
            padding: 15px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 9px;
        }

        .amount-label {
            display: block;
            margin-bottom: 4px;
            color: #15803d;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .amount-value {
            display: block;
            color: #166534;
            font-size: 23px;
            font-weight: 700;
        }

        .probability-bar {
            height: 7px;
            margin-top: 10px;
            overflow: hidden;
            background: #dcfce7;
            border-radius: 999px;
        }

        .probability-fill {
            height: 100%;
            width: 0%;
            background: #22c55e;
            border-radius: inherit;
            transition: width 0.2s ease;
        }

        .probability-caption {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            color: #64748b;
            font-size: 11px;
        }

        /* ------------------------------------------------------------
           Guide
        ------------------------------------------------------------ */

        .guide-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .guide-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 13px;
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
        }

        .guide-list li:last-child {
            margin-bottom: 0;
        }

        .guide-number {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 11px;
            font-weight: 700;
        }

        /* ------------------------------------------------------------
           Tip
        ------------------------------------------------------------ */

        .tip-box {
            padding: 13px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            color: #1e40af;
            font-size: 12px;
            line-height: 1.55;
        }

        .tip-box strong {
            display: block;
            margin-bottom: 3px;
        }

        /* ------------------------------------------------------------
           Dark Mode
        ------------------------------------------------------------ */

        html.dark-mode body {
            background: #0f172a;
        }

        html.dark-mode .page-title h1,
        html.dark-mode .card-header h2,
        html.dark-mode .section-title h3,
        html.dark-mode .side-title h3,
        html.dark-mode .preview-name,
        html.dark-mode .amount-value,
        html.dark-mode .summary-text strong {
            color: #f8fafc;
        }

        html.dark-mode .page-title p,
        html.dark-mode .card-header p,
        html.dark-mode .side-title p,
        html.dark-mode .breadcrumb,
        html.dark-mode .summary-text span {
            color: #94a3b8;
        }

        html.dark-mode .form-card,
        html.dark-mode .side-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: none;
        }

        html.dark-mode .deal-summary {
            background: #0f172a;
            border-color: #1f2937;
        }

        html.dark-mode .section-icon {
            background: #1e3a8a;
            color: #bfdbfe;
        }

        html.dark-mode .card-header,
        html.dark-mode .preview-profile,
        html.dark-mode .button-area {
            border-color: #1f2937;
        }

        html.dark-mode label {
            color: #e2e8f0;
        }

        html.dark-mode input,
        html.dark-mode select,
        html.dark-mode textarea {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        html.dark-mode input::placeholder,
        html.dark-mode textarea::placeholder {
            color: #64748b;
        }

        html.dark-mode input:focus,
        html.dark-mode select:focus,
        html.dark-mode textarea:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.12);
        }

        html.dark-mode .stage-label {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .stage-label:hover {
            background: #1e293b;
        }

        html.dark-mode .stage-option input:checked + .prospecting-stage {
            background: #172554;
            color: #93c5fd;
            border-color: #2563eb;
        }

        html.dark-mode .stage-option input:checked + .qualification-stage {
            background: #2e1065;
            color: #c4b5fd;
            border-color: #7c3aed;
        }

        html.dark-mode .stage-option input:checked + .proposal-stage {
            background: #431407;
            color: #fdba74;
            border-color: #c2410c;
        }

        html.dark-mode .stage-option input:checked + .negotiation-stage {
            background: #422006;
            color: #fde047;
            border-color: #a16207;
        }

        html.dark-mode .stage-option input:checked + .won-stage {
            background: #052e16;
            color: #86efac;
            border-color: #166534;
        }

        html.dark-mode .stage-option input:checked + .lost-stage {
            background: #450a0a;
            color: #fca5a5;
            border-color: #991b1b;
        }

        html.dark-mode .back-btn,
        html.dark-mode .cancel-btn {
            background: #111827;
            border-color: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .back-btn:hover,
        html.dark-mode .cancel-btn:hover {
            background: #1e293b;
        }

        html.dark-mode .preview-icon,
        html.dark-mode .amount-box {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .preview-value {
            color: #cbd5e1;
        }

        html.dark-mode .amount-label {
            color: #86efac;
        }

        html.dark-mode .amount-value {
            color: #bbf7d0;
        }

        html.dark-mode .probability-bar {
            background: #14532d;
        }

        html.dark-mode .probability-caption {
            color: #64748b;
        }

        html.dark-mode .guide-list li {
            color: #cbd5e1;
        }

        html.dark-mode .tip-box {
            background: #172554;
            border-color: #1e3a8a;
            color: #bfdbfe;
        }

        /* ------------------------------------------------------------
           Responsive
        ------------------------------------------------------------ */

        @media (max-width: 1080px) {

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .form-group.full {
                grid-column: auto;
            }

            .form-body {
                padding: 18px;
            }

            .card-header {
                padding: 18px;
            }

            .stage-options {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .button-area {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .save-btn,
            .cancel-btn {
                width: 100%;
            }

            .header-actions {
                width: 100%;
            }

            .back-btn {
                flex: 1;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>


<div class="main-content">

    <div class="page-wrapper">


        <!-- =========================================================
             Breadcrumb
        ========================================================== -->

        <div class="breadcrumb">

            <a href="index.php">
                Deals
            </a>

            <span class="breadcrumb-separator">
                ›
            </span>

            <span>
                Add Deal
            </span>

        </div>


        <!-- =========================================================
             Header
        ========================================================== -->

        <div class="page-header">

            <div class="page-title">

                <h1>
                    Add Deal
                </h1>

                <p>
                    Create a new sales opportunity and track its progress.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="index.php"
                    class="back-btn"
                >
                    ← Deal List
                </a>

            </div>

        </div>


        <!-- =========================================================
             Main Grid
        ========================================================== -->

        <div class="content-grid">


            <!-- =====================================================
                 FORM
            ====================================================== -->

            <div class="form-card">

                <div class="card-header">

                    <h2>
                        Deal Information
                    </h2>

                    <p>
                        Enter the opportunity details below.
                    </p>

                </div>


                <div class="form-body">


                    <?php if (!empty($error)): ?>

                        <div class="error-box">

                            <span class="error-icon">
                                ⚠
                            </span>

                            <span>
                                <?php echo htmlspecialchars($error); ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- Deal Summary -->

                    <div class="deal-summary">

                        <div
                            class="deal-avatar-small"
                            id="summaryAvatar"
                        >
                            <?php echo htmlspecialchars($previewInitial); ?>
                        </div>


                        <div class="summary-text">

                            <strong id="summaryTitle">

                                <?php
                                echo $title !== ""
                                    ? htmlspecialchars($title)
                                    : "New Deal";
                                ?>

                            </strong>


                            <span id="summaryStage">

                                <?php
                                echo isset($stageLabels[$stage])
                                    ? $stageLabels[$stage]
                                    : "Prospecting";
                                ?>

                            </span>

                        </div>

                    </div>


                    <form method="POST">


                        <!-- =================================================
                             DEAL DETAILS
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    💼
                                </div>

                                <h3>
                                    Deal Details
                                </h3>

                            </div>


                            <!-- Title -->

                            <div class="form-row">

                                <div class="form-group full">

                                    <label for="title">

                                        Deal Title

                                        <span class="required">*</span>

                                    </label>

                                    <input
                                        type="text"
                                        name="title"
                                        id="title"
                                        placeholder="Enter deal title"
                                        value="<?php echo htmlspecialchars($title); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- Company / Contact -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="company_id">
                                        Company
                                    </label>

                                    <select
                                        name="company_id"
                                        id="company_id"
                                    >

                                        <option value="">
                                            -- Select Company --
                                        </option>

                                        <?php foreach ($companies as $company): ?>

                                            <option
                                                value="<?php echo (int) $company["id"]; ?>"
                                                <?php echo (
                                                    (string) $company_id ===
                                                    (string) $company["id"]
                                                ) ? "selected" : ""; ?>
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


                                <div class="form-group">

                                    <label for="contact_id">
                                        Contact
                                    </label>

                                    <select
                                        name="contact_id"
                                        id="contact_id"
                                    >

                                        <option value="">
                                            -- Select Contact --
                                        </option>

                                        <?php foreach ($contacts as $contact): ?>

                                            <option
                                                value="<?php echo (int) $contact["id"]; ?>"
                                                <?php echo (
                                                    (string) $contact_id ===
                                                    (string) $contact["id"]
                                                ) ? "selected" : ""; ?>
                                            >

                                                <?php

                                                $contactName = trim(
                                                    $contact["first_name"] .
                                                    " " .
                                                    ($contact["last_name"] ?? "")
                                                );

                                                echo htmlspecialchars(
                                                    $contactName
                                                );

                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>


                            <!-- Customer / Assigned -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="customer_id">
                                        Customer
                                    </label>

                                    <select
                                        name="customer_id"
                                        id="customer_id"
                                    >

                                        <option value="">
                                            -- Select Customer --
                                        </option>

                                        <?php foreach ($customers as $customer): ?>

                                            <option
                                                value="<?php echo (int) $customer["id"]; ?>"
                                                <?php echo (
                                                    (string) $customer_id ===
                                                    (string) $customer["id"]
                                                ) ? "selected" : ""; ?>
                                            >

                                                <?php

                                                $customerDisplay =
                                                    $customer["customer_code"];

                                                if (!empty($customer["company_name"])) {

                                                    $customerDisplay .=
                                                        " - " .
                                                        $customer["company_name"];
                                                }

                                                echo htmlspecialchars(
                                                    $customerDisplay
                                                );

                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <div class="form-group">

                                    <label for="assigned_to">
                                        Assigned To
                                    </label>

                                    <select
                                        name="assigned_to"
                                        id="assigned_to"
                                    >

                                        <option value="">
                                            -- Select User --
                                        </option>

                                        <?php foreach ($users as $user): ?>

                                            <option
                                                value="<?php echo (int) $user["id"]; ?>"
                                                <?php echo (
                                                    (string) $assigned_to ===
                                                    (string) $user["id"]
                                                ) ? "selected" : ""; ?>
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $user["name"]
                                                );
                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             VALUE & FORECAST
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    📊
                                </div>

                                <h3>
                                    Deal Value & Forecast
                                </h3>

                            </div>


                            <!-- Amount / Probability -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="amount">
                                        Deal Amount
                                    </label>

                                    <input
                                        type="number"
                                        name="amount"
                                        id="amount"
                                        value="<?php echo htmlspecialchars($amount); ?>"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="probability">
                                        Probability
                                    </label>

                                    <div class="probability-wrapper">

                                        <input
                                            type="number"
                                            name="probability"
                                            id="probability"
                                            value="<?php echo htmlspecialchars($probability); ?>"
                                            min="0"
                                            max="100"
                                            placeholder="0"
                                        >

                                        <span class="probability-symbol">
                                            %
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <!-- Stage / Close Date -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label>
                                        Deal Stage
                                    </label>

                                    <div class="stage-options">


                                        <div class="stage-option">

                                            <input
                                                type="radio"
                                                name="stage"
                                                id="stage_prospecting"
                                                value="prospecting"
                                                <?php echo (
                                                    $stage === "prospecting"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="stage_prospecting"
                                                class="stage-label prospecting-stage"
                                            >
                                                Prospecting
                                            </label>

                                        </div>


                                        <div class="stage-option">

                                            <input
                                                type="radio"
                                                name="stage"
                                                id="stage_qualification"
                                                value="qualification"
                                                <?php echo (
                                                    $stage === "qualification"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="stage_qualification"
                                                class="stage-label qualification-stage"
                                            >
                                                Qualification
                                            </label>

                                        </div>


                                        <div class="stage-option">

                                            <input
                                                type="radio"
                                                name="stage"
                                                id="stage_proposal"
                                                value="proposal"
                                                <?php echo (
                                                    $stage === "proposal"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="stage_proposal"
                                                class="stage-label proposal-stage"
                                            >
                                                Proposal
                                            </label>

                                        </div>


                                        <div class="stage-option">

                                            <input
                                                type="radio"
                                                name="stage"
                                                id="stage_negotiation"
                                                value="negotiation"
                                                <?php echo (
                                                    $stage === "negotiation"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="stage_negotiation"
                                                class="stage-label negotiation-stage"
                                            >
                                                Negotiation
                                            </label>

                                        </div>


                                        <div class="stage-option">

                                            <input
                                                type="radio"
                                                name="stage"
                                                id="stage_closed_won"
                                                value="closed_won"
                                                <?php echo (
                                                    $stage === "closed_won"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="stage_closed_won"
                                                class="stage-label won-stage"
                                            >
                                                Closed Won
                                            </label>

                                        </div>


                                        <div class="stage-option">

                                            <input
                                                type="radio"
                                                name="stage"
                                                id="stage_closed_lost"
                                                value="closed_lost"
                                                <?php echo (
                                                    $stage === "closed_lost"
                                                ) ? "checked" : ""; ?>
                                            >

                                            <label
                                                for="stage_closed_lost"
                                                class="stage-label lost-stage"
                                            >
                                                Closed Lost
                                            </label>

                                        </div>

                                    </div>

                                </div>


                                <div class="form-group">

                                    <label for="expected_close_date">
                                        Expected Close Date
                                    </label>

                                    <input
                                        type="date"
                                        name="expected_close_date"
                                        id="expected_close_date"
                                        value="<?php echo htmlspecialchars($expected_close_date); ?>"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             DESCRIPTION
                        ================================================== -->

                        <div class="form-section">

                            <div class="section-title">

                                <div class="section-icon">
                                    📝
                                </div>

                                <h3>
                                    Description
                                </h3>

                            </div>


                            <div class="form-row">

                                <div class="form-group full">

                                    <label for="description">
                                        Deal Description
                                    </label>

                                    <textarea
                                        name="description"
                                        id="description"
                                        placeholder="Add additional information about this deal..."
                                    ><?php echo htmlspecialchars($description); ?></textarea>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             BUTTONS
                        ================================================== -->

                        <div class="button-area">

                            <a
                                href="index.php"
                                class="cancel-btn"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="save-btn"
                            >
                                Save Deal
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- =====================================================
                 RIGHT SIDE
            ====================================================== -->

            <div>


                <!-- Deal Preview -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Deal Preview
                        </h3>

                        <p>
                            Preview how the opportunity will appear in your CRM.
                        </p>

                    </div>


                    <div class="preview-profile">

                        <div
                            class="preview-avatar"
                            id="previewAvatar"
                        >
                            <?php echo htmlspecialchars($previewInitial); ?>
                        </div>


                        <h3
                            class="preview-name"
                            id="previewTitle"
                        >

                            <?php
                            echo $title !== ""
                                ? htmlspecialchars($title)
                                : "New Deal";
                            ?>

                        </h3>


                        <div
                            class="preview-subtitle"
                            id="previewStageText"
                        >

                            <?php
                            echo htmlspecialchars(
                                $stageLabels[$stage] ?? "Prospecting"
                            );
                            ?>

                        </div>


                        <span
                            class="preview-stage <?php echo htmlspecialchars($stage); ?>"
                            id="previewStage"
                        >

                            <?php
                            echo htmlspecialchars(
                                $stageLabels[$stage] ?? "Prospecting"
                            );
                            ?>

                        </span>

                    </div>


                    <div class="preview-details">


                        <!-- Company -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                🏢
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Company
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewCompany"
                                >
                                    <?php echo htmlspecialchars(
                                        $selectedCompanyName
                                    ); ?>
                                </span>

                            </div>

                        </div>


                        <!-- Contact -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                👤
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Contact
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewContact"
                                >
                                    <?php echo htmlspecialchars(
                                        $selectedContactName
                                    ); ?>
                                </span>

                            </div>

                        </div>


                        <!-- Customer -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                🧾
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Customer
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewCustomer"
                                >
                                    <?php echo htmlspecialchars(
                                        $selectedCustomerName
                                    ); ?>
                                </span>

                            </div>

                        </div>


                        <!-- Assigned -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                👨‍💼
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Assigned To
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewAssigned"
                                >
                                    <?php echo htmlspecialchars(
                                        $selectedUserName
                                    ); ?>
                                </span>

                            </div>

                        </div>


                        <!-- Close Date -->

                        <div class="preview-item">

                            <div class="preview-icon">
                                📅
                            </div>

                            <div class="preview-text">

                                <span class="preview-label">
                                    Expected Close
                                </span>

                                <span
                                    class="preview-value"
                                    id="previewCloseDate"
                                >
                                    <?php
                                    echo !empty($expected_close_date)
                                        ? htmlspecialchars($expected_close_date)
                                        : "Not set";
                                    ?>
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Amount -->

                    <div class="amount-box">

                        <span class="amount-label">
                            Deal Amount
                        </span>

                        <span
                            class="amount-value"
                            id="previewAmount"
                        >
                            ₹<?php echo number_format(
                                (float) $amount,
                                2
                            ); ?>
                        </span>


                        <div class="probability-bar">

                            <div
                                class="probability-fill"
                                id="probabilityFill"
                                style="width: <?php echo min(
                                    100,
                                    max(
                                        0,
                                        (float) $probability
                                    )
                                ); ?>%;"
                            >
                            </div>

                        </div>


                        <div class="probability-caption">

                            <span>
                                Probability
                            </span>

                            <span id="previewProbability">
                                <?php echo htmlspecialchars($probability); ?>%
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Setup Guide -->

                <div class="side-card">

                    <div class="side-title">

                        <h3>
                            Deal Setup Guide
                        </h3>

                        <p>
                            Recommended information for a complete opportunity record.
                        </p>

                    </div>


                    <ul class="guide-list">

                        <li>

                            <span class="guide-number">
                                1
                            </span>

                            <span>
                                Enter a clear title for the sales opportunity.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                2
                            </span>

                            <span>
                                Link the relevant company, contact, and customer.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                3
                            </span>

                            <span>
                                Set the current sales stage and probability.
                            </span>

                        </li>


                        <li>

                            <span class="guide-number">
                                4
                            </span>

                            <span>
                                Assign the deal to the appropriate team member.
                            </span>

                        </li>

                    </ul>

                </div>


                <!-- Quick Tip -->

                <div class="side-card">

                    <div class="tip-box">

                        <strong>
                            💡 Quick Tip
                        </strong>

                        Keep the deal stage, amount, probability, owner, and expected close date updated for accurate sales tracking.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const titleInput =
        document.getElementById("title");

    const companyInput =
        document.getElementById("company_id");

    const contactInput =
        document.getElementById("contact_id");

    const customerInput =
        document.getElementById("customer_id");

    const assignedInput =
        document.getElementById("assigned_to");

    const amountInput =
        document.getElementById("amount");

    const probabilityInput =
        document.getElementById("probability");

    const closeDateInput =
        document.getElementById("expected_close_date");

    const stageInputs =
        document.querySelectorAll(
            'input[name="stage"]'
        );


    const previewAvatar =
        document.getElementById("previewAvatar");

    const previewTitle =
        document.getElementById("previewTitle");

    const previewStageText =
        document.getElementById("previewStageText");

    const previewStage =
        document.getElementById("previewStage");

    const previewCompany =
        document.getElementById("previewCompany");

    const previewContact =
        document.getElementById("previewContact");

    const previewCustomer =
        document.getElementById("previewCustomer");

    const previewAssigned =
        document.getElementById("previewAssigned");

    const previewCloseDate =
        document.getElementById("previewCloseDate");

    const previewAmount =
        document.getElementById("previewAmount");

    const previewProbability =
        document.getElementById("previewProbability");

    const probabilityFill =
        document.getElementById("probabilityFill");

    const summaryAvatar =
        document.getElementById("summaryAvatar");

    const summaryTitle =
        document.getElementById("summaryTitle");

    const summaryStage =
        document.getElementById("summaryStage");


    const stageLabels = {
        prospecting: "Prospecting",
        qualification: "Qualification",
        proposal: "Proposal",
        negotiation: "Negotiation",
        closed_won: "Closed Won",
        closed_lost: "Closed Lost"
    };


    function updatePreview() {

        /* ------------------------------------------------------------
           Deal Title
        ------------------------------------------------------------ */

        const title =
            titleInput.value.trim();

        previewTitle.textContent =
            title || "New Deal";

        summaryTitle.textContent =
            title || "New Deal";


        /* ------------------------------------------------------------
           Avatar
        ------------------------------------------------------------ */

        const initial =
            title
                ? title.charAt(0).toUpperCase()
                : "D";

        previewAvatar.textContent =
            initial;

        summaryAvatar.textContent =
            initial;


        /* ------------------------------------------------------------
           Company
        ------------------------------------------------------------ */

        if (companyInput.value) {

            const option =
                companyInput.options[
                    companyInput.selectedIndex
                ];

            previewCompany.textContent =
                option.text.trim();

        } else {

            previewCompany.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Contact
        ------------------------------------------------------------ */

        if (contactInput.value) {

            const option =
                contactInput.options[
                    contactInput.selectedIndex
                ];

            previewContact.textContent =
                option.text.trim();

        } else {

            previewContact.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Customer
        ------------------------------------------------------------ */

        if (customerInput.value) {

            const option =
                customerInput.options[
                    customerInput.selectedIndex
                ];

            previewCustomer.textContent =
                option.text.trim();

        } else {

            previewCustomer.textContent =
                "Not selected";
        }


        /* ------------------------------------------------------------
           Assigned User
        ------------------------------------------------------------ */

        if (assignedInput.value) {

            const option =
                assignedInput.options[
                    assignedInput.selectedIndex
                ];

            previewAssigned.textContent =
                option.text.trim();

        } else {

            previewAssigned.textContent =
                "Not assigned";
        }


        /* ------------------------------------------------------------
           Close Date
        ------------------------------------------------------------ */

        const closeDate =
            closeDateInput.value;

        previewCloseDate.textContent =
            closeDate || "Not set";


        /* ------------------------------------------------------------
           Amount
        ------------------------------------------------------------ */

        let amount =
            parseFloat(amountInput.value);

        if (isNaN(amount)) {
            amount = 0;
        }

        previewAmount.textContent =
            "₹" +
            amount.toLocaleString(
                "en-IN",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );


        /* ------------------------------------------------------------
           Probability
        ------------------------------------------------------------ */

        let probability =
            parseFloat(probabilityInput.value);

        if (isNaN(probability)) {
            probability = 0;
        }

        probability =
            Math.max(
                0,
                Math.min(
                    100,
                    probability
                )
            );

        previewProbability.textContent =
            probability + "%";

        probabilityFill.style.width =
            probability + "%";


        /* ------------------------------------------------------------
           Stage
        ------------------------------------------------------------ */

        let selectedStage =
            "prospecting";

        stageInputs.forEach(function (input) {

            if (input.checked) {

                selectedStage =
                    input.value;

            }

        });

        const stageName =
            stageLabels[selectedStage] ||
            "Prospecting";


        previewStageText.textContent =
            stageName;

        summaryStage.textContent =
            stageName;

        previewStage.textContent =
            stageName;


        previewStage.classList.remove(
            "prospecting",
            "qualification",
            "proposal",
            "negotiation",
            "closed_won",
            "closed_lost"
        );

        previewStage.classList.add(
            selectedStage
        );

    }


    /* ------------------------------------------------------------
       Event Listeners
    ------------------------------------------------------------ */

    titleInput.addEventListener(
        "input",
        updatePreview
    );

    companyInput.addEventListener(
        "change",
        updatePreview
    );

    contactInput.addEventListener(
        "change",
        updatePreview
    );

    customerInput.addEventListener(
        "change",
        updatePreview
    );

    assignedInput.addEventListener(
        "change",
        updatePreview
    );

    amountInput.addEventListener(
        "input",
        updatePreview
    );

    probabilityInput.addEventListener(
        "input",
        updatePreview
    );

    closeDateInput.addEventListener(
        "change",
        updatePreview
    );

    stageInputs.forEach(function (input) {

        input.addEventListener(
            "change",
            updatePreview
        );

    });


    updatePreview();

});

</script>

</body>

</html>