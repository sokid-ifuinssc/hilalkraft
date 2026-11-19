{{--
============================================================================
VIEW: HALAMAN TRANSAKSI & PENGIRIMAN TERPISAH (buyer/checkout.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman Checkout / Transaksi resmi setelah pembeli memilih
  produk dari Halaman Keranjang (atau klik Beli Sekarang dari halaman produk).
  Memuat rincian barang yang dipesan per toko, pilihan 3 skema pengiriman,
  alamat tujuan lengkap (Kabupaten, Kecamatan, Desa, Detail), promo Bazar SMK,
  serta kalkulasi rincian pembayaran akhir.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Halaman Transaksi & Pengiriman — hilalkraf SMK PAL')

@section('content')
@php
    $user = auth()->user();
    $buyerSubtype = $user->user_subtype ?? 'umum';
    $itemsList = collect($checkoutItems ?? []);
    $groupedCheckout = $itemsList->groupBy('store_id');
    $firstActiveBazaar = isset($activeBazaarEvents) ? $activeBazaarEvents->first() : null;
    $fallbackImg = asset('images/logo-hilalkraf.jpg');

    $checkoutProductIds = $itemsList->keys()->all();
    $checkoutProducts = \App\Models\Product::with('store')->findMany($checkoutProductIds);
    $canCartPickup = $checkoutProducts->isEmpty() ? true : $checkoutProducts->every(fn($p) => $p->canPickup());
    $canCartDelivery = $checkoutProducts->isEmpty() ? true : $checkoutProducts->every(fn($p) => $p->canDeliveryLokal());
    $canCartTransfer = $checkoutProducts->isEmpty() ? true : $checkoutProducts->every(fn($p) => $p->canShippingTransfer());
    $defaultCartMethod = $canCartPickup ? 'pickup' : ($canCartDelivery ? 'delivery_lokal' : 'shipping_transfer');
    $cartStores = $checkoutProducts->pluck('store')->unique('id')->filter();

    // Hitung subtotal kotor, diskon jenis pembeli, serta diskon bazar otomatis (khusus toko peserta bazar aktif)
    $activeBazaarStoreMap = $activeBazaarByStore ?? \App\Models\BazaarEvent::activeBazaarMapByStore();
    $grandGrossSubtotal = 0;
    $totalBuyerDiscount = 0;
    $totalBazarBaseDiscount = 0;
    $participatingCheckoutBazaars = [];

    foreach ($groupedCheckout as $sId => $sItems) {
        $stModel = $storesById[$sId] ?? \App\Models\Store::find($sId);
        $storeSub = (float) $sItems->sum(fn($i) => $i['price'] * $i['quantity']);
        $grandGrossSubtotal += $storeSub;

        $storeBazaar = $activeBazaarStoreMap->get($sId);
        if ($storeBazaar && $storeBazaar->isActiveNow()) {
            $participatingCheckoutBazaars[$storeBazaar->id] = $storeBazaar;
        } else {
            $storeBazaar = null;
        }

        if ($stModel) {
            $stPricing = $stModel->calculateOrderPricing($storeSub, $user, $storeBazaar, $storeBazaar !== null);
            $totalBuyerDiscount += (float) $stPricing['buyer_type_discount_amount'];
            $totalBazarBaseDiscount += (float) $stPricing['bazaar_discount_amount'];
        }
    }

    $netBeforeBazar = max(0, $grandGrossSubtotal - $totalBuyerDiscount);
    $netAfterDiscounts = max(0, $netBeforeBazar - $totalBazarBaseDiscount);
    $directFirstItem = (!empty($isDirectBuy) && $itemsList->isNotEmpty()) ? $itemsList->first() : null;
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <!-- Header & Alur Tahapan Checkout -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0 border border-[#CDEFE0]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg sm:text-xl font-extrabold text-[#0F291E]">Halaman Transaksi & Pengiriman</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Status Pembeli: <strong class="text-[#0B6E4F]">{{ $user->subtype_label }}</strong> • Periksa produk pesanan dan pilih metode penyerahan barang.
                </p>
            </div>
        </div>

        <!-- Stepper Indikator -->
        <div class="flex items-center gap-2 text-xs font-bold">
            <a href="{{ route('buyer.cart.index') }}" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-[#0B6E4F] transition">
                <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[10px] flex items-center justify-center">1</span>
                <span>Keranjang</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="inline-flex items-center gap-1.5 text-[#0B6E4F]">
                <span class="w-5 h-5 rounded-full bg-[#0B6E4F] text-white text-[10px] flex items-center justify-center">2</span>
                <span>Transaksi & Pengiriman</span>
            </span>
            <span class="text-slate-300">/</span>
            <span class="inline-flex items-center gap-1.5 text-slate-400">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-400 text-[10px] flex items-center justify-center">3</span>
                <span>Selesai</span>
            </span>
        </div>
    </div>

    <form action="{{ route('buyer.checkout') }}" method="POST" id="checkout-form" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        @csrf

        @if($directFirstItem)
            <input type="hidden" name="direct_product_id" value="{{ $directFirstItem['product_id'] }}">
            <input type="hidden" name="direct_quantity" value="{{ $directFirstItem['quantity'] }}">
        @else
            @foreach($itemsList as $pid => $cItem)
                <input type="hidden" name="checkout_product_ids[]" value="{{ $pid }}">
                <input type="hidden" name="checkout_quantities[{{ $pid }}]" value="{{ $cItem['quantity'] }}">
            @endforeach
        @endif

        <!-- KOLOM KIRI (7 Kolom): Daftar Produk Dipesan & Lokasi Tujuan -->
        <div class="lg:col-span-7 space-y-5">

            <!-- 1. Kartu Produk yang Dipesan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-4 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <h2 class="text-sm font-extrabold text-[#0F291E]">Produk Dipesan ({{ $itemsList->count() }} Item)</h2>
                    </div>
                    <a href="{{ route('buyer.cart.index') }}" class="text-xs font-bold text-[#0B6E4F] hover:underline">
                        Ubah Pilihan di Keranjang
                    </a>
                </div>

                <div class="space-y-4">
                    @foreach($groupedCheckout as $storeId => $storeItems)
                        @php
                            $itemStore = $storesById[$storeId] ?? \App\Models\Store::find($storeId);
                            $buyerDiscPct = $itemStore ? $itemStore->getDiscountPercentForBuyer($buyerSubtype) : 0;
                            $bazarExtraPct = $itemStore ? (int) ($itemStore->discount_bazar_extra ?? 0) : 0;
                        @endphp
                        <div class="rounded-xl border border-slate-200/80 overflow-hidden">
                            <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-xs font-extrabold text-[#0F291E]">
                                    <svg class="w-3.5 h-3.5 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                    </svg>
                                    <span>{{ $itemStore?->store_name ?? ($storeItems->first()['store_name'] ?? 'Toko Siswa') }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @if($buyerDiscPct > 0)
                                        <span class="rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                                            Diskon {{ $user->subtype_label }} {{ $buyerDiscPct }}%
                                        </span>
                                    @endif
                                    @php
                                        $storeBazaarBadge = $itemStore ? $activeBazaarStoreMap->get($itemStore->id) : null;
                                        $combinedStoreBazarPct = ($storeBazaarBadge && $storeBazaarBadge->isActiveNow())
                                            ? min(90, (int) $storeBazaarBadge->discount_percent + (int) ($bazarExtraPct ?? 0))
                                            : 0;
                                    @endphp
                                    @if($storeBazaarBadge && $storeBazaarBadge->isActiveNow())
                                        <span class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800">
                                            {{ $storeBazaarBadge->title }}@if($combinedStoreBazarPct > 0) • Diskon Otomatis {{ $combinedStoreBazarPct }}%@endif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="divide-y divide-slate-100 p-3 space-y-2.5">
                                @foreach($storeItems as $item)
                                    @php
                                        $imgUrl = \App\Models\Product::resolveImageUrl($item['image'] ?? '');
                                        $lineSub = (float) $item['price'] * (int) $item['quantity'];
                                    @endphp
                                    <div class="flex items-center justify-between gap-3 pt-2 first:pt-0">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <img src="{{ $imgUrl }}"
                                                 alt="{{ $item['product_name'] }}"
                                                 class="w-14 h-14 rounded-xl object-cover bg-slate-100 border border-slate-200 shrink-0"
                                                 onerror="this.src='{{ $fallbackImg }}'">
                                            <div class="min-w-0">
                                                <h3 class="text-xs sm:text-sm font-extrabold text-[#0F291E] truncate">{{ $item['product_name'] }}</h3>
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    {{ $item['quantity'] }} x Rp {{ number_format($item['price'], 0, ',', '.') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-xs sm:text-sm font-extrabold text-[#0F291E]">
                                                Rp {{ number_format($lineSub, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 2. Kartu Pilihan Metode Transaksi & Pengiriman -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 space-y-4 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <h2 class="text-sm font-extrabold text-[#0F291E]">Metode Transaksi & Pengiriman</h2>
                    </div>
                    <span class="text-[11px] text-slate-500">Sesuai ketentuan toko</span>
                </div>

                <div class="space-y-3">
                    <!-- Opsi 1: Datang ke Sekolah -->
                    <label class="delivery-option block rounded-2xl border-2 p-3.5 transition {{ $canCartPickup ? 'cursor-pointer border-[#0B6E4F] bg-[#EBF9F2]/30' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="pickup">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="pickup" @checked($defaultCartMethod === 'pickup') @disabled(!$canCartPickup) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateDeliveryUI('pickup')">
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">1. Datang ke Sekolah (Transaksi Langsung)</span>
                                    <span class="text-[11px] font-extrabold text-[#0B6E4F] bg-[#EBF9F2] px-2.5 py-0.5 rounded-full">Rp 0 (Gratis)</span>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-snug">
                                    Transaksi & serah terima barang langsung di lingkungan SMK Plus Al Hilal / Stand Bazar.
                                </p>
                                <div class="flex flex-wrap items-center justify-between gap-1 text-[10px]">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md mt-0.5">
                                        <span>Alur 3 Tahap:</span>
                                        <span class="text-[#0B6E4F]">1. Dipesan</span> →
                                        <span class="text-[#0B6E4F]">2. Dikemas</span> →
                                        <span class="text-[#0B6E4F]">3. Diterima / Diambil</span>
                                    </div>
                                    @if(!$canCartPickup)
                                        <span class="text-rose-600 font-bold">(Tidak didukung oleh salah satu produk)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Opsi 2: Delivery / Antar Langsung -->
                    <label class="delivery-option block rounded-2xl border-2 p-3.5 transition {{ $canCartDelivery ? 'cursor-pointer border-slate-200 hover:border-slate-300 bg-white' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="delivery_lokal">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="delivery_lokal" @checked($defaultCartMethod === 'delivery_lokal') @disabled(!$canCartDelivery) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateDeliveryUI('delivery_lokal')">
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">2. Delivery / Antar Langsung</span>
                                    <span class="text-[11px] font-extrabold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full">Rp 5.000</span>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-snug">
                                    Pengantaran langsung oleh kurir sekolah khusus wilayah <strong class="text-slate-900">Kec. Arjawinangun, Susukan, & Kaliwedi</strong> (Kab. Cirebon).
                                </p>
                                <div class="flex flex-wrap items-center justify-between gap-1 text-[10px]">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md mt-0.5">
                                        <span>Alur 4 Tahap:</span>
                                        <span class="text-blue-700">1. Dipesan</span> →
                                        <span class="text-blue-700">2. Dikemas</span> →
                                        <span class="text-blue-700">3. Dikirim</span> →
                                        <span class="text-blue-700">4. Diterima</span>
                                    </div>
                                    @if(!$canCartDelivery)
                                        <span class="text-rose-600 font-bold">(Tidak didukung oleh salah satu produk)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Opsi 3: Transfer Antar -->
                    <label class="delivery-option block rounded-2xl border-2 p-3.5 transition {{ $canCartTransfer ? 'cursor-pointer border-slate-200 hover:border-slate-300 bg-white' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="shipping_transfer">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="shipping_transfer" @checked($defaultCartMethod === 'shipping_transfer') @disabled(!$canCartTransfer) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateDeliveryUI('shipping_transfer')">
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">3. Transfer Antar (Luar Wilayah / Ekspedisi)</span>
                                    <span id="transfer-badge-cost" class="text-[11px] font-extrabold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full">Ongkir Kurir Langsung</span>
                                </div>
                                <p class="text-[11px] text-amber-950 font-medium leading-snug">
                                    Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir. Pembeli hanya mentransfer biaya karya ke rekening / e-wallet toko.
                                </p>
                                <div class="flex flex-wrap items-center justify-between gap-1 text-[10px]">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md mt-0.5">
                                        <span>Alur 4 Tahap:</span>
                                        <span class="text-amber-800">1. Dipesan</span> →
                                        <span class="text-amber-800">2. Dikemas</span> →
                                        <span class="text-amber-800">3. Dikirim</span> →
                                        <span class="text-amber-800">4. Diterima</span>
                                    </div>
                                    @if(!$canCartTransfer)
                                        <span class="text-rose-600 font-bold">(Tidak didukung oleh salah satu produk)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Kotak Rekening & E-Wallet Toko (Muncul saat Transfer Antar dipilih) -->
                <div id="cart-transfer-bank-box" class="hidden rounded-2xl bg-amber-50/80 border border-amber-200 p-4 space-y-3 text-xs text-amber-950">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-amber-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <span>Informasi Rekening & E-Wallet Pembayaran Toko</span>
                        </span>
                        <span class="text-[10px] text-amber-800 font-bold bg-amber-200/60 px-2 py-0.5 rounded-full">Transfer Antar</span>
                    </div>

                    @foreach($cartStores as $cStore)
                        <div class="bg-white rounded-xl p-3 border border-amber-200 space-y-2">
                            <span class="text-xs font-extrabold text-[#0F291E] block">Toko: {{ $cStore->store_name }}</span>
                            @if($cStore->has_bank_account)
                                <div class="flex items-center justify-between text-slate-700 bg-slate-50 rounded-lg p-2">
                                    <div>
                                        <span class="text-[10px] text-slate-400 block font-bold uppercase">Bank {{ $cStore->bank_name }}</span>
                                        <span class="font-mono font-bold text-xs text-[#0F291E]">{{ $cStore->bank_account_number }}</span>
                                        <span class="text-[10px] text-slate-500 block">a.n. {{ $cStore->bank_account_name }}</span>
                                    </div>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $cStore->bank_account_number }}'); alert('Nomor Rekening disalin!');" class="rounded bg-amber-100 hover:bg-amber-200 px-2.5 py-1 text-[10px] font-bold text-amber-900 transition">
                                        Salin
                                    </button>
                                </div>
                            @endif
                            @if($cStore->has_ewallet_account)
                                <div class="flex items-center justify-between text-slate-700 bg-slate-50 rounded-lg p-2">
                                    <div>
                                        <span class="text-[10px] text-slate-400 block font-bold uppercase">E-Wallet {{ $cStore->ewallet_name }}</span>
                                        <span class="font-mono font-bold text-xs text-[#0F291E]">{{ $cStore->ewallet_account_number }}</span>
                                        <span class="text-[10px] text-slate-500 block">a.n. {{ $cStore->ewallet_account_name }}</span>
                                    </div>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $cStore->ewallet_account_number }}'); alert('Nomor E-Wallet disalin!');" class="rounded bg-amber-100 hover:bg-amber-200 px-2.5 py-1 text-[10px] font-bold text-amber-900 transition">
                                        Salin
                                    </button>
                                </div>
                            @endif
                            @if(!$cStore->has_payment_account)
                                <p class="text-[10px] text-slate-500 italic">
                                    Nomor rekening toko akan diberikan melalui konfirmasi WhatsApp penjual setelah pesanan dibuat.
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- 3. Formulir Alamat & Lokasi Tujuan -->
                <div id="address-container" class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-[#0F291E] flex items-center gap-1.5" id="address-section-title">
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Lokasi Penyerahan / Tujuan</span>
                        </span>
                        <span class="text-[10px] text-slate-500 font-semibold" id="address-hint">Kampus SMK Plus Al Hilal</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5" id="geo-district-container">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Kabupaten / Kota</label>
                            <input type="text" name="destination_kabupaten" id="dest-kabupaten" value="{{ old('destination_kabupaten', $user->kabupaten ?: 'Cirebon') }}" placeholder="Cirebon" oninput="calculateLiveOngkir()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Kecamatan</label>
                            <input type="text" name="destination_kecamatan" id="dest-kecamatan" value="{{ old('destination_kecamatan', $user->kecamatan ?: 'Arjawinangun') }}" placeholder="Arjawinangun / Susukan / Kaliwedi" oninput="calculateLiveOngkir()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Desa / Kelurahan</label>
                            <input type="text" name="destination_desa" id="dest-desa" value="{{ old('destination_desa', $user->desa ?: '') }}" placeholder="Nama Desa / Kelurahan" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                    </div>

                    <!-- Shortcut cepat 3 kecamatan delivery lokal -->
                    <div id="local-district-shortcuts" class="hidden text-[11px] text-slate-600 space-y-1">
                        <div class="text-[10px] font-bold text-blue-900">Pilih cepat kecamatan terlayani:</div>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" onclick="setKecamatan('Arjawinangun')" class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-[10px] font-extrabold hover:bg-blue-200 transition">Kec. Arjawinangun</button>
                            <button type="button" onclick="setKecamatan('Susukan')" class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-[10px] font-extrabold hover:bg-blue-200 transition">Kec. Susukan</button>
                            <button type="button" onclick="setKecamatan('Kaliwedi')" class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-[10px] font-extrabold hover:bg-blue-200 transition">Kec. Kaliwedi</button>
                        </div>
                    </div>

                    <!-- Peringatan Wilayah jika Delivery Lokal tidak cocok -->
                    <div id="local-delivery-warning" class="hidden rounded-xl bg-rose-50 border border-rose-200 p-2.5 text-[11px] text-rose-800">
                        <strong>Perhatian:</strong> Delivery Antar Langsung hanya melayani 3 kecamatan di Kab. Cirebon: <strong>Arjawinangun, Susukan, dan Kaliwedi</strong>. Untuk kecamatan lain, silakan pilih <em>Transfer Antar</em> atau <em>Datang ke Sekolah</em>.
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1" id="lbl-shipping-address">Alamat Detail / Ruang Penyerahan *</label>
                        <input type="text" name="shipping_address" id="input-shipping-address" value="{{ old('shipping_address', $user->full_address !== 'Wilayah belum dilengkapi' ? $user->full_address : 'Stand Bazar / Kampus SMK Plus Al Hilal') }}" required placeholder="Contoh: Stand Bazar SMK Plus Al Hilal / Ruang Kelas XI TKJ / Jl. Nyi Mas Gandasari No..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (5 Kolom): Promo Bazar, Catatan & Ringkasan Pembayaran -->
        <div class="lg:col-span-5 space-y-5 lg:sticky lg:top-20">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 space-y-5 shadow-sm">

                @if(count($participatingCheckoutBazaars) > 0)
                    <!-- Info Promo Otomatis Acara Bazar Aktif -->
                    <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-extrabold text-amber-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                </svg>
                                <span>Promo Otomatis Acara Bazar</span>
                            </span>
                            <span class="rounded-full bg-amber-200/80 px-2 py-0.5 text-[10px] font-extrabold text-amber-900">
                                Aktif
                            </span>
                        </div>
                        <div class="space-y-1 text-[11px] text-amber-900">
                            @foreach($participatingCheckoutBazaars as $pbEvent)
                                <p class="font-semibold">
                                    {{ $pbEvent->title }} — Diskon Acara {{ (int) $pbEvent->discount_percent }}% (diterapkan otomatis untuk produk toko peserta)
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Catatan Tambahan -->
                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1">Catatan Tambahan untuk Penjual</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Titipkan di pos satpam / hubungi WA saat berangkat..." class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none">{{ old('notes') }}</textarea>
                </div>

                <!-- Rincian Pembayaran -->
                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                    <h3 class="font-extrabold text-[#0F291E] text-sm pb-1">Ringkasan Pembayaran</h3>

                    <div class="flex items-center justify-between text-slate-500">
                        <span>Subtotal untuk Produk</span>
                        <span class="font-bold text-[#0F291E]">Rp {{ number_format($grandGrossSubtotal, 0, ',', '.') }}</span>
                    </div>

                    @if($totalBuyerDiscount > 0)
                        <div class="flex items-center justify-between text-[#0B6E4F]">
                            <span>Potongan Diskon {{ $user->subtype_label }}</span>
                            <span class="font-bold">- Rp {{ number_format($totalBuyerDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if($totalBazarBaseDiscount > 0)
                        <div id="row-bazar-discount" class="flex items-center justify-between text-amber-700">
                            <span>Potongan Promo Acara Bazar</span>
                            <span class="font-bold">- Rp {{ number_format($totalBazarBaseDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between text-slate-500">
                        <span id="ongkir-label-text">Total Ongkos Kirim</span>
                        <span class="font-bold text-[#0B6E4F]" id="ongkir-display-text">Rp 0 (Gratis)</span>
                    </div>

                    <div class="flex items-center justify-between text-sm font-extrabold text-[#0F291E] pt-2.5 border-t border-dashed border-slate-200">
                        <span>Total Pembayaran</span>
                        <span class="text-lg font-black text-[#0B6E4F]" id="grand-total-text">Rp {{ number_format($netAfterDiscounts, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="space-y-2.5 pt-1">
                    <button type="submit" id="btn-submit-order" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] py-3.5 text-xs sm:text-sm font-extrabold text-white hover:bg-[#08583F] transition shadow-md active:scale-[0.99]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Buat Pesanan Sekarang</span>
                    </button>

                    <a href="{{ route('buyer.cart.index') }}" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                        <span>← Kembali ke Keranjang</span>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const grossSubtotal = {{ (float) $grandGrossSubtotal }};
    const buyerDiscountAmount = {{ (float) $totalBuyerDiscount }};
    const bazarDiscountAmount = {{ (float) $totalBazarBaseDiscount }};
    const hasParticipatingBazaar = @json(count($participatingCheckoutBazaars) > 0);
    const validLocalDistricts = ['arjawinangun', 'susukan', 'kaliwedi'];

    function formatRupiah(amount) {
        return 'Rp ' + Math.round(Number(amount || 0)).toLocaleString('id-ID');
    }

    function getSelectedDeliveryMethod() {
        const radios = document.getElementsByName('delivery_method');
        for (let r of radios) {
            if (r.checked) return r.value;
        }
        return '{{ $defaultCartMethod }}';
    }

    function setKecamatan(name) {
        const kecInput = document.getElementById('dest-kecamatan');
        const kabInput = document.getElementById('dest-kabupaten');
        if (kecInput) kecInput.value = name;
        if (kabInput && !kabInput.value) kabInput.value = 'Cirebon';
        calculateLiveOngkir();
    }

    function updateDeliveryUI(method) {
        document.querySelectorAll('.delivery-option').forEach(el => {
            if (el.getAttribute('data-method') === method) {
                el.classList.add('border-[#0B6E4F]', 'bg-[#EBF9F2]/30');
                el.classList.remove('border-slate-200', 'bg-white');
            } else {
                el.classList.remove('border-[#0B6E4F]', 'bg-[#EBF9F2]/30');
                el.classList.add('border-slate-200', 'bg-white');
            }
        });

        const addressHint = document.getElementById('address-hint');
        const addressLabel = document.getElementById('lbl-shipping-address');
        const addressInput = document.getElementById('input-shipping-address');
        const shortcuts = document.getElementById('local-district-shortcuts');
        const warning = document.getElementById('local-delivery-warning');
        const submitBtn = document.getElementById('btn-submit-order');
        const bankBox = document.getElementById('cart-transfer-bank-box');

        if (method === 'pickup') {
            if (addressHint) addressHint.textContent = hasParticipatingBazaar ? 'Kampus SMK Plus Al Hilal / Bazar' : 'Kampus SMK Plus Al Hilal';
            if (addressLabel) addressLabel.textContent = 'Lokasi / Ruang Penyerahan di Sekolah *';
            if (addressInput && (!addressInput.value || addressInput.value.includes('Jl.') || addressInput.value.includes('Stand Bazar'))) {
                addressInput.value = hasParticipatingBazaar ? 'Stand Bazar / Kampus SMK Plus Al Hilal' : 'Kampus SMK Plus Al Hilal';
            }
            if (shortcuts) shortcuts.classList.add('hidden');
            if (warning) warning.classList.add('hidden');
            if (bankBox) bankBox.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        } else if (method === 'delivery_lokal') {
            if (addressHint) addressHint.textContent = 'Kec. Arjawinangun, Susukan, Kaliwedi';
            if (addressLabel) addressLabel.textContent = 'Alamat Lengkap Pengantaran *';
            if (shortcuts) shortcuts.classList.remove('hidden');
            if (bankBox) bankBox.classList.add('hidden');
        } else {
            if (addressHint) addressHint.textContent = 'Seluruh Indonesia';
            if (addressLabel) addressLabel.textContent = 'Alamat Lengkap Pengiriman Ekspedisi *';
            if (shortcuts) shortcuts.classList.add('hidden');
            if (warning) warning.classList.add('hidden');
            if (bankBox) bankBox.classList.remove('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }

        calculateLiveOngkir();
    }

    function calculateLiveOngkir() {
        const method = getSelectedDeliveryMethod();
        const kecInput = document.getElementById('dest-kecamatan');
        const warning = document.getElementById('local-delivery-warning');
        const submitBtn = document.getElementById('btn-submit-order');
        const ongkirLabel = document.getElementById('ongkir-label-text');
        const ongkirDisplay = document.getElementById('ongkir-display-text');
        const grandTotalDisplay = document.getElementById('grand-total-text');

        let ongkir = 0;

        if (method === 'pickup') {
            ongkir = 0;
            if (ongkirLabel) ongkirLabel.textContent = 'Ongkos Kirim (Datang ke Sekolah)';
            if (ongkirDisplay) {
                ongkirDisplay.textContent = 'Rp 0 (Gratis)';
                ongkirDisplay.className = 'font-bold text-[#0B6E4F]';
            }
        } else if (method === 'delivery_lokal') {
            ongkir = 5000;
            if (ongkirLabel) ongkirLabel.textContent = 'Ongkos Kirim (Delivery Lokal)';
            if (ongkirDisplay) {
                ongkirDisplay.textContent = formatRupiah(ongkir);
                ongkirDisplay.className = 'font-bold text-blue-700';
            }

            const cleanKec = (kecInput ? kecInput.value : '').toLowerCase().trim();
            const isMatch = validLocalDistricts.some(d => cleanKec.includes(d));

            if (!isMatch && cleanKec.length > 0) {
                if (warning) warning.classList.remove('hidden');
                if (submitBtn) submitBtn.disabled = true;
            } else {
                if (warning) warning.classList.add('hidden');
                if (submitBtn) submitBtn.disabled = false;
            }
        } else {
            ongkir = 0;
            if (ongkirLabel) ongkirLabel.textContent = 'Ongkos Kirim (Transfer Antar)';
            if (ongkirDisplay) {
                ongkirDisplay.innerHTML = '<span class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md inline-block text-right">Ditanggung pembeli ke kurir</span>';
                ongkirDisplay.className = '';
            }
            if (warning) warning.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }

        const grandTotal = Math.max(0, grossSubtotal - buyerDiscountAmount - bazarDiscountAmount + ongkir);
        if (grandTotalDisplay) {
            grandTotalDisplay.textContent = formatRupiah(grandTotal);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDeliveryUI('{{ $defaultCartMethod }}');
    });
</script>
@endsection

