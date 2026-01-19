<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$base_url = "http://localhost/PinkyStudy"; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Góc Học Tập - PinkyStudy</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #e0f7fa; margin: 0; }
        .navbar { background-color: #00bcd4; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar a { color: white; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .container { padding: 20px; max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 20px; cursor: pointer; text-decoration: none; color: white; display: inline-block; font-weight: bold; }
        .btn-action { background-color: #ff9800; } /* Màu cam cho hành động */
        .btn-success { background-color: #4caf50; }
        .star-badge { background: #ffeb3b; color: #d32f2f; padding: 5px 10px; border-radius: 10px; font-weight: bold; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">🐰 PinkyStudy</div>
    <div class="menu">
        <a href="<?php echo $base_url; ?>/pages/student/dashboard.php">Nhiệm vụ</a>
        <a href="<?php echo $base_url; ?>/pages/student/shop.php">Đổi quà</a>
        <span class="star-badge">⭐ <?php echo $_SESSION['current_points'] ?? 0; ?> điểm</span>
        <span style="margin-left: 10px;">Hi, <?php echo $_SESSION['fullname']; ?></span>
        <a href="<?php echo $base_url; ?>/actions/auth_logout.php" style="font-weight: normal; font-size: 0.9em;">(Thoát)</a>
    </div>
</div>
<div class="container">