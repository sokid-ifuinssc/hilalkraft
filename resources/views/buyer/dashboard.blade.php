{{--
============================================================================
VIEW: DASHBOARD PROGRES PESANAN & RATING BINTANG PEMBELI (buyer/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman Pembeli yang memuat:
  1. Progres Barang 4 Tahap meskipun komunikasi pembayaran langsung via WhatsApp penjual:
     - Tahap 1: Pesan (Dipesan)
     - Tahap 2: Kemas (Sedang Dikemas Penjual)
     - Tahap 3: Kirim (Dalam Perjalanan / Siap COD)
     - Tahap 4: Sampai (Pesanan Diterima Pembeli)
  2. Rincian Diskon Jenis Pembeli (Pelajar / Keluarga SMK / Alumni / Umum) & Diskon Bazar SMK.
  3. Fitur Klik Bintang (Rating ★ 1 s/d 5) & Ulasan oleh Pembeli setelah transaksi.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Pesanan Saya & Progres Barang — hilalkraf SMK PAL')

@section('content')
@php
    $user = auth()->user();
    $stagesMeta = [
        1 => ['key' => 'pesan',  'icon' => '📝', 'label' => '1. Pesan',  'desc' => 'Pesanan tercatat & terhubung WA'],
        2 => ['key' => 'kemas',  'icon' => '📦', 'label' => '2. Kemas',  'desc' => 'Karya sedang dikemas penjual'],
        3 => ['key' => 'kirim',  'icon' => '🚚', 'label' => '3. Kirim',  'desc' => 'Dalam perjalanan / siap COD'],
        4 => ['key' => 'sampai', 'icon' => '✅', 'label' => '4. Sampai', 'desc' => 'Barang diterima pembeli'],
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Header & Identitas Jenis Pembeli -->
    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 flex flex-wrap items-center justify-between gap-4 shadow-sm">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-extrabold text-[#0B6E4F]">
                    🏷️ Status Pembeli: {{ $user->subtype_label }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-[11px] font-bold text-amber-800">
                    ★ Berhak mendapat Diskon Jenis Pembeli & Promo Bazar SMK
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-[#0F291E]">Progres Pesanan & Penilaian Karya</h1>
            <p class="text-xs text-slate-500">
                Pantau progres barang Anda dari <strong>Pesan → Kemas → Kirim (Dalam Perjalanan) → Sampai</strong>, hubungi penjual via WhatsApp, dan berikan <strong>Rating Bintang ★</strong>.
            </p>
        </div>
        <a href="{{ route('home') }}" class="rounded-xl bg-[#0B6E4F] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition">
            + Belanja Karya Lainnya
        </a>
    </div>

    <!-- Info Acara Bazar SMK yang Sedang Aktif -->
    @if(isset($activeBazaarEvents) && $activeBazaarEvents->count() > 0)
        <div class="rounded-2xl bg-gradient-to-r from-[#0F291E] via-[#0B6E4F] to-emerald-700 text-white p-5 flex flex-wrap items-center justify-between gap-4 shadow-sm">
            <div class="space-y-1">
                <span class="inline-block rounded-full bg-amber-400 text-slate-950 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider">
                    🎪 Promo Bazar & Event SMK Plus Al Hilal
                </span>
                @foreach($activeBazaarEvents as $ev)
                    <p class="text-sm font-extrabold">
                        {{ $ev->title }} — Diskon Event {{ $ev->discount_percent }}% ({{ $ev->location ?? 'SMK Plus Al Hilal' }})
                    </p>
                    <p class="text-xs text-emerald-100">
                        Gunakan Kode Voucher <span class="font-mono font-extrabold bg-white/20 px-2 py-0.5 rounded text-white">{{ $ev->voucher_code }}</span> atau centang <strong>"Beli & Hadir di Acara Bazar SMK"</strong> saat memesan produk!
                    </p>
                @endforeach
            </div>
            <a href="{{ route('buyer.cart.index') }}" class="rounded-xl bg-white text-[#0F291E] px-4 py-2 text-xs font-extrabold hover:bg-emerald-50 transition">
                Lihat Keranjang →
            </a>
        </div>
    @endif

    <!-- Daftar Pesanan & Progres 4 Tahap -->
    <div class="space-y-5">
        @forelse($orders as $order)
            @php
                $currentStep = $order->progress_step;
                $effProgress = $order->effective_progress;
                $waInvoice = urlencode("Assalamu'alaikum {$order->store->store_name}, saya {$user->name} ingin konfirmasi pesanan *#{$order->order_code}* (Progres: {$order->progress_label}) senilai *{$order->formatted_total_price}* di hilalkraf SMK Plus Al Hilal.");
            @endphp

            <div class="rounded-2xl bg-white border border-slate-200/90 p-6 space-y-5 shadow-sm">
                <!-- Baris Atas: Kode Pesanan, Nama Toko, & Tombol WA Penjual -->
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-extrabold text-[#0F291E]">#{{ $order->order_code }}</span>
                            <a href="{{ route('stores.show', $order->store->slug) }}" class="rounded-full bg-[#EBF9F2] px-3 py-0.5 text-xs font-extrabold text-[#0B6E4F] hover:underline">
                                🏪 {{ $order->store->store_name }}
                            </a>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-extrabold {{ $effProgress === 'sampai' ? 'bg-emerald-100 text-emerald-800' : ($effProgress === 'dibatalkan' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800') }}">
                                {{ $order->progress_label }}
                            </span>
                            @if($order->is_bazaar_offline || $order->bazaar_voucher_code)
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-900">
                                    🎪 Diskon Bazar SMK ({{ $order->bazaar_voucher_code ?? 'Hadir di Bazar' }})
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500">
                            📍 Tujuan/Pengambilan: {{ $order->shipping_address }} • 🕒 Dipesan {{ $order->created_at?->format('d M Y, H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($effProgress !== 'sampai' && $effProgress !== 'dibatalkan')
                            <form action="{{ route('buyer.orders.delivered', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-xl border border-emerald-600 bg-[#EBF9F2] px-3.5 py-2 text-xs font-extrabold text-[#0B6E4F] hover:bg-[#0B6E4F] hover:text-white transition">
                                    ✅ Pesanan Sudah Sampai
                                </button>
                            </form>
                        @endif

                        <a href="{{ $order->store->whatsapp_url }}?text={{ $waInvoice }}"
                           target="_blank"
                           rel="noopener"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                            <span>💬 Hubungi Penjual via WhatsApp</span>
                        </a>
                    </div>
                </div>

                <!-- PROGRES BARANG 4 TAHAP: PESAN -> KEMAS -> KIRIM (DALAM PERJALANAN) -> SAMPAI -->
                @if($effProgress !== 'dibatalkan')
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-extrabold text-[#0F291E] uppercase tracking-wider text-[11px]">Progres Pengiriman Barang</span>
                            @if($order->progress_note)
                                <span class="text-xs font-semibold text-[#0B6E4F]">📢 Info Toko: "{{ $order->progress_note }}"</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($stagesMeta as $stepNum => $meta)
                                @php
                                    $isDone = $currentStep >= $stepNum;
                                    $isCurrent = $currentStep === $stepNum;
                                @endphp
                                <div class="rounded-2xl p-3.5 border transition {{ $isDone ? 'bg-[#EBF9F2] border-[#0B6E4F]/40' : 'bg-slate-50 border-slate-200/70 opacity-70' }}">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="w-7 h-7 rounded-full text-xs font-extrabold flex items-center justify-center {{ $isDone ? 'bg-[#0B6E4F] text-white' : 'bg-slate-200 text-slate-500' }}">
                                            {{ $isDone ? '✓' : $stepNum }}
                                        </span>
                                        @if($isCurrent)
                                            <span class="rounded-full bg-[#0B6E4F] text-white px-2 py-0.5 text-[9px] font-extrabold uppercase">Aktif</span>
                                        @endif
                                    </div>
                                    <p class="text-xs font-extrabold text-[#0F291E]">{{ $meta['icon'] }} {{ $meta['label'] }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $meta['desc'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs font-bold text-rose-700">
                        ✕ Pesanan ini telah dibatalkan.
                    </div>
                @endif

                <!-- Rincian Produk & Diskon -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 bg-[#F8FAFC] rounded-2xl p-4 border border-slate-200/70">
                    <div class="lg:col-span-7 space-y-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Daftar Karya yang Dipesan</p>
                        @foreach($order->items as $item)
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-200/60 last:border-0">
                                <div class="flex items-center gap-2.5">
                                    <span class="font-bold text-[#0F291E]">{{ $item->product?->product_name ?? 'Karya Siswa' }}</span>
                                    <span class="text-slate-400">× {{ $item->quantity }}</span>
                                </div>
                                <span class="font-bold text-[#0F291E]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="lg:col-span-5 bg-white rounded-xl p-3.5 border border-slate-200/70 space-y-1.5 text-xs">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal Produk</span>
                            <span class="font-semibold">{{ $order->formatted_subtotal_price }}</span>
                        </div>
                        @if(($order->buyer_type_discount_amount ?? 0) > 0)
                            <div class="flex justify-between text-emerald-700 font-semibold">
                                <span>Diskon {{ $user->subtype_label }} ({{ $order->buyer_type_discount_percent }}%)</span>
                                <span>- Rp {{ number_format($order->buyer_type_discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if(($order->bazaar_discount_amount ?? 0) > 0)
                            <div class="flex justify-between text-amber-700 font-semibold">
                                <span>Diskon Bazar SMK ({{ $order->bazaar_discount_percent }}%)</span>
                                <span>- Rp {{ number_format($order->bazaar_discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm font-extrabold text-[#0F291E] pt-1.5 border-t border-slate-100">
                            <span>Total Akhir</span>
                            <span class="text-[#0B6E4F]">{{ $order->formatted_total_price }}</span>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- FITUR KLIK BINTANG (RATING ★ 1 S/D 5) OLEH PEMBELI SETELAH TRANSAKSI      -->
                <!-- ========================================================================= -->
                <div class="rounded-2xl {{ $order->buyer_rating ? 'bg-amber-50/70 border border-amber-200' : 'bg-[#EBF9F2]/60 border border-[#CDEFE0]' }} p-4 sm:p-5">
                    <form action="{{ route('buyer.orders.rate', $order) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-[#0F291E]">
                                    {{ $order->buyer_rating ? '★ Penilaian Bintang Anda untuk Transaksi Ini' : '★ Beri Rating Bintang untuk Toko & Karya Ini' }}
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Klik bintang (1 sampai 5) untuk memberikan apresiasi kualitas karya & pelayanan toko <strong>{{ $order->store->store_name }}</strong>.
                                </p>
                            </div>

                            <!-- Pilihan Bintang Interaktif (Klik ★ 1 s/d 5) -->
                            <div class="flex items-center gap-1" data-rating-group="{{ $order->id }}">
                                <input type="hidden" name="rating" id="rating-input-{{ $order->id }}" value="{{ $order->buyer_rating ?? 5 }}">
                                @for($star = 1; $star <= 5; $star++)
                                    @php $activeStar = ($order->buyer_rating ?? 5) >= $star; @endphp
                                    <button type="button"
                                            onclick="selectOrderStar({{ $order->id }}, {{ $star }})"
                                            class="star-btn-{{ $order->id }} text-2xl leading-none transition transform hover:scale-110 focus:outline-none {{ $activeStar ? 'text-amber-400' : 'text-slate-300' }}"
                                            data-star="{{ $star }}"
                                            title="Beri {{ $star }} Bintang">
                                        ★
                                    </button>
                                @endfor
                                <span id="rating-label-{{ $order->id }}" class="ml-2 text-xs font-extrabold text-amber-700 bg-white px-2.5 py-1 rounded-lg border border-amber-200">
                                    {{ $order->buyer_rating ?? 5 }} / 5
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <input type="text"
                                   name="review"
                                   value="{{ $order->buyer_review }}"
                                   placeholder="Tulis ulasan singkat Anda tentang karya siswa ini (opsional)..."
                                   class="flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                            <button type="submit" class="rounded-xl bg-[#0B6E4F] px-5 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shrink-0 shadow-sm">
                                {{ $order->buyer_rating ? '✓ Perbarui Rating Bintang' : '★ Kirim Rating Bintang' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-10 text-center space-y-3">
                <p class="text-base font-extrabold text-[#0F291E]">Belum Ada Riwayat Pesanan</p>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Silakan pilih karya siswa di katalog hilalkraf, klik <strong>Beli Sekarang & Hubungi Penjual</strong> atau masukkan ke keranjang untuk memantau progres barang dari Pesan, Kemas, Kirim, hingga Sampai.
                </p>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition">
                    Jelajahi Katalog Karya →
                </a>
            </div>
        @endforelse
    </div>
</div>

<script>
    function selectOrderStar(orderId, starValue) {
        const input = document.getElementById('rating-input-' + orderId);
        const label = document.getElementById('rating-label-' + orderId);
        if (input) input.value = starValue;
        if (label) label.textContent = starValue + ' / 5';

        document.querySelectorAll('.star-btn-' + orderId).forEach(btn => {
            const s = parseInt(btn.getAttribute('data-star') || '1', 10);
            if (s <= starValue) {
                btn.classList.remove('text-slate-300');
                btn.classList.add('text-amber-400');
            } else {
                btn.classList.remove('text-amber-400');
                btn.classList.add('text-slate-300');
            }
        });
    }
</script>
@endsection
