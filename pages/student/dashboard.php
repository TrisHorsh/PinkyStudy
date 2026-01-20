<?php
// pages/student/dashboard.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Chứa hàm lấy TKB
session_start();

// Bảo mật: Chỉ học sinh được vào
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_SESSION['user_id'];

// HẠY LOGIC TỰ ĐỘNG CẬP NHẬT TRẠNG THÁI 'FAILED'
markOverdueTasksAsFailed($conn, $student_id);

// 1. Cập nhật điểm số
$stmtUser = $conn->prepare("SELECT current_points, full_name FROM users WHERE id = :id");
$stmtUser->execute([':id' => $student_id]);
$user = $stmtUser->fetch();
$_SESSION['current_points'] = $user['current_points'];

// 2. Lấy Thời khóa biểu
$timetable = getTimetableData($conn, $student_id);

// 3. Lấy Nhiệm vụ (Tách làm 2 nhóm)
// Nhóm 1: Nhiệm vụ Hàng ngày (Daily) - Chưa hoàn thành hoặc Đang chờ chấm
$sqlDaily = "SELECT * FROM assigned_tasks 
             WHERE student_id = :sid AND task_type = 'daily' 
             AND status != 'approved' 
             ORDER BY created_at DESC";
$stmtDaily = $conn->prepare($sqlDaily);
$stmtDaily->execute([':sid' => $student_id]);
$tasks_daily = $stmtDaily->fetchAll();

// Nhóm 2: Nhiệm vụ Thử thách/Thường (Challenge/Normal) - Chưa hoàn thành hoặc Đang chờ chấm
$sqlChallenge = "SELECT * FROM assigned_tasks 
                 WHERE student_id = :sid AND task_type != 'daily' 
                 AND status != 'approved' 
                 ORDER BY created_at DESC";
$stmtChallenge = $conn->prepare($sqlChallenge);
$stmtChallenge->execute([':sid' => $student_id]);
$tasks_challenge = $stmtChallenge->fetchAll();

// 4. [MỚI] Lấy LỊCH SỬ BÀI ĐÃ CHẤM (Approved) - Lấy 10 bài gần nhất
$sqlHistory = "SELECT * FROM assigned_tasks 
               WHERE student_id = :sid 
               AND status = 'approved' 
               ORDER BY completed_at DESC LIMIT 10";
$stmtHistory = $conn->prepare($sqlHistory);
$stmtHistory->execute([':sid' => $student_id]);
$history_tasks = $stmtHistory->fetchAll();

// 5. Lấy danh sách quà đã Approved (chưa dùng)
    $sqlVouchers = "SELECT r.*, g.gift_name, g.gift_image 
                    FROM redemptions r 
                    JOIN gifts g ON r.gift_id = g.id 
                    WHERE r.student_id = :sid AND r.status = 'approved' 
                    ORDER BY r.redemption_date DESC";
    $stmtV = $conn->prepare($sqlVouchers);
    $stmtV->execute([':sid' => $student_id]);
    $vouchers = $stmtV->fetchAll();

include '../../includes/header_student.php';
?>

<style>
    /* Tổng thể */
    .dashboard-container { max-width: 1000px; margin: 0 auto; padding-bottom: 50px; }
    
    /* 1. Card thông tin (Header) */
    .welcome-card {
        background: linear-gradient(135deg, #00bcd4, #0097a7);
        color: white;
        padding: 20px;
        border-radius: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        box-shadow: 0 4px 10px rgba(0,188,212,0.3);
    }
    .welcome-text h2 { margin: 0; font-size: 1.5em; }
    .welcome-text p { margin: 5px 0 0 0; opacity: 0.9; }
    .points-box {
        background: rgba(255,255,255,0.2);
        padding: 10px 20px;
        border-radius: 10px;
        text-align: center;
        backdrop-filter: blur(5px);
    }
    .points-num { font-size: 1.8em; font-weight: bold; color: #ffeb3b; display: block; }
    
    /* 2. Thời khóa biểu */
    .section-title { font-size: 1.2em; color: #333; margin-bottom: 15px; border-left: 5px solid #00bcd4; padding-left: 10px; display: flex; align-items: center; justify-content: space-between;}
    .tkb-wrapper { background: white; border-radius: 15px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px; transition: all 0.3s; }
    .tkb-table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
    .tkb-table th, .tkb-table td { border: 1px solid #eee; padding: 8px; text-align: center; }
    .tkb-header { background: #f1f5f9; color: #555; }
    .tkb-session { font-weight: bold; background: #f8fafc; color: #00bcd4; }
    
    /* 3. Nhiệm vụ (2 Cột) */
    .tasks-grid { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 25px; }
    .task-col { flex: 1; min-width: 300px; }
    .task-col-header { 
        background: #fff; padding: 10px 15px; border-radius: 10px 10px 0 0; 
        font-weight: bold; text-transform: uppercase; color: #555; border-bottom: 2px solid #eee; 
    }
    .daily-header { border-bottom-color: #4caf50; color: #2e7d32; }
    .challenge-header { border-bottom-color: #ff9800; color: #ef6c00; }
    
    .task-list { background: white; border-radius: 0 0 10px 10px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); min-height: 200px; }
    
    .task-item { 
        display: block; text-decoration: none; color: inherit; /* Biến thẻ a thành block */
        border: 1px solid #eee; border-radius: 8px; padding: 12px; margin-bottom: 10px; 
        transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden;
    }
    .task-item:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-color: #b2ebf2; }
    
    .task-status { position: absolute; top: 0; right: 0; padding: 3px 8px; font-size: 0.7em; border-radius: 0 0 0 8px; font-weight: bold; }
    .status-pending { background: #e0f7fa; color: #0097a7; }
    .status-submitted { background: #e8f5e9; color: #2e7d32; }
    .status-rejected { background: #ffebee; color: #c62828; }

    /* 4. Lịch sử (Mới) */
    .history-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px; }
    .history-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f1f1; }
    .history-item:last-child { border-bottom: none; }
    .history-score { font-weight: bold; color: #2e7d32; font-size: 1.1em; background: #e8f5e9; padding: 5px 10px; border-radius: 20px; }

    /* 5. Cửa hàng (Footer Action) */
    .shop-promo { 
        background: linear-gradient(45deg, #ff9800, #ff5722); 
        color: white; padding: 20px; border-radius: 15px; text-align: center; 
        box-shadow: 0 4px 10px rgba(255,87,34,0.3); cursor: pointer; transition: transform 0.2s;
    }
    .shop-promo:hover { transform: scale(1.02); }
</style>

<div class="dashboard-container">

    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Chào bạn, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h2>
            <p>Chúc bạn một ngày học tập thật hiệu quả.</p>
        </div>
        <div class="points-box">
            <span>Tài khoản hiện có</span>
            <span class="points-num"><?php echo $user['current_points']; ?> ⭐</span>
        </div>
    </div>

    <div class="section-title">
        <span>📅 Thời khóa biểu</span>
        <button id="tkbToggle" onclick="toggleTKB()" class="btn" style="background: #e0f7fa; color: #006064; font-size: 0.8em;">Thu gọn ▲</button>
    </div>
    
    <div id="tkbContent" class="tkb-wrapper">
        <div style="overflow-x: auto;">
            <table class="tkb-table">
                <thead>
                    <tr class="tkb-header">
                        <th style="background: #fff;"></th>
                        <th>T2</th><th>T3</th><th>T4</th><th>T5</th><th>T6</th><th>T7</th><th>CN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sessions = ['morning' => 'Sáng', 'afternoon' => 'Chiều', 'evening' => 'Tối'];
                    foreach ($sessions as $key => $label): 
                    ?>
                    <tr>
                        <td class="tkb-session"><?php echo $label; ?></td>
                        <?php for($d=2; $d<=8; $d++): ?>
                            <td>
                                <?php if (!empty($timetable[$key][$d])): ?>
                                    <?php foreach ($timetable[$key][$d] as $subj): ?>
                                        <div style="margin-bottom: 4px; font-weight: 500;"><?php echo htmlspecialchars($subj['name']); ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tasks-grid">
        
        <div class="task-col">
            <div class="task-col-header daily-header">🌱 Nhiệm vụ Hàng ngày</div>
            <div class="task-list">
                <?php if(count($tasks_daily) > 0): ?>
                    <?php foreach($tasks_daily as $task): ?>
                        <a href="do_task.php?task_id=<?php echo $task['id']; ?>" class="task-item">
                            
                            <?php if($task['status'] == 'submitted'): ?>
                                <span class="task-status status-submitted">Đang chấm...</span>
                            <?php elseif($task['status'] == 'rejected'): ?>
                                <span class="task-status status-rejected">Làm lại</span>
                            <?php else: ?>
                                <span class="task-status status-pending">Cần làm</span>
                            <?php endif; ?>

                            <div style="font-weight: bold; margin-bottom: 5px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size: 0.85em; color: #666;"><?php echo htmlspecialchars($task['description']); ?></div>
                            <div style="margin-top: 8px; font-weight: bold; color: #2e7d32;">+<?php echo $task['points_reward']; ?> sao</div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; margin-top: 20px;">Bạn đã hoàn thành hết việc hôm nay! ✅</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="task-col">
            <div class="task-col-header challenge-header">🔥 Nhiệm vụ Thử thách</div>
            <div class="task-list">
                <?php if(count($tasks_challenge) > 0): ?>
                    <?php foreach($tasks_challenge as $task): ?>
                        <a href="do_task.php?task_id=<?php echo $task['id']; ?>" class="task-item" style="border-left: 3px solid #ff9800;">
                            
                            <?php if($task['status'] == 'submitted'): ?>
                                <span class="task-status status-submitted">Đang chấm...</span>
                            <?php elseif($task['status'] == 'rejected'): ?>
                                <span class="task-status status-rejected">Làm lại</span>
                            <?php else: ?>
                                <span class="task-status status-pending">Thử thách</span>
                            <?php endif; ?>

                            <div style="font-weight: bold; margin-bottom: 5px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size: 0.85em; color: #666;"><?php echo htmlspecialchars($task['description']); ?></div>
                            <div style="margin-top: 8px; font-weight: bold; color: #ef6c00;">+<?php echo $task['points_reward']; ?> sao</div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; margin-top: 20px;">Chưa có thử thách nào mới.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="section-title">
        <span>📜 Lịch sử bài đã chấm (Gần đây)</span>
    </div>
    <div class="history-card">
        <?php if(count($history_tasks) > 0): ?>
            <?php foreach($history_tasks as $ht): ?>
            <div class="history-item">
                <div>
                    <div style="font-weight: bold; color: #333;"><?php echo htmlspecialchars($ht['title']); ?></div>
                    <div style="font-size: 0.85em; color: #888;">
                        Hoàn thành: <?php echo date('H:i - d/m/Y', strtotime($ht['completed_at'])); ?>
                    </div>
                </div>
                <div class="history-score">
                    +<?php echo $ht['points_reward']; ?> ⭐
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #999;">Bạn chưa có bài nào được chấm điểm.</p>
        <?php endif; ?>
    </div>

    <div class="shop-promo" onclick="window.location.href='shop.php'">
        <h3 style="margin: 0 0 5px 0;">🎁 Cửa hàng quà tặng</h3>
        <p style="margin: 0; font-size: 0.9em;">Bạn đang có <b><?php echo $user['current_points']; ?> sao</b>. Bấm vào đây để xem quà nhé!</p>
    </div>

    <?php if (count($vouchers) > 0): ?>
    <div class="section-title">
        <span>🎟️ Kho Voucher của bạn (Đưa bố mẹ quét nhé)</span>
    </div>
    <div style="display: flex; overflow-x: auto; gap: 20px; padding-bottom: 20px;">
        <?php foreach ($vouchers as $v): ?>
            <?php 
                // Tạo link xác thực (Lưu ý: localhost chỉ chạy được trên cùng máy)
                // Để chạy thật, bạn cần thay 'localhost' bằng IP máy tính (VD: 192.168.1.5)
                $verifyLink = "http://localhost/PinkyStudy/pages/parent/verify_gift.php?code=" . $v['voucher_code'];
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verifyLink);
            ?>
            <div style="background: white; min-width: 280px; padding: 20px; border-radius: 15px; text-align: center; border: 2px dashed #ff9800; position: relative;">
                <h4 style="margin: 0 0 10px 0; color: #e65100;"><?php echo htmlspecialchars($v['gift_name']); ?></h4>
                
                <a href="<?php echo $verifyLink; ?>" target="_blank" title="Click để giả lập quét mã">
                    <img src="<?php echo $qrUrl; ?>" alt="QR Code" style="border-radius: 8px;">
                </a>
                
                <div style="font-family: monospace; font-size: 1.5em; font-weight: bold; margin: 10px 0; color: #333;">
                    <?php echo $v['voucher_code']; ?>
                </div>
                <small style="color: #666;">Đưa mã này cho bố mẹ</small>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
    function toggleTKB() {
        const content = document.getElementById('tkbContent');
        const btn = document.getElementById('tkbToggle');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            btn.innerHTML = 'Thu gọn ▲';
        } else {
            content.style.display = 'none';
            btn.innerHTML = 'Mở rộng ▼';
        }
    }
</script>

</body>
</html>