<?php
session_start();
// Nếu đã đăng nhập thì không cho vào trang đăng ký nữa
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
    <title>Đăng ký Phụ huynh - PinkyStudy</title>
    <link rel="stylesheet" href="../../assets/css/auth_style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-header">
        <h2>Đăng ký Phụ huynh</h2>
        <p>Tạo tài khoản để đồng hành cùng con</p>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="../../actions/auth_register.php" method="POST">
        <div class="form-group">
            <label for="fullname">Họ và tên phụ huynh</label>
            <input type="text" id="fullname" name="fullname" required placeholder="Ví dụ: Nguyễn Văn A">
        </div>
        
        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input type="text" id="username" name="username" required placeholder="Chọn tên đăng nhập dễ nhớ">
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
        </div>

        <div class="form-group">
            <label for="confirm_password">Nhập lại mật khẩu</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Xác nhận lại mật khẩu">
        </div>

        <button type="submit" name="register_btn" class="btn-auth btn-success">Đăng ký ngay</button>
    </form>

    <div class="auth-footer">
        Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>
    </div>
</div>

</body>
</html>