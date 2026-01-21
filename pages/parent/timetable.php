<?php
// pages/parent/timetable.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_GET['student_id'] ?? 0;

// Lấy thông tin bé để hiển thị tên
$stmtName = $conn->prepare("SELECT full_name FROM users WHERE id = :id");
$stmtName->execute([':id' => $student_id]);
$student_name = $stmtName->fetchColumn();

// Lấy dữ liệu TKB
$timetable = getTimetableData($conn, $student_id);

include '../../includes/header.php';
?>

<div class="manage-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="manage_student.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại quản lý bé
        </a>
        <h2 style="margin: 0; color: #343a40;">
            📅 Thời khóa biểu: <span style="color: #007bff;"><?php echo htmlspecialchars($student_name); ?></span>
        </h2>
    </div>

    <div class="tkb-card">
        
        <div class="tkb-toolbar">
            <div class="tkb-form-title">
                <i class="fas fa-plus-circle" style="color: #28a745;"></i> Thêm môn học vào lịch
            </div>
            
            <form action="../../actions/timetable_add.php" method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="font-size: 0.9em; font-weight: bold; color: #666;">Thứ:</label>
                    <select name="day" required class="form-control">
                        <option value="">-- Chọn --</option>
                        <option value="2">Thứ 2</option>
                        <option value="3">Thứ 3</option>
                        <option value="4">Thứ 4</option>
                        <option value="5">Thứ 5</option>
                        <option value="6">Thứ 6</option>
                        <option value="7">Thứ 7</option>
                        <option value="8">Chủ Nhật</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-size: 0.9em; font-weight: bold; color: #666;">Buổi:</label>
                    <select name="session" required class="form-control">
                        <option value="">-- Chọn --</option>
                        <option value="morning">Buổi Sáng ☀️</option>
                        <option value="afternoon">Buổi Chiều ⛅</option>
                        <option value="evening">Buổi Tối 🌙</option>
                    </select>
                </div>

                <div style="flex: 2; min-width: 200px;">
                    <label style="font-size: 0.9em; font-weight: bold; color: #666;">Môn học / Hoạt động:</label>
                    <input type="text" name="subject" required placeholder="VD: Toán, Đá bóng, Học đàn..." class="form-control">
                </div>
                
                <button type="submit" name="add_tkb_btn" class="btn btn-primary" style="height: 42px; padding: 0 20px;">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </form>
        </div>

        <div class="tkb-table-wrapper">
            <table class="tkb-table">
                <thead>
                    <tr>
                        <th style="width: 100px; background: #212529;">Buổi</th> <th>Thứ 2</th>
                        <th>Thứ 3</th>
                        <th>Thứ 4</th>
                        <th>Thứ 5</th>
                        <th>Thứ 6</th>
                        <th>Thứ 7</th>
                        <th>CN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sessions_map = [
                        'morning' => ['label' => 'Sáng', 'icon' => '☀️'], 
                        'afternoon' => ['label' => 'Chiều', 'icon' => '⛅'], 
                        'evening' => ['label' => 'Tối', 'icon' => '🌙']
                    ];
                    
                    foreach ($sessions_map as $key => $info): 
                    ?>
                    <tr>
                        <td class="tkb-session-col">
                            <div style="font-size: 1.5em; margin-bottom: 5px;"><?php echo $info['icon']; ?></div>
                            <?php echo $info['label']; ?>
                        </td>
                        
                        <?php for($d=2; $d<=8; $d++): ?>
                            <td>
                                <?php if (!empty($timetable[$key][$d])): ?>
                                    <?php foreach ($timetable[$key][$d] as $subj): ?>
                                        <div class="subject-tag">
                                            <span><?php echo htmlspecialchars($subj['name']); ?></span>
                                            
                                            <div style="display: flex; align-items: center;">
                                                <span class="btn-edit-subject" 
                                                    onclick="openEditModal(<?php echo $subj['id']; ?>, '<?php echo addslashes($subj['name']); ?>')" 
                                                    title="Sửa tên môn">
                                                    ✏️
                                                </span>

                                                <a href="../../actions/timetable_delete.php?id=<?php echo $subj['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                                class="btn-del-subject" onclick="return confirm('Bạn muốn xóa môn này khỏi lịch?')" title="Xóa">
                                                &times;
                                                </a>
                                            </div>
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
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeEditModal()">&times;</span>
        <h3 style="margin-top: 0; color: #007bff;">✏️ Chỉnh sửa môn học</h3>
        
        <form action="../../actions/timetable_edit.php" method="POST">
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <input type="hidden" name="tkb_id" id="modal_tkb_id">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tên môn học:</label>
                <input type="text" name="subject_name" id="modal_subject_name" required class="form-control">
            </div>
            
            <div style="text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Hủy</button>
                <button type="submit" name="edit_tkb_btn" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Hàm mở modal và điền dữ liệu cũ
    function openEditModal(id, name) {
        document.getElementById('modal_tkb_id').value = id;
        document.getElementById('modal_subject_name').value = name;
        document.getElementById('editModal').style.display = "block";
        document.getElementById('modal_subject_name').focus(); // Focus vào ô nhập
    }

    // Hàm đóng modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = "none";
    }

    // Đóng khi click ra ngoài vùng modal
    window.onclick = function(event) {
        var modal = document.getElementById('editModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
</body>
</html>