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
    ImageController
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
