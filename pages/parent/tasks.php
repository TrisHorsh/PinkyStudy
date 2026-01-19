<?php
// pages/parent/tasks.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Gọi hàm tự động

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_GET['student_id'] ?? 0;
$parent_id = $_SESSION['user_id'];

// --- 1. KÍCH HOẠT TỰ ĐỘNG GIAO BÀI HÀNG NGÀY ---
// Mỗi khi phụ huynh vào trang này, hệ thống sẽ kiểm tra và tạo task daily nếu chưa có
checkAndCreateDailyTasks($conn, $student_id, $parent_id);
// ------------------------------------------------

// Lấy thông tin bé
$stmtStudent = $conn->prepare("SELECT full_name FROM users WHERE id = :id AND parent_id = :pid");
$stmtStudent->execute([':id' => $student_id, ':pid' => $parent_id]);
$student = $stmtStudent->fetch();

if (!$student) die("Không tìm thấy học sinh.");

// Lấy danh sách nhiệm vụ ĐÃ GIAO (Bảng assigned_tasks)
$stmtTasks = $conn->prepare("SELECT * FROM assigned_tasks WHERE student_id = :sid ORDER BY created_at DESC");
$stmtTasks->execute([':sid' => $student_id]);
$assigned_tasks = $stmtTasks->fetchAll();

// Lấy danh sách NGÂN HÀNG MẪU (Bảng task_templates)
$stmtTemplates = $conn->prepare("SELECT * FROM task_templates WHERE creator_id = :pid ORDER BY created_at DESC");
$stmtTemplates->execute([':pid' => $parent_id]);
$templates = $stmtTemplates->fetchAll();

include '../../includes/header.php';
?>

<div class="container">
    <a href="dashboard.php" class="btn" style="background:#6c757d; color:white; margin-bottom:15px;">&larr; Quay lại</a>
    <h2>Quản lý bé: <span style="color: #007bff;"><?php echo htmlspecialchars($student['full_name']); ?></span></h2>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 350px;">
            
            <div class="card" style="background: #e3f2fd;">
                <h3>💾 Tạo Mẫu Nhiệm vụ</h3>
                <form action="../../actions/template_add.php" method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>"> <input type="text" name="title" required class="form-control" style="width: 100%; padding: 8px; margin-bottom: 10px;" placeholder="Tên nhiệm vụ mẫu (VD: Học Toán)">
                    <textarea name="description" rows="2" style="width: 100%; padding: 8px; margin-bottom: 10px;" placeholder="Mô tả..."></textarea>
                    
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="points" placeholder="Điểm" required style="width: 80px; padding: 8px;">
                        <select name="task_type" style="padding: 8px;">
                            <option value="normal">Thường (Giao thủ công)</option>
                            <option value="daily">Lặp lại hàng ngày (Tự động)</option>
                        </select>
                        <button type="submit" name="add_template_btn" class="btn btn-primary" style="flex: 1;">Lưu mẫu</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>📂 Ngân hàng mẫu có sẵn</h3>
                <?php if (count($templates) > 0): ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($templates as $tpl): ?>
                        <li style="border-bottom: 1px solid #eee; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <b><?php echo htmlspecialchars($tpl['title']); ?></b> 
                                <span style="font-size: 0.8em; color: #d63384;">(<?php echo $tpl['default_points']; ?>⭐)</span>
                                <br>
                                <?php if($tpl['task_type'] == 'daily'): ?>
                                    <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7em;">Lặp hàng ngày</span>
                                <?php endif; ?>
                            </div>
                            
                            <form action="../../actions/task_assign_template.php" method="POST">
                                <input type="hidden" name="template_id" value="<?php echo $tpl['id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.9em;">Giao ngay ➡️</button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #666;">Chưa có mẫu nào.</p>
                <?php endif; ?>
            </div>
        </div>

        <div style="flex: 1.5; min-width: 400px;">
            <div class="card">
                <h3>📋 Danh sách công việc hiện tại</h3>
                <?php if (count($assigned_tasks) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nhiệm vụ</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assigned_tasks as $task): ?>
                            <tr>
                                <td>
                                    <b><?php echo htmlspecialchars($task['title']); ?></b>
                                    <div style="font-size: 0.85em; color: #666;">
                                        <?php echo htmlspecialchars($task['description']); ?>
                                        <span style="color: #d63384;">(<?php echo $task['points_reward']; ?>⭐)</span>
                                    </div>
                                    <small style="color: #999;">Giao: <?php echo date('d/m H:i', strtotime($task['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        if($task['status']=='pending') echo '<span style="color:orange">Chưa làm</span>';
                                        elseif($task['status']=='submitted') echo '<span style="color:blue; font-weight:bold;">Chờ duyệt</span>';
                                        elseif($task['status']=='approved') echo '<span style="color:green">Đã xong</span>';
                                        else echo '<span style="color:red">Làm lại</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($task['status'] == 'submitted'): ?>
                                        <a href="grading.php?task_id=<?php echo $task['id']; ?>" class="btn btn-primary">Chấm</a>
                                    <?php elseif ($task['status'] == 'pending'): ?>
                                        <a href="../../actions/task_delete.php?id=<?php echo $task['id']; ?>&student_id=<?php echo $student_id; ?>" class="btn btn-danger" onclick="return confirm('Xóa?')">Xóa</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 20px;">Bé chưa có nhiệm vụ nào.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>