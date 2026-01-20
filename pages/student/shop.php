<?php
// pages/student/shop.php
require_once '../../config/db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_SESSION['user_id'];
$parent_id = $_SESSION['parent_id'];

// 1. Lấy điểm hiện tại
$stmtUser = $conn->prepare("SELECT current_points FROM users WHERE id = :id");
$stmtUser->execute([':id' => $student_id]);
$current_points = $stmtUser->fetchColumn();
$_SESSION['current_points'] = $current_points; 

// 2. Lấy danh sách quà
$stmtGifts = $conn->prepare("SELECT * FROM gifts WHERE parent_id = :pid AND is_active = 1");
$stmtGifts->execute([':pid' => $parent_id]);
$gifts = $stmtGifts->fetchAll();

// 3. Lấy lịch sử
$stmtHist = $conn->prepare("SELECT r.*, g.gift_name FROM redemptions r JOIN gifts g ON r.gift_id = g.id WHERE r.student_id = :sid ORDER BY r.redemption_date DESC LIMIT 5");
$stmtHist->execute([':sid' => $student_id]);
$history = $stmtHist->fetchAll();

include '../../includes/header_student.php';
?>

<link rel="stylesheet" href="../../assets/css/student_style.css?v=<?php echo time(); ?>">

<div class="dashboard-container">

    <a href="dashboard.php" class="btn-back" style="margin-bottom: 20px; display: inline-block;">&larr; Quay lại Dashboard</a>

    <div class="wallet-card">
        <div class="wallet-label">Tài sản hiện có</div>
        <div class="wallet-amount"><?php echo $current_points; ?> ⭐</div>
        <p style="margin: 0; position: relative; z-index: 2;">Chăm chỉ làm nhiệm vụ để ví dày thêm nhé!</p>
    </div>

    <div class="section-title" style="color: #e17055;">
        <span>🎁 Cửa hàng tạp hóa kỳ diệu</span>
    </div>

    <div class="shop-grid">
        <?php foreach($gifts as $gift): ?>
            <?php $can_buy = ($current_points >= $gift['point_cost']); ?>
            
            <div class="gift-card" <?php if(!$can_buy) echo 'style="opacity: 0.7; filter: grayscale(0.5);"'; ?>>
                <div class="gift-img-wrapper">
                    <img src="../../uploads/gifts/<?php echo $gift['gift_image']; ?>" class="gift-img">
                </div>
                
                <div class="gift-body">
                    <div>
                        <div class="gift-title"><?php echo htmlspecialchars($gift['gift_name']); ?></div>
                        <div class="gift-price"><?php echo $gift['point_cost']; ?> sao</div>
                    </div>

                    <form action="../../actions/gift_redeem.php" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn đổi món quà này chứ?');">
                        <input type="hidden" name="gift_id" value="<?php echo $gift['id']; ?>">
                        <input type="hidden" name="point_cost" value="<?php echo $gift['point_cost']; ?>">
                        
                        <?php if ($can_buy): ?>
                            <button type="submit" name="redeem_btn" class="btn-redeem active">Đổi ngay 🛍️</button>
                        <?php else: ?>
                            <button type="button" class="btn-redeem disabled">Chưa đủ điểm 🔒</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title" style="margin-top: 40px;">
        <span>📜 Lịch sử giao dịch</span>
    </div>
    <div class="history-card" style="padding: 0; overflow: hidden; border: none;">
        <ul class="history-list">
            <?php foreach($history as $h): ?>
                <li class="history-item">
                    <div>
                        <span style="font-size: 1.2em;">🛍️</span> 
                        Đổi <b><?php echo htmlspecialchars($h['gift_name']); ?></b>
                        <br>
                        <small style="color: #999;">Tiêu tốn: <?php echo $h['points_spent']; ?> sao</small>
                    </div>
                    
                    <div>
                        <?php 
                            if($h['status'] == 'pending') echo '<span class="status-badge st-pending">⏳ Chờ Hệ thống duyệt</span>';
                            elseif($h['status'] == 'approved') echo '<span class="status-badge st-approved">✅ Thành công</span>';
                            else echo '<span class="status-badge st-rejected">❌ Bị từ chối</span>';
                        ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>

</body>
</html>