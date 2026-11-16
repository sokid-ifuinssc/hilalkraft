{{--
============================================================================
VIEW: HALAMAN KERANJANG BELANJA TERPISAH (SEPERTI SHOPEE) (buyer/cart.blade.php)
============================================================================
Fungsi:
  Menampilkan khusus daftar produk yang dimasukkan ke keranjang belanja,
  dikelompokkan per toko, lengkap dengan fitur centang pilih produk,
  pengaturan kuantitas (+/-), hapus produk, serta bar Checkout di bawah
  untuk melanjutkan ke Halaman Transaksi (/pembeli/transaksi).
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Keranjang Belanja Saya — hilalkraf SMK PAL')

@section('content')
@php
    $user = auth()->user();
    $buyerSubtype = $user->user_subtype ?? 'umum';
    $groupedCart = collect($cart)->groupBy('store_id');
    $totalItemsCount = count($cart);
    $fallbackImg = asset('images/logo-hilalkraf.jpg');
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-32 space-y-5">

    <!-- Header Halaman Keranjang & Stepper Alur -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-2xs">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0 border border-[#CDEFE0]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg sm:text-xl font-extrabold text-[#0F291E]">Keranjang Saya</h1>
                    <span class="rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[11px] font-extrabold text-[#0B6E4F]">
                        {{ $totalItemsCount }} Produk
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pilih karya yang ingin Anda beli, atur jumlahnya, lalu klik <strong class="text-[#0F291E]">Checkout</strong> untuk masuk ke halaman transaksi.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 hover:border-[#0B6E4F] hover:text-[#0B6E4F] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Produk Lain</span>
            </a>
            <a href="{{ route('buyer.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-[#0F291E] hover:border-[#0B6E4F] hover:text-[#0B6E4F] transition">
                <svg class="w-3.5 h-3.5 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Pesanan Saya</span>
            </a>
        </div>
    </div>

    @if($totalItemsCount > 0)
        <!-- Form Utama Keranjang menuju Halaman Transaksi (/pembeli/transaksi) -->
        <form action="{{ route('buyer.checkout.index') }}" method="GET" id="cart-selection-form" class="space-y-4">

            <!-- Baris Judul Kolom (Tampilan Desktop ala Shopee) -->
            <div class="hidden md:grid md:grid-cols-12 items-center bg-white rounded-xl border border-slate-200/80 px-5 py-3 text-xs font-bold text-slate-500 shadow-2xs">
                <div class="col-span-5 flex items-center gap-3">
                    <input type="checkbox" id="check-all-top" checked onchange="toggleSelectAllCart(this.checked)" class="w-4 h-4 rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F] cursor-pointer">
                    <label for="check-all-top" class="cursor-pointer text-[#0F291E]">Produk Karya Siswa</label>
                </div>
                <div class="col-span-2 text-center">Harga Satuan</div>
                <div class="col-span-2 text-center">Kuantitas</div>
                <div class="col-span-2 text-center">Total Harga</div>
                <div class="col-span-1 text-right">Aksi</div>
            </div>

            <!-- Daftar Item Dikelompokkan per Toko -->
            @foreach($groupedCart as $storeId => $storeItems)
                @php
                    $itemStore = $storesById[$storeId] ?? \App\Models\Store::find($storeId);
                    $storeTitle = $itemStore?->store_name ?? ($storeItems->first()['store_name'] ?? 'Toko Karya Siswa');
                    $buyerDiscPct = $itemStore ? $itemStore->getDiscountPercentForBuyer($buyerSubtype) : 0;
                    $bazarExtraPct = $itemStore ? (int) ($itemStore->discount_bazar_extra ?? 0) : 0;
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden" data-store-group="{{ $storeId }}">
                    <!-- Header Toko -->
                    <div class="px-4 sm:px-5 py-3 bg-slate-50/70 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   checked
                                   class="store-group-checkbox w-4 h-4 rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F] cursor-pointer"
                                   data-store-id="{{ $storeId }}"
                                   onchange="toggleStoreItems({{ $storeId }}, this.checked)">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded bg-[#0B6E4F] px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-white">
                                    Toko Resmi
                                </span>
                                @if($itemStore)
                                    <a href="{{ route('stores.show', $itemStore->slug) }}" class="text-xs sm:text-sm font-extrabold text-[#0F291E] hover:text-[#0B6E4F] transition flex items-center gap-1">
                                        <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                        </svg>
                                        <span>{{ $storeTitle }}</span>
                                    </a>
                                @else
                                    <span class="text-xs sm:text-sm font-extrabold text-[#0F291E]">{{ $storeTitle }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Badge Diskon Toko -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if($buyerDiscPct > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#EBF9F2] border border-[#CDEFE0] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <span>Diskon {{ $user->subtype_label }} {{ $buyerDiscPct }}%</span>
                                </span>
                            @endif
                            @if($bazarExtraPct > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                    </svg>
                                    <span>+{{ $bazarExtraPct }}% Ekstra Saat Bazar</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Daftar Produk di Toko Ini -->
                    <div class="divide-y divide-slate-100">
                        @foreach($storeItems as $item)
                            @php
                                $pid = (int) $item['product_id'];
                                $imgUrl = \App\Models\Product::resolveImageUrl($item['image'] ?? '');
                                $unitPrice = (float) $item['price'];
                                $qty = max(1, (int) $item['quantity']);
                                $maxStock = max(1, (int) ($item['stock'] ?? 99));
                                $lineTotal = $unitPrice * $qty;
                                $discAmountPerUnit = $buyerDiscPct > 0 ? round($unitPrice * ($buyerDiscPct / 100)) : 0;
                                $afterDiscUnit = max(0, $unitPrice - $discAmountPerUnit);
                            @endphp

                            <div class="p-4 sm:px-5 sm:py-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center hover:bg-slate-50/40 transition cart-item-row"
                                 data-product-id="{{ $pid }}"
                                 data-store-id="{{ $storeId }}"
                                 data-unit-price="{{ $unitPrice }}"
                                 data-disc-pct="{{ $buyerDiscPct }}"
                                 data-max-stock="{{ $maxStock }}">

                                <!-- Kolom 1: Checkbox + Foto + Nama Produk -->
                                <div class="md:col-span-5 flex items-start sm:items-center gap-3">
                                    <input type="checkbox"
                                           name="selected_items[]"
                                           value="{{ $pid }}"
                                           checked
                                           data-store-id="{{ $storeId }}"
                                           onchange="onItemCheckboxChange()"
                                           class="cart-item-checkbox mt-2 sm:mt-0 w-4 h-4 rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F] cursor-pointer shrink-0">

                                    <a href="{{ !empty($item['slug']) ? route('products.show', $item['slug']) : '#' }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 block">
                                        <img src="{{ $imgUrl }}"
                                             alt="{{ $item['product_name'] }}"
                                             class="w-full h-full object-cover"
                                             onerror="this.src='{{ $fallbackImg }}'">
                                    </a>

                                    <div class="min-w-0 flex-1 space-y-1">
                                        <a href="{{ !empty($item['slug']) ? route('products.show', $item['slug']) : '#' }}" class="text-xs sm:text-sm font-extrabold text-[#0F291E] hover:text-[#0B6E4F] line-clamp-2 transition">
                                            {{ $item['product_name'] }}
                                        </a>
                                        @if(!empty($item['category']))
                                            <span class="inline-block rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                                Kategori: {{ $item['category'] }}
                                            </span>
                                        @endif
                                        <p class="text-[10px] text-slate-400">Stok tersedia: {{ $maxStock }} unit</p>
                                    </div>
                                </div>

                                <!-- Kolom 2: Harga Satuan -->
                                <div class="md:col-span-2 flex md:flex-col items-center justify-between md:justify-center text-xs">
                                    <span class="text-slate-400 md:hidden">Harga Satuan:</span>
                                    <div class="text-right md:text-center">
                                        @if($buyerDiscPct > 0)
                                            <span class="text-[11px] text-slate-400 line-through block">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                                            <span class="font-bold text-[#0F291E]">Rp {{ number_format($afterDiscUnit, 0, ',', '.') }}</span>
                                        @else
                                            <span class="font-bold text-[#0F291E]">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Kolom 3: Pengaturan Kuantitas (+/-) -->
                                <div class="md:col-span-2 flex items-center justify-between md:justify-center">
                                    <span class="text-xs text-slate-400 md:hidden">Kuantitas:</span>
                                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
                                        <button type="button"
                                                onclick="changeCartItemQty({{ $pid }}, -1)"
                                                class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold text-sm transition">
                                            −
                                        </button>
                                        <input type="number"
                                               name="quantities[{{ $pid }}]"
                                               id="cart-qty-{{ $pid }}"
                                               value="{{ $qty }}"
                                               min="1"
                                               max="{{ $maxStock }}"
                                               onchange="onCartQtyInputChange({{ $pid }}, this.value)"
                                               class="w-11 h-8 text-center text-xs font-extrabold text-[#0F291E] border-x border-slate-200 focus:outline-none">
                                        <button type="button"
                                                onclick="changeCartItemQty({{ $pid }}, 1)"
                                                class="w-8 h-8 flex items-center justify-center text-[#0B6E4F] hover:bg-emerald-50 font-bold text-sm transition">
                                            +
                                        </button>
                                    </div>
                                </div>

                                <!-- Kolom 4: Total Harga per Item -->
                                <div class="md:col-span-2 flex items-center justify-between md:justify-center">
                                    <span class="text-xs text-slate-400 md:hidden">Total Harga:</span>
                                    <span id="item-subtotal-{{ $pid }}" class="text-xs sm:text-sm font-extrabold text-[#0B6E4F]">
                                        Rp {{ number_format($lineTotal, 0, ',', '.') }}
                                    </span>
                                </div>

                                <!-- Kolom 5: Tombol Hapus -->
                                <div class="md:col-span-1 flex items-center justify-end border-t md:border-t-0 pt-2 md:pt-0 border-slate-100">
                                    <button type="button"
                                            onclick="removeSingleCartItem({{ $pid }})"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Footer Voucher / Info Diskon Toko -->
                    <div class="px-4 sm:px-5 py-2.5 bg-[#F8FAFC] border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>Status Anda: <strong class="text-[#0B6E4F]">{{ $user->subtype_label }}</strong> — Voucher Bazar & metode pengiriman dipilih pada halaman transaksi berikutnya.</span>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Sticky Bottom Bar Checkout (Gaya Shopee) -->
            <div class="fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-[0_-4px_24px_rgba(0,0,0,0.07)]">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
                    <!-- Kiri: Pilih Semua & Hapus -->
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer text-xs sm:text-sm font-bold text-[#0F291E]">
                            <input type="checkbox" id="check-all-bottom" checked onchange="toggleSelectAllCart(this.checked)" class="w-4 h-4 rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F] cursor-pointer">
                            <span>Pilih Semua (<span id="selected-count-label">{{ $totalItemsCount }}</span>)</span>
                        </label>
                        <button type="button" onclick="deleteSelectedCartItems()" class="text-xs font-bold text-rose-600 hover:underline hidden sm:inline-block">
                            Hapus Produk Terpilih
                        </button>
                    </div>

                    <!-- Kanan: Total Harga & Tombol Checkout -->
                    <div class="flex items-center gap-4 sm:gap-6 ml-auto">
                        <div class="text-right">
                            <div class="flex items-baseline justify-end gap-1.5">
                                <span class="text-xs text-slate-500 font-semibold">Total (<span id="checkout-qty-count">{{ $totalItemsCount }}</span> produk):</span>
                                <span id="cart-selected-total" class="text-lg sm:text-xl font-black text-[#0B6E4F]">Rp 0</span>
                            </div>
                            <p id="cart-saved-info" class="text-[11px] font-bold text-amber-700 hidden">
                                Estimasi hemat diskon pembeli: <span id="cart-saved-amount">Rp 0</span>
                            </p>
                        </div>

                        <button type="submit"
                                id="btn-proceed-checkout"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] active:scale-95 text-white px-6 sm:px-8 py-3 text-xs sm:text-sm font-extrabold shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span>Checkout</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form Tersembunyi untuk Hapus Item Tunggal -->
        <form id="single-remove-form" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <!-- Form Tersembunyi untuk Hapus Item Terpilih -->
        <form id="bulk-remove-form" action="{{ route('buyer.cart.remove-selected') }}" method="POST" class="hidden">
            @csrf
            <div id="bulk-remove-inputs"></div>
        </form>
    @else
        <!-- Tampilan Jika Keranjang Kosong -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-10 sm:p-14 text-center max-w-lg mx-auto my-6 space-y-4 shadow-2xs">
            <div class="w-16 h-16 rounded-2xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center mx-auto">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Keranjang Belanja Anda Masih Kosong</h2>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Yuk, jelajahi berbagai karya kreatif buatan siswa & guru SMK Plus Al Hilal dan masukkan produk favoritmu ke keranjang.
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] px-6 py-3 text-xs sm:text-sm font-extrabold text-white shadow-sm transition">
                    <span>Jelajahi Katalog Karya</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    @endif
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function formatRupiah(num) {
        return 'Rp ' + Math.round(Number(num || 0)).toLocaleString('id-ID');
    }

    function recalculateCartSelection() {
        const rows = document.querySelectorAll('.cart-item-row');
        let selectedProductsCount = 0;
        let grossSubtotal = 0;
        let estimatedSavings = 0;

        rows.forEach(row => {
            const pid = row.getAttribute('data-product-id');
            const unitPrice = parseFloat(row.getAttribute('data-unit-price') || '0');
            const discPct = parseFloat(row.getAttribute('data-disc-pct') || '0');
            const checkbox = row.querySelector('.cart-item-checkbox');
            const qtyInput = document.getElementById('cart-qty-' + pid);
            const qty = qtyInput ? Math.max(1, parseInt(qtyInput.value || '1', 10)) : 1;

            const itemLineTotal = unitPrice * qty;
            const subtotalEl = document.getElementById('item-subtotal-' + pid);
            if (subtotalEl) {
                subtotalEl.textContent = formatRupiah(itemLineTotal);
            }

            if (checkbox && checkbox.checked) {
                selectedProductsCount++;
                grossSubtotal += itemLineTotal;
                if (discPct > 0) {
                    estimatedSavings += Math.round(itemLineTotal * (discPct / 100));
                }
            }
        });

        // Sinkronisasi checkbox per toko
        document.querySelectorAll('.store-group-checkbox').forEach(storeCb => {
            const storeId = storeCb.getAttribute('data-store-id');
            const storeItemCbs = document.querySelectorAll(`.cart-item-checkbox[data-store-id="${storeId}"]`);
            const allStoreChecked = Array.from(storeItemCbs).every(cb => cb.checked);
            storeCb.checked = storeItemCbs.length > 0 && allStoreChecked;
        });

        // Sinkronisasi checkbox Pilih Semua
        const allItemCbs = document.querySelectorAll('.cart-item-checkbox');
        const allChecked = allItemCbs.length > 0 && Array.from(allItemCbs).every(cb => cb.checked);
        const topAll = document.getElementById('check-all-top');
        const bottomAll = document.getElementById('check-all-bottom');
        if (topAll) topAll.checked = allChecked;
        if (bottomAll) bottomAll.checked = allChecked;

        // Update UI Bar Bawah
        const countLabel = document.getElementById('selected-count-label');
        const checkoutCount = document.getElementById('checkout-qty-count');
        const totalEl = document.getElementById('cart-selected-total');
        const savedInfo = document.getElementById('cart-saved-info');
        const savedAmount = document.getElementById('cart-saved-amount');
        const checkoutBtn = document.getElementById('btn-proceed-checkout');

        if (countLabel) countLabel.textContent = selectedProductsCount;
        if (checkoutCount) checkoutCount.textContent = selectedProductsCount;
        if (totalEl) totalEl.textContent = formatRupiah(Math.max(0, grossSubtotal - estimatedSavings));

        if (savedInfo && savedAmount) {
            if (estimatedSavings > 0) {
                savedAmount.textContent = formatRupiah(estimatedSavings);
                savedInfo.classList.remove('hidden');
            } else {
                savedInfo.classList.add('hidden');
            }
        }

        if (checkoutBtn) {
            checkoutBtn.disabled = selectedProductsCount === 0;
        }
    }

    function toggleSelectAllCart(checked) {
        document.querySelectorAll('.cart-item-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        document.querySelectorAll('.store-group-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        recalculateCartSelection();
    }

    function toggleStoreItems(storeId, checked) {
        document.querySelectorAll(`.cart-item-checkbox[data-store-id="${storeId}"]`).forEach(cb => {
            cb.checked = checked;
        });
        recalculateCartSelection();
    }

    function onItemCheckboxChange() {
        recalculateCartSelection();
    }

    function changeCartItemQty(pid, delta) {
        const input = document.getElementById('cart-qty-' + pid);
        if (!input) return;
        const max = parseInt(input.getAttribute('max') || '99', 10);
        let next = parseInt(input.value || '1', 10) + delta;
        if (next < 1) next = 1;
        if (next > max) next = max;
        input.value = next;
        syncCartQtyToServer(pid, next);
        recalculateCartSelection();
    }

    function onCartQtyInputChange(pid, val) {
        const input = document.getElementById('cart-qty-' + pid);
        if (!input) return;
        const max = parseInt(input.getAttribute('max') || '99', 10);
        let next = parseInt(val || '1', 10);
        if (isNaN(next) || next < 1) next = 1;
        if (next > max) next = max;
        input.value = next;
        syncCartQtyToServer(pid, next);
        recalculateCartSelection();
    }

    function syncCartQtyToServer(pid, qty) {
        fetch(`/pembeli/keranjang/${pid}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ quantity: qty })
        }).catch(() => {});
    }

    function removeSingleCartItem(pid) {
        const form = document.getElementById('single-remove-form');
        if (!form) return;
        form.action = `/pembeli/keranjang/${pid}`;
        form.submit();
    }

    function deleteSelectedCartItems() {
        const checked = Array.from(document.querySelectorAll('.cart-item-checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0) {
            alert('Pilih minimal 1 produk yang ingin dihapus.');
            return;
        }
        const container = document.getElementById('bulk-remove-inputs');
        const form = document.getElementById('bulk-remove-form');
        if (!container || !form) return;
        container.innerHTML = '';
        checked.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'selected_items[]';
            inp.value = id;
            container.appendChild(inp);
        });
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        recalculateCartSelection();
    });
</script>
@endsection
