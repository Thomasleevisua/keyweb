<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Kết nối database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Xử lý đăng nhập
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND password = PASSWORD(?) AND status = 'active'");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: index.php');
        exit();
    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đăng Nhập - Key Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Đăng Nhập</h3>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Tên đăng nhập</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Mật khẩu</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Đăng Nhập</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="welcome.php" class="text-decoration-none">← Quay về trang chủ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>