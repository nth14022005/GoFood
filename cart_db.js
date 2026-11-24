const cartModal = document.getElementById('cartModal');
const cartList = document.getElementById('cartList');
const totalPriceEl = document.getElementById('totalPrice');
const cartCountEl = document.getElementById('cartCount');

// Hàm để định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Hàm cập nhật số lượng trên icon giỏ hàng
function updateCartIconCount(count) {
    if (cartCountEl) {
        cartCountEl.textContent = count;
    }
}

// Hàm hiển thị giỏ hàng
async function renderCart() {
    if (!cartModal || !cartList || !totalPriceEl) return;

    try {
        const response = await fetch('cart_handler.php?action=get');
        const data = await response.json();

        if (data.success && data.items) {
            cartList.innerHTML = ''; // Xóa các item cũ
            if (data.items.length === 0) {
                cartList.innerHTML = '<li>Giỏ hàng của bạn đang trống.</li>';
            }
            data.items.forEach(item => {
                const listItem = document.createElement('li');
                listItem.style.display = 'flex';
                listItem.style.justifyContent = 'space-between';
                listItem.style.alignItems = 'center';
                listItem.style.marginBottom = '10px';
                listItem.style.borderBottom = '1px solid #eee';
                listItem.style.paddingBottom = '10px';

                const img = document.createElement('img');
                img.src = item.image_url;
                img.alt = item.name;
                img.style.width = '50px';
                img.style.height = '50px';
                img.style.objectFit = 'cover';
                img.style.marginRight = '10px';

                const nameDiv = document.createElement('div');
                nameDiv.textContent = item.name;
                nameDiv.style.flexGrow = '1';

                const quantityInput = document.createElement('input');
                quantityInput.type = 'number';
                quantityInput.value = item.quantity;
                quantityInput.min = '0'; // Cho phép nhập 0 để xóa
                quantityInput.style.width = '50px';
                quantityInput.style.margin = '0 10px';
                quantityInput.addEventListener('change', (e) => {
                    updateCartItemQuantity(item.product_id, parseInt(e.target.value));
                });

                const itemPrice = document.createElement('span');
                itemPrice.textContent = formatCurrency(item.price * item.quantity);

                const removeBtn = document.createElement('button');
                removeBtn.textContent = 'Xóa';
                removeBtn.style.backgroundColor = '#ff6b6b';
                removeBtn.style.color = 'white';
                removeBtn.style.border = 'none';
                removeBtn.style.padding = '5px 8px';
                removeBtn.style.borderRadius = '4px';
                removeBtn.style.cursor = 'pointer';
                removeBtn.onclick = () => updateCartItemQuantity(item.product_id, 0); // Gửi số lượng 0 để xóa

                listItem.appendChild(img);
                listItem.appendChild(nameDiv);
                listItem.appendChild(quantityInput);
                listItem.appendChild(itemPrice);
                listItem.appendChild(removeBtn);
                cartList.appendChild(listItem);
            });
            totalPriceEl.textContent = formatCurrency(data.totalPrice);
            updateCartIconCount(data.cart_item_count);
        } else {
            cartList.innerHTML = `<li>Lỗi khi tải giỏ hàng: ${data.message || 'Unknown error'}</li>`;
            totalPriceEl.textContent = formatCurrency(0);
            updateCartIconCount(data.cart_item_count || 0);
        }
    } catch (error) {
        console.error('Error fetching cart:', error);
        cartList.innerHTML = '<li>Có lỗi xảy ra khi tải giỏ hàng.</li>';
        totalPriceEl.textContent = formatCurrency(0);
        updateCartIconCount(0);
    }
}

// Hàm bật/tắt hiển thị giỏ hàng
function toggleCart() {
    if (cartModal) {
        const isOpen = cartModal.style.display === 'block';
        cartModal.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            renderCart(); // Tải lại giỏ hàng mỗi khi mở
        }
    }
}

// Hàm thêm sản phẩm vào giỏ
async function addToCart(productId, productName, productPrice, productImage) { // Sửa tên tham số để nhất quán
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    // formData.append('quantity', 1); // Mặc định là 1, được xử lý ở backend nếu không có

    try {
        const response = await fetch('cart_handler.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message || 'Đã thêm vào giỏ hàng!');
            updateCartIconCount(data.cart_item_count);
            if (cartModal.style.display === 'block') {
                renderCart(); // Cập nhật lại view giỏ hàng nếu đang mở
            }
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể thêm vào giỏ hàng.'));
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        alert('Có lỗi xảy ra khi thêm sản phẩm.');
    }
}

// Hàm cập nhật số lượng sản phẩm
async function updateCartItemQuantity(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update_quantity');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    try {
        const response = await fetch('cart_handler.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            // alert(data.message || 'Cập nhật giỏ hàng thành công!'); // Có thể bỏ qua alert này cho trải nghiệm mượt hơn
            updateCartIconCount(data.cart_item_count);
            renderCart(); // Tải lại toàn bộ giỏ hàng để cập nhật UI
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể cập nhật giỏ hàng.'));
            renderCart(); // Tải lại để khôi phục trạng thái cũ nếu có lỗi
        }
    } catch (error) {
        console.error('Error updating cart quantity:', error);
        alert('Có lỗi xảy ra khi cập nhật số lượng.');
        renderCart();
    }
}

// Hàm xóa toàn bộ giỏ hàng
async function clearCart() {
    if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?')) return;

    try {
        const response = await fetch('cart_handler.php?action=clear');
        const data = await response.json();

        if (data.success) {
            alert(data.message || 'Đã xóa giỏ hàng!');
            updateCartIconCount(data.cart_item_count);
            renderCart(); // Tải lại giỏ hàng (sẽ trống)
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể xóa giỏ hàng.'));
        }
    } catch (error) {
        console.error('Error clearing cart:', error);
        alert('Có lỗi xảy ra khi xóa giỏ hàng.');
    }
}

// Hàm xử lý thanh toán (chuyển đến trang checkout.html hoặc gọi checkout.php)
function checkoutCart() {
    // Kiểm tra xem giỏ hàng có trống không trước khi chuyển trang
    fetch('cart_handler.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items && data.items.length > 0) {
                window.location.href = 'checkout_page.php'; // Hoặc checkout.html nếu bạn có trang riêng cho form thông tin
            } else if (data.success && data.items.length === 0) {
                alert('Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm trước khi thanh toán.');
            } else {
                alert('Không thể lấy thông tin giỏ hàng để thanh toán. Vui lòng thử lại.');
            }
        })
        .catch(error => {
            console.error('Error before checkout:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
}

// Gọi renderCart khi trang được tải lần đầu nếu giỏ hàng đang được hiển thị (ít khả năng xảy ra với setup hiện tại)
// Hoặc tải số lượng item ban đầu cho icon
(async () => {
    try {
        const response = await fetch('cart_handler.php?action=get');
        const data = await response.json();
        if (data.success) {
            updateCartIconCount(data.cart_item_count);
        }
    } catch(e) {
        updateCartIconCount(0);
    }
})(); 