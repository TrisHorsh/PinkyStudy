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

// [MỚI] Lấy danh sách điều ước cần duyệt
$stmtWishes = $conn->prepare("SELECT * FROM gift_requests WHERE student_id = :sid AND status = 'pending'");
$stmtWishes->execute([':sid' => $student_id]);
$pending_wishes = $stmtWishes->fetchAll();
$wishes_count = count($pending_wishes);

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
                                <button type="submit" name="add_task_btn" class="btn btn-success" style="flex: 1;">
                                    <i class="fas fa-paper-plane"></i> Giao ngay
                                </button>
                                <button type="submit" formaction="../../actions/template_add.php" name="add_template_btn" class="btn btn-primary" title="Lưu lại để dùng lần sau">
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
                                onclick="fillTaskForm(<?php echo $jsTitle; ?>, <?php echo $jsDesc; ?>, <?php echo $tpl['default_points']; ?>)">
                                
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
                            
                            <?php 
                                // Chuẩn bị dữ liệu JSON để truyền vào JS
                                $taskJson = htmlspecialchars(json_encode($hTask), ENT_QUOTES, 'UTF-8');
                            ?>

                            <div class="list-item" onclick="openTaskDetail(<?php echo $taskJson; ?>)" style="cursor: pointer;">
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
                                           onclick="event.stopPropagation(); return confirm('Xóa nhiệm vụ này?')"
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

            <?php if ($wishes_count > 0): ?>
                <div style="background: #e3f2fd; border: 1px solid #90caf9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0; color: #0d47a1;">
                        <i class="fas fa-magic"></i> Bé có <?php echo $wishes_count; ?> điều ước mới!
                    </h4>
                    <table class="table" style="background: white;">
                        <?php foreach ($pending_wishes as $wish): ?>
                        <tr>
                            <td style="padding: 10px;">
                                <strong><?php echo htmlspecialchars($wish['gift_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($wish['gift_desc']); ?></small>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn btn-primary btn-sm" 
                                        onclick="approveWish('<?php echo htmlspecialchars($wish['gift_name']); ?>', <?php echo $wish['id']; ?>)">
                                    <i class="fas fa-check"></i> Tạo quà này
                                </button>
                                
                                <a href="../../actions/gift_request_reject.php?id=<?php echo $wish['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                class="btn btn-danger btn-sm" onclick="return confirm('Từ chối điều ước này?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
            
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

<div id="taskDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin: 0; color: #007bff;"><i class="fas fa-info-circle"></i> Chi tiết nhiệm vụ</h3>
            <span class="close-modal" onclick="closeTaskModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="detail-label">Tên nhiệm vụ:</div>
            <div class="detail-value" id="d_title" style="font-size: 1.1em; font-weight: bold;"></div>
            
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <span class="detail-label">Điểm thưởng:</span>
                    <div class="detail-value" style="color: #d63384; font-weight: bold;" id="d_points"></div>
                </div>
                <div style="flex: 1;">
                    <span class="detail-label">Trạng thái:</span>
                    <div class="detail-value" id="d_status"></div>
                </div>
            </div>

            <div class="detail-label">Mô tả / Hướng dẫn:</div>
            <div class="detail-value" id="d_desc" style="background: #f8f9fa; padding: 10px; border-radius: 6px;"></div>

            <div id="proof_container" style="display: none; margin-top: 20px;">
                <h4 style="border-bottom: 2px solid #2196f3; padding-bottom: 5px; color: #0d47a1; margin-bottom: 15px;">
                    <i class="fas fa-paperclip"></i> Bài làm của bé
                </h4>
                
                <div class="proof-box">
                    <span class="detail-label">💬 Lời nhắn của bé:</span>
                    <p id="d_message" style="font-style: italic; color: #555; margin-bottom: 15px;">...</p>
                    
                    <span class="detail-label">📸 File đính kèm:</span>
                    <div id="d_file_content" style="text-align: center; margin-top: 10px;"></div>
                </div>
                
                <div style="margin-top: 10px; font-size: 0.85em; color: #888; text-align: right;" id="d_time_info"></div>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeTaskModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
    function openTaskDetail(task) {
        // 1. Điền thông tin cơ bản
        document.getElementById('d_title').innerText = task.title;
        document.getElementById('d_desc').innerText = task.description;
        document.getElementById('d_points').innerText = task.points_reward + ' ⭐';
        
        // 2. Xử lý trạng thái
        let statusHtml = '';
        if(task.status == 'pending') statusHtml = '<span class="badge bg-orange">Chưa làm</span>';
        else if(task.status == 'submitted') statusHtml = '<span class="badge bg-blue">Đã nộp (Chờ duyệt)</span>';
        else if(task.status == 'approved') statusHtml = '<span class="badge bg-green">Đã hoàn thành</span>';
        else statusHtml = '<span class="badge bg-red">Làm lại</span>';
        document.getElementById('d_status').innerHTML = statusHtml;

        // 3. Xử lý phần Bằng chứng (Proof)
        let proofContainer = document.getElementById('proof_container');
        
        // Nếu đã nộp hoặc đã duyệt (và có file) thì hiện
        if ((task.status == 'submitted' || task.status == 'approved' || task.status == 'rejected') && task.proof_file) {
            proofContainer.style.display = 'block';
            
            // Lời nhắn
            document.getElementById('d_message').innerText = task.proof_text ? '"' + task.proof_text + '"' : "(Không có lời nhắn)";

            // Kiểm tra xem ID 'd_parent_comment' đã có chưa, nếu chưa thì tạo nó
            let commentBox = document.getElementById('d_parent_comment_box');
            
            // Nếu chưa có div hiển thị comment trong HTML modal (mặc định chưa có), ta sẽ chèn động vào
            if (!commentBox) {
                // Tạo một div mới để chứa comment
                let proofBox = document.querySelector('.proof-box'); 
                if(proofBox) {
                    commentBox = document.createElement('div');
                    commentBox.id = 'd_parent_comment_box';
                    commentBox.style.marginTop = '15px';
                    commentBox.style.padding = '10px';
                    commentBox.style.backgroundColor = '#fff3cd'; // Màu vàng nhạt
                    commentBox.style.borderLeft = '4px solid #ffc107';
                    commentBox.style.borderRadius = '4px';
                    // Chèn vào sau proof-box
                    proofBox.parentNode.insertBefore(commentBox, proofBox.nextSibling);
                }
            }

            // Gán nội dung
            if (task.parent_comment && task.parent_comment.trim() !== "") {
                commentBox.innerHTML = `<strong>✍️ Phụ huynh nhận xét:</strong><br><span style="color: #856404;">${task.parent_comment}</span>`;
                commentBox.style.display = 'block';
            } else {
                if(commentBox) commentBox.style.display = 'none';
            }

            // --- XỬ LÝ FILE ĐÍNH KÈM (JSON ARRAY) ---
            let files = [];
            try {
                // Thử parse JSON
                files = JSON.parse(task.proof_file);
            } catch (e) {
                // Nếu lỗi (do dữ liệu cũ không phải JSON), coi như mảng 1 phần tử
                files = [task.proof_file];
            }

            // Nếu files không phải mảng (trường hợp null/undefined), gán rỗng
            if (!Array.isArray(files)) files = [];

            let contentHtml = '';
            files.forEach(file => {
                let fileExt = file.split('.').pop().toLowerCase();
                let fileUrl = '../../uploads/proofs/' + file;

                contentHtml += `<div style="margin-bottom: 10px; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: white;">`;
                
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                    // Ảnh
                    contentHtml += `<img src="${fileUrl}" style="max-width: 100%; display: block; margin: 0 auto;">`;
                } 
                else if (['mp3', 'wav', 'm4a', 'ogg'].includes(fileExt)) {
                    // Âm thanh (Mới)
                    contentHtml += `<div style="display:flex; align-items:center; gap:10px;">
                                        <i class="fas fa-volume-up" style="font-size: 1.5em; color: #E91E63;"></i>
                                        <div style="flex:1">
                                            <div style="font-size:0.9em; font-weight:bold; margin-bottom:5px;">${file}</div>
                                            <audio controls style="width:100%; height: 30px;">
                                                <source src="${fileUrl}">
                                            </audio>
                                        </div>
                                    </div>`;
                }
                else {
                    // File khác
                    contentHtml += `<div style="text-align: center;">
                                        <i class="fas fa-file"></i> ${file} <br>
                                        <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-primary" style="margin-top:5px;">Tải về</a>
                                    </div>`;
                }
                contentHtml += `</div>`;
            });
            
            document.getElementById('d_file_content').innerHTML = contentHtml;
            // ----------------------------------------

            // Thời gian
            let timeStr = '';
            if (task.submitted_at) timeStr += `Nộp lúc: ${task.submitted_at} `;
            if (task.completed_at) timeStr += `| Duyệt lúc: ${task.completed_at}`;
            document.getElementById('d_time_info').innerText = timeStr;

        } else {
            proofContainer.style.display = 'none';
        }

        // Hiện modal
        document.getElementById('taskDetailModal').style.display = 'block';
    }

    function closeTaskModal() {
        document.getElementById('taskDetailModal').style.display = 'none';
    }

    // Click ngoài để đóng
    window.onclick = function(event) {
        let modal = document.getElementById('taskDetailModal');
        let editModal = document.getElementById('editModal'); // Modal sửa TKB (nếu có)
        if (event.target == modal) {
            modal.style.display = "none";
        }
        if (editModal && event.target == editModal) {
            editModal.style.display = "none";
        }
    }

    // Hàm xử lý khi bấm "Tạo quà này" từ điều ước
    function approveWish(giftName, wishId) {
        // 1. Điền tên quà vào form Thêm quà
        document.querySelector('input[name="gift_name"]').value = giftName;
        
        // 2. Focus vào ô nhập điểm để bố mẹ nhập giá
        document.querySelector('input[name="point_cost"]').focus();
        document.querySelector('input[name="point_cost"]').placeholder = "Nhập số sao cho món quà này";
        
        // 3. Thêm input hidden wish_id vào form để Backend biết đây là tạo từ điều ước
        let form = document.querySelector('form[action="../../actions/gift_add.php"]');
        
        // Xóa input cũ nếu có
        let oldInput = document.getElementById('wish_id_input');
        if(oldInput) oldInput.remove();

        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'wish_id';
        input.id = 'wish_id_input';
        input.value = wishId;
        form.appendChild(input);

        alert('Đã copy tên quà! Hãy nhập số sao, chọn ảnh và bấm Lưu.');
    }
</script>

<button class="chat-widget-btn" onclick="toggleChat()">
    <i class="fas fa-comment-alt"></i>
    <span id="unreadBadge" class="notification-badge">0</span>
</button>

<div id="chatBox" class="chat-box">
    <div class="chat-header">
        <span><i class="fas fa-user-graduate"></i> Chat với <?php echo htmlspecialchars($student['full_name']); ?></span>
        <button class="btn-close-chat" onclick="toggleChat()">&times;</button>
    </div>
    
    <div id="chatContent" class="chat-content">
        <div style="text-align: center; color: #999; margin-top: 50px;">Đang tải cuộc trò chuyện...</div>
    </div>

    <div class="chat-input-area">
        <input type="text" id="chatInput" class="chat-input" placeholder="Nhập tin nhắn..." onkeypress="handleEnter(event)">
        <button onclick="sendMessage()" class="btn-send"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
    // JS XỬ LÝ CHAT (Giống bên Student nhưng receiver là student_id)
    let receiverId = <?php echo $student_id; ?>;
    let chatInterval = null;
    let notiInterval = null; // Interval để check thông báo
    let isChatOpen = false;

    // 1. Chạy ngay khi vào trang: Bắt đầu kiểm tra tin nhắn mới
    startNotificationCheck();

    function toggleChat() {
        let box = document.getElementById('chatBox');
        if (box.style.display === 'flex') {
            box.style.display = 'none';
            isChatOpen = false;
            if(chatInterval) clearInterval(chatInterval);
        } else {
            box.style.display = 'flex';
            isChatOpen = true;
            fetchMessages();
            chatInterval = setInterval(fetchMessages, 3000);
            setTimeout(scrollToBottom, 200);
        }
    }

    // Hàm kiểm tra tin mới (Chạy ngầm khi đóng chat)
    function startNotificationCheck() {
        if(notiInterval) clearInterval(notiInterval);
        // Check ngay lập tức
        checkUnreadCount();
        // Sau đó lặp lại mỗi 3s
        notiInterval = setInterval(checkUnreadCount, 3000);
    }

    function checkUnreadCount() {
        if(isChatOpen) return; // Nếu đang chat thì không cần check kiểu này

        let formData = new FormData();
        formData.append('sender_id', receiverId); // Check tin nhắn TỪ người này gửi đến mình

        fetch('../../actions/chat_check_new.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(count => {
            let badge = document.getElementById('unreadBadge');
            if(parseInt(count) > 0) {
                badge.innerText = count > 9 ? '9+' : count;
                badge.style.display = 'flex'; // Hiện chấm đỏ
                
                // (Tùy chọn) Phát âm thanh nếu muốn
                // playNotificationSound(); 
            } else {
                badge.style.display = 'none'; // Ẩn chấm đỏ
            }
        });
    }

    function handleEnter(e) { if (e.key === 'Enter') sendMessage(); }

    function sendMessage() {
        let input = document.getElementById('chatInput');
        let msg = input.value.trim();
        if (!msg) return;

        let formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', msg);

        fetch('../../actions/chat_send.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            if(data.trim() === 'success') {
                input.value = '';
                fetchMessages();
                scrollToBottom();
            }
        });
    }

    function fetchMessages() {
        if(!isChatOpen) return;

        let formData = new FormData();
        formData.append('receiver_id', receiverId);

        fetch('../../actions/chat_fetch.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(html => {
            let chatBody = document.getElementById('chatContent');
            let isAtBottom = (chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight) < 50;
            chatBody.innerHTML = html;
            
            // Khi load tin nhắn, server đã đánh dấu là "Đã đọc" -> Ẩn chấm đỏ
            document.getElementById('unreadBadge').style.display = 'none';

            if(isAtBottom) scrollToBottom();
        });
    }

    function scrollToBottom() {
        let chatBody = document.getElementById('chatContent');
        chatBody.scrollTop = chatBody.scrollHeight;
    }
</script>

</body>
</html>