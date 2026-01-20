<?php
// includes/header_student.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$base_url = "http://localhost/PinkyStudy"; 
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
    <div class="menu">
        <a href="<?php echo $base_url; ?>/pages/student/dashboard.php">Nhiệm vụ</a>
        <a href="<?php echo $base_url; ?>/pages/student/shop.php">Đổi quà</a>
        
        <span class="star-badge">
            ⭐ <?php echo $_SESSION['current_points'] ?? 0; ?>
        </span>
        
        <a href="<?php echo $base_url; ?>/actions/auth_logout.php" title="Thoát">
            <i class="fas fa-power-off"></i>
        </a>
    </div>
</div>

<div class="container">