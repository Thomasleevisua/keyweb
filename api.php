<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'msg' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $APIKey = $_GET['APIKey'] ?? '';
    
    $stmt = $conn->prepare("SELECT create_date, end_date, device_id FROM vip_keys WHERE api_key = ?");
    $stmt->bind_param("s", $APIKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'create_date' => strtotime($row['create_date']),
            'end_date' => strtotime($row['end_date']),
            'device_ID' => $row['device_id']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Key không hợp lệ'
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $APIKey = $data['APIKey'] ?? '';
    $device_ID = $data['device_ID'] ?? '';
    
    $stmt = $conn->prepare("UPDATE vip_keys SET device_id = ? WHERE api_key = ? AND device_id IS NULL");
    $stmt->bind_param("ss", $device_ID, $APIKey);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'msg' => 'Đã cập nhật device ID'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Không thể cập nhật device ID'
        ]);
    }
}