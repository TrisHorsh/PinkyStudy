<?php
// actions/chat_check_new.php
session_start();
require_once '../config/db_connect.php';

if (isset($_POST['sender_id']) && isset($_SESSION['user_id'])) {
    $my_id = $_SESSION['user_id'];      // Là người nhận (Receiver)
    $other_id = $_POST['sender_id'];    // Là người gửi (Sender) - người mình đang chat cùng

    // Đếm số tin nhắn mà người kia gửi cho mình nhưng trạng thái is_read = 0
    $sql = "SELECT COUNT(*) as unread FROM messages 
            WHERE sender_id = :other_id 
            AND receiver_id = :my_id 
            AND is_read = 0";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':other_id' => $other_id, ':my_id' => $my_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['unread'];
} else {
    echo "0";
}
?>