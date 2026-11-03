<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: SELLER PRODUCT CONTROLLER (MANAJEMEN PRODUK PEMILIK TOKO)
 * ============================================================================
 * Fungsi:
 *   Mengelola seluruh aktivitas Pemilik Toko (Siswa, Guru, maupun Jurusan):
 *   melihat statistik toko, menambah karya produk baru dengan pilihan kategori
 *   dinamis dari database, mengedit/menghapus produk, dan mengelola pesanan.
 * ============================================================================
 */
class SellerProductController extends Controller
{
    public function index(Request $request): View
    {
        $store = $request->user()->store()->withCount(['products', 'orders'])->firstOrFail();
        $products = $store->products()->latest()->paginate(10);
        $orders = $store->orders()->with(['buyer', 'items.product'])->latest()->take(10)->get();

        return view('seller.dashboard', compact('store', 'products', 'orders'));
    }

    public function create(Request $request): View
    {
        $store = $request->user()->store;
        $categories = Category::orderBy('name')->pluck('name');

        return view('seller.product-form', [
            'store'      => $store,
            'product'    => null,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:150'],
            'category'     => ['required', 'string', 'max:100'],
            'price'        => ['required', 'numeric', 'min:1000'],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['required', 'string'],
            'image'        => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $imagePath = $request->file('image')->store('products', 'public');

        $store->products()->create([
            'product_name' => $validated['product_name'],
            'slug'         => Str::slug($validated['product_name']) . '-' . Str::lower(Str::random(5)),
            'category'     => $validated['category'],
            'price'        => $validated['price'],
            'stock'        => $validated['stock'],
            'description'  => $validated['description'],
            'image'        => $imagePath,
            'is_active'    => true,
        ]);

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Karya produk baru berhasil ditambahkan ke katalog toko Anda!');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorizeProductOwner($request, $product);
        $categories = Category::orderBy('name')->pluck('name');

        return view('seller.product-form', [
            'store'      => $request->user()->store,
            'product'    => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProductOwner($request, $product);

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:150'],
            'category'     => ['required', 'string', 'max:100'],
            'price'        => ['required', 'numeric', 'min:1000'],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['required', 'string'],
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProductOwner($request, $product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Produk berhasil dihapus dari toko Anda.');
    }

    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->store_id === $request->user()->store?->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    private function authorizeProductOwner(Request $request, Product $product): void
    {
        abort_unless(
            $request->user()->store && $product->store_id === $request->user()->store->id,
            403,
            'Anda hanya dapat mengelola produk milik toko Anda sendiri.'
        );
    }
}
