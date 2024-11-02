<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Admin_Name = $_POST['admin_name'];
    $Password = $_POST['password'];
    $Admin_Status = $_POST['status'];


    if (strlen($Admin_Name) > 100) {
        $error_message = "ชื่อบัญชีผู้ใช้ต้องไม่เกิน 100 ตัวอักษร";
    } elseif (strlen($Password) > 60) {
        $error_message = "รหัสผ่านต้องไม่เกิน 60 ตัวอักษร";
    } else {

        $check_query = "SELECT * FROM admins WHERE Admin_Name='$Admin_Name'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "ชื่อบัญชีผู้ใช้งานซ้ำ กรุณาใช้ชื่ออื่น";
        } else {

            $query = "INSERT INTO admins (Admin_Name, Admin_Status) VALUES ('$Admin_Name', '$Admin_Status')";
            $result = mysqli_query($conn, $query);

            if ($result) {
                $Admin_ID = mysqli_insert_id($conn);

                $password_with_id = $Admin_ID . $Password;

                $hashed_password = password_hash($password_with_id, PASSWORD_DEFAULT);


                $update_query = "UPDATE admins SET Password='$hashed_password' WHERE Admin_ID='$Admin_ID'";
                $update_result = mysqli_query($conn, $update_query);

                if ($update_result) {
                    $success_message = "เพิ่มผู้ดูแลระบบสำเร็จ";
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน";
                    echo "<script>
                    console.log('เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน: " . addslashes(mysqli_error($conn)) . "');
                    </script>";
                }
            } else {
                $error_message = "เกิดข้อผิดพลาดในการเพิ่มผู้ดูแลระบบ";
                echo "<script>
                console.log('เกิดข้อผิดพลาดในการเพิ่มผู้ดูแลระบบ: " . addslashes(mysqli_error($conn)) . "');
                </script>";
            }
        }
    }

    mysqli_close($conn);
}
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
    <link rel="stylesheet" href="style/styleAddAdminPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>เพิ่มบัญชีผู้ดูแลระบบ</p>
    </header>

    <div class="form_container">
        <form method="post" onsubmit="return validateForm()">
            <div class="form_group">
                <div class="input-group">
                    <label for="admin_name" class="form-label">ชื่อบัญชีผู้ดูแลระบบ :</label>
                    <input type="text" name="admin_name" class="input-box" id="admin_name" placeholder="กรอกชื่อบัญชีผู้ดูแลระบบ..." required>
                    <small class="helper-text">ชื่อบัญชีต้องเป็นภาษาอังกฤษเท่านั้น มีตัวเลข และ _ ได้</small>
                </div>
            </div>
            <div class="form_group">
                <div class="input-group">
                    <label for="password" class="form-label">รหัสผ่าน :</label>
                    <input type="password" name="password" class="input-box" id="password" placeholder="กรอกรหัสผ่าน..." required>
                    <small class="helper-text">รหัสผ่านต้องยาวเกิน 8 ตัว มีอักษรพิมพ์เล็ก-ใหญ่ ตัวเลข และอักขระพิเศษ</small>
                </div>
            </div>
            <div class="form_group">
                <div class="input-group">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน :</label>
                    <input type="password" name="confirm_password" class="input-box" id="confirm_password" placeholder="ยืนยันรหัสผ่าน..." required>
                </div>
            </div>
            <div class="radio-group">
                <label class="radio-label">สถานะการใช้งาน :</label>
                <div class="radio-options">
                    <input type="radio" id="enable" name="status" value="1" checked>
                    <label for="enable">เปิดการใช้งาน</label>

                    <input type="radio" id="disable" name="status" value="2">
                    <label for="disable">ปิดการใช้งาน</label>
                </div>
            </div>

            <button type="submit" class="submit-button">เพิ่มผู้ดูแลระบบ</button>
        </form>

        <div class="popup" id="popup" style="display:none;">
            <div class="popup-message">ต้องการเพิ่มผู้ดูแลระบบใช่หรือไม่?</div>
            <div class="popup-buttons">
                <button class="cancel-button" onclick="closePopup()">ยกเลิก</button>
                <button class="add-button" onclick="confirmAdd()">ยืนยัน</button>
            </div>
        </div>

        <div class="popup" id="errorPopup" style="display:none;">
            <div class="popup-message" id="errorPopupMessage"></div>
            <div class="popup-buttons">
                <button class="cancel-button" onclick="closeErrorPopup()">ปิด</button>
            </div>
        </div>

        <div class="popup" id="successPopup" style="display:none;">
            <div class="popup-success" id="successPopupMessage"></div>
        </div>
    </div>

    <script>
        function validateForm() {
            var adminName = document.getElementById('admin_name').value.trim();
            var password = document.getElementById('password').value.trim();
            var confirmPassword = document.getElementById('confirm_password').value.trim();
            var errorPopupMessage = document.getElementById('errorPopupMessage');

            if (adminName === '' || password === '' || confirmPassword === '') {
                errorPopupMessage.textContent = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
                openErrorPopup();
                return false;
            }

            if (adminName.length > 100) {
                errorPopupMessage.textContent = 'ชื่อบัญชีผู้ใช้ต้องไม่เกิน 100 ตัวอักษร';
                openErrorPopup();
                return false;
            }

            var regex = /^[a-zA-Z0-9_]+$/;
            if (!regex.test(adminName)) {
                errorPopupMessage.textContent = 'ชื่อบัญชีต้องไม่มีอักขระพิเศษ';
                openErrorPopup();
                return false;
            }

            if (password.length < 8 || password.length > 60) {
                errorPopupMessage.textContent = 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัว และไม่เกิน 60 ตัวอักษร';
                openErrorPopup();
                return false;
            }

            if (/^\s+$/.test(password)) {
                errorPopupMessage.textContent = 'รหัสผ่านไม่สามารถมีแต่ช่องว่างได้';
                openErrorPopup();
                return false;
            }

            var hasUpperCase = /[A-Z]/.test(password);
            var hasLowerCase = /[a-z]/.test(password);
            var hasNumber = /[0-9]/.test(password);
            var hasSpecialChar = /[!@#$%^&*(),.?":{}|<>_-]/.test(password);

            if (!(hasUpperCase && hasLowerCase && hasNumber && hasSpecialChar)) {
                errorPopupMessage.textContent = 'รหัสผ่านต้องมีอักษรพิมพ์เล็ก-ใหญ่ ตัวเลข และอักขระพิเศษ';
                openErrorPopup();
                return false;
            }

            if (password !== confirmPassword) {
                errorPopupMessage.textContent = 'รหัสผ่านไม่ตรงกัน กรุณาลองใหม่อีกครั้ง';
                openErrorPopup();
                return false;
            }

            openPopup();
            return false;
        }


        function openPopup() {
            document.getElementById('popup').style.display = 'block';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }

        function openErrorPopup() {
            document.getElementById('errorPopup').style.display = 'block';
        }

        function closeErrorPopup() {
            document.getElementById('errorPopup').style.display = 'none';
        }

        function confirmAdd() {
            document.forms[0].submit();
        }

        function showSuccessPopup() {
            var successPopup = document.getElementById('successPopup');
            successPopup.style.display = 'block';
            setTimeout(function() {
                successPopup.style.display = 'none';
            }, 1000);
        }

        function closeSuccessPopup() {
            document.getElementById('successPopup').style.display = 'none';
        }

        <?php if (!empty($error_message)) : ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorPopupMessage').textContent = "<?php echo $error_message; ?>";
                openErrorPopup();
            });
        <?php elseif (!empty($success_message)) : ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('successPopupMessage').textContent = "<?php echo $success_message; ?>";
                showSuccessPopup();
            });
        <?php endif; ?>

        const inputs = document.querySelectorAll('.input-group input[type="text"], .input-group input[type="password"]');
        const helperTexts = document.querySelectorAll('.helper-text');

        inputs.forEach((input, index) => {
            input.addEventListener('focus', () => {
                helperTexts[index].style.display = 'block';
            });
            input.addEventListener('blur', () => {
                helperTexts[index].style.display = 'none';
            });
        });
    </script>
</body>
</html>