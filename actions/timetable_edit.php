<?php
// actions/timetable_edit.php
session_start();
require_once '../config/db_connect.php';

// Kiểm tra quyền
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../pages/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_tkb_btn'])) {
    
    $id = $_POST['tkb_id'];
    $student_id = $_POST['student_id'];
    $subject_name = trim($_POST['subject_name']);

    if (!empty($subject_name) && !empty($id)) {
        try {
            // Cập nhật tên môn học
            $sql = "UPDATE timetable SET subject_name = :subj WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':subj' => $subject_name, ':id' => $id]);
            
            $_SESSION['success'] = "Đã cập nhật môn học thành công!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Tên môn học không được để trống.";
    }

    // Quay lại trang TKB
    header("Location: ../pages/parent/timetable.php?student_id=" . $student_id);
    exit();
}
?>