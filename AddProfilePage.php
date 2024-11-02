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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profileName = $_POST['image-name'];
    $points = $_POST['image-score'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $query = "INSERT INTO profiles (Profile_Name, Points) VALUES (?, ?)";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("si", $profileName, $points);

            if ($stmt->execute()) {
                $profile_id = $stmt->insert_id;
                $stmt->close();

                $newFileName = "profile_" . $profile_id . '.' . $fileExtension;
                $dest_path = __DIR__ . '/../profiles/' . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $query = "UPDATE profiles SET Profile_Picture = ? WHERE Profile_ID = ?";
                    if ($stmt = $conn->prepare($query)) {
                        $stmt->bind_param("si", $newFileName, $profile_id);

                        if ($stmt->execute()) {
                            $success_message = "เพิ่มรูปโปรไฟล์สำเร็จ";
                        } else {
                            $error_message = "การเพิ่มข้อมูลโปรไฟล์ไม่สำเร็จ";
                            echo "<script>
                            console.log('เกิดข้อผิดพลาดในการเพิ่มข้อมูลโปรไฟล์: " . addslashes($stmt->error) . "');
                            </script>";
                        }
                        $stmt->close();
                    } else {
                        $error_message = "  เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
                        echo "<script>
                        console.log('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . addslashes($conn->error) . "');
                            </script>";
                    }
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
                }
            } else {
                $error_message = "เกิดข้อผิดพลาดในการเพิ่มโปรไฟล์";
                echo "<script>
                        console.log('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . addslashes($stmt->error) . "');
                            </script>";
            }
        } else {
            $error_message = "  เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
            echo "<script>
            console.log('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . addslashes($conn->error) . "');
                </script>";
        }
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
    <link rel="stylesheet" href="style/styleAddProfilePage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>จัดการรูปโปรไฟล์</p>
    </header>
    <div class="container">
        <form id="uploadForm" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="add-profile-container" id="ProfileInputForm">
                <div class="image-upload-group">
                    <input type="file" id="image" name="image" accept=".png, .jpg, .jpeg" onchange="previewImage(event)" hidden />
                    <label for="image" class="image-upload-button">
                        <img src="asset/image_button.png" alt="Upload Icon" class="image-upload-button">
                    </label>
                </div>

                <div class="image-preview" id="image-preview">
                    <img id="preview" src="#" alt="รูปภาพโปรไฟล์" />
                </div>
                <div class="note">*รองรับไฟล์ JPG PNG ที่มีขนาดไม่เกิน 10 MB</div>
                <div class="input-container">
                    <div class="input-group">
                        <label for="image-name">ชื่อรูป :</label>
                        <input type="text" name="image-name" id="image-name" placeholder="กรอกชื่อรูป..." />
                    </div>
                    <p class="input_info">* ชื่อรูปต้องเป็นภาษาไทยเท่านั้น</p>
                    <div class="input-group">
                        <label for="image-score">
                            <img src="asset/point.png" alt="คะแนน" />
                        </label>
                        <input type="text" name="image-score" id="image-score" placeholder="กรอกคะแนน..." />
                    </div>
                    <p class="input_info">* คะเเนนต้องเป็นตัวเลขเท่านั้น</p>
                </div>
                <button type="button" class="submit-button" onclick="validateForm()">เพิ่ม</button>
            </div>
        </form>

        <div class="popup" id="popup" style="display:none;">
            <div class="popup-message">ยืนยันการเพิ่มรูปโปรไฟล์</div>
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
    </div>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('preview');
                var previewContainer = document.getElementById('image-preview');
                var imageUploadGroup = document.querySelector('.image-upload-group');

                output.src = reader.result;
                previewContainer.style.display = 'flex';
                imageUploadGroup.style.display = 'none';
                output.onclick = function() {
                    output.src = '';
                    previewContainer.style.display = 'none';
                    imageUploadGroup.style.display = 'block';
                    document.getElementById('image').value = '';
                };
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function validateForm() {
            var imageName = document.getElementById('image-name').value.trim();
            var imageScore = document.getElementById('image-score').value.trim();
            var imageFile = document.getElementById('image').files[0];
            var errorPopupMessage = document.getElementById('errorPopupMessage');

            if (imageName === '' || imageScore === '' || !imageFile) {
                errorPopupMessage.textContent = 'กรุณากรอกข้อมูลให้ครบถ้วน';
                openErrorPopup();
                return false;
            }

            if (!/^[ก-ฮะ-์\s]+$/.test(imageName)) {
                errorPopupMessage.textContent = 'ชื่อรูปโปรไฟล์ต้องเป็นภาษาไทยเท่านั้น';
                openErrorPopup();
                return false;
            }

            if (!/^\d+$/.test(imageScore)) {
                errorPopupMessage.textContent = 'คะแนนต้องเป็นตัวเลขเท่านั้น';
                openErrorPopup();
                return false;
            }

            if (!['image/jpeg', 'image/png'].includes(imageFile.type)) {
                errorPopupMessage.textContent = 'รูปโปรไฟล์ต้องต้องเป็นไฟล์ภาพ .jpg หรือ .png เท่านั้น';
                openErrorPopup();
                return false;
            }

            if (imageFile.size > 10485760) {
                errorPopupMessage.textContent = 'ขนาดไฟล์ไม่สามารถเกิน 10 MB';
                openErrorPopup();
                return false;
            }

            if (imageName.length > 100) {
                errorPopupMessage.textContent = 'ชื่อรูปโปรไฟล์ต้องไม่ยาวเกิน 100 ตัวอักษร';
                openErrorPopup();
                return false;
            }

            if (imageScore.length > 11) {
                errorPopupMessage.textContent = 'คะแนนต้องไม่ยาวเกิน 11 หลัก';
                openErrorPopup();
                return false;
            }

            openPopup();
            return true;
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
                window.location.href = 'ManageProfilePage.php';
            }, 1000);
        }

        function closeSuccessPopup() {
            document.getElementById('successPopup').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (sessionStorage.getItem('popupShown')) {
                sessionStorage.removeItem('popupShown');
            } else {
                <?php if (!empty($error_message)) : ?>
                    document.getElementById('errorPopupMessage').textContent = "<?php echo $error_message; ?>";
                    openErrorPopup();
                    sessionStorage.setItem('popupShown', true);
                    <?php $error_message = '';
                    ?>
                <?php elseif (!empty($success_message)) : ?>
                    document.getElementById('successPopupMessage').textContent = "<?php echo $success_message; ?>";
                    showSuccessPopup();
                    sessionStorage.setItem('popupShown', true);
                    <?php $success_message = '';
                    ?>
                <?php endif; ?>
            }
        });

        const inputs = document.querySelectorAll('.input-container input[type="text"]');
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