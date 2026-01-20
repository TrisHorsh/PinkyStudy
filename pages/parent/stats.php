<?php
// pages/parent/stats.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_GET['student_id'] ?? 0;
$parent_id = $_SESSION['user_id'];

// 1. Lấy thông tin bé
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = :id AND parent_id = :pid");
$stmt->execute([':id' => $student_id, ':pid' => $parent_id]);
$student = $stmt->fetch();

if (!$student) die("Học sinh không tồn tại.");

// 2. CHẠY LOGIC TỰ ĐỘNG CẬP NHẬT TRẠNG THÁI 'FAILED'
markOverdueTasksAsFailed($conn, $student_id);

// 3. Xử lý bộ lọc thời gian (Mặc định: 7 ngày gần nhất)
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-6 days'));

// 4. LẤY DỮ LIỆU BIỂU ĐỒ (Số sao kiếm được theo ngày - Dựa vào ngày duyệt bài approved)
$sqlChart = "SELECT DATE(completed_at) as date_val, SUM(points_reward) as total_points 
             FROM assigned_tasks 
             WHERE student_id = :sid 
             AND status = 'approved' 
             AND DATE(completed_at) BETWEEN :from AND :to 
             GROUP BY DATE(completed_at) 
             ORDER BY date_val ASC";
$stmtChart = $conn->prepare($sqlChart);
$stmtChart->execute([':sid' => $student_id, ':from' => $from_date, ':to' => $to_date]);
$chartDataRaw = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);

// Chuẩn hóa dữ liệu biểu đồ
$labels = [];
$dataPoints = [];
$current = strtotime($from_date);
$end = strtotime($to_date);

while ($current <= $end) {
    $d = date('Y-m-d', $current);
    $labels[] = date('d/m', $current); 
    $dataPoints[] = $chartDataRaw[$d] ?? 0; 
    $current = strtotime('+1 day', $current);
}

// 5. LẤY DỮ LIỆU DANH SÁCH CHI TIẾT
$sqlList = "SELECT DATE(created_at) as create_date, t.* FROM assigned_tasks t
            WHERE student_id = :sid 
            AND DATE(created_at) BETWEEN :from AND :to 
            ORDER BY created_at DESC";
            
$stmtList = $conn->prepare($sqlList);
$stmtList->execute([':sid' => $student_id, ':from' => $from_date, ':to' => $to_date]);
$taskList = $stmtList->fetchAll(PDO::FETCH_GROUP); // Nhóm theo ngày

include '../../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="manage-container">
    
    <div class="filter-bar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="manage_student.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <h3 style="margin: 0; color: #343a40;">
                📊 Báo cáo: <span style="color: #007bff;"><?php echo htmlspecialchars($student['full_name']); ?></span>
            </h3>
        </div>

        <form method="GET" class="filter-form">
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            
            <div style="font-size: 0.9em; font-weight: bold;">Từ:</div>
            <input type="date" name="from_date" value="<?php echo $from_date; ?>" class="form-control" style="width: auto; padding: 5px;">
            
            <div style="font-size: 0.9em; font-weight: bold;">Đến:</div>
            <input type="date" name="to_date" value="<?php echo $to_date; ?>" class="form-control" style="width: auto; padding: 5px;">
            
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter"></i> Lọc
            </button>
        </form>
    </div>

    <div class="report-layout">
        
        <div class="chart-panel">
            <h4 style="text-align: center; margin-top: 0; color: #495057; margin-bottom: 20px;">
                Tiến độ tích lũy Sao (<?php echo date('d/m', strtotime($from_date)) . ' - ' . date('d/m', strtotime($to_date)); ?>)
            </h4>
            
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="starsChart"></canvas>
            </div>
            
            <div style="text-align: center; margin-top: 20px; font-size: 0.9em; color: #6c757d; font-style: italic;">
                * Biểu đồ hiển thị số điểm thực nhận sau khi phụ huynh duyệt bài.
            </div>
        </div>

        <div class="list-panel">
            <h4 style="margin-top: 0; color: #495057; margin-bottom: 15px;">Chi tiết nhiệm vụ</h4>
            
            <?php 
            // Duyệt ngược từ ngày kết thúc về ngày bắt đầu
            $curr = strtotime($to_date);
            $start = strtotime($from_date);
            $hasData = false;

            while ($curr >= $start) {
                $dateStr = date('Y-m-d', $curr);
                $tasks = $taskList[$dateStr] ?? [];
                
                // Chỉ hiện những ngày CÓ nhiệm vụ để danh sách đỡ dài
                if (count($tasks) > 0): 
                    $hasData = true;
            ?>
                <div class="day-report-card">
                    <div class="day-header">
                        <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', $curr); ?></span>
                        <span class="badge bg-blue"><?php echo count($tasks); ?> task</span>
                    </div>
                    <div class="day-body">
                        <?php foreach ($tasks as $t): ?>
                            <div class="task-row-item">
                                <div>
                                    <?php if ($t['status'] == 'approved'): ?>
                                        <span class="status-dot dot-success" title="Hoàn thành"></span>
                                        <span style="text-decoration: line-through; opacity: 0.7;"><?php echo htmlspecialchars($t['title']); ?></span>
                                    <?php elseif ($t['status'] == 'failed'): ?>
                                        <span class="status-dot dot-failed" title="Thất bại"></span>
                                        <span style="color: #dc3545;"><?php echo htmlspecialchars($t['title']); ?></span>
                                    <?php else: ?>
                                        <span class="status-dot dot-pending" title="Chờ xử lý"></span>
                                        <span><?php echo htmlspecialchars($t['title']); ?></span>
                                    <?php endif; ?>

                                    <?php if($t['task_type'] == 'daily'): ?>
                                        <span class="task-type-badge type-daily">Hàng ngày</span>
                                    <?php else: ?>
                                        <span class="task-type-badge type-normal">Thử thách</span>
                                    <?php endif; ?>
                                </div>

                                <div style="font-weight: bold;">
                                    <?php if ($t['status'] == 'approved'): ?>
                                        <span style="color: #28a745;">+<?php echo $t['points_reward']; ?> ⭐</span>
                                    <?php elseif ($t['status'] == 'failed'): ?>
                                        <span style="color: #dc3545;">0 ⭐</span>
                                    <?php else: ?>
                                        <span style="color: #ffc107;">Wait</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php 
                endif; // End check count tasks
                $curr = strtotime('-1 day', $curr);
            }
            
            if (!$hasData) {
                echo '<div style="text-align:center; color:#999; padding:20px; background:white; border-radius:10px;">Không có dữ liệu trong khoảng thời gian này.</div>';
            }
            ?>
        </div>

    </div>
</div>

<script>
    // Cấu hình biểu đồ Chart.js
    const ctx = document.getElementById('starsChart').getContext('2d');
    const starsChart = new Chart(ctx, {
        type: 'bar', // Dạng cột
        data: {
            labels: <?php echo json_encode($labels); ?>, // Trục X (Ngày)
            datasets: [{
                label: 'Số sao đạt được',
                data: <?php echo json_encode($dataPoints); ?>, // Trục Y (Điểm)
                backgroundColor: 'rgba(54, 162, 235, 0.7)', // Màu cột (Xanh dương)
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 4, // Bo tròn góc cột
                barPercentage: 0.6 // Độ rộng cột
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Để chart co giãn theo div cha
            plugins: {
                legend: { display: false } // Ẩn chú thích vì chỉ có 1 loại dữ liệu
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 } // Chỉ hiện số nguyên
                },
                x: {
                    grid: { display: false } // Ẩn lưới dọc cho đẹp
                }
            }
        }
    });
</script>

</body>
</html>