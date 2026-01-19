<?php
// pages/student/do_task.php
require_once '../../config/db_connect.php';
session_start();

if (!isset($_GET['task_id'])) {
    header("Location: dashboard.php");
    exit();
}

$task_id = $_GET['task_id'];
$student_id = $_SESSION['user_id'];

// Lấy thông tin nhiệm vụ để hiển thị
$stmt = $conn->prepare("SELECT * FROM assigned_tasks WHERE id = :id AND student_id = :sid");
$stmt->execute([':id' => $task_id, ':sid' => $student_id]);
$task = $stmt->fetch();

if (!$task || ($task['status'] == 'approved' || $task['status'] == 'submitted')) {
    die("Nhiệm vụ không tồn tại hoặc bạn đã làm xong rồi.");
}

include '../../includes/header_student.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <a href="dashboard.php" style="text-decoration: none; color: #666;">&larr; Quay lại</a>
    
    <h2 style="color: #00bcd4;"><?php echo htmlspecialchars($task['title']); ?></h2>
    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <p><b>Yêu cầu:</b> <?php echo htmlspecialchars($task['description']); ?></p>
        <p><b>Phần thưởng:</b> <span class="star-badge"><?php echo $task['points_reward']; ?> sao</span></p>
    </div>

    <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">Nộp kết quả</h3>
    
    <form action="../../actions/task_submit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">1. Chọn ảnh bài làm (hoặc file):</label>
            <input type="file" name="proof_file" accept="image/*, .doc, .docx, .pdf" required style="padding: 10px; border: 1px dashed #ccc; width: 100%;">
            <small style="color: #666;">Chấp nhận file ảnh, word hoặc pdf.</small>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">2. Lời nhắn cho Hệ thống (tùy chọn):</label>
            <textarea name="proof_text" rows="3" style="width: 100%; padding: 10px;" placeholder="Ví dụ: Mình làm xong rồi nhé, bài này hơi khó..."></textarea>
        </div>

        <button type="submit" name="submit_task_btn" class="btn btn-success" style="width: 100%;">Gửi bài ngay 🚀</button>
    </form>
</div>

</body>
</html>