<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['error' => 'Bạn chưa đăng nhập']);
    exit;
}

$product_id = $_POST['product_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Kiểm tra giỏ hàng user
$stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
$stmt->fetch();
$stmt->close();

if (!$cart_id) {
    $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_id = $stmt->insert_id;
    $stmt->close();
}

// Lấy giá sản phẩm
$stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($price);
$stmt->fetch();
$stmt->close();

if (!$price) {
    echo json_encode(['error' => 'Sản phẩm không tồn tại']);
    exit;
}

// Kiểm tra món đã có trong giỏ chưa
$stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
$stmt->bind_param("ii", $cart_id, $product_id);
$stmt->execute();
$stmt->bind_result($current_qty);
$stmt->fetch();
$stmt->close();

if ($current_qty) {
    // Cập nhật số lượng
    $new_qty = $current_qty + $quantity;
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $new_qty, $cart_id, $product_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Thêm món mới
    $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $cart_id, $product_id, $quantity, $price);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
?>