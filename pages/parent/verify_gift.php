<?php
// pages/parent/verify_gift.php
require_once '../../config/db_connect.php';
session_start();

// Kiểm tra đăng nhập (Bảo mật: Phải là phụ huynh mới được xác thực)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    die("Vui lòng đăng nhập tài khoản Phụ huynh để xác thực quà.");
}

$code = $_GET['code'] ?? '';

// 1. Tìm thông tin dựa trên mã code
$sql = "SELECT r.*, g.gift_name, g.gift_image, u.full_name 
        FROM redemptions r
        JOIN gifts g ON r.gift_id = g.id
        JOIN users u ON r.student_id = u.id
        WHERE r.voucher_code = :code";
$stmt = $conn->prepare($sql);
$stmt->execute([':code' => $code]);
$redemption = $stmt->fetch();

$message = "";
$message_type = "";

// 2. Xử lý khi bấm nút "Xác nhận đã trao"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_give'])) {
    if ($redemption && $redemption['status'] == 'approved') {
        $updateSql = "UPDATE redemptions SET status = 'used', used_at = NOW() WHERE id = :id";
        $conn->prepare($updateSql)->execute([':id' => $redemption['id']]);
        
        // Refresh lại dữ liệu để hiển thị
        $redemption['status'] = 'used';
        $redemption['used_at'] = date('Y-m-d H:i:s');
        
        $message = "✅ Xác thực thành công! Đã ghi nhận trao quà.";
        $message_type = "success";
    }
}

include '../../includes/header.php';
?>

<div class="container" style="max-width: 600px; margin-top: 50px;">
    <div class="card" style="text-align: center; padding: 40px;">
        
        <?php if (!$redemption): ?>
            <h2 style="color: red;">❌ Mã không hợp lệ</h2>
            <p>Không tìm thấy thông tin quà tặng với mã này.</p>
            <a href="dashboard.php" class="btn">Về trang chủ</a>
        
        <?php else: ?>
            
            <?php if ($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($redemption['status'] == 'used'): ?>
                <div style="background: #e2e3e5; color: #383d41; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                    ⚠️ Món quà này đã được trao ngày: <br>
                    <b><?php echo date('H:i d/m/Y', strtotime($redemption['used_at'])); ?></b>
                </div>
            <?php endif; ?>

            <img src="../../uploads/gifts/<?php echo $redemption['gift_image']; ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">
            
            <h2 style="margin: 0; color: #007bff;"><?php echo htmlspecialchars($redemption['gift_name']); ?></h2>
            <h1 style="font-family: monospace; letter-spacing: 5px; background: #eee; padding: 10px; border-radius: 8px; margin: 20px 0;">
                <?php echo $code; ?>
            </h1>

            <p>Học sinh: <b><?php echo htmlspecialchars($redemption['full_name']); ?></b></p>
            <p>Giá trị: <b style="color: #d63384;"><?php echo $redemption['points_spent']; ?> ⭐</b></p>

            <hr style="margin: 30px 0;">

            <?php if ($redemption['status'] == 'approved'): ?>
                <form method="POST">
                    <p>Phụ huynh xác nhận đang trao món quà này cho bé?</p>
                    <button type="submit" name="confirm_give" class="btn btn-primary" style="font-size: 1.2em; padding: 15px 30px; background: #28a745;">
                        🎁 Xác nhận Đã Trao Quà
                    </button>
                </form>
            <?php else: ?>
                <a href="manage_student.php?student_id=<?php echo $redemption['student_id']; ?>" class="btn btn-primary">Quay lại quản lý</a>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>