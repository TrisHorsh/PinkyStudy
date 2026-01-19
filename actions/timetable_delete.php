<?php
session_start();
require_once '../config/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $student_id = $_GET['student_id'];
    
    // Xóa
    $stmt = $conn->prepare("DELETE FROM timetable WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    $_SESSION['success'] = "Đã xóa môn học.";
    header("Location: ../pages/parent/timetable.php?student_id=" . $student_id);
    exit();
}
?>