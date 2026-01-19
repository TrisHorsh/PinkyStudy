<?php
// actions/task_submit.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_task_btn'])) {
    
    $student_id = $_SESSION['user_id'];
    $task_id = $_POST['task_id'];
    $proof_text = $_POST['proof_text'] ?? '';
    
    // 1. Xử lý file upload
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
        
        $target_dir = "../uploads/proofs/";
        
        // Tạo tên file mới để tránh trùng: timestamp_tên_gốc
        $filename = time() . '_' . basename($_FILES["proof_file"]["name"]);
        $target_file = $target_dir . $filename;
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Kiểm tra loại file (chỉ cho phép ảnh, pdf, doc)
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array($fileType, $allowed)) {
            $_SESSION['error'] = "Chỉ chấp nhận file ảnh hoặc tài liệu (Word, PDF).";
            header("Location: ../pages/student/do_task.php?task_id=$task_id");
            exit();
        }

        // Di chuyển file từ bộ nhớ tạm vào thư mục uploads
        if (move_uploaded_file($_FILES["proof_file"]["tmp_name"], $target_file)) {
            
            // 2. Cập nhật CSDL
            try {
                // Chỉ cập nhật nếu nhiệm vụ đó thuộc về học sinh này
                $sql = "UPDATE assigned_tasks 
                        SET status = 'submitted', 
                            proof_file = :file, 
                            proof_text = :text, 
                            submitted_at = NOW() 
                        WHERE id = :id AND student_id = :sid";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':file' => $filename,
                    ':text' => $proof_text,
                    ':id' => $task_id,
                    ':sid' => $student_id
                ]);

                $_SESSION['success'] = "Nộp bài thành công! Hãy chờ bố mẹ chấm điểm nhé.";
                header("Location: ../pages/student/dashboard.php");
                exit();

            } catch (PDOException $e) {
                $_SESSION['error'] = "Lỗi Database: " . $e->getMessage();
            }

        } else {
            $_SESSION['error'] = "Có lỗi khi tải file lên server.";
        }

    } else {
        $_SESSION['error'] = "Vui lòng chọn file để nộp.";
        header("Location: ../pages/student/do_task.php?task_id=$task_id");
        exit();
    }
}
?>