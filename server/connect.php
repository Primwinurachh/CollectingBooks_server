<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "collectingbook_dbms";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("การเชื่อมต่อล้มเหลว");
    }

    // โค้ดเพิ่มเติมสำหรับการทำงานต่างๆ

} catch (Exception $e) {
    include_once dirname(__FILE__) . "/Error_Connect.html";
    exit();
}
?>
