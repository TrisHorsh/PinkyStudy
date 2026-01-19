<?php
// actions/task_assign_template.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $parent_id = $_SESSION['user_id'];
    $template_id = $_POST['template_id'];
    $student_id = $_POST['student_id'];

    // 1. Lấy thông tin mẫu
    $stmtTpl = $conn->prepare("SELECT * FROM task_templates WHERE id = :tid AND creator_id = :pid");
    $stmtTpl->execute([':tid' => $template_id, ':pid' => $parent_id]);
    $template = $stmtTpl->fetch();

    if ($template) {
        // 2. Tạo nhiệm vụ mới cho học sinh dựa trên mẫu
        $sql = "INSERT INTO assigned_tasks (student_id, title, description, points_reward, status, created_at) 
                VALUES (:sid, :title, :desc, :points, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':sid' => $student_id,
            ':title' => $template['title'],
            ':desc' => $template['description'],
            ':points' => $template['default_points']
        ]);

        $_SESSION['success'] = "Đã giao nhiệm vụ '" . $template['title'] . "' thành công!";
    } else {
        $_SESSION['error'] = "Không tìm thấy mẫu nhiệm vụ.";
    }

    header("Location: ../pages/parent/tasks.php?student_id=" . $student_id);
    exit();
}
?>