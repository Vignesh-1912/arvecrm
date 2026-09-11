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

            .cards {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">

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