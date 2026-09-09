<?php

session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $error = "Please enter email and password.";

    } else {

        $sql = "SELECT * FROM users WHERE email = :email AND status = 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":email" => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];

            header("Location: ../dashboard/index.php");
            exit;

        } else {

            $error = "Invalid email or password.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CRM Login</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            width: 400px;
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            border: none;
            background: #2563eb;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-btn:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

    </style>

</head>

<body>

    <div class="login-container">

        <h2>CRM Login</h2>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>

            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button type="submit" class="login-btn">
                Login
            </button>

        </form>

    </div>

</body>

</html>
