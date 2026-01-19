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

<style>
    .dashboard-container { max-width: 1000px; margin: 0 auto; }
    
    /* Style cho phần Accordion (Form tạo tài khoản) */
    .toggle-header {
        background: #007bff; color: white; padding: 15px; border-radius: 8px;
        cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        font-weight: bold; margin-bottom: 10px;
    }
    .toggle-content { display: block; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; background: white; margin-top: -8px; margin-bottom: 30px; }
    .hidden { display: none; }

    /* Style cho Card học sinh */
    .student-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .student-card {
        background: white; border-radius: 12px; overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s;
        border-top: 5px solid #28a745;
    }
    .student-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    .card-body { padding: 20px; text-align: center; }
    .avatar-circle {
        width: 60px; height: 60px; background: #e9ecef; border-radius: 50%;
        margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: #555;
    }
    .stat-badge { background: #ffc107; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 0.9em; margin-top: 5px; display: inline-block;}
</style>

<div class="dashboard-container">
    
    <div>
        <div class="toggle-header" onclick="toggleSection('createStudentForm', this)">
            <span>➕ Thêm tài khoản học sinh mới</span>
            <span id="icon-createStudentForm">▼</span>
        </div>
        
        <div id="createStudentForm" class="toggle-content">
            <form action="../../actions/parent_create_student.php" method="POST">
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label>Họ tên bé:</label>
                        <input type="text" name="fullname" required class="form-control" style="width: 100%; padding: 10px;" placeholder="Ví dụ: Bé Bông">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label>Username:</label>
                        <input type="text" name="username" required class="form-control" style="width: 100%; padding: 10px;" placeholder="login123">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label>Mật khẩu:</label>
                        <input type="text" name="password" required class="form-control" style="width: 100%; padding: 10px;" placeholder="***">
                    </div>
                </div>
                <button type="submit" name="create_student_btn" class="btn btn-primary" style="margin-top: 15px;">Tạo ngay</button>
            </form>
        </div>
    </div>

    <h3 style="border-left: 5px solid #007bff; padding-left: 10px; margin-bottom: 20px;">Danh sách các bé</h3>
    
    <?php if (count($children) > 0): ?>
        <div class="student-grid">
            <?php foreach ($children as $child): ?>
            <div class="student-card">
                <div class="card-body">
                    <div class="avatar-circle">🎓</div>
                    <h3 style="margin: 5px 0;"><?php echo htmlspecialchars($child['full_name']); ?></h3>
                    <p style="color: #666; font-size: 0.9em;">User: <?php echo htmlspecialchars($child['username']); ?></p>
                    
                    <div class="stat-badge">
                        <?php echo $child['current_points']; ?> ⭐
                    </div>
                    <br><br>
                    
                    <a href="manage_student.php?student_id=<?php echo $child['id']; ?>" class="btn btn-primary" style="width: 100%; display: block; box-sizing: border-box;">
                        ⚙️ Quản lý bé này
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #777;">Chưa có tài khoản nào. Hãy tạo tài khoản cho bé ở trên.</p>
    <?php endif; ?>

</div>

<script>
    function toggleSection(id, header) {
        var content = document.getElementById(id);
        var icon = header.querySelector('span:last-child');
        
        if (content.style.display === "none" || content.classList.contains('hidden')) {
            content.style.display = "block";
            content.classList.remove('hidden');
            icon.innerText = "▼";
        } else {
            content.style.display = "none";
            icon.innerText = "▶"; // Mũi tên thu gọn
        }
    }
</script>

</body>
</html>