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
    // Giữ nguyên logic, có thể làm đẹp thông báo lỗi sau nếu cần
    die("Nhiệm vụ không tồn tại hoặc bạn đã làm xong rồi.");
}

include '../../includes/header_student.php';
?>

<link rel="stylesheet" href="../../assets/css/student_style.css?v=<?php echo time(); ?>">

<div class="quest-detail-card">
    <a href="dashboard.php" class="btn-back">&larr; Quay lại Bảng nhiệm vụ</a>
    
    <div class="quest-header">
        <div class="quest-title"><?php echo htmlspecialchars($task['title']); ?></div>
        <span class="quest-points" style="font-size: 1.2em;">Phần thưởng: <?php echo $task['points_reward']; ?> ⭐</span>
    </div>

    <div class="mission-brief">
        <strong>🎯 Mục tiêu:</strong><br>
        <?php echo nl2br(htmlspecialchars($task['description'])); ?>
    </div>

    <form action="../../actions/task_submit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">

        <div style="margin-bottom: 30px;">
            <label class="form-label">1. Bằng chứng hoàn thành (Ảnh/File):</label>
            
            <div class="upload-zone">
                <input type="file" name="proof_files[]" multiple accept="image/*, audio/*, .doc, .docx, .pdf" required onchange="updateFileName(this)">
                
                <div class="upload-content">
                    <span class="upload-icon">📸 / 🎙️</span>
                    <span class="upload-text" id="fileNameDisplay">Chạm vào đây để chọn (có thể chọn nhiều ảnh)</span>
                    <br>
                    <small style="color: #b2bec3; margin-top: 5px; display: block;">(Hỗ trợ ảnh, Word, PDF, MP3...)</small>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 30px;">
            <label class="form-label">2. Nhắn gửi Hệ thống (Tùy chọn):</label>
            <textarea name="proof_text" rows="4" class="gamified-textarea" placeholder="Ví dụ: Nhiệm vụ này siêu dễ! Hoặc mình đã làm rất cố gắng..."></textarea>
        </div>

        <button type="submit" name="submit_task_btn" class="btn-mission-submit">
            Gửi báo cáo ngay 🚀
        </button>
    </form>
</div>

<script>
function updateFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files.length > 0) {
        if (input.files.length === 1) {
            display.innerText = "✅ Đã chọn: " + input.files[0].name;
        } else {
            display.innerText = "✅ Đã chọn " + input.files.length + " file.";
        }
        display.style.color = "#00b894";
        display.style.fontWeight = "900";
    } else {
        display.innerText = "Chạm vào đây để chọn ảnh hoặc file";
        display.style.color = "#636e72";
    }
}
</script>

</body>
</html>