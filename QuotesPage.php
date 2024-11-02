<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'Datetime_Add_Quote';
$sort_order = 'DESC';

if (in_array($sort, ['Book_Name', 'Page_of_Quote', 'Quote_Detail', 'User_Name', 'Quote_Status', 'Number_of_Like', 'Datetime_Add_Quote'])) {
    $order_by = "ORDER BY $sort $sort_order";
} else {
    $order_by = "ORDER BY Datetime_Add_Quote $sort_order";
}

$quotesPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure the page number is at least 1

$offset = ($page - 1) * $quotesPerPage;

$countSql = "SELECT COUNT(*) AS total
            FROM quotes q
            JOIN books b ON q.Book_ID = b.Book_ID
            JOIN users u ON q.User_ID = u.User_ID
            WHERE b.Book_Name LIKE ? OR q.Quote_Detail LIKE ? OR u.User_Name LIKE ?";
$searchTerm = "%$search%";
$stmt = $conn->prepare($countSql);
$stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$countResult = $stmt->get_result();
$row = $countResult->fetch_assoc();
$totalQuotes = $row['total'];
$totalPages = ceil($totalQuotes / $quotesPerPage);

$sql = "SELECT
            q.Quote_ID, q.Quote_Detail, q.Page_of_Quote, q.Datetime_Add_Quote, q.Quote_Status, COALESCE(q.Number_of_Like, 0) AS Number_of_Like,
            b.Book_Name,
            u.User_Name
        FROM quotes q
        JOIN books b ON q.Book_ID = b.Book_ID
        JOIN users u ON q.User_ID = u.User_ID
        WHERE b.Book_Name LIKE ? OR q.Quote_Detail LIKE ? OR u.User_Name LIKE ?
        $order_by
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssii', $searchTerm, $searchTerm, $searchTerm, $quotesPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

function truncateString($string, $length = 30) {
    if (mb_strlen($string) > $length) {
        return mb_substr($string, 0, $length) . '...';
    }
    return $string;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/styleQuotesPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>Quotes</p>
    </header>

    <div class="search">
        <form action="QuotesPage.php" method="GET">
            <label for="search-input-admin" class="search-icon">
                <img src="asset/search.png" alt="Search" class="search-image">
            </label>
            <input type="text" id="search-input" placeholder="ค้นหาชื่อหนังสือ/โควต/ชื่อบัญชีผู้ใช้..." name="search" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-button">ค้นหา</button>
        </form>
    </div>

    <div class="search-results">
        <?php
        $start = ($page - 1) * $quotesPerPage + 1;
        $end = min($page * $quotesPerPage, $totalQuotes);
        echo "<div class='search-results'>แสดง $start ถึง $end จาก $totalQuotes รายการ</div>";
        ?>
    </div>

    <div class="table">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<table id='quotesTable'>";
            echo "<thead>";
            echo "<tr><th>ชื่อหนังสือ</th><th>หน้า</th><th>โควต</th><th>ชื่อบัญชี</th><th>สถานะโควต</th><th>ไลก์</th><th>วันที่โควต</th></tr>";
            echo "</thead>";
            echo "<tbody>";

            while ($row = mysqli_fetch_assoc($result)) {
                $date = new DateTime($row['Datetime_Add_Quote']);
                $thaiMonths = array(1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.');
                $formattedDate = $date->format('j') . ' ' . $thaiMonths[(int)$date->format('n')] . ' ' . ((int)$date->format('Y') + 543) . ' ' . $date->format('H:i น.');

                $statusQuote = ($row['Quote_Status'] == 1) ? "ส่วนตัว" : "สาธารณะ";

                echo "<tr onclick=\"window.location.href='Quote_Details.php?quote_id=" . htmlspecialchars($row['Quote_ID']) . "'\">";
                echo "<td>" . htmlspecialchars(truncateString($row["Book_Name"],20)) . "</td>";
                echo "<td>" . htmlspecialchars($row["Page_of_Quote"]) . "</td>";
                echo "<td>" . htmlspecialchars(truncateString($row["Quote_Detail"], 30)) . "</td>"; // ใช้ฟังก์ชันตัดข้อความ
                echo "<td>" . htmlspecialchars($row["User_Name"]) . "</td>";
                echo "<td>" . htmlspecialchars($statusQuote) . "</td>";
                echo "<td>" . htmlspecialchars($row["Number_of_Like"]) . "</td>";
                echo "<td>" . htmlspecialchars($formattedDate) . "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>ไม่มีข้อมูล</p>";
        }

        mysqli_close($conn);
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">&laquo;</a>
            <?php endif; ?>

            <?php if ($page > 3): ?>
                <a href="?page=1&search=<?= urlencode($search) ?>">1</a>
                <span>...</span>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                <?php if ($i == $page): ?>
                    <a href="#" class="active"><?= $i ?></a>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages - 2): ?>
                <span>...</span>
                <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>

</html>
