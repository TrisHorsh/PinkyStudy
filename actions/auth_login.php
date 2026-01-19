<?php
session_start();
require_once '../config/db_connect.php'; // Kết nối PDO

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_btn'])) {

    // 1. Lấy dữ liệu
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 2. Validate cơ bản
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
        header("Location: ../pages/auth/login.php");
        exit();
    }

    try {
        // 3. Tìm user trong CSDL
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // 4. Kiểm tra mật khẩu
        // Nếu tìm thấy user VÀ mật khẩu trùng khớp
        if ($user && password_verify($password, $user['password'])) {
            
            // --- ĐĂNG NHẬP THÀNH CÔNG ---
            
            // Lưu thông tin vào session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Nếu là học sinh, lưu thêm parent_id để tiện truy vấn sau này
            if ($user['role'] == 'student') {
                $_SESSION['parent_id'] = $user['parent_id'];
            }

            // Điều hướng dựa trên vai trò (Role)
            if ($user['role'] == 'parent') {
                header("Location: ../pages/parent/dashboard.php");
            } else {
                header("Location: ../pages/student/dashboard.php");
            }
            exit();

        } else {
            // --- ĐĂNG NHẬP THẤT BẠI ---
            $_SESSION['error'] = "Tên đăng nhập hoặc mật khẩu không đúng!";
            header("Location: ../pages/auth/login.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../pages/auth/login.php");
        exit();
    }

} else {
    // Truy cập trực tiếp không qua form
    header("Location: ../pages/auth/login.php");
    exit();
}
?>