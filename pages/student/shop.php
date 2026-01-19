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
$_SESSION['current_points'] = $current_points; // Update session

// 2. Lấy danh sách quà
$stmtGifts = $conn->prepare("SELECT * FROM gifts WHERE parent_id = :pid AND is_active = 1");
$stmtGifts->execute([':pid' => $parent_id]);
$gifts = $stmtGifts->fetchAll();

// 3. Lấy lịch sử đổi quà gần đây
$stmtHist = $conn->prepare("SELECT r.*, g.gift_name FROM redemptions r JOIN gifts g ON r.gift_id = g.id WHERE r.student_id = :sid ORDER BY r.redemption_date DESC LIMIT 5");
$stmtHist->execute([':sid' => $student_id]);
$history = $stmtHist->fetchAll();

include '../../includes/header_student.php';
?>

<div class="card" style="text-align: center; background: linear-gradient(45deg, #ffc107, #ff9800); color: white;">
    <h2>💰 Ví của bạn có: <?php echo $current_points; ?> sao</h2>
    <p>Hãy chăm chỉ làm nhiệm vụ để đổi được nhiều quà nhé!</p>
</div>

<h3 style="color: #e91e63;">🎁 Cửa hàng quà tặng</h3>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
    <?php foreach($gifts as $gift): ?>
        <?php $can_buy = ($current_points >= $gift['point_cost']); ?>
        
        <div class="card" style="padding: 0; overflow: hidden; position: relative; <?php if(!$can_buy) echo 'opacity: 0.6;'; ?>">
            <img src="../../uploads/gifts/<?php echo $gift['gift_image']; ?>" style="width: 100%; height: 180px; object-fit: cover;">
            
            <div style="padding: 15px; text-align: center;">
                <h4 style="margin: 5px 0;"><?php echo htmlspecialchars($gift['gift_name']); ?></h4>
                <div style="font-size: 1.2em; font-weight: bold; color: #d63384; margin-bottom: 10px;">
                    <?php echo $gift['point_cost']; ?> ⭐
                </div>

                <form action="../../actions/gift_redeem.php" method="POST" onsubmit="return confirm('Bạn muốn đổi món quà này chứ?');">
                    <input type="hidden" name="gift_id" value="<?php echo $gift['id']; ?>">
                    <input type="hidden" name="point_cost" value="<?php echo $gift['point_cost']; ?>">
                    
                    <?php if ($can_buy): ?>
                        <button type="submit" name="redeem_btn" class="btn btn-action" style="width: 100%;">Đổi ngay 🎁</button>
                    <?php else: ?>
                        <button type="button" class="btn" style="background: #ccc; cursor: not-allowed; width: 100%;">Thiếu điểm 🔒</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h3 style="margin-top: 40px; color: #2196f3;">📜 Lịch sử đổi quà</h3>
<div class="card">
    <ul>
        <?php foreach($history as $h): ?>
            <li>
                Đổi <b><?php echo htmlspecialchars($h['gift_name']); ?></b> 
                (<?php echo $h['points_spent']; ?> sao) - 
                <?php 
                    if($h['status'] == 'pending') echo '<span style="color:orange; font-weight:bold;">Đang chờ hệ thống duyệt ⏳</span>';
                    elseif($h['status'] == 'approved') echo '<span style="color:green; font-weight:bold;">Thành công ✅</span>';
                    else echo '<span style="color:red;">Bị từ chối ❌</span>';
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

</body>
</html>