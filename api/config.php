<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================
// Database Configuration
// ============================================

define('DB_HOST', 'xxxx');        // Ganti dengan hostname InfinityFree
define('DB_USER', 'xxxx');              // Ganti dengan username database
define('DB_PASS', 'xxxx');                // Ganti dengan password database
define('DB_NAME', 'xxxx');     // Ganti dengan nama database

// ============================================
// Application Configuration
// ============================================

define('APP_NAME', 'Restaurant Ordering System');
define('APP_URL', 'https://webservicemnz.great-site.net/');
define('API_URL', 'https://webservicemnz.great-site.net/api/');
define('JWT_SECRET', 'generate-random-secret-key-min-32-chars-12345678901234');
define('JWT_EXPIRE', 86400 * 7); // 7 days

// ============================================
// Timezone
// ============================================

date_default_timezone_set('Asia/Jakarta');

// ============================================
// Error Handling
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// CORS Headers
// ============================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// Global Database Connection
// ============================================

$GLOBALS['db'] = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($GLOBALS['db']->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $GLOBALS['db']->connect_error
    ]));
}

$GLOBALS['db']->set_charset('utf8mb4');

// ============================================
// Helper Functions untuk JSON Response
// ============================================

function success($message = 'Success', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function error($message = 'Error', $errors = null, $code = 400) {
    http_response_code($code);
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($errors) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response);
    exit;
}

function validate($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $conditions) {
        $value = $data[$field] ?? '';
        
        if (strpos($conditions, 'required') !== false && empty($value)) {
            $errors[$field] = [$field . ' is required'];
        }
        
        if (strpos($conditions, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = ['Invalid email format'];
        }
        
        if (strpos($conditions, 'numeric') !== false && !is_numeric($value)) {
            $errors[$field] = [$field . ' must be numeric'];
        }
    }
    
    return $errors;
}

?>
