<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}
$updateSuccess = false;
$errorMessage = '';

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// เช็คว่า user_id ถูกส่งมามั้ย
if ($userId === 0) {
    echo "<script>alert('ไม่มีการระบุรหัสสมาชิก');
    window.location.href = 'AllUserPage.php';
    </script>";
    exit;
}

if (isset($_GET['success'])) {
    $updateSuccess = true;
} elseif (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
}

$query = "SELECT users.User_Name, users.User_Status, users.Datetime_of_last_use, profiles.Profile_Picture
        FROM users
        LEFT JOIN profiles ON users.Profile_ID = profiles.Profile_ID
        WHERE users.User_ID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

function formatDateThai($dateStr)
{
    $date = new DateTime($dateStr);
    $thaiMonths = [
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
    ];
    $day = $date->format('j');
    $month = $thaiMonths[(int)$date->format('n')];
    $year = (int)$date->format('Y') + 543;
    $time = $date->format('H:i น.');
    return "$day $month $year $time";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['status'])) {
        $newStatus = intval($_POST['status']);

        $updateQuery = "UPDATE users SET User_Status = ? WHERE User_ID = ?";
        $updateStmt = $conn->prepare($updateQuery);

        if ($updateStmt) {
            $updateStmt->bind_param('ii', $newStatus, $userId);
            if ($updateStmt->execute()) {
                header("Location: User_Details.php?user_id=$userId&success=1");
                exit;
            } else {
                $errorMessage = 'ไม่สามารถอัปเดตสถานะได้';
            }
        } else {
            $errorMessage = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล';
        }
    }
}

$formattedLastUseDate = !empty($user['Datetime_of_last_use']) ? formatDateThai($user['Datetime_of_last_use']) : 'ไม่พบวันที่';
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
    <link rel="stylesheet" href="style//styleUser_Details.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายละเอียดข้อมูลผู้ใช้งาน</p>
    </header>
    <?php if ($user): ?>
        <div class="form_container">
            <?php if (!empty($user['Profile_Picture'])): ?>
                <div class="profile_group">
                    <div class="profile-picture-container">
                        <img src="<?php echo '/../profiles/' . htmlspecialchars($user['Profile_Picture']); ?>" alt="Profile Picture" class="profile-picture">
                    </div>
                </div>
            <?php endif; ?>

            <div class="form_group">
                <div class="input-group">
                    <label for="user_name" class="form-label">ชื่อบัญชีผู้ใช้งานระบบ :</label>
                    <input type="text" name="user_name" class="input-box" id="user_name" value="<?php echo htmlspecialchars($user['User_Name']); ?>" readonly>
                </div>
            </div>

            <div class="form_group">
                <div class="input-group">
                    <label for="datetime_of_last_use" class="form-label">วันที่ใช้งานล่าสุด :</label>
                    <input type="text" name="datetime_of_last_use" class="input-box" id="datetime_of_last_use" value="<?php echo htmlspecialchars($formattedLastUseDate); ?>" readonly>
                </div>
            </div>

            <form id="statusForm" name="statusForm" method="post">
                <div class="radio-group">
                    <label class="radio-label">สถานะการใช้งาน :</label>
                    <div class="radio-options">

                        <input type="radio" id="status2" name="status" value="2" <?php echo ($user['User_Status'] == 2) ? 'checked' : ''; ?> onclick="submitForm()">
                        <label for="status2">เปิดการใช้งาน</label>

                        <input type="radio" id="status3" name="status" value="3" <?php echo ($user['User_Status'] == 3) ? 'checked' : ''; ?> onclick="submitForm()">
                        <label for="status3">ปิดการใช้งาน</label>
                    </div>
                </div>
            </form>

            <button onclick="window.location.href='AllUserPage.php'" class="back-button">กลับไปยังหน้าหลัก</button>

            <div class="popup" id="popup" style="display:none;">
                <div class="popup-message">ต้องการเปลี่ยนสถานะผู้ใช้งานระบบใช่หรือไม่?</div>
                <div class="popup-buttons">
                    <button class="cancel-button" onclick="closePopup()">ยกเลิก</button>
                    <button class="add-button" onclick="confirmAdd()">ยืนยัน</button>
                </div>
            </div>

            <div class="popup" id="successPopup" style="display:none;">
                <div class="popup-success" id="successPopupMessage">อัปเดตสถานะสำเร็จ</div>
            </div>

            <div class="popup" id="errorPopup" style="display:none;">
                <div class="popup-message" id="errorPopupMessage"></div>
                <div class="popup-buttons">
                    <button class="cancel-button" onclick="closeErrorPopup()">ตกลง</button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php
        echo "<script>
            alert('ไม่พบข้อมูลผู้ใช้งาน');
            window.location.href = 'AllUserPage.php';
        </script>";
        exit;
        ?>
    <?php endif; ?>
    <script>
        function submitForm() {
            openPopup();
        }

        function openPopup() {
            document.getElementById('popup').style.display = 'block';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
            window.location.reload();
        }

        function confirmAdd() {
            document.forms['statusForm'].submit();
        }

        function showSuccessPopup() {
            var successPopup = document.getElementById('successPopup');
            successPopup.style.display = 'block';
            setTimeout(function() {
                successPopup.style.display = 'none';
                window.location.href = 'AllUserPage.php';
            }, 1000);
        }


        function showErrorPopup(message) {
            document.getElementById('errorPopupMessage').textContent = message;
            document.getElementById('errorPopup').style.display = 'block';
        }

        function closeErrorPopup() {
            document.getElementById('errorPopup').style.display = 'none';
            window.location.reload();
        }

        <?php if ($updateSuccess) : ?>
            window.onload = function() {
                showSuccessPopup();
            };
        <?php elseif (!empty($errorMessage)) : ?>
            window.onload = function() {
                showErrorPopup("<?php echo $errorMessage; ?>");
            };
        <?php endif; ?>
    </script>
</body>

</html>