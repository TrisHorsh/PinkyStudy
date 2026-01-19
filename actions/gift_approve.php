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
                // 1. Trừ điểm
                $conn->prepare("UPDATE users SET current_points = current_points - :cost WHERE id = :id")
                     ->execute([':cost' => $cost, ':id' => $student_id]);
                
                // 2. SINH MÃ VOUCHER (MỚI)
                // Tạo chuỗi ngẫu nhiên 6 ký tự viết hoa
                $code = 'GIFT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

                // 3. Cập nhật trạng thái + Lưu mã
                $sqlApprove = "UPDATE redemptions 
                               SET status = 'approved', voucher_code = :code 
                               WHERE id = :rid";
                $conn->prepare($sqlApprove)->execute([':code' => $code, ':rid' => $redemption_id]);
                
                $_SESSION['success'] = "Đã duyệt! Mã voucher [$code] đã được gửi cho bé.";
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