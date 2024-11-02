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

if (isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    $query = "SELECT reports.*, users.User_Name
            FROM reports
            JOIN users ON reports.User_ID = users.User_ID
            WHERE reports.Report_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = ['error' => true, 'message' => 'ไม่พบคำสั่งที่ระบุ'];

            if ($_POST['action'] === 'reject') {
                $reject_reason = $conn->real_escape_string(trim($_POST['action_detail']));

                if (empty($reject_reason)) {
                    $response = ['error' => true, 'message' => 'กรุณากรอกเหตุผลในการปฏิเสธ'];
                } elseif (mb_strlen($reject_reason) > 255) {
                    $response = ['error' => true, 'message' => 'เหตุผลในการปฏิเสธต้องไม่เกิน 255 ตัวอักษร'];
                } elseif (!preg_match("/^[ก-ฮ0-9a-zA-Z\sเแโใไาิีึืุู่้๊๋็์]+$/", $reject_reason)) {
                    $response = ['error' => true, 'message' => 'เหตุผลการปฏิเสธสามารถมีได้เฉพาะตัวอักษรภาษาไทย ภาษาอังกฤษ และตัวเลขเท่านั้น'];
                } else {
                    $newStatus = 3;
                    $action_detail = $reject_reason;


                    $updateQuery = "UPDATE reports SET Report_Status = ?, Admin_ID = ?, Action_Datetime = NOW(), Action_Detail = ? WHERE Report_ID = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("iisi", $newStatus, $admin_id, $action_detail, $report_id);

                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'ปฏิเสธรายงานสำเร็จ'];
                    } else {
                        $response = ['error' => true, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ'];
                    }
                }
            } elseif ($_POST['action'] === 'complete') {
                $action_detail = $conn->real_escape_string(trim($_POST['action_detail']));

                if (empty($action_detail)) {
                    $response = ['error' => true, 'message' => 'กรุณากรอกรายละเอียดการดำเนินการ'];
                } elseif (mb_strlen($action_detail) > 255) {
                    $response = ['error' => true, 'message' => 'รายละเอียดการดำเนินการต้องไม่เกิน 255 ตัวอักษรอิอิ'];
                } elseif (!preg_match("/^[ก-ฮ0-9a-zA-Z\sเแโใไาิีึืุู่้๊๋็์]+$/", $action_detail)) {
                    $response = ['error' => true, 'message' => 'รายละเอียดการดำเนินการสามารถมีได้เฉพาะตัวอักษรภาษาไทย ภาษาอังกฤษ และตัวเลขเท่านั้น'];
                } else {
                    $newStatus = 1;

                    $updateQuery = "UPDATE reports SET Report_Status = ?, Admin_ID = ?, Action_Datetime = NOW(), Action_Detail = ? WHERE Report_ID = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("iisi", $newStatus, $admin_id, $action_detail, $report_id);

                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'ดำเนินการสำเร็จ'];
                    } else {
                        $response = ['error' => true, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ'];
                    }
                }
            }
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } else {
        echo "<script>alert('ไม่พบข้อมูลรายงาน');
        window.location.href='ReportPoblemsPage.php';
        </script>";
    }
} else {
    echo "<script>alert('ไม่มีการระบุรหัสรายงาน');
    window.location.href='ReportPoblemsPage.php';
    </script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบจัดการแอปพลิเคชันสะสมหนังสือสำหรับแอดมิน</title>
    <link rel="stylesheet" href="style/styleReport_Details.css" />

</head>

<body>
    <?php include('nav.php'); ?>

    <header class="header">
        <p>ปัญหาการใช้งานระบบ</p>
    </header>

    <div class="container">
        <h1><?php echo htmlspecialchars($report['Report_Topic']); ?></h1>
        <p class="report-user">ผู้ส่ง: <?php echo htmlspecialchars($report['User_Name']); ?></p>
        <p class="report-detail">วันที่รายงาน: <?php echo convertToThaiDate($report['Report_Datetime']); ?></p>
        <p class="report-detail">รายละเอียด: <?php echo htmlspecialchars($report['Report_Detail']); ?></p>
        <?php if (!empty($report['Report_Picture'])): ?>
            <img class="report-picture" src="<?php echo '../reports/' . htmlspecialchars($report['Report_Picture']); ?>" alt="Report Picture">
        <?php else: ?>
            <p class="no-image">ไม่มีรูปภาพแนบมาด้วย</p>
        <?php endif; ?>

        <?php
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

        function getStatusText($status)
        {
            switch ($status) {
                case 1:
                    return 'ดำเนินการแล้ว';
                case 2:
                    return 'ยังไม่ดำเนินการ';
                case 3:
                    return 'ปฏิเสธ';
                default:
                    return 'ไม่พบข้อมูลสถานะ';
            }
        }
        ?>

        <?php if ($report['Report_Status'] == 1): ?>
            <p><strong>สถานะ:</strong> <?php echo getStatusText($report['Report_Status']); ?></p>
            <p><strong>รายละเอียดการดำเนินการ:</strong> <?php echo htmlspecialchars($report['Action_Detail']); ?></p>
            <p><strong>วันที่ดำเนินการ:</strong> <?php echo convertToThaiDate($report['Action_Datetime']); ?></p>
            <button onclick="window.location.href='ReportPoblemsPage.php'" class="back_button">กลับ</button>
        <?php elseif ($report['Report_Status'] == 3): ?>
            <p><strong>สถานะ:</strong> <?php echo getStatusText($report['Report_Status']); ?></p>
            <p><strong>เหตุผลในการปฏิเสธ:</strong> <?php echo htmlspecialchars($report['Action_Detail']); ?></p>
            <p><strong>วันที่ดำเนินการ:</strong> <?php echo convertToThaiDate($report['Action_Datetime']); ?></p>
            <button onclick="window.location.href='ReportPoblemsPage.php'" class="back_button">กลับ</button>
        <?php else: ?>
            <div class="action">
                <button class="back-button" onclick="window.location.href='ReportPoblemsPage.php'">กลับ</button>
                <button class="reject" onclick="openPopup('reject')">ปฏิเสธ</button>
                <button class="complete" onclick="openPopup('complete')">ดำเนินการ</button>
            </div>
        <?php endif; ?>
    </div>

    <div id="popupAction" class="popup_container" style="display: none;">
        <div class="popup">
            <h2>รายละเอียดการดำเนินการ</h2>
            <textarea id="action_detail" name="action_detail" placeholder="กรอกรายละเอียดการดำเนินการ.."></textarea>
            <p class="input_info">* รายละเอียดการดำเนินการต้องไม่มีอักขระพิเศษ</p>
            <p id="resultMessage"></p>
            <button class="close_button" onclick="closePopup()">ปิด</button>
            <button class="submit_button" onclick="submitAction()">ยืนยัน</button>
        </div>
    </div>

    <script>
        function openPopup(actionType) {
            document.getElementById('popupAction').style.display = 'block';
            document.getElementById('action_detail').setAttribute('data-action', actionType);
        }

        function closePopup() {
            document.getElementById('popupAction').style.display = 'none';
            window.location.reload();
        }

        function submitAction() {
            const actionType = document.getElementById('action_detail').getAttribute('data-action');
            const actionDetail = document.getElementById('action_detail').value.trim();
            const resultMessage = document.getElementById('resultMessage');

            resultMessage.classList.remove('resultMessage_error', 'resultMessage_success');
            resultMessage.style.display = 'none';

            if (actionType === 'reject') {
                const regex = /^[a-zA-Z0-9ก-๙\s]+$/;
                if (actionDetail === "") {
                    resultMessage.textContent = "กรุณากรอกรายละเอียดเหตุผลการปฏิเสธ";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                    return;
                } else if (actionDetail.length > 255) {
                    resultMessage.textContent = "เหตุผลในการปฏิเสธต้องไม่เกิน 255 ตัวอักษร";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                    return;
                } else if (!regex.test(actionDetail)) {
                    resultMessage.textContent = "เหตุผลการปฏิเสธสามารถมีได้เฉพาะตัวอักษรภาษาไทย ภาษาอังกฤษ และตัวเลขเท่านั้น";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                    return;
                }
            }

            if (actionType === 'complete') {
                let text = "ทำการลบโควตออกจากระบบเเล้วเเละเนื่องจากการตรวจสอบโควตของผู้ใช้นี้มีเเต่คำหยาบคายมาโดยตลอดจึงทำการปิดใช้งานบัญชีผู้ใช้นี้";
                console.log(text.length);
                const regex = /^[a-zA-Z0-9ก-๙\s]+$/;
                if (actionDetail === "") {
                    resultMessage.textContent = "กรุณากรอกรายละเอียดการดำเนินการ";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                    return;
                } else if (actionDetail.length > 255) {
                    resultMessage.textContent = "รายละเอียดการดำเนินการต้องไม่เกิน 255 ตัวอักษร";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                    return;
                } else if (!regex.test(actionDetail)) {
                    resultMessage.textContent = "รายละเอียดการดำเนินการสามารถมีได้เฉพาะตัวอักษรภาษาไทย ภาษาอังกฤษ และตัวเลขเท่านั้น";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                    return;
                }
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", window.location.href, true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);

                    if (response.error) {
                        resultMessage.textContent = response.message;
                        resultMessage.classList.add('resultMessage_error');
                    } else if (response.success) {
                        resultMessage.textContent = response.message;
                        resultMessage.classList.add('resultMessage_success');
                        setTimeout(() => {
                            window.location.href = 'ReportPoblemsPage.php'; // ใส่ URL ที่ต้องการ
                        }, 2000);
                    }


                    resultMessage.style.display = 'block';
                } else {
                    resultMessage.textContent = "เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล";
                    resultMessage.classList.add('resultMessage_error');
                    resultMessage.style.display = 'block';
                }
            };

            xhr.send(`action=${encodeURIComponent(actionType)}&action_detail=${encodeURIComponent(actionDetail)}`);
        }

        function createHiddenField(name, value) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = name;
            hiddenField.value = value;
            return hiddenField;
        }
    </script>

</body>

</html>