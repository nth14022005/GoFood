<?php
$servername = getenv("GOFOOD_DB_HOST") ?: "localhost"; // Máy chủ MySQL, thường là localhost
$username = getenv("GOFOOD_DB_USER") ?: "root";    // Tên đăng nhập MySQL (mặc định của XAMPP là root)
$password = getenv("GOFOOD_DB_PASSWORD") ?: "";         // Mật khẩu MySQL (mặc định của XAMPP là rỗng)
$dbname = getenv("GOFOOD_DB_NAME") ?: "gofood_db"; // Tên cơ sở dữ liệu của bạn

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
  die("Kết nối thất bại: " . $conn->connect_error);
}

// Đặt charset thành UTF-8 để hỗ trợ tiếng Việt
if (!$conn->set_charset("utf8mb4")) {
    printf("Lỗi khi đặt bộ ký tự utf8mb4: %s\n", $conn->error);
    exit();
}
?>