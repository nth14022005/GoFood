<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$username || !$password) {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id_from_db, $password_from_db);
            $stmt->fetch();

            if (password_verify($password, $password_from_db)) {
                // Lưu thông tin user vào session
                $_SESSION['user_id'] = $user_id_from_db;
                $_SESSION['username'] = $username;

                header("Location: index.php");
                exit;
            } else {
                $error = 'Sai mật khẩu.';
            }
        } else {
            $error = 'Tên đăng nhập không tồn tại.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>Đăng nhập</title>
  <style>
  body {
    background-image: url('https://img.freepik.com/free-photo/fast-food-black-slate-with-copy-space_23-2148273110.jpg?semt=ais_hybrid&w=740'); 
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: white;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
  }

  .form {
    background: rgba(44, 44, 44, 0.9);
    padding: 30px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
    width: 320px;
  }

  .form h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 22px;
    color: #ff7315;
  }

  .form input {
    display: block;
    width: 100%;
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    outline: none;
    transition: border-color 0.3s, box-shadow 0.3s;
  }

  .form input:focus {
    border-color: #ff7315;
    box-shadow: 0 0 5px rgba(255, 115, 21, 0.5);
  }

  .form button {
  width: 100%;
  padding: 12px;
  background: #ff7315;
  color: #fff;
  border: 2px solid #ff7315; /* Thêm viền để hover hiển thị rõ */
  border-radius: 8px;
  font-weight: bold;
  font-size: 15px;
  cursor: pointer;
  transition: background 0.3s, color 0.3s, transform 0.2s;
}

.form button:hover {
  background: #fff;
  color: #ff7315;
  transform: translateY(-2px);
}


  .error {
    color: #f44336;
    margin-bottom: 15px;
    text-align: center;
    font-size: 14px;
  }

  .links {
    margin-top: 15px;
    font-size: 14px;
    color: #ccc;
    text-align: center;
  }

  .links a {
    color: #ff7315;
    text-decoration: none;
    font-weight: bold;
  }

  .links a:hover {
    text-decoration: underline;
  }
</style>

<body>
  <div class="form">
    <h2>Đăng nhập</h2>
    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <input type="text" name="username" placeholder="Tên đăng nhập" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
      <input type="password" name="password" placeholder="Mật khẩu" required>
      <button type="submit">Đăng nhập</button>
    </form>

    <div class="links">
      Nếu bạn chưa có tài khoản thì hãy 
      <a href="register.php">đăng ký</a><br>
      <a href="quenmatkhau.html">Quên mật khẩu?</a>
    </div>
  </div>
</body>
</html>
