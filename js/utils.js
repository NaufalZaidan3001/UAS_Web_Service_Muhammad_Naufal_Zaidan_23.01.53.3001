// Local Storage untuk Cart
const getCart = () => JSON.parse(localStorage.getItem('cart')) || {};
const setCart = (cart) => localStorage.setItem('cart', JSON.stringify(cart));

function addToCart(restaurantId, menuItemId, name, price, quantity = 1) {
    const cart = getCart();
    
    if (!cart[restaurantId]) {
        cart[restaurantId] = {
            items: [],
            restaurantId: restaurantId
        };
    }
    
    const existing = cart[restaurantId].items.find(item => item.id === menuItemId);
    
    if (existing) {
        existing.quantity += quantity;
    } else {
        cart[restaurantId].items.push({
            id: menuItemId,
            name: name,
            price: price,
            quantity: quantity
        });
    }
    
    setCart(cart);
    updateCartCount();
    showMessage('Ditambahkan ke keranjang!', 'success');
}

function removeFromCart(restaurantId, menuItemId) {
    const cart = getCart();
    
    if (cart[restaurantId]) {
        cart[restaurantId].items = cart[restaurantId].items.filter(
            item => item.id !== menuItemId
        );
        
        if (cart[restaurantId].items.length === 0) {
            delete cart[restaurantId];
        }
    }
    
    setCart(cart);
    updateCartCount();
}

function clearCart() {
    localStorage.removeItem('cart');
    updateCartCount();
}

function getTotalCartItems() {
    const cart = getCart();
    let total = 0;
    
    for (let restaurantId in cart) {
        total += cart[restaurantId].items.reduce((sum, item) => sum + item.quantity, 0);
    }
    
    return total;
}

function updateCartCount() {
    const count = getTotalCartItems();
    const elements = document.querySelectorAll('#cartCount, #cartCount2');
    elements.forEach(el => el.textContent = count);
}

// Formatting Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Messages
function showMessage(message, type = 'info') {
    const div = document.createElement('div');
    div.className = `${type}`;
    div.innerHTML = message;
    document.body.insertBefore(div, document.body.firstChild);
    
    setTimeout(() => div.remove(), 3000);
}

function showError(error) {
    const message = error.message || 'Terjadi kesalahan';
    showMessage(message, 'error');
}

// Page Navigation
function showPage(pageId) {
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    
    const page = document.getElementById(pageId);
    if (page) {
        page.classList.add('active');
    }
}

function goBack() {
    // Simple back navigation
    const mainPage = document.getElementById('mainPage');
    if (mainPage) {
        showPage('mainPage');
    }
}
