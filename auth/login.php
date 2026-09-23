<?php

declare(strict_types=1);

session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        try {

            $sql = "SELECT *
                    FROM users
                    WHERE email = :email
                    AND status = 1
                    LIMIT 1";

            /*
             * database.php creates the PDO connection as $conn
             */
            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":email" => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {

                /*
                 * Regenerate session ID after successful login
                 */
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                header("Location: ../dashboard/index.php");
                exit;

            } else {

                $error = "The email or password you entered is incorrect.";

            }

        } catch (PDOException $e) {

            error_log("Login error: " . $e->getMessage());

            $error = "Unable to process your login. Please try again.";

        }

    }
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

    <meta
        name="description"
        content="ARVE CRM secure login"
    >

    <title>Sign In | ARVE CRM</title>


    <style>

        /* =====================================================
           ROOT
        ===================================================== */

        :root {

            --background: #050816;

            --background-secondary: #080d1d;

            --card: rgba(15, 23, 42, 0.76);

            --border: rgba(255, 255, 255, 0.09);

            --text: #f8fafc;

            --text-secondary: #cbd5e1;

            --muted: #94a3b8;

            --muted-dark: #64748b;

            --blue: #3b82f6;

            --blue-light: #60a5fa;

            --blue-dark: #2563eb;

            --purple: #7c3aed;

            --danger: #ef4444;

        }


        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html,
        body {

            width: 100%;

            min-height: 100%;

        }


        body {

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
                sans-serif;

            background: var(--background);

            color: var(--text);

            overflow-x: hidden;

        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page {

            min-height: 100vh;

            position: relative;

            display: flex;

            overflow: hidden;

            background:
                radial-gradient(
                    circle at 15% 20%,
                    rgba(37, 99, 235, 0.09),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 85% 80%,
                    rgba(124, 58, 237, 0.08),
                    transparent 30%
                ),
                var(--background);

        }


        /* =====================================================
           GRID
        ===================================================== */

        .background-grid {

            position: absolute;

            inset: 0;

            background-image:
                linear-gradient(
                    rgba(255, 255, 255, 0.025) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0.025) 1px,
                    transparent 1px
                );

            background-size: 52px 52px;

            mask-image:
                linear-gradient(
                    to bottom,
                    black 0%,
                    transparent 90%
                );

            pointer-events: none;

        }


        /* =====================================================
           BACKGROUND GLOWS
        ===================================================== */

        .glow {

            position: absolute;

            border-radius: 50%;

            filter: blur(100px);

            pointer-events: none;

        }


        .glow-one {

            width: 520px;

            height: 520px;

            left: -230px;

            top: -220px;

            background:
                rgba(37, 99, 235, 0.20);

        }


        .glow-two {

            width: 480px;

            height: 480px;

            right: -220px;

            bottom: -220px;

            background:
                rgba(124, 58, 237, 0.15);

        }


        .glow-three {

            width: 280px;

            height: 280px;

            right: 30%;

            top: 35%;

            background:
                rgba(14, 165, 233, 0.07);

        }


        /* =====================================================
           LEFT SIDE
        ===================================================== */

        .brand-panel {

            width: 56%;

            min-height: 100vh;

            position: relative;

            z-index: 2;

            padding: 55px 70px;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

        }


        /* =====================================================
           BRAND
        ===================================================== */

        .brand {

            display: flex;

            align-items: center;

            gap: 13px;

        }


        .brand-mark {

            width: 44px;

            height: 44px;

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #60a5fa,
                    #2563eb 55%,
                    #1d4ed8
                );

            box-shadow:
                0 12px 35px
                rgba(37, 99, 235, 0.35);

        }


        .brand-mark svg {

            width: 23px;

            height: 23px;

        }


        .brand-name {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -0.7px;

        }


        .brand-name span {

            color: var(--blue-light);

        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero-content {

            max-width: 650px;

            margin-top: -35px;

            animation:
                heroIn
                0.8s
                cubic-bezier(.16, 1, .3, 1);

        }


        .eyebrow {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            padding: 8px 14px;

            border-radius: 50px;

            border:
                1px solid
                rgba(96, 165, 250, 0.20);

            background:
                rgba(59, 130, 246, 0.07);

            color: #93c5fd;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.4px;

            text-transform: uppercase;

            margin-bottom: 25px;

        }


        .eyebrow-dot {

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background: #60a5fa;

            box-shadow:
                0 0 12px
                #60a5fa;

            animation:
                pulse 2s infinite;

        }


        .hero-content h1 {

            max-width: 670px;

            font-size:
                clamp(45px, 5vw, 76px);

            line-height: 0.98;

            letter-spacing: -4.5px;

            font-weight: 800;

        }


        .gradient-text {

            background:
                linear-gradient(
                    100deg,
                    #ffffff 0%,
                    #93c5fd 45%,
                    #60a5fa 100%
                );

            -webkit-background-clip: text;

            background-clip: text;

            color: transparent;

        }


        .hero-content p {

            max-width: 530px;

            margin-top: 28px;

            color: var(--muted);

            font-size: 15px;

            line-height: 1.8;

        }


        /* =====================================================
           FEATURES
        ===================================================== */

        .features {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 35px;

        }


        .feature {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 10px 13px;

            border:
                1px solid
                rgba(255, 255, 255, 0.07);

            background:
                rgba(255, 255, 255, 0.025);

            border-radius: 10px;

            color: var(--text-secondary);

            font-size: 11px;

            font-weight: 500;

        }


        .feature-icon {

            width: 26px;

            height: 26px;

            border-radius: 7px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(59, 130, 246, 0.12);

            color: var(--blue-light);

        }


        .feature-icon svg {

            width: 14px;

            height: 14px;

        }


        /* =====================================================
           COPYRIGHT
        ===================================================== */

        .copyright {

            color: #475569;

            font-size: 10px;

        }


        /* =====================================================
           LOGIN PANEL
        ===================================================== */

        .login-panel {

            width: 44%;

            min-height: 100vh;

            position: relative;

            z-index: 5;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 50px 70px;

        }


        /* =====================================================
           LOGIN CARD
        ===================================================== */

        .login-card {

            width: 100%;

            max-width: 465px;

            padding: 43px;

            border-radius: 27px;

            border:
                1px solid
                var(--border);

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,0.055),
                    rgba(255,255,255,0.018)
                );

            backdrop-filter:
                blur(28px);

            -webkit-backdrop-filter:
                blur(28px);

            box-shadow:
                0 35px 90px
                rgba(0,0,0,0.45),

                inset
                0 1px 0
                rgba(255,255,255,0.05);

            animation:
                cardIn
                0.7s
                cubic-bezier(.16, 1, .3, 1);

        }


        /* =====================================================
           MOBILE BRAND
        ===================================================== */

        .mobile-brand {

            display: none;

            align-items: center;

            justify-content: center;

            gap: 11px;

            margin-bottom: 32px;

        }


        /* =====================================================
           LOGIN HEADER
        ===================================================== */

        .login-header {

            margin-bottom: 30px;

        }


        .login-header h2 {

            font-size: 30px;

            line-height: 1.1;

            letter-spacing: -1.3px;

            font-weight: 800;

        }


        .login-header p {

            margin-top: 9px;

            color: var(--muted);

            font-size: 13px;

            line-height: 1.6;

        }


        /* =====================================================
           ERROR
        ===================================================== */

        .error-message {

            display: flex;

            align-items: flex-start;

            gap: 11px;

            padding: 13px 14px;

            margin-bottom: 21px;

            border-radius: 11px;

            border:
                1px solid
                rgba(239, 68, 68, 0.20);

            background:
                rgba(239, 68, 68, 0.08);

            color: #fca5a5;

            font-size: 12px;

            line-height: 1.5;

        }


        .error-message svg {

            width: 17px;

            min-width: 17px;

            margin-top: 1px;

        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

        .form-group {

            margin-bottom: 20px;

        }


        .form-label {

            display: block;

            margin-bottom: 9px;

            color: var(--text-secondary);

            font-size: 12px;

            font-weight: 600;

        }


        .input-wrap {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform:
                translateY(-50%);

            color:
                var(--muted-dark);

            pointer-events: none;

        }


        .input-icon svg {

            width: 17px;

            height: 17px;

        }


        .form-input {

            width: 100%;

            height: 52px;

            padding:
                0 48px;

            border-radius: 11px;

            border:
                1px solid
                rgba(148,163,184,0.14);

            outline: none;

            background:
                rgba(2,6,23,0.35);

            color:
                var(--text);

            font-family: inherit;

            font-size: 13px;

            transition:
                border-color 0.25s ease,
                background 0.25s ease,
                box-shadow 0.25s ease;

        }


        .form-input::placeholder {

            color: #475569;

        }


        .form-input:hover {

            border-color:
                rgba(148,163,184,0.25);

        }


        .form-input:focus {

            border-color:
                rgba(59,130,246,0.65);

            background:
                rgba(2,6,23,0.50);

            box-shadow:
                0 0 0 4px
                rgba(59,130,246,0.08),

                0 10px 30px
                rgba(0,0,0,0.15);

        }


        /* =====================================================
           PASSWORD TOGGLE
        ===================================================== */

        .password-toggle {

            position: absolute;

            right: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            border: none;

            background: transparent;

            color:
                var(--muted-dark);

            cursor: pointer;

            padding: 5px;

        }


        .password-toggle:hover {

            color:
                var(--blue-light);

        }


        .password-toggle svg {

            width: 17px;

            height: 17px;

        }


        /* =====================================================
           OPTIONS
        ===================================================== */

        .form-options {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin:
                3px 0 25px;

        }


        .remember {

            display: flex;

            align-items: center;

            gap: 8px;

            color:
                var(--muted);

            font-size: 11px;

            cursor: pointer;

        }


        .remember-checkbox {

            width: 15px !important;

            height: 15px !important;

            padding: 0 !important;

            cursor: pointer;

            accent-color:
                var(--blue);

        }


        .forgot-password {

            color:
                var(--blue-light);

            text-decoration: none;

            font-size: 11px;

            font-weight: 600;

        }


        .forgot-password:hover {

            color:
                #93c5fd;

        }


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

        .login-button {

            width: 100%;

            height: 52px;

            border: none;

            border-radius: 11px;

            cursor: pointer;

            color: #ffffff;

            font-family: inherit;

            font-size: 13px;

            font-weight: 700;

            background:
                linear-gradient(
                    100deg,
                    #2563eb,
                    #3b82f6,
                    #2563eb
                );

            background-size:
                200% 100%;

            box-shadow:
                0 12px 30px
                rgba(37,99,235,0.25);

            transition:
                0.3s ease;

        }


        .login-button:hover {

            background-position:
                100% 0;

            transform:
                translateY(-1px);

            box-shadow:
                0 17px 38px
                rgba(37,99,235,0.32);

        }


        .login-button:active {

            transform:
                translateY(0);

        }


        .button-content {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

        }


        .button-content svg {

            width: 16px;

            height: 16px;

            transition:
                transform 0.2s ease;

        }


        .login-button:hover
        .button-content svg {

            transform:
                translateX(3px);

        }


        /* =====================================================
           SECURITY
        ===================================================== */

        .security-note {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: 21px;

            color: #475569;

            font-size: 10px;

        }


        .security-note svg {

            width: 13px;

            height: 13px;

        }


        /* =====================================================
           ANIMATIONS
        ===================================================== */

        @keyframes cardIn {

            from {

                opacity: 0;

                transform:
                    translateY(25px)
                    scale(0.98);

            }

            to {

                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);

            }

        }


        @keyframes heroIn {

            from {

                opacity: 0;

                transform:
                    translateX(-25px);

            }

            to {

                opacity: 1;

                transform:
                    translateX(0);

            }

        }


        @keyframes pulse {

            0%,
            100% {

                opacity: 1;

                transform: scale(1);

            }

            50% {

                opacity: 0.5;

                transform: scale(0.75);

            }

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .brand-panel {

                width: 50%;

                padding:
                    45px;

            }


            .login-panel {

                width: 50%;

                padding:
                    35px;

            }


            .hero-content h1 {

                font-size:
                    52px;

            }

        }


        @media (max-width: 820px) {

            .page {

                display: block;

            }


            .brand-panel {

                display: none;

            }


            .login-panel {

                width: 100%;

                min-height: 100vh;

                padding:
                    25px 18px;

            }


            .login-card {

                max-width: 450px;

                padding:
                    35px 27px;

            }


            .mobile-brand {

                display: flex;

            }

        }


        @media (max-width: 420px) {

            .login-card {

                padding:
                    30px 20px;

                border-radius:
                    20px;

            }


            .login-header h2 {

                font-size:
                    27px;

            }

        }

    </style>

</head>


<body>


<div class="page">


    <!-- BACKGROUND -->

    <div class="background-grid"></div>

    <div class="glow glow-one"></div>

    <div class="glow glow-two"></div>

    <div class="glow glow-three"></div>


    <!-- =====================================================
         LEFT BRAND PANEL
    ====================================================== -->

    <section class="brand-panel">


        <!-- BRAND -->

        <div class="brand">

            <div class="brand-mark">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="white"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <path
                        d="M12 2L4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6l-8-4z"
                    />

                    <path
                        d="M8.5 12l2.2 2.2 4.8-5"
                    />

                </svg>

            </div>


            <div class="brand-name">

                ARVE<span>CRM</span>

            </div>

        </div>


        <!-- HERO -->

        <div class="hero-content">


            <div class="eyebrow">

                <span class="eyebrow-dot"></span>

                Intelligent CRM Platform

            </div>


            <h1>

                Everything your

                <span class="gradient-text">

                    business needs.

                </span>

            </h1>


            <p>

                Manage customers, leads, deals, products and sales
                from one powerful workspace built to keep your
                entire business connected.

            </p>


            <!-- FEATURES -->

            <div class="features">


                <!-- FEATURE 1 -->

                <div class="feature">

                    <div class="feature-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path d="M12 3v18"/>

                            <path d="M3 12h18"/>

                        </svg>

                    </div>

                    Smart Workflow

                </div>


                <!-- FEATURE 2 -->

                <div class="feature">

                    <div class="feature-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path
                                d="M12 3a9 9 0 1 0 9 9"
                            />

                            <path
                                d="M12 7v5l3 2"
                            />

                        </svg>

                    </div>

                    Real-time Insights

                </div>


                <!-- FEATURE 3 -->

                <div class="feature">

                    <div class="feature-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="5"
                                y="11"
                                width="14"
                                height="10"
                                rx="2"
                            />

                            <path
                                d="M8 11V8a4 4 0 0 1 8 0v3"
                            />

                        </svg>

                    </div>

                    Secure Access

                </div>


            </div>

        </div>


        <!-- COPYRIGHT -->

        <div class="copyright">

            © <?= date("Y") ?> ARVE CRM · All rights reserved.

        </div>


    </section>


    <!-- =====================================================
         LOGIN PANEL
    ====================================================== -->

    <section class="login-panel">


        <div class="login-card">


            <!-- MOBILE BRAND -->

            <div class="mobile-brand">


                <div class="brand-mark">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="white"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path
                            d="M12 2L4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6l-8-4z"
                        />

                        <path
                            d="M8.5 12l2.2 2.2 4.8-5"
                        />

                    </svg>

                </div>


                <div class="brand-name">

                    ARVE<span>CRM</span>

                </div>


            </div>


            <!-- HEADER -->

            <div class="login-header">

                <h2>

                    Welcome back

                </h2>


                <p>

                    Sign in to continue to your workspace.

                </p>

            </div>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ""): ?>

                <div class="error-message">


                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        />

                        <path d="M12 8v4"/>

                        <path d="M12 16h.01"/>

                    </svg>


                    <span>

                        <?= htmlspecialchars($error) ?>

                    </span>


                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                method="POST"
                action=""
                autocomplete="on"
            >


                <!-- EMAIL -->

                <div class="form-group">


                    <label
                        class="form-label"
                        for="email"
                    >

                        Email address

                    </label>


                    <div class="input-wrap">


                        <span class="input-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="14"
                                    rx="2"
                                />

                                <path
                                    d="m3 7 9 6 9-6"
                                />

                            </svg>

                        </span>


                        <input
                            class="form-input"
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                            autocomplete="email"
                            required
                        >


                    </div>


                </div>


                <!-- PASSWORD -->

                <div class="form-group">


                    <label
                        class="form-label"
                        for="password"
                    >

                        Password

                    </label>


                    <div class="input-wrap">


                        <span class="input-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="11"
                                    rx="2"
                                />

                                <path
                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                />

                            </svg>

                        </span>


                        <input
                            class="form-input"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            onclick="togglePassword()"
                            aria-label="Show password"
                        >

                            <svg
                                id="eyeIcon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path
                                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />

                            </svg>

                        </button>


                    </div>


                </div>


                <!-- OPTIONS -->

                <div class="form-options">


                    <label class="remember">

                        <input
                            class="remember-checkbox"
                            type="checkbox"
                            name="remember"
                        >

                        Remember me

                    </label>


                    <a
                        href="#"
                        class="forgot-password"
                        onclick="return false;"
                    >

                        Forgot password?

                    </a>


                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-button"
                >

                    <span class="button-content">

                        Sign in to dashboard


                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path d="M5 12h14"/>

                            <path
                                d="m13 6 6 6-6 6"
                            />

                        </svg>


                    </span>

                </button>


            </form>


            <!-- SECURITY -->

            <div class="security-note">


                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <rect
                        x="5"
                        y="10"
                        width="14"
                        height="11"
                        rx="2"
                    />

                    <path
                        d="M8 10V7a4 4 0 0 1 8 0v3"
                    />

                </svg>


                Your connection is protected and secure


            </div>


        </div>


    </section>


</div>


<script>

    function togglePassword() {

        const password =
            document.getElementById("password");

        const button =
            document.getElementById("passwordToggle");

        const icon =
            document.getElementById("eyeIcon");


        if (password.type === "password") {

            password.type = "text";

            button.setAttribute(
                "aria-label",
                "Hide password"
            );


            icon.innerHTML = `

                <path d="M3 3l18 18"/>

                <path
                    d="M10.6 10.6a2 2 0 0 0 2.8 2.8"
                />

                <path
                    d="M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17.8 17.8 0 0 1-3.1 4.4"
                />

                <path
                    d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 8 10 8c1.7 0 3.2-.5 4.5-1.2"
                />

            `;


        } else {

            password.type = "password";

            button.setAttribute(
                "aria-label",
                "Show password"
            );


            icon.innerHTML = `

                <path
                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                />

                <circle
                    cx="12"
                    cy="12"
                    r="3"
                />

            `;

        }

    }

</script>


</body>

</html>