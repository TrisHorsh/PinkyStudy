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
    
    // [MỚI] Lấy lời nhận xét
    $parent_comment = isset($_POST['parent_comment']) ? trim($_POST['parent_comment']) : '';

    try {
        $conn->beginTransaction();

        if ($action == 'approve') {
            // Validate: Điểm chấm không được lớn hơn điểm tối đa
            if ($actual_score > $max_points) {
                $actual_score = $max_points; 
            }
            if ($actual_score < 0) $actual_score = 0;

            // 1. Cập nhật trạng thái + điểm thực tế + [MỚI] lời nhận xét
            $sqlTask = "UPDATE assigned_tasks 
                        SET status = 'approved', 
                            points_reward = :actual_score, 
                            parent_comment = :comment,
                            completed_at = NOW() 
                        WHERE id = :tid";
            
            $stmt = $conn->prepare($sqlTask);
            $stmt->execute([
                ':actual_score' => $actual_score, 
                ':comment' => $parent_comment,
                ':tid' => $task_id
            ]);

            // 2. Cộng điểm cho học sinh
            if ($actual_score > 0) {
                $sqlUser = "UPDATE users SET current_points = current_points + :points WHERE id = :sid";
                $conn->prepare($sqlUser)->execute([':points' => $actual_score, ':sid' => $student_id]);
            }
            
            $_SESSION['success'] = "Đã chấm xong! Bé nhận được $actual_score/$max_points sao.";

        } elseif ($action == 'reject') {
            // Nếu từ chối -> Cập nhật trạng thái và [MỚI] lời nhận xét (để bé biết sao sai)
            $sqlTask = "UPDATE assigned_tasks 
                        SET status = 'rejected',
                            parent_comment = :comment
                        WHERE id = :tid";
            $conn->prepare($sqlTask)->execute([
                ':comment' => $parent_comment,
                ':tid' => $task_id
            ]);
            
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