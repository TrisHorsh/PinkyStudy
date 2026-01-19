<?php
// actions/gift_add.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_gift_btn'])) {
    
    $parent_id = $_SESSION['user_id'];
    $name = $_POST['gift_name'];
    $cost = intval($_POST['point_cost']);
    
    // Lấy ID học sinh để quay về (nếu có)
    $return_student_id = $_POST['return_student_id'] ?? 0;

    if (isset($_FILES['gift_image']) && $_FILES['gift_image']['error'] == 0) {
        $target_dir = "../uploads/gifts/";
        $filename = time() . '_' . basename($_FILES["gift_image"]["name"]);
        $target_file = $target_dir . $filename;
        
        move_uploaded_file($_FILES["gift_image"]["tmp_name"], $target_file);

        $stmt = $conn->prepare("INSERT INTO gifts (parent_id, gift_name, point_cost, gift_image) VALUES (:pid, :name, :cost, :img)");
        $stmt->execute([':pid' => $parent_id, ':name' => $name, ':cost' => $cost, ':img' => $filename]);

        $_SESSION['success'] = "Đã thêm món quà mới!";
    } else {
        $_SESSION['error'] = "Vui lòng chọn ảnh cho món quà.";
    }
    
    // ĐIỀU HƯỚNG THÔNG MINH
    if ($return_student_id > 0) {
        header("Location: ../pages/parent/manage_student.php?student_id=" . $return_student_id);
    } else {
        header("Location: ../pages/parent/shop.php"); // Fallback cho trang cũ
    }
    exit();
}
?>