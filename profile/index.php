<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        role
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ":id" => $user_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile - CRM</title>

    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 30px;
        }

        .header {
            background: #1e293b;
            color: white;
            padding: 10px 30px;
            min-height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }

        .profile-card {
            background: white;
            margin-top: 25px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            max-width: 700px;
        }

        .profile-top {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
        }

        .profile-top h2 {
            margin: 0 0 5px;
            color: #172554;
        }

        .profile-top p {
            margin: 0;
            color: #64748b;
        }

        .profile-info {
            display: grid;
            gap: 18px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #475569;
        }

        .info-value {
            color: #172554;
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 220px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <div class="header">

        <h1>My Profile</h1>

        <a
            href="../dashboard/index.php"
            class="back-btn"
        >
            ← Back to Dashboard
        </a>

    </div>

    <div class="profile-card">

        <div class="profile-top">

            <div class="profile-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $user["name"],
                        0,
                        1
                    )
                );

                ?>

            </div>

            <div>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $user["name"]
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $user["role"]
                    );
                    ?>
                </p>

            </div>

        </div>

        <div class="profile-info">

            <div class="info-row">

                <span class="info-label">
                    Name
                </span>

                <span class="info-value">
                    <?php
                    echo htmlspecialchars(
                        $user["name"]
                    );
                    ?>
                </span>

            </div>

            <div class="info-row">

                <span class="info-label">
                    Email
                </span>

                <span class="info-value">
                    <?php
                    echo htmlspecialchars(
                        $user["email"]
                    );
                    ?>
                </span>

            </div>

            <div class="info-row">

                <span class="info-label">
                    Role
                </span>

                <span class="info-value">
                    <?php
                    echo htmlspecialchars(
                        $user["role"]
                    );
                    ?>
                </span>

            </div>

        </div>

    </div>

</div>

</body>

</html>
