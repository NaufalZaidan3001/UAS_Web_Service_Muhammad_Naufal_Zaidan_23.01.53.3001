const API_URL = 'https://webservicemnz.great-site.net/api/';
let authToken = localStorage.getItem('authToken');

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(API_URL + endpoint, options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'API Error');
        }

        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ============================================
// Auth APIs
// ============================================

async function login(email, password) {
    const result = await apiCall('auth/login', 'POST', { email, password });
    authToken = result.data.token;
    localStorage.setItem('authToken', authToken);
    localStorage.setItem('currentUser', JSON.stringify(result.data));
    return result.data;
}

async function register(name, email, password, phone, address, role = 'customer') {
    const result = await apiCall('auth/register', 'POST', { name, email, password, phone, address, role });
    authToken = result.data.token;
    localStorage.setItem('authToken', authToken);
    return result.data;
}

// ============================================
// User APIs
// ============================================

async function getUserProfile() {
    const result = await apiCall('users/profile', 'GET');
    return result.data;
}

async function updateUserProfile(name, phone, address) {
    const result = await apiCall('users/profile', 'PUT', { name, phone, address });
    return result.data;
}

// ============================================
// Restaurant APIs
// ============================================

async function getRestaurants(page = 1, limit = 10, search = '', status = 'active') {
    const query = new URLSearchParams({
        page, limit, search, status
    });
    const result = await apiCall(`restaurants?${query}`, 'GET');
    return result;
}

async function getRestaurantDetail(id) {
    const result = await apiCall(`restaurants/${id}`, 'GET');
    return result.data;
}

async function createRestaurant(name, description, address, phone, operating_hours) {
    const result = await apiCall('restaurants', 'POST', {
        name, description, address, phone, operating_hours
    });
    return result.data;
}

async function updateRestaurant(id, name, description, address, phone, operating_hours) {
    const result = await apiCall(`restaurants/${id}`, 'PUT', {
        name, description, address, phone, operating_hours
    });
    return result.data;
}

// ============================================
// Menu APIs
// ============================================

async function getRestaurantMenu(restaurantId, category = '') {
    const query = new URLSearchParams({ category });
    const result = await apiCall(`restaurants/${restaurantId}/menu?${query}`, 'GET');
    return result.data;
}

async function getMenuItemDetail(id) {
    const result = await apiCall(`menu-items/${id}`, 'GET');
    return result.data;
}

async function createMenuItem(restaurantId, name, description, price, category, stock) {
    const result = await apiCall(`restaurants/${restaurantId}/menu`, 'POST', {
        name, description, price, category, stock
    });
    return result.data;
}

async function updateMenuItem(id, name, description, price, category, stock) {
    const result = await apiCall(`menu-items/${id}`, 'PUT', {
        name, description, price, category, stock
    });
    return result.data;
}

// ============================================
// Order APIs
// ============================================

async function createOrder(restaurantId, deliveryAddress, items, deliveryFee = 0, notes = '') {
    const result = await apiCall('orders', 'POST', {
        restaurant_id: restaurantId,
        delivery_address: deliveryAddress,
        delivery_fee: deliveryFee,
        notes: notes,
        items: items
    });
    return result.data;
}

async function getMyOrders(page = 1, limit = 10, status = '') {
    const query = new URLSearchParams({ page, limit, status });
    const result = await apiCall(`orders?${query}`, 'GET');
    return result;
}

async function getOrderDetail(id) {
    const result = await apiCall(`orders/${id}`, 'GET');
    return result.data;
}

async function getRestaurantOrders(restaurantId, page = 1, limit = 10, status = '') {
    const query = new URLSearchParams({ page, limit, status });
    const result = await apiCall(`restaurants/${restaurantId}/orders?${query}`, 'GET');
    return result;
}

async function updateOrderStatus(orderId, status) {
    const result = await apiCall(`orders/${orderId}/status`, 'PUT', { status });
    return result.data;
}

async function cancelOrder(orderId) {
    const result = await apiCall(`orders/${orderId}/cancel`, 'PUT');
    return result.data;
}

// ============================================
// Review APIs
// ============================================

async function createReview(orderId, rating, comment) {
    const result = await apiCall(`orders/${orderId}/review`, 'POST', {
        rating, comment
    });
    return result.data;
}

async function getRestaurantReviews(restaurantId, page = 1, limit = 10) {
    const query = new URLSearchParams({ page, limit });
    const result = await apiCall(`restaurants/${restaurantId}/reviews?${query}`, 'GET');
    return result;
}
