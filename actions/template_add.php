<?php
// actions/template_add.php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php'; // Gọi hàm để nếu là daily thì chạy luôn

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_template_btn'])) {
    
    $parent_id = $_SESSION['user_id'];
    $student_id = $_POST['student_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $points = intval($_POST['points']);
    $type = $_POST['task_type']; // Lấy từ form (challenge/daily)

    if (!empty($title)) {
        // Lưu vào bảng templates
        $sql = "INSERT INTO task_templates (creator_id, title, description, default_points, task_type) 
                VALUES (:pid, :title, :desc, :pts, :type)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pid' => $parent_id,
            ':title' => $title,
            ':desc' => $desc,
            ':pts' => $points,
            ':type' => $type
        ]);
        
        // NẾU LÀ DAILY: Kích hoạt ngay lập tức cho hôm nay
        if ($type === 'daily') {
            checkAndCreateDailyTasks($conn, $student_id, $parent_id);
        }
        
        $_SESSION['success'] = "Đã lưu mẫu nhiệm vụ mới!";
    }

    header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
    exit();
}
?>