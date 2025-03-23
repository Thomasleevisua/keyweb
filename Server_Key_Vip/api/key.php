<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once '../../config.php';

// Test database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'msg' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Add debug logging
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("APIKey: " . ($_GET['APIKey'] ?? 'not set'));

// Xử lý GET request - Kiểm tra key
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $APIKey = $_GET['APIKey'] ?? '';
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("SELECT * FROM vip_keys WHERE api_key = ? AND status = 'active'");
    $stmt->bind_param("s", $APIKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Kiểm tra giới hạn IP
        if ($row['ip_limit'] > 0) {
            // Đếm số IP đã sử dụng key này
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT ip_address) as ip_count FROM key_logs WHERE key_id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $ip_count = $stmt->get_result()->fetch_assoc()['ip_count'];
            
            if ($ip_count >= $row['ip_limit'] && !in_array($client_ip, explode(',', $row['allowed_ip']))) {
                echo json_encode([
                    'status' => 'error',
                    'msg' => 'Key đã đạt giới hạn số IP có thể sử dụng'
                ]);
                exit;
            }
        }
        
        // Lưu IP vào allowed_ip nếu chưa có
        if (!$row['allowed_ip']) {
            $conn->query("UPDATE vip_keys SET allowed_ip = '$client_ip' WHERE id = {$row['id']}");
        } else if (!in_array($client_ip, explode(',', $row['allowed_ip']))) {
            $allowed_ips = $row['allowed_ip'] . ',' . $client_ip;
            $conn->query("UPDATE vip_keys SET allowed_ip = '$allowed_ips' WHERE id = {$row['id']}");
        }

        // Log IP usage
        $stmt = $conn->prepare("INSERT INTO key_logs (key_id, device_id, action, ip_address) VALUES (?, ?, 'check', ?)");
        $stmt->bind_param("iss", $row['id'], $row['device_id'], $client_ip);
        $stmt->execute();
        
        // Key ADMIN không kiểm tra hạn và device_id
        if ($row['key_type'] === 'ADMIN') {
            echo json_encode([
                'status' => 'success',
                'key_type' => 'ADMIN',
                'create_date' => strtotime($row['create_date']),
                'end_date' => strtotime('2099-12-31 23:59:59'),
                'device_ID' => null, // ADMIN key không cần device_id
                'client_ip' => $client_ip
            ]);
            exit;
        }
        
        $now = time();
        $end_date = strtotime($row['end_date']);
        
        // Kiểm tra hết hạn
        if ($end_date < $now) {
            $conn->query("UPDATE vip_keys SET status = 'expired' WHERE api_key = '$APIKey'");
            echo json_encode([
                'status' => 'error',
                'msg' => 'Key đã hết hạn'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'key_type' => $row['key_type'],
            'create_date' => strtotime($row['create_date']),
            'end_date' => $end_date,
            'device_ID' => $row['device_id'],
            'client_ip' => $client_ip
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Key không hợp lệ hoặc đã bị khóa'
        ]);
    }
}

// Xử lý POST request - Cập nhật device ID
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $APIKey = $data['APIKey'] ?? '';
    $device_ID = $data['device_ID'] ?? '';
    
    if (!$APIKey || !$device_ID) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Thiếu thông tin key hoặc device ID'
        ]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE vip_keys SET device_id = ?, last_used = NOW() WHERE api_key = ? AND device_id IS NULL");
    $stmt->bind_param("ss", $device_ID, $APIKey);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'msg' => 'Đã kích hoạt key thành công'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Key đã được sử dụng trên thiết bị khác'
        ]);
    }
}

$conn->close();