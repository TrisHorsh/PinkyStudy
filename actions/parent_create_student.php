<?php
// actions/parent_create_student.php
session_start();
require_once '../config/db_connect.php';

// Kiểm tra quyền: Phải là Parent mới được tạo
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    die("Bạn không có quyền thực hiện chức năng này.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_student_btn'])) {
    
    $parent_id = $_SESSION['user_id']; // ID của phụ huynh đang đăng nhập
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Validate
    if (empty($fullname) || empty($username) || empty($password)) {
        $_SESSION['error'] = "Vui lòng nhập đủ thông tin cho bé.";
        header("Location: ../pages/parent/dashboard.php");
        exit();
    }

    try {
        // 2. Kiểm tra trùng username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Tên đăng nhập '$username' đã tồn tại. Vui lòng chọn tên khác.";
            header("Location: ../pages/parent/dashboard.php");
            exit();
        }

        // 3. Tạo tài khoản
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password, full_name, role, parent_id, current_points) 
                VALUES (:username, :password, :fullname, 'student', :pid, 0)";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password,
            ':fullname' => $fullname,
            ':pid'      => $parent_id
        ]);

        if ($result) {
            $_SESSION['success'] = "Đã tạo tài khoản cho bé '$fullname' thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi tạo tài khoản.";
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
    }

    // Quay lại dashboard
    header("Location: ../pages/parent/dashboard.php");
    exit();

} else {
    header("Location: ../pages/parent/dashboard.php");
    exit();
}
?>