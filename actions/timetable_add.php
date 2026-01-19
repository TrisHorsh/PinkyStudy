<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tkb_btn'])) {
    $student_id = $_POST['student_id'];
    $day = $_POST['day'];
    $session = $_POST['session'];
    $subject = trim($_POST['subject']);

    if (!empty($subject)) {
        $stmt = $conn->prepare("INSERT INTO timetable (student_id, day_of_week, time_session, subject_name) VALUES (:sid, :day, :sess, :subj)");
        $stmt->execute([':sid'=>$student_id, ':day'=>$day, ':sess'=>$session, ':subj'=>$subject]);
        $_SESSION['success'] = "Đã thêm môn học!";
    }
    header("Location: ../pages/parent/timetable.php?student_id=" . $student_id);
    exit();
}
?>