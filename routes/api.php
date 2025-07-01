<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AddressController,
    UserController,
    CategoryController,
    ProductController,
    SizeController,
    OrderController,
    CouponController,
    ProductMediaController,
    OrderProductController,
    OrderListController,
    ImageController,
    OrdersController
};

// ✅ Auth Routes
Route::post('/login-or-register', [UserController::class, 'loginOrRegisterWithOTP']);
Route::post('/verify-otp', [UserController::class, 'verifyOtp']);
Route::post('/admin-register', [UserController::class, 'createAdmin']);
Route::post('/admin-login', [UserController::class, 'adminlogin']);

// ✅ User Routes
Route::apiResource('users', UserController::class)->except(['update']);
Route::post('/users/{id}', [UserController::class, 'update']); // Custom update using POST

// ✅ Address Routes
Route::apiResource('addresses', AddressController::class);


// ✅ Category Routes
Route::apiResource('categories', CategoryController::class);

// ✅ Product Routes
Route::apiResource('products', ProductController::class);
// Route::match(['post', 'patch'], '/products/{id}', [ProductController::class, 'update']); // Accept both POST & PATCH for update

// ✅ Size Routes
Route::apiResource('sizes', SizeController::class);

// ✅ Order Routes
Route::apiResource('order', OrderController::class);

// ✅ Coupon Routes
Route::apiResource('coupons', CouponController::class);

// ✅ Product Media Routes
Route::post('/product-media', [ProductMediaController::class, 'store']);
Route::get('/product-media', [ProductMediaController::class, 'index']);
Route::delete('/product-media/{id}', [ProductMediaController::class, 'destroy']);

// ✅ Order Product Routes
Route::apiResource('order-products', OrderProductController::class);

// ✅ Order List Routes
Route::apiResource('order-lists', OrderListController::class);

// ✅ Image Routes
Route::apiResource('images', ImageController::class)->except(['index']); // Add index if needed


// ✅ Orders
Route::get('/orders', [OrdersController::class, 'index']);         // List orders (filters: user_id, status, etc.)
Route::post('/orders', [OrdersController::class, 'placeOrder']);   // Create a new order
Route::get('/orders/{id}', [OrdersController::class, 'show']);     // Show single order
Route::put('/orders/{id}', [OrdersController::class, 'update']);   // Update order statuses

// 💳 PhonePe Payment
Route::post('/phonepe/response', [OrdersController::class, 'phonepeResponse'])->name('phonepe.response');
