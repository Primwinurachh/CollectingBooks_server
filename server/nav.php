<?php

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">


<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <style>
        * {
            font-family: "Anuphan", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .header-top {
            display: flex;
            background-color: #414141;
            color: #fff;
            padding: 8px 20px;
            justify-content: space-between;
            align-items: center;
        }

        .header {
            background-color: #D9D9D9;
            color: #000000;
            padding: 10px 20px;
            text-align: left;
            font-size: 30px;
            height: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            order: -1;
        }

        .icon_admin {
            width: 28px;
            height: 28px;
            vertical-align: middle;
            margin-right: 10px;
        }

        .admin_name {
            font-weight: bold;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .menu-button {
            font-size: 24px;
            cursor: pointer;
            margin-right: 10px;
        }

        .logout-button {
            background-color: #D9D9D9;
            color: #000000;
            border: none;
            padding: 8px 14px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            cursor: pointer;
            border-radius: 20px;
        }

        .logout-button:hover {
            background-color: #fff;
        }

        /* Slide bar styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #414141;
            position: fixed;
            left: -250px;
            transition: left 0.3s;
            z-index: 2;
            padding-top:5px;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
        }

        .sidebar a:hover {
            background-color: #D9D9D9;
            color: black;
        }

        .sub-dropdown-content {
            display: none;
            background-color: #414141;
            padding: 0;
        }

        .sub-dropdown-content a {
            padding: 10px 16px;
            display: block;
            font-size: 14px;
            margin-left: 5px;
        }
    </style>
</head>

<body>
    <header class="header-top">
        <div class="left">
            <nav>
                <div class="admin_name">
                    <div class="menu-button" onclick="toggleSidebar()">&#9776;</div>
                    <img src="asset/icon_admin.png" class="icon_admin">
                    <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                </div>
            </nav>
        </div>

        <a href="logout.php" class="logout-button">ออกจากระบบ</a>
    </header>

    <div class="sidebar" id="sidebar">
        <a href="RequestPage.php">คำร้องขอเพิ่มข้อมูลหนังสือ</a>
        <a href="ReportPoblemsPage.php">ปัญหาการใช้งานระบบ</a>
        <a href="AllBookPage.php">หนังสือทั้งหมด</a>
        <a href="AddBookPage.php">เพิ่มหนังสือ</a>
        <a href="AllUserPage.php">ผู้ใช้งานทั้งหมด</a>
        <a href="AddAdminPage.php">เพิ่มบัญชีผู้ดูแลระบบ</a>
        <a href="QuotesPage.php">Quotes</a>
        <a href="ManageProfilePage.php">จัดการรูปโปรไฟล์</a>

        <div class="dropdown">
        <a href="javascript:void(0);" onclick="toggleStatisticsDropdown()">สถิติ <i class="fas fa-chevron-down"></i></a>
            <div class="sub-dropdown-content" id="statisticsDropdown">
                <a href="StatisticUserPage.php">จำนวนการสมัครสมาชิก</a>
                <a href="StatisticQuotesPage.php">จำนวนโควตของสมาชิก</a>
                <a href="StatisticAddBookByAdminPage.php">จำนวนหนังสือที่เพิ่มโดยผู้ดูแลระบบ</a>
                <a href="TopScoreBookPage.php">รายชื่อหนังสือที่มีคะแนนมากที่สุด 10 อันดับแรก</a>
                <a href="TopQuotesBookPage.php">รายชื่อหนังสือที่มีจำนวนโควตมากที่สุด 10 อันดับแรก</a>
                <a href="TopReadBookPage.php">รายชื่อหนังสือที่มีจำนวนผู้อ่านมากที่สุด 10 อันดับแรก</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function toggleStatisticsDropdown() {
            var dropdown = document.getElementById('statisticsDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        window.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                var dropdown = document.getElementById('statisticsDropdown');
                dropdown.style.display = 'none';
            }
        });
    </script>

</body>

</html>
