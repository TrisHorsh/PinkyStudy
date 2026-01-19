<?php
// Kiểm tra nếu chưa start session thì start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy đường dẫn gốc của website để link không bị lỗi khi include
$base_url = "http://localhost/PinkyStudy"; 
// Lưu ý: Nếu bạn đặt folder tên khác, hãy sửa lại dòng trên
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PinkyStudy</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f6f9; }
        .navbar { background-color: #343a40; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .navbar a:hover { color: #ccc; }
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">PinkyStudy <b>(Phụ huynh)</b></div>
    <div class="menu">
        <span style="margin-left: 20px; color: #ffc107;">Hi, <?php echo $_SESSION['fullname'] ?? 'User'; ?></span>
        <a href="<?php echo $base_url; ?>/actions/auth_logout.php" style="color: #ff6b6b;">Đăng xuất</a>
    </div>
</div>

<div class="container">
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>