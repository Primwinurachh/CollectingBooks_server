<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

function showAlertAndRedirect($message)
{
    echo "<script>alert('$message');</script>";
}

$bookId = isset($_GET['book_id']) ? $_GET['book_id'] : '';

if ($bookId) {
    //ว่ามีการใช้หนังสือมั้ย
    $checkSql = "
        SELECT COUNT(*) as count FROM (
            SELECT Book_ID FROM books_on_bookshelf WHERE Book_ID = ?
            UNION ALL
            SELECT Book_ID FROM quotes WHERE Book_ID = ?
            UNION ALL
            SELECT Book_ID FROM statistics WHERE Book_ID = ?
        ) as combined
    ";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param('iii', $bookId, $bookId, $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // เช็คว่าหนังสือถูกใช้งานในตารางใดๆ
    $isUsed = $row['count'] > 0;

    // ดึงข้อมูลหนังสือ
    $sql = "
    SELECT b.Book_Name, b.Author, b.Publisher_ID, b.Printed, b.Category_ID, b.Number_of_Page, b.ISBN, b.Book_Picture, p.Publisher_Name 
    FROM books b
    JOIN publishers p ON b.Publisher_ID = p.Publisher_ID
    WHERE b.Book_ID = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();

        $categorySql = "SELECT * FROM categories ORDER BY Category_Name ASC";
        $categoryResult = $conn->query($categorySql);
        $categories = $categoryResult->fetch_all(MYSQLI_ASSOC);

        //เก็บข้อมูลในตัวแปรไปแสดงในinput
        $book_name = $book['Book_Name'];
        $author = $book['Author'];
        $publisher = $book['Publisher_Name'];
        $printed = $book['Printed'];
        $category_id = $book['Category_ID'];
        $number_of_page = $book['Number_of_Page'];
        $isbn = $book['ISBN'];
        $book_picture = $book['Book_Picture'];
    } else {
        echo "<script>
            alert('ไม่พบข้อมูลหนังสือ');
            window.location.href= 'AllBookPage.php';
            </script>";
        exit;
    }
} else {
    echo "<script>
    alert('ไม่มีการระบุรหัสหนังสือ');
    window.location.href= 'AllBookPage.php';
    </script>";
    exit;
}

// แก้ไขข้อมูลหนังสือ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['submitted'])) {
    $_SESSION['submitted'] = true;

    $book_name = trim($_POST['book_name']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $printed = trim($_POST['printed']);
    $number_of_page = trim($_POST['number_of_page']);
    $isbn = trim($_POST['isbn']);
    $bError = false;

    // ตรวจสอบข้อผิดพลาด
    $empty_fields = 0;
    if (empty($book_name)) $empty_fields++;
    if (empty($author)) $empty_fields++;
    if (empty($isbn)) $empty_fields++;
    if (empty($printed)) $empty_fields++;
    if (empty($number_of_page)) $empty_fields++;
    if (empty($publisher)) $empty_fields++;
    //ตรวจสอบข้อมูล
    if ($empty_fields > 0) {
        showAlertAndRedirect("กรุณากรอกข้อมูลให้ครบทุกช่อง");
        $bError = true;
    } elseif (mb_strlen(trim($book_name)) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),:]+$/u', trim($book_name))) {
        showAlertAndRedirect("ชื่อหนังสือสามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - : () , และต้องไม่เกิน 100 ตัวอักษร");
        $bError = true;
    } elseif (mb_strlen($author) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),]+$/u', $author)) {
        showAlertAndRedirect("ชื่อผู้แต่งสามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - () , และต้องไม่เกิน 100 ตัวอักษร");
        $bError = true;
    } elseif (!ctype_digit($printed) || strlen($printed) > 11 || intval($printed) == 0) {
        showAlertAndRedirect("พิมพ์ครั้งที่ต้องเป็นตัวเลขเท่านั้น ไม่เป็นศูนย์ และไม่เกิน 11 หลัก");
        $bError = true;
    } elseif (!ctype_digit($number_of_page) || strlen($number_of_page) > 11 || intval($number_of_page) == 0) {
        showAlertAndRedirect("จำนวนหน้าต้องเป็นตัวเลขเท่านั้น ไม่เป็นศูนย์ และไม่เกิน 11 หลัก");
        $bError = true;
    } elseif (!ctype_digit($isbn) || (strlen($isbn) !== 13 && strlen($isbn) !== 10) || intval($isbn) == 0) {
        showAlertAndRedirect("ISBN ต้องเป็นตัวเลข 10 หรือ 13 หลักเท่านั้น และไม่เป็นศูนย์");
        $bError = true;
    } else {
        $isbnCheckSql = "SELECT COUNT(*) as count FROM books WHERE ISBN = ? AND Book_ID != ?";
        $stmt = $conn->prepare($isbnCheckSql);
        $stmt->bind_param('si', $isbn, $bookId);
        $stmt->execute();
        $isbnCheckResult = $stmt->get_result();
        $isbnCheckRow = $isbnCheckResult->fetch_assoc();

        if ($isbnCheckRow['count'] > 0) {
            showAlertAndRedirect("รหัส ISBN นี้มีอยู่ในระบบแล้ว");
            $bError = true;
        }
    }

    // หากไม่มีข้อผิดพลาดในการกรอกข้อมูล
    if ($bError == false) {

        // ลบหนังสือ
        if (isset($_POST['delete'])) {
            if (!$isUsed) {
                $selectSql = "SELECT Book_Picture FROM books WHERE Book_ID = ?";
                $stmt = $conn->prepare($selectSql);
                $stmt->bind_param('i', $bookId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row && !empty($row['Book_Picture']) && file_exists("../books/" . $row['Book_Picture'])) {
                    unlink("../books/" . $row['Book_Picture']);
                }

                $deleteSql = "DELETE FROM books WHERE Book_ID = ?";
                $stmt = $conn->prepare($deleteSql);
                $stmt->bind_param('i', $bookId);
                if ($stmt->execute()) {
                    echo "<script>alert('ลบหนังสือเรียบร้อยแล้ว');
                    window.location.href='AllBookPage.php';
                    </script>";
                } else {
                    showAlertAndRedirect("การลบหนังสือไม่สำเร็จ");
                }
            } else {
                showAlertAndRedirect("ไม่สามารถลบหนังสือได้ เนื่องจากถูกใช้งานในระบบ");
            }

            //ลบปกหนังสือ
        } elseif (isset($_POST['delete_image'])) {
            if ($book_picture && file_exists("../books/$book_picture")) {
                unlink("../books/$book_picture");
            }
            $updateSql = "UPDATE books SET Book_Picture = NULL WHERE Book_ID = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            echo "<script>
                alert('ลบรูปภาพปกเรียบร้อยแล้ว');
                window.location.href='Book_Details.php?book_id=" . $bookId . "';
            </script>";

            // ตรวจสอบและเพิ่มสำนักพิมพ์
        } else {
            $stmt = $conn->prepare("SELECT Publisher_ID FROM publishers WHERE Publisher_Name = ?");
            $stmt->bind_param("s", $publisher);
            $stmt->execute();
            $result = $stmt->get_result();
        
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $publisher_id = $row['Publisher_ID']; // มีสำนักพิมพ์ในฐานข้อมูลแล้ว
            } else {
                // ถ้าไม่มีสำนักพิมพ์ให้เพิ่ม
                $stmt = $conn->prepare("INSERT INTO publishers (Publisher_Name) VALUES (?)");
                $stmt->bind_param("s", $publisher);
                if ($stmt->execute()) {
                    $publisher_id = $stmt->insert_id; // ดึง ID ของสำนักพิมพ์ที่เพิ่งเพิ่ม
                } else {
                    showAlertAndRedirect("เกิดข้อผิดพลาดในการบันทึกสำนักพิมพ์");
                }
            }
            
            // ตรวจสอบการอัปโหลดหนังสือ
            if (isset($_FILES['book_picture']) && $_FILES['book_picture']['error'] == UPLOAD_ERR_OK) {
                $originalFilename = $_FILES['book_picture']['name'];
                $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                $tempname = $_FILES['book_picture']['tmp_name'];
                $fileSize = $_FILES['book_picture']['size'];
        
                if ($fileSize == 0) {
                    showAlertAndRedirect("กรุณาอัปโหลดไฟล์รูปภาพ");
                } elseif (!in_array($fileExtension, ['jpg', 'jpeg'])) {
                    showAlertAndRedirect("ไฟล์รูปภาพต้องเป็นไฟล์ JPG หรือ JPEG เท่านั้น");
                } elseif ($fileSize > 10 * 1024 * 1024) {
                    showAlertAndRedirect("ขนาดไฟล์รูปภาพต้องไม่เกิน 10 MB");
                } elseif ($book_picture && file_exists("../books/$book_picture")) {
                    unlink("../books/$book_picture");
                }
        
                $newFilename = "book_". $book_id . "." . $fileExtension;
                $folder = "../books/" . $newFilename;
        
                if (move_uploaded_file($tempname, $folder)) {
                    $updateSql = "UPDATE books SET
                                Book_Name = ?, Author = ?, Publisher_ID = ?, Printed = ?, Category_ID = ?, Number_of_Page = ?, ISBN = ?, Book_Picture = ?
                                WHERE Book_ID = ?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param('ssiiisisi', $book_name, $author, $publisher_id, $printed, $_POST['category'], $number_of_page, $isbn, $newFilename, $bookId);
                    $stmt->execute();
                    echo "<script>
                    alert('ข้อมูลหนังสืออัปเดตเรียบร้อย');
                    window.location.href = 'Book_Details.php?book_id=" . $bookId . "';
                    </script>";
                } else {
                    showAlertAndRedirect("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ");
                }
            } else {
                // อัปเดตข้อมูลโดยไม่ต้องอัปโหลดรูปภาพ
                $updateSql = "UPDATE books SET
                    Book_Name = ?, Author = ?, Publisher_ID = ?, Printed = ?, Category_ID = ?, Number_of_Page = ?, ISBN = ?
                    WHERE Book_ID = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('ssiiisii', $book_name, $author, $publisher_id, $printed, $_POST['category'], $number_of_page, $isbn, $bookId);
                $stmt->execute();
                echo "<script>
                alert('ข้อมูลหนังสืออัปเดตเรียบร้อย');
                window.location.href = 'Book_Details.php?book_id=" . $bookId . "';
                </script>";
            }
        }
        
    }
}

$_SESSION['submitted'] = null;
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
    <link rel="stylesheet" href="style/styleBook_Details.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายละเอียดหนังสือ</p>
    </header>

    <div class="form_col">
        <form method="POST" enctype="multipart/form-data" onsubmit="return confirmSubmit();">

            <div class="book_info">
                <div class="book_info">
                    <?php if (!empty($book_picture)): ?>
                        <img src="../books/<?php echo htmlspecialchars($book_picture); ?>" alt="รูปภาพหนังสือ" style="max-width: 300px;">
                        <button type="submit" name="delete_image">ลบรูปภาพ</button>
                    <?php else: ?>
                        <p>ไม่มีรูปภาพสำหรับหนังสือเล่มนี้</p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="input_group">
                <label for="book_picture">เลือกรูปภาพปกใหม่:</label>
                <input type="file" id="book_picture" name="book_picture" accept=".jpg, .jpeg" onchange="previewImage();">
                <div class="container">
                    <img id="image_preview" src="" alt="ตัวอย่างรูปภาพ" style="max-width: 300px; display: none;">
                </div>
            </div>
            <div class="input_group">
                <label for="book_name">ชื่อหนังสือ:</label>
                <input type="text" id="book_name" name="book_name" value="<?php echo htmlspecialchars($book_name); ?>" placeholder="กรอกชื่อหนังสือ...">
            </div>
            <div class="input_group">
                <label for="author">ผู้เขียน:</label>
                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>" placeholder="กรอกชื่อผู้แต่ง...">
            </div>

            <div class="input_group">
                <label for="author">สำนักพิมพ์:</label>
                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($publisher); ?>" placeholder="กรอกสำนักพิมพ์...">
            </div>

            <div class="input_group">
                <label for="printed">พิมพ์ครั้งที่:</label>
                <input type="text" id="printed" name="printed" value="<?php echo htmlspecialchars($printed); ?>" placeholder="กรอกครั้งที่พิมพ์">
            </div>
            <div class="input_group">
                <label for="category">หมวดหมู่:</label>
                <select id="category" name="category">
                    <?php if (empty($categories)): ?>
                        <option value="" disabled>ไม่พบหมวดหมู่</option>
                    <?php else: ?>
                        <option value="" disabled selected hidden>กรุณาเลือกหมวดหมู่...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['Category_id']; ?>" <?php echo ($cat['Category_id'] == $category_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['Category_Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="input_group">
                <label for="number_of_page">จำนวนหน้า:</label>
                <input type="text" id="number_of_page" name="number_of_page" value="<?php echo htmlspecialchars($number_of_page); ?>" placeholder="กรอกจำนวนหน้า...">
            </div>
            <div class="input_group">
                <label for="isbn">ISBN:</label>
                <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>" placeholder="กรอกรหัส ISBN...">
            </div>

            <div class="button_group">
                <button type="button" onclick="if(checkBookPicture()) { window.location.href='AllBookPage.php'; }" class="back_button">กลับไปยังหน้าหลัก</button>
                <button type="submit" class="submit_button">บันทึกการเปลี่ยนแปลง</button>

                <?php if (!$isUsed): ?>
                    <button type="submit" name="delete" class="delete_button" onclick="return confirmDeleteBook();">ลบหนังสือ</button>
                <?php endif; ?>

            </div>
        </form>
    </div>
    <script>
        function previewImage() {
            const fileInput = document.getElementById('book_picture');
            const file = fileInput.files[0];
            const imagePreview = document.getElementById('image_preview');

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }

                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '';
                imagePreview.style.display = 'none';
            }
        }

        function confirmDeleteImage() {
            return confirm('คุณต้องการลบรูปภาพนี้หรือไม่?');
        }

        function confirmDeleteBook() {
            return confirm('คุณต้องการลบหนังสือเล่มนี้หรือไม่?');
        }

        function confirmSubmit() {
            return confirm('คุณต้องการบันทึกการเปลี่ยนแปลงหรือไม่?');
        }

        function checkBookPicture() {
        var bookPicture = "<?php echo $book_picture; ?>"; // ดึงข้อมูลจาก PHP

        if (!bookPicture) {
            alert('กรุณาเพิ่มรูปภาพหน้าปกหนังสือ');
            return false;
        }
        return true;
    }
    </script>
</body>

</html>