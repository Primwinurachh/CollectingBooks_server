<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styleLoginPage.css">
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="asset//logo.png" alt="Logo" width="270" height="270">
        </div>
        <form action="login.php" method="post">
            <!--แสดงerror-->
            <?php if (isset($_SESSION['error'])) : ?>
                <div class="error_message">
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif ?>

            <div class="input_group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_SESSION['input_username']) ? htmlspecialchars($_SESSION['input_username'], ENT_QUOTES) : ''; ?>">
            </div>
            <div class="input_group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required value="<?php echo isset($_SESSION['input_password']) ? htmlspecialchars($_SESSION['input_password'], ENT_QUOTES) : ''; ?>">
            </div>
            <button class="btn" name="login" type="submit">เข้าสู่ระบบ</button>
        </form>
    </div>

    <?php
    // ล้างข้อมูลที่เก็บใน session หลังแสดงผล
    unset($_SESSION['input_username']);
    unset($_SESSION['input_password']);
    ?>

</body>

</html>