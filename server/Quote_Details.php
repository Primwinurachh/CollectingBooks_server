<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$error_message = '';
$success_message = '';

$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 0;


if ($quote_id <= 0) {
    echo "<script>
    alert('ไม่มีการระบุรหัสโควต');
    window.location.href = 'QuotesPage.php';
</script>";
exit;

} else {

    $sql = "SELECT
                q.Quote_ID, q.Quote_Detail, q.Page_of_Quote, q.Datetime_Add_Quote, q.Quote_Status, q.Quote_Status, COALESCE(q.Number_of_Like, 0) AS Number_of_Like,
                b.Book_Name, b.Book_Picture, b.Score,
                u.User_Name
            FROM quotes q
            JOIN books b ON q.Book_ID = b.Book_ID
            JOIN users u ON q.User_ID = u.User_ID
            WHERE q.Quote_ID = $quote_id";

    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) === 0) {
        // หากไม่พบหรือการดึงข้อมูลล้มเหลว
        echo "<script>
            alert('ไม่พบข้อมูลโควต');
            window.location.href = 'QuotesPage.php';
        </script>";
        exit;
    } else {
        $quote = mysqli_fetch_assoc($result);

        $statusQuote = '';
        switch ($quote['Quote_Status']) {
            case 1:
                $statusQuote = "ส่วนตัว";
                break;
            case 2:
                $statusQuote = "สาธารณะ";
                break;
            default:
                $statusQuote = "ไม่ทราบสถานะ";
        }

        $thaiMonths = array(
            1 => 'ม.ค.',
            2 => 'ก.พ.',
            3 => 'มี.ค.',
            4 => 'เม.ย.',
            5 => 'พ.ค.',
            6 => 'มิ.ย.',
            7 => 'ก.ค.',
            8 => 'ส.ค.',
            9 => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.'
        );
        $datetime = new DateTime($quote['Datetime_Add_Quote']);
        $day = $datetime->format('j');
        $month = $thaiMonths[(int)$datetime->format('n')];
        $year = (int)$datetime->format('Y') + 543;
        $time = $datetime->format('H:i น.');
        $formattedDate = "$day $month $year $time";
    }
}

if (isset($_POST['delete'])) {
    $delete_sql = "DELETE FROM Quotes WHERE Quote_ID = $quote_id";

    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "ลบโควตสำเร็จ";
    } else {
        $error_message = "การลบข้อมูลล้มเหลว";
        echo "<script>
        console.log('การลบข้อมูลล้มเหลว: " . addslashes(mysqli_error($conn)) . "');
        </script>";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>รายละเอียดโควตา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/styleQuote_Details.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายละเอียดโควต</p>
    </header>

    <?php if (!$error_message): ?>
        <div class="quote-details-container">
            <div class="quote-details">
                <img src="../books/<?php echo htmlspecialchars($quote['Book_Picture']); ?>" alt="รูปภาพหนังสือ">
                <div class="details-content">
                    <h1><?php echo htmlspecialchars($quote["Book_Name"]); ?></h1>
                    <p class="quote">“<?php echo htmlspecialchars($quote["Quote_Detail"]); ?>”</p>
                    <p class="meta">หน้า <?php echo htmlspecialchars($quote["Page_of_Quote"]); ?> - โดย <?php echo htmlspecialchars($quote["User_Name"]); ?> - วันที่เพิ่ม <?php echo htmlspecialchars($formattedDate); ?></p>
                    <p class="status"><?php echo htmlspecialchars($statusQuote); ?></p>
                    <div class="button-container">
                        <button class="delete-btn" onclick="showConfirmation()">ลบ</button>
                        <button class="back-btn" onclick="window.location.href='QuotesPage.php'">กลับ</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <div id="confirmationPopup" class="confirmation-popup">
        <p>คุณแน่ใจหรือไม่ว่าต้องการลบโควตนี้?</p>
        <form method="post">
            <div class="popup-buttons">
                <button type="button" class="cancel-button" onclick="hideConfirmation()">ยกเลิก</button>
                <button type="submit" name="delete" class="confirm-button">ตกลง</button>
            </div>
        </form>
    </div>

    <div id="messagePopup" class="message-popup">
        <?php if ($error_message): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <script>
                setTimeout(function() {
                    hideMessagePopup();
                    window.location.href = 'QuotesPage.php';
                }, 2000);
            </script>
        <?php endif; ?>
    </div>

    <script>
        function showConfirmation() {
            var popup = document.getElementById('confirmationPopup');
            popup.style.display = 'block';
        }

        function hideConfirmation() {
            var popup = document.getElementById('confirmationPopup');
            popup.style.display = 'none';
        }

        function showMessagePopup() {
            var popup = document.getElementById('messagePopup');
            popup.style.display = 'block';
        }

        function hideMessagePopup() {
            var popup = document.getElementById('messagePopup');
            popup.style.display = 'none';
        }

        <?php if ($error_message || $success_message): ?>
            showMessagePopup();
        <?php endif; ?>
    </script>
</body>

</html>