<?php

require_once 'config.php';
require_once 'functions.php';
require_once 'middleware.php';

$route = getRoute();
$method = getRequestMethod();
$data = getRequestData();

// ============================================
// AUTHENTICATION
// ============================================

// LOGIN
if (preg_match('#^auth/login$#', $route) && $method === 'POST') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    $user = getOne('SELECT * FROM users WHERE email = ?', [$email]);
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        error('Invalid credentials', null, 401);
    }
    
    $token = generateToken($user['id'], $user['email']);
    
    success('Login successful', [
        'user_id' => $user['id'], 'name' => $user['name'], 'role' => $user['role'], 'token' => $token
    ]);
}

// REGISTER (FIXED: Return Token)
// REGISTER (UPDATED WITH ADDRESS)
if (preg_match('#^auth/register$#', $route) && $method === 'POST') {
    $name = $data['name'] ?? ''; 
    $email = $data['email'] ?? ''; 
    $password = $data['password'] ?? ''; 
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? ''; 
    
    if (empty($name) || empty($email) || empty($password)) error('Wajib diisi');
    
    $existing = getOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing) error('Email already registered');
    
    // UPDATE QUERY: Tambahkan kolom 'address'
    execute('INSERT INTO users (name, email, password, phone, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$name, $email, hashPassword($password), $phone, $address, 'customer']);
        
    $user = getOne('SELECT * FROM users WHERE email = ?', [$email]);
    $token = generateToken($user['id'], $user['email']);
    
    success('Registration successful', [
        'user_id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'address' => $user['address'], // Balikkan alamat juga
        'token' => $token
    ], 201);
}

// USER PROFILE
if (preg_match('#^users/profile$#', $route) && $method === 'GET') {
    $user = requireAuth();
    success('User profile', $user);
}

// ============================================
// ADMIN ROUTES
// ============================================

// GET ALL USERS
if (preg_match('#^admin/users$#', $route) && $method === 'GET') {
    requireRole('admin');
    $users = getAll('SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC');
    success('All users retrieved', $users);
}

// DELETE USER
if (preg_match('#^admin/users/(\d+)$#', $route, $matches) && $method === 'DELETE') {
    requireRole('admin');
    $id = $matches[1];
    execute('DELETE FROM users WHERE id = ?', [$id]);
    success('User deleted successfully');
}

// PROMOTE TO OWNER
if (preg_match('#^admin/users/(\d+)/promote$#', $route, $matches) && $method === 'PUT') {
    requireRole('admin');
    $id = $matches[1];
    execute("UPDATE users SET role = 'owner' WHERE id = ?", [$id]);
    success('User promoted to Owner');
}

// ============================================
// RESTAURANT ROUTES
// ============================================

// GET ALL RESTAURANTS (Public + Owner Priority)
if (preg_match('#^restaurants$#', $route) && $method === 'GET') {
    $search = $_GET['search'] ?? '';
    $sql = 'SELECT * FROM restaurants WHERE status = "active"';
    $params = [];
    
    if (!empty($search)) {
        $sql .= ' AND (name LIKE ? OR address LIKE ?)';
        $search = "%$search%";
        $params[] = $search;
        $params[] = $search;
    }
    
    // Auth Check for Sorting
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $currentUserId = 0;

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        try {
            $token = $matches[1];
            $decoded = verifyToken($token); 
            $currentUserId = $decoded['user_id'] ?? $decoded['sub'] ?? 0;
        } catch (Exception $e) {}
    }

    if ($currentUserId > 0) {
        $sql .= ' ORDER BY (owner_id = ?) DESC, created_at DESC';
        $params[] = $currentUserId;
    } else {
        $sql .= ' ORDER BY created_at DESC';
    }
    
    $restaurants = getAll($sql, $params);
    success('Restaurants retrieved', $restaurants);
}

// OWNER: GET MY RESTAURANTS (Dropdown List) -- NEW ROUTE
if (preg_match('#^owner/restaurants$#', $route) && $method === 'GET') {
    $user = requireRole('owner'); 
    $sql = 'SELECT id, name FROM restaurants WHERE owner_id = ? AND status = "active"';
    $myRestaurants = getAll($sql, [$user['id']]);
    success('Owner restaurants retrieved', $myRestaurants);
}

// GET RESTAURANT DETAIL
if (preg_match('#^restaurants/(\d+)$#', $route, $matches) && $method === 'GET') {
    $id = $matches[1];
    $restaurant = getOne('SELECT * FROM restaurants WHERE id = ?', [$id]);
    if (!$restaurant) error('Restaurant not found', null, 404);
    
    $restaurant['menu_items'] = getAll('SELECT * FROM menu_items WHERE restaurant_id = ?', [$id]);
    success('Restaurant retrieved', $restaurant);
}

// OWNER: CREATE RESTAURANT
if (preg_match('#^restaurants$#', $route) && $method === 'POST') {
    $user = requireRole('owner');
    $name = $data['name']; $address = $data['address'];
    $hours = $data['operating_hours'];
    
    execute('INSERT INTO restaurants (name, address, owner_id, operating_hours, status) VALUES (?, ?, ?, ?, "active")',
        [$name, $address, $user['id'], $hours]);
    success('Restaurant created', null, 201);
}

// OWNER: UPDATE RESTAURANT
if (preg_match('#^restaurants/(\d+)$#', $route, $matches) && $method === 'PUT') {
    $id = $matches[1];
    $user = requireAuth();
    
    $checkSql = 'SELECT * FROM restaurants WHERE id = ?';
    $checkParams = [$id];
    if ($user['role'] !== 'admin') {
        $checkSql .= ' AND owner_id = ?';
        $checkParams[] = $user['id'];
    }
    
    $restaurant = getOne($checkSql, $checkParams);
    if (!$restaurant) error('Unauthorized', null, 403);
    
    $name = $data['name'] ?? $restaurant['name'];
    $address = $data['address'] ?? $restaurant['address'];
    $operating_hours = $data['operating_hours'] ?? $restaurant['operating_hours'];
    
    execute('UPDATE restaurants SET name=?, address=?, operating_hours=?, updated_at=NOW() WHERE id=?', 
        [$name, $address, $operating_hours, $id]);
    
    success('Restaurant updated');
}

// OWNER: ADD MENU
if (preg_match('#^restaurants/(\d+)/menu$#', $route, $matches) && $method === 'POST') {
    $restaurant_id = $matches[1];
    $user = requireAuth();
    
    $checkSql = 'SELECT * FROM restaurants WHERE id = ?';
    $checkParams = [$restaurant_id];
    if ($user['role'] !== 'admin') {
        $checkSql .= ' AND owner_id = ?';
        $checkParams[] = $user['id'];
    }
    
    if (!getOne($checkSql, $checkParams)) error('Unauthorized', null, 403);
    
    $name = $data['name'];
    $desc = $data['description'] ?? ''; 
    $price = $data['price'];
    
    execute('INSERT INTO menu_items (restaurant_id, name, description, price, category) VALUES (?, ?, ?, ?, "makanan")',
        [$restaurant_id, $name, $desc, $price]);
        
    success('Menu item added', null, 201);
}

// OWNER: GET ORDERS (Strict)
if (preg_match('#^restaurants/(\d+)/orders$#', $route, $matches) && $method === 'GET') {
    $restaurant_id = $matches[1];
    $user = requireAuth();
    
    $checkSql = 'SELECT * FROM restaurants WHERE id = ?';
    $checkParams = [$restaurant_id];
    if ($user['role'] !== 'admin') {
        $checkSql .= ' AND owner_id = ?';
        $checkParams[] = $user['id'];
    }
    
    if (!getOne($checkSql, $checkParams)) error('Unauthorized', null, 403);
    
    $orders = getAll('SELECT * FROM orders WHERE restaurant_id = ? ORDER BY order_date DESC', [$restaurant_id]);
    success('Orders retrieved', $orders);
}

// ============================================
// ORDER ROUTES
// ============================================

// CREATE ORDER
if (preg_match('#^orders$#', $route) && $method === 'POST') {
    $user = requireAuth();
    $restaurant_id = $data['restaurant_id'];
    $items = $data['items'];
    $address = $data['delivery_address'];
    $notes = $data['notes'] ?? '';
    $delivery_fee = $data['delivery_fee'] ?? 0;
    
    execute('INSERT INTO orders (restaurant_id, customer_id, status, total_price, delivery_address, delivery_fee, notes, order_date) VALUES (?, ?, "pending", 0, ?, ?, ?, NOW())',
        [$restaurant_id, $user['id'], $address, $delivery_fee, $notes]);
        
    $order = getOne('SELECT id FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 1', [$user['id']]);
    
    $total = $delivery_fee;
    foreach ($items as $item) {
        $menu = getOne('SELECT price FROM menu_items WHERE id = ?', [$item['menu_item_id']]);
        $subtotal = $menu['price'] * $item['quantity'];
        $total += $subtotal;
        execute('INSERT INTO order_details (order_id, menu_item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)',
            [$order['id'], $item['menu_item_id'], $item['quantity'], $menu['price'], $subtotal]);
    }
    
    execute('UPDATE orders SET total_price = ? WHERE id = ?', [$total, $order['id']]);
    success('Order created');
}

// GET MY ORDERS
if (preg_match('#^orders$#', $route) && $method === 'GET') {
    $user = requireAuth();
    $orders = getAll('SELECT o.*, r.name as restaurant_name FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.customer_id = ? ORDER BY order_date DESC', [$user['id']]);
    success('Orders retrieved', $orders);
}

// GET ORDER DETAIL
if (preg_match('#^orders/(\d+)$#', $route, $matches) && $method === 'GET') {
    $user = requireAuth();
    $order_id = $matches[1];
    
    $order = getOne('SELECT * FROM orders WHERE id = ?', [$order_id]);
    if (!$order) error('Order not found', null, 404);
    
    $order['items'] = getAll('SELECT od.*, m.name FROM order_details od JOIN menu_items m ON od.menu_item_id = m.id WHERE od.order_id = ?', [$order_id]);
    success('Order retrieved', $order);
}

// UPDATE ORDER STATUS
if (preg_match('#^orders/(\d+)/status$#', $route, $matches) && $method === 'PUT') {
    $id = $matches[1];
    requireAuth(); 
    execute('UPDATE orders SET status = ? WHERE id = ?', [$data['status'], $id]);
    success('Status updated');
}

// ============================================
// REVIEW ROUTES
// ============================================

// GET REVIEWS
if (preg_match('#^restaurants/(\d+)/reviews$#', $route, $matches) && $method === 'GET') {
    $id = $matches[1];
    $reviews = getAll('SELECT r.*, u.name as customer_name FROM reviews r JOIN users u ON r.customer_id = u.id WHERE r.restaurant_id = ? ORDER BY r.created_at DESC', [$id]);
    success('Reviews retrieved', $reviews);
}

// CREATE REVIEW
if (preg_match('#^orders/(\d+)/review$#', $route, $matches) && $method === 'POST') {
    $user = requireAuth();
    $order_id = $matches[1];
    $order = getOne('SELECT * FROM orders WHERE id = ?', [$order_id]);
    
    execute('INSERT INTO reviews (order_id, restaurant_id, customer_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
        [$order_id, $order['restaurant_id'], $user['id'], $data['rating'], $data['comment']]);
        
    // Auto-update Restaurant Rating
    $avg = getOne('SELECT AVG(rating) as val FROM reviews WHERE restaurant_id = ?', [$order['restaurant_id']]);
    $count = getOne('SELECT COUNT(*) as cnt FROM reviews WHERE restaurant_id = ?', [$order['restaurant_id']]);
    execute('UPDATE restaurants SET rating = ?, total_reviews = ? WHERE id = ?', [$avg['val'], $count['cnt'], $order['restaurant_id']]);
        
    success('Review added');
}

error('Route not found', null, 404);
?>
