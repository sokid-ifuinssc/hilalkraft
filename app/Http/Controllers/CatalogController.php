<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: CATALOG CONTROLLER (KATALOG PUBLIK & PENCARIAN)
 * ============================================================================
 * Fungsi:
 *   Menangani seluruh halaman publik yang dapat diakses oleh Pengunjung (Guest)
 *   maupun pengguna login: halaman beranda galeri produk, pencarian, filter
 *   kategori dinamis dari tabel `categories`, detail produk, dan profil toko.
 * ============================================================================
 */
class CatalogController extends Controller
{
    /**
     * Fungsi:
     *   Menampilkan halaman utama marketplace (Landing Page) beserta daftar
     *   kategori dinamis dan produk aktif dari toko (Siswa/Guru/Jurusan) terverifikasi.
     *
     * Algoritma (Dynamic Category Lookup + Eager Loading & Pagination):
     *   1. Mengambil daftar nama kategori secara dinamis dari tabel `categories`
     *      diurutkan berdasarkan abjad (`orderBy('name')->pluck('name')`).
     *   2. Menggunakan Eager Loading `with(['store.owner'])` untuk mengatasi N+1 Query.
     *   3. Memfilter produk aktif dari toko terverifikasi, menerapkan pencarian `q`
     *      dan filter `category`, lalu membagi menjadi 12 item per halaman.
     */
    public function index(Request $request): View
    {
        $categories = Category::orderBy('name')->pluck('name')->toArray();

        $products = Product::with(['store.owner'])
            ->where('is_active', true)
            ->whereHas('store', fn ($q) => $q->where('is_verified', true))
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
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', compact('products', 'categories'));
    }

    public function show(Product $product): View
    {
        $product->load(['store.owner']);

        $relatedProducts = Product::with('store')
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('catalog.show', compact('product', 'relatedProducts'));
    }

    public function storeProfile(Store $store): View
    {
        $store->load(['owner', 'products' => fn ($q) => $q->where('is_active', true)->latest()]);

        return view('catalog.store', compact('store'));
    }
}
