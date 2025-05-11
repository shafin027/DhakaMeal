document.addEventListener('DOMContentLoaded', () => {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

    addToCartButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            if (button.disabled) return;

            const foodId = button.getAttribute('data-food-id');
            button.style.position = 'relative';
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            spinner.style.display = 'block';
            button.appendChild(spinner);
            button.disabled = true;

            try {
                const response = await fetch('add-to-cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `food_id=${foodId}&quantity=1`
                });

                const result = await response.json();
                if (result.success) {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = parseInt(cartCount.textContent || 0) + 1;
                    }
                    alert('Item added to cart successfully!');
                } else {
                    alert('Failed to add item: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                alert('An error occurred while adding to cart.');
            } finally {
                button.removeChild(spinner);
                button.disabled = false;
            }
        });
    });
});