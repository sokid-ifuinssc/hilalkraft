<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductLike;
use App\Models\Store;
use App\Models\VisitorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: CATALOG CONTROLLER (KATALOG, DETEKSI VIEWS MATA, LIKE & KOMENTAR)
 * ============================================================================
 * Fungsi:
 *   Menangani katalog produk publik, pencatatan jumlah dilihat (ikon mata 👁️)
 *   baik dari pengunjung tamu (Guest) maupun pengguna berakun, pemberian
 *   komentar produk oleh pengguna berakun, serta fitur suka (like ❤️).
 * ============================================================================
 */
class CatalogController extends Controller
{
    /**
     * Fungsi: Menampilkan beranda katalog dengan statistik views, komentar, dan like.
     * Algoritma: Eager Loading `with(['store.owner'])` + Subquery `withCount('comments')`.
     */
    public function index(Request $request): View
    {
        \App\Models\CmsContent::clearCache();

        $categories = Category::orderBy('name')->pluck('name')->toArray();

        $sort = $request->string('sort', 'rekomendasi')->toString();

        $productsQuery = Product::availableForPublic()
            ->with(['store.owner'])
            ->withCount('comments')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q');
                $query->where(function ($sub) use ($search) {
                    $sub->where('product_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('store', fn ($s) => $s->where('store_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->string('category'));
            });

        match ($sort) {
            'populer' => $productsQuery->orderByDesc('views_count'),
            'disukai' => $productsQuery->orderByDesc('likes_count'),
            'terbaru' => $productsQuery->latest(),
            default   => $productsQuery->orderByDesc('views_count')->latest(),
        };

        $products = $productsQuery->paginate(12)->withQueryString();

        $featuredProduct = Product::availableForPublic()
            ->with(['store.owner'])
            ->orderByDesc('views_count')
            ->first();

        $partnerAds = \Illuminate\Support\Facades\Schema::hasTable('cms_contents')
            ? \App\Models\CmsContent::activePartnerAds()
            : collect();

        $customCmsBanners = \Illuminate\Support\Facades\Schema::hasTable('cms_contents')
            ? \App\Models\CmsContent::customItems()
            : collect();

        $activeBazaarEvents = \Illuminate\Support\Facades\Schema::hasTable('bazaar_events')
            ? \App\Models\BazaarEvent::activeEvents()
            : collect();

        return view('catalog.index', compact('products', 'categories', 'featuredProduct', 'partnerAds', 'customCmsBanners', 'activeBazaarEvents'));
    }

    /**
     * Fungsi:
     *   Menampilkan halaman detail produk, mendeteksi & menambah jumlah dilihat
     *   (ikon mata 👁️) untuk Guest maupun User berakun, serta memuat komentar.
     *
     * Algoritma (Real-Time View Tracking & Visitor Profiling):
     *   1. Increment `views_count` secara atomik pada tabel `products` dan `stores`.
     *   2. Identifikasi pengunjung: jika `auth()->check()`, ambil nama, `user_subtype`
     *      (`pelajar`, `keluarga`, `alumni`, `umum`, `siswa`, `guru`), serta asal
     *      wilayah (`desa, kecamatan, kabupaten`). Jika belum login, catat sebagai `guest`.
     *   3. Simpan ke tabel `visitor_logs` untuk bahan Laporan Pengunjung di Dashboard Admin.
     */
    public function show(Request $request, Product $product): View
    {
        $user = $request->user();
        $store = $product->store;
        $isOwnerOrAdmin = $user && ($user->id === ($store?->user_id ?? 0) || in_array($user->role, ['admin', 'super_admin']));

        // Jika produk tidak aktif atau tokonya nonaktif/terhapus, cegah pengunjung umum
        if (! $product->is_active || ! $store || ! $store->is_verified || $store->status === 'inactive' || $store->trashed()) {
            if (! $isOwnerOrAdmin) {
                abort(404, 'Karya produk atau toko ini sedang dinonaktifkan oleh pengelola.');
            }
        }

        $product->increment('views_count');
        if ($store) {
            $store->increment('views_count');
        }

        VisitorLog::create([
            'user_id'       => $user?->id,
            'store_id'      => $product->store_id,
            'product_id'    => $product->id,
            'visitor_type'  => $user ? ($user->user_subtype ?? $user->role) : 'guest',
            'visitor_name'  => $user ? $user->name : 'Pengunjung Tamu (Tanpa Akun)',
            'origin_region' => $user ? $user->full_address : 'Akses Publik (Guest)',
            'ip_address'    => $request->ip(),
        ]);

        $product->load(['store.owner', 'comments.user']);

        $relatedProducts = Product::availableForPublic()
            ->with('store')
            ->withCount('comments')
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        $activeBazaarEvents = \Illuminate\Support\Facades\Schema::hasTable('bazaar_events')
            ? \App\Models\BazaarEvent::activeEvents()
            : collect();

        return view('catalog.show', compact('product', 'relatedProducts', 'activeBazaarEvents'));
    }

    /**
     * Fungsi: Menyimpan komentar dan rating produk dari pengunjung yang sudah memiliki akun.
     * Algoritma: Validasi input komentar -> Insert ke `product_comments` dengan `user_id` aktif.
     */
    public function storeComment(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
            'rating'  => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        ProductComment::create([
            'product_id' => $product->id,
            'user_id'    => $request->user()->id,
            'comment'    => $validated['comment'],
            'rating'     => $validated['rating'] ?? 5,
        ]);

        return back()->with('success', 'Komentar Anda berhasil ditambahkan pada produk ini.');
    }

    /**
     * Fungsi: Menambah suka (like ❤️) pada produk oleh pengunjung maupun pengguna berakun.
     * Algoritma (Deduplicated Like Toggle / Counter):
     *   Mengecek apakah `user_id` (jika login) atau `ip_address` (jika guest) sudah
     *   menyukai produk ini; jika belum, simpan ke `product_likes` dan increment `likes_count`.
     */
    public function toggleLike(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();
        $ip = $request->ip();

        $existing = ProductLike::where('product_id', $product->id)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when(! $user, fn ($q) => $q->whereNull('user_id')->where('ip_address', $ip))
            ->first();

        if ($existing) {
            $existing->delete();
            if ($product->likes_count > 0) {
                $product->decrement('likes_count');
            }
            return back()->with('success', 'Tanda suka pada produk dibatalkan.');
        }

        ProductLike::create([
            'product_id' => $product->id,
            'user_id'    => $user?->id,
            'ip_address' => $ip,
        ]);
        $product->increment('likes_count');

        return back()->with('success', 'Terima kasih! Anda menyukai karya produk ini.');
    }

    public function storeProfile(Request $request, Store $store): View
    {
        $user = $request->user();
        $isOwnerOrAdmin = $user && ($user->id === $store->user_id || in_array($user->role, ['admin', 'super_admin']));

        // Jika toko nonaktif atau terhapus, tolak akses publik
        if (! $store->is_verified || $store->status === 'inactive' || $store->trashed()) {
            if (! $isOwnerOrAdmin) {
                abort(404, 'Toko ini sedang dinonaktifkan atau dalam pemeliharaan.');
            }
        }

        $store->increment('views_count');

        VisitorLog::create([
            'user_id'       => $user?->id,
            'store_id'      => $store->id,
            'product_id'    => null,
            'visitor_type'  => $user ? ($user->user_subtype ?? $user->role) : 'guest',
            'visitor_name'  => $user ? $user->name : 'Pengunjung Tamu (Tanpa Akun)',
            'origin_region' => $user ? $user->full_address : 'Akses Publik (Guest)',
            'ip_address'    => $request->ip(),
        ]);

        $store->load(['owner', 'products' => fn ($q) => $q->withCount('comments')->where('is_active', true)->latest()]);

        return view('catalog.store', compact('store'));
    }
}
