{{--
============================================================================
MASTER LAYOUT: layouts/app.blade.php
============================================================================
Fungsi:
  Menjadi kerangka utama (Master Template) seluruh halaman aplikasi hilalkraft
  yang memuat Header/Navbar (Logo Uploader di kiri, Search Bar di tengah,
  Menu Role/Login di kanan), Notifikasi Flash Session, Area Konten Dinamis,
  serta Footer resmi SMK Plus Al Hilal.
Algoritma:
  1. Logo Fallback Handling (`onerror`): Memuat `images/logo-hilalkraft.jpg` pada
     slot `#logo-uploader-slot`. Apabila gambar gagal dimuat, event handler
     menyembunyikan elemen `<img>` dan menampilkan lencana inisial `HK`.
  2. Role-Based Navigation Dispatch (`match(auth()->user()->role)`): Menentukan
     tautan tombol profil dan lencana role secara dinamis berdasarkan role user
     yang sedang login, atau menampilkan tombol Masuk/Daftar jika statusnya Guest.
============================================================================
--}}
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'hilalkraft — Marketplace Karya Siswa SMK Plus Al Hilal')</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN + Custom Config (Fallback siap pakai tanpa build & mendukung Vite) -->
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
                            50: '#ECFDF5',
                            100: '#D1FAE5',
                            500: '#10B981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064E3B',
                            gold: '#F59E0B',
                        },
                        surface: '#F8FAFC',
                    }
                }
            }
        }
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 antialiased selection:bg-[#10B981] selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-[#10B981] to-[#059669] text-white text-xs py-2 px-4">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <p class="font-medium flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-amber-300 animate-pulse"></span>
                <span><strong>hilalkraft</strong> — Karya Siswa Berkah Bersama • Platform Resmi Wirausaha Siswa SMK Plus Al Hilal</span>
            </p>
            <div class="hidden sm:flex items-center gap-4 text-emerald-50">
                <a href="{{ route('home') }}" class="hover:text-white transition">Jelajah Katalog</a>
                <span>•</span>
                <a href="{{ route('register') }}" class="hover:text-white transition">Buka Toko Siswa</a>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-emerald-500/15 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">

            <!-- Kiri: [LOGO_UPLOADER] Tempat Penampung Logo Aplikasi -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0 group" title="hilalkraft - Karya Siswa Berkah Bersama">
                <div id="logo-uploader-slot" class="relative w-12 h-12 rounded-2xl overflow-hidden border border-emerald-500/20 bg-white shadow-sm flex items-center justify-center p-0.5 group-hover:border-[#10B981] transition">
                    <!-- [LOGO_UPLOADER] Menggunakan logo hilalkraf yang diunggah atau fallback icon -->
                    <img src="{{ asset('images/logo-hilalkraft.jpg') }}"
                         alt="Logo hilalkraft"
                         class="w-full h-full object-contain"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="hidden w-full h-full items-center justify-center bg-gradient-to-br from-[#10B981] to-[#059669] text-white font-extrabold text-lg rounded-xl">
                        HK
                    </div>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-[#10B981] to-[#059669] bg-clip-text text-transparent">
                        hilalkraft
                    </span>
                    <span class="block text-[11px] font-medium text-slate-500 -mt-1">
                        SMK Plus Al Hilal
                    </span>
                </div>
            </a>

            <!-- Tengah: Bilah Pencarian Produk -->
            <form action="{{ route('home') }}" method="GET" class="flex-1 max-w-xl mx-2 hidden md:block">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex items-center">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari karya siswa, kerajinan, kuliner, atau nama toko..."
                        class="w-full rounded-2xl border border-slate-200 bg-[#F8FAFC] pl-11 pr-24 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition focus:border-[#10B981] focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                    >
                    <svg class="w-5 h-5 text-slate-400 absolute left-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <button type="submit" class="absolute right-1.5 rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-4 py-1.5 text-xs font-semibold text-white hover:opacity-95 transition">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Kanan: Tombol Login/Register atau Profil User -->
            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                @guest
                    <a href="{{ route('login') }}" class="rounded-xl border border-emerald-500/30 bg-white px-4 py-2.5 text-sm font-semibold text-[#059669] hover:bg-emerald-50 transition">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-500/20 hover:opacity-95 transition">
                        Daftar Akun
                    </a>
                @else
                    @if(auth()->user()->role === 'buyer')
                        @php $cartCount = count(session('hilalkraft_cart', [])); @endphp
                        <a href="{{ route('buyer.cart.index') }}" class="relative p-2.5 rounded-xl bg-emerald-50 text-[#059669] hover:bg-emerald-100 transition" title="Keranjang Belanja">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            @if($cartCount > 0)
                                <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-amber-500 text-white text-[11px] font-bold flex items-center justify-center">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </a>
                    @endif

                    @php
                        $dashboardRoute = match(auth()->user()->role) {
                            'super_admin', 'admin' => route('admin.dashboard'),
                            'seller' => route('seller.dashboard'),
                            default => route('buyer.dashboard'),
                        };
                        $roleBadge = match(auth()->user()->role) {
                            'super_admin' => 'Super Admin',
                            'admin' => 'Admin Sekolah',
                            'seller' => 'Siswa Kreator',
                            default => 'Pembeli',
                        };
                    @endphp

                    <a href="{{ $dashboardRoute }}" class="flex items-center gap-2.5 rounded-xl border border-emerald-500/20 bg-emerald-50/60 px-3.5 py-2 hover:bg-emerald-100/70 transition">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#10B981] to-[#059669] text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-bold text-slate-800 leading-none">{{ Str::limit(auth()->user()->name, 16) }}</p>
                            <p class="text-[10px] font-semibold text-[#059669] mt-0.5">{{ $roleBadge }}</p>
                        </div>
                    </a>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2.5 rounded-xl border border-slate-200 text-slate-500 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition" title="Keluar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                @endguest
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div class="md:hidden px-4 pb-3">
            <form action="{{ route('home') }}" method="GET" class="relative flex items-center">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari produk karya siswa..."
                    class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] pl-4 pr-20 py-2 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none"
                >
                <button type="submit" class="absolute right-1.5 rounded-lg bg-gradient-to-r from-[#10B981] to-[#059669] px-3 py-1 text-xs font-semibold text-white">
                    Cari
                </button>
            </form>
        </div>
    </header>

    <!-- Flash Notifications -->
    @if(session('success') || session('error') || $errors->any())
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc list-inside space-y-1">
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

    <!-- Footer SMK Plus Al Hilal -->
    <footer class="bg-white border-t border-emerald-500/15 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-hilalkraft.jpg') }}" alt="hilalkraft" class="w-10 h-10 rounded-xl object-contain border border-emerald-100">
                        <div>
                            <h3 class="text-lg font-extrabold bg-gradient-to-r from-[#10B981] to-[#059669] bg-clip-text text-transparent">hilalkraft</h3>
                            <p class="text-xs text-slate-500">Karya Siswa Berkah Bersama</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 leading-relaxed max-w-md">
                        Platform e-commerce resmi untuk memamerkan, memasarkan, dan mengapresiasi karya kreatif serta produk kewirausahaan siswa-siswi <strong>SMK Plus Al Hilal</strong>.
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-3">Navigasi Cepat</h4>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li><a href="{{ route('home') }}" class="hover:text-[#059669] transition">Beranda Katalog</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-[#059669] transition">Registrasi Toko Siswa</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-[#059669] transition">Registrasi Pembeli</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-[#059669] transition">Portal Login</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-3">SMK Plus Al Hilal</h4>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li>Unit Produksi & Kewirausahaan Siswa</li>
                        <li>Email: info@hilalkraft.sch.id</li>
                        <li>Jam Layanan: Senin – Sabtu (08.00 – 16.00 WIB)</li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <p>&copy; {{ date('Y') }} <strong>hilalkraft</strong> — SMK Plus Al Hilal. Hak Cipta Dilindungi.</p>
                <p>Dibangun dengan Laravel 11 & Tailwind CSS Emerald Theme</p>
            </div>
        </div>
    </footer>

</body>
</html>
