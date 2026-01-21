<?php
// includes/header_student.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// $base_url = "http://localhost/PinkyStudy"; 
// Tự động lấy giao thức (http/https) và tên miền/IP hiện tại
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST']; // Sẽ tự lấy là localhost hoặc 192.168.1.x
$base_url = "$protocol://$host/PinkyStudy";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Góc Học Tập - PinkyStudy</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/student_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="navbar">
    <div class="brand"><i class="fas fa-rabbit"></i> PinkyStudy</div>
    
    <button class="navbar-toggle" onclick="toggleStudentMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="menu" id="studentMenu">
        <a href="<?php echo $base_url; ?>/pages/student/dashboard.php" class="nav-item">
            <i class="fas fa-gamepad"></i> Nhiệm vụ
        </a>
        <a href="<?php echo $base_url; ?>/pages/student/shop.php" class="nav-item">
            <i class="fas fa-store"></i> Đổi quà
        </a>
        
        <span class="star-badge">
            ⭐ <?php echo $_SESSION['current_points'] ?? 0; ?>
        </span>
        
        <a href="<?php echo $base_url; ?>/actions/auth_logout.php" title="Thoát" class="nav-item logout-btn">
            <i class="fas fa-power-off"></i> Thoát
        </a>
    </div>
</div>

<div class="container">

<script>
    function toggleStudentMenu() {
        document.getElementById('studentMenu').classList.toggle('active');
    }
</script>