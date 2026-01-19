<?php
// pages/parent/timetable.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php"); exit();
}

$student_id = $_GET['student_id'] ?? 0;
// Lấy dữ liệu TKB
$timetable = getTimetableData($conn, $student_id);

include '../../includes/header.php';
?>

<style>
    /* CSS riêng cho bảng TKB giống hình */
    .tkb-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
    .tkb-table th, .tkb-table td { border: 1px solid #999; padding: 10px; vertical-align: top; text-align: center; }
    .tkb-header { background-color: #d1d5db; font-weight: bold; color: #333; } /* Màu xám header */
    .tkb-session { background-color: #e5e7eb; font-weight: bold; width: 100px; vertical-align: middle; } /* Cột buổi */
    .tkb-cell { min-height: 80px; font-size: 0.9em; }
    
    .subject-tag { 
        background: #e3f2fd; padding: 4px; margin-bottom: 4px; border-radius: 4px; 
        display: flex; justify-content: space-between; align-items: center;
        text-align: left;
    }
    .btn-del-subject { color: red; cursor: pointer; text-decoration: none; font-weight: bold; margin-left: 5px;}
</style>

<div class="container">
    <a href="manage_student.php?student_id=<?php echo $student_id; ?>" class="btn" style="background:#6c757d; color:white; margin-bottom:15px;">&larr; Quay lại quản lý bé</a>    
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; background: #d1d5db; padding: 10px; border-radius: 8px 8px 0 0;">
            <button class="btn" style="background: #a7f3d0; color: #065f46; border-radius: 50%;">V</button> <h2 style="margin: 0; font-size: 1.2em;">Thời khóa biểu</h2>
            <button class="btn" style="background: #a7f3d0; color: #065f46; border-radius: 50%;">✏️</button>
        </div>

        <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-top: none; margin-bottom: 20px;">
            <h4>➕ Thêm môn học vào lịch</h4>
            <form action="../../actions/timetable_add.php" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                
                <select name="day" required style="padding: 8px;">
                    <option value="">-- Chọn Thứ --</option>
                    <option value="2">Thứ 2</option>
                    <option value="3">Thứ 3</option>
                    <option value="4">Thứ 4</option>
                    <option value="5">Thứ 5</option>
                    <option value="6">Thứ 6</option>
                    <option value="7">Thứ 7</option>
                    <option value="8">Chủ Nhật</option>
                </select>

                <select name="session" required style="padding: 8px;">
                    <option value="">-- Chọn Buổi --</option>
                    <option value="morning">Buổi Sáng</option>
                    <option value="afternoon">Buổi Chiều</option>
                    <option value="evening">Buổi Tối</option>
                </select>

                <input type="text" name="subject" required placeholder="Tên môn (VD: Toán)" style="padding: 8px; flex: 1;">
                
                <button type="submit" name="add_tkb_btn" class="btn btn-primary">Thêm</button>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="tkb-table">
                <thead>
                    <tr class="tkb-header">
                        <th style="background: #e5e7eb;"></th>
                        <th>T2</th>
                        <th>T3</th>
                        <th>T4</th>
                        <th>T5</th>
                        <th>T6</th>
                        <th>T7</th>
                        <th>CN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sessions_map = ['morning' => 'Sáng', 'afternoon' => 'Chiều', 'evening' => 'Tối'];
                    foreach ($sessions_map as $key => $label): 
                    ?>
                    <tr>
                        <td class="tkb-session"><?php echo $label; ?></td>
                        <?php for($d=2; $d<=8; $d++): ?>
                            <td class="tkb-cell">
                                <?php if (!empty($timetable[$key][$d])): ?>
                                    <?php foreach ($timetable[$key][$d] as $subj): ?>
                                        <div class="subject-tag">
                                            <span><?php echo htmlspecialchars($subj['name']); ?></span>
                                            <a href="../../actions/timetable_delete.php?id=<?php echo $subj['id']; ?>&student_id=<?php echo $student_id; ?>" 
                                               class="btn-del-subject" onclick="return confirm('Xóa môn này?')">×</a>
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
</body>
</html>