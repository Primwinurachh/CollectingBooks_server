<?php
include_once dirname(__FILE__) . "/connect.php";
include_once dirname(__FILE__) . "/simple_html_dom.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit();
}

$admin_id = $_SESSION['Admin_ID'];

$selected_method = isset($_POST['method']) ? $_POST['method'] : 'manual';

function showAlertAndRedirect($message)
{
    echo "<script>alert('$message');";
    echo "</script>";
}


$sql = "SELECT Category_ID, Category_Name FROM categories ORDER BY Category_Name ASC";
$result = $conn->query($sql);

$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$book_name = "";
$author = "";
$publisher = "";
$isbn = "";
$printed = "";
$number_of_page = "";
$category_id = "";
$search_results = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'search') {

        if (isset($_POST['site']) && isset($_POST['url'])) {

            if (empty($_POST['site'])) {
                echo "<script>alert('กรุณาเลือกเเหล่งที่มาของหนังสือ');</script>";
            } elseif (empty($_POST['url'])) {
                echo "<script>alert('กรุณากรอก URL ของหนังสือ');</script>";
            } else {
                $site = $_POST['site'];
                $url = $_POST['url'];

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    echo "<script>alert('URL ที่ระบุไม่ถูกต้อง');</script>";
                } else {
                    $html_content = @file_get_contents($url);
                    if ($html_content === FALSE) {
                        echo "<script>alert('ไม่สามารถเข้าถึงข้อมูลจาก URL ที่ระบุได้');</script>";
                    } else {
                        $html = file_get_html($url);
                        if (!$html) {
                            echo "<script>alert('ไม่สามารถเข้าถึงข้อมูลจาก URL ที่ระบุได้');</script>";
                        } else {
                            switch ($site) {


                                case 'naiin':
                                    if (!$html) {
                                        $search_results = ""; // รีเซ็ต $search_results
                                        echo "<script>alert('ไม่สามารถเข้าถึง URL ที่ระบุได้');</script>";
                                        break;
                                    }
                                    $fide_title = $html->find('h1.title-topic', 0);
                                    if ($fide_title) {
                                        $book_name = trim($fide_title->plaintext);
                                    } else {
                                        $search_results = ""; // รีเซ็ต $search_results
                                        echo "<script>alert('ไม่พบข้อมูลหนังสือใน URL ที่ระบุ');</script>";
                                        break;
                                    }
                                    $author_element = $html->find('p', 0)->find('a.inline-block.link-book-detail', 0);
                                    $author = $author_element ? trim($author_element->plaintext) : 'ไม่พบผู้เขียน';

                                    $publisher_element = $html->find('p', 1)->find('a.inline-block.link-book-detail', 0);
                                    $publisher = $publisher_element ? trim($publisher_element->plaintext) : 'ไม่พบสำนักพิมพ์';

                                    $page_div = $html->find('div.additional-information-item', 0);
                                    if ($page_div) {
                                        $number_of_page_text = trim($page_div->find('div', 2)->plaintext);
                                        $number_of_page = preg_replace('/\D/', '', $number_of_page_text);
                                    } else {
                                        $number_of_page = 'ไม่พบข้อมูลจำนวนหน้า';
                                    }

                                    $barcode_div = $html->find('div.additional-information-item', 4);
                                    $isbn = $barcode_div ? trim($barcode_div->find('div', 2)->plaintext) : 'ไม่พบข้อมูล ISBN';

                                    break;

                                case 'se-ed':
                                    $book_title_element = $html->find('h1.book-title', 0);
                                    $book_name = $book_title_element ? trim($book_title_element->plaintext) : 'ไม่พบชื่อหนังสือ';

                                    $author_element = $html->find('td.book-author-list a', 0);
                                    $author = $author_element ? trim($author_element->plaintext) : 'ไม่พบชื่อผู้เขียน';

                                    $publisher_element = $html->find('tr', 5);
                                    $publisher_find = $html->find('td.right a', 0);

                                    if ($publisher_find) {
                                        $publisher = $publisher_find->plaintext;
                                    }

                                    $detail_table = $html->find('table.detail-table', 0);
                                    if ($detail_table) {
                                        $isbn_td = null;
                                        foreach ($detail_table->find('td.left') as $td) {
                                            if (trim($td->plaintext) === 'ISBN') {
                                                $isbn_td = $td;
                                                break;
                                            }
                                        }

                                        if ($isbn_td) {
                                            $isbn_text = $isbn_td->next_sibling()->plaintext;

                                            if (preg_match('/\b\d{13}\b/', $isbn_text, $matches)) {
                                                $isbn = $matches[0];
                                            } else {
                                                $search_results = ""; // รีเซ็ต $search_results
                                                showAlertAndRedirect("ไม่พบข้อมูล ISBN");
                                            }
                                        } else {
                                            $search_results = ""; // รีเซ็ต $search_results
                                            showAlertAndRedirect("ไม่พบข้อมูล ISBN");
                                        }
                                    } else {
                                        $search_results = ""; // รีเซ็ต $search_results
                                        showAlertAndRedirect("ไม่พบ URL ที่ระบุ");
                                        break;
                                    }

                                    if ($detail_table) {
                                        $pages_td = null;
                                        foreach ($detail_table->find('td.left') as $td) {
                                            if (trim($td->plaintext) === 'ISBN') {
                                                $pages_td = $td;
                                                break;
                                            }
                                        }
                                        if ($pages_td) {
                                            $page_text = $pages_td->next_sibling()->plaintext;
                                            if (preg_match('/(\d+)\s/', $page_text, $matches)) {
                                                $number_of_page = $matches[1];
                                            }
                                        }
                                    }
                                    break;

                                case 'nlt':
                                    $fide_title = $html->find('#ReqBookTitleName', 0);
                                    if ($fide_title) {
                                        $book_name = $fide_title->value;
                                    } else {
                                        $search_results = ""; // รีเซ็ต $search_results
                                        echo "<script>alert('ไม่พบข้อมูลชื่อหนังสือใน URL ที่ระบุ');</script>";
                                        break;
                                    }

                                    $fide_author = $html->find('#AuthorNames', 0);
                                    if ($fide_author) {
                                        $author = $fide_author->value;
                                    } else {
                                        echo "<script>alert('ไม่พบข้อมูลชื่อผู้เขียนใน URL ที่ระบุ');</script>";
                                        break;
                                    }

                                    $find_edition = $html->find('#EditionNote', 0);
                                    if ($find_edition) {
                                        $printed = $find_edition->value;
                                    } else {
                                        echo "<script>alert('ไม่พบข้อมูลพิมพ์ครั้งที่ใน URL ที่ระบุ');</script>";
                                        break;
                                    }

                                    $find_pages = $html->find('input[name=NoOfPage]');
                                    if ($find_pages) {
                                        foreach ($find_pages as $pages_value) {
                                            $number_of_page = $pages_value->value;
                                        }
                                    } else {
                                        echo "<script>alert('ไม่พบข้อมูลจำนวนหน้าใน URL ที่ระบุ');</script>";
                                        break;
                                    }

                                    $find_isbn = $html->find('#ISBNCode', 0);
                                    if ($find_isbn) {
                                        $isbn = $find_isbn->value;
                                        $isbn = preg_replace('/\D/', '', $isbn);
                                    } else {
                                        echo "<script>alert('ไม่พบข้อมูล ISBN ใน URL ที่ระบุ');</script>";
                                        break;
                                    }

                                    $find_publisher = $html->find('#PubName', 0);
                                    if ($find_publisher) {
                                        $publisher = $find_publisher->value;
                                    } else {
                                        echo "<script>alert('ไม่พบข้อมูลสำนักพิมพ์ใน URL ที่ระบุ');</script>";
                                        break;
                                    }
                                    break;

                                default:
                                    $search_results = ""; // รีเซ็ต $search_results
                                    break;
                            }
                            $search_results = "
                            <p><strong>ชื่อหนังสือ:</strong> " . htmlspecialchars($book_name) . "</p>
                            <p><strong>ผู้เขียน:</strong> " . htmlspecialchars($author) . "</p>
                            <p><strong>สำนักพิมพ์:</strong> " . htmlspecialchars($publisher) . "</p>
                            <p><strong>ISBN:</strong> " . htmlspecialchars($isbn) . "</p>
                            <p><strong>พิมพ์ครั้งที่:</strong> " . htmlspecialchars($printed) . "</p>
                            <p><strong>จำนวนหน้า:</strong> " . htmlspecialchars($number_of_page) . "</p>
                            <p><strong>หมวดหมู่:</strong> " . htmlspecialchars($category_id) . "</p>
                            ";
                        }
                    }
                }
            }
        }
    } else {

        if ($action === 'savesearch') {

            $book_name = isset($_POST['book_name']) ? $_POST['book_name'] : "-";
            $author = isset($_POST['author']) ? $_POST['author'] : "-";
            $publisher = isset($_POST['publisher']) ? $_POST['publisher'] : "-";
            $isbn = isset($_POST['isbn']) ? $_POST['isbn'] : "-";
            $printed = isset($_POST['printed']) ? $_POST['printed'] : "-";
            $number_of_page = isset($_POST['number_of_page']) ? $_POST['number_of_page'] : "-";
            $category_id = isset($_POST['category']) ? $_POST['category'] : null;
            $method = $_POST['method'] ?? 'manual';
            $bError = false;
            $search_results = "
                            <p><strong>ชื่อหนังสือ:</strong> " . htmlspecialchars($book_name) . "</p>
                            <p><strong>ผู้เขียน:</strong> " . htmlspecialchars($author) . "</p>
                            <p><strong>สำนักพิมพ์:</strong> " . htmlspecialchars($publisher) . "</p>
                            <p><strong>ISBN:</strong> " . htmlspecialchars($isbn) . "</p>
                            <p><strong>พิมพ์ครั้งที่:</strong> " . htmlspecialchars($printed) . "</p>
                            <p><strong>จำนวนหน้า:</strong> " . htmlspecialchars($number_of_page) . "</p>
                            <p><strong>หมวดหมู่:</strong> " . htmlspecialchars($category_id) . "</p>
                            ";

            if (!isset($_FILES["uploadfile_search"]) || $_FILES["uploadfile_search"]["size"] == 0) {
                showAlertAndRedirect("กรุณาเลือกรูปภาพ");
                $bError = true;
            } elseif (empty($book_name) || empty($author) || empty($publisher) || empty($isbn) || empty($printed) || empty($number_of_page) || empty($category_id)) {
                showAlertAndRedirect("กรุณากรอกข้อมูลให้ครบทุกช่อง");
                $bError = true;
            } elseif (mb_strlen($book_name) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),:]+$/u', $book_name)) {
                showAlertAndRedirect("ชื่อหนังสือสามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - : () , และต้องไม่เกิน 100 ตัวอักษร");
                $bError = true;
            } elseif (mb_strlen($author) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),]+$/u', $author)) {
                showAlertAndRedirect("ชื่อผู้แต่งสามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - () , และต้องไม่เกิน 100 ตัวอักษร");
                $bError = true;
            } elseif (mb_strlen($publisher) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),]+$/u', $publisher)) {
                showAlertAndRedirect("สำนักพิมพ์สามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - () , และต้องไม่เกิน 100 ตัวอักษร");
                $bError = true;
            } elseif (!ctype_digit($printed) || strlen($printed) > 11 || intval($printed) == 0) {
                showAlertAndRedirect("พิมพ์ครั้งที่ต้องเป็นตัวเลขเท่านั้น ไม่เป็นศูนย์ และไม่เกิน 11 หลัก");
                $bError = true;
            } elseif (!ctype_digit($number_of_page) || strlen($number_of_page) > 11 || intval($number_of_page) == 0) {
                showAlertAndRedirect("จำนวนหน้าต้องเป็นตัวเลขเท่านั้น ไม่เป็นศูนย์ และไม่เกิน 11 หลัก");
                $bError = true;
            } elseif (!ctype_digit($isbn) || (strlen($isbn) !== 13 && strlen($isbn) !== 10) || intval($isbn) == 0) {
                showAlertAndRedirect("ISBN ต้องเป็นตัวเลข 10 หรือ 13 หลักเท่านั้น และไม่เป็นศูนย์");
                $bError = true;
            }

            $stmt = $conn->prepare("SELECT * FROM categories WHERE Category_ID = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                showAlertAndRedirect("กรุณาเลือกหมวดหมู่");
                $bError = true;
            }

            $stmt = $conn->prepare("SELECT ISBN, Book_Name FROM books WHERE ISBN = ? OR Book_Name = ?");
            $stmt->bind_param("ss", $isbn, $book_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                showAlertAndRedirect("มีหนังสือที่มี ISBN หรือชื่อเดียวกันอยู่ในระบบแล้ว");
                $bError = true;
            }
            //ไม่มีข้อผิดพลาด
            if ($bError == false) {
                $stmt = $conn->prepare("SELECT Publisher_ID FROM publishers WHERE Publisher_Name = ?");
                $stmt->bind_param("s", $publisher);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $publisher_id = $row['Publisher_ID'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO publishers (Publisher_Name) VALUES (?)");
                    $stmt->bind_param("s", $publisher);
                    if ($stmt->execute()) {
                        $publisher_id = $stmt->insert_id;
                    } else {
                        showAlertAndRedirect("เกิดข้อผิดพลาดในการบันทึกสำนักพิมพ์");
                    }
                }

                $datetime_added = date('Y-m-d H:i:s');

                $stmt = $conn->prepare("INSERT INTO books (Book_Name, Author, Publisher_ID, ISBN, Printed, Number_of_Page, Category_ID, Datetime_Added, Admin_ID) VALUES (?, ?, ?, ?, ?, ?, ?, now(), ?)");
                $stmt->bind_param("sssssiii", $book_name, $author, $publisher_id, $isbn, $printed, $number_of_page, $category_id, $admin_id);

                if ($stmt->execute()) {
                    $book_id = $stmt->insert_id;
                } else {
                    echo "<script>
                console.log('เกิดข้อผิดพลาดในการบันทึกหนังสือ: " . addslashes($stmt->error) . "');
                showAlertAndRedirect('การเพิ่มหนังสือไม่สำเร็จ');
                </script>";
                }

                $stmt->close();

                if (isset($_FILES["uploadfile_search"]) && $_FILES["uploadfile_search"]["error"] == UPLOAD_ERR_OK) {
                    $originalFilename = $_FILES["uploadfile_search"]["name"];
                    $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                    $tempname = $_FILES["uploadfile_search"]["tmp_name"];

                    $allowedExtensions = ['jpg', 'jpeg'];
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        showAlertAndRedirect("ไฟล์รูปภาพต้องเป็นไฟล์ JPG หรือ JPEG เท่านั้น");
                    } elseif ($_FILES["uploadfile_search"]["size"] > 10485760) {
                        showAlertAndRedirect("ขนาดไฟล์รูปภาพต้องไม่เกิน 10 MB");
                    }

                    $newFilename = "book_". $book_id . "." . $fileExtension;
                    $folder = "../books/" . $newFilename;
                    if (!move_uploaded_file($tempname, $folder)) {
                        showAlertAndRedirect("ไม่สามารถอัพโหลดไฟล์ได้");
                    }

                    $stmt = $conn->prepare("UPDATE books SET Book_Picture = ? WHERE Book_ID = ?");
                    $stmt->bind_param("si", $newFilename, $book_id);

                    if ($stmt->execute()) {
                        echo "<script>
                        alert('บันทึกหนังสือเรียบร้อยแล้ว');
                        window.location.href = 'Request_Details_2.php?request_id=" . $request_id . "';
                    </script>";
                    } else {
                        echo "<script>
                    console.log('เกิดข้อผิดพลาดในการอัปเดตรูปภาพ: " . addslashes($stmt->error) . "');
                    showAlertAndRedirect('การเพิ่มรูปภาพไม่สำเร็จ');
                    </script>";
                    }

                    $stmt->close();
                } else {
                    echo "<script>
                alert('บันทึกหนังสือเรียบร้อยเเล้วแต่ยังไม่ได้อัปโหลดรูปภาพ');
                window.location.href = 'Request_Details_2.php?request_id=" . $request_id . "';
                </script>";
                }

                $conn->close();
            }
        } elseif ($action === 'saveself') {
            $book_name = trim($_POST['book_name'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $publisher = trim($_POST['publisher'] ?? '');
            $isbn = trim($_POST['isbn'] ?? '');
            $printed = trim($_POST['printed'] ?? '');
            $number_of_page = trim($_POST['number_of_page'] ?? '');
            $category_id = $_POST['category'] ?? '';
            $method = $_POST['method'] ?? 'manual';
            $bError = false;

            if (!isset($_FILES["uploadfile_self"]) || $_FILES["uploadfile_self"]["size"] == 0) {
                showAlertAndRedirect("กรุณาเลือกรูปภาพ");
                $bError = true;
            } elseif (empty($book_name) || empty($author) || empty($publisher) || empty($isbn) || empty($printed) || empty($number_of_page) || empty($category_id)) {
                showAlertAndRedirect("กรุณากรอกข้อมูลให้ครบทุกช่อง");
                $bError = true;
            } elseif (mb_strlen($book_name) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),:]+$/u', $book_name)) {
                showAlertAndRedirect("ชื่อหนังสือสามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - : () , และต้องไม่เกิน 100 ตัวอักษร");
                $bError = true;
            } elseif (mb_strlen($author) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),]+$/u', $author)) {
                showAlertAndRedirect("ชื่อผู้แต่งสามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - () , และต้องไม่เกิน 100 ตัวอักษร");
                $bError = true;
            } elseif (mb_strlen($publisher) > 100 || !preg_match('/^[ก-๙A-Za-z0-9 .\-(),]+$/u', $publisher)) {
                showAlertAndRedirect("สำนักพิมพ์สามารถมีได้เฉพาะตัวอักษรไทย, อังกฤษ, ตัวเลข และอักขระ . - () , และต้องไม่เกิน 100 ตัวอักษร");
                $bError = true;
            } elseif (!ctype_digit($printed) || strlen($printed) > 11 || intval($printed) == 0) {
                showAlertAndRedirect("พิมพ์ครั้งที่ต้องเป็นตัวเลขเท่านั้น ไม่เป็นศูนย์ และไม่เกิน 11 หลัก");
                $bError = true;
            } elseif (!ctype_digit($number_of_page) || strlen($number_of_page) > 11 || intval($number_of_page) == 0) {
                showAlertAndRedirect("จำนวนหน้าต้องเป็นตัวเลขเท่านั้น ไม่เป็นศูนย์ และไม่เกิน 11 หลัก");
                $bError = true;
            } elseif (!ctype_digit($isbn) || (strlen($isbn) !== 13 && strlen($isbn) !== 10) || intval($isbn) == 0) {
                showAlertAndRedirect("ISBN ต้องเป็นตัวเลข 10 หรือ 13 หลักเท่านั้น และไม่เป็นศูนย์");
                $bError = true;
            }

            $stmt = $conn->prepare("SELECT * FROM categories WHERE Category_ID = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                showAlertAndRedirect("ไม่พบ Category ID ในฐานข้อมูล");
                $bError = true;
            }

            $stmt = $conn->prepare("SELECT ISBN, Book_Name FROM books WHERE ISBN = ? OR Book_Name = ?");
            $stmt->bind_param("ss", $isbn, $book_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                showAlertAndRedirect("มีหนังสือที่มี ISBN หรือชื่อเดียวกันอยู่ในระบบแล้ว");
                $bError = true;
            }
            //ไม่มีข้อผิดพลาด
            if ($bError == false) {
                $stmt = $conn->prepare("SELECT Publisher_ID FROM publishers WHERE Publisher_Name = ?");
                $stmt->bind_param("s", $publisher);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $publisher_id = $row['Publisher_ID'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO publishers (Publisher_Name) VALUES (?)");
                    $stmt->bind_param("s", $publisher);
                    if ($stmt->execute()) {
                        $publisher_id = $stmt->insert_id;
                    } else {
                        showAlertAndRedirect("เกิดข้อผิดพลาดในการบันทึกสำนักพิมพ์");
                    }
                }
                $datetime_added = date('Y-m-d H:i:s');

                $stmt = $conn->prepare("INSERT INTO books (Book_Name, Author, Publisher_ID, ISBN, Printed, Number_of_Page, Category_ID, Datetime_Added, Admin_ID) VALUES (?, ?, ?, ?, ?, ?, ?, now(), ?)");
                $stmt->bind_param("sssssiii", $book_name, $author, $publisher_id, $isbn, $printed, $number_of_page, $category_id, $admin_id);

                if ($stmt->execute()) {
                    $book_id = $stmt->insert_id;
                } else {
                    echo "<script>
                console.log('เกิดข้อผิดพลาดในการบันทึกหนังสือ: " . addslashes($stmt->error) . "');
                showAlertAndRedirect('การเพิ่มหนังสือไม่สำเร็จ');
                </script>";
                }

                $stmt->close();

                if (isset($_FILES["uploadfile_self"]) && $_FILES["uploadfile_self"]["error"] == UPLOAD_ERR_OK) {
                    $originalFilename = $_FILES["uploadfile_self"]["name"];
                    $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                    $tempname = $_FILES["uploadfile_self"]["tmp_name"];

                    $allowedExtensions = ['jpg', 'jpeg'];
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        showAlertAndRedirect("ไฟล์รูปภาพต้องเป็นไฟล์ JPG หรือ JPEG เท่านั้น");
                    } elseif ($_FILES["uploadfile_self"]["size"] > 10485760) {
                        showAlertAndRedirect("ขนาดไฟล์รูปภาพต้องไม่เกิน 10 MB");
                    }

                    $newFilename = "book_". $book_id . "." . $fileExtension;
                    $folder = "../books/" . $newFilename;
                    if (!move_uploaded_file($tempname, $folder)) {
                        showAlertAndRedirect("ไม่สามารถอัพโหลดไฟล์ได้");
                    }

                    $stmt = $conn->prepare("UPDATE books SET Book_Picture = ? WHERE Book_ID = ?");
                    $stmt->bind_param("si", $newFilename, $book_id);

                    if ($stmt->execute()) {
                        echo "<script>
                        alert('บันทึกหนังสือเรียบร้อยแล้ว');
                        window.location.href = 'Request_Details_2.php?request_id=" . $request_id . "';
                    </script>";
                    
                    } else {
                        echo "<script>
                    console.log('เกิดข้อผิดพลาดในการอัปเดตรูปภาพ: " . addslashes($stmt->error) . "');
                    showAlertAndRedirect('การเพิ่มรูปภาพไม่สำเร็จ');
                    </script>";
                    }

                    $stmt->close();
                } else {
                    echo "<script>
                alert('บันทึกหนังสือเรียบร้อยเเล้วแต่ยังไม่ได้อัปโหลดรูปภาพ');
                window.location.href = 'Request_Details_2.php?request_id=" . $request_id . "';
                </script>";
                }
            }
        }
    }
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
    <link rel="stylesheet" href="style/styleAddBookPage.css">
</head>

<body>
    <div class="addBook center_content">
        <h3>เลือกรูปแบบการเพิ่มหนังสือ</h3>

        <!--ดึงข้อมูลจากurl-->
        <form id="myForm" method="POST" enctype="multipart/form-data">
            <input type="radio" id="manual-entry" name="method" value="manual" onclick="toggleInputMethod()"
                <?php echo ($selected_method === 'manual') ? 'checked' : ''; ?>>
            <label for="manual-entry">กรอกรายละเอียดด้วยตัวเอง</label>

            <input type="radio" id="search-url" name="method" value="url" onclick="toggleInputMethod()"
                <?php echo ($selected_method === 'url') ? 'checked' : ''; ?>>
            <label for="search-url">ค้นหาหนังสือด้วย URL</label>

            <div class="SearchBar" id="SearchBar" style="display: <?php echo ($selected_method === 'url') ? 'block' : 'none'; ?>;">
                <div class="dropdown_container">
                    <select id="site" name="site" class="book_source_dropdown">
                        <option value="" disabled selected hidden>เลือกแหล่งที่มาหนังสือ</option>
                        <option value="naiin" <?php echo (isset($_POST['site']) && $_POST['site'] === 'naiin') ? 'selected' : ''; ?>>นายอินทร์</option>
                        <option value="se-ed" <?php echo (isset($_POST['site']) && $_POST['site'] === 'se-ed') ? 'selected' : ''; ?>>SE-ED</option>
                        <option value="nlt" <?php echo (isset($_POST['site']) && $_POST['site'] === 'nlt') ? 'selected' : ''; ?>>หอสมุดแห่งชาติ</option>
                    </select>
                </div>
                <input type="text" id="url_input" name="url" placeholder="กรอก URL หนังสือที่นี่..." value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : ''; ?>">
                <button type="submit" class="submit_button" name="action" value="search" id="search-btn">ค้นหา</button>
            </div>

            <!--แสดงที่ดึงข้อมูลจากurl-->
            <div id="SearchResults" style="display: none;">
                <div class="self_input_container">
                    <div class="form_col">
                        <div class="image_upload_group">
                            <label for="uploadfile_search" id="label_search" class="image-upload-button">
                                <img src="asset/image_button.png" alt="Upload Icon">
                                <input type="file" id="uploadfile_search" name="uploadfile_search" accept=".jpg, .jpeg" style="display: none;">
                            </label>
                            <img id="preview_search" src="#" alt="Image Preview" style="display: none; max-height: 380px;">
                        </div>
                        <div class="note">*รองรับไฟล์ JPG ที่มีขนาดไม่เกิน 10 MB</div>
                    </div>
                    <div class="form_col">
                        <div class="link-url">
                            <div class="input_group">
                                <label for="book_name">ชื่อหนังสือ:</label>
                                <input type="text" id="book_name" name="book_name" value="<?php echo htmlspecialchars($book_name); ?>">
                                <small class="helper-text">ชื่อหนังสือสามารถมีตัวอักษร, ตัวเลข, และอักขระพิเศษ . - () , : เท่านั้น</small>
                            </div>
                            <div class="input_group">
                                <label for="author">ผู้แต่ง:</label>
                                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>">
                                <small class="helper-text">ชื่อผู้แต่งสามารถมีตัวอักษร, ตัวเลข, และอักขระพิเศษ . - () , เท่านั้น(กรอกภาษาไทยหากมี)</small>
                            </div>
                            <div class="input_group">
                                <label for="publisher">สำนักพิมพ์:</label>
                                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($publisher); ?>">
                                <small class="helper-text">สำนักพิมพ์สามารถมีตัวอักษร, ตัวเลข, และอักขระพิเศษ . - () , เท่านั้น(กรอกภาษาไทยหากมี)</small>
                            </div>
                            <div class="input_group">
                                <label for="printed">พิมพ์ครั้งที่:</label>
                                <input type="text" id="printed" name="printed" value="<?php echo htmlspecialchars($printed); ?>">
                                <small class="helper-text">พิมพ์ครั้งที่ต้องประกอบตัวเลขเท่านั้น และไม่เท่ากับ0</small>
                            </div>
                            <div class="input_group">
                                <label for="category">หมวดหมู่:</label>
                                <select id="category" name="category">
                                    <option value="" disabled selected hidden>กรุณาเลือกหมวดหมู่...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['Category_ID']; ?>" <?php echo isset($_POST['category']) && $_POST['category'] == $cat['Category_ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['Category_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="input_group">
                                <label for="number_of_page">จำนวนหน้า:</label>
                                <input type="text" id="number_of_page" name="number_of_page" value="<?php echo htmlspecialchars($number_of_page); ?>">
                                <small class="helper-text">จำนวนหน้าต้องประกอบตัวเลขเท่านั้น และไม่เท่ากับ0</small>
                            </div>
                            <div class="input_group">
                                <label for="isbn">ISBN:</label>
                                <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
                                <small class="helper-text">ISBN ต้องประกอบตัวเลข 10 หรือ 13 หลักเท่านั้น และไม่เท่ากับ0</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="submit_button_container">
                    <button type="submit" class="submit_button" name="action" value="savesearch" id="savesearch">บันทึก</button>
                </div>
            </div>
        </form>
        <!--กรอกด้วยตัวเอง-->
        <form id="selfForm" method="POST" enctype="multipart/form-data">
            <div id="SelfInputForm" style="display: <?php echo ($selected_method === 'manual') ? 'block' : 'none'; ?>;">
                <div class="self_input_container">
                    <div class="form_col">
                        <div class="image_upload_group">
                            <label for="uploadfile_self" id="label_self" class="image-upload-button">
                                <img src="asset/image_button.png" alt="Upload Icon">
                                <input type="file" id="uploadfile_self" name="uploadfile_self" accept=".jpg, .jpeg" style="display: none;">
                            </label>
                            <img id="preview_self" src="#" alt="Image Preview" style="display: none; max-height: 380px;">
                        </div>
                        <div class="note">*รองรับไฟล์ JPG ที่มีขนาดไม่เกิน 10 MB</div>
                    </div>

                    <div class="form_col">
                        <div class="link-url">
                            <div class="input_group">
                                <label for="book_name">ชื่อหนังสือ:</label>
                                <input type="text" id="book_name" name="book_name" value="<?php echo $book_name; ?>" placeholder="กรอกชื่อหนังสือ...">
                                <small class="helper-text">ชื่อหนังสือสามารถมีตัวอักษร, ตัวเลข, และอักขระพิเศษ . - () , : เท่านั้น</small>
                            </div>
                            <div class="input_group">
                                <label for="author">ผู้แต่ง:</label>
                                <input type="text" id="author" name="author" value="<?php echo $author; ?>" placeholder="กรอกชื่อผู้แต่ง...">
                                <small class="helper-text">ชื่อผู้แต่งสามารถมีตัวอักษร, ตัวเลข, และอักขระพิเศษ . - () , เท่านั้น(กรอกภาษาไทยหากมี)</small>
                            </div>
                            <div class="input_group">
                                <label for="publisher">สำนักพิมพ์:</label>
                                <input type="text" id="publisher" name="publisher" placeholder="กรอกชื่อสำนักพิมพ์..." value="<?php echo isset($_POST['publisher']) ? htmlspecialchars($_POST['publisher']) : ''; ?>">
                                <small class="helper-text">สำนักพิมพ์สามารถมีตัวอักษร, ตัวเลข, และอักขระพิเศษ . - () , เท่านั้น(กรอกภาษาไทยหากมี)</small>
                            </div>
                            <div class="input_group">
                                <label for="printed">พิมพ์ครั้งที่:</label>
                                <input type="text" id="printed" name="printed" value="<?php echo $printed; ?>" placeholder="กรอกครั้งที่พิมพ์">
                                <small class="helper-text">พิมพ์ครั้งที่ต้องประกอบตัวเลขเท่านั้น และไม่เท่ากับ0</small>
                            </div>
                            <div class="input_group">
                                <label for="category">หมวดหมู่:</label>
                                <select id="category" name="category">
                                    <option value="" disabled selected hidden>กรุณาเลือกหมวดหมู่...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['Category_ID']; ?>" <?php echo isset($_POST['category']) && $_POST['category'] == $cat['Category_ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['Category_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="input_group">
                                <label for="number_of_page">จำนวนหน้า:</label>
                                <input type="text" id="number_of_page" value="<?php echo $number_of_page; ?>" name="number_of_page" placeholder="กรอกจำนวนหน้า...">
                                <small class="helper-text">จำนวนหน้าต้องประกอบตัวเลขเท่านั้น และไม่เท่ากับ0</small>
                            </div>
                            <div class="input_group">
                                <label for="isbn">ISBN:</label>
                                <input type="text" id="isbn" name="isbn" placeholder="กรอกรหัส ISBN..." value="<?php echo $isbn; ?>">
                                <small class="helper-text">ISBN ต้องประกอบตัวเลข 10 หรือ 13  หลักเท่านั้น และไม่เท่ากับ0</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="submit_button_container">
                    <button type="submit" class="submit_button" name="action" value="saveself" id="saveself">บันทึก</button>
                </div>
            </div>
        </form>

    </div>
    <script>
        function toggleInputMethod() {
            var searchBar = document.getElementById('SearchBar');
            var selfInputForm = document.getElementById('SelfInputForm');
            var searchResults = document.getElementById('SearchResults');
            var selectedMethod = localStorage.getItem('selectedMethod') || document.querySelector('input[name="method"]:checked').value;

            console.log('Method ที่เลือกคือ: ' + selectedMethod);

            localStorage.setItem('selectedMethod', selectedMethod);

            if (selectedMethod === 'url') {
                searchBar.style.display = 'block';
                selfInputForm.style.display = 'none';
                searchResults.style.display = 'none';
            } else if (selectedMethod === 'manual') {
                searchBar.style.display = 'none';
                selfInputForm.style.display = 'block';
                searchResults.style.display = 'none';
            }
        }

        document.querySelectorAll('input[name="method"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                localStorage.setItem('selectedMethod', this.value);
                toggleInputMethod();
            });
        });
        //แสดงsearch result
        if (<?php echo !empty($search_results) ? 'true' : 'false'; ?>) {
            document.getElementById('SearchResults').style.display = 'block';
        }

        document.addEventListener('DOMContentLoaded', function() {
            function handleImageUpload(uploadFileInputId, previewId, uploadButtonLabelId) {
                const uploadFileInput = document.getElementById(uploadFileInputId);
                const preview = document.getElementById(previewId);
                const uploadButtonLabel = document.getElementById(uploadButtonLabelId);

                uploadFileInput.addEventListener('change', function() {
                    const file = uploadFileInput.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            uploadButtonLabel.style.display = 'none';
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                        uploadButtonLabel.style.display = 'block';
                    }
                });

                preview.addEventListener('click', function(e) {
                    e.preventDefault();

                    preview.src = '';
                    preview.style.display = 'none';
                    uploadButtonLabel.style.display = 'block';
                    uploadFileInput.value = '';
                });
            }

            handleImageUpload('uploadfile_self', 'preview_self', 'label_self');
            handleImageUpload('uploadfile_search', 'preview_search', 'label_search');

        });

        const inputs = document.querySelectorAll('.input_group input[type="text"]');
        const helperTexts = document.querySelectorAll('.helper-text');

        inputs.forEach((input, index) => {
            input.addEventListener('focus', () => {
                helperTexts[index].style.display = 'block';
            });
            input.addEventListener('blur', () => {
                helperTexts[index].style.display = 'none';
            });
        });
    </script>

</body>

</html>