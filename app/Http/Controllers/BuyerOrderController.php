<?php

namespace App\Http\Controllers;

use App\Models\BazaarEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\Regency;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: BUYER ORDER CONTROLLER (KERANJANG TERPISAH, HALAMAN TRANSAKSI,
 *             DISKON BAZAR/PEMBELI, PROGRES BARANG & RATING SETELAH TRANSAKSI)
 * ============================================================================
 * Fungsi:
 *   Mengelola seluruh siklus transaksi Pembeli (`buyer`):
 *   1. Halaman Keranjang Belanja (`addToCart`, `viewCart`, `updateCartQuantity`,
 *      `removeFromCart`, `removeSelectedFromCart`) yang menampilkan daftar
 *      barang yang dipilih (seperti Shopee).
 *   2. Halaman Transaksi / Checkout Terpisah (`showCheckout`, `checkout`)
 *      untuk memilih metode pengiriman, lokasi, voucher bazar, dan konfirmasi.
 *   3. Pesan Langsung dari Halaman Produk (`directOrder`) agar transaksi tetap
 *      tercatat progres barangnya (`Pesan` -> `Kemas` -> `Kirim` -> `Sampai`).
 *   4. Kalkulasi Otomatis Skema Diskon Jenis Pembeli (`Pelajar`, `Keluarga SMK`,
 *      `Alumni`, `Umum`) & Diskon Acara Bazar / Event SMK (`BazaarEvent`).
 *   5. Konfirmasi Barang Sampai (`markDelivered`) & Pemberian Rating Bintang
 *      (`rateOrder`, 1 s/d 5) setelah transaksi.
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
        $newQty = min((int) $product->stock, ($cart[$product->id]['quantity'] ?? 0) + $quantity);

        $cart[$product->id] = [
            'product_id'   => $product->id,
            'slug'         => $product->slug,
            'store_id'     => $product->store_id,
            'store_name'   => $product->store->store_name ?? 'Toko Karya Siswa',
            'product_name' => $product->product_name,
            'category'     => $product->category,
            'price'        => (float) $product->price,
            'stock'        => (int) $product->stock,
            'image'        => $product->image,
            'quantity'     => max(1, $newQty),
        ];

        session()->put('hilalkraft_cart', $cart);

        return redirect()->route('buyer.cart.index')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang belanja.');
    }

    /**
     * Halaman Keranjang Belanja (Hanya menampilkan daftar barang yang dipilih seperti Shopee).
     */
    public function viewCart(): View
    {
        $cart = session()->get('hilalkraft_cart', []);

        // Sinkronisasi data produk terbaru (stok, harga, gambar, slug)
        if (! empty($cart)) {
            $productsById = Product::with('store')->whereIn('id', array_keys($cart))->get()->keyBy('id');
            foreach ($cart as $pid => &$item) {
                if (isset($productsById[$pid])) {
                    $p = $productsById[$pid];
                    $item['slug']         = $p->slug;
                    $item['product_name'] = $p->product_name;
                    $item['category']     = $p->category;
                    $item['price']        = (float) $p->price;
                    $item['stock']        = (int) $p->stock;
                    $item['image']        = $p->image;
                    $item['store_id']     = $p->store_id;
                    $item['store_name']   = $p->store->store_name ?? $item['store_name'];
                }
            }
            unset($item);
            session()->put('hilalkraft_cart', $cart);
        }

        $storeIds = collect($cart)->pluck('store_id')->unique()->values()->all();
        $storesById = Store::whereIn('id', $storeIds)->get()->keyBy('id');
        $activeBazaarEvents = BazaarEvent::activeEvents();

        return view('buyer.cart', compact('cart', 'storesById', 'activeBazaarEvents'));
    }

    /**
     * Memperbarui kuantitas item di keranjang belanja.
     */
    public function updateCartQuantity(Request $request, int $productId): RedirectResponse|JsonResponse
    {
        $cart = session()->get('hilalkraft_cart', []);
        if (! isset($cart[$productId])) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Item tidak ditemukan di keranjang.'], 404);
            }
            return back()->with('error', 'Item tidak ditemukan di keranjang.');
        }

        $product = Product::find($productId);
        $maxStock = $product ? max(1, (int) $product->stock) : 99;
        $qty = max(1, min($maxStock, (int) $request->input('quantity', 1)));

        $cart[$productId]['quantity'] = $qty;
        if ($product) {
            $cart[$productId]['price'] = (float) $product->price;
            $cart[$productId]['stock'] = (int) $product->stock;
        }
        session()->put('hilalkraft_cart', $cart);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'            => true,
                'product_id'    => $productId,
                'quantity'      => $qty,
                'unit_price'    => (float) $cart[$productId]['price'],
                'item_subtotal' => (float) $cart[$productId]['price'] * $qty,
            ]);
        }

        return back()->with('success', 'Jumlah barang di keranjang diperbarui.');
    }

    public function removeFromCart(int $productId): RedirectResponse
    {
        $cart = session()->get('hilalkraft_cart', []);
        unset($cart[$productId]);
        session()->put('hilalkraft_cart', $cart);

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    public function removeSelectedFromCart(Request $request): RedirectResponse
    {
        $selectedIds = array_map('intval', (array) $request->input('selected_items', []));
        $cart = session()->get('hilalkraft_cart', []);

        foreach ($selectedIds as $pid) {
            unset($cart[$pid]);
        }

        session()->put('hilalkraft_cart', $cart);

        return redirect()->route('buyer.cart.index')
            ->with('success', 'Produk yang dipilih berhasil dihapus dari keranjang.');
    }

    /**
     * Halaman Transaksi / Checkout Terpisah (Menampilkan rincian barang yang akan dibeli,
     * pilihan metode pengiriman, alamat tujuan, promo bazar, dan konfirmasi pembayaran).
     */
    public function showCheckout(Request $request): View|RedirectResponse
    {
        $activeBazaarEvents = BazaarEvent::activeEvents();
        $regenciesGrouped = Regency::getGroupedByProvince();

        // Mode 1: Beli / Pesan Langsung dari Halaman Detail Produk
        if ($request->filled('direct_product_id')) {
            $productId = (int) $request->input('direct_product_id');
            $product = Product::with('store')->find($productId);

            if (! $product || ! $product->store) {
                return redirect()->route('home')->with('error', 'Produk tidak ditemukan.');
            }

            $qty = max(1, min(max(1, (int) $product->stock), (int) $request->input('quantity', 1)));
            $isBazaarClaim = $request->boolean('is_bazaar_offline');

            $checkoutItems = [
                $product->id => [
                    'product_id'   => $product->id,
                    'slug'         => $product->slug,
                    'store_id'     => $product->store_id,
                    'store_name'   => $product->store->store_name,
                    'product_name' => $product->product_name,
                    'category'     => $product->category,
                    'price'        => (float) $product->price,
                    'stock'        => (int) $product->stock,
                    'image'        => $product->image,
                    'quantity'     => $qty,
                ],
            ];

            $storesById = collect([$product->store_id => $product->store]);
            $isDirectBuy = true;

            return view('buyer.checkout', compact(
                'checkoutItems',
                'storesById',
                'activeBazaarEvents',
                'regenciesGrouped',
                'isDirectBuy',
                'isBazaarClaim'
            ));
        }

        // Mode 2: Checkout dari Halaman Keranjang Belanja
        $cart = session()->get('hilalkraft_cart', []);
        if (empty($cart)) {
            return redirect()->route('buyer.cart.index')
                ->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        // Sinkronisasi kuantitas terbaru jika dikirim dari form keranjang
        $inputQuantities = (array) $request->input('quantities', []);
        if (! empty($inputQuantities)) {
            foreach ($inputQuantities as $pid => $qVal) {
                $pid = (int) $pid;
                if (isset($cart[$pid])) {
                    $cart[$pid]['quantity'] = max(1, (int) $qVal);
                }
            }
            session()->put('hilalkraft_cart', $cart);
        }

        // Filter hanya produk yang dicentang / dipilih oleh pembeli
        if ($request->has('selected_items')) {
            $selectedIds = array_values(array_filter(array_map('intval', (array) $request->input('selected_items', []))));
            session()->put('hilalkraft_checkout_selected', $selectedIds);
        } else {
            $selectedIds = session()->get('hilalkraft_checkout_selected', array_keys($cart));
        }

        $checkoutItems = [];
        foreach ($selectedIds as $pid) {
            if (isset($cart[$pid])) {
                $checkoutItems[$pid] = $cart[$pid];
            }
        }

        // Jika tidak ada yang cocok, fallback ke seluruh isi keranjang
        if (empty($checkoutItems)) {
            $checkoutItems = $cart;
        }

        $storeIds = collect($checkoutItems)->pluck('store_id')->unique()->values()->all();
        $storesById = Store::whereIn('id', $storeIds)->get()->keyBy('id');
        $isDirectBuy = false;
        $isBazaarClaim = false;

        return view('buyer.checkout', compact(
            'checkoutItems',
            'storesById',
            'activeBazaarEvents',
            'regenciesGrouped',
            'isDirectBuy',
            'isBazaarClaim'
        ));
    }

    /**
     * Mengeksekusi pembuatan pesanan dari Halaman Transaksi (`buyer/checkout.blade.php`)
     * beserta perhitungan diskon jenis pembeli & diskon acara Bazar SMK.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_method'        => ['nullable', 'string', 'in:pickup,delivery_lokal,shipping_transfer'],
            'shipping_address'       => ['nullable', 'string', 'max:255'],
            'destination_kabupaten'  => ['nullable', 'string', 'max:100'],
            'destination_kecamatan'  => ['nullable', 'string', 'max:100'],
            'destination_desa'       => ['nullable', 'string', 'max:100'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'bazaar_voucher_code'    => ['nullable', 'string', 'max:50'],
            'is_bazaar_offline'      => ['nullable', 'boolean'],
            'direct_product_id'      => ['nullable', 'integer'],
            'direct_quantity'        => ['nullable', 'integer', 'min:1'],
            'checkout_product_ids'   => ['nullable', 'array'],
            'checkout_product_ids.*' => ['integer'],
            'checkout_quantities'    => ['nullable', 'array'],
        ]);

        $buyer = $request->user();
        $isDirectBuy = $request->filled('direct_product_id');
        $itemsToProcess = [];

        if ($isDirectBuy) {
            $directProduct = Product::with('store')->find((int) $validated['direct_product_id']);
            if (! $directProduct || ! $directProduct->store) {
                return redirect()->route('home')->with('error', 'Produk yang dipesan tidak ditemukan.');
            }
            $directQty = max(1, (int) ($validated['direct_quantity'] ?? 1));
            if ($directProduct->stock < $directQty) {
                return back()->withInput()->with('error', 'Stok produk tidak mencukupi.');
            }

            $itemsToProcess[$directProduct->id] = [
                'product_id'   => $directProduct->id,
                'store_id'     => $directProduct->store_id,
                'store_name'   => $directProduct->store->store_name,
                'product_name' => $directProduct->product_name,
                'price'        => (float) $directProduct->price,
                'image'        => $directProduct->image,
                'quantity'     => $directQty,
            ];
        } else {
            $cart = session()->get('hilalkraft_cart', []);
            if (empty($cart)) {
                return redirect()->route('buyer.cart.index')->with('error', 'Keranjang belanja Anda masih kosong.');
            }

            $selectedIds = array_values(array_filter(array_map('intval', (array) ($validated['checkout_product_ids'] ?? []))));
            $customQtys = (array) ($validated['checkout_quantities'] ?? []);

            if (! empty($selectedIds)) {
                foreach ($selectedIds as $pid) {
                    if (isset($cart[$pid])) {
                        $item = $cart[$pid];
                        if (isset($customQtys[$pid])) {
                            $item['quantity'] = max(1, (int) $customQtys[$pid]);
                        }
                        $itemsToProcess[$pid] = $item;
                    }
                }
            }

            if (empty($itemsToProcess)) {
                $itemsToProcess = $cart;
            }
        }

        $deliveryMethod = ($validated['delivery_method'] ?? null) ?: 'pickup';
        $kabupaten = ($validated['destination_kabupaten'] ?? null) ?: ($buyer->kabupaten ?: 'Cirebon');
        $kecamatan = ($validated['destination_kecamatan'] ?? null) ?: ($buyer->kecamatan ?: '');
        $desa = ($validated['destination_desa'] ?? null) ?: ($buyer->desa ?: '');

        // Validasi izin metode transaksi dari masing-masing produk yang dipesan
        $productModels = Product::findMany(array_keys($itemsToProcess));
        foreach ($productModels as $pModel) {
            if (! $pModel->isDeliveryMethodAllowed($deliveryMethod)) {
                return back()->withInput()->with('error', "Karya produk '{$pModel->product_name}' hanya melayani opsi: {$pModel->allowed_delivery_label}. Pilihan pengiriman saat ini tidak didukung.");
            }
        }

        // Validasi Aturan Wilayah: Delivery Lokal hanya untuk Arjawinangun, Susukan, Kaliwedi (Cirebon)
        if ($deliveryMethod === 'delivery_lokal') {
            $cleanKec = strtolower(trim((string) $kecamatan));
            if (! in_array($cleanKec, Order::LOCAL_DELIVERY_DISTRICTS, true)) {
                return back()->withInput()->with('error', 'Metode Delivery / Antar Langsung hanya melayani wilayah Kecamatan Arjawinangun, Susukan, dan Kaliwedi (Kabupaten Cirebon). Untuk wilayah lain, silakan pilih Transfer Antar atau Datang ke Sekolah.');
            }
        }

        $shippingCost = Order::calculateEstimatedShipping($deliveryMethod, $kabupaten, $kecamatan);

        $rawShippingAddr = trim((string) ($validated['shipping_address'] ?? ''));
        $shippingAddress = match ($deliveryMethod) {
            'pickup' => 'Ambil Langsung di SMK Plus Al Hilal Arjawinangun' . ($rawShippingAddr !== '' ? ' (' . $rawShippingAddr . ')' : ''),
            default  => $rawShippingAddr !== '' ? $rawShippingAddr : ($buyer->full_address ?: "Desa {$desa}, Kec. {$kecamatan}, Kab. {$kabupaten}"),
        };

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

        $groupedByStore = collect($itemsToProcess)->groupBy('store_id');

        DB::transaction(function () use ($groupedByStore, $buyer, $validated, $bazaarEvent, $isBazaarOffline, $deliveryMethod, $shippingCost, $kabupaten, $kecamatan, $desa, $shippingAddress) {
            foreach ($groupedByStore as $storeId => $items) {
                $store = Store::findOrFail($storeId);
                $subtotal = (float) $items->sum(fn ($item) => $item['price'] * $item['quantity']);

                $pricing = $store->calculateOrderPricing($subtotal, $buyer, $bazaarEvent, $isBazaarOffline);
                $finalTotal = max(0, (float) $pricing['total_price'] + (float) $shippingCost);

                $courierName = match ($deliveryMethod) {
                    'delivery_lokal'    => 'Kurir Sekolah (Antar Langsung)',
                    'shipping_transfer' => 'Ekspedisi Reguler (Transfer Antar)',
                    default             => 'Ambil Sendiri di Sekolah',
                };

                $progressNote = match ($deliveryMethod) {
                    'delivery_lokal'    => 'Pesanan diterima. Akan diantar langsung oleh kurir ke alamat pembeli.',
                    'shipping_transfer' => 'Pesanan diterima. Menunggu koordinasi transfer dan pengiriman ekspedisi.',
                    default             => 'Pesanan diterima. Silakan datang langsung ke SMK Plus Al Hilal untuk serah terima barang.',
                };

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
                    'delivery_method'             => $deliveryMethod,
                    'shipping_cost'               => $shippingCost,
                    'destination_kabupaten'       => $kabupaten,
                    'destination_kecamatan'       => $kecamatan,
                    'destination_desa'            => $desa,
                    'courier_name'                => $courierName,
                    'total_price'                 => $finalTotal,
                    'status'                      => 'pending',
                    'progress_status'             => 'pesan',
                    'progress_note'               => $progressNote,
                    'shipping_address'            => $shippingAddress,
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

        // Hapus hanya item yang telah di-checkout dari keranjang session
        if (! $isDirectBuy) {
            $cart = session()->get('hilalkraft_cart', []);
            foreach (array_keys($itemsToProcess) as $checkedOutId) {
                unset($cart[$checkedOutId]);
            }
            session()->put('hilalkraft_cart', $cart);
            session()->forget('hilalkraft_checkout_selected');
        }

        return redirect()
            ->route('buyer.dashboard')
            ->with('success', 'Pesanan berhasil dibuat! Anda dapat memantau progres pesanan dan menghubungi penjual via WhatsApp.');
    }

    /**
     * Membuat pesanan langsung dari halaman detail produk (Pesan Sekarang /
     * Beli di Bazar SMK) sehingga progres pesanan & rating bintang tetap tercatat.
     */
    public function directOrder(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity'              => ['nullable', 'integer', 'min:1'],
            'delivery_method'       => ['nullable', 'string', 'in:pickup,delivery_lokal,shipping_transfer'],
            'destination_kabupaten' => ['nullable', 'string', 'max:100'],
            'destination_kecamatan' => ['nullable', 'string', 'max:100'],
            'destination_desa'      => ['nullable', 'string', 'max:100'],
            'is_bazaar_offline'     => ['nullable', 'boolean'],
            'bazaar_voucher_code'   => ['nullable', 'string', 'max:50'],
            'shipping_address'      => ['nullable', 'string', 'max:255'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $qty = max(1, (int) ($validated['quantity'] ?? 1));
        if ($product->stock < $qty) {
            return back()->with('error', 'Stok produk tidak mencukupi.');
        }

        $buyer = $request->user();
        $store = $product->store;
        $deliveryMethod = ($validated['delivery_method'] ?? null) ?: 'pickup';
        $kabupaten = ($validated['destination_kabupaten'] ?? null) ?: ($buyer->kabupaten ?: 'Cirebon');
        $kecamatan = ($validated['destination_kecamatan'] ?? null) ?: ($buyer->kecamatan ?: '');
        $desa = ($validated['destination_desa'] ?? null) ?: ($buyer->desa ?: '');

        if (! $product->isDeliveryMethodAllowed($deliveryMethod)) {
            return back()->withInput()->with('error', "Karya produk '{$product->product_name}' hanya melayani opsi: {$product->allowed_delivery_label}. Pilihan metode transaksi saat ini tidak didukung.");
        }

        if ($deliveryMethod === 'delivery_lokal') {
            $cleanKec = strtolower(trim((string) $kecamatan));
            if (! in_array($cleanKec, Order::LOCAL_DELIVERY_DISTRICTS, true)) {
                return back()->withInput()->with('error', 'Metode Delivery / Antar Langsung hanya melayani wilayah Kecamatan Arjawinangun, Susukan, dan Kaliwedi (Kabupaten Cirebon). Untuk wilayah lain, silakan pilih Transfer Antar atau Datang ke Sekolah.');
            }
        }

        $shippingCost = Order::calculateEstimatedShipping($deliveryMethod, $kabupaten, $kecamatan);

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
        $finalTotal = max(0, (float) $pricing['total_price'] + (float) $shippingCost);

        $rawShippingAddr = trim((string) ($validated['shipping_address'] ?? ''));
        $shippingAddress = match ($deliveryMethod) {
            'pickup' => 'Ambil Langsung di SMK Plus Al Hilal Arjawinangun' . ($rawShippingAddr !== '' ? ' (' . $rawShippingAddr . ')' : ''),
            default  => $rawShippingAddr !== '' ? $rawShippingAddr : ($buyer->full_address ?: "Desa {$desa}, Kec. {$kecamatan}, Kab. {$kabupaten}"),
        };

        $courierName = match ($deliveryMethod) {
            'delivery_lokal'    => 'Kurir Sekolah (Antar Langsung)',
            'shipping_transfer' => 'Ekspedisi Reguler (Transfer Antar)',
            default             => 'Ambil Sendiri di Sekolah',
        };

        $progressNote = match ($deliveryMethod) {
            'delivery_lokal'    => 'Pesanan langsung dibuat. Akan diantar kurir ke alamat Anda.',
            'shipping_transfer' => 'Pesanan langsung dibuat. Silakan hubungi penjual via WA untuk transfer & pengiriman.',
            default             => $isBazaarOffline
                ? 'Pesanan Stand Bazar SMK dibuat. Tunjukkan invoice ini kepada penjaga stand toko.'
                : 'Pesanan dibuat. Silakan datang ke SMK Plus Al Hilal untuk serah terima barang.',
        };

        $notes = $validated['notes'] ?? null;

        $order = DB::transaction(function () use ($product, $store, $buyer, $qty, $pricing, $finalTotal, $shippingCost, $deliveryMethod, $courierName, $kabupaten, $kecamatan, $desa, $progressNote, $bazaarEvent, $isBazaarOffline, $shippingAddress, $notes) {
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
                'delivery_method'             => $deliveryMethod,
                'shipping_cost'               => $shippingCost,
                'destination_kabupaten'       => $kabupaten,
                'destination_kecamatan'       => $kecamatan,
                'destination_desa'            => $desa,
                'courier_name'                => $courierName,
                'total_price'                 => $finalTotal,
                'status'                      => 'pending',
                'progress_status'             => 'pesan',
                'progress_note'               => $progressNote,
                'shipping_address'            => $shippingAddress,
                'notes'                       => $notes,
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

            return $order;
        });

        return redirect()
            ->route('buyer.dashboard')
            ->with('success', "Pesanan '{$product->product_name}' berhasil dicatat! Anda dapat melacak progres ({$order->delivery_method_short_label}), menghubungi WA penjual, dan memberi penilaian.");
    }

    /**
     * Pembeli mengonfirmasi bahwa barang sudah diterima (`progress_status = 'sampai'`).
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

        return back()->with('success', 'Alhamdulillah, pesanan telah ditandai Sampai! Silakan berikan penilaian untuk karya siswa ini.');
    }

    /**
     * Pembeli membatalkan pesanan yang belum dikemas (tahap 'pesan').
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
     * Pembeli memberikan Rating (1..5) & Ulasan setelah transaksi.
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
                    'rating'     => (int) $validated['rating'],
                    'comment'    => $reviewText,
                    'ip_address' => $request->ip(),
                ]
            );
        }

        return back()->with('success', "Terima kasih! Penilaian {$validated['rating']}/5 Anda telah berhasil disimpan.");
    }

    /**
     * Memperbarui data profil akun pembeli (Nama, WhatsApp, Jenis Pembeli, Alamat Pengiriman).
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
