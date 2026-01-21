<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base_url = "http://localhost/PinkyStudy"; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PinkyStudy - Phụ huynh</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/parent_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="navbar">
    <div class="brand">
        <i class="fas fa-user-tie"></i> PinkyStudy <span>(Phụ huynh)</span>
    </div>
    
    <button class="navbar-toggle" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="menu" id="navbarMenu">
        <span class="user-welcome">Xin chào, <b><?php echo $_SESSION['fullname'] ?? 'User'; ?></b></span>
        <a href="<?php echo $base_url; ?>/pages/parent/dashboard.php" class="nav-link">
            <i class="fas fa-home"></i> Trang chủ
        </a>
        <a href="<?php echo $base_url; ?>/actions/auth_logout.php" class="nav-link logout-link">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</div>

<div class="container">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

<script>
    // Script để bật tắt menu trên mobile
    function toggleMenu() {
        document.getElementById('navbarMenu').classList.toggle('active');
    }
</script>