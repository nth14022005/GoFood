<?php
// momo_ipn.php
// File này nhận thông báo thanh toán tức thời (IPN) từ MoMo server.

include 'db_connect.php';
// Nếu bạn lưu trữ key ở file config riêng thì include vào đây
// include 'momo_config.php'; // Ví dụ: chứa $momo_secret_key

// Lấy thông tin Secret Key của bạn từ cấu hình an toàn
$momo_secret_key = (getenv("GOFOOD_MOMO_SECRET_KEY") ?: ""); // THAY THẾ BẰNG SECRET KEY THẬT CỦA BẠN

// Nhận dữ liệu MoMo gửi sang (dạng JSON POST)
$json_data = file_get_contents('php://input');
$momo_response = json_decode($json_data, true);

// Ghi log lại dữ liệu nhận được từ MoMo để debug (quan trọng khi phát triển)
// file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " IPN Received: " . $json_data . "\n", FILE_APPEND);

if ($momo_response && isset($momo_response['signature'])) {
    // Tách chữ ký từ response
    $received_signature = $momo_response['signature'];

    // Xây dựng lại chuỗi rawHashData từ các tham số MoMo gửi sang (theo đúng thứ tự và định dạng MoMo yêu cầu cho IPN)
    // Thứ tự và danh sách các trường có thể khác với lúc bạn tạo request, cần xem tài liệu IPN của MoMo
    // Ví dụ (CẦN KIỂM TRA LẠI VỚI TÀI LIỆU MOMO CHO IPN):
    // $rawHash = "partnerCode=" . $momo_response['partnerCode'] .
    //            "&accessKey=" . $momo_response['accessKey'] . // Hoặc accessKey của bạn nếu MoMo không gửi lại
    //            "&requestId=" . $momo_response['requestId'] .
    //            "&amount=" . $momo_response['amount'] .
    //            "&orderId=" . $momo_response['orderId'] .
    //            "&orderInfo=" . $momo_response['orderInfo'] .
    //            "&orderType=" . $momo_response['orderType'] . // Ví dụ 'momo_wallet'
    //            "&transId=" . $momo_response['transId'] . // Mã giao dịch của MoMo
    //            "&message=" . $momo_response['message'] .
    //            "&localMessage=" . $momo_response['localMessage'] .
    //            "&responseTime=" . $momo_response['responseTime'] .
    //            "&errorCode=" . $momo_response['resultCode'] . // Chú ý: errorCode trong IPN có thể là resultCode
    //            "&payType=" . $momo_response['payType'];
    //            // "&extraData=" . $momo_response['extraData']; // Nếu có gửi

    // Tạo chữ ký của bạn để so sánh
    // $calculated_signature = hash_hmac("sha256", $rawHash, $momo_secret_key);
    
    // ***** QUAN TRỌNG: BẠN CẦN TỰ XÂY DỰNG $rawHash VÀ $calculated_signature THEO ĐÚNG TÀI LIỆU IPN CỦA MOMO *****
    // Code phía trên chỉ là VÍ DỤ, các tham số và thứ tự có thể không chính xác.

    // Để test nhanh (KHÔNG DÙNG CHO PRODUCTION), bạn có thể tạm bỏ qua kiểm tra chữ ký, nhưng NÊN IMPLEMENT ĐẦY ĐỦ
    $signature_is_valid = false; // Mặc định là không hợp lệ
    
    // TODO: Bỏ comment và hoàn thiện phần tạo $rawHash và kiểm tra chữ ký
    /*
    if ($received_signature == $calculated_signature) {
        $signature_is_valid = true;
    } else {
        // Ghi log lỗi chữ ký không khớp
        file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " IPN Signature Mismatch. Received: " . $received_signature . " | Calculated: " . $calculated_signature . " | RawHash: " . $rawHash . "\n", FILE_APPEND);
    }
    */

    // GIẢ LẬP CHỮ KÝ HỢP LỆ ĐỂ TEST LOGIC BÊN DƯỚI (XÓA KHI CÓ CODE KIỂM TRA CHỮ KÝ THẬT)
    if (isset($momo_response['resultCode'])) { // Chỉ cần có resultCode để test
         $signature_is_valid = true;
    }


    if ($signature_is_valid) {
        $order_id = $momo_response['orderId'] ?? null;
        $result_code = $momo_response['resultCode'] ?? null; // Mã kết quả giao dịch từ MoMo
        $momo_trans_id = $momo_response['transId'] ?? null; // Mã giao dịch của MoMo
        $message = $momo_response['message'] ?? '';

        if ($order_id && $result_code !== null) {
            $conn->begin_transaction();
            try {
                // Lấy thông tin user_id từ order_id để xóa giỏ hàng
                $user_id_to_clear_cart = null;
                $stmt_get_user = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
                if ($stmt_get_user) {
                    $stmt_get_user->bind_param("s", $order_id);
                    $stmt_get_user->execute();
                    $result_user = $stmt_get_user->get_result();
                    if($row_user = $result_user->fetch_assoc()){
                        $user_id_to_clear_cart = $row_user['user_id'];
                    }
                    $stmt_get_user->close();
                }

                if ($result_code == 0) { // Thanh toán thành công
                    $new_payment_status = 'Đã thanh toán';
                    // Cập nhật trạng thái đơn hàng và mã giao dịch MoMo
                    $stmt_update = $conn->prepare("UPDATE orders SET payment_status = ?, momo_trans_id = ? WHERE order_id = ? AND payment_status = 'Chờ thanh toán MoMo'");
                    if ($stmt_update) {
                        $stmt_update->bind_param("sss", $new_payment_status, $momo_trans_id, $order_id);
                        $stmt_update->execute();
                        $affected_rows = $stmt_update->affected_rows;
                        $stmt_update->close();

                        if ($affected_rows > 0 && $user_id_to_clear_cart) {
                            // Xóa giỏ hàng của người dùng
                            $stmt_delete_cart = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
                            if($stmt_delete_cart){
                                $stmt_delete_cart->bind_param("i", $user_id_to_clear_cart);
                                $stmt_delete_cart->execute();
                                $stmt_delete_cart->close();
                                // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " Cart cleared for user_id: " . $user_id_to_clear_cart . " order_id: " . $order_id . "\n", FILE_APPEND);
                            }
                        }
                        // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " Order $order_id updated to $new_payment_status. MoMo Trans ID: $momo_trans_id. Affected rows: $affected_rows\n", FILE_APPEND);
                    } else {
                         // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " Error preparing update statement for order $order_id: " . $conn->error . "\n", FILE_APPEND);
                    }
                } else { // Thanh toán thất bại hoặc bị hủy
                    $new_payment_status = 'Thanh toán MoMo thất bại (Code: ' . $result_code . ')';
                    // Cập nhật trạng thái đơn hàng
                    $stmt_update = $conn->prepare("UPDATE orders SET payment_status = ?, momo_trans_id = ? WHERE order_id = ? AND payment_status = 'Chờ thanh toán MoMo'");
                     if ($stmt_update) {
                        $stmt_update->bind_param("sss", $new_payment_status, $momo_trans_id, $order_id);
                        $stmt_update->execute();
                        // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " Order $order_id updated to $new_payment_status. MoMo Code: $result_code, Trans ID: $momo_trans_id\n", FILE_APPEND);
                        $stmt_update->close();
                    }
                }
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                // Ghi log lỗi nghiêm trọng
                // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " IPN DB Exception for order $order_id: " . $e->getMessage() . "\n", FILE_APPEND);
            }

            // Phản hồi cho MoMo (theo tài liệu của MoMo)
            // MoMo thường yêu cầu phản hồi HTTP 204 (No Content) hoặc JSON với các tham số nhất định để xác nhận đã nhận IPN
            // Ví dụ: header("HTTP/1.1 204 No Content");
            // Hoặc trả về JSON theo yêu cầu của MoMo (tham khảo tài liệu của họ)
            // Ví dụ cấu trúc phản hồi (CẦN KIỂM TRA LẠI VỚI TÀI LIỆU MOMO):
            /*
            $response_to_momo = [
                "partnerCode" => $momo_response['partnerCode'],
                "requestId" => $momo_response['requestId'],
                "orderId" => $momo_response['orderId'],
                "errorCode" => 0, // 0 nếu xử lý thành công phía bạn
                "message" => "Success",
                "responseTime" => round(microtime(true) * 1000),
                "signature" => hash_hmac("sha256", "partnerCode=" . $momo_response['partnerCode'] . "&requestId=" . $momo_response['requestId'] . "&orderId=" . $momo_response['orderId'] . "&errorCode=0&message=Success&responseTime=" . $response_to_momo['responseTime'], $momo_secret_key)
            ];
            header('Content-Type: application/json');
            echo json_encode($response_to_momo);
            */
            
            // Quan trọng: Đảm bảo bạn gửi phản hồi đúng cách để MoMo không gửi lại IPN nhiều lần.
            // Hiện tại chỉ gửi 204 No content để đơn giản.
            http_response_code(204);


        } else {
            // Dữ liệu IPN không đủ thông tin cần thiết
            // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " IPN Missing orderId or resultCode. Data: " . $json_data . "\n", FILE_APPEND);
            http_response_code(400); // Bad Request
        }
    } else {
        // Chữ ký không hợp lệ
        // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " IPN Invalid Signature. Data: " . $json_data . "\n", FILE_APPEND);
        http_response_code(401); // Unauthorized
    }
} else {
    // Không nhận được dữ liệu hoặc không có chữ ký
    // file_put_contents('momo_ipn_log.txt', date('[Y-m-d H:i:s]') . " IPN No data or no signature. Raw input: " . $json_data . "\n", FILE_APPEND);
    http_response_code(400); // Bad Request
}

exit;
?>