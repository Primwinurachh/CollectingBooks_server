<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == false) {
    header("Location: LoginPage.php");
    exit;
}

session_unset();
session_destroy();

header("Location: LoginPage.php");
exit;
?>
