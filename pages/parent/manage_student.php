<?php
// pages/parent/manage_student.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$parent_id = $_SESSION['user_id'];
$student_id = $_GET['student_id'] ?? 0;

// --- KÍCH HOẠT TỰ ĐỘNG GIAO BÀI HÀNG NGÀY ---
checkAndCreateDailyTasks($conn, $student_id, $parent_id);

// 1. Lấy thông tin bé
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id AND parent_id = :pid");
$stmt->execute([':id' => $student_id, ':pid' => $parent_id]);
$student = $stmt->fetch();

if (!$student) die("Không tìm thấy học sinh này.");

// 2. Lấy bài cần chấm
$stmtGrade = $conn->prepare("SELECT * FROM assigned_tasks WHERE student_id = :sid AND status = 'submitted' ORDER BY submitted_at ASC");
$stmtGrade->execute([':sid' => $student_id]);
$pending_tasks = $stmtGrade->fetchAll();
$pending_count = count($pending_tasks);

// 3. Lấy yêu cầu đổi quà Pending
$stmtRedeem = $conn->prepare("SELECT r.*, g.gift_name, g.gift_image 
                              FROM redemptions r
                              JOIN gifts g ON r.gift_id = g.id
                              WHERE r.student_id = :sid AND r.status = 'pending'");
$stmtRedeem->execute([':sid' => $student_id]);
$pending_redemptions = $stmtRedeem->fetchAll();
$redeem_count = count($pending_redemptions);

// 4. Lấy dữ liệu Cấu hình Nhiệm vụ
// Template
$stmtTemplates = $conn->prepare("SELECT * FROM task_templates WHERE creator_id = :pid ORDER BY created_at DESC");
$stmtTemplates->execute([':pid' => $parent_id]);
$templates = $stmtTemplates->fetchAll();

// Daily Configs
$daily_configs = array_filter($templates, function($t) { return $t['task_type'] === 'daily'; });

// History
$stmtHistory = $conn->prepare("SELECT * FROM assigned_tasks WHERE student_id = :sid ORDER BY created_at DESC LIMIT 20");
$stmtHistory->execute([':sid' => $student_id]);
$history_tasks = $stmtHistory->fetchAll();

// Gifts
$stmtGifts = $conn->prepare("SELECT * FROM gifts WHERE parent_id = :pid ORDER BY created_at DESC");
$stmtGifts->execute([':pid' => $parent_id]);
$gifts = $stmtGifts->fetchAll();

include '../../includes/header.php';
?>

<div class="manage-container">
    <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>

    <div class="student-info-bar">
        <div class="student-info-left">
            <div class="student-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="student-detail">
                <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                <span>Tên đăng nhập: @<?php echo htmlspecialchars($student['username']); ?></span>
                <div style="margin-top: 10px;">
                    <a href="stats.php?student_id=<?php echo $student_id; ?>" class="btn btn-warning btn-sm" style="font-size: 0.9em;">
                        <i class="fas fa-chart-line"></i> Xem báo cáo thống kê
                    </a>
                </div>
            </div>
        </div>
        <div class="student-stats-box">
            <span style="display: block; font-size: 0.9em; margin-bottom: 5px;">Tích lũy hiện tại</span>
            <span style="font-size: 2.2em; font-weight: 800; color: #ffeb3b;">
                <?php echo $student['current_points']; ?> <i class="fas fa-star"></i>
            </span>
        </div>
    </div>

    <?php if ($pending_count > 0): ?>
    <div class="section-box" style="border-top: 4px solid #dc3545;">
        <div class="section-header" style="background: #fff5f5; color: #dc3545; cursor: default;">
            <span><i class="fas fa-bell"></i> Cần chấm điểm ngay <span class="count-badge"><?php echo $pending_count; ?></span></span>
        </div>
        <div class="section-body always-show">
            <table class="table">
                <?php foreach ($pending_tasks as $task): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                        <div style="font-size: 0.9em; color: #666;">
                            Nộp lúc: <?php echo date('H:i d/m', strtotime($task['submitted_at'])); ?>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <a href="grading.php?task_id=<?php echo $task['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-marker"></i> Chấm bài
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-box">
        <div class="section-header" onclick="toggleBox('taskConfigBody', this)">
            <span><i class="fas fa-tasks"></i> Quản lý & Giao nhiệm vụ</span>
            <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
        </div>
        
        <div id="taskConfigBody" class="section-body show">
            <div class="task-config-grid">
                
                <div>
                    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #90caf9;">
                        <h4 style="margin-top: 0; color: #0d47a1; margin-bottom: 15px;">
                            <i class="fas fa-pen-fancy"></i> Soạn nhiệm vụ mới
                        </h4>
                        
                        <form action="../../actions/task_add.php" method="POST" id="taskForm">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 0.9em;">Tên nhiệm vụ:</label>
                                <input type="text" name="title" id="inpTitle" required class="form-control" placeholder="VD: Làm bài tập Toán">
                            </div>
                            
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 0.9em;">Mô tả:</label>
                                <textarea name="description" id="inpDesc" rows="2" class="form-control" placeholder="Hướng dẫn bé làm gì..."></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 15px;">
                                <div style="flex: 1;">
                                    <label style="font-weight: 600; font-size: 0.9em;">Điểm thưởng:</label>
                                    <input type="number" name="points" id="inpPoints" value="10" required class="form-control">
                                </div>
                                <div style="flex: 1;">
                                    <label style="font-weight: 600; font-size: 0.9em;">Loại:</label>
                                    <select name="task_type" id="inpType" class="form-control">
                                        <option value="challenge">Thử thách (1 lần)</option>
                                        <option value="daily">Hàng ngày (Lặp lại)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top: 15px; display: flex; gap: 10px;">
                                <button type="submit" name="add_task_btn" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-paper-plane"></i> Giao ngay
                                </button>
                                <button type="submit" formaction="../../actions/template_add.php" name="add_template_btn" class="btn btn-secondary" title="Lưu lại để dùng lần sau">
                                    <i class="fas fa-save"></i> Lưu mẫu
                                </button>
                            </div>
                        </form>
                    </div>

                    <h4 style="margin-bottom: 10px; color: #495057;"><i class="fas fa-folder-open"></i> Ngân hàng mẫu có sẵn</h4>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if(count($templates) > 0): ?>
                            <?php foreach($templates as $tpl): ?>
                            <?php 
                                $jsTitle = htmlspecialchars(json_encode($tpl['title']));
                                $jsDesc  = htmlspecialchars(json_encode($tpl['description']));
                            ?>
                            <div class="list-item" style="border-left: 4px solid #007bff; cursor: pointer;"
                                 onclick="fillTaskForm('<?php echo addslashes($tpl['title']); ?>', '<?php echo addslashes($tpl['description']); ?>', <?php echo $tpl['default_points']; ?>)">
                                
                                <div class="list-item-header">
                                    <strong><?php echo htmlspecialchars($tpl['title']); ?></strong>
                                    <div>
                                        <span class="badge bg-blue"><?php echo $tpl['default_points']; ?> ⭐</span>
                                        <a href="../../actions/template_delete.php?id=<?php echo $tpl['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                           onclick="return confirm('Xóa mẫu này?'); event.stopPropagation();"
                                           class="btn btn-danger btn-sm" style="padding: 2px 6px; margin-left: 5px;">&times;</a>
                                    </div>
                                </div>
                                <div style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($tpl['description']); ?></div>
                                <div style="text-align: right; margin-top: 5px; font-size: 0.85em; color: #007bff; font-weight: 600;">
                                    Sử dụng mẫu này ➔
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999; font-style: italic;">Chưa có mẫu nào.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c8e6c9;">
                        <h4 style="margin-top: 0; color: #2e7d32; margin-bottom: 10px;">
                            <i class="fas fa-sync-alt"></i> Đang tự động giao hàng ngày
                        </h4>
                        <?php if(count($daily_configs) > 0): ?>
                            <?php foreach($daily_configs as $daily): ?>
                            <div class="list-item" style="margin-bottom: 5px; padding: 8px;">
                                <div class="list-item-header">
                                    <span><?php echo htmlspecialchars($daily['title']); ?></span>
                                    <a href="../../actions/template_delete.php?id=<?php echo $daily['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                       onclick="return confirm('Dừng tự động giao nhiệm vụ này?')" 
                                       class="btn btn-danger btn-sm" style="font-size: 0.8em;">Dừng</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; font-size: 0.9em; margin: 0;">Chưa có nhiệm vụ lặp lại.</p>
                        <?php endif; ?>
                    </div>

                    <h4 style="margin-bottom: 10px; color: #495057;"><i class="fas fa-history"></i> Lịch sử nhiệm vụ (Gần đây)</h4>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php if(count($history_tasks) > 0): ?>
                            <?php foreach($history_tasks as $hTask): ?>
                            <div class="list-item">
                                <div class="list-item-header">
                                    <strong><?php echo htmlspecialchars($hTask['title']); ?></strong>
                                    <?php 
                                        if($hTask['status']=='pending') echo '<span class="badge bg-orange">Chưa làm</span>';
                                        elseif($hTask['status']=='submitted') echo '<span class="badge bg-blue">Chờ duyệt</span>';
                                        elseif($hTask['status']=='approved') echo '<span class="badge bg-green">Đã xong</span>';
                                        else echo '<span class="badge bg-red">Làm lại</span>';
                                    ?>
                                </div>
                                <small style="color: #888;">
                                    Giao: <?php echo date('d/m H:i', strtotime($hTask['created_at'])); ?>
                                </small>
                                
                                <?php if($hTask['status'] == 'pending'): ?>
                                    <div style="text-align: right; margin-top: 5px;">
                                        <a href="../../actions/task_delete.php?id=<?php echo $hTask['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                           onclick="return confirm('Xóa nhiệm vụ này?')"
                                           style="color: #dc3545; font-size: 0.9em; text-decoration: none;">
                                           <i class="fas fa-trash"></i> Xóa bỏ
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999;">Chưa có lịch sử.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="section-box">
        <div class="section-header" onclick="toggleBox('rewardConfigBody', this)">
            <span><i class="fas fa-gift"></i> Quản lý quà tặng & Duyệt đổi quà</span>
            <span class="toggle-icon"><i class="fas fa-chevron-right"></i></span>
        </div>

        <div id="rewardConfigBody" class="section-body hidden">
            
            <?php if ($redeem_count > 0): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #856404;">
                    <i class="fas fa-exclamation-circle"></i> Yêu cầu đổi quà mới (<?php echo $redeem_count; ?>)
                </h4>
                <table class="table" style="background: white;">
                    <?php foreach ($pending_redemptions as $req): ?>
                    <tr>
                        <td style="padding: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="../../uploads/gifts/<?php echo $req['gift_image']; ?>" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                <div>
                                    <b><?php echo htmlspecialchars($req['gift_name']); ?></b>
                                    <div style="color: #d63384; font-weight: bold; font-size: 0.9em;">Giá: <?php echo $req['points_spent']; ?> ⭐</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <form action="../../actions/gift_approve.php" method="POST" style="display: inline-block;">
                                <input type="hidden" name="redemption_id" value="<?php echo $req['id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                <input type="hidden" name="cost" value="<?php echo $req['points_spent']; ?>">
                                
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Đồng ý</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Từ chối yêu cầu này?')">Từ chối</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h5 style="margin-top: 0; margin-bottom: 10px;">Thêm món quà vào cửa hàng</h5>
                <form action="../../actions/gift_add.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                    <input type="hidden" name="return_student_id" value="<?php echo $student_id; ?>">
                    <div style="flex: 2; min-width: 200px;">
                        <input type="text" name="gift_name" required placeholder="Tên món quà (VD: Lego, Truyện tranh...)" class="form-control">
                    </div>
                    <div style="width: 120px;">
                        <input type="number" name="point_cost" required placeholder="Số sao" class="form-control">
                    </div>
                    <div style="flex: 1;">
                        <input type="file" name="gift_image" required accept="image/*" class="form-control" style="padding: 5px;">
                    </div>
                    <button type="submit" name="add_gift_btn" class="btn btn-primary">Lưu</button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">
                <?php foreach($gifts as $gift): ?>
                <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; text-align: center; background: white;">
                    <div style="height: 120px; overflow: hidden;">
                        <img src="../../uploads/gifts/<?php echo $gift['gift_image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding: 10px;">
                        <div style="font-weight: bold; font-size: 0.95em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($gift['gift_name']); ?>
                        </div>
                        <div style="color: #d63384; font-weight: bold; margin: 5px 0;">
                            <?php echo $gift['point_cost']; ?> ⭐
                        </div>
                        <a href="../../actions/gift_delete.php?id=<?php echo $gift['id']; ?>&student_id=<?php echo $student_id; ?>" 
                           class="btn btn-danger btn-sm" style="font-size: 0.8em;" onclick="return confirm('Xóa quà này?')">
                           <i class="fas fa-trash"></i> Xóa
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <div class="section-box">
        <div class="section-header" onclick="window.location.href='timetable.php?student_id=<?php echo $student_id; ?>'">
            <span><i class="fas fa-calendar-alt"></i> Cấu hình Thời khóa biểu</span>
            <span style="color: #007bff;">Chỉnh sửa <i class="fas fa-arrow-right"></i></span>
        </div>
    </div>

</div>

<script>
    // Hàm ẩn hiện các section
    function toggleBox(id, header) {
        var content = document.getElementById(id);
        var icon = header.querySelector('.toggle-icon i');
        
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            content.classList.add('hidden');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        } else {
            content.classList.remove('hidden');
            content.classList.add('show');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        }
    }

    // Hàm điền dữ liệu từ mẫu lên form
    function fillTaskForm(title, desc, points) {
        document.getElementById('inpTitle').value = title;
        document.getElementById('inpDesc').value = desc;
        document.getElementById('inpPoints').value = points;
        
        // Cuộn lên form
        document.getElementById('taskForm').scrollIntoView({behavior: 'smooth', block: 'center'});
        
        // Hiệu ứng flash nhẹ
        var formBox = document.getElementById('taskForm').parentElement;
        formBox.style.boxShadow = "0 0 15px rgba(33, 150, 243, 0.5)";
        setTimeout(() => { formBox.style.boxShadow = "none"; }, 1000);
    }
</script>

</body>
</html>