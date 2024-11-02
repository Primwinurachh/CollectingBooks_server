<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$countSql = "SELECT COUNT(*) AS count FROM books WHERE Recommend_Status = 2";
$countResult = $conn->query($countSql);
$countRow = $countResult->fetch_assoc();
$currentRecommendCount = $countRow['count'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $bookId = $_POST['id'];
    $status = $_POST['status'] == 1 ? 1 : 2;

    if ($status == 2 && $currentRecommendCount >= 5) {
        echo json_encode(array('success' => false, 'message' => 'ไม่สามารถแนะนำหนังสือได้มากกว่า 5 เล่ม'));
        $conn->close();
        exit;
    }

    $updateSql = "UPDATE books SET Recommend_Status = ? WHERE Book_ID = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param('ii', $status, $bookId);
    $stmt->execute();
    echo json_encode(array('success' => true));
    $stmt->close();
    $conn->close();
    exit;
}

$booksPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $booksPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

$countSql = "SELECT COUNT(*) as total
            FROM books b
            LEFT JOIN categories c ON b.Category_ID = c.Category_ID
            LEFT JOIN publishers p ON b.Publisher_ID = p.Publisher_ID
            WHERE b.Book_Name LIKE ? OR c.Category_Name LIKE ? OR p.Publisher_Name LIKE ? OR b.Author LIKE ? OR b.ISBN LIKE ?";

$stmt = $conn->prepare($countSql);
$searchTerm = "%$search%";
$stmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalBooks = $row['total'];
$totalPages = ceil($totalBooks / $booksPerPage);

$sql = "SELECT
            b.Book_ID,
            b.Book_Name,
            b.Author,
            p.Publisher_Name,
            b.ISBN,
            c.Category_Name,
            COALESCE(COUNT(DISTINCT s.User_ID), 0) AS Number_of_Readers,  -- นับจำนวนผู้ใช้จากตาราง statistics
            COALESCE(COUNT(DISTINCT q.Quote_ID), 0) AS Number_of_Quotes,
            COALESCE(b.Avg_Score, 0) AS Score,  -- ใช้คะแนนจากตาราง books
            b.Recommend_Status
        FROM
            books b
        LEFT JOIN quotes q ON b.Book_ID = q.Book_ID
        LEFT JOIN statistics s ON b.Book_ID = s.Book_ID
        LEFT JOIN categories c ON b.Category_ID = c.Category_ID
        LEFT JOIN publishers p ON b.Publisher_ID = p.Publisher_ID
        WHERE
            b.Book_Name LIKE ? OR
            c.Category_Name LIKE ? OR
            p.Publisher_Name LIKE ? OR
            b.Author LIKE ? OR
            b.ISBN LIKE ?
        GROUP BY
            b.Book_ID, b.Book_Name, b.Author, p.Publisher_Name, b.ISBN, c.Category_Name, b.Recommend_Status, b.Avg_Score
        ORDER BY
            b.Recommend_Status DESC,
            b.Book_Name ASC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $offset, $booksPerPage);


$stmt->execute();
$result = $stmt->get_result();
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
    <link rel="stylesheet" href="style/styleAllBookPage.css" />
</head>

<body>
    <?php include_once dirname(__FILE__) . "/nav.php"; ?>
    <header class="header">
        <p>หนังสือทั้งหมด</p>
    </header>
    <div class="search">
        <form  method="GET">
            <label for="search-input" class="search-icon">
                <img src="asset/search.png" alt="Search" class="search-image">
            </label>
            <input type="text" id="search-ndone" placeholder="ค้นหาหนังสือ/ หมวดหมู่หนังสือ/ สำนักพิมพ์/ ชื่อผู้แต่ง/ เลข ISBN..." name="search" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" name="searchbook" class="search-button">ค้นหา</button>
        </form>
        <button type="button" name="category" class="category-button" onclick="window.location.href='AddCategoryPage.php'">หมวดหมู่หนังสือ</button>
    </div>

    <div class="search-results">
        <?php
        $start = ($page - 1) * $booksPerPage + 1;
        $end = min($page * $booksPerPage, $totalBooks);

        echo "<div class='search-results'>";
        echo "แสดง $start ถึง $end จาก $totalBooks รายการ";
        echo "</div>";
        ?>
    </div>

    <div class="table" id="booksTable">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>แนะนำ</th>
                        <th>ชื่อหนังสือ</th>
                        <th>ผู้แต่ง</th>
                        <th>สำนักพิมพ์</th>
                        <th>เลข ISBN</th>
                        <th>หมวดหมู่</th>
                        <th>จำนวนโควต</th>
                        <th>จำนวนผู้อ่าน</th>
                        <th>คะแนน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php $averageScore = number_format($row["Score"], 2); ?>
                        <?php $isChecked = $row["Recommend_Status"] == 2 ? 'checked' : ''; ?>
                        <tr>
                            <td>
                                <label class='switch'>
                                    <input type='checkbox' name='recommendSwitch'
                                        <?= $isChecked ?>
                                        <?= $currentRecommendCount >= 5 && $row["Recommend_Status"] != 2
                                            ? 'onclick="alertDisabledSwitch(); return false;"'
                                            : 'onclick="confirmStatusChange(event, ' . htmlspecialchars($row["Book_ID"]) . ', this.checked)"'
                                        ?>>
                                    <span class='slider round'></span>
                                </label>
                            </td>

                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["Book_Name"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["Author"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["Publisher_Name"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["ISBN"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["Category_Name"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["Number_of_Quotes"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($row["Number_of_Readers"]) ?></td>
                            <td onclick="window.location.href='Book_Details.php?book_id=<?= htmlspecialchars($row['Book_ID']) ?>'"><?= htmlspecialchars($averageScore) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>ไม่มีข้อมูลหนังสือที่ตรงตามคำค้นหา</p>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo;</a>
            <?php endif; ?>

            <?php if ($page > 3): ?>
                <a href="?page=1">1</a>
                <span>...</span>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                <?php if ($i == $page): ?>
                    <a href="#" class="active"><?= $i ?></a>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages - 2): ?>
                <span>...</span>
                <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
        function confirmStatusChange(event, bookId, isChecked) {
            event.stopPropagation();
            var status = isChecked ? 2 : 1;
            var message = isChecked ? "คุณต้องการเปิดการแนะนำหนังสือหรือไม่?" : "คุณต้องการปิดการแนะนำหนังสือหรือไม่?";

            if (confirm(message)) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    var response = JSON.parse(xhr.responseText);
                    if (!response.success) {
                        alert(response.message);
                        document.querySelector('input[type="checkbox"][onclick*="' + bookId + '"]').checked = !isChecked;
                    } else {
                        alert("สถานะการแนะนำอัปเดตแล้ว");
                        location.reload();
                    }
                };
                xhr.send("id=" + bookId + "&status=" + status);
            } else {
                document.querySelector('input[type="checkbox"][onclick*="' + bookId + '"]').checked = !isChecked;
            }
        }

        function alertDisabledSwitch() {
            alert('ไม่สามารถแนะนำหนังสือได้มากกว่า 5 เล่ม');
        }
    </script>
</body>

</html>