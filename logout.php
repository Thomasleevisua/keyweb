<?php
session_start();
session_destroy();
header('Location: welcome.php'); // Thay đổi từ login.php sang welcome.php
exit();