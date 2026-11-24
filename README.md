# GoFood – Website đặt món ăn

Dự án cá nhân website đặt món sử dụng PHP, MySQL, JavaScript, HTML/CSS. **Mã nguồn có sử dụng AI hỗ trợ tạo và chỉnh sửa**; tác giả tự thiết lập môi trường và chạy thử các chức năng trong quá trình học tập.

## Các tính năng có trong mã nguồn
- Đăng ký/đăng nhập, hồ sơ người dùng, giỏ hàng, đặt hàng và lịch sử đơn.
- Trang chính và giao diện hiển thị món ăn.
- Có thành phần tích hợp/giả lập luồng thanh toán MoMo trong `checkout.php`, `momo_return.php`, `momo_ipn.php`.

## Chạy thử (tham khảo)
1. Cài PHP/MySQL (chẳng hạn XAMPP) và đưa các file vào thư mục ứng dụng `gofood` dưới web root.
2. Tạo database `gofood_db` và tự cung cấp bảng dữ liệu tương ứng. **Bộ mã nguồn bản gốc không kèm script SQL hoàn chỉnh**, nên không thể bảo đảm website chạy đầy đủ chỉ bằng clone repository.
3. Trong môi trường chạy PHP, cung cấp `GOFOOD_DB_HOST`, `GOFOOD_DB_USER`, `GOFOOD_DB_PASSWORD`, `GOFOOD_DB_NAME`, hoặc dùng cấu hình mặc định cho môi trường phát triển an toàn.
4. Truy cập `index.php` qua localhost. Không chạy trên môi trường production khi chưa rà soát bảo mật, phiên đăng nhập, quyền truy cập và truy vấn SQL.

## Lưu ý thanh toán
- Những biến MoMo trong mã nguồn đã được chuyển sang `getenv(...)` thay vì chứa credential ngay trong code; ví dụ biến môi trường có trong `.env.example` (**file này chỉ là mẫu, PHP không tự động nạp `.env`**).
- Không có thông tin xác thực thanh toán thực. Các luồng MoMo là mã nguồn tham khảo/thử nghiệm, **chưa xác minh giao dịch thật**; tuyệt đối không dùng tiền thật trước khi tự kiểm thử và đối chiếu tài liệu MoMo.
