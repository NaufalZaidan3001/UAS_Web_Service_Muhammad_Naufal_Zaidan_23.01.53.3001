<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Grup route untuk API v1
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], function($routes) {
    // Tambahkan header CORS untuk semua request di dalam grup ini
    $routes->options('(:any)', 'RestaurantsController::options'); // Handle preflight request
    
    // Definisikan resource routes
    $routes->resource('restaurants', ['controller' => 'RestaurantsController']);
    $routes->resource('orders', ['controller' => 'OrdersController']); // Jika ada
});
