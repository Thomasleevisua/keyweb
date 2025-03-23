<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
require_once '../../../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'msg' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $APIKey = $_GET['APIKey'] ?? '';
    $end_date_local = (int)($_GET['end_date_local'] ?? 0);
    
    if (!$APIKey || !$end_date_local) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Thiếu thông tin key hoặc ngày hết hạn'
        ]);
        exit;
    }

    $stmt = $conn->prepare("SELECT end_date FROM vip_keys WHERE api_key = ? AND status = 'active'");
    $stmt->bind_param("s", $APIKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $server_end_date = strtotime($row['end_date']);
        
        if ($server_end_date !== $end_date_local) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Thời gian hết hạn không khớp',
                'server_end_date' => $server_end_date
            ]);
            exit;
        }

        if ($server_end_date < time()) {
            $conn->query("UPDATE vip_keys SET status = 'expired' WHERE api_key = '$APIKey'");
            echo json_encode([
                'status' => 'error',
                'msg' => 'Key đã hết hạn'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'msg' => 'Key còn hạn sử dụng'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Key không tồn tại hoặc đã bị khóa'
        ]);
    }
}

$conn->close();