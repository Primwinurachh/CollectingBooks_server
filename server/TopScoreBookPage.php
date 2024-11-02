<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$startMonth = isset($_POST['month-start']) && !empty($_POST['month-start']) ? $_POST['month-start'] : date('Y-m', strtotime("-11 months"));
$endMonth = isset($_POST['month-end']) && !empty($_POST['month-end']) ? $_POST['month-end'] : date('Y-m');

$query = "SELECT b.Book_Name, FORMAT(COALESCE(b.Avg_Score, 0), 2) AS Average_Score
        FROM books b
        WHERE b.Datetime_Added BETWEEN '$startMonth-01' AND LAST_DAY('$endMonth-01')
        ORDER BY Average_Score DESC, b.Book_Name ASC
        LIMIT 10";

$result = $conn->query($query);

$topBooks = array();
while ($row = $result->fetch_assoc()) {
    $topBooks[] = $row;
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
    <link rel="stylesheet" href="style/styleTopScoreBookPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายชื่อหนังสือที่มีคะแนนมากที่สุด 10 อันดับแรก</p>
    </header>

    <form method="POST">
        <div class="month-years">
            <input type="month" id="month-start" name="month-start" class="month-input"
                max="<?php echo date('Y-m'); ?>"
                value="<?php echo $startMonth; ?>">
            <div class="label">ถึง</div>
            <input type="month" id="month-end" name="month-end" class="month-input" readonly
                max="<?php echo date('Y-m'); ?>"
                value="<?php echo $endMonth; ?>">
            <button type="submit">แสดงผล</button>
        </div>
    </form>

    <div class="top10-container">
        <div class="top10-toppic">
            <label>ลำดับ</label>
            <label>ชื่อหนังสือ</label>
            <label>คะแนน</label>
        </div>
        <div class="top10-list">
            <?php if (count($topBooks) > 0): ?>
                <?php foreach ($topBooks as $index => $book): ?>
                    <div class="top10-item">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <span><?php echo htmlspecialchars($book['Book_Name']); ?></span>
                        <span><?php echo htmlspecialchars($book['Average_Score']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="top10-item">
                    <span colspan="3">ไม่พบข้อมูล</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.getElementById('month-start').addEventListener('change', function() {
            var startMonth = this.value;
            if (startMonth) {
                var startDate = new Date(startMonth + '-01');
                var endDate = new Date(startDate.setMonth(startDate.getMonth() + 11));

                var currentDate = new Date();
                if (endDate > currentDate) {
                    endDate = currentDate;
                }

                var year = endDate.getFullYear();
                var month = (endDate.getMonth() + 1).toString().padStart(2, '0');
                var endMonth = year + '-' + month;

                document.getElementById('month-end').value = endMonth;
            } else {
                document.getElementById('month-end').value = '';
            }
        });
    </script>

</body>

</html>
