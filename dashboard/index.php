<?php

session_start();

if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");
    exit;

}

require_once "../config/database.php";

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CRM Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #1e293b;
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin-bottom: 5px;
            border-radius: 5px;
        }

        .sidebar a:hover {
            background: #334155;
        }

        .main {
            margin-left: 240px;
            padding: 25px;
        }

        .topbar {
            background: white;
            padding: 18px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin-top: 0;
            color: #555;
        }

        .card .number {
            font-size: 30px;
            font-weight: bold;
        }

        .welcome {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-top: 25px;
        }

        @media (max-width: 900px) {

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <!-- Sidebar -->

    <div class="sidebar">

        <h2>CRM</h2>

        <a href="index.php">Dashboard</a>

        <a href="../customers/index.php">Customers</a>

        <a href="../companies/index.php">Companies</a>

        <a href="../contacts/index.php">Contacts</a>

        <a href="../leads/index.php">Leads</a>

        <a href="../deals/index.php">Deals</a>

        <a href="../products/index.php">Products</a>

        <a href="../quotes/index.php">Quotes</a>

        <a href="../sales/index.php">Sales</a>

        <a href="../tasks/index.php">Tasks</a>

        <a href="../reports/index.php">Reports</a>

        <a href="../auth/logout.php">Logout</a>

    </div>


    <!-- Main Content -->

    <div class="main">

        <div class="topbar">

            <h1>Dashboard</h1>

            <div>

                Welcome,
                <strong>
                    <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                </strong>

            </div>

        </div>


        <!-- Dashboard Cards -->

        <div class="cards">

            <div class="card">

                <h3>Customers</h3>

                <div class="number">0</div>

            </div>


            <div class="card">

                <h3>Leads</h3>

                <div class="number">0</div>

            </div>


            <div class="card">

                <h3>Deals</h3>

                <div class="number">0</div>

            </div>


            <div class="card">

                <h3>Sales</h3>

                <div class="number">0</div>

            </div>

        </div>


        <!-- Welcome Section -->

        <div class="welcome">

            <h2>Welcome to your CRM</h2>

            <p>
                You have successfully logged into the CRM system.
            </p>

            <p>
                Use the menu on the left to manage customers,
                leads, deals, products, quotes and sales.
            </p>

        </div>

    </div>

</body>

</html>