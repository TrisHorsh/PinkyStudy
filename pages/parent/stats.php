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
// Chỉ tính những bài đã approved
$sqlChart = "SELECT DATE(completed_at) as date_val, SUM(points_reward) as total_points 
             FROM assigned_tasks 
             WHERE student_id = :sid 
             AND status = 'approved' 
             AND DATE(completed_at) BETWEEN :from AND :to 
             GROUP BY DATE(completed_at) 
             ORDER BY date_val ASC";
$stmtChart = $conn->prepare($sqlChart);
$stmtChart->execute([':sid' => $student_id, ':from' => $from_date, ':to' => $to_date]);
$chartDataRaw = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR); // Ra dạng ['2023-10-01' => 15, '2023-10-02' => 20]

// Chuẩn hóa dữ liệu biểu đồ (Điền 0 cho những ngày không có dữ liệu)
$labels = [];
$dataPoints = [];
$current = strtotime($from_date);
$end = strtotime($to_date);

while ($current <= $end) {
    $d = date('Y-m-d', $current);
    $labels[] = date('d/m', $current); // Label trục hoành (VD: 25/10)
    $dataPoints[] = $chartDataRaw[$d] ?? 0; // Nếu không có thì là 0 sao
    $current = strtotime('+1 day', $current);
}

// 5. LẤY DỮ LIỆU DANH SÁCH (Nhiệm vụ theo ngày - Dựa vào ngày giao created_at)
// Lấy tất cả trạng thái để báo cáo
$sqlList = "SELECT DATE(created_at) as create_date, t.* FROM assigned_tasks t
            WHERE student_id = :sid 
            AND DATE(created_at) BETWEEN :from AND :to 
            ORDER BY created_at DESC";
            
$stmtList = $conn->prepare($sqlList);
$stmtList->execute([':sid' => $student_id, ':from' => $from_date, ':to' => $to_date]);
$taskList = $stmtList->fetchAll(PDO::FETCH_GROUP); // Nhóm theo create_date: ['2023-10-25' => [task1, task2]]

include '../../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .stats-container { max-width: 1200px; margin: 0 auto; padding-bottom: 50px; }
    
    /* Layout 2 cột */
    .report-grid { display: flex; gap: 30px; margin-top: 20px; }
    .chart-col { flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .list-col { flex: 1; }
    
    /* Responsive: Mobile xuống dòng */
    @media (max-width: 768px) { .report-grid { flex-direction: column; } }

    /* Box ngày tháng trong danh sách */
    .day-box { background: white; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
    .day-header { 
        background: #f8f9fa; padding: 10px 15px; font-weight: bold; color: #555; 
        border-bottom: 1px solid #eee; display: flex; justify-content: space-between; 
    }
    .day-body { padding: 10px; max-height: 300px; overflow-y: auto; } /* Scroll nếu dài */

    /* Item nhiệm vụ */
    .task-row { 
        display: flex; justify-content: space-between; align-items: center; 
        padding: 8px 0; border-bottom: 1px dashed #eee; 
    }
    .task-row:last-child { border-bottom: none; }
    
    .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .dot-success { background: #28a745; } /* Đã xong */
    .dot-failed { background: #dc3545; } /* Thất bại */
    .dot-pending { background: #ffc107; } /* Chưa xong */
    
    .task-type-badge { font-size: 0.7em; padding: 2px 5px; border-radius: 4px; margin-left: 5px; text-transform: uppercase; }
    .type-daily { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .type-challenge { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }
</style>

<div class="stats-container">
    <a href="manage_student.php?student_id=<?php echo $student_id; ?>" class="btn" style="background:#6c757d; color:white; margin-bottom:15px;">&larr; Quay lại quản lý</a>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0;">📊 Báo cáo học tập: <?php echo htmlspecialchars($student['full_name']); ?></h2>
        
        <form method="GET" style="background: white; padding: 10px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <label>Từ:</label>
            <input type="date" name="from_date" value="<?php echo $from_date; ?>" style="border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
            <label>Đến:</label>
            <input type="date" name="to_date" value="<?php echo $to_date; ?>" style="border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
            <button type="submit" class="btn btn-primary" style="padding: 6px 15px; font-size: 0.9em;">Lọc</button>
        </form>
    </div>

    <div class="report-grid">
        
        <div class="chart-col">
            <h4 style="text-align: center; margin-top: 0; color: #007bff;">Số sao tích lũy được</h4>
            <canvas id="starsChart"></canvas>
            <div style="text-align: center; margin-top: 20px; font-size: 0.9em; color: #666;">
                <i>Biểu đồ thể hiện số sao bé thực sự nhận được (đã được duyệt) trong khoảng thời gian này.</i>
            </div>
        </div>

        <div class="list-col">
            <h4 style="margin-top: 0; color: #333;">Chi tiết nhiệm vụ theo ngày</h4>
            
            <?php 
            // Vòng lặp từ ngày Đến -> ngày Từ (Đảo ngược để ngày mới nhất lên đầu)
            $curr = strtotime($to_date);
            $start = strtotime($from_date);
            
            while ($curr >= $start) {
                $dateStr = date('Y-m-d', $curr);
                $tasks = $taskList[$dateStr] ?? []; // Lấy task của ngày đó
                
                // Chỉ hiện box nếu ngày đó có task (hoặc bạn có thể hiện box trống nếu muốn báo cáo chặt chẽ hơn)
                // Ở đây tôi chọn hiện cả ngày trống để phụ huynh biết ngày đó không giao bài
                ?>
                <div class="day-box">
                    <div class="day-header">
                        <span>🗓 <?php echo date('d/m/Y', $curr); ?></span>
                        <span style="font-size: 0.9em; font-weight: normal;">
                            (<?php echo count($tasks); ?> nhiệm vụ)
                        </span>
                    </div>
                    <div class="day-body">
                        <?php if (count($tasks) > 0): ?>
                            <?php foreach ($tasks as $t): ?>
                                <div class="task-row">
                                    <div>
                                        <?php if ($t['status'] == 'approved'): ?>
                                            <span class="status-dot dot-success" title="Hoàn thành"></span>
                                            <span style="text-decoration: line-through; color: #888;"><?php echo htmlspecialchars($t['title']); ?></span>
                                        <?php elseif ($t['status'] == 'failed'): ?>
                                            <span class="status-dot dot-failed" title="Thất bại/Hết hạn"></span>
                                            <span style="color: #dc3545;"><?php echo htmlspecialchars($t['title']); ?></span>
                                        <?php else: ?>
                                            <span class="status-dot dot-pending" title="Chờ xử lý"></span>
                                            <span><?php echo htmlspecialchars($t['title']); ?></span>
                                        <?php endif; ?>

                                        <?php if($t['task_type'] == 'daily'): ?>
                                            <span class="task-type-badge type-daily">Hàng ngày</span>
                                        <?php else: ?>
                                            <span class="task-type-badge type-challenge">Thử thách</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="font-weight: bold; font-size: 0.9em;">
                                        <?php if ($t['status'] == 'approved'): ?>
                                            <span style="color: #28a745;">+<?php echo $t['points_reward']; ?> ⭐</span>
                                        <?php elseif ($t['status'] == 'failed'): ?>
                                            <span style="color: #ccc;">0 ⭐</span>
                                        <?php else: ?>
                                            <span style="color: #ffc107;">...</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #ccc; margin: 10px 0;">Không có nhiệm vụ nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $curr = strtotime('-1 day', $curr);
            }
            ?>
        </div>

    </div>
</div>

<script>
    const ctx = document.getElementById('starsChart').getContext('2d');
    const starsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>, // Mảng ngày (trục X)
            datasets: [{
                label: 'Sao đạt được',
                data: <?php echo json_encode($dataPoints); ?>, // Mảng điểm (trục Y)
                backgroundColor: 'rgba(54, 162, 235, 0.6)', // Màu cột xanh
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 } // Chỉ hiện số nguyên
                }
            },
            plugins: {
                legend: { display: false } // Ẩn chú thích (vì chỉ có 1 cột)
            }
        }
    });
</script>

</body>
</html>