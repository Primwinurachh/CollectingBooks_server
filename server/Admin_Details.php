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

$adminId = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;

// เช็คว่า admin_id ถูกส่งมามั้ย
if ($adminId === 0) {
    echo "<script>alert('ไม่มีการระบุรหัสผู้ดูแลระบบ');
    window.location.href = 'AllUserPage.php';
    </script>";
    exit;
}

if (isset($_GET['success'])) {
    $updateSuccess = true;
} elseif (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
}

$query = "SELECT * FROM admins WHERE Admin_ID = ? AND Admin_Name != 'Admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

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
        $updateQuery = "UPDATE admins SET Admin_Status = ? WHERE Admin_ID = ?";
        $updateStmt = $conn->prepare($updateQuery);

        if ($updateStmt) {
            $updateStmt->bind_param('ii', $newStatus, $adminId);
            if ($updateStmt->execute()) {
                header("Location: admin_details.php?admin_id=$adminId&success=1");
                exit;
            } else {
                $errorMessage = 'ไม่สามารถอัปเดตสถานะได้';
            }
        } else {
            $errorMessage = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล';
        }
    }
}

$formattedLastUseDate = !empty($admin['Date_of_last_use']) ? formatDateThai($admin['Date_of_last_use']) : 'ไม่พบวันที่';
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
    <link rel="stylesheet" href="style/styleAdmin_Details.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายละเอียดข้อมูลผู้ดูแลระบบ</p>
    </header>
    <?php if ($admin): ?>
        <div class="form_container">
            <div class="form_group">
                <div class="input-group">
                    <label for="admin_name" class="form-label">ชื่อบัญชีผู้ดูแลระบบ :</label>
                    <input type="text" name="admin_name" class="input-box" id="admin_name" value="<?php echo htmlspecialchars($admin['Admin_Name']); ?>" readonly>
                </div>
            </div>
            <div class="form_group">
                <div class="input-group">
                    <label for="date_of_last_use" class="form-label">วันที่ใช้งานล่าสุด :</label>
                    <input type="text" name="date_of_last_use" class="input-box" id="date_of_last_use" value="<?php echo htmlspecialchars($formattedLastUseDate); ?>" readonly>
                </div>
            </div>

            <form id="statusForm" name="statusForm" method="post">
                <div class="radio-group">
                    <label class="radio-label">สถานะการใช้งาน :</label>
                    <div class="radio-options">
                        <input type="radio" id="enable" name="status" value="1" <?php echo ($admin['Admin_Status'] == 1) ? 'checked' : ''; ?> onclick="submitForm()">
                        <label for="enable">เปิดการใช้งาน</label>

                        <input type="radio" id="disable" name="status" value="2" <?php echo ($admin['Admin_Status'] == 2) ? 'checked' : ''; ?> onclick="submitForm()">
                        <label for="disable">ปิดการใช้งาน</label>
                    </div>
                </div>
            </form>
            <button onclick="window.location.href='AllUserPage.php'" class="back-button">กลับไปยังหน้าหลัก</button>

            <div class="popup" id="popup" style="display:none;">
                <div class="popup-message">ต้องการเปลี่ยนสถานะผู้ดูแลระบบใช่หรือไม่?</div>
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
            alert('ไม่พบข้อมูลผู้ดูแลระบบ');
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