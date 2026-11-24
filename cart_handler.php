<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.', 'cart_item_count' => 0]);
    exit;
}

// Hàm lấy số lượng sản phẩm hiện tại trong giỏ hàng của user
function get_cart_item_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return (int)($row['total_items'] ?? 0);
        }
        $stmt->close();
    }
    return 0;
}

switch ($action) {
    case 'add':
        $product_id = $_POST['product_id'] ?? null;
        $quantity = (isset($_POST['quantity']) && is_numeric($_POST['quantity']) && $_POST['quantity'] > 0) ? (int)$_POST['quantity'] : 1;

        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID sản phẩm.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            exit;
        }

        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $stmt_check = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        if (!$stmt_check) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare check).', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            exit;
        }
        $stmt_check->bind_param("ii", $user_id, $product_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($row_check = $result_check->fetch_assoc()) {
            // Sản phẩm đã tồn tại, cập nhật số lượng
            $new_quantity = $row_check['quantity'] + $quantity;
            $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
            if (!$stmt_update) {
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare update).', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
                exit;
            }
            $stmt_update->bind_param("iii", $new_quantity, $user_id, $product_id);
            if ($stmt_update->execute()) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật số lượng sản phẩm trong giỏ.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật giỏ hàng.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            }
            $stmt_update->close();
        } else {
            // Sản phẩm chưa có, thêm mới
            $stmt_insert = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            if (!$stmt_insert) {
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare insert).', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
                exit;
            }
            $stmt_insert->bind_param("iii", $user_id, $product_id, $quantity);
            if ($stmt_insert->execute()) {
                echo json_encode(['success' => true, 'message' => 'Đã thêm sản phẩm vào giỏ.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm vào giỏ hàng.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
        break;

    case 'get':
        $stmt_get = $conn->prepare("SELECT ci.product_id, p.name, p.price, ci.quantity, p.image_url FROM cart_items ci JOIN products p ON ci.product_id = p.product_id WHERE ci.user_id = ? ORDER BY ci.added_at DESC");
        if (!$stmt_get) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare get).', 'items' => [], 'totalPrice' => 0, 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            exit;
        }
        $stmt_get->bind_param("i", $user_id);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $items = [];
        $total_price = 0;
        while ($row = $result_get->fetch_assoc()) {
            $items[] = $row;
            $total_price += $row['price'] * $row['quantity'];
        }
        echo json_encode(['success' => true, 'items' => $items, 'totalPrice' => $total_price, 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
        $stmt_get->close();
        break;

    case 'update_quantity':
        $product_id = $_POST['product_id'] ?? null;
        $quantity = (isset($_POST['quantity']) && is_numeric($_POST['quantity']) && $_POST['quantity'] >= 0) ? (int)$_POST['quantity'] : -1;

        if (!$product_id || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            exit;
        }

        if ($quantity == 0) {
            // Nếu số lượng là 0, xóa sản phẩm
            $stmt_delete_item = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            if (!$stmt_delete_item) {
                 echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare delete item).', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
                 exit;
            }
            $stmt_delete_item->bind_param("ii", $user_id, $product_id);
            if ($stmt_delete_item->execute()) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            }
            $stmt_delete_item->close();
        } else {
            // Cập nhật số lượng
            $stmt_update_qty = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
             if (!$stmt_update_qty) {
                 echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare update qty).', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
                 exit;
            }
            $stmt_update_qty->bind_param("iii", $quantity, $user_id, $product_id);
            if ($stmt_update_qty->execute()) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật số lượng.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật số lượng.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            }
            $stmt_update_qty->close();
        }
        break;

    case 'clear':
        $stmt_clear = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        if (!$stmt_clear) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare clear).', 'cart_item_count' => 0]);
            exit;
        }
        $stmt_clear->bind_param("i", $user_id);
        if ($stmt_clear->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng.', 'cart_item_count' => 0]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa giỏ hàng.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
        }
        $stmt_clear->close();
        break;

    case 'remove':
        $product_id = $_POST['product_id'] ?? $_GET['product_id'] ?? null;

        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID sản phẩm để xóa.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
            exit;
        }

        $stmt_delete_item = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        if (!$stmt_delete_item) {
             echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare delete item).', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
             exit;
        }
        $stmt_delete_item->bind_param("ii", $user_id, $product_id);
        if ($stmt_delete_item->execute()) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
        }
        $stmt_delete_item->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.', 'cart_item_count' => get_cart_item_count($conn, $user_id)]);
        break;
}

$conn->close();
?> 