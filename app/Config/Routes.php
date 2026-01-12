<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API Routes
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    
    // Handle OPTIONS request (CORS Preflight) untuk semua endpoint
    $routes->options('(:any)', 'Restaurants::options'); // Default handler

    // 1. Restaurants
    $routes->resource('restaurants', ['controller' => 'Restaurants']);
    
    // 2. Menus (Custom Route)
    $routes->get('restaurants/(:num)/menus', 'Menus::getByRestaurant/$1');
    $routes->options('restaurants/(:num)/menus', 'Menus::options');

    // 3. Customers
    $routes->post('customers', 'Customers::create');
    $routes->options('customers', 'Customers::options');

    // 4. Orders
    $routes->post('orders', 'Orders::create');
    $routes->options('orders', 'Orders::options');
    
    // 5. Track Order by Email
    $routes->get('customers/email/(:any)/orders', 'Orders::getByEmail/$1');
    $routes->options('customers/email/(:any)/orders', 'Orders::options');
});
