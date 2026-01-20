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
$sqlDaily = "SELECT * FROM assigned_tasks 
             WHERE student_id = :sid AND task_type = 'daily' 
             AND status != 'approved' 
             ORDER BY created_at DESC";
$stmtDaily = $conn->prepare($sqlDaily);
$stmtDaily->execute([':sid' => $student_id]);
$tasks_daily = $stmtDaily->fetchAll();

$sqlChallenge = "SELECT * FROM assigned_tasks 
                 WHERE student_id = :sid AND task_type != 'daily' 
                 AND status != 'approved' 
                 ORDER BY created_at DESC";
$stmtChallenge = $conn->prepare($sqlChallenge);
$stmtChallenge->execute([':sid' => $student_id]);
$tasks_challenge = $stmtChallenge->fetchAll();

// 4. Lấy LỊCH SỬ BÀI ĐÃ CHẤM
$sqlHistory = "SELECT * FROM assigned_tasks 
               WHERE student_id = :sid 
               AND status = 'approved' 
               ORDER BY completed_at DESC LIMIT 10";
$stmtHistory = $conn->prepare($sqlHistory);
$stmtHistory->execute([':sid' => $student_id]);
$history_tasks = $stmtHistory->fetchAll();

// 5. Lấy danh sách quà đã Approved
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

<link rel="stylesheet" href="../../assets/css/student_style.css?v=<?php echo time(); ?>">

<div class="dashboard-container">

    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Xin chào, <?php echo htmlspecialchars($user['full_name']); ?>! 🚀</h2>
            <p>Sẵn sàng chinh phục thử thách hôm nay chưa?</p>
        </div>
        <div class="points-box">
            <span style="font-size: 0.9em; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; color: #b2bec3;">Kho báu của bạn</span>
            <span class="points-num"><?php echo $user['current_points']; ?> ⭐</span>
        </div>
    </div>

    <div class="section-title">
        <span>📅 Lịch học trong tuần</span>
        <button id="tkbToggle" onclick="toggleTKB()" class="btn-toggle">Thu gọn ▲</button>
    </div>
    
    <div id="tkbContent" class="tkb-wrapper">
        <div style="overflow-x: auto;">
            <table class="tkb-table" style="width: 100%; border-collapse: separate; border-spacing: 5px;">
                <thead>
                    <tr>
                        <th style="background: transparent;"></th>
                        <th>Thứ 2</th><th>Thứ 3</th><th>Thứ 4</th><th>Thứ 5</th><th>Thứ 6</th><th>Thứ 7</th><th>CN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sessions = ['morning' => 'Sáng ☀️', 'afternoon' => 'Chiều ⛅', 'evening' => 'Tối 🌙'];
                    foreach ($sessions as $key => $label): 
                    ?>
                    <tr>
                        <td class="tkb-session" style="text-align: center; vertical-align: middle;"><?php echo $label; ?></td>
                        <?php for($d=2; $d<=8; $d++): ?>
                            <td>
                                <?php if (!empty($timetable[$key][$d])): ?>
                                    <?php foreach ($timetable[$key][$d] as $subj): ?>
                                        <div style="background: #dfe6e9; padding: 5px; border-radius: 5px; margin-bottom: 5px; font-weight: bold; font-size: 0.9em;">
                                            <?php echo htmlspecialchars($subj['name']); ?>
                                        </div>
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
                        <a href="do_task.php?task_id=<?php echo $task['id']; ?>" class="quest-card">
                            <?php if($task['status'] == 'submitted'): ?>
                                <span class="quest-badge badge-submitted">Đang chấm... ⏳</span>
                            <?php elseif($task['status'] == 'rejected'): ?>
                                <span class="quest-badge badge-rejected">Làm lại ⚠️</span>
                            <?php else: ?>
                                <span class="quest-badge badge-pending">Mới ✨</span>
                            <?php endif; ?>

                            <div style="font-weight: 800; font-size: 1.1em; margin-bottom: 5px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size: 0.9em; color: #636e72;"><?php echo htmlspecialchars($task['description']); ?></div>
                            <div class="quest-points">+<?php echo $task['points_reward']; ?> sao</div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <span style="font-size: 3em;">🎉</span>
                        <p style="color: #00b894; font-weight: bold;">Tuyệt vời! Đã xong hết việc hôm nay.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="task-col">
            <div class="task-col-header challenge-header">🔥 Thử thách đặc biệt</div>
            <div class="task-list">
                <?php if(count($tasks_challenge) > 0): ?>
                    <?php foreach($tasks_challenge as $task): ?>
                        <a href="do_task.php?task_id=<?php echo $task['id']; ?>" class="quest-card" style="border-left: 5px solid #ff7675;">
                            
                            <?php if($task['status'] == 'submitted'): ?>
                                <span class="quest-badge badge-submitted">Đang chấm...</span>
                            <?php elseif($task['status'] == 'rejected'): ?>
                                <span class="quest-badge badge-rejected">Làm lại</span>
                            <?php else: ?>
                                <span class="quest-badge badge-pending">Thử thách ⚔️</span>
                            <?php endif; ?>

                            <div style="font-weight: 800; font-size: 1.1em; margin-bottom: 5px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size: 0.9em; color: #636e72;"><?php echo htmlspecialchars($task['description']); ?></div>
                            <div class="quest-points" style="background: #ff7675; color: white;">+<?php echo $task['points_reward']; ?> sao</div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #b2bec3; margin-top: 20px;">Đang chờ Hệ thống cập nhật thêm thử thách...</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="section-title">
        <span>📜 Bảng vàng thành tích (Gần đây)</span>
    </div>
    <div class="history-card">
        <?php if(count($history_tasks) > 0): ?>
            <?php foreach($history_tasks as $ht): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #eee;">
                <div>
                    <div style="font-weight: bold; color: #2d3436;"><?php echo htmlspecialchars($ht['title']); ?></div>
                    <div style="font-size: 0.8em; color: #b2bec3;">
                        Hoàn thành: <?php echo date('H:i - d/m/Y', strtotime($ht['completed_at'])); ?>
                    </div>
                </div>
                <div style="font-weight: 900; color: #00b894; background: #55efc433; padding: 5px 10px; border-radius: 15px;">
                    +<?php echo $ht['points_reward']; ?> ⭐
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #b2bec3;">Bạn chưa có bài nào được chấm điểm.</p>
        <?php endif; ?>
    </div>

    <div class="shop-promo" onclick="window.location.href='shop.php'">
        <h3>🎁 Cửa hàng quà tặng</h3>
        <p style="margin: 0; font-size: 1.1em;">Bạn đang có <b><?php echo $user['current_points']; ?> sao</b>. Bấm vào đây để đổi quà ngay!</p>
    </div>

    <?php if (count($vouchers) > 0): ?>
    <div class="section-title" style="margin-top: 30px;">
        <span>🎟️ Vé đổi quà của bạn</span>
    </div>
    <div style="display: flex; overflow-x: auto; gap: 20px; padding-bottom: 20px;">
        <?php foreach ($vouchers as $v): ?>
            <?php 
                // Tạo link xác thực
                $verifyLink = "http://localhost/PinkyStudy/pages/parent/verify_gift.php?code=" . $v['voucher_code'];
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verifyLink);
            ?>
            <div class="voucher-ticket">
                <h4 style="margin: 0 0 10px 0; color: #e17055; font-size: 1.2em;"><?php echo htmlspecialchars($v['gift_name']); ?></h4>
                
                <a href="<?php echo $verifyLink; ?>" target="_blank" title="Click để giả lập quét mã">
                    <img src="<?php echo $qrUrl; ?>" alt="QR Code" style="border-radius: 8px; border: 2px solid #eee;">
                </a>
                
                <div style="font-family: 'Courier New', monospace; font-size: 1.5em; font-weight: 900; margin: 10px 0; color: #2d3436; letter-spacing: 2px;">
                    <?php echo $v['voucher_code']; ?>
                </div>
                <small style="color: #636e72;">Đưa mã này cho bố mẹ quét nhé</small>
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