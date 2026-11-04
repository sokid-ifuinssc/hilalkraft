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
            <form action="{{ route('buyer.checkout') }}" method="POST" class="bg-white rounded-2xl border border-slate-200/80 p-6 space-y-4 shadow-sm">
                @csrf
                <h2 class="text-base font-extrabold text-[#0F291E]">Ringkasan Checkout & Skema Diskon</h2>

                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1">Alamat Pengiriman / Lokasi Pengambilan (Stand Bazar / Kelas) *</label>
                    <input type="text" name="shipping_address" value="{{ old('shipping_address', $user->full_address !== 'Wilayah belum dilengkapi' ? $user->full_address : 'Stand Bazar SMK Plus Al Hilal') }}" required placeholder="Contoh: Stand Bazar SMK Plus Al Hilal / Ruang Tamu Sekolah" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs font-medium focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
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
                    <textarea name="notes" rows="2" placeholder="Contoh: Ambil langsung di stand bazar pukul 10.00..." class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Subtotal Keranjang</span>
                        <span class="font-bold text-[#0F291E]">Rp {{ number_format($grandSubtotal, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-[11px] text-[#0B6E4F] font-semibold">
                        ✓ Diskon Jenis Pembeli ({{ $user->subtype_label }}) & Diskon Bazar SMK otomatis dihitung saat pesanan dibuat.
                    </p>
                </div>

                <button type="submit" @disabled(empty($cart)) class="w-full rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-bold text-white hover:bg-[#08583F] transition shadow-sm disabled:opacity-50">
                    ✓ Buat Pesanan & Pantau Progres Barang
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
