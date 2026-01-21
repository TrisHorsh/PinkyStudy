<?php
// actions/gift_request_add.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $student_id = $_SESSION['user_id'];
    $gift_name = trim($_POST['gift_name']);
    $gift_desc = trim($_POST['gift_desc']);

    if (!empty($gift_name)) {
        try {
            $sql = "INSERT INTO gift_requests (student_id, gift_name, gift_desc) VALUES (:sid, :name, :desc)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':sid' => $student_id,
                ':name' => $gift_name,
                ':desc' => $gift_desc
            ]);
            $_SESSION['success'] = "Đã gửi điều ước thành công! Hãy chờ bố mẹ xem nhé.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Vui lòng nhập tên món quà.";
    }
}
header("Location: ../pages/student/shop.php");
exit();
?>