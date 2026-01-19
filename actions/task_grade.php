<?php
// actions/task_grade.php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    die("Truy cập bị từ chối.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $task_id = $_POST['task_id'];
    $student_id = $_POST['student_id'];
    $action = $_POST['action']; 
    
    // Lấy điểm phụ huynh chấm (nếu không nhập thì mặc định là 0)
    $actual_score = isset($_POST['actual_score']) ? intval($_POST['actual_score']) : 0;
    $max_points = intval($_POST['max_points']); // Điểm tối đa để validate

    try {
        $conn->beginTransaction();

        if ($action == 'approve') {
            // Validate: Điểm chấm không được lớn hơn điểm tối đa (tránh hack HTML)
            if ($actual_score > $max_points) {
                $actual_score = $max_points; 
            }
            if ($actual_score < 0) $actual_score = 0;

            // 1. Cập nhật trạng thái VÀ lưu số điểm thực tế nhận được vào bảng tasks
            // (Lưu ý: Tôi cập nhật cột points_reward thành số điểm thực tế để lịch sử hiển thị đúng số điểm bé nhận)
            $sqlTask = "UPDATE assigned_tasks 
                        SET status = 'approved', 
                            points_reward = :actual_score, 
                            completed_at = NOW() 
                        WHERE id = :tid";
            
            $stmt = $conn->prepare($sqlTask);
            $stmt->execute([':actual_score' => $actual_score, ':tid' => $task_id]);

            // 2. Cộng điểm cho học sinh
            if ($actual_score > 0) {
                $sqlUser = "UPDATE users SET current_points = current_points + :points WHERE id = :sid";
                $conn->prepare($sqlUser)->execute([':points' => $actual_score, ':sid' => $student_id]);
            }
            
            $_SESSION['success'] = "Đã chấm xong! Bé nhận được $actual_score/$max_points sao.";

        } elseif ($action == 'reject') {
            // Nếu từ chối, điểm không đổi, trạng thái về rejected
            $sqlTask = "UPDATE assigned_tasks SET status = 'rejected' WHERE id = :tid";
            $conn->prepare($sqlTask)->execute([':tid' => $task_id]);
            
            $_SESSION['success'] = "Đã trả lại bài. Yêu cầu bé làm lại.";
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

header("Location: ../pages/parent/manage_student.php?student_id=$student_id");
exit();
}
?>