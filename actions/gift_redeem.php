<?php
// actions/gift_redeem.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_btn'])) {
    
    $student_id = $_SESSION['user_id'];
    $gift_id = $_POST['gift_id'];
    $point_cost = intval($_POST['point_cost']);

    // 1. Kiểm tra lại xem có đủ điểm không (Validate phía server)
    $stmt = $conn->prepare("SELECT current_points FROM users WHERE id = :id");
    $stmt->execute([':id' => $student_id]);
    $current_points = $stmt->fetchColumn();

    if ($current_points < $point_cost) {
        $_SESSION['error'] = "Con chưa đủ điểm để đổi món này.";
        header("Location: ../pages/student/shop.php");
        exit();
    }

    // 2. Tạo yêu cầu đổi quà
    $sql = "INSERT INTO redemptions (student_id, gift_id, points_spent, status) 
            VALUES (:sid, :gid, :cost, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':sid' => $student_id,
        ':gid' => $gift_id,
        ':cost' => $point_cost
    ]);

    if ($result) {
        $_SESSION['success'] = "Đã gửi yêu cầu đổi quà! Hãy chờ bố mẹ đồng ý nhé.";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra.";
    }

    header("Location: ../pages/student/shop.php");
    exit();
}
?>