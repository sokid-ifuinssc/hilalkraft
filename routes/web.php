<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerOrderController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SellerProductController;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * ROUTING WEB: HILALKRAFT MARKETPLACE (SMK PLUS AL HILAL)
 * ============================================================================
 * Fungsi:
 *   Memetakan seluruh endpoint URL HTTP ke Controller yang bersangkutan:
 *   1. Katalog Publik, Deteksi Views (Ikon Mata), Like Produk, & Komentar Berakun.
 *   2. Autentikasi & Registrasi Profil Lengkap (Pemilik Toko Siswa/Guru &
 *      4 Kategori Pengunjung: Pelajar, Keluarga SMK Plus Al Hilal, Alumni, Umum).
 *   3. Transaksi Pembeli (Keranjang & Multi-Store Checkout).
 *   4. Studio Pemilik Toko (Satu Toko Satu Akun, Edit Profil Lengkap & Kelola Produk).
 *   5. Panel Admin & Super Admin (CMS Konten Website, CMS Karya Produk, Grafik
 *      Analitik Toko, Laporan Pengunjung, Penetapan Guru->Admin, CRUD Master).
 * ============================================================================
 */

/*
|--------------------------------------------------------------------------
| 1. PUBLIC / GUEST ROUTES & INTERAKSI PRODUK (VIEWS, LIKE, KOMENTAR)
|--------------------------------------------------------------------------
*/
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/produk/{product:slug}', [CatalogController::class, 'show'])->name('products.show');
Route::post('/produk/{product:slug}/like', [CatalogController::class, 'toggleLike'])->name('products.like');
Route::post('/produk/{product:slug}/komentar', [CatalogController::class, 'storeComment'])
    ->middleware('auth')
    ->name('products.comment');
Route::get('/toko/{store:slug}', [CatalogController::class, 'storeProfile'])->name('stores.show');

/*
|--------------------------------------------------------------------------
| 2. AUTHENTICATION ROUTES (Multi-Role Login & Registrasi Profil Lengkap)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| 3. BUYER ROUTES (Pembeli / Pengunjung Terdaftar: Keranjang, Progres & Rating)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:buyer,seller,admin,super_admin'])->prefix('pembeli')->name('buyer.')->group(function () {
    Route::get('/dashboard', [BuyerOrderController::class, 'index'])->name('dashboard');
    Route::put('/profil', [BuyerOrderController::class, 'updateProfile'])->name('profile.update');
    Route::get('/laporan', [ReportController::class, 'buyer'])->name('reports');
    Route::post('/keranjang/{product}', [BuyerOrderController::class, 'addToCart'])->name('cart.add');
    Route::get('/keranjang', [BuyerOrderController::class, 'viewCart'])->name('cart.index');
    Route::delete('/keranjang/{productId}', [BuyerOrderController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/checkout', [BuyerOrderController::class, 'checkout'])->name('checkout');
    Route::post('/pesan-langsung/{product}', [BuyerOrderController::class, 'directOrder'])->name('direct-order');
    Route::patch('/pesanan/{order}/sampai', [BuyerOrderController::class, 'markDelivered'])->name('orders.delivered');
    Route::patch('/pesanan/{order}/batal', [BuyerOrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/pesanan/{order}/rating', [BuyerOrderController::class, 'rateOrder'])->name('orders.rate');
});

/*
|--------------------------------------------------------------------------
| 4. SELLER ROUTES (Pemilik Toko: Satu Toko Satu Akun, Diskon, Produk & Progres Pesanan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:seller,admin,super_admin'])->prefix('toko-saya')->name('seller.')->group(function () {
    Route::get('/dashboard', [SellerProductController::class, 'index'])->name('dashboard');
    Route::get('/laporan', [ReportController::class, 'seller'])->name('reports');
    Route::put('/profil', [SellerProductController::class, 'updateProfile'])->name('profile.update');
    Route::put('/diskon', [SellerProductController::class, 'updateDiscounts'])->name('discounts.update');
    Route::get('/produk/tambah', [SellerProductController::class, 'create'])->name('products.create');
    Route::post('/produk', [SellerProductController::class, 'store'])->name('products.store');
    Route::get('/produk/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
    Route::put('/produk/{product}', [SellerProductController::class, 'update'])->name('products.update');
    Route::delete('/produk/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('/pesanan/{order}/status', [SellerProductController::class, 'updateOrderStatus'])->name('orders.status');
});

/*
|--------------------------------------------------------------------------
| 5. ADMIN & SUPER ADMIN ROUTES (CMS, Bazar/Event, Analitik, Pengunjung, Guru->Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin,admin'])->prefix('panel-admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/laporan', [ReportController::class, 'admin'])->name('reports');
    Route::get('/log-aktivitas', [AdminDashboardController::class, 'activityLogs'])->name('logs');

    // CMS Konten Website (Tambah, Edit, Hapus Konten & Iklan Mitra)
    Route::post('/cms', [AdminDashboardController::class, 'storeCms'])->name('cms.store');
    Route::put('/cms/{cmsContent}', [AdminDashboardController::class, 'updateCms'])->name('cms.update');
    Route::delete('/cms/{cmsContent}', [AdminDashboardController::class, 'destroyCms'])->name('cms.destroy');

    // Skema Diskon Bazar & Event Resmi SMK
    Route::post('/bazaar-events', [AdminDashboardController::class, 'storeBazaarEvent'])->name('bazaar.store');
    Route::put('/bazaar-events/{bazaarEvent}', [AdminDashboardController::class, 'updateBazaarEvent'])->name('bazaar.update');
    Route::delete('/bazaar-events/{bazaarEvent}', [AdminDashboardController::class, 'destroyBazaarEvent'])->name('bazaar.destroy');

    // CMS Karya Siswa / Produk & Kelola Status/Hapus Toko
    Route::patch('/stores/{store}/verify', [AdminDashboardController::class, 'toggleVerifyStore'])->name('stores.verify');
    Route::delete('/stores/{id}', [AdminDashboardController::class, 'destroyStore'])->name('stores.destroy');
    Route::patch('/stores/{id}/restore', [AdminDashboardController::class, 'restoreStore'])->name('stores.restore');
    Route::delete('/stores/{id}/force', [AdminDashboardController::class, 'forceDeleteStore'])->name('stores.force-delete');

    Route::post('/products', [AdminDashboardController::class, 'storeProduct'])->name('products.store');
    Route::put('/products/{product}', [AdminDashboardController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminDashboardController::class, 'destroyProduct'])->name('products.destroy');

    // CRUD User & Penetapan Guru sebagai Admin oleh Super Admin
    Route::post('/users', [AdminDashboardController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/promote-teacher', [AdminDashboardController::class, 'promoteTeacherToAdmin'])->name('users.promote-teacher');
    Route::delete('/users/{user}', [AdminDashboardController::class, 'destroyUser'])->name('users.destroy');

    // CRUD Kategori Produk
    Route::post('/categories', [AdminDashboardController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminDashboardController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminDashboardController::class, 'destroyCategory'])->name('categories.destroy');

    // CRUD Kelas & Jurusan
    Route::post('/class-majors', [AdminDashboardController::class, 'storeClassMajor'])->name('class-majors.store');
    Route::put('/class-majors/{classMajor}', [AdminDashboardController::class, 'updateClassMajor'])->name('class-majors.update');
    Route::delete('/class-majors/{classMajor}', [AdminDashboardController::class, 'destroyClassMajor'])->name('class-majors.destroy');

    // Audit Interaksi & Reset (Komentar, Suka, Dilihat & Deteksi Akun Terhubung)
    Route::get('/interaksi', [AdminDashboardController::class, 'interactions'])->name('interactions');
    Route::delete('/interaksi/komentar/{comment}', [AdminDashboardController::class, 'destroyComment'])->name('interactions.destroy-comment');
    Route::delete('/interaksi/suka/{like}', [AdminDashboardController::class, 'destroyLike'])->name('interactions.destroy-like');
    Route::delete('/interaksi/kunjungan/{log}', [AdminDashboardController::class, 'destroyVisitorLog'])->name('interactions.destroy-visitor-log');
    Route::post('/interaksi/produk/{product}/reset', [AdminDashboardController::class, 'resetProductInteractions'])->name('interactions.reset-product');
    Route::post('/interaksi/toko/{store}/reset', [AdminDashboardController::class, 'resetStoreInteractions'])->name('interactions.reset-store');
    Route::post('/interaksi/massal/reset', [AdminDashboardController::class, 'bulkResetInteractions'])->name('interactions.reset-bulk');
});
