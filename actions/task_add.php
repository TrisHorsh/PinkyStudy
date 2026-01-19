<?php
// actions/task_add.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task_btn'])) {
    
    // Lấy dữ liệu từ form
    $student_id = $_POST['student_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $points = intval($_POST['points']);
    $task_type = $_POST['task_type']; // 'challenge' hoặc 'daily'
    $parent_id = $_SESSION['user_id'];

    if (empty($title)) {
        $_SESSION['error'] = "Tên nhiệm vụ không được để trống.";
        header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
        exit();
    }

    try {
        $conn->beginTransaction();

        // TRƯỜNG HỢP 1: NHIỆM VỤ HÀNG NGÀY (LẶP LẠI)
        if ($task_type === 'daily') {
            // 1. Lưu vào bảng Mẫu (Templates) để nó tự chạy vào các ngày sau
            $sqlTpl = "INSERT INTO task_templates (creator_id, title, description, default_points, task_type) 
                       VALUES (:pid, :title, :desc, :pts, 'daily')";
            $stmtTpl = $conn->prepare($sqlTpl);
            $stmtTpl->execute([
                ':pid' => $parent_id,
                ':title' => $title,
                ':desc' => $description,
                ':pts' => $points
            ]);

            // 2. Tạo ngay một nhiệm vụ cho ngày hôm nay (để bé thấy luôn không cần chờ mai)
            // Kiểm tra xem hôm nay đã có chưa (tránh trùng nếu bấm nhiều lần)
            $sqlCheck = "SELECT id FROM assigned_tasks 
                         WHERE student_id = :sid AND title = :title AND DATE(created_at) = CURDATE()";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->execute([':sid' => $student_id, ':title' => $title]);

            if ($stmtCheck->rowCount() == 0) {
                $sqlTask = "INSERT INTO assigned_tasks (student_id, title, description, points_reward, status, task_type) 
                            VALUES (:sid, :title, :desc, :pts, 'pending', 'daily')";
                $conn->prepare($sqlTask)->execute([
                    ':sid' => $student_id,
                    ':title' => $title,
                    ':desc' => $description,
                    ':pts' => $points
                ]);
            }

            $_SESSION['success'] = "Đã thiết lập nhiệm vụ hàng ngày và giao cho bé ngay hôm nay!";
        } 
        
        // TRƯỜNG HỢP 2: NHIỆM VỤ THỬ THÁCH (GIAO 1 LẦN)
        else {
            $sqlTask = "INSERT INTO assigned_tasks (student_id, title, description, points_reward, status, task_type) 
                        VALUES (:sid, :title, :desc, :pts, 'pending', 'challenge')";
            $conn->prepare($sqlTask)->execute([
                ':sid' => $student_id,
                ':title' => $title,
                ':desc' => $description,
                ':pts' => $points
            ]);
            
            $_SESSION['success'] = "Đã giao nhiệm vụ thành công!";
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    // Quay lại trang quản lý bé
    header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
    exit();
}
?>