<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/* Get Counts */

$customers = $conn->query("SELECT COUNT(*) FROM customers")->fetchColumn();

$companies = $conn->query("SELECT COUNT(*) FROM companies")->fetchColumn();

$contacts = $conn->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

$leads = $conn->query("SELECT COUNT(*) FROM leads")->fetchColumn();

$deals = $conn->query("SELECT COUNT(*) FROM deals")->fetchColumn();

$products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();

$quotes = $conn->query("SELECT COUNT(*) FROM quotes")->fetchColumn();

$sales = $conn->query("SELECT COUNT(*) FROM sales")->fetchColumn();

$tasks = $conn->query("SELECT COUNT(*) FROM tasks")->fetchColumn();

$activities = $conn->query("SELECT COUNT(*) FROM activities")->fetchColumn();


/* Get Amounts */

$total_sales = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM sales
")->fetchColumn();

$total_deals = $conn->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM deals
")->fetchColumn();

$total_quotes = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM quotes
")->fetchColumn();

?>

<!DOCTYPE html>
<html>

<head>

    <title>Reports</title>

    <link rel="stylesheet" href="/crm/assets/css/sidebar.css">

    <style>

        .report-header {
            margin-bottom: 25px;
        }

        .report-header h1 {
            margin-bottom: 5px;
        }

        .report-header p {
            color: #6b7280;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        /* Clickable Report Card */

        .report-card {
            display: block;
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-decoration: none;
            transition: 0.2s;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }

        .report-card h3 {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
            font-weight: normal;
        }

        .report-card .number {
            margin-top: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #111827;
        }

        .report-card .view-report {
            margin-top: 12px;
            font-size: 13px;
            color: #2563eb;
            font-weight: bold;
        }

        /* Reports Not Available Yet */

        .report-card.disabled {
            cursor: default;
        }

        .report-card.disabled:hover {
            transform: none;
        }

        .report-card.disabled .view-report {
            color: #9ca3af;
        }

        /* Financial Section */

        .amount-section {
            margin-top: 30px;
        }

        .amount-section h2 {
            margin-bottom: 20px;
        }

        .amount-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .amount-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .amount-card h3 {
            margin: 0;
            color: #6b7280;
            font-size: 15px;
        }

        .amount {
            margin-top: 10px;
            font-size: 25px;
            font-weight: bold;
        }

        @media (max-width: 1000px) {

            .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .amount-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .report-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="report-header">

        <h1>📈 Reports</h1>

        <p>CRM business overview and statistics</p>

    </div>


    <!-- Record Reports -->

    <div class="report-grid">


        <!-- Customers -->

        <a href="customers.php" class="report-card">

            <h3>Customers</h3>

            <div class="number">
                <?php echo $customers; ?>
            </div>

            <div class="view-report">
                View Customer Report →
            </div>

        </a>


        <!-- Companies -->

        <div class="report-card disabled">

            <h3>Companies</h3>

            <div class="number">
                <?php echo $companies; ?>
            </div>

            <div class="view-report">
                Company Report Coming Soon
            </div>

        </div>


        <!-- Contacts -->

        <div class="report-card disabled">

            <h3>Contacts</h3>

            <div class="number">
                <?php echo $contacts; ?>
            </div>

            <div class="view-report">
                Contact Report Coming Soon
            </div>

        </div>


        <!-- Leads -->

        <a href="leads.php" class="report-card">

            <h3>Leads</h3>

            <div class="number">
                <?php echo $leads; ?>
            </div>

            <div class="view-report">
                View Lead Report →
            </div>

        </a>


        <!-- Deals -->

        <a href="deals.php" class="report-card">

            <h3>Deals</h3>

            <div class="number">
                <?php echo $deals; ?>
            </div>

            <div class="view-report">
                View Deal Report →
            </div>

        </a>


        <!-- Products -->

        <div class="report-card disabled">

            <h3>Products</h3>

            <div class="number">
                <?php echo $products; ?>
            </div>

            <div class="view-report">
                Product Report Coming Soon
            </div>

        </div>


        <!-- Quotes -->

        <a href="quotes.php" class="report-card">

            <h3>Quotes</h3>

            <div class="number">
                <?php echo $quotes; ?>
            </div>

            <div class="view-report">
                View Quote Report →
            </div>

        </a>


        <!-- Sales -->

        <a href="sales.php" class="report-card">

            <h3>Sales</h3>

            <div class="number">
                <?php echo $sales; ?>
            </div>

            <div class="view-report">
                View Sales Report →
            </div>

        </a>


        <!-- Tasks -->

        <a href="tasks_activities.php" class="report-card">

            <h3>Tasks</h3>

            <div class="number">
                <?php echo $tasks; ?>
            </div>

            <div class="view-report">
                View Tasks Report →
            </div>

        </a>


        <!-- Activities -->

        <a href="tasks_activities.php" class="report-card">

            <h3>Activities</h3>

            <div class="number">
                <?php echo $activities; ?>
            </div>

            <div class="view-report">
                View Activities Report →
            </div>

        </a>

    </div>


    <!-- Financial Reports -->

    <div class="amount-section">

        <h2>Financial Overview</h2>

        <div class="amount-grid">

            <div class="amount-card">

                <h3>Total Sales</h3>

                <div class="amount">
                    ₹<?php echo number_format($total_sales, 2); ?>
                </div>

            </div>


            <div class="amount-card">

                <h3>Total Deal Value</h3>

                <div class="amount">
                    ₹<?php echo number_format($total_deals, 2); ?>
                </div>

            </div>


            <div class="amount-card">

                <h3>Total Quote Value</h3>

                <div class="amount">
                    ₹<?php echo number_format($total_quotes, 2); ?>
                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>