<!DOCTYPE html>
<html>
<head>
    <title>Welcome - Key Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .welcome-box {
            max-width: 500px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .logo {
            margin-bottom: 30px;
        }
        .btn {
            padding: 10px 30px;
            margin: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-box">
            <div class="logo">
                <h1>Key Management System</h1>
                <p class="text-muted">Quản lý và phát hành key</p>
            </div>
            <?php if(isset($_SESSION['admin_logged_in'])): ?>
                <a href="index.php" class="btn btn-primary">Vào Trang Quản Lý</a>
                <a href="logout.php" class="btn btn-danger">Đăng Xuất</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Đăng Nhập</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>