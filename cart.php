<?php
session_start();
include("db_connect.php"); // file kết nối DB

if (!isset($_SESSION["user_id"])) {
    die("Bạn cần đăng nhập để xem giỏ hàng.");
}

$user_id = $_SESSION["user_id"];

// Xử lý cập nhật số lượng
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update"])) {
    foreach ($_POST["qty"] as $product_id => $qty) {
        $qty = intval($qty);
        if ($qty <= 0) {
            $deleteQuery = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        } else {
            $updateQuery = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("iii", $qty, $user_id, $product_id);
            $stmt->execute();
        }
    }
    header("Location: cart.php");
    exit();
}

// Lấy giỏ hàng từ DB
$query = "
    SELECT p.product_id as id, p.name, p.price, ci.quantity 
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += $row["price"] * $row["quantity"];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>
</head>
<body>
    <h1>Giỏ hàng của bạn</h1>

    <?php if (empty($items)): ?>
        <p>Giỏ hàng đang trống.</p>
    <?php else: ?>
    <form method="POST" action="cart.php">
        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>Tên món</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Tổng</th>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item["name"]) ?></td>
                <td><?= number_format($item["price"], 0, ',', '.') ?>đ</td>
                <td>
                    <input type="number" name="qty[<?= $item["id"] ?>]" value="<?= $item["quantity"] ?>" min="0">
                </td>
                <td><?= number_format($item["price"] * $item["quantity"], 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3"><strong>Tổng cộng</strong></td>
                <td><strong><?= number_format($total, 0, ',', '.') ?>đ</strong></td>
            </tr>
        </table>
        <br>
        <input type="submit" name="update" value="Cập nhật giỏ hàng">
    </form>
    <?php endif; ?>
</body>
</html>