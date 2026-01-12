<?php

function requireAuth() {
    $user = getCurrentUser();
    
    if (!$user) {
        error('Unauthorized access', null, 401);
    }
    
    return $user;
}

function requireRole($role) {
    $user = requireAuth();
    
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        error('Access denied. Required role: ' . $role, null, 403);
    }
    
    return $user;
}

function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

function getRequestData() {
    $method = getRequestMethod();
    
    if ($method === 'GET') {
        return $_GET;
    }
    
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    return [];
}

function getRoute() {
    $route = $_GET['route'] ?? '';
    return trim($route, '/');
}

function matchRoute($route, $pattern) {
    $pattern = preg_replace('/\{[a-zA-Z]+\}/', '(?P<\\w+>[^/]+)', $pattern);
    $pattern = '#^' . $pattern . '$#';
    
    if (preg_match($pattern, $route, $matches)) {
        return $matches;
    }
    
    return false;
}

?>
