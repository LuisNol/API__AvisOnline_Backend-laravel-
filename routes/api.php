<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Ecommerce\HomeController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Product\CategorieController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes
Route::group([
    'prefix' => 'auth'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login_ecommerce', [AuthController::class, 'login_ecommerce'])->name('login_ecommerce');
    Route::post('/google_login', [AuthController::class, 'googleLogin'])->name('google_login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/permissions', [AuthController::class, 'permissions'])->name('permissions');
    Route::get('/permissions', [AuthController::class, 'permissions']);
    Route::post('/verified_auth', [AuthController::class, 'verified_auth'])->name('verified_auth');
    Route::post('/login-json', [\App\Http\Controllers\AuthController::class, 'loginJson'])->name('login_json');
    Route::post('/verified_email', [AuthController::class, 'verified_email'])->name('verified_email');
    Route::post('/verified_code', [AuthController::class, 'verified_code'])->name('verified_code');
    Route::post('/new_password', [AuthController::class, 'new_password'])->name('new_password');
});

// Admin Panel Routes (Protected)
Route::group([
    "middleware" => "auth:api",
    "prefix" => "admin",
],function ($router) {
    
    // User Management Routes (Admin only)
    Route::middleware(['permission:manage-users'])->group(function () {
        Route::post("users-list", [App\Http\Controllers\Admin\UserController::class, "index"]);
        Route::apiResource("users", App\Http\Controllers\Admin\UserController::class);
        Route::post("users/{user_id}/roles/{role_id}", [App\Http\Controllers\Admin\UserController::class, "assignRole"]);
        Route::delete("users/{user_id}/roles/{role_id}", [App\Http\Controllers\Admin\UserController::class, "removeRole"]);
        
        Route::post("roles-list", [App\Http\Controllers\Admin\RoleController::class, "index"]);
        Route::post("roles/{id}/users", [App\Http\Controllers\Admin\RoleController::class, "getUsers"]);
        Route::delete("roles/{role_id}/users/{user_id}", [App\Http\Controllers\Admin\RoleController::class, "deleteUser"]);
        Route::apiResource("roles", App\Http\Controllers\Admin\RoleController::class);
        
        Route::post("permissions-list", [App\Http\Controllers\Admin\PermissionController::class, "index"]);
        Route::apiResource("permissions", App\Http\Controllers\Admin\PermissionController::class);
    });
    
    // Announcements Management (Products) - Users can manage their own, admins can manage all
    Route::middleware(['permission:manage-all-announcements|manage-own-announcements'])->group(function () {
        Route::get("products/config", [ProductController::class, "config"]);
        Route::post("products/index", [ProductController::class, "index"]);
        Route::post("products", [ProductController::class, "store"])->middleware('product.limit');
        Route::get("products/{id}", [ProductController::class, "show"]);
        Route::post("products/{id}", [ProductController::class, "update"]);
        Route::delete("products/{id}", [ProductController::class, "destroy"]);
        
        // User statistics
        Route::get("products/user/stats", [ProductController::class, "getUserStats"]);
        
        // Image management for announcements
        Route::post("products/imagens", [ProductController::class, "imagens"]);
        Route::delete("products/imagens/{id}", [ProductController::class, "delete_imagen"]);
        
        // Categories management
        Route::get("categories/config", [CategorieController::class, "config"]);
        Route::get("categories", [CategorieController::class, "index"]);
        Route::post("categories", [CategorieController::class, "store"]);
        Route::get("categories/{id}", [CategorieController::class, "show"]);
        Route::post("categories/{id}", [CategorieController::class, "update"]);
        Route::delete("categories/{id}", [CategorieController::class, "destroy"]);
    });
    
    // Admin-only routes
    Route::middleware(['permission:manage-all-announcements'])->group(function () {
        // Sliders management
        Route::resource("sliders", SliderController::class);
        Route::post("sliders/{id}", [SliderController::class, "update"]);
    });
});

// Public Frontend Routes (AvisOnline Store)
Route::group([
    "prefix" => "ecommerce",
],function ($router) {
    // Home page data
    Route::get("home",[HomeController::class,"home"]);
    Route::get("menus",[HomeController::class,"menus"]);

    // Announcement details
    Route::get("product/{slug}",[HomeController::class,"show_product"]);
    
    // Search and filtering
    Route::get("config-filter-advance",[HomeController::class,"config_filter_advance"]);
    Route::post("filter-advance-product",[HomeController::class,"filter_advance_product"]);
    
    // Promotional announcements
    Route::post("campaing-discount-link",[HomeController::class,"campaing_discount_link"]);

    // User profile routes (authenticated users)
    Route::group([
        "middleware" => 'auth:api',
    ],function($router) {
        Route::get("profile_client/me",[AuthController::class,"me"]);
        Route::post("profile_client",[AuthController::class,"update"]);
    });
});

// Public user registration (for creating accounts to post announcements)
Route::post('/users', [UserController::class, 'store']);