<?php
session_start();
require_once '../config/db_connect.php'; // Kết nối CSDL bằng PDO

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_btn'])) {
    
    // 1. Lấy dữ liệu từ form và làm sạch (Sanitize)
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Validate dữ liệu cơ bản
    if (empty($fullname) || empty($username) || empty($password)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin!";
        header("Location: ../pages/auth/register.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Mật khẩu nhập lại không khớp!";
        header("Location: ../pages/auth/register.php");
        exit();
    }

    try {
        // 3. Kiểm tra xem tên đăng nhập đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Tên đăng nhập này đã được sử dụng!";
            header("Location: ../pages/auth/register.php");
            exit();
        }

        // 4. Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 5. Thêm người dùng mới vào CSDL
        // Lưu ý: role mặc định là 'parent', parent_id là NULL
        $sql = "INSERT INTO users (username, password, full_name, role, parent_id) 
                VALUES (:username, :password, :fullname, 'parent', NULL)";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password,
            ':fullname' => $fullname
        ]);

        if ($result) {
            // Đăng ký thành công -> Chuyển sang trang đăng nhập
            $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
            header("Location: ../pages/auth/login.php");
            exit();
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra, vui lòng thử lại.";
            header("Location: ../pages/auth/register.php");
            exit();
        }

    } catch (PDOException $e) {
        // Bắt lỗi hệ thống
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../pages/auth/register.php");
        exit();
    }

} else {
    // Nếu truy cập trực tiếp file này mà không submit form
    header("Location: ../pages/auth/register.php");
    exit();
}
?>