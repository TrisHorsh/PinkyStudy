<?php
// index.php
session_start(); // Khởi động session

// Kiểm tra xem người dùng đã đăng nhập chưa
if (isset($_SESSION['user_id'])) {
    
    $role = $_SESSION['role'] ?? ''; // Lấy vai trò (nếu không có thì trả về rỗng)

    if ($role === 'parent') {
        // Nếu là phụ huynh
        header("Location: pages/parent/dashboard.php");
        exit();
    } elseif ($role === 'student') {
        // Nếu là học sinh
        header("Location: pages/student/dashboard.php");
        exit();
    } else {
        // Lỗi vai trò không hợp lệ -> Đăng xuất
        header("Location: actions/auth_logout.php");
        exit();
    }

} else {
    // Chưa đăng nhập -> Chuyển về trang Login
    header("Location: pages/auth/login.php");
    exit();
}
?>  