<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\Sale\SalesController;
use App\Http\Controllers\Admin\Product\BrandController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Product\CategorieController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

/*
|--------------------------------------------------------------------------
| API Routes for AvisOnline - Restructured
|--------------------------------------------------------------------------
|
| Rutas específicamente diseñadas para AvisOnline con permisos reorganizados
|
*/

// Rutas de autenticación (sin middleware)
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
    Route::post('/verified_email', [AuthController::class, 'verified_email'])->name('verified_email');
    Route::post('/verified_code', [AuthController::class, 'verified_code'])->name('verified_code');
    Route::post('/new_password', [AuthController::class, 'new_password'])->name('new_password');
});

// Rutas administrativas con autenticación requerida
Route::group([
    "middleware" => "auth:api",
    "prefix" => "admin",
], function ($router) {
    
    // ================================
    // GESTIÓN DE USUARIOS, ROLES Y PERMISOS
    // ================================
    Route::middleware(['permission:full-admin|manage-users'])->group(function () {
        // Usuarios
        Route::post("users-list", [UserController::class, "index"]);
        Route::apiResource("users", UserController::class);
        Route::post("users/{user_id}/roles/{role_id}", [UserController::class, "assignRole"]);
        Route::delete("users/{user_id}/roles/{role_id}", [UserController::class, "removeRole"]);
        
        // Roles
        Route::post("roles-list", [RoleController::class, "index"]);
        Route::post("roles/{id}/users", [RoleController::class, "getUsers"]);
        Route::delete("roles/{role_id}/users/{user_id}", [RoleController::class, "deleteUser"]);
        Route::apiResource("roles", RoleController::class);
        
        // Permisos
        Route::post("permissions-list", [PermissionController::class, "index"]);
        Route::apiResource("permissions", PermissionController::class);
    });
    
    // ================================
    // GESTIÓN DE ANUNCIOS
    // ================================
    Route::middleware(['permission:full-admin|manage-all-announcements|manage-own-announcements'])->group(function () {
        Route::get("announcements/config", [ProductController::class, "config"]);
        Route::post("announcements/index", [ProductController::class, "index"]);
        Route::post("announcements", [ProductController::class, "store"]);
        Route::get("announcements/{id}", [ProductController::class, "show"]);
        Route::post("announcements/{id}", [ProductController::class, "update"]);
        Route::delete("announcements/{id}", [ProductController::class, "destroy"]);
        
        // Gestión de imágenes de anuncios
        Route::post("announcements/images", [ProductController::class, "imagens"]);
        Route::delete("announcements/images/{id}", [ProductController::class, "delete_imagen"]);
    });
    
    // ================================
    // GESTIÓN DE CATEGORÍAS
    // ================================
    Route::middleware(['permission:full-admin|manage-categories'])->group(function () {
        Route::get("categories/config", [CategorieController::class, "config"]);
        Route::get("categories", [CategorieController::class, "index"]);
        Route::post("categories", [CategorieController::class, "store"]);
        Route::get("categories/{id}", [CategorieController::class, "show"]);
        Route::post("categories/{id}", [CategorieController::class, "update"]);
        Route::delete("categories/{id}", [CategorieController::class, "destroy"]);
    });
    
    // ================================
    // GESTIÓN DE SLIDERS
    // ================================
    Route::middleware(['permission:full-admin|manage-sliders'])->group(function () {
        Route::resource("sliders", SliderController::class);
        Route::post("sliders/{id}", [SliderController::class, "update"]);
    });
    
    // ================================
    // RUTAS SOLO PARA SUPER ADMIN
    // ================================
    Route::middleware(['permission:full-admin'])->group(function () {
        // Reportes y estadísticas avanzadas
        Route::post("reports/announcements", [ProductController::class, "getAnnouncementsReport"]);
        Route::post("reports/users", [UserController::class, "getUsersReport"]);
        Route::post("reports/categories", [CategorieController::class, "getCategoriesReport"]);
        
        // Configuraciones del sistema
        Route::get("system/config", [SystemController::class, "getConfig"]);
        Route::post("system/config", [SystemController::class, "updateConfig"]);
        
        // Logs y auditoría
        Route::get("system/logs", [SystemController::class, "getLogs"]);
        Route::get("system/activity", [SystemController::class, "getActivity"]);
    });
});

// ================================
// RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
// ================================
Route::group([
    "prefix" => "public",
], function ($router) {
    // Listado público de anuncios (solo activos)
    Route::get("announcements", [ProductController::class, "getPublicAnnouncements"]);
    Route::get("announcements/{slug}", [ProductController::class, "getPublicAnnouncement"]);
    
    // Categorías públicas
    Route::get("categories", [CategorieController::class, "getPublicCategories"]);
    
    // Sliders públicos
    Route::get("sliders", [SliderController::class, "getPublicSliders"]);
    
    // Filtros de búsqueda
    Route::post("announcements/search", [ProductController::class, "searchAnnouncements"]);
    Route::get("announcements/filter/options", [ProductController::class, "getFilterOptions"]);
});

// ================================
// RUTAS DE USUARIO AUTENTICADO (FRONTEND)
// ================================
Route::group([
    "middleware" => 'auth:api',
    "prefix" => "user",
], function($router) {
    // Perfil del usuario
    Route::get("profile", [AuthController::class, "me"]);
    Route::post("profile", [AuthController::class, "update"]);
    
    // Anuncios del usuario
    Route::get("announcements", [ProductController::class, "getUserAnnouncements"]);
    Route::post("announcements", [ProductController::class, "createUserAnnouncement"]);
    Route::get("announcements/{id}", [ProductController::class, "getUserAnnouncement"]);
    Route::post("announcements/{id}", [ProductController::class, "updateUserAnnouncement"]);
    Route::delete("announcements/{id}", [ProductController::class, "deleteUserAnnouncement"]);
    
    // Estadísticas del usuario
    Route::get("stats", [ProductController::class, "getUserStats"]);
});

// ================================
// RUTAS DE COMPATIBILIDAD (TEMPORALES)
// ================================
// Estas rutas mantienen compatibilidad con el frontend existente
// TODO: Migrar gradualmente al nuevo esquema

Route::group([
    "middleware" => "auth:api",
    "prefix" => "admin",
], function ($router) {
    // Alias para productos -> anuncios
    Route::post("products/index", [ProductController::class, "index"]);
    Route::post("products", [ProductController::class, "store"]);
    Route::get("products/{id}", [ProductController::class, "show"]);
    Route::post("products/{id}", [ProductController::class, "update"]);
    Route::delete("products/{id}", [ProductController::class, "destroy"]);
    Route::get("products/config", [ProductController::class, "config"]);
    Route::post("products/imagens", [ProductController::class, "imagens"]);
    Route::delete("products/imagens/{id}", [ProductController::class, "delete_imagen"]);
}); 