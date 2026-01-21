<?php
// actions/chat_fetch.php
session_start();
require_once '../config/db_connect.php';

if (isset($_POST['receiver_id']) && isset($_SESSION['user_id'])) {
    $my_id = $_SESSION['user_id'];
    $other_id = $_POST['receiver_id'];

    // SỬA LỖI SQLSTATE[HY093]: Đặt tên tham số riêng biệt cho từng vị trí
    $sql = "SELECT * FROM messages 
            WHERE (sender_id = :my_id1 AND receiver_id = :other_id1) 
               OR (sender_id = :other_id2 AND receiver_id = :my_id2) 
            ORDER BY created_at ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':my_id1' => $my_id, 
        ':other_id1' => $other_id,
        ':other_id2' => $other_id, 
        ':my_id2' => $my_id
    ]);
    
    $messages = $stmt->fetchAll();

    // Đánh dấu đã đọc
    $update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = :other_id AND receiver_id = :my_id");
    $update->execute([':other_id' => $other_id, ':my_id' => $my_id]);

    // Xuất HTML
    foreach ($messages as $msg) {
        $is_me = ($msg['sender_id'] == $my_id);
        $class = $is_me ? 'chat-me' : 'chat-you';
        $time = date('H:i', strtotime($msg['created_at']));
        $class = ($msg['sender_id'] == $my_id) ? "me" : "you";
        
        echo '<div class="msg-row ' . $class . '">';
        echo '    <div class="msg-bubble">' . htmlspecialchars($msg['message']) . '</div>';
        echo '</div>';
    }
}
?>