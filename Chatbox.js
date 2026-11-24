
chatBtn.addEventListener('click', () => {
  chatBox.classList.toggle('hidden');
});

closeBtn.addEventListener('click', () => {
  chatBox.classList.add('hidden');
});

// Xử lý lọc theo danh mục món ăn
const categorySpans = document.querySelectorAll('.food-categories span');
const foodItems = document.querySelectorAll('.items .item');

categorySpans.forEach(span => {
  span.addEventListener('click', () => {
    const selectedCategory = span.getAttribute('data-category');

    foodItems.forEach(item => {
      const itemCategory = item.getAttribute('data-category');

      if (itemCategory === selectedCategory || selectedCategory === "Tất cả") {
        item.style.visibility = 'visible';
        item.style.position = 'static';
      } else {
        item.style.visibility = 'hidden';
        item.style.position = 'absolute';
      }
    });
  });
});