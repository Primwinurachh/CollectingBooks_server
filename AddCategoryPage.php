<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}
ob_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $response = ['error' => true, 'message' => 'ไม่พบคำสั่งที่ระบุ'];

    if ($action == 'add') {
        $categoryName = $_POST['categoryName'];

        $categoryName = mysqli_real_escape_string($conn, $categoryName);

        $check_query = "SELECT Category_ID, Category_Name FROM categories WHERE Category_Name = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 's', $categoryName);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $response = ['error' => true, 'message' => "มีหมวดหมู่หนังสือ:" . $categoryName . " แล้วกรุณาใช้ชื่ออื่น"];
        } else {
            $sql = "INSERT INTO categories (Category_Name) VALUES (?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 's', $categoryName);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response = ['success' => true, 'message' => "เพิ่มหมวดหมู่หนังสือสำเร็จ"];
                } else {
                    $response = ['error' => true, 'message' => 'ไม่สามารถเพิ่มหมวดหมู่หนังสือได้'];
                }
            } else {
                $response = ['error' => true, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่หนังสือ'];
                echo "<script>
                console.log('เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่หนังสือ" . addslashes($conn->error) . "');
                </script>";
            }
        }

        echo json_encode($response);
    } elseif ($action == 'delete') {
        $categoryName = $_POST['categoryName'];

        $sql = "DELETE FROM categories WHERE Category_Name = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $categoryName);

        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $response = ['success' => true];
            } else {
                $response = ['error' => true, 'message' => 'ไม่สามารถลบหมวดหมู่หนังสือได้'];
            }
        } else {
            $response = ['error' => true, 'message' => 'เกิดข้อผิดพลาดในการลบหมวดหมู่หนังสือ'];
        }

        mysqli_stmt_close($stmt);
    } elseif ($action == 'edit') {
        $oldCategoryName = $_POST['oldCategoryName'];
        $newCategoryName = $_POST['newCategoryName'];


        $sql = "UPDATE categories SET Category_Name = ? WHERE Category_Name = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $newCategoryName, $oldCategoryName);

        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $response = ['success' => true];
            } else {
                $response = ['error' => true, 'message' => 'ไม่สามารถแก้ไขหมวดหมู่หนังสือได้'];
            }
        } else {
            $response = ['error' => true, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่หนังสือ'];
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);

    ob_end_clean();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$sql = "SELECT Category_Name FROM categories ORDER BY Category_Name ASC";
$result = mysqli_query($conn, $sql);
$categories = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['Category_Name'];
    }
    mysqli_free_result($result);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styleAddCategoryPage.css">
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>หมวดหมู่หนังสือ</p>
        <button class="image_button" name="addcategory" onclick="openAddCategoryPopup()">
            <img src="asset/plus.png" alt="Add Profile">
        </button>
    </header>
    <div class="container">
        <div class="toppic">
            <label>หมวดหมู่หนังสือ</label>
        </div>
        <div class="categories">
            <?php if (!empty($categories)) : ?>
                <?php
                $itemsPerPage = 8;
                $totalPages = ceil(count($categories) / $itemsPerPage);
                for ($page = 0; $page < $totalPages; $page++) :
                ?>
                    <div class="page" data-page="<?php echo $page; ?>" style="<?php echo $page > 0 ? 'display:none;' : ''; ?>">
                        <ul>
                            <?php
                            $start = $page * $itemsPerPage;
                            $end = min(($page + 1) * $itemsPerPage, count($categories));
                            for ($i = $start; $i < $end; $i++) :
                            ?>
                                <li>
                                    <?php echo htmlspecialchars($categories[$i]); ?>
                                    <div class="button_group">
                                        <button class="edit_button" onclick="editCategory('<?php echo htmlspecialchars($categories[$i]); ?>')">แก้ไข</button>
                                        <button class="delete_button" onclick="deleteCategory('<?php echo htmlspecialchars($categories[$i]); ?>')">ลบ</button>
                                    </div>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                <?php endfor; ?>
            <?php else : ?>
                <p>ไม่มีหมวดหมู่หนังสือ</p>
                <?php $totalPages = 1; ?>
            <?php endif; ?>
        </div>

        <div class="navigation_buttons">
            <a class="prev" id="prev_button" onclick="plusSlides(-1)">&#10094;</a>
            <span id="page_number" class="page_number">1/<?php echo $totalPages; ?></span>
            <a class="next" id="next_button" onclick="plusSlides(1)">&#10095;</a>
        </div>
    </div>

    <div id="addCategoryPopup" class="popup_container" style="display: none;">
        <div class="popup">
            <h2>เพิ่มหมวดหมู่หนังสือ</h2>
            <input type="text" id="categoryName" placeholder="กรอกชื่อหมวดหมู่หนังสือ..">
            <p class="input_info">* ชื่อหมวดหมู่ต้องเป็นภาษาไทยและยาวไม่เกิน 100 ตัวอักษร</p>
            <p id="resultMessage"></p>
            <button class="close_button" onclick="closeAddCategoryPopup()">ปิด</button>
            <button class="add_button" onclick="addCategory()">เพิ่ม</button>
        </div>
    </div>

    <div id="editCategoryPopup" class="popup_container" style="display: none;">
        <div class="popup">
            <h2>แก้ไขหมวดหมู่หนังสือ</h2>
            <input type="text" id="newCategoryName" placeholder="กรอกชื่อหมวดหมู่หนังสือใหม่..">
            <p id="editResultMessage"></p>
            <button class="close_button" onclick="closeEditCategoryPopup()">ปิด</button>
            <button class="add_button" onclick="updateCategory()">ยืนยัน</button>
        </div>
    </div>

    <div id="deleteCategoryPopup" class="popup_container" style="display: none;">
        <div class="popup">
            <h2>ลบหมวดหมู่หนังสือ</h2>
            <p>คุณแน่ใจว่าต้องการลบหมวดหมู่:</p>
            <p id="deleteCategoryName"></p>
            <p id="deleteResultMessage"></p>
            <button class="close_button" onclick="closeDeleteCategoryPopup()">ปิด</button>
            <button class="add_button" onclick="confirmDeleteCategory()">ยืนยัน</button>
        </div>
    </div>
    <script>
        function openAddCategoryPopup() {
            document.getElementById('addCategoryPopup').style.display = 'block';
        }

        function closeAddCategoryPopup() {
            document.getElementById('addCategoryPopup').style.display = 'none';
            var resultMessage = document.getElementById('resultMessage');
            resultMessage.style.display = 'none';
            resultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
        }

        async function addCategory() {
            var categoryName = document.getElementById('categoryName').value.trim();
            var thaiRegex = /^[ก-ฮะ-าิ-ูเ-แโ-ไำ็่-๋ั์ๅํ๎\s]+$/;
            var resultMessage = document.getElementById('resultMessage');
            resultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
            resultMessage.style.display = 'none';

            if (categoryName === "") {
                resultMessage.textContent = "กรุณากรอกชื่อหมวดหมู่หนังสือ";
                resultMessage.classList.add('resultMessage_error');
                resultMessage.style.display = 'block';
                return;
            }

            if (categoryName.length > 100) {
                resultMessage.textContent = "ความยาวชื่อหมวดหมู่หนังสือต้องไม่เกิน 100 ตัวอักษร";
                resultMessage.classList.add('resultMessage_error');
                resultMessage.style.display = 'block';
                return;
            }

            if (!thaiRegex.test(categoryName)) {
                resultMessage.textContent = "กรุณากรอกชื่อหมวดหมู่หนังสือเป็นภาษาไทยเท่านั้น";
                resultMessage.classList.add('resultMessage_error');
                resultMessage.style.display = 'block';
                return;
            }

            try {
                const response = await fetch('AddCategoryPage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=add&categoryName=' + encodeURIComponent(categoryName)
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.success) {

                    resultMessage.textContent = "เพิ่มหมวดหมู่: " + categoryName + " สำเร็จ";
                    resultMessage.classList.add('resultMessage_success');
                    resultMessage.style.display = 'block';

                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    resultMessage.textContent = result.message;
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                }
            } catch (error) {
                resultMessage.textContent = 'ไม่สามารถเพิ่มหมวดหมู่ได้';
                resultMessage.classList.add('resultMessage_error');
                resultMessage.style.display = 'block';
            }
        }

        function deleteCategory(categoryName) {
            document.getElementById('deleteCategoryPopup').style.display = 'block';
            document.getElementById('deleteCategoryName').textContent = categoryName;
            document.getElementById('deleteCategoryPopup').dataset.categoryName = categoryName;
        }

        function closeDeleteCategoryPopup() {
            document.getElementById('deleteCategoryPopup').style.display = 'none';
            var deleteResultMessage = document.getElementById('deleteResultMessage');
            deleteResultMessage.style.display = 'none';
            deleteResultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
        }

        async function confirmDeleteCategory() {
            var categoryName = document.getElementById('deleteCategoryPopup').dataset.categoryName;
            var deleteResultMessage = document.getElementById('deleteResultMessage');
            deleteResultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
            deleteResultMessage.style.display = 'none';

            try {
                const response = await fetch('AddCategoryPage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete&categoryName=' + encodeURIComponent(categoryName)
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.success) {
                    deleteResultMessage.textContent = "ลบหมวดหมู่: " + categoryName + " สำเร็จ";
                    deleteResultMessage.classList.add('resultMessage_success');
                    deleteResultMessage.style.display = 'block';

                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    deleteResultMessage.textContent = result.message;
                    deleteResultMessage.classList.add('resultMessage_error');
                    deleteResultMessage.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                deleteResultMessage.textContent = 'ไม่สามารถลบหมวดหมู่ได้เนื่องจากถูกใช้งาน';
                deleteResultMessage.classList.add('resultMessage_error');
                deleteResultMessage.style.display = 'block';
            }
        }


        function editCategory(categoryName) {
            document.getElementById('editCategoryPopup').style.display = 'block';
            document.getElementById('newCategoryName').value = categoryName;
            document.getElementById('editCategoryPopup').dataset.oldCategoryName = categoryName;
        }

        function closeEditCategoryPopup() {
            document.getElementById('editCategoryPopup').style.display = 'none';
            var editResultMessage = document.getElementById('editResultMessage');
            editResultMessage.style.display = 'none';
            editResultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
        }

        async function updateCategory() {
            var oldCategoryName = document.getElementById('editCategoryPopup').dataset.oldCategoryName;
            var newCategoryName = document.getElementById('newCategoryName').value.trim();
            var thaiRegex = /^[ก-ฮะ-าิ-ูเ-แโ-ไำ็่-๋ั์ๅํ๎\s]+$/;
            var specialCharRegex = /[!@#$%^&*(),.?":{}|<>฿+-]/;
            var editResultMessage = document.getElementById('editResultMessage');
            editResultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
            editResultMessage.style.display = 'none';

            if (newCategoryName === "") {
                editResultMessage.textContent = "กรุณากรอกชื่อหมวดหมู่หนังสือใหม่";
                editResultMessage.classList.add('resultMessage_error');
                editResultMessage.style.display = 'block';
                return;
            }
            if (newCategoryName === oldCategoryName) {
                editResultMessage.textContent = "ชื่อหมวดหมู่นี้ถูกใช้งานไปแล้ว";
                editResultMessage.classList.add('resultMessage_error');
                editResultMessage.style.display = 'block';
                return;
            }

            if (specialCharRegex.test(newCategoryName)) {
                editResultMessage.textContent = ("หมวดหมู่หนังสือต้องไม่มีอักขระพิเศษ");
                editResultMessage.classList.add('resultMessage_error');
                editResultMessage.style.display = 'block';
                return;
            }

            if (!thaiRegex.test(newCategoryName)) {
                editResultMessage.textContent = "กรุณากรอกชื่อหมวดหมู่หนังสือใหม่เป็นภาษาไทยเท่านั้น";
                editResultMessage.classList.add('resultMessage_error');
                editResultMessage.style.display = 'block';
                return;
            }

            try {
                const response = await fetch('AddCategoryPage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=edit&oldCategoryName=' + encodeURIComponent(oldCategoryName) + '&newCategoryName=' + encodeURIComponent(newCategoryName)
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.success) {
                    editResultMessage.textContent = "อัปเดตหมวดหมู่: " + oldCategoryName + " เป็น " + newCategoryName + " สำเร็จ";
                    editResultMessage.classList.add('resultMessage_success');
                    editResultMessage.style.display = 'block';

                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {

                    editResultMessage.textContent = result.message;
                    editResultMessage.classList.add('resultMessage_error');
                    editResultMessage.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                editResultMessage.textContent = 'ไม่สามารถแก้ไขหมวดหมู่ได้';
                editResultMessage.classList.add('resultMessage_error');
                editResultMessage.style.display = 'block';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const slides = document.querySelectorAll('.page');
            const pageNumber = document.getElementById('page_number');
            const prevButton = document.getElementById('prev_button');
            const nextButton = document.getElementById('next_button');
            let currentSlide = 0;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.style.display = i === index ? 'block' : 'none';
                });

                pageNumber.textContent = (index + 1) + '/' + slides.length;
                updateNavigationButtons(index);
            }

            function plusSlides(n) {
                currentSlide += n;

                if (currentSlide >= slides.length) {
                    currentSlide = slides.length - 1;
                } else if (currentSlide < 0) {
                    currentSlide = 0;
                }

                showSlide(currentSlide);
            }

            function updateNavigationButtons(index) {
                prevButton.style.visibility = (index > 0) ? 'visible' : 'hidden';
                nextButton.style.visibility = (index < slides.length - 1) ? 'visible' : 'hidden';
            }

            prevButton.addEventListener('click', () => plusSlides(-1));
            nextButton.addEventListener('click', () => plusSlides(1));

            if (slides.length > 0) {
                showSlide(currentSlide);
            } else {
                document.querySelector('.navigation_buttons').style.display = 'none';
            }
        });
    </script>
</body>

</html>