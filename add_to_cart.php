<?php
session_start();
include("db_connect.php");

// Kiểm tra đăng nhập
if (!isset($_SESSION["user_id"])) {
    die("Bạn cần đăng nhập");
}

// Kiểm tra yêu cầu thêm vào giỏ hàng
if (isset($_POST["add_to_cart"]) && isset($_GET["id"])) {
    $user_id = $_SESSION["user_id"]; // ID người dùng từ session
    $product_id = intval($_GET["id"]); // ID sản phẩm
    $quantity = intval($_POST["quantity"]); // Số lượng thêm vào

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    $checkQuery = "SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $user_id, $product_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        // Nếu đã có, tăng số lượng
        $updateQuery = "UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("iii", $quantity, $user_id, $product_id);
        $updateStmt->execute();
    } else {
        // Nếu chưa có, thêm mới vào giỏ
        $insertQuery = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iii", $user_id, $product_id, $quantity);
        $insertStmt->execute();
    }

    // Chuyển hướng về trang giỏ hàng
    header("Location: cart.php");
    exit();
}
?>