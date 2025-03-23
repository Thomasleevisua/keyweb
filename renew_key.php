<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['status' => 'error', 'message' => ''];

try {
    if (!isset($_POST['key_id']) || !isset($_POST['duration'])) {
        throw new Exception('Thiếu thông tin gia hạn');
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $key_id = (int)$_POST['key_id'];
    $duration = (int)$_POST['duration'];

    // Lấy thông tin key hiện tại
    $stmt = $conn->prepare("SELECT end_date FROM vip_keys WHERE id = ?");
    $stmt->bind_param("i", $key_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Key không tồn tại');
    }

    $row = $result->fetch_assoc();
    $current_end_date = new DateTime($row['end_date']);
    
    // Thêm số ngày gia hạn
    $current_end_date->modify("+$duration days");
    $new_end_date = $current_end_date->format('Y-m-d H:i:s');

    // Cập nhật ngày hết hạn mới
    $stmt = $conn->prepare("UPDATE vip_keys SET end_date = ? WHERE id = ?");
    $stmt->bind_param("si", $new_end_date, $key_id);
    
    if ($stmt->execute()) {
        $response = [
            'status' => 'success',
            'message' => "Đã gia hạn thêm $duration ngày",
            'new_end_date' => $new_end_date
        ];
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

$conn->close();