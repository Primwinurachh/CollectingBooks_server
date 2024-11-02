<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$query = "SELECT * FROM profiles ORDER BY Points ASC, Profile_Name ASC";
$result = $conn->query($query);

$profiles = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profiles[] = [
            'name' => $row['Profile_Name'],
            'image' => '/../profiles/' . $row['Profile_Picture'],
            'score' => number_format($row['Points'])
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/styleManageProfilePage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>จัดการรูปโปรไฟล์</p>
        <button class="image_button" onclick="window.location.href='AddProfilePage.php'">
            <img src="asset/plus.png" alt="Add Profile" />
        </button>
    </header>

    <div class="container" id="profile-list">
        <?php foreach ($profiles as $profile): ?>
            <div class="profile">
                <img src="<?php echo htmlspecialchars($profile['image']); ?>" alt="รูปภาพโปรไฟล์">
                <p>ชื่อรูป: <?php echo htmlspecialchars($profile['name']); ?></p>
                <div class="score-container">
                    <img src="asset/point.png" alt="คะแนน">
                    <p><?php echo htmlspecialchars($profile['score']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="AddProfile"></div>
    </div>
</body>

</html>
