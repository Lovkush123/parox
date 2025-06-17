<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CouponController;
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::get('/categories', [CategoryController::class, 'index']);

// Get a single category by ID
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Create a new category
Route::post('/categories', [CategoryController::class, 'store']);

// Update an existing category
Route::post('/categories/{id}', [CategoryController::class, 'update']);

// Delete a category
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

// Get all products
Route::get('/products', [ProductController::class, 'index']);

// Create a new product
Route::post('/products', [ProductController::class, 'store']);

// Get a single product by ID
Route::get('/products/{id}', [ProductController::class, 'show']);

// Update a product
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::patch('/products/{id}', [ProductController::class, 'update']); // Optional

// Delete a product
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

Route::get('/sizes', [SizeController::class, 'index']);          // Get all sizes
Route::post('/sizes', [SizeController::class, 'store']);         // Create new size
Route::get('/sizes/{id}', [SizeController::class, 'show']);      // Get a specific size
Route::put('/sizes/{id}', [SizeController::class, 'update']);    // Update a specific size
Route::delete('/sizes/{id}', [SizeController::class, 'destroy']); // Delete a specific size

Route::get('/order', [OrderController::class, 'index']);           // Get all orders
Route::post('/order', [OrderController::class, 'store']);          // Create a new order
Route::get('/order/{id}', [OrderController::class, 'show']);       // Get a specific order
Route::put('/order/{id}', [OrderController::class, 'update']);     // Update an order
Route::delete('/order/{id}', [OrderController::class, 'destroy']); // Delete an order

Route::get('/coupons', [CouponController::class, 'index']);          // List all coupons
Route::post('/coupons', [CouponController::class, 'store']);         // Create a new coupon
Route::get('/coupons/{id}', [CouponController::class, 'show']);      // Show a specific coupon
Route::put('/coupons/{id}', [CouponController::class, 'update']);    // Update a specific coupon
Route::delete('/coupons/{id}', [CouponController::class, 'destroy']); // Delete a specific coupon