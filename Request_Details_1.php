<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['Admin_ID'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    //จับกลุ่มจากวันที่ดำเนินการเเละชื่อหนังสือ
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
        r.Request_Status = 1
        AND (r.Request_Book_Name = (SELECT Request_Book_Name FROM requests WHERE Request_ID = ?)
        AND  r.Action_Datetime = (SELECT Action_Datetime FROM requests WHERE Request_ID = ?))
ORDER BY
    r.Request_Datetime ASC
";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $request_id, $request_id);
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
        if ($row['Request_Status'] == 1) {
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

    <div class="slideshow-container">
        <?php if (!empty($requests)) : ?>
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
                                <p><strong>สถานะคำร้อง:</strong> ดำเนินการแล้ว</p>
                                <p><strong>วันที่ส่งคำร้อง:</strong> <?php echo convertToThaiDate($request['Request_Datetime']); ?></p>
                                <p><strong>วันที่ดำเนินการ:</strong> <?php echo convertToThaiDate($request['Action_Datetime']); ?></p>
                                <hr style="border: 0.5px solid #ccc; width: 100%; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                                <p><strong>ชื่อหนังสือ:</strong> <?php echo htmlspecialchars($request['Request_Book_Name']); ?></p>
                                <p><strong>ผู้เขียน:</strong> <?php echo htmlspecialchars($request['Request_Author']); ?></p>
                                <p><strong>พิมพ์ครั้งที่:</strong> <?php echo $request['Request_Printed']; ?></p>
                                <p><strong>ISBN:</strong> <?php echo htmlspecialchars($request['Request_ISBN']); ?></p>
                                <button onclick="window.location.href='RequestPage.php'" class="back-button">กลับไปยังหน้าหลัก</button>
                            </div>
                        </div>
                    </div>

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
        <?php else: ?>
            <p>ไม่มีคำร้องที่ดำเนินการแล้ว</p>
        <?php endif; ?>
    </div>

    <script>
        let slideIndex = 1;
        const requests = <?php echo !empty($requests) ? json_encode($requests) : '[]'; ?>;

        // แสดงสไลด์เริ่มต้น
        showSlides(slideIndex);
        updateFormData(slideIndex - 1);

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
            if (requests.length > 0) {
                document.getElementById('current_request_id').value = requests[index].Request_ID;
                document.getElementById('current_user_id').value = requests[index].User_ID;
            }
        }
    </script>
</body>

</html>