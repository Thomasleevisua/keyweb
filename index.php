<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Kết nối database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Xử lý tạo key mới
if (isset($_POST['create_key'])) {
    $duration = (int)$_POST['duration'];
    $key_type = $_POST['key_type'];
    
    // Chọn prefix theo loại key
    switch($key_type) {
        case 'ADMIN':
            $prefix = ADMIN_KEY_PREFIX;
            $end_date = '2099-12-31 23:59:59'; // Không giới hạn thời gian
            break;
        case 'VIP':
            $prefix = VIP_KEY_PREFIX;
            $end_date = date('Y-m-d H:i:s', strtotime("+$duration days"));
            break;
        case 'FREE':
            $prefix = FREE_KEY_PREFIX;
            $end_date = date('Y-m-d H:i:s', strtotime("+$duration days"));
            break;
    }
    
    $key = $prefix . substr(md5(uniqid()), 0, 6); // 6 ký tự random
    $create_date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO vip_keys (api_key, key_type, create_date, end_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $key, $key_type, $create_date, $end_date);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Tạo key $key_type thành công: " . $key;
    } else {
        $_SESSION['error'] = "Lỗi khi tạo key: " . $conn->error;
    }
}

// Xử lý xóa key
if (isset($_POST['delete_key'])) {
    $key_id = (int)$_POST['key_id'];
    $stmt = $conn->prepare("DELETE FROM vip_keys WHERE id = ?");
    $stmt->bind_param("i", $key_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Đã xóa key thành công";
    }
}

// Xử lý gia hạn key
if (isset($_POST['renew_key'])) {
    $key_id = (int)$_POST['key_id'];
    $duration = (int)$_POST['renew_duration'];
    $stmt = $conn->prepare("UPDATE vip_keys SET end_date = DATE_ADD(end_date, INTERVAL ? DAY), renewed_date = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $duration, $key_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Đã gia hạn key thành công";
    }
}

// Lấy danh sách key
$result = $conn->query("SELECT * FROM vip_keys ORDER BY create_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản Lý Key</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .toast-success { background-color: #51A351 !important; }
        .toast-error { background-color: #BD362F !important; }
        .toast-info { background-color: #2F96B4 !important; }
        .toast-warning { background-color: #F89406 !important; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Add logout button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản Lý Key</h2>
            <a href="logout.php" class="btn btn-danger">Đăng xuất</a>
        </div>
        
        <!-- Form tạo key -->
        <div class="card mb-4">
            <div class="card-body">
                <h4>Tạo Key Mới</h4>
                <form method="POST" id="createKeyForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label>Loại Key</label>
                            <select name="key_type" class="form-control" required>
                                <option value="VIP">VIP</option>
                                <option value="FREE">FREE</option>
                                <option value="ADMIN">ADMIN</option>
                            </select>
                        </div>
                        <div class="col-md3">
                            <label>Thời hạn (ngày)</label>
                            <input type="number" name="duration" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Giới hạn IP (0 = không giới hạn)</label>
                            <input type="number" name="ip_limit" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Tạo Key</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Danh sách key -->
        <div class="card">
            <div class="card-body">
                <h4>Danh Sách Key</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Loại</th>
                            <th>Ngày Tạo</th>
                            <th>Ngày Hết Hạn</th>
                            <th>Device ID</th>
                            <th>IP đã dùng</th>
                            <th>Giới hạn IP</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['api_key']); ?></td>
                            <td><span class="badge bg-<?php echo $row['key_type'] == 'VIP' ? 'primary' : 'secondary'; ?>"><?php echo $row['key_type']; ?></span></td>
                            <td><?php echo $row['create_date']; ?></td>
                            <td><?php echo $row['end_date']; ?></td>
                            <td><?php echo $row['device_id'] ?: 'Chưa sử dụng'; ?></td>
                            <td><?php echo $row['allowed_ip'] ?: 'Chưa có IP'; ?></td>
                            <td><?php echo $row['ip_limit'] ?: 'Không giới hạn'; ?></td>
                            <td>
                                <?php 
                                if (strtotime($row['end_date']) < time()) {
                                    echo '<span class="badge bg-danger">Hết hạn</span>';
                                } else if ($row['device_id']) {
                                    echo '<span class="badge bg-success">Đang sử dụng</span>';
                                } else {
                                    echo '<span class="badge bg-warning">Chưa sử dụng</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <form class="d-inline renewKeyForm">
                                    <input type="hidden" name="key_id" value="<?php echo $row['id']; ?>">
                                    <input type="number" name="duration" placeholder="Số ngày" class="form-control form-control-sm d-inline" style="width:80px" required>
                                    <button type="submit" class="btn btn-warning btn-sm">Gia hạn</button>
                                </form>
                                <!-- Form xóa -->
                                <button class="btn btn-danger btn-sm deleteKey" data-id="<?php echo $row['id']; ?>">Xóa</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Popup notification function
        function showPopup(title, text, icon = 'success') {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        }

        // Delete confirmation popup
        function confirmDelete(keyId) {
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Bạn có chắc muốn xóa key này không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteKey(keyId);
                }
            });
        }

        // AJAX handlers
        $('#createKeyForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'create_key.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        Swal.fire({
                            title: 'Thành công!',
                            html: `Đã tạo key mới:<br><b>${response.key}</b>`,  // Hiển thị key với HTML
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Lỗi!', response.message, 'error');
                    }
                }
            });
        });

        $('.deleteKey').click(function() {
            confirmDelete($(this).data('id'));
        });

        function deleteKey(keyId) {
            $.ajax({
                url: 'delete_key.php',
                method: 'POST',
                data: {key_id: keyId},
                success: function(response) {
                    if(response.status === 'success') {
                        showPopup('Thành công!', 'Đã xóa key thành công');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showPopup('Lỗi!', response.message, 'error');
                    }
                }
            });
        }

        $('.renewKeyForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'renew_key.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if(response.status === 'success') {
                        showPopup('Thành công!', 'Đã gia hạn key thành công');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showPopup('Lỗi!', response.message, 'error');
                    }
                }
            });
        });

        // Show PHP session messages
        <?php if(isset($_SESSION['message'])): ?>
            showPopup(
                '<?php echo $_SESSION['message_title'] ?? 'Thông báo'; ?>',
                '<?php echo $_SESSION['message']; ?>',
                '<?php echo $_SESSION['message_type'] ?? 'success'; ?>'
            );
            <?php 
                unset($_SESSION['message']); 
                unset($_SESSION['message_title']);
                unset($_SESSION['message_type']); 
            ?>
        <?php endif; ?>
    </script>
</body>
</html>