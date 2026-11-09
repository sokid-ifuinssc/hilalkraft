<?php

namespace App\Http\Controllers;

use App\Models\BazaarEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: BUYER ORDER CONTROLLER (KERANJANG, DISKON BAZAR/PEMBELI,
 *             PROGRES BARANG 4 TAHAP & RATING BINTANG SETELAH TRANSAKSI)
 * ============================================================================
 * Fungsi:
 *   Mengelola seluruh siklus transaksi Pembeli (`buyer`):
 *   1. Keranjang & Checkout Multi-Toko (`addToCart`, `viewCart`, `checkout`).
 *   2. Pesan Langsung dari Halaman Produk (`directOrder`) agar transaksi yang
 *      dilanjutkan ke WhatsApp penjual tetap tercatat progres barangnya
 *      (`Pesan` -> `Kemas` -> `Kirim / Dalam Perjalanan` -> `Sampai`).
 *   3. Kalkulasi Otomatis Skema Diskon Jenis Pembeli (`Pelajar`, `Keluarga SMK`,
 *      `Alumni`, `Umum`) & Diskon Acara Bazar / Event SMK (`BazaarEvent`).
 *   4. Konfirmasi Barang Sampai (`markDelivered`) & Pemberian Rating Bintang
 *      (`rateOrder`, ★ 1 s/d 5) setelah transaksi.
 * ============================================================================
 */
class BuyerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['store.owner', 'items.product', 'bazaarEvent'])
            ->latest()
            ->paginate(10);

        $activeBazaarEvents = BazaarEvent::activeEvents();

        return view('buyer.dashboard', compact('orders', 'activeBazaarEvents'));
    }

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
        $storeIds = collect($cart)->pluck('store_id')->unique()->values()->all();
        $storesById = Store::whereIn('id', $storeIds)->get()->keyBy('id');
        $activeBazaarEvents = BazaarEvent::activeEvents();

        return view('buyer.cart', compact('cart', 'storesById', 'activeBazaarEvents'));
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
     *   Mengeksekusi checkout keranjang menjadi transaksi resmi beserta
     *   perhitungan diskon jenis pembeli & diskon acara Bazar SMK.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address'    => ['required', 'string', 'max:255'],
            'notes'               => ['nullable', 'string', 'max:500'],
            'bazaar_voucher_code' => ['nullable', 'string', 'max:50'],
            'is_bazaar_offline'   => ['nullable', 'boolean'],
        ]);

        $cart = session()->get('hilalkraft_cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        $buyer = $request->user();
        $isBazaarOffline = $request->boolean('is_bazaar_offline');
        $voucherCode = strtoupper(trim((string) ($validated['bazaar_voucher_code'] ?? '')));

        $bazaarEvent = null;
        if ($voucherCode !== '') {
            $bazaarEvent = BazaarEvent::whereRaw('UPPER(voucher_code) = ?', [$voucherCode])->first();
            if (! $bazaarEvent || ! $bazaarEvent->isActiveNow()) {
                return back()->withInput()->with('error', "Kode voucher bazar '{$voucherCode}' tidak ditemukan atau sudah tidak aktif.");
            }
        } elseif ($isBazaarOffline) {
            $bazaarEvent = BazaarEvent::activeEvents()->first();
        }

        $groupedByStore = collect($cart)->groupBy('store_id');

        DB::transaction(function () use ($groupedByStore, $buyer, $validated, $bazaarEvent, $isBazaarOffline) {
            foreach ($groupedByStore as $storeId => $items) {
                $store = Store::findOrFail($storeId);
                $subtotal = (float) $items->sum(fn ($item) => $item['price'] * $item['quantity']);

                $pricing = $store->calculateOrderPricing($subtotal, $buyer, $bazaarEvent, $isBazaarOffline);

                $order = Order::create([
                    'invoice_number'              => 'HK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5)),
                    'buyer_id'                    => $buyer->id,
                    'store_id'                    => $store->id,
                    'subtotal_price'              => $pricing['subtotal_price'],
                    'buyer_type_discount_percent' => $pricing['buyer_type_discount_percent'],
                    'buyer_type_discount_amount'  => $pricing['buyer_type_discount_amount'],
                    'bazaar_event_id'             => $bazaarEvent?->id,
                    'bazaar_voucher_code'         => $bazaarEvent?->voucher_code,
                    'bazaar_discount_percent'     => $pricing['bazaar_discount_percent'],
                    'bazaar_discount_amount'      => $pricing['bazaar_discount_amount'],
                    'is_bazaar_offline'           => $isBazaarOffline,
                    'total_price'                 => $pricing['total_price'],
                    'status'                      => 'pending',
                    'progress_status'             => 'pesan',
                    'progress_note'               => $isBazaarOffline
                        ? 'Pembelian langsung di Stand Bazar / Event SMK Plus Al Hilal.'
                        : 'Pesanan berhasil dibuat. Silakan hubungi WhatsApp penjual untuk konfirmasi.',
                    'shipping_address'            => $validated['shipping_address'],
                    'notes'                       => $validated['notes'] ?? null,
                ]);

                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $qty = max(1, min((int) $item['quantity'], max(1, (int) $product->stock)));

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'unit_price' => $product->price,
                        'subtotal'   => $product->price * $qty,
                    ]);

                    if ($product->stock >= $qty) {
                        $product->decrement('stock', $qty);
                    }
                }
            }
        });

        session()->forget('hilalkraft_cart');

        return redirect()
            ->route('buyer.dashboard')
            ->with('success', 'Pesanan berhasil dibuat! Pantau progres barang (Pesan → Kemas → Kirim → Sampai) di bawah ini dan hubungi penjual via WhatsApp.');
    }

    /**
     * Fungsi:
     *   Membuat pesanan langsung dari halaman detail produk (Pesan Sekarang /
     *   Beli di Bazar SMK) sehingga progres pesanan & rating bintang tetap tercatat.
     */
    public function directOrder(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity'            => ['nullable', 'integer', 'min:1'],
            'is_bazaar_offline'   => ['nullable', 'boolean'],
            'bazaar_voucher_code' => ['nullable', 'string', 'max:50'],
            'shipping_address'    => ['nullable', 'string', 'max:255'],
        ]);

        $qty = max(1, (int) ($validated['quantity'] ?? 1));
        if ($product->stock < $qty) {
            return back()->with('error', 'Stok produk tidak mencukupi.');
        }

        $buyer = $request->user();
        $store = $product->store;
        $isBazaarOffline = $request->boolean('is_bazaar_offline');
        $voucherCode = strtoupper(trim((string) ($validated['bazaar_voucher_code'] ?? '')));

        $bazaarEvent = null;
        if ($voucherCode !== '') {
            $bazaarEvent = BazaarEvent::whereRaw('UPPER(voucher_code) = ?', [$voucherCode])->first();
        } elseif ($isBazaarOffline) {
            $bazaarEvent = BazaarEvent::activeEvents()->first();
        }

        $subtotal = (float) $product->price * $qty;
        $pricing = $store->calculateOrderPricing($subtotal, $buyer, $bazaarEvent, $isBazaarOffline);

        $shippingAddress = ! empty($validated['shipping_address'])
            ? $validated['shipping_address']
            : ($isBazaarOffline
                ? ('Ambil Langsung di Stand Bazar SMK (' . ($bazaarEvent->location ?? 'SMK Plus Al Hilal') . ')')
                : ($buyer->full_address ?: 'Konfirmasi via WhatsApp Penjual'));

        DB::transaction(function () use ($product, $store, $buyer, $qty, $pricing, $bazaarEvent, $isBazaarOffline, $shippingAddress) {
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);

            $order = Order::create([
                'invoice_number'              => 'HK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5)),
                'buyer_id'                    => $buyer->id,
                'store_id'                    => $store->id,
                'subtotal_price'              => $pricing['subtotal_price'],
                'buyer_type_discount_percent' => $pricing['buyer_type_discount_percent'],
                'buyer_type_discount_amount'  => $pricing['buyer_type_discount_amount'],
                'bazaar_event_id'             => $bazaarEvent?->id,
                'bazaar_voucher_code'         => $bazaarEvent?->voucher_code,
                'bazaar_discount_percent'     => $pricing['bazaar_discount_percent'],
                'bazaar_discount_amount'      => $pricing['bazaar_discount_amount'],
                'is_bazaar_offline'           => $isBazaarOffline,
                'total_price'                 => $pricing['total_price'],
                'status'                      => 'pending',
                'progress_status'             => 'pesan',
                'progress_note'               => $isBazaarOffline
                    ? 'Pesanan Stand Bazar SMK dibuat. Tunjukkan invoice ini kepada penjaga stand toko.'
                    : 'Pesanan dibuat. Silakan klik tombol Hubungi WhatsApp Toko untuk koordinasi pengiriman.',
                'shipping_address'            => $shippingAddress,
            ]);

            $order->items()->create([
                'product_id' => $lockedProduct->id,
                'quantity'   => $qty,
                'unit_price' => $lockedProduct->price,
                'subtotal'   => $lockedProduct->price * $qty,
            ]);

            if ($lockedProduct->stock >= $qty) {
                $lockedProduct->decrement('stock', $qty);
            }
        });

        return redirect()
            ->route('buyer.dashboard')
            ->with('success', "Pesanan '{$product->product_name}' berhasil dicatat! Anda dapat melacak progres (Pesan → Kemas → Kirim → Sampai), menghubungi WA penjual, dan memberi rating bintang.");
    }

    /**
     * Fungsi:
     *   Pembeli mengonfirmasi bahwa barang sudah diterima (`progress_status = 'sampai'`).
     */
    public function markDelivered(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $order->update([
            'status'          => 'completed',
            'progress_status' => 'sampai',
            'delivered_at'    => $order->delivered_at ?? now(),
            'progress_note'   => 'Barang telah dikonfirmasi sampai dan diterima oleh pembeli.',
        ]);

        return back()->with('success', 'Alhamdulillah, pesanan telah ditandai Sampai! Silakan berikan rating bintang (★) untuk karya siswa ini.');
    }

    /**
     * Fungsi:
     *   Pembeli membatalkan pesanan yang belum dikemas (tahap 'pesan').
     *
     * Algoritma:
     *   1. Pastikan pesanan milik pembeli yang sedang login.
     *   2. Validasi tahap: hanya boleh dibatalkan jika belum dikemas ($order->effective_progress === 'pesan').
     *   3. Jika sudah 'kemas', 'kirim', atau 'sampai', tolak pembatalan.
     *   4. Kembalikan kuantitas stok produk yang sebelumnya dikurangi.
     *   5. Ubah status pesanan menjadi 'cancelled' & progress_status 'dibatalkan'.
     */
    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        if ($order->effective_progress !== 'pesan') {
            return back()->with('error', 'Pesanan tidak dapat dibatalkan karena sudah dalam proses pengemasan/pengiriman atau telah selesai.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->update([
                'status'          => 'cancelled',
                'progress_status' => 'dibatalkan',
                'progress_note'   => 'Pesanan dibatalkan oleh pembeli sebelum barang dikemas.',
            ]);
        });

        return back()->with('success', "Pesanan #{$order->order_code} berhasil dibatalkan dan stok produk telah dikembalikan.");
    }

    /**
     * Fungsi:
     *   Pembeli memberikan Rating Bintang (★ 1..5) & Ulasan setelah transaksi.
     *
     * Algoritma:
     *   1. Memastikan pesanan milik pembeli yang sedang login.
     *   2. Menyimpan `buyer_rating`, `buyer_review`, dan `rated_at` pada `orders`
     *      (sekaligus menandai `progress_status = 'sampai'` jika belum).
     *   3. Menyinkronkan rating & ulasan ke tabel `product_comments` untuk setiap
     *      produk di dalam pesanan agar rata-rata rating produk & toko di
     *      katalog serta Grafik Toko Admin langsung ter-update.
     */
    public function rateOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewText = trim((string) ($validated['review'] ?? ''));
        if ($reviewText === '') {
            $reviewText = 'Produk karya siswa sangat bagus dan memuaskan! (Rating Transaksi ' . $order->invoice_number . ')';
        }

        $order->update([
            'status'          => 'completed',
            'progress_status' => 'sampai',
            'delivered_at'    => $order->delivered_at ?? now(),
            'buyer_rating'    => (int) $validated['rating'],
            'buyer_review'    => $reviewText,
            'rated_at'        => now(),
        ]);

        $order->load('items');
        foreach ($order->items as $item) {
            ProductComment::updateOrCreate(
                [
                    'product_id' => $item->product_id,
                    'user_id'    => $request->user()->id,
                ],
                [
                    'rating'  => (int) $validated['rating'],
                    'comment' => $reviewText,
                ]
            );
        }

        return back()->with('success', "Terima kasih! Rating {$validated['rating']} Bintang (★) Anda telah berhasil disimpan.");
    }

    /**
     * Fungsi: Memperbarui data profil akun pembeli (Nama, WhatsApp, Jenis Pembeli, Alamat Pengiriman).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $messages = [
            'name.required'         => 'Nama lengkap pembeli wajib diisi.',
            'phone.required'        => 'Nomor WhatsApp pembeli wajib diisi.',
            'user_subtype.required' => 'Pilih kategori pembeli untuk penyesuaian diskon.',
            'desa.required'         => 'Desa / Kelurahan alamat pengiriman wajib diisi.',
            'kecamatan.required'    => 'Kecamatan alamat pengiriman wajib diisi.',
            'kabupaten.required'    => 'Kabupaten / Kota alamat pengiriman wajib diisi.',
            'password.min'          => 'Password baru minimal terdiri dari 6 karakter.',
        ];

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'phone'          => ['required', 'string', 'max:30'],
            'user_subtype'   => ['required', 'string', 'in:pelajar,keluarga,alumni,umum,siswa,guru'],
            'desa'           => ['required', 'string', 'max:100'],
            'kecamatan'      => ['required', 'string', 'max:100'],
            'kabupaten'      => ['required', 'string', 'max:100'],
            'kode_pos'       => ['nullable', 'string', 'max:15'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'password'       => ['nullable', 'string', 'min:6'],
        ], $messages);

        $updateData = [
            'name'           => $validated['name'],
            'phone'          => $validated['phone'],
            'user_subtype'   => $validated['user_subtype'],
            'desa'           => $validated['desa'],
            'kecamatan'      => $validated['kecamatan'],
            'kabupaten'      => $validated['kabupaten'],
            'kode_pos'       => $validated['kode_pos'] ?? null,
            'address_detail' => $validated['address_detail'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = bcrypt($validated['password']);
            $updateData['password_plain'] = $validated['password'];
        }

        $user->update($updateData);

        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::record(
                'profile_update',
                'Pembaruan Profil Pembeli',
                "Pengguna '{$user->name}' memperbarui data profil akun pembeli.",
                $user
            );
        }

        return redirect()->to(route('buyer.dashboard') . '#menu-profil')->with('success', 'Profil akun pembeli Anda berhasil disimpan!');
    }
}
