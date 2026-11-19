{{--
============================================================================
VIEW: DETAIL PRODUK, GALERI FOTO ASLI, KENALI PEMBUATNYA & ULASAN (catalog/show.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman detail produk dengan tata letak 2 kolom yang seimbang
  tanpa ruang kosong terbuang:
  - Kolom Kiri: Foto Utama + Galeri Foto Asli yang Diupload (tanpa foto produk lain)
    serta Kartu "Kenali Pembuatnya & Profil Toko" tepat di bawah foto.
  - Kolom Kanan: Informasi Produk, Harga, Skema Diskon, Pilihan Kuantitas,
    Tombol Beli/Pesan Sekarang (menuju Halaman Transaksi), Tambah ke Keranjang,
    serta Konsultasi WhatsApp.
  - Bagian Bawah: Tab Deskripsi Lengkap & Ulasan/Komentar, diikuti Karya Lainnya.
  Seluruh ikon menggunakan Flat SVG Icon bersih tanpa emoji.
============================================================================
--}}
@extends('layouts.app')

@php
    $uploadedUrls = $product->all_image_urls;
    $mainImg = $uploadedUrls[0] ?? asset('images/logo-hilalkraf.jpg');
    $fallbackImg = asset('images/logo-hilalkraf.jpg');
    $shortDesc = \Illuminate\Support\Str::limit(strip_tags($product->description ?? 'Karya inovatif siswa SMK Plus Al Hilal.'), 155);
@endphp

@section('title', $product->product_name . ' — hilalkraf')
@section('og_type', 'product')
@section('og_title', $product->product_name . ' — ' . $product->formatted_price . ' | hilalkraf')
@section('og_description', 'Beli ' . $product->product_name . ' (' . $product->formatted_price . ') di toko ' . ($product->store->store_name ?? 'SMK Plus Al Hilal') . '. ' . $shortDesc)
@section('og_image', $mainImg)
@section('og_image_banner', $mainImg)

@section('content')
@php
    $canPickup = $product->canPickup();
    $canDelivery = $product->canDeliveryLokal();
    $canTransfer = $product->canShippingTransfer();
    $defaultMethod = $canPickup ? 'pickup' : ($canDelivery ? 'delivery_lokal' : 'shipping_transfer');

    $productPageUrl = request()->fullUrl();
    $waConsultationMessage = urlencode("Assalamu'alaikum {$product->store->owner->name} ({$product->store->store_name}), saya ingin berkonsultasi seputar produk *{$product->product_name}* ({$product->formatted_price}) di Hilalkraf SMK Plus Al Hilal. Mohon infonya kak.\n\n(Catatan: Pemesanan resmi akan saya lakukan via aplikasi Hilalkraf agar aman & tercatat status transaksinya).\nLink: {$productPageUrl}");
    $waMessage = $waConsultationMessage;
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-28 lg:pb-12 space-y-8">

    <!-- Breadcrumb -->
    <nav class="text-xs text-slate-400 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-[#0B6E4F] transition">Home</a>
        <span>›</span>
        <a href="{{ route('home', ['category' => $product->category]) }}" class="hover:text-[#0B6E4F] transition">{{ $product->category }}</a>
        <span>›</span>
        <span class="text-slate-600 font-semibold truncate">{{ $product->product_name }}</span>
    </nav>

    <!-- Bagian Utama: 2 Kolom Seimbang (Kiri: Foto + Kenali Pembuatnya, Kanan: Detail & Aksi Transaksi) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- KOLOM KIRI (5 Kolom): Galeri Foto Produk Asli + Kartu Kenali Pembuatnya (Memaksimalkan Ruang Kosong) -->
        <div class="lg:col-span-5 space-y-5">

            <!-- Kotak Galeri Foto Produk -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-3.5 space-y-3 shadow-2xs">
                <div class="relative aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-100">
                    <img
                        id="main-product-image"
                        src="{{ $mainImg }}"
                        alt="{{ $product->product_name }}"
                        class="w-full h-full object-cover transition duration-300"
                        onerror="this.src='{{ $fallbackImg }}'"
                    >
                    @if(count($uploadedUrls) > 1)
                        <span class="absolute bottom-2.5 right-2.5 rounded-lg bg-slate-900/70 backdrop-blur-xs px-2.5 py-1 text-[10px] font-bold text-white">
                            {{ count($uploadedUrls) }} Foto Produk
                        </span>
                    @endif
                </div>

                {{-- Hanya tampilkan deretan thumbnail jika foto yang diupload lebih dari 1 foto --}}
                @if(count($uploadedUrls) > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach($uploadedUrls as $i => $tImg)
                            <button type="button"
                                    onclick="selectGalleryThumbnail(this, '{{ $tImg }}')"
                                    class="product-thumb-btn aspect-square rounded-xl overflow-hidden border-2 {{ $i === 0 ? 'border-[#0B6E4F] ring-2 ring-[#0B6E4F]/20' : 'border-slate-200 hover:border-slate-300 opacity-80 hover:opacity-100' }} bg-slate-100 transition">
                                <img src="{{ $tImg }}" alt="Foto {{ $i + 1 }}" class="w-full h-full object-cover" onerror="this.src='{{ $fallbackImg }}'">
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center justify-between text-[11px] text-slate-500 px-1 pt-0.5">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Foto asli karya terkurasi</span>
                    </span>
                    <span class="font-semibold text-[#0B6E4F]">Kode: HK-{{ str_pad((string) $product->id, 3, '0', STR_PAD_LEFT) }}</span>
                </div>
            </div>

            <!-- Kartu "Kenali Pembuatnya & Identitas Toko" (Ditempatkan langsung di bawah foto agar tidak ada ruang kosong) -->
            <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs sm:text-sm font-extrabold text-[#0F291E] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Kenali Pembuat Karya</span>
                    </h3>
                    <a href="{{ route('stores.show', $product->store->slug) }}" class="text-[11px] font-bold text-[#0B6E4F] hover:underline">
                        Kunjungi Toko →
                    </a>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-[#EBF9F2] border border-[#CDEFE0] text-[#0B6E4F] font-extrabold text-sm flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($product->store->owner->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-extrabold text-[#0F291E] truncate">{{ $product->store->owner->name }}</h4>
                        <p class="text-xs text-slate-500 truncate">{{ $product->store->store_name }} • {{ $product->store->student_class ?: 'SMK Plus Al Hilal' }}</p>
                        <p class="text-[11px] font-bold text-[#0B6E4F] mt-0.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Identitas sivitas terverifikasi</span>
                        </p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 italic leading-relaxed bg-[#F8FAFC] rounded-xl p-3 border border-slate-100">
                    "{{ $product->store->description ?: 'Karya wirausaha muda SMK Plus Al Hilal yang dibuat dengan ketelitian dan semangat berkarya.' }}"
                </p>

                <div class="grid grid-cols-3 gap-2 text-center bg-slate-50/80 rounded-xl p-2.5 border border-slate-100 text-xs">
                    <div>
                        <p class="font-extrabold text-[#0F291E]">± 15 mnt</p>
                        <p class="text-[10px] text-slate-400">Respon Chat</p>
                    </div>
                    <div class="border-x border-slate-200/70">
                        <p class="font-extrabold text-[#0B6E4F]">{{ number_format($product->average_rating, 1, ',', '.') }} / 5</p>
                        <p class="text-[10px] text-slate-400">Penilaian</p>
                    </div>
                    <div>
                        <p class="font-extrabold text-[#0F291E]">{{ $product->stock }} unit</p>
                        <p class="text-[10px] text-slate-400">Stok Karya</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('stores.show', $product->store->slug) }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:border-[#0B6E4F] py-2.5 px-3 text-xs font-bold text-[#0F291E] transition">
                        <svg class="w-3.5 h-3.5 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Lihat Profil Toko</span>
                    </a>
                    @php
                        $inAppChatUrl = route('chat.start.store', ['store' => $product->store, 'product_id' => $product->id]);
                        $isOwnStore = auth()->check() && (int) auth()->id() === (int) $product->store->user_id;
                    @endphp
                    <a href="{{ auth()->check() ? ($isOwnStore ? route('chat.index') : $inAppChatUrl) : route('login', ['redirect' => $inAppChatUrl]) }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] py-2.5 px-3 text-xs font-bold text-white transition"
                       title="{{ $isOwnStore ? 'Buka kotak masuk chat toko Anda' : 'Chat langsung dengan penjual di dalam aplikasi' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>{{ $isOwnStore ? 'Kotak Masuk Chat' : 'Chat Penjual' }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (7 Kolom): Detail Produk, Skema Diskon & Tombol Transaksi -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-7 space-y-5 shadow-2xs">

            <!-- Baris Atas: Badge Kategori & Statistik Interaksi -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-bold text-[#0B6E4F]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span>{{ $product->category }} • Karya Kreatif</span>
                </span>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" title="Jumlah dilihat">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>{{ number_format($product->views_count ?? 0) }}</span>
                    </span>

                    <button type="button" onclick="switchProductTab('ulasan'); document.getElementById('section-tabs').scrollIntoView({behavior:'smooth'});" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition" title="Lihat komentar">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <span>{{ $product->comments->count() }}</span>
                    </button>

                    <form action="{{ route('products.like', $product->slug) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition" title="Sukai karya ini">
                            <svg class="w-3.5 h-3.5 fill-rose-500 text-rose-500" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span>{{ number_format($product->likes_count ?? 0) }}</span>
                        </button>
                    </form>

                    <button type="button"
                            onclick="openShareModal()"
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-[#EBF9F2] px-3 py-1 text-xs font-bold text-[#0B6E4F] hover:bg-[#D5F5E6] transition"
                            title="Bagikan link produk">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span>Bagikan</span>
                    </button>
                </div>
            </div>

            <!-- Judul Produk & Ringkasan Statistik -->
            <div class="space-y-1.5">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">
                    {{ $product->product_name }}
                </h1>
                <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-amber-600 font-bold">
                        <svg class="w-3.5 h-3.5 fill-amber-400 text-amber-400" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>{{ number_format($product->average_rating, 1, ',', '.') }} ({{ $product->comments->count() }} ulasan)</span>
                    </span>
                    <span>•</span>
                    <span>{{ number_format($product->views_count ?? 0) }} dilihat</span>
                    <span>•</span>
                    <span>{{ number_format($product->likes_count ?? 0) }} disukai</span>
                    <span>•</span>
                    <span>Karya {{ $product->store->seller_type === 'guru' ? 'Guru' : 'Siswa' }} SMK PAL</span>
                </div>
            </div>

            <!-- Harga, Stok & Deretan Diskon -->
            <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/70 p-4 space-y-2.5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#0B6E4F]">
                        {{ $product->formatted_price }}
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full {{ $product->stock > 0 ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-bold">
                        {{ $product->stock > 0 ? 'Stok Tersedia: ' . $product->stock . ' unit' : 'Stok Sedang Habis' }}
                    </span>
                </div>

                <!-- Badge Skema Diskon Pembeli & Bazar -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @if(($product->store->discount_pelajar ?? 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-white border border-[#CDEFE0] px-2.5 py-1 text-[11px] font-bold text-[#0B6E4F]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span>Diskon Pelajar {{ $product->store->discount_pelajar }}%</span>
                        </span>
                    @endif
                    @if(($product->store->discount_keluarga ?? 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-white border border-[#CDEFE0] px-2.5 py-1 text-[11px] font-bold text-[#0B6E4F]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Diskon Keluarga SMK {{ $product->store->discount_keluarga }}%</span>
                        </span>
                    @endif
                    @if(($product->store->discount_alumni ?? 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-white border border-[#CDEFE0] px-2.5 py-1 text-[11px] font-bold text-[#0B6E4F]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            <span>Diskon Alumni {{ $product->store->discount_alumni }}%</span>
                        </span>
                    @endif
                    @if(isset($storeActiveBazaar) && $storeActiveBazaar)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 border border-amber-200 px-2.5 py-1 text-[11px] font-extrabold text-amber-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            <span>Promo {{ $storeActiveBazaar->title }} {{ $storeActiveBazaar->discount_percent + ($product->store->discount_bazar_extra ?? 0) }}% &bull; Otomatis</span>
                        </span>
                    @endif
                </div>

                <!-- Metode Pengiriman yang Diizinkan -->
                <div class="pt-1 flex items-center gap-2 text-xs text-slate-600">
                    <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span>Opsi Transaksi: <strong class="font-extrabold text-[#0F291E]">{{ $product->allowed_delivery_label }}</strong></span>
                </div>
            </div>

            <!-- Ringkasan Deskripsi & Spesifikasi Cepat -->
            <div class="space-y-2">
                <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Tentang Karya Ini</h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    {{ $product->description }}
                </p>
            </div>

            <!-- Pengaturan Jumlah & Tombol Aksi Utama -->
            <div class="pt-2 border-t border-slate-100 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#0F291E]">Jumlah Pembelian</span>
                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <button type="button" onclick="changeQty(-1)" class="px-3.5 py-1.5 text-sm font-bold text-slate-500 hover:bg-slate-50 hover:text-[#0B6E4F] transition">−</button>
                        <input type="number" id="qty-display" value="1" min="1" max="{{ max(1, $product->stock) }}" readonly class="w-12 text-center text-xs font-extrabold text-[#0F291E] border-x border-slate-200 py-1.5 focus:outline-none">
                        <button type="button" onclick="changeQty(1)" class="px-3.5 py-1.5 text-sm font-bold text-[#0B6E4F] hover:bg-emerald-50 transition">+</button>
                    </div>
                </div>

                <!-- 1. TOMBOL UTAMA: BELI / PESAN SEKARANG (MENuju HALAMAN TRANSAKSI) -->
                @auth
                    <form action="{{ route('buyer.checkout.index') }}" method="GET">
                        <input type="hidden" name="direct_product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" id="direct-checkout-qty-input" value="1">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2.5 rounded-xl sm:rounded-2xl bg-[#0B6E4F] hover:bg-[#08583F] active:scale-[0.99] text-white px-5 py-3.5 sm:py-4 text-xs sm:text-sm font-extrabold shadow-md hover:shadow-lg transition-all duration-200">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Beli / Pesan Sekarang (Lanjut ke Halaman Transaksi)</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"
                       class="w-full inline-flex items-center justify-center gap-2.5 rounded-xl sm:rounded-2xl bg-[#0B6E4F] hover:bg-[#08583F] active:scale-[0.99] text-white px-5 py-3.5 sm:py-4 text-xs sm:text-sm font-extrabold shadow-md hover:shadow-lg transition-all duration-200">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span>Pesan via Aplikasi (Masuk / Daftar Akun)</span>
                    </a>
                @endauth

                <!-- 2. OPSI TAMBAH KE KERANJANG & KONSULTASI VIA WHATSAPP -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @auth
                        <form action="{{ route('buyer.cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" id="cart-qty-input" value="1">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-[#0B6E4F] bg-[#EBF9F2]/50 hover:bg-[#EBF9F2] px-4 py-2.5 text-xs font-extrabold text-[#0B6E4F] transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>+ Masukkan Keranjang</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-[#0B6E4F] bg-[#EBF9F2]/50 hover:bg-[#EBF9F2] px-4 py-2.5 text-xs font-extrabold text-[#0B6E4F] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>+ Masukkan Keranjang</span>
                        </a>
                    @endauth

                    <a href="{{ $product->store->whatsapp_url }}?text={{ $waMessage }}"
                       target="_blank"
                       rel="noopener"
                       id="btn-main-whatsapp"
                       class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-[#25D366] bg-[#25D366]/10 hover:bg-[#25D366] text-[#0B6E4F] hover:text-white px-4 py-2.5 text-xs font-extrabold transition-all duration-200 group">
                        <svg class="w-4 h-4 fill-[#25D366] group-hover:fill-white shrink-0 transition-colors" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.073.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.112.551 4.148 1.6 5.952l-1.6 5.848 6.002-1.574c1.735 1.002 3.731 1.574 5.998 1.574 6.627 0 12-5.373 12-12s-5.373-12-12-12zm.001 22.083c-1.899 0-3.69-.536-5.231-1.465l-.375-.224-3.568.937.953-3.479-.247-.393c-1.026-1.633-1.568-3.528-1.568-5.459 0-5.556 4.519-10.075 10.076-10.075 5.556 0 10.075 4.519 10.075 10.075 0 5.557-4.519 10.076-10.075 10.076z"/>
                        </svg>
                        <span>Tanya / Konsultasi WA</span>
                    </a>
                </div>

                <!-- Info Panduan Transaksi Aman -->
                <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3 text-[11px] text-slate-600 leading-relaxed flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-[#0B6E4F] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span><strong>Transaksi Aman:</strong> Gunakan <strong>Chat Penjual</strong> (di aplikasi) atau <strong>Tanya / Konsultasi WA</strong> untuk tanya jawab detail karya. Pemesanan tetap dilakukan melalui aplikasi agar terlindungi sistem (Dipesan → Dikemas → Dikirim → Diterima).</span>
                </div>

                <!-- Deretan Micro-Badges Visual Nilai Tambah Produk -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2 text-xs font-semibold text-slate-700">
                        <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="truncate">100% Original Karya Siswa</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2 text-xs font-semibold text-slate-700">
                        <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="truncate">Gratis Ongkir (Ambil di Sekolah)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian Bawah: Tab Deskripsi Produk & Ulasan/Komentar (Full Width & Rapi) -->
    <div id="section-tabs" class="rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-6 shadow-2xs">
        <div class="flex items-center gap-6 border-b border-slate-200/80 text-xs sm:text-sm font-bold">
            <button type="button" id="tab-btn-deskripsi" onclick="switchProductTab('deskripsi')" class="pb-3 border-b-2 border-[#0B6E4F] text-[#0B6E4F] transition">
                Deskripsi & Spesifikasi Karya
            </button>
            <button type="button" id="tab-btn-ulasan" onclick="switchProductTab('ulasan')" class="pb-3 border-b-2 border-transparent text-slate-400 hover:text-slate-700 transition">
                Ulasan & Komentar ({{ $product->comments->count() }})
            </button>
        </div>

        <!-- Konten Tab 1: Deskripsi & Spesifikasi -->
        <div id="tab-panel-deskripsi" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Penjelasan Lengkap</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                    {{ $product->description }}
                </p>
                <p class="text-[11px] text-slate-400 pt-2">
                    Catatan: Setiap karya dibuat dengan ketelitian tinggi oleh sivitas SMK Plus Al Hilal.
                </p>
            </div>

            <div class="lg:col-span-5 rounded-xl bg-[#F8FAFC] border border-slate-200/70 p-4 space-y-2.5 text-xs">
                <h3 class="font-extrabold text-[#0F291E] pb-1 border-b border-slate-200/60">Spesifikasi Karya</h3>
                <div class="grid grid-cols-3 py-1">
                    <span class="text-slate-400">Kategori</span>
                    <span class="col-span-2 font-semibold text-[#0F291E]">{{ $product->category }}</span>
                </div>
                <div class="grid grid-cols-3 py-1">
                    <span class="text-slate-400">Unit / Kelas</span>
                    <span class="col-span-2 font-semibold text-[#0F291E]">{{ $product->store->student_class ?: 'SMK Plus Al Hilal' }}</span>
                </div>
                <div class="grid grid-cols-3 py-1">
                    <span class="text-slate-400">Stok Tersedia</span>
                    <span class="col-span-2 font-semibold text-[#0F291E]">{{ $product->stock }} unit</span>
                </div>
                <div class="grid grid-cols-3 py-1">
                    <span class="text-slate-400">Statistik</span>
                    <span class="col-span-2 font-semibold text-[#0F291E]">
                        {{ number_format($product->views_count ?? 0) }} dilihat • {{ number_format($product->likes_count ?? 0) }} suka • {{ $product->comments->count() }} komentar
                    </span>
                </div>
            </div>
        </div>

        <!-- Konten Tab 2: Komentar & Ulasan Pengunjung Berakun -->
        <div id="tab-panel-ulasan" class="hidden space-y-5">
            @auth
                <form action="{{ route('products.comment', $product->slug) }}" method="POST" class="rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80 space-y-3">
                    @csrf
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-[#0F291E]">{{ auth()->user()->name }} ({{ auth()->user()->subtype_label }})</span>
                        <select name="rating" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">
                            <option value="5">5 / 5 - Sangat Bagus</option>
                            <option value="4">4 / 5 - Bagus</option>
                            <option value="3">3 / 5 - Cukup</option>
                            <option value="2">2 / 5 - Kurang</option>
                            <option value="1">1 / 5 - Perlu Perbaikan</option>
                        </select>
                    </div>
                    <textarea name="comment" rows="3" required placeholder="Tulis komentar atau apresiasi Anda..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none"></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                            Kirim Komentar
                        </button>
                    </div>
                </form>
            @else
                <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] p-4 flex items-center justify-between gap-3">
                    <p class="text-xs text-[#0F291E]">Silakan masuk untuk memberikan komentar pada karya ini.</p>
                    <a href="{{ route('login') }}" class="rounded-lg bg-[#0B6E4F] px-3 py-1.5 text-xs font-semibold text-white shrink-0">Masuk</a>
                </div>
            @endauth

            <div class="space-y-3">
                @forelse($product->comments as $c)
                    <div class="rounded-xl bg-[#F8FAFC] border border-slate-200/60 p-4 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-[#0F291E]">{{ $c->user->name ?? 'Pengunjung' }}</span>
                            <span class="text-[11px] text-slate-400">{{ $c->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $c->comment }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-3">Belum ada komentar. Jadilah yang pertama memberikan apresiasi!</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Karya Lain Untukmu -->
    @if($relatedProducts->count() > 0)
        <div class="pt-4 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg sm:text-xl font-extrabold text-[#0F291E]">Karya Lain Untukmu</h2>
                <a href="{{ route('home') }}" class="text-xs font-bold text-[#0B6E4F] hover:underline">Jelajahi semua →</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-5">
                @foreach($relatedProducts as $rel)
                    @php
                        $relImg = \App\Models\Product::resolveImageUrl($rel->image);
                    @endphp
                    <article class="group rounded-xl sm:rounded-2xl bg-white border border-slate-200/80 overflow-hidden hover:shadow-md transition flex flex-col">
                        <a href="{{ route('products.show', $rel->slug) }}" class="relative aspect-square w-full bg-slate-100 overflow-hidden block" style="aspect-ratio: 1 / 1;">
                            <img src="{{ $relImg }}"
                                 alt="{{ $rel->product_name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 style="aspect-ratio: 1 / 1; object-fit: cover;"
                                 onerror="this.src='{{ $fallbackImg }}'">
                        </a>
                        <div class="p-2.5 sm:p-4 space-y-1 sm:space-y-1.5 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="inline-block rounded bg-[#EBF9F2] px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold text-[#0B6E4F] truncate max-w-full">
                                    {{ $rel->store->student_class ?: $rel->category }}
                                </span>
                                <h3 class="font-bold text-[#0F291E] text-xs sm:text-sm line-clamp-1 hover:text-[#0B6E4F] transition mt-1">
                                    <a href="{{ route('products.show', $rel->slug) }}">{{ $rel->product_name }}</a>
                                </h3>
                                <p class="text-[10px] sm:text-xs text-slate-500 line-clamp-2 leading-relaxed mt-0.5">
                                    {{ $rel->description ?: 'Karya siswa SMK Plus Al Hilal.' }}
                                </p>
                                <p class="text-xs sm:text-sm font-extrabold text-[#0B6E4F] mt-1">{{ $rel->formatted_price }}</p>
                            </div>
                            <div class="pt-2 border-t border-slate-100/90 space-y-2">
                                <div class="grid grid-cols-2 gap-1.5">
                                    <a href="{{ route('products.show', $rel->slug) }}" class="inline-flex items-center justify-center rounded-lg bg-[#F8FAFC] hover:bg-slate-100 text-slate-700 border border-slate-200/80 py-1.5 px-2 text-[10px] sm:text-xs font-bold transition text-center hover:text-[#0B6E4F]">
                                        <span>Detail</span>
                                    </a>
                                    <a href="{{ route('products.show', $rel->slug) }}" class="inline-flex items-center justify-center rounded-lg bg-[#0B6E4F] hover:bg-[#08583F] text-white py-1.5 px-2 text-[10px] sm:text-xs font-extrabold transition text-center active:scale-95 shadow-2xs">
                                        <span>Pesan</span>
                                    </a>
                                </div>
                                <div class="flex items-center justify-between text-[9px] sm:text-[10px] text-slate-400 font-medium px-0.5">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>{{ $rel->views_count ?? 0 }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 text-rose-500 fill-rose-500" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        <span>{{ $rel->likes_count ?? 0 }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Modal Bagikan Produk -->
    <div id="share-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 transition-all">
        <div class="relative w-full max-w-md rounded-2xl bg-white p-5 sm:p-6 shadow-2xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold text-[#0F291E]">Bagikan Produk</h3>
                        <p class="text-[11px] text-slate-400">Bagikan karya ini ke media sosial atau teman Anda</p>
                    </div>
                </div>
                <button type="button" onclick="closeShareModal()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold text-base flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <div class="flex items-center gap-3 rounded-xl bg-[#F8FAFC] border border-slate-200/80 p-3">
                <img src="{{ $mainImg }}" alt="{{ $product->product_name }}" class="w-14 h-14 rounded-lg object-cover bg-white shrink-0 border border-slate-200" onerror="this.src='{{ $fallbackImg }}'">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-[#0F291E] line-clamp-1">{{ $product->product_name }}</p>
                    <p class="text-xs font-extrabold text-[#0B6E4F] mt-0.5">{{ $product->formatted_price }}</p>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">{{ $product->store->store_name }} • SMK Plus Al Hilal</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-[11px] font-bold text-slate-600">Salin Tautan / Link Produk</label>
                <div class="flex items-center gap-2">
                    <input type="text"
                           id="share-product-url-input"
                           value="{{ url()->current() }}"
                           readonly
                           class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs text-slate-700 font-mono focus:outline-none">
                    <button type="button"
                            id="btn-copy-share-link"
                            onclick="copyShareLink()"
                            class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shrink-0">
                        Salin
                    </button>
                </div>
                <p id="share-copy-feedback" class="text-[11px] font-semibold text-[#0B6E4F] hidden">Link berhasil disalin ke clipboard!</p>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Mobile CTA Bar (Khusus HP) -->
<aside class="fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_24px_rgba(0,0,0,0.08)] px-4 py-3 lg:hidden"
       style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
    <div class="max-w-md mx-auto flex items-center justify-between gap-3">
        <div class="min-w-0">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Harga</span>
            <div class="flex items-baseline gap-1.5">
                <span id="mobile-sticky-price" class="text-base sm:text-lg font-black text-[#0B6E4F] tracking-tight">
                    {{ $product->formatted_price }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
            @auth
                <a href="{{ route('buyer.checkout.index', ['direct_product_id' => $product->id, 'quantity' => 1]) }}"
                   id="btn-sticky-order"
                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] active:scale-95 text-white px-3.5 sm:px-4 py-2.5 text-xs font-black shadow-md transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Beli Sekarang</span>
                </a>
            @else
                <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"
                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] active:scale-95 text-white px-3.5 sm:px-4 py-2.5 text-xs font-black shadow-md transition">
                    <span>Beli Sekarang</span>
                </a>
            @endauth

            <a href="{{ auth()->check() ? ($isOwnStore ? route('chat.index') : $inAppChatUrl) : route('login', ['redirect' => $inAppChatUrl]) }}"
               class="inline-flex items-center justify-center rounded-xl border border-[#0B6E4F] bg-[#EBF9F2] hover:bg-[#D5F5E6] active:scale-95 text-[#0B6E4F] px-2.5 py-2.5 text-xs font-bold transition"
               title="{{ $isOwnStore ? 'Kotak Masuk Chat' : 'Chat Penjual di Aplikasi' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </a>

            <a href="{{ $product->store->whatsapp_url }}?text={{ $waMessage }}"
               target="_blank"
               rel="noopener"
               id="btn-sticky-whatsapp"
               class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#25D366] hover:bg-[#20ba5a] active:scale-95 text-white px-3 sm:px-3.5 py-2.5 text-xs font-bold shadow-sm transition">
                <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.073.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.112.551 4.148 1.6 5.952l-1.6 5.848 6.002-1.574c1.735 1.002 3.731 1.574 5.998 1.574 6.627 0 12-5.373 12-12s-5.373-12-12-12zm.001 22.083c-1.899 0-3.69-.536-5.231-1.465l-.375-.224-3.568.937.953-3.479-.247-.393c-1.026-1.633-1.568-3.528-1.568-5.459 0-5.556 4.519-10.075 10.076-10.075 5.556 0 10.075 4.519 10.075 10.075 0 5.557-4.519 10.076-10.075 10.076z"/>
                </svg>
                <span>Tanya WA</span>
            </a>
        </div>
    </div>
</aside>

<script>
    const unitPriceProduct = {{ (int) $product->price }};
    const baseWaProductUrl = @json($product->store->whatsapp_url);
    const sellerOwnerName = @json($product->store->owner->name ?? $product->store->store_name);
    const storeTitleName = @json($product->store->store_name);
    const productItemName = @json($product->product_name);
    const baseDirectCheckoutUrl = @json(route('buyer.checkout.index', ['direct_product_id' => $product->id]));

    function selectGalleryThumbnail(btn, imgUrl) {
        const mainImg = document.getElementById('main-product-image');
        if (mainImg) {
            mainImg.src = imgUrl;
        }
        document.querySelectorAll('.product-thumb-btn').forEach(el => {
            el.classList.remove('border-[#0B6E4F]', 'ring-2', 'ring-[#0B6E4F]/20', 'opacity-100');
            el.classList.add('border-slate-200', 'opacity-80');
        });
        btn.classList.remove('border-slate-200', 'opacity-80');
        btn.classList.add('border-[#0B6E4F]', 'ring-2', 'ring-[#0B6E4F]/20', 'opacity-100');
    }

    function changeQty(delta) {
        const display = document.getElementById('qty-display');
        const hiddenInput = document.getElementById('cart-qty-input');
        const directCheckoutQty = document.getElementById('direct-checkout-qty-input');
        const stickyOrderBtn = document.getElementById('btn-sticky-order');
        if (!display) return;
        const max = parseInt(display.getAttribute('max') || '99', 10);
        let next = parseInt(display.value || '1', 10) + delta;
        if (next < 1) next = 1;
        if (next > max) next = max;
        display.value = next;
        if (hiddenInput) hiddenInput.value = next;
        if (directCheckoutQty) directCheckoutQty.value = next;
        if (stickyOrderBtn) {
            stickyOrderBtn.href = baseDirectCheckoutUrl + '&quantity=' + next;
        }

        const stickyPrice = document.getElementById('mobile-sticky-price');
        if (stickyPrice) {
            const totalCalc = unitPriceProduct * next;
            stickyPrice.textContent = 'Rp ' + totalCalc.toLocaleString('id-ID');
        }

        const dynamicWaMsg = encodeURIComponent("Assalamu'alaikum " + sellerOwnerName + " (" + storeTitleName + "), saya ingin berkonsultasi seputar produk *" + productItemName + "* (" + next + " item) di Hilalkraf SMK Plus Al Hilal. Mohon infonya kak.\n\n(Catatan: Pemesanan resmi akan saya lakukan via aplikasi Hilalkraf agar aman & tercatat status transaksinya).");
        const mainWa = document.getElementById('btn-main-whatsapp');
        const stickyWa = document.getElementById('btn-sticky-whatsapp');
        if (mainWa) mainWa.href = baseWaProductUrl + '?text=' + dynamicWaMsg;
        if (stickyWa) stickyWa.href = baseWaProductUrl + '?text=' + dynamicWaMsg;
    }

    function switchProductTab(tab) {
        const isDesc = tab === 'deskripsi';
        document.getElementById('tab-panel-deskripsi').classList.toggle('hidden', !isDesc);
        document.getElementById('tab-panel-ulasan').classList.toggle('hidden', isDesc);

        const btnDesc = document.getElementById('tab-btn-deskripsi');
        const btnUlasan = document.getElementById('tab-btn-ulasan');
        btnDesc.className = isDesc
            ? 'pb-3 border-b-2 border-[#0B6E4F] text-[#0B6E4F] transition'
            : 'pb-3 border-b-2 border-transparent text-slate-400 hover:text-slate-700 transition';
        btnUlasan.className = !isDesc
            ? 'pb-3 border-b-2 border-[#0B6E4F] text-[#0B6E4F] transition'
            : 'pb-3 border-b-2 border-transparent text-slate-400 hover:text-slate-700 transition';
    }

    function openShareModal() {
        const modal = document.getElementById('share-product-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeShareModal() {
        const modal = document.getElementById('share-product-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function copyShareLink() {
        const input = document.getElementById('share-product-url-input');
        const btn = document.getElementById('btn-copy-share-link');
        const feedback = document.getElementById('share-copy-feedback');
        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999);

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(onCopySuccess).catch(() => {
                document.execCommand('copy');
                onCopySuccess();
            });
        } else {
            document.execCommand('copy');
            onCopySuccess();
        }

        function onCopySuccess() {
            if (btn) {
                btn.textContent = 'Tersalin!';
                setTimeout(() => { btn.textContent = 'Salin'; }, 2500);
            }
            if (feedback) {
                feedback.classList.remove('hidden');
                setTimeout(() => feedback.classList.add('hidden'), 3500);
            }
        }
    }

    document.addEventListener('click', (e) => {
        const shareModal = document.getElementById('share-product-modal');
        if (shareModal && e.target === shareModal) {
            closeShareModal();
        }
    });
</script>
@endsection
