<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']); // thêm họ tên
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!$fullname || !$username || !$email || !$password || !$confirm_password) {
        $error = 'Vui lòng điền đủ thông tin.';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu và xác nhận mật khẩu không khớp.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc email đã được sử dụng.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $username, $email, $password_hash);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = 'Đăng ký thất bại, thử lại sau.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng ký</title>
  <style>
    body {
      background-color: #1e1e1e;
      color: white;
      font-family: Arial;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background-image: url('https://img.freepik.com/free-photo/fast-food-black-slate-with-copy-space_23-2148273110.jpg?semt=ais_hybrid&w=740'); 
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }
    .form {
      background: #2c2c2c;
      padding: 25px;
      border-radius: 12px;
      border: 1px solid white;
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
      width: 320px;
    }
    .form h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    .form input {
      display: block;
      width: 100%;
      margin-bottom: 12px;
      padding: 10px;
      border-radius: 6px;
      border: none;
      font-size: 14px;
    }
    .form button {
      width: 100%;
      padding: 10px;
      background-color: #ff7315;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }
    .form button:hover {
      background-color: #45a049;
    }
    .error {
      color: #f44336;
      margin-bottom: 15px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="form">
    <h2>Đăng ký tài khoản</h2>
    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <input type="text" name="fullname" placeholder="Họ và tên" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
      <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
      <input type="text" name="username" placeholder="Tên đăng nhập" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
      <input type="password" name="password" placeholder="Mật khẩu" required>
      <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
      <button type="submit">Đăng ký</button>
    </form>
  </div>
</body>
</html>