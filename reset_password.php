<?php
include 'db_connect.php';

// Hàm giúp bảo mật khi in giá trị
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Bước 1: Lấy token từ URL
if (!isset($_GET['token'])) {
    die("Liên kết không hợp lệ.");
}
$token = $_GET['token'];

// Bước 2: Kiểm tra token trong DB và thời gian hết hạn
$sql = "SELECT user_id, expires_at FROM password_resets WHERE reset_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Liên kết không tồn tại hoặc đã được sử dụng.");
}

$row = $result->fetch_assoc();
$expires_at = $row['expires_at'];
$user_id = $row['user_id'];

if (strtotime($expires_at) < time()) {
    die("Liên kết đã hết hạn. Vui lòng thực hiện lại yêu cầu quên mật khẩu.");
}

// Bước 3: Nếu form được submit (POST), xử lý cập nhật mật khẩu mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Kiểm tra mật khẩu có nhập và khớp không
    if (empty($password)) {
        $error = "Vui lòng nhập mật khẩu mới.";
    } elseif ($password !== $password_confirm) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Hash mật khẩu
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu mới vào bảng users
        $sql_update = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $password_hash, $user_id);
        $stmt_update->execute();

        // Xóa token đã dùng
        $sql_delete = "DELETE FROM password_resets WHERE reset_token = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("s", $token);
        $stmt_delete->execute();

        // Chuyển hướng về trang đăng nhập sau khi thành công
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Đặt lại mật khẩu - GoFood</title>
    <style>
        body {
            font-family: Arial, sans-serif; background-color: #121212; color: #eee;
            display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;
        }
        .container {
            background-color: #1e1e1e; padding: 30px; border-radius: 8px; width: 350px;
            box-shadow: 0 0 15px rgba(0,0,0,0.7);
        }
        h2 {
            margin-bottom: 20px; text-align: center;
        }
        label {
            display: block; margin-bottom: 8px; color: #ccc;
        }
        input {
            width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 5px;
            border: 1px solid #444; background-color: #222; color: #eee;
            box-sizing: border-box;
        }
        button {
            width: 100%; padding: 12px; background-color: #28a745; border: none;
            border-radius: 5px; font-size: 16px; color: white; cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #218838;
        }
        .error {
            color: #ff6b6b; margin-bottom: 15px; text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Đặt lại mật khẩu</h2>

    <?php if (!empty($error)) : ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="password">Mật khẩu mới:</label>
        <input type="password" id="password" name="password" required />

        <label for="password_confirm">Xác nhận mật khẩu mới:</label>
        <input type="password" id="password_confirm" name="password_confirm" required />

        <button type="submit">Cập nhật mật khẩu</button>
    </form>
</div>
</body>
</html>