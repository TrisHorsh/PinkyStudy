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

// 2. Lấy danh sách quà trong shop
$stmtGifts = $conn->prepare("SELECT * FROM gifts WHERE parent_id = :pid AND is_active = 1");
$stmtGifts->execute([':pid' => $parent_id]);
$gifts = $stmtGifts->fetchAll();

// 3. Lấy lịch sử đổi quà
$stmtHist = $conn->prepare("SELECT r.*, g.gift_name FROM redemptions r JOIN gifts g ON r.gift_id = g.id WHERE r.student_id = :sid ORDER BY r.redemption_date DESC LIMIT 5");
$stmtHist->execute([':sid' => $student_id]);
$history = $stmtHist->fetchAll();

// 4. Lấy danh sách điều ước của bé
$stmtReq = $conn->prepare("SELECT * FROM gift_requests WHERE student_id = :sid ORDER BY created_at DESC");
$stmtReq->execute([':sid' => $student_id]);
$my_requests = $stmtReq->fetchAll();

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

    <div class="wish-section">
        <div class="wish-header">
            <h3>🧞 Tủ điều ước của em</h3>
            <button onclick="toggleWishModal(true)" class="btn-wish-open">
                <i class="fas fa-magic"></i> Gửi điều ước mới
            </button>
        </div>

        <?php if(count($my_requests) > 0): ?>
            <div class="wish-scroll-container">
                <?php foreach($my_requests as $req): ?>
                    <div class="wish-card">
                        <div>
                            <div class="wish-name"><?php echo htmlspecialchars($req['gift_name']); ?></div>
                            <div class="wish-desc"><?php echo htmlspecialchars($req['gift_desc']); ?></div>
                        </div>
                        
                        <?php if($req['status'] == 'pending'): ?>
                            <span class="badge badge-pending">⏳ Chờ xem xét</span>
                        <?php elseif($req['status'] == 'approved'): ?>
                            <span class="badge badge-approved">🎉 Đã có trong shop!</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">❌ Chưa được duyệt</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #546e7a; padding: 20px;">
                <i class="far fa-star" style="font-size: 2em; margin-bottom: 10px; opacity: 0.5;"></i>
                <p style="margin: 0;">Em chưa có điều ước nào. Hãy gửi cho Hệ thống biết em thích gì nhé!</p>
            </div>
        <?php endif; ?>
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

<div id="wishModal" class="modal-overlay">
    <div class="modal-box">
        <h3 class="modal-title">✨ Em muốn quà gì nào?</h3>
        
        <form action="../../actions/gift_request_add.php" method="POST">
            <div style="margin-bottom: 20px;">
                <label class="form-label">Tên món quà:</label>
                <input type="text" name="gift_name" required placeholder="Ví dụ: Gà rán..." class="form-control" style="width: 100%; padding: 12px; border: 2px solid #dfe6e9; border-radius: 10px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label class="form-label">Mô tả (hoặc link ảnh/mua hàng):</label>
                <textarea name="gift_desc" rows="3" placeholder="Gửi link cho Hệ thống hoặc mô tả màu sắc..." class="textareaoso"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="toggleWishModal(false)" class="btn-secondary">Để sau</button>
                <button type="submit" class="btn-submit-wish">Gửi điều ước 🚀</button>
            </div>
        </form>
    </div>
</div>
<script>
    // Script bật tắt modal
    function toggleWishModal(show) {
        const modal = document.getElementById('wishModal');
        if (show) {
            modal.classList.add('open');
        } else {
            modal.classList.remove('open');
        }
    }
    
    // Đóng modal khi click ra ngoài vùng trắng
    window.onclick = function(event) {
        const modal = document.getElementById('wishModal');
        if (event.target == modal) {
            modal.classList.remove('open');
        }
    }
</script>

</body>
</html>