<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (isset($_POST['key_type']) && isset($_POST['duration']) && isset($_POST['ip_limit'])) {
        $duration = (int)$_POST['duration'];
        $key_type = $_POST['key_type'];
        $ip_limit = (int)$_POST['ip_limit'];
        
        // Generate key based on type
        switch($key_type) {
            case 'ADMIN':
                $prefix = 'ADMIN_';
                $end_date = '2099-12-31 23:59:59';
                break;
            case 'VIP':
                $prefix = 'THOMAS_VIP_';
                $end_date = date('Y-m-d H:i:s', strtotime("+$duration days"));
                break;
            case 'FREE':
                $prefix = 'THOMAS_FREE_';
                $end_date = date('Y-m-d H:i:s', strtotime("+$duration days"));
                break;
            default:
                throw new Exception('Invalid key type');
        }
        
        $key = $prefix . substr(md5(uniqid()), 0, 6);
        $create_date = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO vip_keys (api_key, key_type, create_date, end_date, ip_limit) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $key, $key_type, $create_date, $end_date, $ip_limit);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => "Đã tạo key $key_type thành công: $key"
            ]);
        } else {
            throw new Exception($conn->error);
        }
    } else {
        throw new Exception('Missing required fields');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();