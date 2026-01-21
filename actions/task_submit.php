<?php
// actions/task_submit.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_task_btn'])) {
    
    $student_id = $_SESSION['user_id'];
    $task_id = $_POST['task_id'];
    $proof_text = $_POST['proof_text'] ?? '';
    
    // Kiểm tra xem có file nào được upload không
    if (isset($_FILES['proof_files']) && count($_FILES['proof_files']['name']) > 0) {
        
        $target_dir = "../uploads/proofs/";
        $uploaded_files = []; // Mảng chứa tên các file đã upload thành công
        $errors = [];

        $count = count($_FILES['proof_files']['name']);

        // Vòng lặp xử lý từng file
        for ($i = 0; $i < $count; $i++) {
            $origin_name = $_FILES['proof_files']['name'][$i];
            $tmp_name    = $_FILES['proof_files']['tmp_name'][$i];
            $error       = $_FILES['proof_files']['error'][$i];

            if ($error == 0) {
                // Tạo tên file mới: timestamp_index_tên_gốc
                $filename = time() . "_{$i}_" . basename($origin_name);
                $target_file = $target_dir . $filename;
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Validate loại file
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp3', 'wav', 'm4a', 'ogg'];
                if (in_array($fileType, $allowed)) {
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $uploaded_files[] = $filename; // Thêm vào danh sách thành công
                    } else {
                        $errors[] = "Lỗi khi lưu file: $origin_name";
                    }
                } else {
                    $errors[] = "File '$origin_name' không đúng định dạng.";
                }
            }
        }

        // Nếu có ít nhất 1 file thành công -> Lưu vào DB
        if (count($uploaded_files) > 0) {
            try {
                // Chuyển mảng tên file thành chuỗi JSON
                $files_json = json_encode($uploaded_files);

                $sql = "UPDATE assigned_tasks 
                        SET status = 'submitted', 
                            proof_file = :files, 
                            proof_text = :text, 
                            submitted_at = NOW() 
                        WHERE id = :id AND student_id = :sid";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':files' => $files_json,
                    ':text' => $proof_text,
                    ':id' => $task_id,
                    ':sid' => $student_id
                ]);

                $_SESSION['success'] = "Nộp bài thành công (" . count($uploaded_files) . " file)!";
                header("Location: ../pages/student/dashboard.php");
                exit();

            } catch (PDOException $e) {
                $_SESSION['error'] = "Lỗi Database: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Không có file nào được tải lên thành công. " . implode(" ", $errors);
            header("Location: ../pages/student/do_task.php?task_id=$task_id");
            exit();
        }

    } else {
        $_SESSION['error'] = "Vui lòng chọn ít nhất 1 file.";
        header("Location: ../pages/student/do_task.php?task_id=$task_id");
        exit();
    }
}
?>