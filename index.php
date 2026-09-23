<?php

session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard/index.php");
    exit;
}

header("Location: auth/login.php");
exit;
