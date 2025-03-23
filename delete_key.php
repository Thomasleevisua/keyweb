<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (isset($_POST['key_id'])) {
    $key_id = (int)$_POST['key_id'];
    
    $stmt = $conn->prepare("DELETE FROM vip_keys WHERE id = ?");
    $stmt->bind_param("i", $key_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Đã xóa key thành công'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Lỗi khi xóa key: ' . $conn->error
        ]);
    }
}

$conn->close();