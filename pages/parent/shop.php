<?php
// pages/parent/shop.php
require_once '../../config/db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$parent_id = $_SESSION['user_id'];

// 1. Lấy danh sách yêu cầu đổi quà đang CHỜ DUYỆT (Pending)
$sqlReq = "SELECT r.*, g.gift_name, g.gift_image, u.full_name, u.current_points 
           FROM redemptions r
           JOIN gifts g ON r.gift_id = g.id
           JOIN users u ON r.student_id = u.id
           WHERE u.parent_id = :pid AND r.status = 'pending'
           ORDER BY r.redemption_date ASC";
$stmtReq = $conn->prepare($sqlReq);
$stmtReq->execute([':pid' => $parent_id]);
$requests = $stmtReq->fetchAll();

// 2. Lấy danh sách quà đang có trong shop
$stmtGifts = $conn->prepare("SELECT * FROM gifts WHERE parent_id = :pid ORDER BY created_at DESC");
$stmtGifts->execute([':pid' => $parent_id]);
$gifts = $stmtGifts->fetchAll();

include '../../includes/header.php';
?>

<div class="container">
    
    <?php if(count($requests) > 0): ?>
    <div class="card" style="border-left: 5px solid #ffc107; background: #fff3cd;">
        <h3>🔔 Yêu cầu đổi quà mới (<?php echo count($requests); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>Bé</th>
                    <th>Muốn đổi</th>
                    <th>Giá</th>
                    <th>Điểm hiện có</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $req): ?>
                <tr>
                    <td><b><?php echo htmlspecialchars($req['full_name']); ?></b></td>
                    <td>
                        <img src="../../uploads/gifts/<?php echo $req['gift_image']; ?>" width="40" style="vertical-align: middle;">
                        <?php echo htmlspecialchars($req['gift_name']); ?>
                    </td>
                    <td style="color: #d63384; font-weight: bold;"><?php echo $req['points_spent']; ?> ⭐</td>
                    <td><?php echo $req['current_points']; ?> ⭐</td>
                    <td>
                        <form action="../../actions/gift_approve.php" method="POST" style="display: inline;">
                            <input type="hidden" name="redemption_id" value="<?php echo $req['id']; ?>">
                            <input type="hidden" name="student_id" value="<?php echo $req['student_id']; ?>">
                            <input type="hidden" name="cost" value="<?php echo $req['points_spent']; ?>">
                            
                            <button type="submit" name="action" value="approve" class="btn btn-primary">✅ Đồng ý</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Từ chối</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>🏪 Quản lý Cửa hàng quà tặng</h3>
        
        <form action="../../actions/gift_add.php" method="POST" enctype="multipart/form-data" style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: flex; gap: 10px; align-items: end;">
            <div style="flex: 2;">
                <label>Tên món quà:</label>
                <input type="text" name="gift_name" required class="form-control" style="width: 100%; padding: 8px;" placeholder="VD: Bộ Lego City">
            </div>
            <div style="flex: 1;">
                <label>Giá (Số sao):</label>
                <input type="number" name="point_cost" required class="form-control" style="width: 100%; padding: 8px;" placeholder="100">
            </div>
            <div style="flex: 1;">
                <label>Ảnh minh họa:</label>
                <input type="file" name="gift_image" required accept="image/*" style="font-size: 0.8em;">
            </div>
            <button type="submit" name="add_gift_btn" class="btn btn-primary">➕ Thêm quà</button>
        </form>

        <hr>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach($gifts as $gift): ?>
            <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; text-align: center;">
                <img src="../../uploads/gifts/<?php echo $gift['gift_image']; ?>" style="width: 100%; height: 150px; object-fit: cover;">
                <div style="padding: 10px;">
                    <h4 style="margin: 5px 0;"><?php echo htmlspecialchars($gift['gift_name']); ?></h4>
                    <span style="background: #ffc107; padding: 3px 8px; border-radius: 10px; font-weight: bold; font-size: 0.9em;">
                        <?php echo $gift['point_cost']; ?> ⭐
                    </span>
                    <br><br>
                    <a href="../../actions/gift_delete.php?id=<?php echo $gift['id']; ?>" class="btn btn-danger" style="font-size: 0.8em;" onclick="return confirm('Xóa quà này?')">Xóa</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>