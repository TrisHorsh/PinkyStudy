<?php
// pages/student/dashboard.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Chứa hàm lấy TKB
session_start();

// Bảo mật: Chỉ học sinh được vào
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_SESSION['user_id'];

// HẠY LOGIC TỰ ĐỘNG CẬP NHẬT TRẠNG THÁI 'FAILED'
markOverdueTasksAsFailed($conn, $student_id);

// 1. Cập nhật điểm số
// Thêm parent_id để biết chat với ai
$stmtUser = $conn->prepare("SELECT current_points, full_name, parent_id FROM users WHERE id = :id");
$stmtUser->execute([':id' => $student_id]);
$user = $stmtUser->fetch();
$_SESSION['current_points'] = $user['current_points'];

$parent_id = $user['parent_id'];

// --- KÍCH HOẠT TỰ ĐỘNG GIAO BÀI HÀNG NGÀY ---
checkAndCreateDailyTasks($conn, $student_id, $parent_id);

// 2. Lấy Thời khóa biểu
$timetable = getTimetableData($conn, $student_id);

// 3. Lấy Nhiệm vụ (Tách làm 2 nhóm)
$sqlDaily = "SELECT * FROM assigned_tasks 
             WHERE student_id = :sid 
             AND task_type = 'daily' 
             AND status != 'failed'
             AND (status != 'approved' OR DATE(completed_at) = CURDATE()) 
             ORDER BY created_at DESC";
$stmtDaily = $conn->prepare($sqlDaily);
$stmtDaily->execute([':sid' => $student_id]);
$tasks_daily = $stmtDaily->fetchAll();

$sqlChallenge = "SELECT * FROM assigned_tasks 
                 WHERE student_id = :sid AND task_type != 'daily' 
                 AND status != 'failed'
                 AND (status != 'approved' OR DATE(completed_at) = CURDATE()) 
                 ORDER BY created_at DESC";
$stmtChallenge = $conn->prepare($sqlChallenge);
$stmtChallenge->execute([':sid' => $student_id]);
$tasks_challenge = $stmtChallenge->fetchAll();

// 4. Lấy LỊCH SỬ BÀI ĐÃ CHẤM
$sqlHistory = "SELECT * FROM assigned_tasks 
               WHERE student_id = :sid 
               AND status = 'approved' 
               ORDER BY completed_at DESC LIMIT 10";
$stmtHistory = $conn->prepare($sqlHistory);
$stmtHistory->execute([':sid' => $student_id]);
$history_tasks = $stmtHistory->fetchAll();

// 5. Lấy danh sách quà đã Approved
$sqlVouchers = "SELECT r.*, g.gift_name, g.gift_image 
                FROM redemptions r 
                JOIN gifts g ON r.gift_id = g.id 
                WHERE r.student_id = :sid AND r.status = 'approved' 
                ORDER BY r.redemption_date DESC";
$stmtV = $conn->prepare($sqlVouchers);
$stmtV->execute([':sid' => $student_id]);
$vouchers = $stmtV->fetchAll();

include '../../includes/header_student.php';
?>

<link rel="stylesheet" href="../../assets/css/student_style.css?v=<?php echo time(); ?>">

<div class="dashboard-container">

    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Xin chào, <?php echo htmlspecialchars($user['full_name']); ?>! 🚀</h2>
            <p>Sẵn sàng chinh phục thử thách hôm nay chưa?</p>
        </div>
        <div class="points-box">
            <span style="font-size: 0.9em; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; color: #b2bec3;">Kho báu của bạn</span>
            <span class="points-num"><?php echo $user['current_points']; ?> ⭐</span>
        </div>
    </div>

    <div class="section-title">
        <span>📅 Lịch học trong tuần</span>
        <button id="tkbToggle" onclick="toggleTKB()" class="btn-toggle">Thu gọn ▲</button>
    </div>
    
    <div id="tkbContent" class="tkb-wrapper">
        <div style="overflow-x: auto;">
            <table class="tkb-table" style="width: 100%; border-collapse: separate; border-spacing: 5px;">
                <thead>
                    <tr>
                        <th style="background: transparent;"></th>
                        <th>Thứ 2</th><th>Thứ 3</th><th>Thứ 4</th><th>Thứ 5</th><th>Thứ 6</th><th>Thứ 7</th><th>CN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sessions = ['morning' => 'Sáng ☀️', 'afternoon' => 'Chiều ⛅', 'evening' => 'Tối 🌙'];
                    foreach ($sessions as $key => $label): 
                    ?>
                    <tr>
                        <td class="tkb-session" style="text-align: center; vertical-align: middle;"><?php echo $label; ?></td>
                        <?php for($d=2; $d<=8; $d++): ?>
                            <td>
                                <?php if (!empty($timetable[$key][$d])): ?>
                                    <?php foreach ($timetable[$key][$d] as $subj): ?>
                                        <div style="background: #dfe6e9; padding: 5px; border-radius: 5px; margin-bottom: 5px; font-weight: bold; font-size: 0.9em;">
                                            <?php echo htmlspecialchars($subj['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tasks-grid">
        
        <div class="task-col" style="max-height: 500px; overflow-y: auto; padding-right: 5px;">
            <div class="task-col-header daily-header">🌱 Nhiệm vụ Hàng ngày</div>
            <div class="task-list">
                <?php if(count($tasks_daily) > 0): ?>
                    <?php foreach($tasks_daily as $task): ?>
                        <?php 
                            // Chuẩn bị dữ liệu JSON để truyền vào JS
                            $taskJson = htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="quest-card" onclick="checkTaskAction(<?php echo $taskJson; ?>)" style="cursor: pointer;">
                            
                            <?php if($task['status'] == 'approved'): ?>
                                <span class="quest-badge" style="background: #00b894; color: white;">Đã chấm ✅</span>
                            <?php elseif($task['status'] == 'submitted'): ?>
                                <span class="quest-badge badge-submitted">Đang chấm... ⏳</span>
                            <?php elseif($task['status'] == 'rejected'): ?>
                                <span class="quest-badge badge-rejected">Làm lại ⚠️</span>
                            <?php else: ?>
                                <span class="quest-badge badge-pending">Mới ✨</span>
                            <?php endif; ?>

                            <div style="font-weight: 800; font-size: 1.1em; margin-bottom: 5px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size: 0.9em; color: #636e72;"><?php echo htmlspecialchars($task['description']); ?></div>
                            
                            <?php if(!empty($task['parent_comment'])): ?>
                                <div style="margin-top: 8px; background: #fff9c4; border-left: 3px solid #fbc02d; padding: 5px 8px; border-radius: 4px; font-size: 0.85em; color: #5d4037;">
                                    <strong>💬 Bố mẹ nhắn:</strong> <?php echo nl2br(htmlspecialchars($task['parent_comment'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="quest-points">+<?php echo $task['points_reward']; ?> sao</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <span style="font-size: 3em;">🎉</span>
                        <p style="color: #00b894; font-weight: bold;">Tuyệt vời! Đã xong hết việc hôm nay.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="task-col" style="max-height: 500px; overflow-y: auto; padding-right: 5px;">
            <div class="task-col-header challenge-header">🔥 Thử thách đặc biệt</div>
            <div class="task-list">
                <?php if(count($tasks_challenge) > 0): ?>
                    <?php foreach($tasks_challenge as $task): ?>
                        <?php 
                            $taskJson = htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="quest-card" onclick="checkTaskAction(<?php echo $taskJson; ?>)" style="cursor: pointer; border-left: 5px solid #ff7675;">
                            
                            <?php if($task['status'] == 'approved'): ?>
                                <span class="quest-badge" style="background: #00b894; color: white;">Đã chấm ✅</span>
                            <?php elseif($task['status'] == 'submitted'): ?>
                                <span class="quest-badge badge-submitted">Đang chấm...</span>
                            <?php elseif($task['status'] == 'rejected'): ?>
                                <span class="quest-badge badge-rejected">Làm lại</span>
                            <?php else: ?>
                                <span class="quest-badge badge-pending">Thử thách ⚔️</span>
                            <?php endif; ?>

                            <div style="font-weight: 800; font-size: 1.1em; margin-bottom: 5px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size: 0.9em; color: #636e72;"><?php echo htmlspecialchars($task['description']); ?></div>

                            <?php if(!empty($task['parent_comment'])): ?>
                                <div style="margin-top: 8px; background: #fff9c4; border-left: 3px solid #fbc02d; padding: 5px 8px; border-radius: 4px; font-size: 0.85em; color: #5d4037;">
                                    <strong>💬 Lời nhắn:</strong> <?php echo nl2br(htmlspecialchars($task['parent_comment'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="quest-points" style="background: #ff7675; color: white;">+<?php echo $task['points_reward']; ?> sao</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #b2bec3; margin-top: 20px;">Đang chờ Hệ thống cập nhật thêm thử thách...</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="section-title">
        <span>📜 Bảng vàng thành tích (Gần đây)</span>
    </div>
    <div class="history-card" style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
        <?php if(count($history_tasks) > 0): ?>
            <?php foreach($history_tasks as $ht): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #eee;">
                <div>
                    <div style="font-weight: bold; color: #2d3436;"><?php echo htmlspecialchars($ht['title']); ?></div>
                    <div style="font-size: 0.8em; color: #b2bec3;">
                        Hoàn thành: <?php echo date('H:i - d/m/Y', strtotime($ht['completed_at'])); ?>
                    </div>
                    <?php if (!empty($ac['parent_comment'])): ?>
                                        <div style="font-size:0.85em; color:#d35400; font-style:italic; margin-top:3px;">
                                            "<?php echo htmlspecialchars($ac['parent_comment']); ?>"
                                        </div>
                                    <?php endif; ?>
                </div>
                <div style="font-weight: 900; color: #00b894; background: #55efc433; padding: 5px 10px; border-radius: 15px;">
                    +<?php echo $ht['points_reward']; ?> ⭐
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #b2bec3;">Bạn chưa có bài nào được chấm điểm.</p>
        <?php endif; ?>
    </div>

    <div class="shop-promo" onclick="window.location.href='shop.php'">
        <h3>🎁 Cửa hàng quà tặng</h3>
        <p style="margin: 0; font-size: 1.1em;">Bạn đang có <b><?php echo $user['current_points']; ?> sao</b>. Bấm vào đây để đổi quà ngay!</p>
    </div>

    <?php if (count($vouchers) > 0): ?>
    <div class="section-title" style="margin-top: 30px;">
        <span>🎟️ Vé đổi quà của bạn</span>
    </div>
    <div style="display: flex; overflow-x: auto; gap: 20px; padding-bottom: 20px;">
        <?php foreach ($vouchers as $v): ?>
            <?php 
                // Tạo link xác thực
                // $verifyLink = "http://localhost/PinkyStudy/pages/parent/verify_gift.php?code=" . $v['voucher_code'];
                $host = $_SERVER['HTTP_HOST'];
                $verifyLink = "http://$host/PinkyStudy/pages/parent/verify_gift.php?code=" . $v['voucher_code'];
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verifyLink);
            ?>
            <div class="voucher-ticket">
                <h4 style="margin: 0 0 10px 0; color: #e17055; font-size: 1.2em;"><?php echo htmlspecialchars($v['gift_name']); ?></h4>
                
                <a href="<?php echo $verifyLink; ?>" target="_blank" title="Click để giả lập quét mã">
                    <img src="<?php echo $qrUrl; ?>" alt="QR Code" style="border-radius: 8px; border: 2px solid #eee;">
                </a>
                
                <div style="font-family: 'Courier New', monospace; font-size: 1.5em; font-weight: 900; margin: 10px 0; color: #2d3436; letter-spacing: 2px;">
                    <?php echo $v['voucher_code']; ?>
                </div>
                <small style="color: #636e72;">Đưa mã này cho bố mẹ quét nhé</small>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
    function toggleTKB() {
        const content = document.getElementById('tkbContent');
        const btn = document.getElementById('tkbToggle');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            btn.innerHTML = 'Thu gọn ▲';
        } else {
            content.style.display = 'none';
            btn.innerHTML = 'Mở rộng ▼';
        }
    }
</script>

<div id="studentTaskModal" class="st-modal">
    <div class="st-modal-content">
        <div class="st-modal-header">
            <span class="st-close" onclick="closeStudentModal()">&times;</span>
            <h2 id="st_title" style="margin: 0; font-size: 1.4em;">Tên nhiệm vụ</h2>
            <div id="st_points" style="margin-top: 5px; font-weight: bold; background: rgba(255,255,255,0.2); display: inline-block; padding: 3px 10px; border-radius: 15px;">+10 sao</div>
        </div>
        <div class="st-modal-body">
            
            <div style="text-align: center; margin-bottom: 20px;">
                <span id="st_status_badge"></span>
            </div>

            <div class="st-info-row">
                <span class="st-label">📝 Mô tả nhiệm vụ:</span>
                <div class="st-value" id="st_desc">...</div>
            </div>

            <div id="st_comment_area" class="st-comment-box" style="display: none;">
                <strong style="display: block; margin-bottom: 5px;">💬 Hệ thống nhận xét:</strong>
                <span id="st_comment_content" style="font-style: italic;"></span>
            </div>

            <div class="st-proof-box">
                <h4 style="margin-top: 0; color: #2d3436; border-bottom: 2px solid #dfe6e9; padding-bottom: 10px;">
                    📂 Bài làm của bạn
                </h4>
                
                <span class="st-label">Lời nhắn bạn gửi:</span>
                <p id="st_proof_text" style="font-style: italic; color: #555; margin-bottom: 15px;">(Không có lời nhắn)</p>
                
                <span class="st-label">Ảnh/File đính kèm:</span>
                <div id="st_proof_files" style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                    </div>
                
                <div id="st_time" style="text-align: right; font-size: 0.8em; color: #999; margin-top: 15px;"></div>
            </div>

        </div>
    </div>
</div>

<script>
    function checkTaskAction(task) {
        // LOGIC QUAN TRỌNG:
        // Nếu bài chưa xong (pending) hoặc bị trả lại (rejected) -> Chuyển trang làm bài
        // Nếu bài đã nộp (submitted) hoặc đã duyệt (approved) -> Mở Modal xem chi tiết
        
        if (task.status === 'pending' || task.status === 'rejected') {
            window.location.href = 'do_task.php?task_id=' + task.id;
        } else {
            openStudentModal(task);
        }
    }

    function openStudentModal(task) {
        // 1. Điền thông tin cơ bản
        document.getElementById('st_title').innerText = task.title;
        document.getElementById('st_desc').innerText = task.description;
        document.getElementById('st_points').innerText = '+' + task.points_reward + ' ⭐';
        
        // 2. Badge trạng thái
        let badgeHtml = '';
        if(task.status == 'approved') badgeHtml = '<span class="st-badge-approved">✅ Đã hoàn thành</span>';
        else badgeHtml = '<span class="st-badge-submitted">⏳ Đã nộp - Đang chờ chấm</span>';
        document.getElementById('st_status_badge').innerHTML = badgeHtml;

        // 3. Nhận xét phụ huynh
        if (task.parent_comment) {
            document.getElementById('st_comment_area').style.display = 'block';
            document.getElementById('st_comment_content').innerText = task.parent_comment;
        } else {
            document.getElementById('st_comment_area').style.display = 'none';
        }

        // 4. Bài làm của bé (Proof)
        document.getElementById('st_proof_text').innerText = task.proof_text ? '"' + task.proof_text + '"' : "(Không có lời nhắn)";
        
        // Xử lý File JSON
        let files = [];
        try { files = JSON.parse(task.proof_file); } catch (e) { files = [task.proof_file]; } // Fallback nếu lỗi
        if (!Array.isArray(files) || !files[0]) files = []; // Check rỗng

        let filesHtml = '';
        if (files.length > 0) {
            files.forEach(file => {
                let ext = file.split('.').pop().toLowerCase();
                let url = '../../uploads/proofs/' + file;
                
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                    filesHtml += `<img src="${url}" style="width: 100%; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">`;
                } 
                else if (['mp3', 'wav', 'm4a', 'ogg'].includes(ext)) {
                    filesHtml += `<div style="background: white; padding: 10px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 20px;">🎵</span>
                                    <audio controls style="flex: 1; height: 30px;"><source src="${url}"></audio>
                                  </div>`;
                }
                else {
                    filesHtml += `<a href="${url}" target="_blank" class="btn-do" style="text-align: center; display: block; text-decoration: none;">📄 Xem file ${ext}</a>`;
                }
            });
        } else {
            filesHtml = '<span style="color: #999; font-style: italic;">(Không có file đính kèm)</span>';
        }
        document.getElementById('st_proof_files').innerHTML = filesHtml;

        // 5. Thời gian
        let timeStr = '';
        if (task.submitted_at) timeStr = 'Nộp lúc: ' + new Date(task.submitted_at).toLocaleString('vi-VN');
        document.getElementById('st_time').innerText = timeStr;

        // Hiện Modal
        document.getElementById('studentTaskModal').style.display = 'block';
    }

    function closeStudentModal() {
        document.getElementById('studentTaskModal').style.display = 'none';
    }

    // Đóng khi click ra ngoài
    window.onclick = function(event) {
        let modal = document.getElementById('studentTaskModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

<style>
    /* Nút chat nổi */
    .chat-widget-btn {
        position: fixed; bottom: 20px; right: 20px;
        background: #0984e3; color: white;
        width: 60px; height: 60px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 30px; cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        z-index: 9999;
        transition: transform 0.2s;
    }
    .chat-widget-btn:hover { transform: scale(1.1); }

    /* Khung chat */
    .chat-box {
        display: none;
        position: fixed; bottom: 90px; right: 20px;
        width: 320px; height: 450px;
        background: white; border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 9999;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #dfe6e9;
    }
    
    .chat-header { background: #0984e3; color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; }
    .chat-body { flex: 1; padding: 15px; overflow-y: auto; background: #f1f2f6; display: flex; flex-direction: column; gap: 10px; }
    .chat-footer { padding: 10px; border-top: 1px solid #eee; display: flex; background: white; }
    
    .chat-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
    .chat-send { background: none; border: none; color: #0984e3; font-size: 20px; margin-left: 10px; cursor: pointer; }

    /* Bong bóng chat */
    .message { display: flex; flex-direction: column; max-width: 80%; }
    .chat-me { align-self: flex-end; align-items: flex-end; }
    .chat-you { align-self: flex-start; align-items: flex-start; }
    
    .msg-bubble { padding: 10px 15px; border-radius: 15px; font-size: 0.95em; word-wrap: break-word; }
    .chat-me .msg-bubble { background: #0984e3; color: white; border-bottom-right-radius: 2px; }
    .chat-you .msg-bubble { background: white; color: #333; border: 1px solid #ddd; border-bottom-left-radius: 2px; }
    .msg-time { font-size: 0.7em; color: #999; margin-top: 3px; }
</style>

<button class="chat-widget-btn" onclick="toggleChat()">
    <i class="fas fa-comment-dots"></i>
    <span id="unreadBadge" class="notification-badge">0</span>
</button>

<div id="chatBox" class="chat-box">
    <div class="chat-header">
        <span><i class="fas fa-robot"></i> Trò chuyện với Hệ thống</span>
        <button class="btn-close-chat" onclick="toggleChat()">&times;</button>
    </div>
    
    <div id="chatContent" class="chat-content">
        <div style="text-align: center; color: #999; margin-top: 50px;">Đang tải tin nhắn...</div>
    </div>

    <div class="chat-input-area">
        <input type="text" id="chatInput" class="chat-input" placeholder="Nhập tin nhắn..." onkeypress="handleEnter(event)">
        <button onclick="sendMessage()" class="btn-send"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
    // JS XỬ LÝ CHAT
    let receiverId = <?php echo $parent_id; ?>;
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