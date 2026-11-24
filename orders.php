<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Đơn hàng của tôi</title>
</head>
<body>

<h1>Đơn hàng của bạn</h1>

<table border="1" cellpadding="10">
    <tr>
        <th>Mã đơn</th>
        <th>Tổng tiền</th>
        <th>Trạng thái</th>
        <th>Ngày tạo</th>
    </tr>
    <?php while ($order = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $order['id'] ?></td>
        <td><?= number_format($order['total_amount']) ?>đ</td>
        <td><?= htmlspecialchars($order['status']) ?></td>
        <td><?= $order['created_at'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<a href="index.php">Quay về trang chủ</a>

</body>
</html>