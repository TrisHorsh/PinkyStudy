<?php
session_start();
// Nếu đã đăng nhập rồi thì đá về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - PinkyStudy</title>
    <link rel="stylesheet" href="../../assets/css/auth_style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-header">
        <h2>PinkyStudy</h2>
        <p>Đăng nhập để quản lý và học tập</p>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            ✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form action="../../actions/auth_login.php" method="POST">
        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username" required placeholder="Nhập username của bạn">
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
        </div>

        <button type="submit" name="login_btn" class="btn-auth btn-primary">Đăng Nhập</button>
    </form>

    <div class="auth-footer">
        Phụ huynh chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </div>
</div>

</body>
</html>