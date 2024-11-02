<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['Admin_ID'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$admin_id = $_SESSION['Admin_ID'];

if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    //จับกลุ่มจากชื่อหนังสือ
    $query = "
SELECT
    r.Request_ID,
    r.Request_Book_Name,
    r.Request_Datetime,
    r.Request_Author,
    r.Request_Printed,
    r.Request_ISBN,
    r.Request_Picture,
    r.Request_Status,
    r.Action_Datetime,
    u.User_ID,
    u.User_Name,
    b.Bookshelf_Name
FROM
    requests r
JOIN
    users u ON r.User_ID = u.User_ID
JOIN
    bookshelves b ON r.Bookshelf_ID = b.Bookshelf_ID
WHERE
    r.Request_Status = 2
    AND r.Request_Book_Name = (SELECT Request_Book_Name FROM requests WHERE Request_ID = ?)
ORDER BY
    r.Request_Datetime ASC
";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // หากไม่พบ request_id ที่ตรงกัน
        echo "<script>
            alert('ไม่พบข้อมูลคำร้อง');
            window.location.href = 'RequestPage.php';
        </script>";
        exit;
    }

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['Request_Status'] == 2) {
            $requests[] = $row;
        }
    }

    $stmt->close();
} else {
    echo "<script>
    alert('ไม่มีการระบุรหัสคำร้อง');
    window.location.href = 'RequestPage.php';
</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['selected_requests']) && is_array($_GET['selected_requests']) && isset($_GET['selected_book'])) {
        $selected_requests = $_GET['selected_requests'];
        $selected_book = $_GET['selected_book'];

        $canInsert = true;
        $user_names_with_book = [];

        // ตรวจสอบว่ามีหนังสืออยู่ในชั้นหนังสือของผู้ใช้ใดบ้าง
        foreach ($selected_requests as $request_id) {
            $request_id = intval($request_id);

            $query = "
            SELECT *
            FROM requests
            WHERE Request_ID = ?
            AND Request_Status = 2
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $bookshelf_id = $row['Bookshelf_ID'];
                    $user_id = $row['User_ID'];

                    // ตรวจสอบว่ามีหนังสือในชั้นแล้วมั้ย
                    $check_query = "
                    SELECT u.User_Name
                    FROM books_on_bookshelf bbs
                    JOIN users u ON u.User_ID = bbs.User_ID
                    WHERE bbs.Bookshelf_ID = ?
                    AND bbs.Book_ID = ?
                    ";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->bind_param("ii", $bookshelf_id, $selected_book);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        // เก็บชื่อผู้ใช้ที่มีหนังสือในชั้นแล้ว
                        while ($user_row = $check_result->fetch_assoc()) {
                            $user_names_with_book[] = $user_row['User_Name'];
                        }
                        $canInsert = false;
                    }

                    $check_stmt->close();
                }
            } else {
                echo "<script>
                alert('ไม่พบคำร้อง');
                window.location.href = 'RequestPage.php';
                </script>";
                exit;
            }

            $stmt->close();
        }

        // มีหนังสือในชั้นแล้ว
        if (!$canInsert && !empty($user_names_with_book)) {
            $user_names_list = implode(', ', $user_names_with_book);
            echo "<script>
                alert('หนังสือเล่มนี้มีอยู่ในชั้นหนังสือของผู้ใช้ดังนี้แล้ว: $user_names_list');
            </script>";
        } else {
            // ไม่มีหนังสือในชั้น
            $addedBooks = []; // เก็บ ID หนังสือที่เพิ่มแล้ว

            foreach ($selected_requests as $request_id) {
                $request_id = intval($request_id);

                $query = "
                SELECT *
                FROM requests
                WHERE Request_ID = ?
                AND Request_Status = 2
                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $bookshelf_id = $row['Bookshelf_ID'];
                        $user_id = $row['User_ID'];

                        // ตรวจสอบว่ามีหนังสือในชั้นนี้แล้วหรือไม่
                        if (!in_array($user_id . '-' . $selected_book, $addedBooks)) {
                            $insert_query = "INSERT INTO books_on_bookshelf (Bookshelf_ID, Book_ID, User_ID) VALUES (?, ?, ?)";
                            $insert_stmt = $conn->prepare($insert_query);
                            $insert_stmt->bind_param("iii", $bookshelf_id, $selected_book, $user_id);

                            if ($insert_stmt->execute()) {
                                $addedBooks[] = $user_id . '-' . $selected_book; // บันทึก ID หนังสือที่เพิ่มแล้ว
                                $update_query = "UPDATE requests SET Request_Status = 1, Admin_ID = ?, Action_Datetime = NOW() WHERE Request_ID = ?";
                                $update_stmt = $conn->prepare($update_query);
                                $update_stmt->bind_param("ii", $admin_id, $request_id);
                                $update_stmt->execute();
                            } else {
                                echo "เกิดข้อผิดพลาด: " . $insert_stmt->error;
                            }

                            $insert_stmt->close();
                        }
                    }
                }

                $stmt->close();
            }

            // แจ้งว่าเพิ่มสำเร็จ
            echo "<script>
                alert('เพิ่มหนังสือเข้าชั้นหนังสือเรียบร้อยแล้ว');
                window.location.href = 'RequestPage.php';
            </script>";
        }
    }
}


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
    <link rel="stylesheet" href="style/styleRequest_Details.css" />
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>รายละเอียดคำร้อง</p>
    </header>

    <form id="book-selection-form" method="GET">
        <div class="slideshow-container">
            <?php foreach ($requests as $index => $request) : ?>
                <div class="mySlides fade">
                    <div class="request-details">
                        <div class="row">
                            <div class="column">
                                <?php if (!empty($request['Request_Picture'])): ?>
                                    <img class="Request_Picture" src="<?php echo '../requests/' . htmlspecialchars($request['Request_Picture']); ?>" alt="Request Picture">
                                <?php else: ?>
                                    <div style="display: flex; justify-content: center; align-items: center; height: 100%; text-align: center;">
                                        <p style="margin: 0;">ไม่ได้แนบไฟล์ภาพ</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="column">
                                <p><strong>ชื่อผู้ใช้:</strong> <?php echo htmlspecialchars($request['User_Name']); ?></p>
                                <p><strong>ชื่อชั้นหนังสือ:</strong> <?php echo htmlspecialchars($request['Bookshelf_Name']); ?></p>
                                <p><strong>สถานะคำร้อง:</strong> <?php echo $request['Request_Status'] == 1 ? 'ดำเนินการแล้ว' : 'รอดำเนินการ'; ?></p>
                                <p><strong>วันที่ส่งคำร้อง:</strong> <?php echo convertToThaiDate($request['Request_Datetime']); ?></p>
                                <hr style="border: 0.5px solid #ccc; width: 100%; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                                <p><strong>ชื่อหนังสือ:</strong> <?php echo htmlspecialchars($request['Request_Book_Name']); ?></p>
                                <p><strong>ผู้เขียน:</strong> <?php echo htmlspecialchars($request['Request_Author']); ?></p>
                                <p><strong>พิมพ์ครั้งที่:</strong> <?php echo $request['Request_Printed']; ?></p>
                                <p><strong>ISBN:</strong> <?php echo htmlspecialchars($request['Request_ISBN']); ?></p>
                                <label><input type="checkbox" name="selected_requests[]" value="<?php echo $request['Request_ID']; ?>" checked> เลือกคำร้องนี้</label>
                            </div>
                        </div>
                    </div>
                    <div class="button-container"><button class='select-button' name="select" onclick='confirmSelection()'>ยืนยันการเลือก</button></div>
                    <?php if (count($requests) > 1): ?>
                        <div style="text-align:center">
                            <div class="numbertext"><?php echo $index + 1; ?> / <?php echo count($requests); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (count($requests) > 1): ?>
                <a class="prev" id="prevBtn" onclick="plusSlides(-1)">❮</a>
                <a class="next" id="nextBtn" onclick="plusSlides(1)">❯</a>
            <?php endif; ?>
        </div>

        <div class="tab">
            <button class="tablinks" onclick="openTab(event, 'Tab1')">เพิ่มหนังสือจากคลังหนังสือ</button>
            <button class="tablinks" onclick="openTab(event, 'Tab2')">เพิ่มหนังสือในระบบ</button>
        </div>

        <div id="Tab1" class="tabcontent">
            <div class="table-container">
                <input type="hidden" name="tab" id="tab-input" value="Tab1">
                <input type="hidden" name="request_id" id="current_request_id" value="<?php echo htmlspecialchars($requests[0]['Request_ID']); ?>">
                <input type="hidden" name="user_id" id="current_user_id" value="<?php echo htmlspecialchars($requests[0]['User_ID']); ?>">
                <!--ค้นหา-->
                <form method="GET" action="">
                    <div class="search">
                        <input type="text" id="search-input" name="search-input" value="<?php echo isset($_GET['search-input']) ? htmlspecialchars($_GET['search-input']) : ''; ?>" placeholder="ค้นหาชื่อหนังสือ/ หมวดหมู่/ สำนักพิมพ์/ ผู้แต่ง/ISBN..." oninput="focusSearchBT()">
                        <button type="submit" id="btSearch" name="btSearch" class="search-button">ค้นหา</button>
                    </div>
                </form>

                <?php
                $booksPerPage = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $booksPerPage;

                $search = isset($_GET['search-input']) ? $conn->real_escape_string($_GET['search-input']) : '';

                $sql = "SELECT b.Book_ID, b.Book_Picture, b.Book_Name, b.Author, c.Category_Name, p.Publisher_Name, b.Printed, b.ISBN, b.Number_of_Page
                            FROM books b
                            LEFT JOIN categories c ON b.Category_ID = c.Category_id
                            LEFT JOIN publishers p ON b.Publisher_ID = p.Publisher_ID";

                if (!empty($search)) {
                    $sql .= " WHERE b.Book_Name LIKE '%$search%' OR b.Author LIKE '%$search%' OR c.Category_Name LIKE '%$search%' OR p.Publisher_Name LIKE '%$search%' OR b.ISBN LIKE '%$search%'";
                }

                $sql .= " ORDER BY b.Book_Name ASC";

                $sql .= " LIMIT $booksPerPage OFFSET $offset";

                $countQuery = "SELECT COUNT(*) AS totalBooks FROM books b
                                LEFT JOIN categories c ON b.Category_ID = c.Category_id
                                LEFT JOIN publishers p ON b.Publisher_ID = p.Publisher_ID";
                if (!empty($search)) {
                    $countQuery .= " WHERE b.Book_Name LIKE '%$search%' OR b.Author LIKE '%$search%' OR c.Category_Name LIKE '%$search%' OR p.Publisher_Name LIKE '%$search%' OR b.ISBN LIKE '%$search%'";
                }

                $countResult = $conn->query($countQuery);
                $totalBooks = $countResult->fetch_assoc()['totalBooks'];
                $totalPages = ceil($totalBooks / $booksPerPage);

                $result = $conn->query($sql);

                $start = ($page - 1) * $booksPerPage + 1;
                $end = min($page * $booksPerPage, $totalBooks);
                echo "<div class='search-results'>แสดง $start ถึง $end จาก $totalBooks รายการ</div>";

                if ($result->num_rows > 0) {
                    echo "<table border='1'>
                                <tr>
                                    <th>เลือก</th>
                                    <th>รูปปก</th>
                                    <th>ชื่อหนังสือ</th>
                                    <th>ผู้แต่ง</th>
                                    <th>หมวดหมู่</th>
                                    <th>สำนักพิมพ์</th>
                                    <th>ครั้งที่พิมพ์</th>
                                    <th>ISBN</th>
                                    <th>จำนวนหน้า</th>
                                </tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td><input type='radio' name='selected_book' value='" . $row["Book_ID"] . "'></td>
                                    <td><img src='../books/" . $row["Book_Picture"] . "' alt='Book Picture' width='100'></td>
                                    <td>" . $row["Book_Name"] . "</td>
                                    <td>" . $row["Author"] . "</td>
                                    <td>" . $row["Category_Name"] . "</td>
                                    <td>" . $row["Publisher_Name"] . "</td>
                                    <td>" . $row["Printed"] . "</td>
                                    <td>" . $row["ISBN"] . "</td>
                                    <td>" . $row["Number_of_Page"] . "</td>
                                </tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>ไม่มีข้อมูลในตาราง books</p>";
                }
                ?>
        </div>
</form>



    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?tab=Tab1&page=<?= $page - 1 ?>&request_id=<?= htmlspecialchars($requests[0]['Request_ID']) ?>&user_id=<?= htmlspecialchars($requests[0]['User_ID']) ?>">&laquo;</a>
            <?php endif; ?>

            <?php if ($page > 3): ?>
                <a href="?tab=Tab1&page=1&request_id=<?= htmlspecialchars($requests[0]['Request_ID']) ?>&user_id=<?= htmlspecialchars($requests[0]['User_ID']) ?>">1</a>
                <span>...</span>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                <?php if ($i == $page): ?>
                    <a href="#" class="active"><?= $i ?></a>
                <?php else: ?>
                    <a href="?tab=Tab1&page=<?= $i ?>&request_id=<?= htmlspecialchars($requests[0]['Request_ID']) ?>&user_id=<?= htmlspecialchars($requests[0]['User_ID']) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages - 2): ?>
                <span>...</span>
                <a href="?tab=Tab1&page=<?= $totalPages ?>&request_id=<?= htmlspecialchars($requests[0]['Request_ID']) ?>&user_id=<?= htmlspecialchars($requests[0]['User_ID']) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?tab=Tab1&page=<?= $page + 1 ?>&request_id=<?= htmlspecialchars($requests[0]['Request_ID']) ?>&user_id=<?= htmlspecialchars($requests[0]['User_ID']) ?>">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>

    <div id="Tab2" class="tabcontent">
        <input type="hidden" name="tab" value="Tab2">
        <?php include('Addbook_request.php');
        $conn->close();
        ?>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;

            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; tablinks[i]; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            document.getElementById(tabName).style.display = "block";
            if (evt) {
                evt.currentTarget.className += " active";
            }

            document.getElementById('tab-input').value = tabName;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'Tab1';

            openTab(null, tab);
        });


        function confirmSelection() {
            if (confirm("คุณต้องการเลือกหนังสือเล่มนี้ใช่หรือไม่?")) {
                document.getElementById('book-selection-form').submit();
            }
        }

        let slideIndex = 1;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n);
            updateFormData(slideIndex - 1);
        }

        function currentSlide(n) {
            showSlides(slideIndex = n);
            updateFormData(slideIndex - 1);
        }

        function showSlides(n) {
            let slides = document.getElementsByClassName("mySlides");
            let prevBtn = document.getElementById("prevBtn");
            let nextBtn = document.getElementById("nextBtn");

            if (n > slides.length) {
                slideIndex = slides.length;
            } else if (n < 1) {
                slideIndex = 1;
            }

            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }

            slides[slideIndex - 1].style.display = "block";

            prevBtn.style.display = (slideIndex === 1) ? "none" : "block";
            nextBtn.style.display = (slideIndex === slides.length) ? "none" : "block";
        }

        function updateFormData(index) {
            const requests = <?php echo json_encode($requests); ?>;
            document.getElementById('current_request_id').value = requests[index].Request_ID;
            document.getElementById('current_user_id').value = requests[index].User_ID;
        }

        var input = document.getElementById("search-input");
        input.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                document.getElementById("btSearch").click();
            }
        });
    </script>
</body>

</html>