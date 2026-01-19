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
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .register-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #218838; }
        .error { color: red; font-size: 14px; text-align: center; margin-bottom: 10px; }
        .login-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .login-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>

<div class="register-container">
    <h2>Đăng ký Phụ huynh</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="../../actions/auth_register.php" method="POST">
        <div class="form-group">
            <label for="fullname">Họ và tên phụ huynh:</label>
            <input type="text" id="fullname" name="fullname" required placeholder="Ví dụ: Nguyễn Văn A">
        </div>
        
        <div class="form-group">
            <label for="username">Tên đăng nhập:</label>
            <input type="text" id="username" name="username" required placeholder="Nhập tên đăng nhập">
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
        </div>

        <div class="form-group">
            <label for="confirm_password">Nhập lại mật khẩu:</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Nhập lại mật khẩu">
        </div>

        <button type="submit" name="register_btn">Đăng ký ngay</button>
    </form>

    <div class="login-link">
        Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>
    </div>
</div>

</body>
</html>