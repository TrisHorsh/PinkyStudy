<?php
// config/db_connect.php

$host = 'localhost';
$db   = 'pinky_study';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Thiết lập DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Các tùy chọn cấu hình cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi dạng Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Lấy dữ liệu dạng mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Dùng Prepare Statement thật (an toàn hơn)
];

try {
    // Khởi tạo kết nối PDO
    $conn = new PDO($dsn, $user, $pass, $options);
    
    // Nếu chạy đến đây nghĩa là kết nối thành công!
} catch (\PDOException $e) {
    // Nếu lỗi, bắt lỗi và hiển thị
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}
?>