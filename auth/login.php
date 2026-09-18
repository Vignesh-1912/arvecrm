<?php

session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: ../dashboard/index.php");
    exit;
}

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                email,
                password,
                role
            FROM users
            WHERE email = :email
              AND status = 1
            LIMIT 1
        ");

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
<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sign in — Relate CRM</title>

    <style>

        :root {
            --navy: #10233f;
            --blue: #1c6ce5;
            --cyan: #35c9e4;
            --ink: #172b4d;
            --muted: #66758d;
            --border: #dce5f1;
            --pale: #f5f8fc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                "Segoe UI",
                sans-serif;
            color: var(--ink);
            background: var(--pale);
        }

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
        }

        /* ==========================
           LEFT VISUAL SECTION
        ========================== */

        .visual {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 44px clamp(40px, 7vw, 110px);
            color: #ffffff;
            background:
                linear-gradient(
                    145deg,
                    #10233f,
                    #174b91
                );
        }

        .visual::before {
            content: "";
            position: absolute;
            width: 700px;
            height: 700px;
            right: -260px;
            top: -350px;
            border-radius: 50%;
            background: #38c8e42b;
        }

        .visual::after {
            content: "";
            position: absolute;
            width: 460px;
            height: 460px;
            bottom: -280px;
            left: -170px;
            border: 1px solid #ffffff22;
            border-radius: 50%;
            box-shadow:
                0 0 0 70px #ffffff08,
                0 0 0 140px #ffffff06;
        }

        .brand {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 10px;
            align-items: center;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        .brand-mark {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #ffffff;
            color: #1768d9;
            font-size: 20px;
            font-weight: 800;
        }

        .scene {
            position: relative;
            z-index: 1;
            margin: auto 0;
        }

        .label {
            display: inline-flex;
            gap: 7px;
            align-items: center;
            border: 1px solid #ffffff30;
            border-radius: 99px;
            padding: 7px 11px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.09em;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #55e2ad;
        }

        .scene h1 {
            max-width: 600px;
            margin: 18px 0;
            font-size: clamp(42px, 5vw, 65px);
            line-height: 1.02;
            letter-spacing: -0.065em;
        }

        .scene p {
            max-width: 475px;
            margin: 0;
            color: #d2e1f7;
            font-size: 16px;
            line-height: 1.7;
        }

        .cards {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 15px;
            margin-top: 46px;
        }

        .stat {
            min-width: 145px;
            padding: 16px;
            border: 1px solid #ffffff24;
            border-radius: 13px;
            background: #ffffff12;
            backdrop-filter: blur(8px);
        }

        .stat strong {
            display: block;
            font-size: 20px;
        }

        .stat span {
            display: block;
            margin-top: 4px;
            color: #d3e1f5;
            font-size: 11px;
        }

        /* ==========================
           LOGIN SECTION
        ========================== */

        .signin {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background: #ffffff;
        }

        .box {
            width: min(100%, 390px);
        }

        .mobile-brand {
            display: none;
        }

        .box h2 {
            margin: 0 0 9px;
            font-size: 30px;
            letter-spacing: -1.1px;
        }

        .intro {
            margin: 0 0 31px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.035em;
            color: #41536e;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 9px;
            outline: 0;
            font: inherit;
            transition: 0.2s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--blue);
            box-shadow:
                0 0 0 4px #1c6ce51c;
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 18px 0 26px;
            font-size: 13px;
        }

        .remember {
            display: flex;
            gap: 7px;
            align-items: center;
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0;
        }

        .remember input {
            width: 15px;
            height: 15px;
            accent-color: var(--blue);
        }

        a {
            color: var(--blue);
            font-weight: 700;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .main-button {
            width: 100%;
            border: 0;
            border-radius: 9px;
            padding: 14px;
            background: var(--blue);
            box-shadow: 0 9px 18px #1c6ce533;
            color: #ffffff;
            font: 800 14px/1 inherit;
            cursor: pointer;
            transition: 0.2s;
        }

        .main-button:hover {
            background: #135bc9;
            transform: translateY(-1px);
        }

        .error-message {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 26px 0;
            color: #95a3b8;
            font-size: 11px;
            font-weight: 700;
        }

        .divider::before,
        .divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: var(--border);
        }

        .sso {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 13px;
            background: #ffffff;
            color: #334761;
            font: 700 14px/1 inherit;
            cursor: pointer;
        }

        .join {
            margin: 27px 0 0;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        .trust {
            margin-top: 35px;
            padding-top: 21px;
            border-top: 1px solid #e8edf5;
            text-align: center;
            color: #8997aa;
            font-size: 11px;
        }

        /* ==========================
           RESPONSIVE
        ========================== */

        @media (max-width: 850px) {

            .page {
                grid-template-columns: 1fr;
            }

            .visual {
                display: none;
            }

            .signin {
                min-height: 100vh;
                padding: 30px 24px;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 9px;
                margin-bottom: 45px;
                font-size: 19px;
                font-weight: 800;
            }

            .mobile-brand .brand-mark {
                background: var(--blue);
                color: #ffffff;
            }
        }

    </style>

</head>

<body>

<main class="page">

    <!-- LEFT SIDE -->

    <section class="visual">

        <div class="brand">

            <span class="brand-mark">
                R
            </span>

            relate

            <span
                style="
                    font-weight:500;
                    color:#bcd6f8;
                "
            >
                CRM
            </span>

        </div>


        <div class="scene">

            <span class="label">

                <i class="dot"></i>

                CUSTOMER RELATIONSHIPS, SIMPLIFIED

            </span>


            <h1>
                Every customer story,
                <br>
                moving forward.
            </h1>


            <p>
                Bring conversations, opportunities,
                and relationships into one clear,
                connected workspace.
            </p>


            <div class="cards">

                <div class="stat">

                    <strong>
                        10k+
                    </strong>

                    <span>
                        growing teams
                    </span>

                </div>


                <div class="stat">

                    <strong>
                        99.99%
                    </strong>

                    <span>
                        secure uptime
                    </span>

                </div>

            </div>

        </div>

    </section>


    <!-- LOGIN SIDE -->

    <section class="signin">

        <div class="box">

            <div class="mobile-brand">

                <span class="brand-mark">
                    R
                </span>

                relate CRM

            </div>


            <h2>
                Welcome back
            </h2>


            <p class="intro">
                Sign in to manage your customers
                and grow your pipeline.
            </p>


            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <?php
                    echo htmlspecialchars(
                        $error
                    );
                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
            >

                <label for="email">
                    EMAIL ADDRESS
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="you@company.com"
                    autocomplete="email"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST["email"] ?? ""
                        );
                    ?>"
                    required
                >


                <label for="password">
                    PASSWORD
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >


                <div class="actions">

                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        Keep me signed in

                    </label>


                    <a href="#">
                        Forgot password?
                    </a>

                </div>


                <button
                    class="main-button"
                    type="submit"
                >
                    Sign in to Relate
                </button>

            </form>


            <div class="divider">
                OR
            </div>


            <button
                class="sso"
                type="button"
                onclick="alert('Google sign-in is not configured yet.')"
            >
                Continue with Google
            </button>


            <p class="join">

                New to Relate?

                <a href="#">
                    Request a demo
                </a>

            </p>


            <p class="trust">
                🔒 Your data is protected with
                enterprise-grade security.
            </p>

        </div>

    </section>

</main>

</body>

</html>