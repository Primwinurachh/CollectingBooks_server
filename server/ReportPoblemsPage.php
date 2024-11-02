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

$countPendingSql = "SELECT COUNT(*) AS count FROM reports WHERE Report_Status = 2 AND (Report_ID LIKE '%$searchPendingTerm%' OR Report_Topic LIKE '%$searchPendingTerm%' OR Report_Datetime LIKE '%$searchPendingTerm%')";
$countPendingResult = $conn->query($countPendingSql);
$totalPending = $countPendingResult->fetch_assoc()['count'];
$totalPagesPending = ceil($totalPending / $itemsPerPage);

$countCompletedSql = "SELECT COUNT(*) AS count FROM reports WHERE (Report_Status = 1 OR Report_Status = 3) AND (Report_ID LIKE '%$searchCompletedTerm%' OR Report_Topic LIKE '%$searchCompletedTerm%' OR Report_Datetime LIKE '%$searchCompletedTerm%')";
$countCompletedResult = $conn->query($countCompletedSql);
$totalCompleted = $countCompletedResult->fetch_assoc()['count'];
$totalPagesCompleted = ceil($totalCompleted / $itemsPerPage);

$pendingQuery = "
    SELECT reports.*, users.User_Name
    FROM reports
    JOIN users ON reports.User_ID = users.User_ID
    WHERE Report_Status = 2 AND (
        users.User_Name LIKE '%$searchPendingTerm%' OR
        reports.Report_Topic LIKE '%$searchPendingTerm%'
    )
    ORDER BY reports.Report_Datetime ASC
    LIMIT $itemsPerPage OFFSET $offsetPending";
$pendingResult = $conn->query($pendingQuery);

$completedQuery = "
    SELECT reports.*, users.User_Name
    FROM reports
    JOIN users ON reports.User_ID = users.User_ID
    WHERE (Report_Status = 1 OR Report_Status = 3) AND (
        users.User_Name LIKE '%$searchCompletedTerm%' OR
        reports.Report_Topic LIKE '%$searchCompletedTerm%'
    )
    ORDER BY reports.Action_Datetime DESC
    LIMIT $itemsPerPage OFFSET $offsetCompleted";
$completedResult = $conn->query($completedQuery);

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
    <link rel="stylesheet" href="style/styleReportPoblemsPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>ปัญหาการใช้งานระบบ</p>
    </header>

    <div class="tab">
        <button class="tablink" onclick="openPage('pending', this)" id="pendingTab">รายงานปัญหาการใช้งานที่รอดำเนินการ</button>
        <button class="tablink" onclick="openPage('completed', this)" id="completedTab">รายงานปัญหาการใช้งานที่ดำเนินการแล้ว</button>
    </div>

    <div id="pending" class="tabcontent">
        <div class="search">
            <form id="searchFormPending" method="GET">
                <input type="hidden" name="status" value="pending">
                <label for="search-input-pending" class="search-icon">
                    <img src="asset/search.png" alt="Search" class="search-image">
                </label>
                <input type="text" id="search-pending" placeholder="ค้นหาผู้ใช้/หัวข้อรายงาน..." name="search-pending" value="<?php echo htmlspecialchars($searchPendingTerm); ?>">
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
                            <th>ชื่อบัญชีผู้ใช้</th>
                            <th>หัวข้อรายงาน</th>
                            <th>สถานะรายงาน</th>
                            <th>วันที่และเวลารายงาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $pendingResult->fetch_assoc()): ?>
                            <?php $formattedDate = convertToThaiDate($row['Report_Datetime']); ?>
                            <tr onclick="window.location.href='Report_Details.php?report_id=<?= htmlspecialchars($row['Report_ID']) ?>'">
                                <td><?= htmlspecialchars($row['User_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Report_Topic']) ?></td>
                                <td>รอดำเนินการ</td>
                                <td><?= htmlspecialchars($formattedDate) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>ไม่พบรายงานที่รอดำเนินการ</p>
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
                <input type="text" id="search-completed" placeholder="ค้นหาผู้ใช้/หัวข้อรายงาน..." name="search-completed" value="<?php echo htmlspecialchars($searchCompletedTerm); ?>">
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
                            <th>ชื่อบัญชีผู้ใช้</th>
                            <th>หัวข้อรายงาน</th>
                            <th>สถานะรายงาน</th>
                            <th>วันที่และเวลาที่ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $completedResult->fetch_assoc()): ?>
                            <?php
                            $statusText = $row['Report_Status'] == 1 ? "ดำเนินการแล้ว" : "ปฏิเสธการดำเนินการ";
                            $formattedDate = convertToThaiDate($row['Action_Datetime']);
                            ?>
                            <tr onclick="window.location.href='Report_Details.php?report_id=<?= htmlspecialchars($row['Report_ID']) ?>'">
                                <td><?= htmlspecialchars($row['User_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Report_Topic']) ?></td>
                                <td><?= htmlspecialchars($statusText) ?></td>
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