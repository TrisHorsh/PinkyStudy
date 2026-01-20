<?php
// includes/functions.php

/**
 * Hàm này kiểm tra và tự động tạo nhiệm vụ hàng ngày cho học sinh
 * Logic: Lấy tất cả mẫu task 'daily' -> Kiểm tra xem hôm nay đã có chưa -> Nếu chưa thì tạo mới
 */
function checkAndCreateDailyTasks($conn, $student_id, $parent_id) {
    // 1. Lấy danh sách các nhiệm vụ mẫu được đánh dấu là 'daily' (Lặp lại hàng ngày) của phụ huynh này
    $sql = "SELECT * FROM task_templates WHERE creator_id = :pid AND task_type = 'daily'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':pid' => $parent_id]);
    $dailyTemplates = $stmt->fetchAll();

    $today = date('Y-m-d'); // Ngày hiện tại

    foreach ($dailyTemplates as $tpl) {
        // 2. Kiểm tra xem nhiệm vụ này HÔM NAY đã được giao cho bé chưa
        // Chúng ta so sánh dựa trên tiêu đề (title) và ngày tạo (created_at)
        $checkSql = "SELECT id FROM assigned_tasks 
                     WHERE student_id = :sid 
                     AND title = :title 
                     AND DATE(created_at) = :today";
        
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([
            ':sid' => $student_id,
            ':title' => $tpl['title'],
            ':today' => $today
        ]);

        // 3. Nếu chưa có -> Tạo mới (Insert)
        if ($checkStmt->rowCount() == 0) {
            $insertSql = "INSERT INTO assigned_tasks (student_id, title, description, points_reward, status, created_at, task_type) 
                          VALUES (:sid, :title, :desc, :points, 'pending', NOW(), 'daily')";
            
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->execute([
                ':sid' => $student_id,
                ':title' => $tpl['title'],
                ':desc' => $tpl['description'],
                ':points' => $tpl['default_points']
            ]);
        }
    }
}


/**
 * Lấy thời khóa biểu và sắp xếp thành dạng mảng 2 chiều [buổi][thứ]
 */
function getTimetableData($conn, $student_id) {
    // Lấy dữ liệu thô
    $sql = "SELECT * FROM timetable WHERE student_id = :sid ORDER BY day_of_week ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':sid' => $student_id]);
    $raw_data = $stmt->fetchAll();

    // Khởi tạo khung dữ liệu rỗng
    $grid = [
        'morning'   => [2=>[], 3=>[], 4=>[], 5=>[], 6=>[], 7=>[], 8=>[]],
        'afternoon' => [2=>[], 3=>[], 4=>[], 5=>[], 6=>[], 7=>[], 8=>[]],
        'evening'   => [2=>[], 3=>[], 4=>[], 5=>[], 6=>[], 7=>[], 8=>[]]
    ];

    // Đổ dữ liệu vào khung
    foreach ($raw_data as $row) {
        $session = $row['time_session']; // morning, afternoon, evening
        $day = $row['day_of_week'];
        
        // Lưu cả tên môn và ID (để phụ huynh có thể xóa)
        $grid[$session][$day][] = [
            'id' => $row['id'],
            'name' => $row['subject_name']
        ];
    }

    return $grid;
}

/**
 * Tự động đánh dấu các nhiệm vụ 'pending' CŨ (trước ngày hôm nay) thành 'failed'
 */
function markOverdueTasksAsFailed($conn, $student_id) {
    // Logic: Nếu status là 'pending' VÀ ngày tạo nhỏ hơn Ngày hiện tại -> Chuyển thành 'failed'
    // Lưu ý: CURDATE() lấy ngày hiện tại (00:00:00).
    
    $sql = "UPDATE assigned_tasks 
            SET status = 'failed' 
            WHERE student_id = :sid 
            AND status = 'pending' 
            AND DATE(created_at) < CURDATE()";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':sid' => $student_id]);
}
?>