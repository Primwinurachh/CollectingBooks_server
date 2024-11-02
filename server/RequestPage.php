<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$searchPendingTerm = isset($_GET['search-pending']) ? $conn->real_escape_string($_GET['search-pending']) : '';
$searchCompletedTerm = isset($_GET['search-completed']) ? $conn->real_escape_string($_GET['search-completed']) : '';

$itemsPerPage = 10;
$pagePending = isset($_GET['pagePending']) ? (int)$_GET['pagePending'] : 1;
$pageCompleted = isset($_GET['pageCompleted']) ? (int)$_GET['pageCompleted'] : 1;
$offsetPending = ($pagePending - 1) * $itemsPerPage;
$offsetCompleted = ($pageCompleted - 1) * $itemsPerPage;
// รอดำเนินการ
$countPendingSql = "SELECT COUNT(DISTINCT Request_Book_Name) AS count FROM requests WHERE Request_Status = 2 AND (Request_Book_Name LIKE ?)";
$stmt = $conn->prepare($countPendingSql);
$searchTerm = "%$searchPendingTerm%";
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$countPendingResult = $stmt->get_result();
$totalPending = $countPendingResult->fetch_assoc()['count'];
$totalPagesPending = ceil($totalPending / $itemsPerPage);
$stmt->close();


// ดำเนินการการแล้ว
$countCompletedSql = "SELECT COUNT(DISTINCT Request_Book_Name, Action_Datetime) AS count FROM requests WHERE Request_Status = 1 AND (Request_Book_Name LIKE ? OR Action_Datetime LIKE ?)";
$stmt = $conn->prepare($countCompletedSql);
$searchTermCompleted = "%$searchCompletedTerm%";
$stmt->bind_param('ss', $searchTermCompleted, $searchTermCompleted);
$stmt->execute();
$countCompletedResult = $stmt->get_result();
$totalCompleted = $countCompletedResult->fetch_assoc()['count'];
$totalPagesCompleted = ceil($totalCompleted / $itemsPerPage);
$stmt->close();

// รอดำเนินการ
$pendingQuery = "
SELECT
    COALESCE(MAX(Request_ISBN), 'No ISBN') AS Request_ISBN,
    Request_Book_Name,
    COUNT(Request_ID) AS Number_of_Requests,
    MIN(Request_Datetime) AS Last_Request_Date,
    MAX(Request_ID) AS Request_ID
FROM requests
WHERE Request_Status = 2
AND Request_Book_Name LIKE ?
GROUP BY Request_Book_Name
ORDER BY Number_of_Requests DESC, Last_Request_Date ASC
LIMIT ? OFFSET ?;";


$stmt = $conn->prepare($pendingQuery);

if ($stmt) {
    $searchTerm = "%$searchPendingTerm%";
    $stmt->bind_param('sii', $searchTerm, $itemsPerPage, $offsetPending);
    $stmt->execute();
    $pendingResult = $stmt->get_result();
} else {
    die("Error preparing statement: " . $conn->error);
}

// ดำเนินการแล้ว
$completedQuery = "
    SELECT Request_ISBN, Request_Book_Name, COUNT(Request_ID) AS Number_of_Requests, MAX(Action_Datetime) AS Last_Action_Date, Request_ID
    FROM requests
    WHERE Request_Status = 1
    AND Request_Book_Name LIKE ?
    GROUP BY Action_Datetime, Request_Book_Name
    ORDER BY Last_Action_Date DESC
    LIMIT ? OFFSET ?;";
$stmt = $conn->prepare($completedQuery);
if ($stmt) {
    $searchTermCompleted = "%$searchCompletedTerm%";
    $stmt->bind_param('sii', $searchTermCompleted, $itemsPerPage, $offsetCompleted);
    $stmt->execute();
    $completedResult = $stmt->get_result();
} else {
    die("Error preparing statement: " . $conn->error);
}
$stmt->close();


function convertToThaiDate($date)
{
    $months = [
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

    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = date('n', $timestamp);
    $year = date('Y', $timestamp) + 543;
    $time = date('H:i น.', $timestamp);

    return "$day {$months[$month]} $year $time";
}

$activeTab = isset($_GET['status']) && $_GET['status'] == 'completed' ? 'completed' : 'pending';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/styleRequestPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>คำร้องขอเพิ่มข้อมูลหนังสือ</p>
    </header>
    
    <div class="tab">
        <button class="tablink" id="pendingTab" onclick="openPage('pending', this)">คำร้องขอเพิ่มข้อมูลหนังสือที่รอดำเนินการ</button>
        <button class="tablink" id="completedTab" onclick="openPage('completed', this)">คำร้องขอเพิ่มข้อมูลหนังสือที่ดำเนินการแล้ว</button>
    </div>

    <div id="pending" class="tabcontent">
        <div class="search">
            <form id="searchFormPending" method="GET">
                <input type="hidden" name="status" value="pending">
                <label for="search-input-pending" class="search-icon">
                    <img src="asset/search.png" alt="Search" class="search-image">
                </label>
                <input type="text" id="search-pending" placeholder="ค้นหาชื่อหนังสือ..." name="search-pending" value="<?php echo htmlspecialchars($searchPendingTerm); ?>">
                <button class="search-button" type="submit">ค้นหา</button>
            </form>
        </div>
        <div class="search-results">
            <?php
            $start = ($pagePending - 1) * $itemsPerPage + 1;
            $end = min($pagePending * $itemsPerPage, $totalPending);
            echo "แสดง $start ถึง $end จาก $totalPending รายการ";
            ?>
        </div>
        <div class="table">
            <?php if ($pendingResult->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อหนังสือ</th>
                            <th>จำนวนคำร้อง</th>
                            <th>วันที่ส่งคำร้องครั้งแรก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $pendingResult->fetch_assoc()): ?>
                            <?php $formattedDate = convertToThaiDate($row['Last_Request_Date']); ?>
                            <tr onclick="window.location.href='Request_Details_2.php?request_id=<?= htmlspecialchars($row['Request_ID']) ?>'">
                                <td><?= htmlspecialchars($row['Request_Book_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Number_of_Requests']) ?></td>
                                <td><?= htmlspecialchars($formattedDate) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>ไม่คำร้องที่รอดำเนินการ</p>
            <?php endif; ?>
        </div>

        <?php if ($totalPagesPending > 1): ?>
            <div class="pagination">
                <?php if ($pagePending > 1): ?>
                    <a href="?pagePending=<?= $pagePending - 1 ?>">&laquo;</a>
                <?php endif; ?>

                <?php if ($pagePending > 3): ?>
                    <a href="?pagePending=1">1</a>
                    <span>...</span>
                <?php endif; ?>

                <?php for ($i = max(1, $pagePending - 2); $i <= min($pagePending + 2, $totalPagesPending); $i++): ?>
                    <?php if ($i == $pagePending): ?>
                        <a href="#" class="active"><?= $i ?></a>
                    <?php else: ?>
                        <a href="?pagePending=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagePending < $totalPagesPending - 2): ?>
                    <span>...</span>
                    <a href="?pagePending=<?= $totalPagesPending ?>"><?= $totalPagesPending ?></a>
                <?php endif; ?>

                <?php if ($pagePending < $totalPagesPending): ?>
                    <a href="?pagePending=<?= $pagePending + 1 ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="completed" class="tabcontent">
        <div class="search">
            <form id="searchFormCompleted" method="GET">
                <input type="hidden" name="status" value="completed">
                <label for="search-input-completed" class="search-icon">
                    <img src="asset/search.png" alt="Search" class="search-image">
                </label>
                <input type="text" id="search-completed" placeholder="ค้นหาชื่อหนังสือ..." name="search-completed" value="<?php echo htmlspecialchars($searchCompletedTerm); ?>">
                <button class="search-button" type="submit">ค้นหา</button>
            </form>
        </div>
        <div class="search-results">
            <?php
            $start = ($pageCompleted - 1) * $itemsPerPage + 1;
            $end = min($pageCompleted * $itemsPerPage, $totalCompleted);
            echo "แสดง $start ถึง $end จาก $totalCompleted รายการ";
            ?>
        </div>
        <div class="table">
            <?php if ($completedResult->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อหนังสือ</th>
                            <th>จำนวนคำร้อง</th>
                            <th>วันที่ดำเนินการล่าสุด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $completedResult->fetch_assoc()): ?>
                            <?php $formattedDate = convertToThaiDate($row['Last_Action_Date']); ?>
                            <tr onclick="window.location.href='Request_Details_1.php?request_id=<?= htmlspecialchars($row['Request_ID']) ?>'">
                                <td><?= htmlspecialchars($row['Request_Book_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Number_of_Requests']) ?></td>
                                <td><?= htmlspecialchars($formattedDate) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>ไม่พบรายงานที่ดำเนินการแล้ว</p>
            <?php endif; ?>
        </div>

        <?php if ($totalPagesCompleted > 1): ?>
            <div class="pagination">
                <?php if ($pageCompleted > 1): ?>
                    <a href="?pageCompleted=<?= $pageCompleted - 1 ?>&status=completed">&laquo;</a>
                <?php endif; ?>

                <?php if ($pageCompleted > 3): ?>
                    <a href="?pageCompleted=1&status=completed">1</a>
                    <span>...</span>
                <?php endif; ?>

                <?php for ($i = max(1, $pageCompleted - 2); $i <= min($pageCompleted + 2, $totalPagesCompleted); $i++): ?>
                    <?php if ($i == $pageCompleted): ?>
                        <a href="#" class="active"><?= $i ?></a>
                    <?php else: ?>
                        <a href="?pageCompleted=<?= $i ?>&status=completed"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pageCompleted < $totalPagesCompleted - 2): ?>
                    <span>...</span>
                    <a href="?pageCompleted=<?= $totalPagesCompleted ?>&status=completed"><?= $totalPagesCompleted ?></a>
                <?php endif; ?>

                <?php if ($pageCompleted < $totalPagesCompleted): ?>
                    <a href="?pageCompleted=<?= $pageCompleted + 1 ?>&status=completed">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function openPage(pageName, elmnt) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablink");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(pageName).style.display = "block";
            elmnt.classList.add("active");
        }

        document.getElementById("<?php echo $activeTab; ?>Tab").click();
    </script>
</body>

</html>