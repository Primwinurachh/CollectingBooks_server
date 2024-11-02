<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once dirname(__FILE__) . "/connect.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('Location: LoginPage.php');
    exit;
}

$startMonth = isset($_POST['month-start']) && !empty($_POST['month-start']) ? $_POST['month-start'] : date('Y-m', strtotime("-11 months"));
$endMonth = isset($_POST['month-end']) && !empty($_POST['month-end']) ? $_POST['month-end'] : date('Y-m');

$query = "SELECT DATE_FORMAT(Datetime_Register, '%Y-%m') AS month, COUNT(User_ID) AS users
        FROM users
        WHERE Datetime_Register BETWEEN '$startMonth-01' AND LAST_DAY('$endMonth-01')
        GROUP BY month
        ORDER BY month";

$result = $conn->query($query);

$data = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = DateTime::createFromFormat('Y-m', $row['month']);
        $monthNames = [
            'Jan' => 'ม.ค.',
            'Feb' => 'ก.พ.',
            'Mar' => 'มี.ค.',
            'Apr' => 'เม.ย.',
            'May' => 'พ.ค.',
            'Jun' => 'มิ.ย.',
            'Jul' => 'ก.ค.',
            'Aug' => 'ส.ค.',
            'Sep' => 'ก.ย.',
            'Oct' => 'ต.ค.',
            'Nov' => 'พ.ย.',
            'Dec' => 'ธ.ค.'
        ];

        $thaiYear = (int)$date->format('Y') + 543;
        $formattedYear = substr($thaiYear, -2);

        $formattedMonth = $monthNames[$date->format('M')] . ' ' . $formattedYear;

        $data[] = [
            'month' => $formattedMonth,
            'users' => $row['users']
        ];
    }
} else {
    $data = null;
}

$conn->close();
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
    <link rel="stylesheet" href="style/styleStatisticUserPage.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include('nav.php'); ?>
    <header class="header">
        <p>จำนวนการสมัครสมาชิก</p>
    </header>

    <form method="POST">
        <div class="month-years">
            <input type="month" id="month-start" name="month-start" class="month-input"
                max="<?php echo date('Y-m'); ?>"
                value="<?php echo $startMonth; ?>">
            <div class="label">ถึง</div>
            <input type="month" id="month-end" name="month-end" class="month-input" readonly
                max="<?php echo date('Y-m'); ?>"
                value="<?php echo $endMonth; ?>">
            <button type="submit">แสดงผล</button>
        </div>
    </form>


    <?php if ($data): ?>
        <div class="chart-container">
            <canvas id="myChart"></canvas>
        </div>
        <script>
            var chartData = <?php echo json_encode($data); ?>;

            var labels = [];
            var data = [];

            chartData.forEach(function(item) {
                labels.push(item.month);
                data.push(item.users);
            });

            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'จำนวนการสมัครสมาชิกในแต่ละเดือน',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        hoverBackgroundColor: 'rgba(255, 99, 132, 0.6)',
                        hoverBorderColor: 'rgba(255, 99, 132, 1)',
                        borderRadius: 5
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    family: 'Anuphan',
                                    size: 16,
                                    style: 'normal',
                                    lineHeight: 1.2
                                },
                                color: '#333'
                            }
                        },
                        tooltip: {
                            titleFont: {
                                family: 'Anuphan',
                                size: 14
                            },
                            bodyFont: {
                                family: 'Anuphan',
                                size: 12
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'เดือน',
                                color: '#333',
                                font: {
                                    family: 'Anuphan',
                                    size: 14
                                }
                            },
                            ticks: {
                                font: {
                                    family: 'Anuphan',
                                    size: 12
                                },
                                color: '#333'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'จำนวนการสมัครสมาชิก (คน)',
                                color: '#333',
                                font: {
                                    family: 'Anuphan',
                                    size: 14
                                }
                            },
                            ticks: {
                                font: {
                                    family: 'Anuphan',
                                    size: 12
                                },
                                beginAtZero: true,
                                stepSize: 10,
                                color: '#333'
                            }
                        }
                    }
                }
            });
        </script>
    <?php else: ?>
        <p style="text-align: center;">ไม่พบข้อมูล</p>
    <?php endif; ?>

    <script>
        document.getElementById('month-start').addEventListener('change', function() {
            var startMonth = this.value;
            if (startMonth) {
                var startDate = new Date(startMonth + '-01');
                var endDate = new Date(startDate.setMonth(startDate.getMonth() + 11));

                var currentDate = new Date();
                if (endDate > currentDate) {
                    endDate = currentDate;
                }

                var year = endDate.getFullYear();
                var month = (endDate.getMonth() + 1).toString().padStart(2, '0');
                var endMonth = year + '-' + month;

                document.getElementById('month-end').value = endMonth;
            } else {
                document.getElementById('month-end').value = '';
            }
        });
    </script>
</body>

</html>