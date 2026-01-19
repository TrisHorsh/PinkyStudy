<?php
// actions/template_delete.php
session_start();
require_once '../config/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $student_id = $_GET['student_id'] ?? 0; // Để redirect về
    
    // Chỉ xóa nếu là người tạo (bảo mật)
    $stmt = $conn->prepare("DELETE FROM task_templates WHERE id = :id AND creator_id = :pid");
    $stmt->execute([':id' => $id, ':pid' => $_SESSION['user_id']]);
    
    $_SESSION['success'] = "Đã xóa mẫu nhiệm vụ.";
    
    if ($student_id > 0) {
        header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
    } else {
        header("Location: ../pages/parent/dashboard.php");
    }
    exit();
}
?>