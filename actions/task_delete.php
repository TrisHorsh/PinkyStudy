<?php
// actions/task_delete.php
session_start();
require_once '../config/db_connect.php';

// 1. Kiểm tra quyền Phụ huynh
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../pages/auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];
    $student_id = $_GET['student_id'] ?? 0; // Lấy để redirect về đúng trang
    $parent_id = $_SESSION['user_id'];

    try {
        // 2. Thực hiện xóa an toàn
        // Logic: Chỉ xóa nhiệm vụ NẾU nhiệm vụ đó thuộc về học sinh CỦA phụ huynh đang đăng nhập
        // Chúng ta dùng Sub-query để kiểm tra quyền sở hữu
        $sql = "DELETE FROM assigned_tasks 
                WHERE id = :tid 
                AND student_id IN (SELECT id FROM users WHERE parent_id = :pid)";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':tid' => $task_id, 
            ':pid' => $parent_id
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Đã xóa nhiệm vụ thành công.";
        } else {
            $_SESSION['error'] = "Không thể xóa (Nhiệm vụ không tồn tại hoặc không thuộc quyền quản lý của bạn).";
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
    }
    
    // 3. Điều hướng quay lại
    if ($student_id > 0) {
        header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
    } else {
        header("Location: ../pages/parent/dashboard.php");
    }
    exit();

} else {
    // Nếu không có ID thì quay về dashboard
    header("Location: ../pages/parent/dashboard.php");
    exit();
}
?>