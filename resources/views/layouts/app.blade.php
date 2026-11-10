{{--
============================================================================
MASTER LAYOUT: layouts/app.blade.php
============================================================================
Fungsi:
  Menjadi kerangka utama (Master Template) aplikasi hilalkraf yang bersih,
  ringkas, tidak berulang-ulang, dan responsif di seluruh perangkat (Mobile,
  Tablet, dan Desktop). Memuat Top Bar informasi singkat, Navbar utama dengan
  pencarian responsif, area konten utama, serta Footer resmi yang memuat
  identitas kepemilikan SMK Plus Al Hilal (Kepala Lembaga Inkubator Bisnis
  dan Wirausaha Muda SMK PAL) secara proporsional satu kali di bagian bawah.
Algoritma:
  1. Single-Point Institutional Branding: Menampilkan logo `hilalkraf` di
     Navbar dan menampilkan duet logo `hilalkraf` + `SMK Plus Al Hilal` satu
     kali secara elegan pada Footer agar tidak terjadi pengulangan visual.
  2. Responsive Navigation & Search: Menyediakan bilah pencarian untuk layar
     desktop maupun perangkat seluler (mobile).
============================================================================
--}}
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'hilalkraf — Marketplace Karya Siswa SMK Plus Al Hilal')</title>

    @php
        $pageTitle = trim($__env->yieldContent('title', 'hilalkraf — Marketplace Karya Siswa SMK Plus Al Hilal'));
        $ogTitle = trim($__env->yieldContent('og_title', $pageTitle));
        $ogDescription = trim($__env->yieldContent('og_description', 'Marketplace karya inovatif dan produk kreatif siswa SMK Plus Al Hilal (SMK PAL). Dari kerajinan tangan, produk kreatif, hingga teknologi anak bangsa.'));
        $ogImage = trim($__env->yieldContent('og_image', asset('images/og-hilalkraf-square.png') . '?v=4'));
        $ogImageBanner = trim($__env->yieldContent('og_image_banner', asset('images/og-hilalkraf-share.png') . '?v=4'));
        $ogUrl = url()->current();
        $ext = strtolower(pathinfo(parse_url($ogImage, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $ogMime = match($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp'        => 'image/webp',
            default       => 'image/png',
        };
    @endphp

    <!-- Meta SEO & Social Sharing (WhatsApp, Facebook, Twitter, Telegram, LinkedIn) -->
    <meta name="description" content="{{ $ogDescription }}">
    <link rel="canonical" href="{{ $ogUrl }}">

    <!-- Open Graph / WhatsApp Preview Card (HD Ultra-Sharp) -->
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="hilalkraf — SMK Plus Al Hilal">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="{{ $ogMime }}">
    <meta property="og:image:alt" content="{{ $ogTitle }}">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $ogUrl }}">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImageBanner }}">

    <!-- Logo Resmi hilalkraf pada Tab Bar Browser (Favicon & Mobile Touch Icon) -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=4">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=4">
    <link rel="icon" type="image/png" href="{{ asset('images/og-hilalkraf-square.png') }}?v=4">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=4">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('apple-touch-icon.png') }}?v=4">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN + Custom Config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        hilal: {
                            50: '#EBF9F2',
                            100: '#D5F5E6',
                            500: '#10B981',
                            600: '#0B6E4F',
                            700: '#08583F',
                            900: '#0F291E',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen flex flex-col bg-[#F8FAFC] text-[#0F291E] antialiased selection:bg-[#0B6E4F] selection:text-white">

    @unless(View::hasSection('hide_navbar'))
        @php
            $topbarLeft = \App\Models\CmsContent::val('topbar_left', 'Dari ruang kelas, untuk kehidupan sehari-hari.');
            $topbarRight = \App\Models\CmsContent::val('topbar_right', 'SMK Plus Al Hilal • Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL');
            $navCategories = \Illuminate\Support\Facades\Schema::hasTable('categories')
                ? \App\Models\Category::orderBy('name')->pluck('name')
                : collect();
            $cartCount = count(session('hilalkraft_cart', []));
        @endphp

        <!-- Topbar Ringkas (Terintegrasi CMS Super Admin) -->
        @if($topbarLeft !== '' || $topbarRight !== '')
            <div class="bg-[#E6F7F0] text-[#0B6E4F] text-[11px] py-1.5 px-4 border-b border-[#D0EFE2]">
                <div class="max-w-7xl mx-auto flex items-center justify-between gap-2">
                    <p class="font-medium truncate">{{ $topbarLeft }}</p>
                    @if($topbarRight !== '')
                        <p class="hidden md:block font-semibold text-[#0B6E4F] truncate">{{ $topbarRight }}</p>
                    @endif
                </div>
            </div>
        @endif

        <!-- Navbar Utama Responsif & Simpel -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 space-y-2.5 lg:space-y-0">
                <div class="flex items-center justify-between gap-3 sm:gap-4">

                    <!-- Kiri: Brand hilalkraf Simpel & Jelas -->
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 group">
                        <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                             alt="Logo hilalkraf"
                             class="h-10 sm:h-11 w-auto object-contain rounded-xl border border-emerald-500/20 bg-white px-2 py-0.5 shadow-2xs">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-lg sm:text-xl font-extrabold tracking-tight text-[#0F291E] leading-none">
                                    hilalkraf
                                </span>
                                <span class="rounded bg-[#EBF9F2] px-1.5 py-0.5 text-[9px] font-extrabold text-[#0B6E4F]">
                                    SMK PAL
                                </span>
                            </div>
                            <p class="text-[10px] font-medium text-slate-500 hidden sm:block mt-0.5">
                                Karya Siswa Berkah Bersama
                            </p>
                        </div>
                    </a>

                    <!-- Tengah (Desktop): Bilah Pencarian Terpadu -->
                    <form action="{{ route('home') }}" method="GET" class="flex-1 max-w-xl mx-2 hidden lg:flex items-center rounded-xl border border-slate-200 bg-[#F8FAFC] focus-within:bg-white focus-within:border-[#0B6E4F] focus-within:ring-2 focus-within:ring-emerald-500/10 transition overflow-hidden">
                        <div class="pl-3.5 pr-2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Cari karya siswa, produk, atau nama toko..."
                            class="w-full bg-transparent py-2 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none"
                        >
                        @if($navCategories->count() > 0)
                            <div class="border-l border-slate-200 px-3">
                                <select name="category" onchange="this.form.submit()" class="bg-transparent text-xs font-medium text-slate-600 focus:outline-none py-1.5 cursor-pointer">
                                    <option value="">Semua kategori</option>
                                    @foreach($navCategories as $navCat)
                                        <option value="{{ $navCat }}" @selected(request('category') === $navCat)>{{ $navCat }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <button type="submit" class="bg-[#0B6E4F] text-white px-4 py-2 text-xs font-semibold hover:bg-[#08583F] transition">
                            Cari
                        </button>
                    </form>

                    <!-- Kanan: Ikon Keranjang & Navigasi Akun -->
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ auth()->check() ? route('buyer.cart.index') : route('login') }}"
                           class="relative p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition" title="Keranjang Belanja">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            @if($cartCount > 0)
                                <span class="absolute top-1 right-1 w-4 h-4 rounded-full bg-[#0B6E4F] text-white text-[10px] font-bold flex items-center justify-center">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </a>

                        @guest
                            <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Masuk
                            </a>
                            <a href="{{ route('register') }}" class="rounded-xl bg-[#0B6E4F] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                                Daftar
                            </a>
                        @else
                            @php
                                $dashboardRoute = match(auth()->user()->role) {
                                    'super_admin', 'admin' => route('admin.dashboard'),
                                    'seller' => route('seller.dashboard'),
                                    default => route('buyer.dashboard'),
                                };
                                $roleBadge = match(auth()->user()->role) {
                                    'super_admin' => 'Super Admin',
                                    'admin' => 'Admin (Guru)',
                                    'seller' => 'Toko Siswa',
                                    default => 'Pembeli',
                                };
                            @endphp

                            @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/30 bg-white px-2.5 sm:px-3 py-1.5 text-xs font-bold text-[#0B6E4F] hover:bg-emerald-50 transition shadow-2xs" title="Kelola Toko Guru Saya (Produk & Pesanan)">
                                    <span>🏪</span>
                                    <span class="hidden xs:inline">Toko Guru</span>
                                </a>
                            @endif

                            <a href="{{ $dashboardRoute }}" class="flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-[#EBF9F2] px-3 py-1.5 hover:bg-[#D5F5E6] transition">
                                <div class="w-6 h-6 rounded-lg bg-[#0B6E4F] text-white font-bold text-[11px] flex items-center justify-center">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ Str::limit(auth()->user()->name, 14) }}</p>
                                    <p class="text-[10px] font-medium text-[#0B6E4F] mt-0.5">{{ $roleBadge }}</p>
                                </div>
                            </a>

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 rounded-xl border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 transition" title="Keluar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                </button>
                            </form>
                        @endguest
                    </div>
                </div>

                <!-- Baris Pencarian Mobile & Tablet -->
                <form action="{{ route('home') }}" method="GET" class="flex lg:hidden items-center rounded-xl border border-slate-200 bg-[#F8FAFC] focus-within:bg-white focus-within:border-[#0B6E4F] overflow-hidden">
                    <div class="pl-3 pr-2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari karya siswa atau nama toko..."
                        class="w-full bg-transparent py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none"
                    >
                    <button type="submit" class="bg-[#0B6E4F] text-white px-3.5 py-2 text-xs font-semibold">
                        Cari
                    </button>
                </form>
            </div>
        </header>
    @endunless

    <!-- Flash Notifications -->
    @if(session('success') || session('error') || (isset($errors) && $errors->any()))
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
                <div class="rounded-xl bg-[#EBF9F2] border border-[#B8E8D2] px-4 py-2.5 text-xs sm:text-sm text-[#0B6E4F] font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs sm:text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if(isset($errors) && $errors->any())
                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs sm:text-sm text-rose-800">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    @unless(View::hasSection('hide_footer'))
        <!-- Footer Resmi yang Rapi & Tidak Berulang (Logo hilalkraf + Logo SMK Plus Al Hilal) -->
        <footer class="bg-white border-t border-slate-200/80 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-8 border-b border-slate-100 items-start">

                    <!-- Kolom 1: Logo hilalkraf + Logo SMK Plus Al Hilal (Tampil Satu Kali dengan Jelas) -->
                    <div class="md:col-span-6 space-y-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 shadow-2xs flex items-center gap-2.5">
                                <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                                     alt="Logo hilalkraf"
                                     class="h-11 sm:h-12 w-auto object-contain">
                                <span class="h-8 w-[1px] bg-slate-200"></span>
                                <img src="{{ asset('images/logo-smk-alhilal.png') }}"
                                     alt="Logo SMK Plus Al Hilal Arjawinangun"
                                     class="h-10 sm:h-11 w-10 sm:w-11 object-contain">
                            </div>
                            <div>
                                <span class="text-base sm:text-lg font-extrabold tracking-tight text-[#0F291E] block leading-tight">
                                    hilalkraf — SMK Plus Al Hilal
                                </span>
                                <span class="text-[11px] font-bold text-[#0B6E4F]">
                                    {{ \App\Models\CmsContent::val('navbar_subtitle', 'Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL') }}
                                </span>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed max-w-xl">
                            {{ \App\Models\CmsContent::val('footer_description', 'Platform resmi hilalkraf (Karya Siswa Berkah Bersama) milik SMK Plus Al Hilal Arjawinangun, khususnya di bawah naungan Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL.') }}
                        </p>
                    </div>

                    <!-- Kolom 2: Navigasi Cepat -->
                    <div class="md:col-span-3 space-y-2 text-xs">
                        <h4 class="font-bold text-[#0F291E]">Navigasi</h4>
                        <ul class="space-y-1.5 text-slate-500">
                            <li><a href="{{ route('home') }}" class="hover:text-[#0B6E4F] transition">Beranda Katalog</a></li>
                            @guest
                                <li><a href="{{ route('register') }}" class="hover:text-[#0B6E4F] transition">Daftar Akun / Buka Toko</a></li>
                                <li><a href="{{ route('login') }}" class="hover:text-[#0B6E4F] transition">Portal Masuk</a></li>
                            @else
                                <li><a href="{{ $dashboardRoute ?? route('home') }}" class="hover:text-[#0B6E4F] transition">Dashboard Saya</a></li>
                            @endguest
                        </ul>
                    </div>

                    <!-- Kolom 3: Kontak Resmi -->
                    <div class="md:col-span-3 space-y-2 text-xs text-slate-500">
                        <h4 class="font-bold text-[#0F291E]">Kontak Inkubator SMK PAL</h4>
                        <p>Instagram: <span class="text-slate-700 font-medium">{{ \App\Models\CmsContent::val('footer_instagram', '@hilalkraf.smkpal') }}</span></p>
                        <p>WhatsApp: <span class="text-slate-700 font-medium">{{ \App\Models\CmsContent::val('footer_whatsapp', '+62 821-2938-0141') }}</span></p>
                        <p>Email: <span class="text-slate-700 font-medium">{{ \App\Models\CmsContent::val('footer_email', 'inkubator@hilalkraf.sch.id') }}</span></p>
                    </div>
                </div>

                <div class="pt-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-400">
                    <p>&copy; {{ date('Y') }} <strong class="text-slate-600">hilalkraf</strong> • SMK Plus Al Hilal Arjawinangun.</p>
                    <p class="text-[#0B6E4F] font-semibold">Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</p>
                </div>
            </div>
        </footer>
    @endunless

</body>
</html>
