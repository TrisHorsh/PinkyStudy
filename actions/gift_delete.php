<?php
// actions/gift_delete.php
session_start();
require_once '../config/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Lấy student_id từ URL nếu có
    $student_id = $_GET['student_id'] ?? 0;

    $stmt = $conn->prepare("DELETE FROM gifts WHERE id = :id AND parent_id = :pid");
    $stmt->execute([':id' => $id, ':pid' => $_SESSION['user_id']]);
    
    $_SESSION['success'] = "Đã xóa món quà.";
    
    if ($student_id > 0) {
        header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
    } else {
        header("Location: ../pages/parent/shop.php");
    }
    exit();
}
?>