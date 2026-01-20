<?php
// pages/parent/dashboard.php
require_once '../../config/db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$parent_id = $_SESSION['user_id'];

// Lấy danh sách con
$stmt = $conn->prepare("SELECT * FROM users WHERE parent_id = :pid AND role = 'student'");
$stmt->execute([':pid' => $parent_id]);
$children = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="dashboard-title">Tổng quan</h2>
</div>

<div class="toggle-box">
    <div class="toggle-header" onclick="toggleCreateForm(this)">
        <span><i class="fas fa-user-plus"></i> &nbsp; Thêm tài khoản học sinh mới</span>
        <span id="toggleIcon"><i class="fas fa-chevron-down"></i></span>
    </div>
    
    <div id="createStudentForm" class="toggle-content">
        <form action="../../actions/parent_create_student.php" method="POST">
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;">Họ tên bé:</label>
                    <input type="text" name="fullname" required class="form-control" placeholder="Ví dụ: Bé Bông">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;">Tên đăng nhập:</label>
                    <input type="text" name="username" required class="form-control" placeholder="login123">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;">Mật khẩu:</label>
                    <input type="text" name="password" required class="form-control" placeholder="***">
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" name="create_student_btn" class="btn btn-success">
                    <i class="fas fa-check"></i> Tạo tài khoản
                </button>
            </div>
        </form>
    </div>
</div>

<h3 style="color: #495057; margin-bottom: 20px;">
    <i class="fas fa-users"></i> Danh sách các bé
</h3>

<?php if (count($children) > 0): ?>
    <div class="student-grid">
        <?php foreach ($children as $child): ?>
        <div class="student-card">
            <div class="student-card-header">
                <div class="student-avatar">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 class="student-name"><?php echo htmlspecialchars($child['full_name']); ?></h3>
                <span class="student-username">@<?php echo htmlspecialchars($child['username']); ?></span>
            </div>
            
            <div class="student-card-body">
                <div class="point-display">
                    <i class="fas fa-star" style="color: #ffc107;"></i> <?php echo $child['current_points']; ?> Điểm
                </div>
                <p style="color: #6c757d; font-size: 0.9em; margin: 0;">
                    Tích cực hoàn thành nhiệm vụ để nhận quà nhé!
                </p>
            </div>

            <div class="student-card-footer">
                <a href="manage_student.php?student_id=<?php echo $child['id']; ?>" class="btn-manage">
                    <i class="fas fa-cog"></i> Quản lý & Giao việc
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning" style="text-align: center; background: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
        <i class="fas fa-info-circle"></i> Chưa có tài khoản học sinh nào. Hãy tạo tài khoản cho bé ở mục trên.
    </div>
<?php endif; ?>

<script>
    // Hàm ẩn hiện form tạo tài khoản
    function toggleCreateForm(header) {
        var content = document.getElementById('createStudentForm');
        var icon = document.getElementById('toggleIcon');
        
        // Toggle class 'show' để kích hoạt animation/display
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            icon.innerHTML = '<i class="fas fa-chevron-down"></i>';
        } else {
            content.classList.add('show');
            icon.innerHTML = '<i class="fas fa-chevron-up"></i>';
        }
    }
</script>

</body>
</html>