<?php
// actions/chat_send.php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (:s, :r, :m)");
        $stmt->execute([':s' => $sender_id, ':r' => $receiver_id, ':m' => $message]);
        echo "success";
    } else {
        echo "empty";
    }
}
?>