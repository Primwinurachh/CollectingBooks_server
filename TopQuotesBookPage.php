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

$query = "SELECT b.Book_Name, COUNT(q.Quote_ID) AS total_quotes
            FROM books b
            LEFT JOIN quotes q ON b.Book_ID = q.Book_ID
            WHERE q.Datetime_Add_Quote BETWEEN '$startMonth-01' AND LAST_DAY('$endMonth-01')
            GROUP BY b.Book_ID, b.Book_Name
            ORDER BY total_quotes DESC, b.Book_Name ASC
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
    <title>รายชื่อหนังสือที่มีจำนวนโควตมากที่สุด 10 อันดับแรก</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/styleTopQuotesBookPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายชื่อหนังสือที่มีจำนวนโควตมากที่สุด 10 อันดับแรก</p>
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
            <label>จำนวนโควต</label>
        </div>
        <div class="top10-list">
            <?php if (count($topBooks) > 0): ?>
                <?php foreach ($topBooks as $index => $book): ?>
                    <div class="top10-item">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <span><?php echo htmlspecialchars($book['Book_Name']); ?></span>
                        <span><?php echo htmlspecialchars($book['total_quotes']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="top10-item">
                    <span colspan="2">ไม่พบข้อมูล</span>
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