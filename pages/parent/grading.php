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
    // Có thể thêm trang báo lỗi đẹp hơn sau này, tạm thời dùng die
    die("Nhiệm vụ không tồn tại hoặc chưa được nộp.");
}

include '../../includes/header.php';
?>

<div class="grading-container">
    <a href="manage_student.php?student_id=<?php echo $task['student_id']; ?>" class="btn btn-secondary" style="margin-bottom:15px;">
        <i class="fas fa-arrow-left"></i> Quay lại quản lý bé
    </a>

    <div class="grading-layout">
        
        <div class="grading-panel">
            <h2 style="margin-top: 0; color: #28a745; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> Chấm bài
            </h2>
            
            <div class="task-info-box">
                <div class="task-info-item">
                    <span class="task-info-label">Học sinh:</span>
                    <strong><?php echo htmlspecialchars($task['full_name']); ?></strong>
                </div>
                <div class="task-info-item">
                    <span class="task-info-label">Nhiệm vụ:</span>
                    <span><?php echo htmlspecialchars($task['title']); ?></span>
                </div>
                <div class="task-info-item">
                    <span class="task-info-label">Mô tả:</span>
                    <span style="font-size: 0.9em; text-align: right;"><?php echo htmlspecialchars($task['description']); ?></span>
                </div>
                <div class="task-info-item" style="border-top: 1px dashed #ccc; padding-top: 10px; margin-top: 10px;">
                    <span class="task-info-label">Điểm thưởng:</span>
                    <span class="badge bg-green" style="font-size: 1em;"><?php echo $task['points_reward']; ?> ⭐</span>
                </div>
                
                <?php if (!empty($task['proof_text'])): ?>
                <div class="student-message">
                    <i class="fas fa-comment-dots"></i> "<?php echo htmlspecialchars($task['proof_text']); ?>"
                </div>
                <?php endif; ?>
            </div>

            <h3 style="margin-bottom: 15px;">Đánh giá & Cho điểm</h3>
            
            <form action="../../actions/task_grade.php" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <input type="hidden" name="student_id" value="<?php echo $task['student_id']; ?>">
                <input type="hidden" name="max_points" value="<?php echo $task['points_reward']; ?>">

                <div class="score-control">
                    <div>
                        <label style="font-weight: bold; display: block; color: #856404;">Số sao thực nhận:</label>
                        <small style="color: #856404;">(Có thể trừ bớt nếu làm chưa tốt)</small>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="number" name="actual_score" 
                               value="<?php echo $task['points_reward']; ?>" 
                               max="<?php echo $task['points_reward']; ?>" 
                               min="0" 
                               class="score-input">
                        <span style="font-weight: bold; font-size: 1.2em; color: #856404;">/ <?php echo $task['points_reward']; ?></span>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px; color: #495057;">✍️ Lời nhận xét / Dặn dò:</label>
                    <textarea name="parent_comment" class="form-control" rows="3" 
                              placeholder="Ví dụ: Con làm tốt lắm, nhưng chú ý viết chữ đẹp hơn nhé..."
                              style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 8px; font-family: inherit;"></textarea>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="submit" name="action" value="approve" class="btn btn-success" style="padding: 12px; font-size: 1.1em; font-weight: bold;">
                        <i class="fas fa-award"></i> Duyệt & Cộng điểm
                    </button>
                    
                    <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Bạn chắc chắn muốn yêu cầu bé làm lại?');" style="background: white; color: #dc3545; border: 1px solid #dc3545;">
                        <i class="fas fa-undo"></i> Yêu cầu làm lại
                    </button>
                </div>
            </form>
        </div>

        <div class="proof-viewer">
            <div class="proof-header">
                <h3 style="margin: 0; font-size: 1.1em;"><i class="fas fa-paperclip"></i> Bằng chứng nộp bài</h3>
                <?php 
                    $file_url = "../../uploads/proofs/" . $task['proof_file']; 
                ?>
                <a href="<?php echo $file_url; ?>" download class="btn btn-sm btn-primary" style="font-size: 0.8em;">
                    <i class="fas fa-download"></i> Tải về
                </a>
            </div>

            <div class="proof-content" style="flex-direction: column; overflow-y: auto; padding: 10px;">
                <?php 
                    // Giải mã JSON. Nếu file cũ (không phải JSON) thì đưa vào mảng 1 phần tử.
                    $files = json_decode($task['proof_file']);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
                        $files = [$task['proof_file']];
                    }

                    foreach ($files as $file) {
                        $file_url = "../../uploads/proofs/" . $file;
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        
                        echo "<div style='margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #fff;'>";
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            // HIỂN THỊ ẢNH
                            echo "<img src='$file_url' style='max-width: 100%; display: block; margin: 0 auto; border-radius: 4px;'>";
                        } 
                        elseif (in_array($ext, ['mp3', 'wav', 'm4a', 'ogg'])) {
                            // HIỂN THỊ TRÌNH NGHE NHẠC (AUDIO PLAYER)
                            echo "<div style='display: flex; align-items: center; gap: 10px;'>
                                    <div style='font-size: 2em; color: #E91E63;'><i class='fas fa-music'></i></div>
                                    <div style='flex: 1;'>
                                        <div style='font-weight: bold; margin-bottom: 5px; color: #333;'>File ghi âm: $file</div>
                                        <audio controls style='width: 100%;'>
                                            <source src='$file_url' type='audio/$ext'>
                                            Trình duyệt không hỗ trợ phát âm thanh.
                                        </audio>
                                    </div>
                                  </div>";
                        }
                        elseif ($ext == 'pdf') {
                            // HIỂN THỊ PDF
                            echo "<iframe src='$file_url' style='width: 100%; height: 500px; border: none;'></iframe>";
                        } 
                        else {
                            // FILE KHÁC (WORD, EXCEL...)
                            echo "<div style='text-align: center; padding: 20px;'>
                                    <i class='fas fa-file-alt' style='font-size: 3em; color: #007bff;'></i> <br>
                                    <p style='margin: 10px 0; font-weight: bold;'>$file</p>
                                    <a href='$file_url' download class='btn btn-sm btn-primary'>
                                        <i class='fas fa-download'></i> Tải về
                                    </a>
                                  </div>";
                        }
                        echo "</div>";
                    }
                ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>