<?php
ini_set('session.cookie_lifetime', 10800); //3ชม.
session_start();
include_once dirname(__FILE__) . "/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // เก็บข้อมูลที่กรอกไว้ใน session
    $_SESSION['input_username'] = $username;
    $_SESSION['input_password'] = $password;

    $query = "SELECT Admin_ID,Admin_Name, Password, Admin_Status FROM admins WHERE Admin_Name = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            $password_with_id = $row['Admin_ID'] . $password;

            if (password_verify($password_with_id, $row['Password'])) {
                if ($row['Admin_Status'] == 1) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['admin_name'] = $row['Admin_Name'];
                    $_SESSION['Admin_ID'] = $row['Admin_ID'];

                    $admin_id = $row['Admin_ID'];
                    $updateQuery = "UPDATE admins SET Date_of_last_use = NOW() WHERE Admin_ID = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("i", $admin_id);
                    $stmt->execute();

                    $stmt->close();
                    $conn->close();

                    header('Location: RequestPage.php');
                    exit;

                } elseif ($row['Admin_Status'] == 2) {
                    $_SESSION['error'] = 'บัญชีของคุณถูกปิดการใช้งาน';
                }
            } else {
                $_SESSION['error'] = 'รหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $_SESSION['error'] = 'ชื่อผู้ใช้ไม่ถูกต้อง';
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดไม่สามารถดำเนินการได้ในขณะนี้';
    }

    $conn->close();
    header('Location: LoginPage.php');
    exit;
}
