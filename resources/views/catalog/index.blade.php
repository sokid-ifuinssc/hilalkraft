{{--
============================================================================
VIEW: BERANDA MARKETPLACE, BARIS IKLAN MITRA & KATALOG (catalog/index.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman beranda marketplace hilalkraf yang simpel, bersih,
  tidak menumpuk, dan responsif di semua perangkat (Mobile, Tablet, Desktop):
  1. Baris Iklan Mitra & Sponsor di Bagian Paling Atas (gaya Tokopedia):
     Menampilkan iklan mitra kerjasama sekolah (misal: Bank Syariah Indonesia /
     BSI, PT. Mega Mentari, Daihatsu) secara bergantian/berkedip sesuai
     `duration_seconds` masing-masing iklan dan masa kontrak aktif (`contract_start`
     s/d `contract_end`) yang diatur oleh Super Admin pada CMS.
  2. Hero Banner Ringkas & Kartu Pilihan Minggu Ini.
  3. Filter Kategori Dinamis (dari input Admin/Super Admin) & Katalog Produk
     lengkap dengan indikator Ikon Lihat (👁️), Suka (❤️), dan Komentar (💬).

Algoritma:
  1. Per-Slide Dynamic Timer Carousel (`initPartnerAdsCarousel()`):
     Membaca atribut `data-duration` (dalam detik) pada slide iklan aktif,
     menjalankan animasi progress bar selama `duration * 1000` ms, lalu
     berpindah secara otomatis dengan efek kedip/transisi halus (`ad-slide-active`)
     ke iklan mitra berikutnya.
  2. Contract-Aware Filtering: Hanya iklan dengan `is_active = true` dan
     tanggal hari ini berada dalam rentang `contract_start` s/d `contract_end`
     yang dikirim oleh `CmsContent::activePartnerAds()`.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'hilalkraf — Marketplace Karya Siswa SMK Plus Al Hilal')

@section('content')

<!-- ========================================================================
     1. BARIS IKLAN MITRA KERJASAMA SEKOLAH DI ATAS (ALA TOKOPEDIA BANNER SLIDER)
     ======================================================================== -->
@if(isset($partnerAds) && $partnerAds->count() > 0)
<section class="bg-white border-b border-slate-200/70 py-2 sm:py-2.5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div id="partner-ad-carousel" class="relative rounded-xl sm:rounded-2xl overflow-hidden shadow-2xs border border-slate-200/80 bg-slate-900">
            @foreach($partnerAds as $idx => $ad)
                @php
                    $durationSec = max(1, (int) ($ad->duration_seconds ?: 5));
                    $theme = $ad->theme_color ?: 'emerald';
                    $bgGradient = match($theme) {
                        'amber', 'bsi' => 'from-[#00615C] via-[#00827B] to-[#F39C12]',
                        'blue', 'megamentari' => 'from-[#0F172A] via-[#1E3A8A] to-[#0284C7]',
                        'rose', 'daihatsu' => 'from-[#7F1D1D] via-[#B91C1C] to-[#E11D48]',
                        default => 'from-[#064E3B] via-[#0B6E4F] to-[#10B981]',
                    };
                    $accentBadge = match($theme) {
                        'amber', 'bsi' => 'bg-amber-400 text-slate-950',
                        'blue', 'megamentari' => 'bg-sky-400 text-slate-950',
                        'rose', 'daihatsu' => 'bg-rose-400 text-slate-950',
                        default => 'bg-emerald-300 text-slate-950',
                    };
                    $adImg = $ad->image
                        ? (Str::startsWith($ad->image, 'http') ? $ad->image : (Str::startsWith($ad->image, 'images/') ? asset($ad->image) : asset('storage/' . $ad->image)))
                        : null;
                @endphp

                <div class="partner-ad-slide {{ $idx === 0 ? 'block ad-slide-active' : 'hidden' }}"
                     data-index="{{ $idx }}"
                     data-duration="{{ $durationSec }}">
                    <div class="relative bg-gradient-to-r {{ $bgGradient }} text-white p-3 sm:py-3.5 sm:px-6 flex flex-row items-center justify-between gap-3 sm:gap-6">

                        <!-- Dekorasi Cahaya Latar Halus -->
                        <div class="pointer-events-none absolute -right-8 -top-8 w-28 h-28 rounded-full bg-white/10 blur-xl"></div>

                        <!-- Konten Kiri Iklan Mitra Ringkas -->
                        <div class="relative z-10 space-y-1 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-full {{ $accentBadge }} px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider shadow-2xs">
                                    <span>★</span>
                                    <span>{{ $ad->badge_text ?: 'Mitra Resmi' }}</span>
                                </span>

                                @if($ad->partner_name)
                                    <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-white truncate max-w-[150px]">
                                        {{ $ad->partner_name }}
                                    </span>
                                @endif

                                <span class="hidden sm:inline-block rounded-full bg-black/25 px-2 py-0.5 text-[9px] text-white/80">
                                    ⏱ {{ $durationSec }}s
                                </span>
                            </div>

                            <h2 class="text-xs sm:text-base font-extrabold tracking-tight leading-snug line-clamp-1">
                                {{ $ad->title }}
                            </h2>

                            @if($ad->content)
                                <p class="text-[10px] sm:text-xs text-white/90 leading-tight line-clamp-1">
                                    {{ $ad->content }}
                                </p>
                            @endif
                        </div>

                        <!-- Kanan: Visual Gambar Iklan Kecil Setengahnya & Tombol CTA -->
                        <div class="relative z-10 flex items-center gap-2 sm:gap-3 shrink-0">
                            @if($adImg)
                                <img src="{{ $adImg }}"
                                     alt="{{ $ad->title }}"
                                     class="h-10 sm:h-14 w-16 sm:w-24 rounded-lg object-contain border border-white/40 shadow-xs bg-white p-0.5">
                            @endif

                            @if($ad->link_url)
                                <a href="{{ $ad->link_url }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-[10px] sm:text-xs font-bold text-slate-900 hover:bg-slate-100 transition shadow-2xs shrink-0">
                                    <span>Info</span>
                                    <span>→</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Progress Bar Durasi Tayang Slide Aktif -->
            @if($partnerAds->count() > 1)
                <div class="h-0.5 w-full bg-black/30">
                    <div id="partner-ad-progress" class="h-full bg-white/90 transition-all ease-linear" style="width: 0%;"></div>
                </div>

                <!-- Navigasi Titik (Dots) & Tombol Prev/Next -->
                <div class="absolute bottom-1.5 right-3 z-20 flex items-center gap-1">
                    <button type="button" onclick="stepPartnerAd(-1)" class="w-5 h-5 rounded-full bg-black/35 text-white hover:bg-black/60 flex items-center justify-center text-[10px] transition" title="Iklan Sebelumnya">‹</button>
                    @foreach($partnerAds as $idx => $ad)
                        <button type="button"
                                onclick="goToPartnerAd({{ $idx }})"
                                class="partner-ad-dot h-1.5 rounded-full transition-all {{ $idx === 0 ? 'w-4 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80' }}"
                                title="{{ $ad->partner_name ?: $ad->title }}">
                        </button>
                    @endforeach
                    <button type="button" onclick="stepPartnerAd(1)" class="w-5 h-5 rounded-full bg-black/35 text-white hover:bg-black/60 flex items-center justify-center text-[10px] transition" title="Iklan Berikutnya">›</button>
                </div>
            @endif
        </div>
    </div>
</section>
@endif

<!-- ========================================================================
     2. HERO SECTION RINGKAS & ELEGAN (TERINTEGRASI CMS)
     ======================================================================== -->
@php
    $heroBadge    = str_replace(['dikeola', 'Dikeola'], ['dikelola', 'Dikelola'], \App\Models\CmsContent::val('hero_badge', 'KARYA LOKAL, POTENSI BESAR.'));
    $heroTitle    = str_replace(['dikeola', 'Dikeola'], ['dikelola', 'Dikelola'], \App\Models\CmsContent::val('hero_title', 'Karya Inovatif Siswa SMK Plus Al Hilal'));
    $heroSubtitle = str_replace(['dikeola', 'Dikeola'], ['dikelola', 'Dikelola'], \App\Models\CmsContent::val('hero_subtitle', 'Temukan karya yang punya cerita. Dari kerajinan tangan hingga inovasi teknologi, dibuat dengan hati oleh talenta muda.'));
    $heroCtaText  = \App\Models\CmsContent::val('hero_cta_text', 'Jelajahi Produk');
    $heroStats    = \App\Models\CmsContent::val('hero_stats_text', 'Siswa & Guru Berkarya');
@endphp

<section class="bg-gradient-to-b from-[#E6F7F0] via-[#F2FBF7] to-[#F8FAFC] border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

            <!-- Kiri: Teks Utama Hero -->
            <div class="lg:col-span-7 space-y-4">
                @if($heroBadge !== '')
                    <div class="inline-flex items-center gap-2 rounded-full bg-white border border-emerald-500/20 px-3 py-1 text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-[#0B6E4F] shadow-2xs">
                        <span>✦</span>
                        <span>{{ $heroBadge }}</span>
                    </div>
                @endif

                @if($heroTitle !== '')
                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-[#0F291E] tracking-tight leading-[1.15]">
                        {{ $heroTitle }}
                    </h1>
                @endif

                @if($heroSubtitle !== '')
                    <p class="text-xs sm:text-sm lg:text-base text-slate-600 max-w-xl leading-relaxed">
                        {{ $heroSubtitle }}
                    </p>
                @endif

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    @if($heroCtaText !== '')
                        <a href="#katalog-produk" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-xs">
                            <span>{{ $heroCtaText }}</span>
                            <span>→</span>
                        </a>
                    @endif
                    @guest
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#0F291E] hover:border-[#0B6E4F] transition">
                            <span>Buka Toko Siswa / Daftar</span>
                        </a>
                    @endguest
                </div>

                <!-- 3 Poin Nilai Utama Ringkas dalam 1 Baris -->
                <div class="pt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-semibold text-slate-600">
                    @if(\App\Models\CmsContent::val('value_1', 'Karya siswa asli') !== '')
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-[#D5F5E6] text-[#0B6E4F] flex items-center justify-center text-[10px]">✓</span>
                            {{ \App\Models\CmsContent::val('value_1', 'Karya siswa asli') }}
                        </span>
                    @endif
                    @if(\App\Models\CmsContent::val('value_2', 'Langsung ke kreator') !== '')
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-[#D5F5E6] text-[#0B6E4F] flex items-center justify-center text-[10px]">💬</span>
                            {{ \App\Models\CmsContent::val('value_2', 'Langsung ke kreator') }}
                        </span>
                    @endif
                    @if(\App\Models\CmsContent::val('value_3', 'Dukung talenta muda') !== '')
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-[#D5F5E6] text-[#0B6E4F] flex items-center justify-center text-[10px]">♡</span>
                            {{ \App\Models\CmsContent::val('value_3', 'Dukung talenta muda') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Kanan: Kartu Showcase Karya Unggulan -->
            <div class="lg:col-span-5">
                @php
                    $heroImg = $featuredProduct
                        ? (Str::startsWith($featuredProduct->image, 'http')
                            ? $featuredProduct->image
                            : (Str::startsWith($featuredProduct->image, 'images/') ? asset($featuredProduct->image) : asset('storage/' . $featuredProduct->image)))
                        : asset('images/products/gantungan-kunci-bunga.jpg');
                    $heroLink = $featuredProduct ? route('products.show', $featuredProduct->slug) : '#katalog-produk';
                @endphp
                <a href="{{ $heroLink }}" class="group block relative rounded-2xl overflow-hidden shadow-md bg-white border border-slate-200/80 aspect-[16/11]">
                    <img
                        src="{{ $heroImg }}"
                        alt="{{ $featuredProduct->product_name ?? 'Karya Siswa SMK Plus Al Hilal' }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        onerror="this.src='{{ asset('images/products/gantungan-kunci-bunga.jpg') }}'"
                    >
                    <div class="absolute inset-x-3 bottom-3 rounded-xl bg-white/95 backdrop-blur-md p-3.5 shadow-md border border-slate-100 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-[#0B6E4F]">
                                KARYA PILIHAN SISWA
                            </span>
                            <h3 class="text-xs sm:text-sm font-bold text-[#0F291E] truncate group-hover:text-[#0B6E4F] transition">
                                {{ $featuredProduct->product_name ?? 'Gantungan Kunci Bunga' }}
                            </h3>
                            <p class="text-[11px] text-slate-500 truncate">
                                {{ $featuredProduct->store->owner->name ?? 'Khoiriyah Muntazah' }} • {{ $featuredProduct->store->student_class ?? 'XI TKJT' }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-lg bg-[#EBF9F2] px-2.5 py-1 text-xs font-extrabold text-[#0B6E4F]">
                            {{ $featuredProduct->formatted_price ?? 'Rp 2.000' }}
                        </span>
                    </div>
                </a>
            </div>

        </div>
    </div>
</section>

<!-- ========================================================================
     2. INFO & PENGUMUMAN / PROMO DISKON (HORIZONTAL SLIDER / CAROUSEL RINGKAS)
     ======================================================================== -->
@php
    $promoCards = collect();

    if (isset($customCmsBanners) && $customCmsBanners->count() > 0) {
        foreach ($customCmsBanners as $b) {
            $promoCards->push((object)[
                'category'  => $b->section ?: 'Info & Promo',
                'title'     => $b->title,
                'content'   => $b->content,
                'link_url'  => $b->link_url,
                'image'     => $b->image,
            ]);
        }
    } else {
        $dPelajar = (int) \App\Models\CmsContent::val('policy_discount_pelajar', '10');
        $dKeluarga = (int) \App\Models\CmsContent::val('policy_discount_keluarga', '10');
        $dAlumni = (int) \App\Models\CmsContent::val('policy_discount_alumni', '5');
        $bazarEvent = isset($activeBazaarEvents) ? $activeBazaarEvents->first() : null;

        $promoCards->push((object)[
            'category' => 'Diskon Pelajar',
            'title'    => "Diskon {$dPelajar}% Pelajar",
            'content'  => 'Khusus siswa SMK Plus Al Hilal & pelajar terdaftar.',
            'link_url' => route('register'),
            'image'    => null,
        ]);
        $promoCards->push((object)[
            'category' => 'Diskon Keluarga',
            'title'    => "Diskon {$dKeluarga}% Keluarga",
            'content'  => 'Wali & keluarga besar siswa SMK Plus Al Hilal.',
            'link_url' => route('register'),
            'image'    => null,
        ]);
        $promoCards->push((object)[
            'category' => 'Diskon Alumni',
            'title'    => "Diskon {$dAlumni}% Alumni",
            'content'  => 'Apresiasi istimewa bagi seluruh alumni SMK.',
            'link_url' => route('register'),
            'image'    => null,
        ]);
        if ($bazarEvent) {
            $promoCards->push((object)[
                'category' => 'Diskon Bazar',
                'title'    => "Bazar {$bazarEvent->discount_percent}% ({$bazarEvent->voucher_code})",
                'content'  => $bazarEvent->title . ' • Lokasi: ' . ($bazarEvent->location ?? 'SMK Plus Al Hilal'),
                'link_url' => '#katalog-produk',
                'image'    => null,
            ]);
        }
    }
@endphp

@if($promoCards->count() > 0)
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 pb-1 sm:pt-4 sm:pb-2">
    <!-- Header Petunjuk: Info & Promo Diskon di Kiri, Geser ➔ di Kanan -->
    <div class="flex items-center justify-between text-xs font-bold text-slate-500 mb-2 px-0.5">
        <div class="flex items-center gap-1.5 text-[#0F291E]">
            <span class="inline-block w-2 h-2 rounded-full bg-[#0B6E4F]"></span>
            <span class="text-xs font-extrabold uppercase tracking-wider text-[#0F291E]">Info & Promo Diskon</span>
        </div>
        <span class="text-[11px] font-bold text-[#0B6E4F] flex items-center gap-1 cursor-pointer select-none">
            <span>Geser</span>
            <span class="animate-pulse">➔</span>
        </span>
    </div>

    <!-- Pembungkus Horizontal Scroll (Mobile First Slider / Carousel) -->
    <div class="flex flex-row overflow-x-auto gap-3 pb-2 scrollbar-none snap-x snap-mandatory -mx-4 px-4 sm:mx-0 sm:px-0">
        @foreach($promoCards as $banner)
            @php
                $cardText = strtolower($banner->title . ' ' . ($banner->content ?? '') . ' ' . ($banner->category ?? ''));
                $icon = match(true) {
                    str_contains($cardText, 'pelajar') || str_contains($cardText, 'siswa') => '🎓',
                    str_contains($cardText, 'keluarga') || str_contains($cardText, 'wali') => '🏠',
                    str_contains($cardText, 'alumni') || str_contains($cardText, 'lulus') => '🎖️',
                    str_contains($cardText, 'bazar') || str_contains($cardText, 'event') || str_contains($cardText, 'promo') => '🎪',
                    default => match($loop->index % 4) {
                        0 => '🎓',
                        1 => '🏠',
                        2 => '🎖️',
                        default => '🎪',
                    }
                };

                $style = match($loop->index % 4) {
                    0 => [
                        'bg'     => 'bg-emerald-50/90 hover:bg-emerald-100/70',
                        'border' => 'border-emerald-200/80',
                        'badge'  => 'text-[#0B6E4F] bg-emerald-100/80',
                        'link'   => 'text-[#0B6E4F]',
                    ],
                    1 => [
                        'bg'     => 'bg-blue-50/90 hover:bg-blue-100/70',
                        'border' => 'border-blue-200/80',
                        'badge'  => 'text-blue-700 bg-blue-100/80',
                        'link'   => 'text-blue-700',
                    ],
                    2 => [
                        'bg'     => 'bg-purple-50/90 hover:bg-purple-100/70',
                        'border' => 'border-purple-200/80',
                        'badge'  => 'text-purple-700 bg-purple-100/80',
                        'link'   => 'text-purple-700',
                    ],
                    default => [
                        'bg'     => 'bg-amber-50/90 hover:bg-amber-100/70',
                        'border' => 'border-amber-200/80',
                        'badge'  => 'text-amber-800 bg-amber-100/80',
                        'link'   => 'text-amber-800',
                    ],
                };

                $cardImg = $banner->image
                    ? (Str::startsWith($banner->image, 'http')
                        ? $banner->image
                        : (Str::startsWith($banner->image, 'images/') ? asset($banner->image) : asset('storage/' . $banner->image)))
                    : null;
            @endphp
            <div class="flex-shrink-0 w-[240px] sm:w-[260px] snap-start rounded-xl {{ $style['bg'] }} border {{ $style['border'] }} p-3 sm:p-3.5 shadow-xs hover:shadow-sm transition-all duration-200 flex items-start justify-between gap-2.5">
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs shrink-0 select-none">{{ $icon }}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $style['badge'] }} px-1.5 py-0.5 rounded-md truncate max-w-full">
                            {{ $banner->category ?: 'Info & Promo' }}
                        </span>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 line-clamp-1 leading-snug">
                        {{ $banner->title }}
                    </h3>
                    @if($banner->content)
                        <p class="text-[10px] text-slate-500 line-clamp-2 leading-relaxed">
                            {{ $banner->content }}
                        </p>
                    @endif
                    @if($banner->link_url)
                        <a href="{{ $banner->link_url }}" class="inline-flex items-center gap-1 text-[10px] font-extrabold {{ $style['link'] }} hover:underline pt-0.5">
                            <span>Detail Promo</span>
                            <span>→</span>
                        </a>
                    @endif
                </div>
                @if($cardImg)
                    <img src="{{ $cardImg }}"
                         alt="{{ $banner->title }}"
                         class="w-11 h-11 rounded-lg object-cover shrink-0 border border-white/80 shadow-2xs">
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif

<!-- ========================================================================
     3. KATALOG KARYA SISWA & FILTER KATEGORI DINAMIS
     ======================================================================== -->
<section id="katalog-produk" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-6 pb-8 sm:pb-10 space-y-6">

    <!-- Baris Filter Kategori & Pengurutan -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg sm:text-xl font-extrabold text-[#0F291E]">Katalog Karya Siswa</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Menampilkan {{ $products->total() }} karya terkurasi beserta jumlah dilihat (👁️), suka (❤️), dan komentar (💬)
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Filter Kategori (Dinamis dari Tabel Categories) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <a href="{{ route('home', ['q' => request('q'), 'sort' => request('sort')]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition {{ !request('category') ? 'bg-[#0B6E4F] text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:border-[#0B6E4F]' }}">
                    <span>Semua</span>
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('home', ['category' => $cat, 'q' => request('q'), 'sort' => request('sort')]) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition {{ request('category') === $cat ? 'bg-[#0B6E4F] text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:border-[#0B6E4F]' }}">
                        <span>{{ $cat }}</span>
                    </a>
                @endforeach
            </div>

            <!-- Dropdown Urutkan -->
            <form action="{{ route('home') }}" method="GET" class="ml-auto md:ml-0">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                <select name="sort" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 focus:border-[#0B6E4F] focus:outline-none cursor-pointer">
                    <option value="rekomendasi" @selected(request('sort', 'rekomendasi') === 'rekomendasi')>Urutkan: Rekomendasi</option>
                    <option value="populer" @selected(request('sort') === 'populer')>Paling Banyak Dilihat (👁️)</option>
                    <option value="disukai" @selected(request('sort') === 'disukai')>Paling Banyak Disukai (❤️)</option>
                    <option value="terbaru" @selected(request('sort') === 'terbaru')>Terbaru</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Grid Kartu Produk Responsif (Mobile 2 Kolom Berjajar Ringkas & Desktop 4 Kolom) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-5">
        @forelse($products as $product)
            @php
                $fallbackImage = asset('images/products/gantungan-kunci-bunga.jpg');
                $cardImage = Str::startsWith($product->image, 'http')
                    ? $product->image
                    : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image));
            @endphp
            <article class="group rounded-xl sm:rounded-2xl bg-white border border-slate-200/80 overflow-hidden hover:shadow-md hover:border-[#0B6E4F]/40 transition-all duration-300 flex flex-col">
                <div class="relative aspect-square w-full bg-slate-100 overflow-hidden" style="aspect-ratio: 1 / 1;">
                    <a href="{{ route('products.show', $product->slug) }}" class="block w-full h-full">
                        <img
                            src="{{ $cardImage }}"
                            alt="{{ $product->product_name }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            style="aspect-ratio: 1 / 1; object-fit: cover;"
                            onerror="this.src='{{ $fallbackImage }}'"
                        >
                    </a>

                    <!-- Tombol Cepat Bagikan Link Produk -->
                    <button type="button"
                            onclick="shareQuickProduct('{{ route('products.show', $product->slug) }}', '{{ addslashes($product->product_name) }}', '{{ $product->formatted_price }}')"
                            class="absolute top-2 left-2 sm:top-3 sm:left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/95 backdrop-blur-xs shadow-xs flex items-center justify-center text-slate-600 hover:text-[#0B6E4F] transition"
                            title="Bagikan link produk">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </button>

                    <form action="{{ route('products.like', $product->slug) }}" method="POST" class="absolute top-2 right-2 sm:top-3 sm:right-3">
                        @csrf
                        <button type="submit" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/95 backdrop-blur-xs shadow-xs flex items-center justify-center text-slate-600 hover:text-rose-500 transition" title="Sukai karya ini">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between space-y-2">
                    <div class="space-y-1 sm:space-y-1.5">
                        <div class="flex items-center justify-between gap-1 sm:gap-2">
                            <span class="inline-block rounded bg-[#EBF9F2] px-1.5 py-0.5 sm:px-2 text-[9px] sm:text-[10px] font-bold text-[#0B6E4F] truncate max-w-[58%]">
                                {{ $product->store->student_class ?: ($product->category ?: 'Karya Siswa') }}
                            </span>
                            <span class="text-[9px] sm:text-[10px] font-medium text-slate-400 truncate max-w-[40%] text-right">
                                {{ $product->store->owner->name ?? $product->store->store_name }}
                            </span>
                        </div>

                        <h3 class="font-bold text-[#0F291E] text-xs sm:text-base leading-snug line-clamp-1 group-hover:text-[#0B6E4F] transition">
                            <a href="{{ route('products.show', $product->slug) }}">
                                {{ $product->product_name }}
                            </a>
                        </h3>

                        <!-- Cuplikan Deskripsi Produk (Diperlihatkan Sedikit / 2 Baris) -->
                        <p class="text-[10px] sm:text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            {{ $product->description ?: 'Karya kreatif dan inovatif buatan siswa SMK Plus Al Hilal.' }}
                        </p>

                        <p class="text-xs sm:text-base font-extrabold text-[#0B6E4F] pt-0.5">
                            {{ $product->formatted_price }}
                        </p>
                    </div>

                    <!-- Baris Bawah: Tombol Action "Pesan" & "Detail" (Hierarki Visual Jelas) serta Interaksi -->
                    <div class="pt-2 sm:pt-2.5 border-t border-slate-100/90 space-y-2">
                        <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                            <!-- Tombol Sekunder: Detail -->
                            <a href="{{ route('products.show', $product->slug) }}"
                               class="inline-flex items-center justify-center gap-1 rounded-lg sm:rounded-xl bg-[#F8FAFC] hover:bg-slate-100 text-slate-700 border border-slate-200/80 py-1.5 sm:py-2 px-2 text-[10px] sm:text-xs font-bold transition text-center hover:text-[#0B6E4F]">
                                <span>Detail</span>
                            </a>

                            <!-- Tombol Utama: Pesan (Lebih Kontras & Mencolok) -->
                            <a href="{{ route('products.show', $product->slug) }}"
                               class="inline-flex items-center justify-center gap-1 rounded-lg sm:rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white shadow-xs hover:shadow-sm py-1.5 sm:py-2 px-2 text-[10px] sm:text-xs font-extrabold transition text-center active:scale-95">
                                <span>💬 Pesan</span>
                            </a>
                        </div>

                        <!-- Metrik Interaksi (Lihat, Suka, Komentar) -->
                        <div class="flex items-center justify-between text-[9px] sm:text-[11px] text-slate-400 font-medium px-0.5">
                            <span class="inline-flex items-center gap-1" title="Dilihat">
                                👁️ {{ number_format($product->views_count ?? 0) }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-rose-500 font-semibold" title="Disukai">
                                ❤️ {{ number_format($product->likes_count ?? 0) }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-slate-500" title="Komentar">
                                💬 {{ number_format($product->comments_count ?? 0) }}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl bg-white border border-slate-200/80 p-10 text-center space-y-2">
                <p class="text-sm font-bold text-[#0F291E]">Belum ada karya produk yang ditemukan</p>
                <p class="text-xs text-slate-500">Silakan reset pencarian atau tambahkan karya baru melalui Dashboard Toko / Super Admin.</p>
            </div>
        @endforelse
    </div>

    @if($products->hasPages())
        <div class="pt-4">
            {{ $products->links() }}
        </div>
    @endif

</section>

<!-- Script Navigasi Manual Baris Iklan Mitra (Otomatis Dihapus sesuai Permintaan) -->
<script>
    (function () {
        const slides = Array.from(document.querySelectorAll('.partner-ad-slide'));
        const dots = Array.from(document.querySelectorAll('.partner-ad-dot'));

        if (slides.length <= 1) return;

        let currentIndex = 0;

        function activateSlide(index) {
            currentIndex = (index + slides.length) % slides.length;

            slides.forEach((slide, idx) => {
                if (idx === currentIndex) {
                    slide.classList.remove('hidden');
                    slide.classList.add('block');
                } else {
                    slide.classList.add('hidden');
                    slide.classList.remove('block');
                }
            });

            dots.forEach((dot, idx) => {
                if (idx === currentIndex) {
                    dot.classList.add('w-6', 'bg-white');
                    dot.classList.remove('w-2', 'bg-white/50');
                } else {
                    dot.classList.remove('w-6', 'bg-white');
                    dot.classList.add('w-2', 'bg-white/50');
                }
            });
        }

        window.goToPartnerAd = function (idx) {
            activateSlide(idx);
        };

        window.stepPartnerAd = function (delta) {
            activateSlide(currentIndex + delta);
        };

        activateSlide(0);
    })();

    // Fungsi Bagikan Cepat Link Produk dari Katalog
    function shareQuickProduct(url, title, price) {
        if (navigator.share) {
            navigator.share({
                title: title,
                text: 'Yuk lihat karya siswa SMK Plus Al Hilal: ' + title + ' (' + price + ') di hilalkraft!',
                url: url,
            }).catch(() => {});
        } else {
            // Salin link ke clipboard
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(showToast).catch(() => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast();
        }

        function showToast() {
            let toast = document.getElementById('quick-share-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'quick-share-toast';
                toast.className = 'fixed bottom-5 left-1/2 -translate-x-1/2 z-[9999] bg-slate-900 text-white text-xs font-semibold px-4 py-2.5 rounded-full shadow-xl flex items-center gap-2 border border-slate-700 transition-all duration-300';
                document.body.appendChild(toast);
            }
            toast.innerHTML = '<span>🔗</span><span>Link produk berhasil disalin ke clipboard!</span>';
            toast.style.opacity = '1';
            toast.style.transform = 'translate(-50%, 0)';
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, 10px)';
            }, 3000);
        }
    }
</script>
@endsection
