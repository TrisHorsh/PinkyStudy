<?php
// pages/parent/grading.php
require_once '../../config/db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php");
    exit();
}

$task_id = $_GET['task_id'] ?? 0;
$parent_id = $_SESSION['user_id'];

// Lấy thông tin nhiệm vụ
$sql = "SELECT t.*, s.full_name, s.id as student_id 
        FROM assigned_tasks t
        JOIN users s ON t.student_id = s.id
        WHERE t.id = :tid AND s.parent_id = :pid AND t.status = 'submitted'";

$stmt = $conn->prepare($sql);
$stmt->execute([':tid' => $task_id, ':pid' => $parent_id]);
$task = $stmt->fetch();

if (!$task) {
    die("Nhiệm vụ không tồn tại hoặc chưa được nộp.");
}

include '../../includes/header.php';
?>

<div class="container">
    <a href="tasks.php?student_id=<?php echo $task['student_id']; ?>" class="btn" style="background:#6c757d; color:white; margin-bottom:15px;">&larr; Quay lại danh sách</a>

    <div class="card" style="border-top: 5px solid #007bff; display: flex; flex-wrap: wrap; gap: 20px;">
        
        <div style="flex: 1; min-width: 300px;">
            <h2>📝 Chấm bài: <?php echo htmlspecialchars($task['full_name']); ?></h2>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <p><strong>Nhiệm vụ:</strong> <?php echo htmlspecialchars($task['title']); ?></p>
                <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($task['description']); ?></p>
                <p><strong>Điểm tối đa:</strong> <span style="color: #d63384; font-weight: bold;"><?php echo $task['points_reward']; ?> ⭐</span></p>
                <hr>
                <p><strong>Lời nhắn của bé:</strong><br> "<i><?php echo htmlspecialchars($task['proof_text'] ?: 'Không có lời nhắn'); ?></i>"</p>
            </div>

            <h3 style="margin-top: 20px;">Đánh giá & Cho điểm</h3>
            <form action="../../actions/task_grade.php" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <input type="hidden" name="student_id" value="<?php echo $task['student_id']; ?>">
                <input type="hidden" name="max_points" value="<?php echo $task['points_reward']; ?>">

                <div style="margin-bottom: 15px;">
                    <label style="font-weight: bold;">Số sao thực nhận:</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="number" name="actual_score" 
                               value="<?php echo $task['points_reward']; ?>" 
                               max="<?php echo $task['points_reward']; ?>" 
                               min="0" 
                               class="form-control" style="width: 100px; padding: 10px; font-size: 1.2em; border: 2px solid #28a745; text-align: center;">
                        <span>/ <?php echo $task['points_reward']; ?> ⭐</span>
                    </div>
                    <small style="color: #666;">Bạn có thể trừ điểm nếu bé làm chưa tốt.</small>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="action" value="approve" class="btn btn-primary" style="background-color: #28a745; flex: 1;">
                        ✅ Duyệt & Cộng điểm
                    </button>
                    
                    <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Yêu cầu bé làm lại?');">
                        ❌ Làm lại
                    </button>
                </div>
            </form>
        </div>

        <div style="flex: 2; min-width: 400px; border: 1px dashed #ccc; padding: 10px; border-radius: 8px; background: #fff;">
            <h3 style="text-align: center; margin-top: 0;">Bằng chứng nộp bài</h3>
            <?php 
                $file_path = "../../uploads/proofs/" . $task['proof_file'];
                $ext = strtolower(pathinfo($task['proof_file'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    // HIỂN THỊ ẢNH
                    echo "<div style='text-align: center;'><img src='$file_path' style='max-width: 100%; max-height: 600px; border-radius: 4px;'></div>";
                } elseif ($ext == 'pdf') {
                    // HIỂN THỊ PDF (Trực tiếp)
                    echo "<iframe src='$file_path' width='100%' height='600px' style='border: none;'></iframe>";
                } else {
                    // WORD/EXCEL (Tải về)
                    echo "<div style='text-align: center; padding: 50px;'>
                            <p>Định dạng <b>.$ext</b> không hỗ trợ xem trước.</p>
                            <a href='$file_path' class='btn btn-primary'>📥 Tải xuống để xem</a>
                          </div>";
                }
            ?>
        </div>

    </div>
</div>
</body>
</html>