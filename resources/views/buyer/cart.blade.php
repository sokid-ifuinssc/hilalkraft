{{--
============================================================================
VIEW: KERANJANG & CHECKOUT DENGAN SKEMA DISKON JENIS PEMBELI & BAZAR SMK
      (buyer/cart.blade.php)
============================================================================
Fungsi:
  Menampilkan daftar item di keranjang session, menghitung diskon otomatis
  berdasarkan Jenis Pembeli (Pelajar / Keluarga SMK / Alumni / Umum),
  menyediakan opsi Diskon Acara Bazar SMK (hadir langsung di bazar atau
  kode voucher bazar), serta membuat pesanan dengan skema progres 4 tahap.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Keranjang Belanja & Promo Bazar — hilalkraf SMK PAL')

@section('content')
@php
    $user = auth()->user();
    $buyerSubtype = $user->user_subtype ?? 'umum';
    $grandSubtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']);
    $firstActiveBazaar = isset($activeBazaarEvents) ? $activeBazaarEvents->first() : null;
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0F291E]">Keranjang Belanja Anda</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Status Pembeli: <strong class="text-[#0B6E4F]">{{ $user->subtype_label }}</strong> • Otomatis memperoleh diskon jenis pembeli & diskon bazar SMK.
            </p>
        </div>
        <a href="{{ route('buyer.dashboard') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-[#0F291E] hover:border-[#0B6E4F] transition">
            🚚 Lihat Progres Pesanan Saya →
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-7 space-y-4">
            @forelse($cart as $id => $item)
                @php
                    $itemStore = \App\Models\Store::find($item['store_id'] ?? null);
                    $buyerDiscPct = $itemStore ? $itemStore->getDiscountPercentForBuyer($buyerSubtype) : 0;
                    $bazarExtraPct = $itemStore ? (int) ($itemStore->discount_bazar_extra ?? 0) : 0;
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 flex items-center justify-between gap-4 shadow-sm">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-extrabold text-[#0B6E4F]">🏪 {{ $item['store_name'] }}</span>
                            @if($buyerDiscPct > 0)
                                <span class="rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                                    🏷️ Diskon {{ $user->subtype_label }} {{ $buyerDiscPct }}%
                                </span>
                            @endif
                            @if($bazarExtraPct > 0)
                                <span class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800">
                                    🎪 +{{ $bazarExtraPct }}% Ekstra Toko saat Bazar
                                </span>
                            @endif
                        </div>
                        <h3 class="font-extrabold text-[#0F291E] text-sm">{{ $item['product_name'] }}</h3>
                        <p class="text-xs text-slate-500">
                            {{ $item['quantity'] }} × Rp {{ number_format($item['price'], 0, ',', '.') }}
                            = <strong class="text-[#0F291E]">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</strong>
                        </p>
                    </div>
                    <form action="{{ route('buyer.cart.remove', $id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-3">
                    <p class="text-sm font-bold text-[#0F291E]">Keranjang Anda masih kosong.</p>
                    <a href="{{ route('home') }}" class="inline-block rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white">
                        Jelajahi Katalog Karya →
                    </a>
                </div>
            @endforelse
        </div>

        <div class="lg:col-span-5">
            @php
                $cartProductIds = array_keys($cart);
                $cartProducts = \App\Models\Product::with('store')->findMany($cartProductIds);
                $canCartPickup = $cartProducts->every(fn($p) => $p->canPickup());
                $canCartDelivery = $cartProducts->every(fn($p) => $p->canDeliveryLokal());
                $canCartTransfer = $cartProducts->every(fn($p) => $p->canShippingTransfer());
                $defaultCartMethod = $canCartPickup ? 'pickup' : ($canCartDelivery ? 'delivery_lokal' : 'shipping_transfer');
                $cartStores = $cartProducts->pluck('store')->unique('id')->filter();
            @endphp
            <form action="{{ route('buyer.checkout') }}" method="POST" id="checkout-form" class="bg-white rounded-2xl border border-slate-200/80 p-6 space-y-5 shadow-sm">
                @csrf
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-extrabold text-[#0F291E]">Metode Transaksi & Pengiriman</h2>
                    <span class="text-[10px] text-slate-500">Sesuai ketentuan produk</span>
                </div>

                <!-- 3 Pilihan Skema Transaksi Sesuai Ketentuan -->
                <div class="space-y-3">
                    <!-- Opsi 1: Datang ke Sekolah -->
                    <label class="delivery-option block rounded-2xl border-2 p-3.5 transition {{ $canCartPickup ? 'cursor-pointer border-[#0B6E4F] bg-[#EBF9F2]/30' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="pickup">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="pickup" @checked($defaultCartMethod === 'pickup') @disabled(!$canCartPickup) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateDeliveryUI('pickup')">
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">🏫 1. Datang ke Sekolah (Transaksi Langsung)</span>
                                    <span class="text-[11px] font-extrabold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">Rp 0 (Gratis)</span>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-snug">
                                    Transaksi & serah terima barang langsung di lingkungan SMK Plus Al Hilal / Stand Bazar.
                                </p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md mt-0.5">
                                        <span>Alur 3 Tahap:</span>
                                        <span class="text-[#0B6E4F]">1. Dipesan</span> →
                                        <span class="text-[#0B6E4F]">2. Dikemas</span> →
                                        <span class="text-[#0B6E4F]">3. Diterima / Diambil</span>
                                    </div>
                                    @if(!$canCartPickup)
                                        <span class="text-rose-600 font-bold">(Ada produk yang tidak melayani Datang Langsung)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Opsi 2: Delivery / Antar Langsung (Khusus Arjawinangun, Susukan, Kaliwedi) -->
                    <label class="delivery-option block rounded-2xl border-2 p-3.5 transition {{ $canCartDelivery ? 'cursor-pointer border-slate-200 hover:border-slate-300 bg-white' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="delivery_lokal">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="delivery_lokal" @checked($defaultCartMethod === 'delivery_lokal') @disabled(!$canCartDelivery) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateDeliveryUI('delivery_lokal')">
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">🛵 2. Delivery / Antar Langsung</span>
                                    <span class="text-[11px] font-extrabold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">Rp 5.000</span>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-snug">
                                    Pengantaran langsung oleh kurir sekolah khusus wilayah <strong class="text-slate-900">Kec. Arjawinangun, Susukan, & Kaliwedi</strong> (Kab. Cirebon).
                                </p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md mt-0.5">
                                        <span>Alur 4 Tahap:</span>
                                        <span class="text-blue-700">1. Dipesan</span> →
                                        <span class="text-blue-700">2. Dikemas</span> →
                                        <span class="text-blue-700">3. Dikirim</span> →
                                        <span class="text-blue-700">4. Diterima</span>
                                    </div>
                                    @if(!$canCartDelivery)
                                        <span class="text-rose-600 font-bold">(Ada produk yang hanya melayani Datang Langsung)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Opsi 3: Transfer Antar (Ekspedisi Luar Wilayah / Luar Kota) -->
                    <label class="delivery-option block rounded-2xl border-2 p-3.5 transition {{ $canCartTransfer ? 'cursor-pointer border-slate-200 hover:border-slate-300 bg-white' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="shipping_transfer">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="shipping_transfer" @checked($defaultCartMethod === 'shipping_transfer') @disabled(!$canCartTransfer) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateDeliveryUI('shipping_transfer')">
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">📦 3. Transfer Antar (Luar Wilayah / Ekspedisi)</span>
                                    <span id="transfer-badge-cost" class="text-[11px] font-extrabold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full">Ongkir Kurir Langsung</span>
                                </div>
                                <p class="text-[11px] text-amber-950 font-medium leading-snug">
                                    Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir. Pembeli hanya mentransfer biaya karya ke rekening / e-wallet toko.
                                </p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md mt-0.5">
                                        <span>Alur 4 Tahap:</span>
                                        <span class="text-amber-800">1. Dipesan</span> →
                                        <span class="text-amber-800">2. Dikemas</span> →
                                        <span class="text-amber-800">3. Dikirim</span> →
                                        <span class="text-amber-800">4. Diterima</span>
                                    </div>
                                    @if(!$canCartTransfer)
                                        <span class="text-rose-600 font-bold">(Ada produk yang hanya melayani Datang Langsung)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Kotak Rekening & E-Wallet Toko (Ditampilkan saat Transfer Antar dipilih) -->
                <div id="cart-transfer-bank-box" class="hidden rounded-2xl bg-amber-50/80 border border-amber-200 p-4 space-y-3 text-xs text-amber-950">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-amber-900 flex items-center gap-1.5">
                            <span>💳</span>
                            <span>Informasi Rekening & E-Wallet Pembayaran Toko</span>
                        </span>
                        <span class="text-[10px] text-amber-800 font-bold bg-amber-200/60 px-2 py-0.5 rounded-full">Transfer Antar</span>
                    </div>

                    @foreach($cartStores as $cStore)
                        <div class="bg-white rounded-xl p-3 border border-amber-200 space-y-2">
                            <span class="text-xs font-extrabold text-[#0F291E] block">🏪 Toko: {{ $cStore->store_name }}</span>
                            @if($cStore->has_bank_account)
                                <div class="flex items-center justify-between text-slate-700 bg-slate-50 rounded-lg p-2">
                                    <div>
                                        <span class="text-[10px] text-slate-400 block font-bold uppercase">Bank {{ $cStore->bank_name }}</span>
                                        <span class="font-mono font-bold text-xs text-[#0F291E]">{{ $cStore->bank_account_number }}</span>
                                        <span class="text-[10px] text-slate-500 block">a.n. {{ $cStore->bank_account_name }}</span>
                                    </div>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $cStore->bank_account_number }}'); alert('Nomor Rekening disalin!');" class="rounded bg-amber-100 hover:bg-amber-200 px-2 py-1 text-[10px] font-bold text-amber-900 transition">
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
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $cStore->ewallet_account_number }}'); alert('Nomor E-Wallet disalin!');" class="rounded bg-amber-100 hover:bg-amber-200 px-2 py-1 text-[10px] font-bold text-amber-900 transition">
                                        Salin
                                    </button>
                                </div>
                            @endif
                            @if(!$cStore->has_payment_account)
                                <p class="text-[10px] text-slate-500 italic">
                                    Nomor rekening toko akan diberikan melalui konfirmasi WhatsApp penjual.
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Formulir Alamat & Lokasi Tujuan -->
                <div id="address-container" class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-[#0F291E]" id="address-section-title">📍 Lokasi Penyerahan / Tujuan</span>
                        <span class="text-[10px] text-slate-500" id="address-hint">Sekolah SMK Plus Al Hilal</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2" id="geo-district-container">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-0.5">Kabupaten / Kota</label>
                            <input type="text" name="destination_kabupaten" id="dest-kabupaten" value="{{ old('destination_kabupaten', $user->kabupaten ?: 'Cirebon') }}" placeholder="Cirebon" oninput="calculateLiveOngkir()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-0.5">Kecamatan</label>
                            <input type="text" name="destination_kecamatan" id="dest-kecamatan" value="{{ old('destination_kecamatan', $user->kecamatan ?: 'Arjawinangun') }}" placeholder="Arjawinangun / Susukan / Kaliwedi" oninput="calculateLiveOngkir()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
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
                        ⚠️ <strong>Perhatian:</strong> Delivery Antar Langsung hanya melayani 3 kecamatan di Kab. Cirebon: <strong>Arjawinangun, Susukan, dan Kaliwedi</strong>. Untuk kecamatan lain, silakan pilih <em>Transfer Antar</em> atau <em>Datang ke Sekolah</em>.
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-0.5" id="lbl-shipping-address">Alamat Detail / Ruang Penyerahan *</label>
                        <input type="text" name="shipping_address" id="input-shipping-address" value="{{ old('shipping_address', $user->full_address !== 'Wilayah belum dilengkapi' ? $user->full_address : 'Stand Bazar / Kampus SMK Plus Al Hilal') }}" required placeholder="Contoh: Stand Bazar SMK Plus Al Hilal / Ruang Kelas XI TKJ / Jl. Nyi Mas Gandasari No..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                </div>

                <!-- Skema Diskon Bazar / Event SMK -->
                <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-amber-900">🎪 Skema Promo Bazar & Event SMK</span>
                        @if($firstActiveBazaar)
                            <span class="rounded-full bg-amber-200/80 px-2 py-0.5 text-[10px] font-extrabold text-amber-900">
                                Diskon Event {{ $firstActiveBazaar->discount_percent }}%
                            </span>
                        @endif
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="is_bazaar_offline" value="1" class="mt-0.5 rounded border-amber-400 text-[#0B6E4F] focus:ring-[#0B6E4F]">
                        <span class="text-xs text-amber-950 leading-snug">
                            <strong>Saya membeli & hadir langsung pada Acara Bazar SMK</strong>
                            <span class="block text-[11px] text-amber-800/90">Otomatis mendapatkan potongan Diskon Acara Bazar SMK & Diskon Ekstra Stand Toko!</span>
                        </span>
                    </label>

                    <div>
                        <label class="block text-[11px] font-bold text-amber-900 mb-1">Kode Voucher Bazar / Event SMK (Opsional)</label>
                        <div class="flex gap-2">
                            <input type="text" id="bazaar-code-input" name="bazaar_voucher_code" value="{{ old('bazaar_voucher_code', $firstActiveBazaar?->voucher_code) }}" placeholder="Contoh: BAZARSMKPAL" class="flex-1 rounded-xl border border-amber-300 bg-white px-3 py-1.5 text-xs font-mono font-bold uppercase text-amber-900 focus:outline-none">
                            @if($firstActiveBazaar)
                                <button type="button" onclick="document.getElementById('bazaar-code-input').value='{{ $firstActiveBazaar->voucher_code }}'" class="rounded-xl bg-amber-500/20 border border-amber-400 px-3 py-1.5 text-[11px] font-extrabold text-amber-900 hover:bg-amber-500/30 transition">
                                    Pakai {{ $firstActiveBazaar->voucher_code }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1">Catatan Tambahan untuk Penjual</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Titipkan di pos satpam / hubungi WA saat berangkat..." class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Subtotal Produk</span>
                        <span class="font-bold text-[#0F291E]">Rp {{ number_format($grandSubtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span id="ongkir-label-text">Ongkos Kirim (Datang ke Sekolah)</span>
                        <span class="font-bold text-[#0B6E4F]" id="ongkir-display-text">Rp 0 (Gratis)</span>
                    </div>
                    <div class="flex items-center justify-between text-sm font-extrabold text-[#0F291E] pt-1 border-t border-dashed border-slate-200">
                        <span>Total Pembayaran</span>
                        <span class="text-base text-[#0B6E4F]" id="grand-total-text">Rp {{ number_format($grandSubtotal, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-semibold pt-1">
                        ✓ Diskon Jenis Pembeli ({{ $user->subtype_label }}) & Diskon Bazar SMK otomatis dihitung saat konfirmasi.
                    </p>
                </div>

                <button type="submit" id="btn-submit-order" @disabled(empty($cart)) class="w-full rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-bold text-white hover:bg-[#08583F] transition shadow-sm disabled:opacity-50">
                    ✓ Konfirmasi Pesanan & Pantau Progres
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const subtotalBase = {{ (float) $grandSubtotal }};
    const validLocalDistricts = ['arjawinangun', 'susukan', 'kaliwedi'];

    function formatRupiah(amount) {
        return 'Rp ' + Number(amount).toLocaleString('id-ID');
    }

    function getSelectedDeliveryMethod() {
        const radios = document.getElementsByName('delivery_method');
        for (let r of radios) {
            if (r.checked) return r.value;
        }
        return 'pickup';
    }

    function setKecamatan(name) {
        const kecInput = document.getElementById('dest-kecamatan');
        const kabInput = document.getElementById('dest-kabupaten');
        if (kecInput) kecInput.value = name;
        if (kabInput && !kabInput.value) kabInput.value = 'Cirebon';
        calculateLiveOngkir();
    }

    function updateDeliveryUI(method) {
        // Highlight active card
        document.querySelectorAll('.delivery-option').forEach(el => {
            if (el.getAttribute('data-method') === method) {
                el.classList.add('border-[#0B6E4F]', 'bg-[#EBF9F2]/30');
                el.classList.remove('border-slate-200', 'bg-white');
            } else {
                el.classList.remove('border-[#0B6E4F]', 'bg-[#EBF9F2]/30');
                el.classList.add('border-slate-200', 'bg-white');
            }
        });

        const addressTitle = document.getElementById('address-section-title');
        const addressHint = document.getElementById('address-hint');
        const addressLabel = document.getElementById('lbl-shipping-address');
        const addressInput = document.getElementById('input-shipping-address');
        const shortcuts = document.getElementById('local-district-shortcuts');
        const warning = document.getElementById('local-delivery-warning');
        const submitBtn = document.getElementById('btn-submit-order');
        const bankBox = document.getElementById('cart-transfer-bank-box');

        if (method === 'pickup') {
            addressTitle.textContent = '📍 Lokasi Pengambilan di Sekolah';
            addressHint.textContent = 'Kampus SMK Plus Al Hilal / Bazar';
            addressLabel.textContent = 'Stand / Ruang Penyerahan di Sekolah *';
            if (addressInput && (!addressInput.value || addressInput.value.includes('Jl.'))) {
                addressInput.value = 'Stand Bazar / Kampus SMK Plus Al Hilal';
            }
            if (shortcuts) shortcuts.classList.add('hidden');
            if (warning) warning.classList.add('hidden');
            if (bankBox) bankBox.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        } else if (method === 'delivery_lokal') {
            addressTitle.textContent = '📍 Alamat Delivery (Khusus Cirebon Barat)';
            addressHint.textContent = 'Kec. Arjawinangun, Susukan, Kaliwedi';
            addressLabel.textContent = 'Alamat Lengkap Pengantaran *';
            if (shortcuts) shortcuts.classList.remove('hidden');
            if (bankBox) bankBox.classList.add('hidden');
        } else {
            // shipping_transfer
            addressTitle.textContent = '📍 Alamat Ekspedisi (Transfer Antar)';
            addressHint.textContent = 'Seluruh Indonesia';
            addressLabel.textContent = 'Alamat Lengkap Pengiriman Ekspedisi *';
            if (shortcuts) shortcuts.classList.add('hidden');
            if (warning) warning.classList.add('hidden');
            if (bankBox) bankBox.classList.remove('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }

        calculateLiveOngkir();
    }

    function calculateLiveOngkir() {
        const method = getSelectedDeliveryMethod();
        const kabInput = document.getElementById('dest-kabupaten');
        const kecInput = document.getElementById('dest-kecamatan');
        const warning = document.getElementById('local-delivery-warning');
        const submitBtn = document.getElementById('btn-submit-order');
        const ongkirLabel = document.getElementById('ongkir-label-text');
        const ongkirDisplay = document.getElementById('ongkir-display-text');
        const grandTotalDisplay = document.getElementById('grand-total-text');

        let ongkir = 0;

        if (method === 'pickup') {
            ongkir = 0;
            ongkirLabel.textContent = 'Ongkos Kirim (Datang ke Sekolah)';
            ongkirDisplay.textContent = 'Rp 0 (Gratis)';
            ongkirDisplay.className = 'font-bold text-[#0B6E4F]';
        } else if (method === 'delivery_lokal') {
            ongkir = 5000;
            ongkirLabel.textContent = 'Ongkos Kirim (Delivery Lokal)';
            ongkirDisplay.textContent = formatRupiah(ongkir);
            ongkirDisplay.className = 'font-bold text-blue-700';

            const cleanKec = (kecInput ? kecInput.value : '').toLowerCase().trim();
            const isMatch = validLocalDistricts.some(d => cleanKec.includes(d));

            if (!isMatch && cleanKec.length > 0) {
                warning.classList.remove('hidden');
                if (submitBtn) submitBtn.disabled = true;
            } else {
                warning.classList.add('hidden');
                if (submitBtn) submitBtn.disabled = false;
            }
        } else {
            // shipping_transfer (Luar Wilayah)
            ongkir = 0; // Biaya ongkir tidak ditambahkan ke tagihan toko
            ongkirLabel.textContent = 'Ongkos Kirim (Transfer Antar / Luar Wilayah)';
            ongkirDisplay.innerHTML = '<span class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md inline-block text-right">Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir.</span>';
            ongkirDisplay.className = '';
            const badgeCost = document.getElementById('transfer-badge-cost');
            if (badgeCost) badgeCost.textContent = 'Ongkir Langsung ke Kurir';
            if (warning) warning.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }

        const grandTotal = Math.max(0, subtotalBase + ongkir);
        grandTotalDisplay.textContent = formatRupiah(grandTotal);
    }

    // Inisialisasi awal saat halaman dibuka
    document.addEventListener('DOMContentLoaded', function() {
        updateDeliveryUI('pickup');
    });
</script>
@endsection
