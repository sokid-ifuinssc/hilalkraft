<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: BUYER ORDER CONTROLLER (KERANJANG & TRANSAKSI PEMBELI)
 * ============================================================================
 * Fungsi:
 *   Mengelola alur belanja Pembeli (`buyer`): menampilkan riwayat pesanan,
 *   menyimpan item ke keranjang berbasis session, serta memproses checkout
 *   dengan pembagian pesanan otomatis per toko (Multi-Store Order Splitting).
 * ============================================================================
 */
class BuyerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['store', 'items.product'])
            ->latest()
            ->paginate(10);

        return view('buyer.dashboard', compact('orders'));
    }

    /**
     * Fungsi: Menambahkan produk yang dipilih pembeli ke dalam keranjang session.
     *
     * Algoritma (Hash Map Session Cart Accumulation):
     *   1. Memastikan kuantitas minimal 1 (`max(1, ...)`), lalu mengecek ketersediaan stok.
     *   2. Mengambil struktur Hash Map/Associative Array `hilalkraft_cart` dari session
     *      dengan key berupa `product->id` (akses O(1)).
     *   3. Jika produk sudah ada di keranjang, akumulasikan `quantity` lama + baru;
     *      jika belum ada, inisialisasi entri baru.
     */
    public function addToCart(Request $request, Product $product): RedirectResponse
    {
        $quantity = max(1, (int) $request->input('quantity', 1));

        if ($product->stock < $quantity) {
            return back()->with('error', 'Stok produk tidak mencukupi.');
        }

        $cart = session()->get('hilalkraft_cart', []);

        $cart[$product->id] = [
            'product_id'   => $product->id,
            'store_id'     => $product->store_id,
            'store_name'   => $product->store->store_name,
            'product_name' => $product->product_name,
            'price'        => (float) $product->price,
            'image'        => $product->image,
            'quantity'     => ($cart[$product->id]['quantity'] ?? 0) + $quantity,
        ];

        session()->put('hilalkraft_cart', $cart);

        return redirect()->route('buyer.cart.index')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang belanja.');
    }

    public function viewCart(): View
    {
        $cart = session()->get('hilalkraft_cart', []);
        return view('buyer.cart', compact('cart'));
    }

    public function removeFromCart(int $productId): RedirectResponse
    {
        $cart = session()->get('hilalkraft_cart', []);
        unset($cart[$productId]);
        session()->put('hilalkraft_cart', $cart);

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    /**
     * Fungsi:
     *   Mengeksekusi checkout keranjang menjadi transaksi resmi dan mengosongkan keranjang.
     *
     * Algoritma (Multi-Store Order Splitting + Pessimistic Row Locking):
     *   1. Mengelompokkan isi keranjang berdasarkan `store_id` menggunakan algoritma
     *      partisi koleksi `collect($cart)->groupBy('store_id')`.
     *   2. Membungkus proses di dalam `DB::transaction()` (ACID):
     *      a. Untuk setiap grup toko (`$storeId => $items`), hitung total harga
     *         dan buat 1 header `Order` dengan nomor invoice unik (`HK-YYYYMMDD-XXXXX`).
     *      b. Untuk setiap item di toko tersebut, kunci baris produk di database
     *         menggunakan `lockForUpdate()` (Pessimistic Concurrency Control: `SELECT ... FOR UPDATE`)
     *         untuk mencegah Race Condition saat 2 pembeli membeli stok terakhir bersamaan.
     *      c. Buat record `OrderItem` dan kurangi stok produk (`decrement('stock', $qty)`).
     *   3. Hapus session keranjang (`session()->forget('hilalkraft_cart')`).
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $cart = session()->get('hilalkraft_cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        // Langkah 1: Partisi item keranjang berdasarkan toko penjual
        $groupedByStore = collect($cart)->groupBy('store_id');

        // Langkah 2: Transaksi atomik pembuatan Order per Toko + pengurangan stok
        DB::transaction(function () use ($groupedByStore, $request, $validated) {
            foreach ($groupedByStore as $storeId => $items) {
                $totalPrice = $items->sum(fn ($item) => $item['price'] * $item['quantity']);

                $order = Order::create([
                    'invoice_number'   => 'HK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5)),
                    'buyer_id'         => $request->user()->id,
                    'store_id'         => $storeId,
                    'total_price'      => $totalPrice,
                    'status'           => 'pending',
                    'shipping_address' => $validated['shipping_address'],
                    'notes'            => $validated['notes'] ?? null,
                ]);

                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $qty = min($item['quantity'], $product->stock);

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'unit_price' => $product->price,
                        'subtotal'   => $product->price * $qty,
                    ]);

                    $product->decrement('stock', $qty);
                }
            }
        });

        session()->forget('hilalkraft_cart');

        return redirect()->route('buyer.dashboard')
            ->with('success', 'Pesanan berhasil dibuat! Silakan lanjutkan konfirmasi via WhatsApp ke pemilik toko.');
    }
}
