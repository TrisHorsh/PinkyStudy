<?php
// actions/auth_logout.php
session_start();

// 1. Xóa sạch các biến trong session
session_unset();

// 2. Hủy phiên làm việc
session_destroy();

// 3. Tự động lấy đường dẫn gốc (Dynamic URL)
// Giúp chạy được cả trên localhost, IP mạng LAN (điện thoại) hoặc Hosting
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host/PinkyStudy";

// 4. Điều hướng về trang Đăng nhập
header("Location: $base_url/pages/auth/login.php");
exit();
?>