<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$searchUserTerm = isset($_GET['search_user']) ? $conn->real_escape_string($_GET['search_user']) : '';
$searchAdminTerm = isset($_GET['search_admin']) ? $conn->real_escape_string($_GET['search_admin']) : '';

$statusMap = [
    "ยังไม่ยืนยันตัวตน" => 1,
    "เปิดการใช้งาน" => 2,
    "ปิดการใช้งาน" => 3
];

$itemsPerPage = 10;

$pageUser = isset($_GET['pageUser']) ? (int)$_GET['pageUser'] : 1;
$pageAdmin = isset($_GET['pageAdmin']) ? (int)$_GET['pageAdmin'] : 1;

$offsetUser = ($pageUser - 1) * $itemsPerPage;
$offsetAdmin = ($pageAdmin - 1) * $itemsPerPage;

$countUserSql = "SELECT COUNT(*) AS count FROM users WHERE User_ID LIKE ? OR User_Name LIKE ? OR Year_of_Birth LIKE ? OR Datetime_of_last_use LIKE ?";
$searchTerm = "%$searchUserTerm%";
$stmt = $conn->prepare($countUserSql);
$stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$countResultUser = $stmt->get_result();
$countRowUser = $countResultUser->fetch_assoc();
$totalUsers = $countRowUser['count'];
$totalPagesUser = ceil($totalUsers / $itemsPerPage);

$countAdminSql = "SELECT COUNT(*) AS count FROM admins WHERE (Admin_ID LIKE ? OR Admin_Name LIKE ? OR Date_of_last_use LIKE ?) AND Admin_Name != 'Admin'";
$searchTermAdmin = "%$searchAdminTerm%";
$stmt = $conn->prepare($countAdminSql);
$stmt->bind_param('sss', $searchTermAdmin, $searchTermAdmin, $searchTermAdmin);
$stmt->execute();
$countResultAdmin = $stmt->get_result();
$countRowAdmin = $countResultAdmin->fetch_assoc();
$totalAdmins = $countRowAdmin['count'];
$totalPagesAdmin = ceil($totalAdmins / $itemsPerPage);

$userQuery = "SELECT * FROM users WHERE User_Name LIKE ?  ORDER BY User_Name ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('sii', $searchTerm, $itemsPerPage, $offsetUser);
$stmt->execute();
$userResult = $stmt->get_result();

$adminQuery = "SELECT * FROM admins WHERE Admin_Name LIKE ? AND Admin_Name != 'Admin' ORDER BY Admin_Name ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($adminQuery);
$stmt->bind_param('sii', $searchTermAdmin, $itemsPerPage, $offsetAdmin);
$stmt->execute();
$adminResult = $stmt->get_result();


$activeTab = isset($_GET['search_user']) ? 'user' : (isset($_GET['search_admin']) ? 'admin' : 'user');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style/styleAllUserPage.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>จัดการข้อมูลผู้ใช้งานและแอดมิน</p>
    </header>

    <div class="tab">
        <button class="tablink" onclick="openPage('user', this)" id="userTab">ข้อมูลผู้ใช้งาน</button>
        <button class="tablink" onclick="openPage('admin', this)" id="adminTab">ข้อมูลผู้ดูแลระบบ</button>
    </div>

    <div id="user" class="tabcontent" style="vertical-align: middle;">
        <div class="search">
            <form id="searchFormUser" method="GET">
                <label for="search-input-user" class="search-icon">
                    <img src="asset/search.png" alt="Search" class="search-image">
                </label>
                <input type="text" id="search-user" placeholder="ค้นหาชื่อบัญชี..." name="search_user" value="<?php echo htmlspecialchars($searchUserTerm); ?>">
                <button class="search-button" type="submit">ค้นหา</button>
            </form>
        </div>
        <div class="search-results">
            <?php
            $start = ($pageUser - 1) * $itemsPerPage + 1;
            $end = min($pageUser * $itemsPerPage, $totalUsers);
            echo "<div class='search-results'>";
            echo "แสดง $start ถึง $end จาก $totalUsers รายการ";
            echo "</div>";
            ?>
        </div>

        <div class="table">
            <?php if ($userResult->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อบัญชีผู้ใช้งาน</th>
                            <th>ปีเกิด</th>
                            <th>วันที่ใช้งานระบบล่าสุด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $userResult->fetch_assoc()): ?>
                            <?php
                            if (!empty($row['Datetime_of_last_use'])) {
                                $date = new DateTime($row['Datetime_of_last_use']);
                                $thaiMonths = array(
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
                                );
                                $day = $date->format('j');
                                $month = $thaiMonths[(int)$date->format('n')];
                                $year = (int)$date->format('Y') + 543;
                                $time = $date->format('H:i น.');
                                $formattedDate = "$day $month $year $time";
                            } else {
                                $formattedDate = "ไม่พบวันที่";
                            }
                            $formattedBirthYear = "ค.ศ. " . $row['Year_of_Birth'];
                            switch ($row['User_Status']) {
                                case 1:
                                    $statusUser = "ยังไม่ยืนยันตัวตน";
                                    break;
                                case 2:
                                    $statusUser = "เปิดการใช้งาน";
                                    break;
                                case 3:
                                    $statusUser = "ปิดการใช้งาน";
                                    break;
                                default:
                                    $statusUser = "ไม่ทราบสถานะ";
                            }
                            ?>
                            <tr onclick="window.location.href='User_Details.php?user_id=<?= htmlspecialchars($row['User_ID']) ?>'">
                                <td><?= htmlspecialchars($row['User_Name']) ?></td>
                                <td><?= htmlspecialchars($formattedBirthYear) ?></td>
                                <td><?= htmlspecialchars($formattedDate) ?></td>
                                <td><?= htmlspecialchars($statusUser) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($totalPagesUser > 1): ?>
                    <div class="pagination">
                        <?php if ($pageUser > 1): ?>
                            <a href="?pageUser=<?= $pageUser - 1 ?>">&laquo;</a>
                        <?php endif; ?>

                        <?php if ($pageUser > 3): ?>
                            <a href="?pageUser=1">1</a>
                            <span>...</span>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pageUser - 2); $i <= min($pageUser + 2, $totalPagesUser); $i++): ?>
                            <?php if ($i == $pageUser): ?>
                                <a href="#" class="active"><?= $i ?></a>
                            <?php else: ?>
                                <a href="?pageUser=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pageUser < $totalPagesUser - 2): ?>
                            <span>...</span>
                            <a href="?pageUser=<?= $totalPagesUser ?>"><?= $totalPagesUser ?></a>
                        <?php endif; ?>

                        <?php if ($pageUser < $totalPagesUser): ?>
                            <a href="?pageUser=<?= $pageUser + 1 ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <p>ไม่พบข้อมูลผู้ใช้งาน</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="admin" class="tabcontent" style="vertical-align: middle;">
        <div class="search">
            <form id="searchFormAdmin" method="GET">
                <label for="search-input-admin" class="search-icon">
                    <img src="asset/search.png" alt="Search" class="search-image">
                </label>
                <input type="text" id="search-admin" placeholder="ค้นชื่อบัญชีผู้ดูแลระบบ..." name="search_admin" value="<?php echo htmlspecialchars($searchAdminTerm); ?>">
                <button class="search-button" type="submit">ค้นหา</button>
            </form>
        </div>

        <div class="search-results">
            <?php
            $start = ($pageAdmin - 1) * $itemsPerPage + 1;
            $end = min($pageAdmin * $itemsPerPage, $totalAdmins);
            echo "<div class='search-results'>";
            echo "แสดง $start ถึง $end จาก $totalAdmins รายการ";
            echo "</div>";
            ?>
        </div>

        <div class="table">
            <?php if ($adminResult->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อบัญชีผู้ดูแลระบบ</th>
                            <th>วันที่ใช้งานระบบล่าสุด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $adminResult->fetch_assoc()): ?>
                            <?php
                            if (!empty($row['Date_of_last_use'])) {
                                $date = new DateTime($row['Date_of_last_use']);
                                $thaiMonths = array(
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
                                );
                                $day = $date->format('j');
                                $month = $thaiMonths[(int)$date->format('n')];
                                $year = (int)$date->format('Y') + 543;
                                $time = $date->format('H:i น.');
                                $formattedDate = "$day $month $year $time";
                            } else {
                                $formattedDate = "ไม่พบวันที่";
                            }
                            switch ($row['Admin_Status']) {
                                case 1:
                                    $statusAdmin = "เปิดการใช้งาน";
                                    break;
                                case 2:
                                    $statusAdmin = "ปิดการใช้งาน";
                                    break;
                                default:
                                    $statusAdmin = "ไม่ทราบสถานะ";
                            }
                            ?>
                            <tr onclick="window.location.href='Admin_Details.php?admin_id=<?= htmlspecialchars($row['Admin_ID']) ?>'">
                                <td><?= htmlspecialchars($row['Admin_Name']) ?></td>
                                <td><?= htmlspecialchars($formattedDate) ?></td>
                                <td><?= htmlspecialchars($statusAdmin) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($totalPagesAdmin > 1): ?>
    <div class="pagination">
        <?php if ($pageAdmin > 1): ?>
            <a href="?pageAdmin=<?= $pageAdmin - 1 ?>&search_admin=<?= urlencode($searchAdminTerm) ?>">&laquo;</a>
        <?php endif; ?>

        <?php if ($pageAdmin > 3): ?>
            <a href="?pageAdmin=1&search_admin=<?= urlencode($searchAdminTerm) ?>">1</a>
            <span>...</span>
        <?php endif; ?>

        <?php for ($i = max(1, $pageAdmin - 2); $i <= min($pageAdmin + 2, $totalPagesAdmin); $i++): ?>
            <?php if ($i == $pageAdmin): ?>
                <a href="#" class="active"><?= $i ?></a>
            <?php else: ?>
                <a href="?pageAdmin=<?= $i ?>&search_admin=<?= urlencode($searchAdminTerm) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pageAdmin < $totalPagesAdmin - 2): ?>
            <span>...</span>
            <a href="?pageAdmin=<?= $totalPagesAdmin ?>&search_admin=<?= urlencode($searchAdminTerm) ?>"><?= $totalPagesAdmin ?></a>
        <?php endif; ?>

        <?php if ($pageAdmin < $totalPagesAdmin): ?>
            <a href="?pageAdmin=<?= $pageAdmin + 1 ?>&search_admin=<?= urlencode($searchAdminTerm) ?>">&raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>


            <?php else: ?>
                <p>ไม่พบข้อมูลผู้ดูแลระบบ</p>
            <?php endif; ?>
        </div>
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