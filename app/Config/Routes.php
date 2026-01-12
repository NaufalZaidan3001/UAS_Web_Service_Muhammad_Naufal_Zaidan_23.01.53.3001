<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/*
 * --------------------------------------------------------------------
 * API Routes
 * --------------------------------------------------------------------
 */
// Perhatikan namespace: App\Controllers\Api
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    
    // 1. Handle Preflight Request (OPTIONS) untuk CORS
    // Menangkap semua request OPTIONS di bawah api/v1/...
    $routes->options('(:any)', 'Restaurants::options');

    // 2. Resource Routes untuk Restaurants
    // Ini otomatis membuat route GET, POST, PUT, DELETE
    $routes->resource('restaurants');
    
    // Jika Anda punya controller Orders atau lainnya, tambahkan di sini:
    // $routes->resource('orders');
});