<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\OrderProductController;
use App\Http\Controllers\OrderListController;
use App\Http\Controllers\ImageController;
// Route::get('/users', [UserController::class, 'index']);
Route::post('/login-or-register', [UserController::class, 'loginOrRegisterWithOTP']);
Route::post('/verify-otp', [UserController::class, 'verifyOtp']);

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users/{id}', [UserController::class, 'update']);
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
Route::post('/products/{id}', [ProductController::class, 'update']);
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

Route::post('/product-media', [ProductMediaController::class, 'store']);
Route::get('/product-media', [ProductMediaController::class, 'index']);
Route::delete('/product-media/{id}', [ProductMediaController::class, 'destroy']);

// List all order products
Route::get('/order-products', [OrderProductController::class, 'index']);

// Show a single order product by ID
Route::get('/order-products/{id}', [OrderProductController::class, 'show']);

// Create a new order product
Route::post('/order-products', [OrderProductController::class, 'store']);

// Update an existing order product
Route::put('/order-products/{id}', [OrderProductController::class, 'update']);

// Delete an order product
Route::delete('/order-products/{id}', [OrderProductController::class, 'destroy']);
Route::get('/order-lists', [OrderListController::class, 'index']);
Route::get('/order-lists/{id}', [OrderListController::class, 'show']);
Route::post('/order-lists', [OrderListController::class, 'store']);
Route::put('/order-lists/{id}', [OrderListController::class, 'update']);
Route::delete('/order-lists/{id}', [OrderListController::class, 'destroy']);

// Route::post('/users/send-otp', [UserController::class, 'sendOtp']);
// Route::post('/users/login-otp', [UserController::class, 'loginWithOtp']);
// Route::post('/send-otp', [UserController::class, 'sendOtp']);
// Route::post('/verify-otp', [UserController::class, 'verifyOtp']);
Route::post('/images', [ImageController::class, 'store']);
Route::get('/images/{id}', [ImageController::class, 'show']);
Route::put('/images/{id}', [ImageController::class, 'update']);
Route::delete('/images/{id}', [ImageController::class, 'destroy']);