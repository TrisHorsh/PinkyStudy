<?php
// actions/gift_approve.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $redemption_id = $_POST['redemption_id'];
    $student_id = $_POST['student_id'];
    $cost = intval($_POST['cost']);
    $action = $_POST['action'];

    try {
        $conn->beginTransaction();

        if ($action == 'approve') {
            // Kiểm tra điểm
            $stmt = $conn->prepare("SELECT current_points FROM users WHERE id = :id");
            $stmt->execute([':id' => $student_id]);
            $current_points = $stmt->fetchColumn();

            if ($current_points >= $cost) {
                // Trừ điểm
                $conn->prepare("UPDATE users SET current_points = current_points - :cost WHERE id = :id")
                     ->execute([':cost' => $cost, ':id' => $student_id]);
                
                // Cập nhật trạng thái
                $conn->prepare("UPDATE redemptions SET status = 'approved' WHERE id = :rid")
                     ->execute([':rid' => $redemption_id]);
                
                $_SESSION['success'] = "Đã duyệt đổi quà! Bé bị trừ $cost sao.";
            } else {
                $_SESSION['error'] = "Không thể duyệt: Bé không còn đủ điểm.";
            }

        } else {
            // Từ chối
            $conn->prepare("UPDATE redemptions SET status = 'rejected' WHERE id = :rid")
                 ->execute([':rid' => $redemption_id]);
            $_SESSION['success'] = "Đã từ chối yêu cầu.";
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    // LUÔN QUAY VỀ TRANG QUẢN LÝ BÉ
    header("Location: ../pages/parent/manage_student.php?student_id=" . $student_id);
    exit();
}
?>