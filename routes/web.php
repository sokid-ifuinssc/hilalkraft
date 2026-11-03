<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerOrderController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\SellerProductController;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * ROUTING WEB: HILALKRAFT MARKETPLACE (SMK PLUS AL HILAL)
 * ============================================================================
 * Fungsi:
 *   Memetakan seluruh endpoint URL HTTP (GET, POST, PUT, PATCH, DELETE) ke
 *   Controller yang bersangkutan dengan pengelompokan hak akses (Middleware).
 *
 * Algoritma (Hierarchical Route Grouping & Middleware Pipeline):
 *   1. Group 1 (Public/Guest): Katalog publik, pencarian, detail produk, profil toko.
 *   2. Group 2 (Guest Auth): Login & Registrasi (Pembeli maupun Pemilik Toko: Siswa, Guru, Jurusan).
 *   3. Group 3 (Buyer): Keranjang & Checkout Pembeli (`role:buyer`).
 *   4. Group 4 (Seller): Manajemen Produk & Pesanan Pemilik Toko (`role:seller`).
 *   5. Group 5 (Admin & Super Admin): Manajemen User, Kredensial Realtime,
 *      Validasi Toko, CRUD Kategori Produk, serta CRUD Kelas & Jurusan (`role:super_admin,admin`).
 * ============================================================================
 */

/*
|--------------------------------------------------------------------------
| 1. PUBLIC / GUEST ROUTES (Pengunjung & Semua User)
|--------------------------------------------------------------------------
*/
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/produk/{product:slug}', [CatalogController::class, 'show'])->name('products.show');
Route::get('/toko/{store:slug}', [CatalogController::class, 'storeProfile'])->name('stores.show');

/*
|--------------------------------------------------------------------------
| 2. AUTHENTICATION ROUTES (Multi-Role Login & Registrasi Seller/Buyer)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| 3. BUYER ROUTES (Pembeli: Keranjang, Checkout & Riwayat Transaksi)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:buyer'])->prefix('pembeli')->name('buyer.')->group(function () {
    Route::get('/dashboard', [BuyerOrderController::class, 'index'])->name('dashboard');
    Route::post('/keranjang/{product}', [BuyerOrderController::class, 'addToCart'])->name('cart.add');
    Route::get('/keranjang', [BuyerOrderController::class, 'viewCart'])->name('cart.index');
    Route::delete('/keranjang/{productId}', [BuyerOrderController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/checkout', [BuyerOrderController::class, 'checkout'])->name('checkout');
});

/*
|--------------------------------------------------------------------------
| 4. SELLER ROUTES (Pemilik Toko: Siswa, Guru & Jurusan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:seller'])->prefix('toko-saya')->name('seller.')->group(function () {
    Route::get('/dashboard', [SellerProductController::class, 'index'])->name('dashboard');
    Route::get('/produk/tambah', [SellerProductController::class, 'create'])->name('products.create');
    Route::post('/produk', [SellerProductController::class, 'store'])->name('products.store');
    Route::get('/produk/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
    Route::put('/produk/{product}', [SellerProductController::class, 'update'])->name('products.update');
    Route::delete('/produk/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('/pesanan/{order}/status', [SellerProductController::class, 'updateOrderStatus'])->name('orders.status');
});

/*
|--------------------------------------------------------------------------
| 5. ADMIN & SUPER ADMIN ROUTES (User, Toko, Kategori Produk, Kelas & Jurusan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin,admin'])->prefix('panel-admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Validasi Toko & Pantau Produk
    Route::patch('/stores/{store}/verify', [AdminDashboardController::class, 'toggleVerifyStore'])->name('stores.verify');
    Route::delete('/products/{product}', [AdminDashboardController::class, 'destroyProduct'])->name('products.destroy');

    // CRUD User (Termasuk Pembuatan Akun Toko Siswa, Guru & Jurusan)
    Route::post('/users', [AdminDashboardController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminDashboardController::class, 'destroyUser'])->name('users.destroy');

    // CRUD Kategori Produk (Dapat dikelola oleh Super Admin & Admin)
    Route::post('/categories', [AdminDashboardController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminDashboardController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminDashboardController::class, 'destroyCategory'])->name('categories.destroy');

    // CRUD Kelas & Jurusan (Dapat dikelola oleh Super Admin & Admin)
    Route::post('/class-majors', [AdminDashboardController::class, 'storeClassMajor'])->name('class-majors.store');
    Route::put('/class-majors/{classMajor}', [AdminDashboardController::class, 'updateClassMajor'])->name('class-majors.update');
    Route::delete('/class-majors/{classMajor}', [AdminDashboardController::class, 'destroyClassMajor'])->name('class-majors.destroy');
});
