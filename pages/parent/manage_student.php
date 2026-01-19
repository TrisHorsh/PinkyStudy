<?php
// pages/parent/manage_student.php
require_once '../../config/db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$parent_id = $_SESSION['user_id'];
$student_id = $_GET['student_id'] ?? 0;

// 1. Lấy thông tin bé
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id AND parent_id = :pid");
$stmt->execute([':id' => $student_id, ':pid' => $parent_id]);
$student = $stmt->fetch();

if (!$student) die("Không tìm thấy học sinh này.");

// 2. Lấy danh sách bài cần chấm (Status = submitted)
$stmtGrade = $conn->prepare("SELECT * FROM assigned_tasks WHERE student_id = :sid AND status = 'submitted' ORDER BY submitted_at ASC");
$stmtGrade->execute([':sid' => $student_id]);
$pending_tasks = $stmtGrade->fetchAll();
$pending_count = count($pending_tasks);

// [MỚI] Lấy yêu cầu đổi quà đang CHỜ (Pending)
$stmtRedeem = $conn->prepare("SELECT r.*, g.gift_name, g.gift_image 
                              FROM redemptions r
                              JOIN gifts g ON r.gift_id = g.id
                              WHERE r.student_id = :sid AND r.status = 'pending'");
$stmtRedeem->execute([':sid' => $student_id]);
$pending_redemptions = $stmtRedeem->fetchAll();
$redeem_count = count($pending_redemptions);

// 3. Lấy dữ liệu cho Cấu hình Nhiệm vụ
// 3a. Ngân hàng mẫu (Tất cả template)
$stmtTemplates = $conn->prepare("SELECT * FROM task_templates WHERE creator_id = :pid ORDER BY created_at DESC");
$stmtTemplates->execute([':pid' => $parent_id]);
$templates = $stmtTemplates->fetchAll();

// 3b. Nhiệm vụ Hàng ngày (Đang chạy tự động - Lọc từ templates có type=daily)
$daily_configs = array_filter($templates, function($t) { return $t['task_type'] === 'daily'; });

// 3c. Lịch sử nhiệm vụ đã giao (20 bài gần nhất)
$stmtHistory = $conn->prepare("SELECT * FROM assigned_tasks WHERE student_id = :sid ORDER BY created_at DESC LIMIT 20");
$stmtHistory->execute([':sid' => $student_id]);
$history_tasks = $stmtHistory->fetchAll();

// 4. Lấy danh sách quà
$stmtGifts = $conn->prepare("SELECT * FROM gifts WHERE parent_id = :pid ORDER BY created_at DESC");
$stmtGifts->execute([':pid' => $parent_id]);
$gifts = $stmtGifts->fetchAll();

include '../../includes/header.php';
?>

<style>
    .manage-container { max-width: 1200px; margin: 0 auto; padding-bottom: 50px; }
    
    /* Layout chung */
    .section-box { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
    .section-header { padding: 15px 20px; background: #f8f9fa; font-weight: bold; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border-bottom: 1px solid #eee; }
    .section-body { padding: 20px; }
    .hidden { display: none; }
    
    /* Info Bar */
    .student-info-bar {
        background: linear-gradient(135deg, #667eea, #764ba2); color: white;
        padding: 20px; border-radius: 10px; margin-bottom: 25px;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    /* Grid Layout cho Cấu hình nhiệm vụ */
    .task-config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .task-config-grid { grid-template-columns: 1fr; } }

    /* Form Styles */
    .form-group { margin-bottom: 10px; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    
    /* List Items */
    .list-item { border: 1px solid #eee; padding: 10px; border-radius: 6px; margin-bottom: 8px; background: #fff; }
    .list-item:hover { background: #f9f9f9; }
    .list-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .badge { padding: 3px 6px; border-radius: 4px; font-size: 0.75em; font-weight: bold; color: white; }
    .bg-green { background: #28a745; }
    .bg-orange { background: #fd7e14; }
    .bg-blue { background: #007bff; }
</style>

<div class="manage-container">
    <a href="dashboard.php" style="display: inline-block; margin-bottom: 15px; color: #666; text-decoration: none;">&larr; Quay lại danh sách</a>

    <div class="student-info-bar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="font-size: 2.5em;">🎓</div>
            <div>
                <h2 style="margin: 0; font-size: 1.5em;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
                <span style="opacity: 0.8;">@<?php echo htmlspecialchars($student['username']); ?></span>
            </div>
        </div>
        <div style="text-align: right;">
            <span style="display: block; font-size: 0.9em; opacity: 0.9;">Tích lũy hiện tại</span>
            <span style="font-size: 2em; font-weight: bold; color: #ffeb3b;"><?php echo $student['current_points']; ?> ⭐</span>
        </div>
    </div>

    <div class="section-box" style="border-top: 4px solid #dc3545;">
        <div class="section-header" style="background: #fff5f5; color: #dc3545; cursor: default;">
            <span>📝 Cần chấm điểm <?php if($pending_count > 0) echo "<span class='count-badge'>$pending_count</span>"; ?></span>
        </div>
        <div class="section-body">
            <?php if ($pending_count > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <?php foreach ($pending_tasks as $task): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;">
                            <b><?php echo htmlspecialchars($task['title']); ?></b><br>
                            <small style="color: #666;">Nộp lúc: <?php echo date('H:i d/m', strtotime($task['submitted_at'])); ?></small>
                        </td>
                        <td style="text-align: right;">
                            <a href="grading.php?task_id=<?php echo $task['id']; ?>" class="btn btn-primary" style="font-size: 0.9em;">Chấm ngay ➔</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; margin: 0;">Hiện không có bài nào cần chấm.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-box">
        <div class="section-header" onclick="toggleBox('configTaskBox', this)">
            <span>🛠 Quản lý & Giao nhiệm vụ</span>
            <span>▼</span>
        </div>
        <div id="configTaskBox" class="section-body">
            
            <div class="task-config-grid">
                
                <div>
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #90caf9;">
                        <h4 style="margin-top: 0; color: #0d47a1;">✍️ Soạn / Giao nhiệm vụ</h4>
                        
                        <form action="../../actions/task_add.php" method="POST" id="taskForm">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            
                            <div class="form-group">
                                <label style="font-size: 0.9em; font-weight: bold;">Tên nhiệm vụ:</label>
                                <input type="text" name="title" id="inpTitle" required class="form-control" placeholder="VD: Làm bài tập Toán">
                            </div>
                            
                            <div class="form-group">
                                <label style="font-size: 0.9em;">Mô tả chi tiết:</label>
                                <textarea name="description" id="inpDesc" rows="2" class="form-control" placeholder="Hướng dẫn bé làm gì..."></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <label style="font-size: 0.9em;">Điểm thưởng:</label>
                                    <input type="number" name="points" id="inpPoints" value="10" required class="form-control">
                                </div>
                                <div style="flex: 1;">
                                    <label style="font-size: 0.9em;">Loại:</label>
                                    <select name="task_type" id="inpType" class="form-control">
                                        <option value="challenge">Thử thách (Bình thường)</option>
                                        <option value="daily">Hàng ngày</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top: 15px; display: flex; gap: 10px;">
                                <button type="submit" name="add_task_btn" class="btn btn-primary" style="flex: 1;">🚀 Giao ngay</button>
                                <button type="submit" formaction="../../actions/template_add.php" name="add_template_btn" class="btn" style="background: #fff; border: 1px solid #ccc; color: #333;">💾 Lưu mẫu</button>
                            </div>
                        </form>
                    </div>

                    <h4 style="margin-bottom: 10px;">📂 Ngân hàng mẫu có sẵn</h4>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if(count($templates) > 0): ?>
                            <?php foreach($templates as $tpl): ?>
                            <div class="list-item" style="cursor: pointer; border-left: 3px solid #007bff;" 
                                 onclick="fillTaskForm('<?php echo addslashes($tpl['title']); ?>', '<?php echo addslashes($tpl['description']); ?>', <?php echo $tpl['default_points']; ?>)">
                                <div class="list-item-header">
                                    <strong><?php echo htmlspecialchars($tpl['title']); ?></strong>
                                    <span class="badge bg-blue"><?php echo $tpl['default_points']; ?> ⭐</span>
                                </div>
                                <small style="color: #666;"><?php echo htmlspecialchars($tpl['description']); ?></small>
                                <div style="text-align: right; margin-top: 5px;">
                                    <small style="color: #0d47a1; font-weight: bold;">Click để dùng ➔</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999; font-style: italic;">Chưa có mẫu nào. Hãy soạn ở trên và bấm "Lưu mẫu".</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c8e6c9;">
                        <h4 style="margin-top: 0; color: #2e7d32;">🔄 Đang tự động giao hàng ngày</h4>
                        <?php if(count($daily_configs) > 0): ?>
                            <?php foreach($daily_configs as $daily): ?>
                            <div class="list-item" style="background: white; border-left: 3px solid #28a745;">
                                <div class="list-item-header">
                                    <span><?php echo htmlspecialchars($daily['title']); ?></span>
                                    <a href="../../actions/template_delete.php?id=<?php echo $daily['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                       onclick="return confirm('Dừng tự động giao nhiệm vụ này?')" 
                                       style="color: red; text-decoration: none;">&times; Dừng</a>
                                </div>
                                <small class="badge bg-green">Tự động mỗi ngày</small>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; font-size: 0.9em;">Chưa có nhiệm vụ lặp lại nào.</p>
                        <?php endif; ?>
                    </div>

                    <h4 style="margin-bottom: 10px;">📋 Lịch sử nhiệm vụ đã giao</h4>
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
                                        else echo '<span class="badge" style="background:red">Làm lại</span>';
                                    ?>
                                </div>
                                <small style="color: #888;">Giao: <?php echo date('d/m H:i', strtotime($hTask['created_at'])); ?></small>
                                
                                <?php if($hTask['status'] == 'pending'): ?>
                                    <div style="text-align: right; margin-top: 5px;">
                                        <a href="../../actions/task_delete.php?id=<?php echo $hTask['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                           style="color: red; font-size: 0.85em;" onclick="return confirm('Xóa nhiệm vụ này?')">Xóa bỏ</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999;">Chưa giao nhiệm vụ nào.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="section-box">
        <div class="section-header" onclick="toggleBox('configRewardBox', this)">
            <span>🎁 Cấu hình Cửa hàng quà tặng</span>
            <span>▶</span>
        </div>
        <div id="configRewardBox" class="section-body hidden">
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #e65100;">Thêm món quà mới</h4>
                <form action="../../actions/gift_add.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                    <input type="hidden" name="return_student_id" value="<?php echo $student_id; ?>">
                    <div style="flex: 2;">
                        <input type="text" name="gift_name" required placeholder="Tên món quà..." class="form-control">
                    </div>
                    <div style="width: 100px;">
                        <input type="number" name="point_cost" required placeholder="Số sao" class="form-control">
                    </div>
                    <div>
                        <input type="file" name="gift_image" required accept="image/*" style="font-size: 0.8em;">
                    </div>
                    <button type="submit" name="add_gift_btn" class="btn btn-primary">Lưu</button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                <?php foreach($gifts as $gift): ?>
                <div style="border: 1px solid #eee; border-radius: 8px; padding: 10px; text-align: center; background: white;">
                    <img src="../../uploads/gifts/<?php echo $gift['gift_image']; ?>" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                    <div style="font-weight: bold; margin: 5px 0; font-size: 0.9em;"><?php echo htmlspecialchars($gift['gift_name']); ?></div>
                    <div style="color: #d63384; font-weight: bold;"><?php echo $gift['point_cost']; ?> ⭐</div>
                    <a href="../../actions/gift_delete.php?id=<?php echo $gift['id']; ?>&student_id=<?php echo $student_id; ?>" 
                       style="color: red; font-size: 0.8em; text-decoration: none;" onclick="return confirm('Xóa quà này?')">[Xóa]</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php if ($redeem_count > 0): ?>
    <div class="section-box" style="border-top: 4px solid #ffc107;">
        <div class="section-header" style="background: #fff3cd; color: #856404;">
            <span>🎁 Yêu cầu đổi quà <span style="background: #ffc107; color: #856404; padding: 2px 8px; border-radius: 10px; font-size: 0.8em;"><?php echo $redeem_count; ?></span></span>
        </div>
        <div class="section-body">
            <table style="width: 100%;">
                <?php foreach ($pending_redemptions as $req): ?>
                <tr style="border-bottom: 1px solid #eee;">
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
                            
                            <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm" style="background: #28a745;">✓ Đồng ý</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Từ chối yêu cầu này?')">✕</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-box">
        <div class="section-header" onclick="toggleBox('configTimetableBox', this)">
            <span>📅 Cấu hình Thời khóa biểu</span>
            <span>▶</span>
        </div>
        <div id="configTimetableBox" class="section-body hidden">
            <div style="background: #e0f2f1; padding: 25px; border-radius: 8px; text-align: center; border: 1px solid #80cbc4;">
                <h4 style="color: #00695c; margin-top: 0;">Lịch học & Hoạt động</h4>
                <p style="color: #555; margin-bottom: 20px;">
                    Thiết lập thời khóa biểu (Sáng - Chiều - Tối) để bé dễ dàng theo dõi trên Dashboard.
                </p>
                
                <a href="timetable.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary" style="background-color: #00897b; border: none; padding: 10px 20px; font-size: 1.1em;">
                    ✏️ Chỉnh sửa Thời khóa biểu chi tiết ➔
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Hàm ẩn hiện các box
    function toggleBox(id, header) {
        var content = document.getElementById(id);
        var icon = header.querySelector('span:last-child');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.innerText = "▼";
        } else {
            content.classList.add('hidden');
            icon.innerText = "▶";
        }
    }

    // Hàm điền dữ liệu từ Mẫu lên Form (Core feature)
    function fillTaskForm(title, desc, points) {
        document.getElementById('inpTitle').value = title;
        document.getElementById('inpDesc').value = desc;
        document.getElementById('inpPoints').value = points;
        
        // Cuộn nhẹ lên form để người dùng thấy đã điền
        document.getElementById('taskForm').scrollIntoView({behavior: 'smooth', block: 'center'});
        
        // Highlight nhẹ form để báo hiệu
        var formBox = document.querySelector('.task-config-grid > div > div'); // Lấy cái box chứa form
        formBox.style.boxShadow = "0 0 10px #2196f3";
        setTimeout(() => { formBox.style.boxShadow = "none"; }, 1000);
    }
</script>

</body>
</html>