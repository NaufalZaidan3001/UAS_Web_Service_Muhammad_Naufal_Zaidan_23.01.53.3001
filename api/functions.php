<?php

// ============================================
// Database Query Helpers
// ============================================

function query($sql, $params = []) {
    $db = $GLOBALS['db'];
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        return ['error' => $db->error];
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        return ['error' => $stmt->error];
    }
    
    return $stmt;
}

function getOne($sql, $params = []) {
    $stmt = query($sql, $params);
    
    // PERBAIKAN DI SINI: Cek dulu apakah $stmt array (berarti error)
    if (is_array($stmt) && isset($stmt['error'])) {
        return null;
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

function getAll($sql, $params = []) {
    $stmt = query($sql, $params);
    
    // PERBAIKAN DI SINI JUGA
    if (is_array($stmt) && isset($stmt['error'])) {
        return [];
    }
    
    $result = $stmt->get_result();
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

function execute($sql, $params = []) {
    $stmt = query($sql, $params);
    
    // PERBAIKAN DI SINI JUGA
    if (is_array($stmt) && isset($stmt['error'])) {
        return false;
    }
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected > 0;
}

// ============================================
// Authentication Functions
// ============================================

function generateToken($user_id, $email) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'email' => $email,
        'exp' => time() + JWT_EXPIRE
    ]));
    
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature = base64_encode($signature);
    
    return "$header.$payload.$signature";
}

function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    list($header, $payload, $signature) = $parts;
    
    $verify = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $verify = base64_encode($verify);
    
    if ($signature !== $verify) return null;
    
    $decoded = json_decode(base64_decode($payload), true);
    
    if ($decoded['exp'] < time()) return null;
    
    return $decoded;
}

// ============================================
// Polyfill untuk getallheaders() jika tidak ada
// ============================================

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

function getAuthHeader() {
    $headers = getallheaders();
    
    // Cari header Authorization (case-insensitive)
    $authHeader = null;
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    }
    
    if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
        return substr($authHeader, 7);
    }
    
    return null;
}

function getCurrentUser() {
    $token = getAuthHeader();
    if (!$token) return null;
    
    $decoded = verifyToken($token);
    if (!$decoded) return null;
    
    $user = getOne('SELECT * FROM users WHERE id = ?', [$decoded['user_id']]);
    return $user;
}

// ============================================
// Helper Functions
// ============================================

function hashPassword($password) {
    return md5($password);
}

function verifyPassword($password, $hash) {
    return md5($password) === $hash;
}

function calculateOrderTotal($order_id) {
    $result = getOne(
        'SELECT SUM(subtotal) as total FROM order_details WHERE order_id = ?',
        [$order_id]
    );
    return $result['total'] ?? 0;
}

function updateRestaurantRating($restaurant_id) {
    $result = getOne(
        'SELECT AVG(rating) as avg_rating, COUNT(*) as count 
         FROM reviews WHERE restaurant_id = ?',
        [$restaurant_id]
    );
    
    $avgRating = $result['avg_rating'] ?? 0;
    $count = $result['count'] ?? 0;
    
    execute(
        'UPDATE restaurants SET rating = ?, total_reviews = ? WHERE id = ?',
        [$avgRating, $count, $restaurant_id]
    );
}

function getPaginatedData($sql, $page = 1, $limit = 10, $params = []) {
    // Get total count
    $countSql = preg_replace('/^SELECT .*? FROM/i', 'SELECT COUNT(*) as count FROM', $sql);
    $countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);
    
    $countResult = getOne($countSql, $params);
    $total = $countResult['count'] ?? 0;
    $totalPages = ceil($total / $limit);
    
    // Get paginated data
    $offset = ($page - 1) * $limit;
    $paginated_sql = $sql . ' LIMIT ? OFFSET ?';
    $data = getAll($paginated_sql, array_merge($params, [$limit, $offset]));
    
    return [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'per_page' => $limit
        ]
    ];
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags($data));
}
?>
