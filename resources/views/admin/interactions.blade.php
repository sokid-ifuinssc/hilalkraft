{{--
============================================================================
VIEW: AUDIT INTERAKSI & DETEKSI AKUN TERHUBUNG (admin/interactions.blade.php)
============================================================================
Fungsi:
  1. Menampilkan rincian siapa saja yang berkomentar, menyukai (like),
     dan melihat (views) produk & toko karya siswa.
  2. Radar Cerdas Deteksi Akun Terhubung:
     - 🚨 Terhubung: Pemilik Toko Sendiri (Self-Like / Self-Comment)
     - ⚠️ Terhubung: Alamat IP Sama dengan Toko (Multi-Akun / Perangkat Sama)
     - 🎓 Terhubung: Teman Sekelas / Sejurusan Siswa (SMK Plus Al Hilal)
     - 👨‍👩‍👧 Terhubung: Akun Keluarga Siswa
     - 👨‍🏫 Dewan Guru / Pembimbing SMK
  3. Menu Reset Interaksi:
     - Hapus individual per-komentar, per-suka, per-kunjungan.
     - Reset interaksi spesifik per-produk (Dilihat, Suka, Komentar).
     - Reset interaksi per-toko.
     - Reset massal sistem dengan pencatatan otomatis ke Activity Log.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Audit Interaksi & Deteksi Akun — hilalkraf SMK Plus Al Hilal')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Sidebar Kiri Administrasi -->
    <aside class="w-64 bg-white border-r border-slate-200/80 hidden lg:flex flex-col justify-between shrink-0 sticky top-0 h-screen p-6 overflow-y-auto">
        <div class="space-y-6">
            <a href="{{ route('home') }}" class="flex flex-col gap-2 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-2.5 py-1.5 shadow-xs">
                    <img src="{{ asset('images/logo-hilalkraf.jpg') }}" alt="hilalkraf" class="h-10 w-auto object-contain">
                    <span class="h-6 w-[1px] bg-slate-200"></span>
                    <img src="{{ asset('images/logo-smk-alhilal.png') }}" alt="SMK Plus Al Hilal" class="h-8 w-8 object-contain">
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                    <span class="text-[10px] font-bold text-[#0B6E4F]">Inkubator Bisnis SMK PAL</span>
                </div>
            </a>

            <div class="space-y-1">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">PANEL AUDIT & KONTROL</p>
                <p class="text-xs font-extrabold text-[#0F291E]">SMK Plus Al Hilal</p>
                <span class="inline-block rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F] mt-1">
                    {{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin (Guru)' }}
                </span>
            </div>

            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>⊞</span>
                    <span>Dashboard Utama</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>📊</span>
                    <span>Laporan & Rekap</span>
                </a>
                <a href="{{ route('admin.logs') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>📋</span>
                    <span>Log Akses & Aktivitas</span>
                </a>
                <a href="{{ route('admin.interactions') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold shadow-2xs">
                    <span>🔍</span>
                    <span>Audit Interaksi & Reset</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-cms" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>✨</span>
                    <span>CMS Konten Website</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-manajemen-user" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>👥</span>
                    <span>Manajemen User</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-kelola-toko" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🏪</span>
                    <span>Kelola & Peringkat Toko</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-bazar-event" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🎪</span>
                    <span>Bazar & Diskon SMK</span>
                </a>
            </nav>
        </div>

        <div class="space-y-3 pt-4 border-t border-slate-100 text-xs">
            <a href="{{ route('home') }}" class="block text-slate-500 hover:text-[#0B6E4F] transition">↗ Lihat Beranda Publik</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-rose-600 font-semibold hover:underline">Keluar dari Akun</button>
            </form>
        </div>
    </aside>

    <!-- Konten Utama Audit Interaksi -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar Header -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-10 flex items-center justify-between gap-4 sticky top-0 z-20">
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="font-bold text-[#0B6E4F]">← Kembali ke Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Audit Interaksi & Deteksi Akun</span>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="openBulkResetModal()" class="rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition shadow-2xs flex items-center gap-1.5">
                    <span>🔄</span>
                    <span class="hidden sm:inline">Reset Massal Sistem</span>
                </button>

                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin (Guru)' }}</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Area Konten Utama -->
        <main class="p-6 sm:p-10 space-y-6 max-w-7xl w-full mx-auto">

            <!-- Banner Notifikasi Sukses / Error -->
            @if(session('success'))
                <div class="rounded-2xl bg-[#EBF9F2] border border-[#B8E8D2] p-4 text-xs font-bold text-[#0B6E4F] flex items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base">✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-[#0B6E4F] hover:opacity-70 text-base">✕</button>
                </div>
            @endif

            <!-- Header Halaman & Deskripsi -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-extrabold text-[#0B6E4F] mb-1">
                        <span>🔍 AUDIT & DETEKSI AKUN TERHUBUNG</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-[#0F291E]">Audit Komentar, Suka & Pengunjung</h1>
                    <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                        Pantau secara transparan siapa saja yang berinteraksi pada produk karya siswa. Dilengkapi radar pendeteksi interaksi akun pemilik sendiri, teman sekelas, maupun alamat IP yang sama, serta opsi reset interaksi.
                    </p>
                </div>

                @if($selectedProduct)
                    <div class="rounded-2xl bg-white border border-emerald-300 p-3.5 shadow-xs flex items-center gap-3">
                        <img src="{{ $selectedProduct->image_url }}" alt="{{ $selectedProduct->product_name }}" class="w-12 h-12 rounded-xl object-cover shrink-0 border border-slate-100">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase text-[#0B6E4F]">Filter Produk Terpilih:</span>
                            <p class="text-xs font-extrabold text-[#0F291E] line-clamp-1">{{ $selectedProduct->product_name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $selectedProduct->store->store_name ?? 'Toko' }} ({{ $selectedProduct->store->owner->name ?? '-' }})</p>
                        </div>
                        <button type="button" onclick="openProductResetModal({{ $selectedProduct->id }}, '{{ addslashes($selectedProduct->product_name) }}', {{ $selectedProduct->views_count }}, {{ $selectedProduct->likes_count }}, {{ $selectedProduct->comments()->count() }})" class="rounded-lg bg-rose-50 border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-700 hover:bg-rose-100 transition shrink-0 ml-2">
                            🔄 Reset Produk Ini
                        </button>
                    </div>
                @endif
            </div>

            <!-- 4 Kartu KPI Ringkasan Interaksi -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Komentar -->
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider">Total Komentar</span>
                        <span class="text-lg">💬</span>
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold text-[#0F291E]">{{ number_format($stats['total_comments']) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Ulasan dari pengunjung berakun</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between text-[11px]">
                        <a href="{{ route('admin.interactions', ['tab' => 'comments']) }}" class="font-bold text-[#0B6E4F] hover:underline">Lihat Rincian →</a>
                    </div>
                </div>

                <!-- 2. Suka (Likes) -->
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider">Total Suka (Likes)</span>
                        <span class="text-lg">❤️</span>
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold text-rose-600">{{ number_format($stats['total_likes']) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Apresiasi suka pada karya</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between text-[11px]">
                        <a href="{{ route('admin.interactions', ['tab' => 'likes']) }}" class="font-bold text-[#0B6E4F] hover:underline">Lihat Rincian →</a>
                    </div>
                </div>

                <!-- 3. Dilihat (Views) -->
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider">Total Dilihat (Views)</span>
                        <span class="text-lg">👁️</span>
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold text-[#0B6E4F]">{{ number_format($stats['total_views']) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi produk & toko</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between text-[11px]">
                        <a href="{{ route('admin.interactions', ['tab' => 'views']) }}" class="font-bold text-[#0B6E4F] hover:underline">Lihat Log Tamu →</a>
                    </div>
                </div>

                <!-- 4. Radar Deteksi Akun -->
                <div class="rounded-2xl bg-gradient-to-br from-[#0B6E4F] to-[#08583F] p-5 shadow-xs text-white flex flex-col justify-between">
                    <div class="flex items-center justify-between text-emerald-200 mb-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider">Radar Deteksi</span>
                        <span class="text-lg">🛡️</span>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold">Audit Akun Terhubung</p>
                        <p class="text-[11px] text-emerald-100 mt-1 leading-snug">
                            Mendeteksi interaksi pemilik toko sendiri, teman sekelas, dan alamat IP yang sama secara otomatis.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-emerald-600/60 mt-2">
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-200">
                            <span>●</span>
                            <span>Sistem Audit Aktif</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Toolbar Filter & Tab Navigasi -->
            <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-xs">
                <!-- Baris Tab Interaksi -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <a href="{{ route('admin.interactions', array_merge(request()->except(['tab', 'page', 'comments_page', 'likes_page', 'views_page']), ['tab' => 'all'])) }}"
                           class="rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $tab === 'all' ? 'bg-[#0B6E4F] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            📋 Semua Interaksi
                        </a>
                        <a href="{{ route('admin.interactions', array_merge(request()->except(['tab', 'page', 'comments_page', 'likes_page', 'views_page']), ['tab' => 'comments'])) }}"
                           class="rounded-xl px-3.5 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $tab === 'comments' ? 'bg-[#0B6E4F] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <span>💬 Komentar</span>
                            <span class="rounded-full bg-black/10 px-1.5 py-0.2 text-[10px]">{{ $stats['total_comments'] }}</span>
                        </a>
                        <a href="{{ route('admin.interactions', array_merge(request()->except(['tab', 'page', 'comments_page', 'likes_page', 'views_page']), ['tab' => 'likes'])) }}"
                           class="rounded-xl px-3.5 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $tab === 'likes' ? 'bg-[#0B6E4F] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <span>❤️ Suka (Likes)</span>
                            <span class="rounded-full bg-black/10 px-1.5 py-0.2 text-[10px]">{{ $stats['total_likes'] }}</span>
                        </a>
                        <a href="{{ route('admin.interactions', array_merge(request()->except(['tab', 'page', 'comments_page', 'likes_page', 'views_page']), ['tab' => 'views'])) }}"
                           class="rounded-xl px-3.5 py-2 text-xs font-bold transition flex items-center gap-1.5 {{ $tab === 'views' ? 'bg-[#0B6E4F] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            <span>👁️ Riwayat Dilihat</span>
                            <span class="rounded-full bg-black/10 px-1.5 py-0.2 text-[10px]">{{ $stats['total_visitor_logs'] }}</span>
                        </a>
                    </div>

                    @if(request()->filled('product_id') || request()->filled('store_id') || request()->filled('q'))
                        <a href="{{ route('admin.interactions', ['tab' => $tab]) }}" class="text-xs font-bold text-rose-600 hover:underline flex items-center gap-1">
                            <span>✕</span>
                            <span>Reset Filter Pencarian</span>
                        </a>
                    @endif
                </div>

                <!-- Form Filter Multi-Kriteria -->
                <form method="GET" action="{{ route('admin.interactions') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <!-- 1. Filter Produk -->
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Filter Karya Siswa (Produk)</label>
                        <select name="product_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Semua Produk Karya</option>
                            @foreach($allProducts as $p)
                                <option value="{{ $p->id }}" @selected($productId == $p->id)>{{ Str::limit($p->product_name, 35) }} ({{ $p->store->store_name ?? 'Toko' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Filter Toko Siswa -->
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Filter Toko Siswa</label>
                        <select name="store_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Semua Toko</option>
                            @foreach($allStores as $s)
                                <option value="{{ $s->id }}" @selected($storeId == $s->id)>{{ $s->store_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Pencarian Kata Kunci -->
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Cari Akun, NISN, Produk, atau Alamat IP</label>
                        <div class="flex gap-2">
                            <input type="search" name="q" value="{{ $search }}" placeholder="Ketik nama pengulas, email, NISN, IP address, teks komentar..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                            <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shrink-0 shadow-xs">
                                Cari
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- =================================================================
                 TAB 1: AUDIT & DAFTAR KOMENTAR PRODUK
                 ================================================================= --}}
            @if($tab === 'all' || $tab === 'comments' || $tab === 'connected')
                <div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-xs space-y-0">
                    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="text-base">💬</span>
                            <div>
                                <h2 class="text-sm font-extrabold text-[#0F291E]">Daftar Komentar & Ulasan Produk</h2>
                                <p class="text-[11px] text-slate-400">Rincian identitas akun pengomentar, rating bintang, dan deteksi keterhubungan dengan pemilik karya.</p>
                            </div>
                        </div>

                        @if($comments && $comments->total() > 0)
                            <span class="rounded-full bg-emerald-50 text-[#0B6E4F] font-bold text-[11px] px-3 py-1 border border-emerald-200">
                                {{ $comments->total() }} Komentar Ditemukan
                            </span>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#F8FAFC] text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-200/80">
                                <tr>
                                    <th class="py-3 px-4">KARYA PRODUK & TOKO</th>
                                    <th class="py-3 px-4">AKUN PENGOMENTAR</th>
                                    <th class="py-3 px-4">IDENTITAS SISWA & WILAYAH</th>
                                    <th class="py-3 px-4">DETEKSI KETERHUBUNGAN</th>
                                    <th class="py-3 px-4">RATING & KOMENTAR</th>
                                    <th class="py-3 px-4">WAKTU & IP</th>
                                    <th class="py-3 px-4 text-right">AKSI RESET</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($comments as $c)
                                    @php
                                        $cUser = $c->user;
                                        $cProd = $c->product;
                                        $cStore = $cProd?->store;
                                        $cOwner = $cStore?->owner;

                                        // Deteksi Keterhubungan
                                        $isSelf = $cUser && $cStore && ($cUser->id === $cStore->user_id);
                                        $isSameClass = $cUser && $cOwner && $cUser->id !== $cOwner->id && $cUser->class_name && ($cUser->class_name === $cOwner->class_name);
                                        $isSameMajor = $isSameClass && $cUser->major_name && ($cUser->major_name === $cOwner->major_name);
                                        $isFamily = $cUser && $cUser->user_subtype === 'keluarga';
                                        $isTeacher = $cUser && ($cUser->is_teacher || in_array($cUser->role, ['admin', 'super_admin']));
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition {{ $isSelf ? 'bg-rose-50/30' : '' }}">
                                        <!-- Produk & Toko -->
                                        <td class="py-3.5 px-4">
                                            @if($cProd)
                                                <div class="flex items-center gap-2.5">
                                                    <img src="{{ $cProd->image_url }}" alt="{{ $cProd->product_name }}" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-slate-200">
                                                    <div>
                                                        <a href="{{ route('products.show', $cProd->slug) }}" target="_blank" class="font-bold text-[#0F291E] hover:text-[#0B6E4F] line-clamp-1">
                                                            {{ $cProd->product_name }}
                                                        </a>
                                                        <p class="text-[10px] text-slate-400">
                                                            🏪 {{ $cStore->store_name ?? 'Toko' }} ({{ $cOwner->name ?? '-' }})
                                                        </p>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-slate-400 italic">Produk ID #{{ $c->product_id }}</span>
                                            @endif
                                        </td>

                                        <!-- Akun Pengomentar -->
                                        <td class="py-3.5 px-4">
                                            @if($cUser)
                                                <div class="space-y-0.5">
                                                    <p class="font-bold text-[#0F291E] flex items-center gap-1.5">
                                                        <span>{{ $cUser->name }}</span>
                                                        <span class="rounded bg-slate-100 text-slate-600 text-[9px] font-bold px-1.5 py-0.2 uppercase">
                                                            {{ $cUser->user_subtype ?? $cUser->role }}
                                                        </span>
                                                    </p>
                                                    <p class="text-[10px] text-slate-400">{{ $cUser->email }}</p>
                                                    @if($cUser->phone)
                                                        <p class="text-[9px] text-slate-400 font-mono">📱 {{ $cUser->phone }}</p>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-slate-400 italic">User ID #{{ $c->user_id }}</span>
                                            @endif
                                        </td>

                                        <!-- Identitas Siswa & Wilayah -->
                                        <td class="py-3.5 px-4">
                                            @if($cUser && ($cUser->class_name || $cUser->major_name))
                                                <span class="inline-flex items-center gap-1 rounded bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                                                    <span>🎓</span>
                                                    <span>Kelas {{ $cUser->class_name }} {{ $cUser->major_name ? '• ' . $cUser->major_name : '' }}</span>
                                                </span>
                                                @if($cUser->nis_nisn)
                                                    <p class="text-[9px] text-slate-400 font-mono mt-0.5">NISN: {{ $cUser->nis_nisn }}</p>
                                                @endif
                                            @elseif($cUser && $cUser->is_teacher)
                                                <span class="rounded bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold px-2 py-0.5">
                                                    👨‍🏫 Guru SMK {{ $cUser->major_name ? '• ' . $cUser->major_name : '' }}
                                                </span>
                                            @elseif($cUser && $cUser->user_subtype === 'keluarga')
                                                <span class="rounded bg-purple-50 text-purple-800 text-[10px] font-bold px-2 py-0.5">
                                                    👨‍👩‍👧 Keluarga: {{ $cUser->family_student_name ?? '-' }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-[11px] italic">Umum</span>
                                            @endif

                                            @if($cUser && ($cUser->kabupaten || $cUser->kecamatan))
                                                <p class="text-[10px] text-slate-500 mt-1">
                                                    📍 {{ $cUser->kabupaten ? $cUser->kabupaten : '' }}{{ $cUser->kecamatan ? ', Kec. ' . $cUser->kecamatan : '' }}
                                                </p>
                                            @endif
                                        </td>

                                        <!-- Radar Deteksi Keterhubungan -->
                                        <td class="py-3.5 px-4">
                                            @if($isSelf)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 border border-rose-300 px-2 py-0.5 text-[10px] font-extrabold text-rose-700 animate-pulse">
                                                    <span>🚨</span>
                                                    <span>PEMILIK TOKO SENDIRI</span>
                                                </span>
                                                <p class="text-[9px] text-rose-600 mt-0.5">Komentar pada dagangannya sendiri</p>
                                            @elseif($isSameMajor)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 border border-emerald-300 px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                                                    <span>🎓</span>
                                                    <span>Teman Sekelas & Sejurusan</span>
                                                </span>
                                            @elseif($isSameClass)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-teal-100 border border-teal-300 px-2 py-0.5 text-[10px] font-bold text-teal-800">
                                                    <span>🎓</span>
                                                    <span>Satu Tingkat (Kelas {{ $cUser->class_name }})</span>
                                                </span>
                                            @elseif($isFamily)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-purple-100 border border-purple-300 px-2 py-0.5 text-[10px] font-bold text-purple-800">
                                                    <span>👨‍👩‍👧</span>
                                                    <span>Akun Keluarga Siswa</span>
                                                </span>
                                            @elseif($isTeacher)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-100 border border-blue-300 px-2 py-0.5 text-[10px] font-bold text-blue-800">
                                                    <span>👨‍🏫</span>
                                                    <span>Guru / Pembimbing</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 text-slate-600 px-2 py-0.5 text-[10px] font-semibold">
                                                    <span>🌐</span>
                                                    <span>Pengunjung Netral</span>
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Rating & Komentar -->
                                        <td class="py-3.5 px-4 max-w-xs">
                                            <div class="space-y-1">
                                                <div class="flex items-center text-amber-400 text-xs">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <span>{{ $i <= ($c->rating ?? 5) ? '★' : '☆' }}</span>
                                                    @endfor
                                                    <span class="text-slate-400 text-[10px] font-bold ml-1">({{ $c->rating ?? 5 }}/5)</span>
                                                </div>
                                                <p class="text-xs text-[#0F291E] font-medium leading-relaxed bg-white/70 p-2 rounded-lg border border-slate-100">
                                                    "{{ $c->comment }}"
                                                </p>
                                            </div>
                                        </td>

                                        <!-- Waktu & IP Address -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <p class="font-bold text-slate-700 text-[11px]">{{ $c->created_at->format('d M Y') }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $c->created_at->format('H:i') }} WIB ({{ $c->created_at->diffForHumans() }})</p>
                                            @if($c->ip_address)
                                                <span class="font-mono text-[9px] text-slate-400 bg-slate-100 px-1.5 py-0.2 rounded mt-0.5 inline-block">
                                                    IP: {{ $c->ip_address }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Aksi Reset -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5">
                                            <!-- Hapus Komentar Ini -->
                                            <form action="{{ route('admin.interactions.destroy-comment', $c) }}" method="POST" class="inline" onsubmit="return confirm('Hapus komentar ini dari ulasan produk?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg bg-rose-50 border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-100 transition">
                                                    🗑️ Hapus
                                                </button>
                                            </form>

                                            <!-- Opsi Reset Produk -->
                                            @if($cProd)
                                                <button type="button" onclick="openProductResetModal({{ $cProd->id }}, '{{ addslashes($cProd->product_name) }}', {{ $cProd->views_count }}, {{ $cProd->likes_count }}, {{ $cProd->comments()->count() }})" class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2 py-1 text-[10px] font-bold text-slate-700 transition" title="Buka menu reset karya ini">
                                                    🔄 Reset
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-10 text-center text-slate-400">
                                            <span class="text-2xl block mb-1">💬</span>
                                            <span>Tidak ada komentar yang sesuai dengan filter pencarian ini.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($comments && $comments->hasPages())
                        <div class="p-4 border-t border-slate-100 bg-[#F8FAFC]">
                            {{ $comments->links() }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- =================================================================
                 TAB 2: AUDIT & DAFTAR SUKA (LIKES) PRODUK
                 ================================================================= --}}
            @if($tab === 'all' || $tab === 'likes' || $tab === 'connected')
                <div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-xs space-y-0">
                    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="text-base">❤️</span>
                            <div>
                                <h2 class="text-sm font-extrabold text-[#0F291E]">Daftar Suka (Likes) Produk Karya Siswa</h2>
                                <p class="text-[11px] text-slate-400">Audit siapa saja yang menyukai karya siswa beserta radar kecurangan like dari akun pemilik sendiri.</p>
                            </div>
                        </div>

                        @if($likes && $likes->total() > 0)
                            <span class="rounded-full bg-rose-50 text-rose-700 font-bold text-[11px] px-3 py-1 border border-rose-200">
                                {{ $likes->total() }} Suka Ditemukan
                            </span>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#F8FAFC] text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-200/80">
                                <tr>
                                    <th class="py-3 px-4">KARYA PRODUK & TOKO</th>
                                    <th class="py-3 px-4">AKUN PENYUKA (LIKER)</th>
                                    <th class="py-3 px-4">IDENTITAS SISWA & WILAYAH</th>
                                    <th class="py-3 px-4">DETEKSI KETERHUBUNGAN</th>
                                    <th class="py-3 px-4">ALAMAT IP & WAKTU</th>
                                    <th class="py-3 px-4 text-right">AKSI RESET</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($likes as $l)
                                    @php
                                        $lUser = $l->user;
                                        $lProd = $l->product;
                                        $lStore = $lProd?->store;
                                        $lOwner = $lStore?->owner;

                                        // Deteksi Keterhubungan
                                        $isSelf = $lUser && $lStore && ($lUser->id === $lStore->user_id);
                                        $isSameClass = $lUser && $lOwner && $lUser->id !== $lOwner->id && $lUser->class_name && ($lUser->class_name === $lOwner->class_name);
                                        $isSameMajor = $isSameClass && $lUser->major_name && ($lUser->major_name === $lOwner->major_name);
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition {{ $isSelf ? 'bg-rose-50/30' : '' }}">
                                        <!-- Produk & Toko -->
                                        <td class="py-3.5 px-4">
                                            @if($lProd)
                                                <div class="flex items-center gap-2.5">
                                                    <img src="{{ $lProd->image_url }}" alt="{{ $lProd->product_name }}" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-slate-200">
                                                    <div>
                                                        <a href="{{ route('products.show', $lProd->slug) }}" target="_blank" class="font-bold text-[#0F291E] hover:text-[#0B6E4F] line-clamp-1">
                                                            {{ $lProd->product_name }}
                                                        </a>
                                                        <p class="text-[10px] text-slate-400">
                                                            🏪 {{ $lStore->store_name ?? 'Toko' }} ({{ $lOwner->name ?? '-' }})
                                                        </p>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-slate-400 italic">Produk ID #{{ $l->product_id }}</span>
                                            @endif
                                        </td>

                                        <!-- Akun Penyuka -->
                                        <td class="py-3.5 px-4">
                                            @if($lUser)
                                                <div class="space-y-0.5">
                                                    <p class="font-bold text-[#0F291E] flex items-center gap-1.5">
                                                        <span>{{ $lUser->name }}</span>
                                                        <span class="rounded bg-slate-100 text-slate-600 text-[9px] font-bold px-1.5 py-0.2 uppercase">
                                                            {{ $lUser->user_subtype ?? $lUser->role }}
                                                        </span>
                                                    </p>
                                                    <p class="text-[10px] text-slate-400">{{ $lUser->email }}</p>
                                                </div>
                                            @else
                                                <div class="space-y-0.5">
                                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                                        👤 Pengunjung Tamu (Guest)
                                                    </span>
                                                    <p class="text-[10px] text-slate-400 font-mono">IP: {{ $l->ip_address ?? '-' }}</p>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Identitas & Wilayah -->
                                        <td class="py-3.5 px-4">
                                            @if($lUser && ($lUser->class_name || $lUser->major_name))
                                                <span class="inline-flex items-center gap-1 rounded bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                                                    <span>🎓</span>
                                                    <span>Kelas {{ $lUser->class_name }} {{ $lUser->major_name ? '• ' . $lUser->major_name : '' }}</span>
                                                </span>
                                            @elseif($lUser && $lUser->is_teacher)
                                                <span class="rounded bg-amber-50 text-amber-800 text-[10px] font-bold px-2 py-0.5">
                                                    👨‍🏫 Guru SMK
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-[11px] italic">Umum / Tamu</span>
                                            @endif

                                            @if($lUser && ($lUser->kabupaten || $lUser->kecamatan))
                                                <p class="text-[10px] text-slate-500 mt-1">
                                                    📍 {{ $lUser->kabupaten ? $lUser->kabupaten : '' }}{{ $lUser->kecamatan ? ', Kec. ' . $lUser->kecamatan : '' }}
                                                </p>
                                            @endif
                                        </td>

                                        <!-- Radar Deteksi Keterhubungan -->
                                        <td class="py-3.5 px-4">
                                            @if($isSelf)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 border border-rose-300 px-2 py-0.5 text-[10px] font-extrabold text-rose-700 animate-pulse">
                                                    <span>🚨</span>
                                                    <span>SELF-LIKE: PEMILIK TOKO</span>
                                                </span>
                                                <p class="text-[9px] text-rose-600 mt-0.5">Menyukai karyanya sendiri</p>
                                            @elseif($isSameMajor)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 border border-emerald-300 px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                                                    <span>🎓</span>
                                                    <span>Teman Sekelas & Sejurusan</span>
                                                </span>
                                            @elseif($isSameClass)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-teal-100 border border-teal-300 px-2 py-0.5 text-[10px] font-bold text-teal-800">
                                                    <span>🎓</span>
                                                    <span>Satu Tingkat (Kelas {{ $lUser->class_name }})</span>
                                                </span>
                                            @elseif($lUser)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 text-slate-600 px-2 py-0.5 text-[10px] font-semibold">
                                                    <span>❤️</span>
                                                    <span>Pengguna Terdaftar</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-50 text-slate-400 px-2 py-0.5 text-[10px]">
                                                    <span>🌐</span>
                                                    <span>Tamu Luar (IP Akses)</span>
                                                </span>
                                            @endif
                                        </td>

                                        <!-- IP Address & Waktu -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <p class="font-bold text-slate-700 text-[11px]">{{ $l->created_at->format('d M Y') }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $l->created_at->format('H:i') }} WIB</p>
                                            <span class="font-mono text-[9px] text-slate-400 bg-slate-100 px-1.5 py-0.2 rounded mt-0.5 inline-block">
                                                IP: {{ $l->ip_address ?? '-' }}
                                            </span>
                                        </td>

                                        <!-- Aksi Reset -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5">
                                            <form action="{{ route('admin.interactions.destroy-like', $l) }}" method="POST" class="inline" onsubmit="return confirm('Batalkan tanda suka ini dan kurangi counter suka produk?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg bg-rose-50 border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-100 transition">
                                                    🗑️ Batalkan Suka
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-slate-400">
                                            <span class="text-2xl block mb-1">❤️</span>
                                            <span>Tidak ada data suka yang sesuai dengan filter pencarian ini.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($likes && $likes->hasPages())
                        <div class="p-4 border-t border-slate-100 bg-[#F8FAFC]">
                            {{ $likes->links() }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- =================================================================
                 TAB 3: AUDIT & DAFTAR KUNJUNGAN (DILIHAT / VIEWS)
                 ================================================================= --}}
            @if($tab === 'all' || $tab === 'views')
                <div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-xs space-y-0">
                    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="text-base">👁️</span>
                            <div>
                                <h2 class="text-sm font-extrabold text-[#0F291E]">Log Kunjungan (Dilihat / Views)</h2>
                                <p class="text-[11px] text-slate-400">Melacak siapa saja (pengguna berakun maupun pengunjung tamu) yang melihat produk & toko siswa.</p>
                            </div>
                        </div>

                        @if($views && $views->total() > 0)
                            <span class="rounded-full bg-blue-50 text-blue-700 font-bold text-[11px] px-3 py-1 border border-blue-200">
                                {{ $views->total() }} Log Kunjungan Ditemukan
                            </span>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#F8FAFC] text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-200/80">
                                <tr>
                                    <th class="py-3 px-4">PRODUK / TOKO YANG DILIHAT</th>
                                    <th class="py-3 px-4">PENGUNJUNG (NAMA & TIPE)</th>
                                    <th class="py-3 px-4">ASAL WILAYAH</th>
                                    <th class="py-3 px-4">DETEKSI PEMILIK</th>
                                    <th class="py-3 px-4">ALAMAT IP & WAKTU</th>
                                    <th class="py-3 px-4 text-right">AKSI RESET</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($views as $v)
                                    @php
                                        $vUser = $v->user;
                                        $vProd = $v->product;
                                        $vStore = $v->store ?: $vProd?->store;
                                        $isSelfView = $vUser && $vStore && ($vUser->id === $vStore->user_id);
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition {{ $isSelfView ? 'bg-amber-50/20' : '' }}">
                                        <!-- Produk / Toko -->
                                        <td class="py-3.5 px-4">
                                            @if($vProd)
                                                <div class="flex items-center gap-2.5">
                                                    <img src="{{ $vProd->image_url }}" alt="{{ $vProd->product_name }}" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-slate-200">
                                                    <div>
                                                        <a href="{{ route('products.show', $vProd->slug) }}" target="_blank" class="font-bold text-[#0F291E] hover:text-[#0B6E4F] line-clamp-1">
                                                            {{ $vProd->product_name }}
                                                        </a>
                                                        <p class="text-[10px] text-slate-400">
                                                            🏪 {{ $vStore->store_name ?? 'Toko' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @elseif($vStore)
                                                <div>
                                                    <p class="font-bold text-[#0F291E]">🏪 Profil Toko: {{ $vStore->store_name }}</p>
                                                    <p class="text-[10px] text-slate-400">Pemilik: {{ $vStore->owner->name ?? '-' }}</p>
                                                </div>
                                            @else
                                                <span class="text-slate-400 italic">Target Kunjungan Umum</span>
                                            @endif
                                        </td>

                                        <!-- Pengunjung -->
                                        <td class="py-3.5 px-4">
                                            @if($vUser)
                                                <p class="font-bold text-[#0F291E] flex items-center gap-1.5">
                                                    <span>{{ $vUser->name }}</span>
                                                    <span class="rounded bg-emerald-100 text-[#0B6E4F] text-[9px] font-bold px-1.5 py-0.2">
                                                        Akun
                                                    </span>
                                                </p>
                                                <p class="text-[10px] text-slate-400">{{ $vUser->email }}</p>
                                            @else
                                                <p class="font-bold text-slate-700">{{ $v->visitor_name ?? 'Pengunjung Tamu' }}</p>
                                                <span class="rounded bg-slate-100 text-slate-500 text-[9px] font-bold px-1.5 py-0.2">
                                                    {{ $v->visitor_type ?? 'Guest' }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Asal Wilayah -->
                                        <td class="py-3.5 px-4">
                                            <p class="text-[11px] font-semibold text-slate-700">
                                                📍 {{ $v->origin_region ?? ($vUser ? $vUser->full_address : 'Akses Publik') }}
                                            </p>
                                        </td>

                                        <!-- Deteksi Pemilik -->
                                        <td class="py-3.5 px-4">
                                            @if($isSelfView)
                                                <span class="inline-flex items-center gap-1 rounded bg-amber-100 text-amber-800 border border-amber-300 px-2 py-0.5 text-[10px] font-extrabold">
                                                    <span>⚠️</span>
                                                    <span>Dilihat Pemilik Toko Sendiri</span>
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-[11px]">Pengunjung Luar</span>
                                            @endif
                                        </td>

                                        <!-- IP & Waktu -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <p class="font-bold text-slate-700 text-[11px]">{{ $v->created_at->format('d M Y') }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $v->created_at->format('H:i:s') }} WIB</p>
                                            <span class="font-mono text-[9px] text-slate-400 bg-slate-100 px-1.5 py-0.2 rounded mt-0.5 inline-block">
                                                IP: {{ $v->ip_address ?? '-' }}
                                            </span>
                                        </td>

                                        <!-- Aksi Hapus -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <form action="{{ route('admin.interactions.destroy-visitor-log', $v) }}" method="POST" class="inline" onsubmit="return confirm('Hapus log kunjungan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg bg-rose-50 border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-100 transition">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-slate-400">
                                            <span class="text-2xl block mb-1">👁️</span>
                                            <span>Tidak ada log kunjungan yang sesuai dengan filter pencarian ini.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($views && $views->hasPages())
                        <div class="p-4 border-t border-slate-100 bg-[#F8FAFC]">
                            {{ $views->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </main>
    </div>
</div>

{{-- =========================================================================
     MODAL 1: RESET INTERAKSI PRODUK SPESIFIK
     ========================================================================= --}}
<div id="product-reset-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <span class="rounded-full bg-rose-100 text-rose-700 px-2.5 py-0.5 text-[10px] font-extrabold uppercase">Menu Kontrol Admin</span>
                <h3 class="text-base font-extrabold text-[#0F291E] mt-1">Reset Interaksi Karya Produk</h3>
            </div>
            <button type="button" onclick="closeProductResetModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form id="product-reset-form" method="POST" action="" class="space-y-4">
            @csrf

            <div>
                <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Nama Produk yang Direset:</label>
                <p id="modal-product-name" class="text-sm font-extrabold text-[#0B6E4F] bg-[#EBF9F2] p-2.5 rounded-xl border border-emerald-200">
                    -
                </p>
            </div>

            <!-- Statistik Saat Ini -->
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3.5 space-y-1.5 text-xs">
                <span class="text-[10px] font-extrabold uppercase text-slate-400 block mb-1">Statistik Saat Ini:</span>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Total Dilihat (Views):</span>
                    <span id="modal-product-views" class="font-bold text-[#0F291E]">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Total Suka (Likes):</span>
                    <span id="modal-product-likes" class="font-bold text-rose-600">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Total Komentar:</span>
                    <span id="modal-product-comments" class="font-bold text-blue-600">0</span>
                </div>
            </div>

            <!-- Opsi Pilihan Reset -->
            <div class="space-y-2">
                <span class="block text-[11px] font-extrabold uppercase text-slate-400">Pilih Interaksi yang Ingin Direset:</span>

                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="reset_views" value="1" class="mt-0.5 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                    <div>
                        <p class="font-bold text-xs text-[#0F291E]">👁️ Reset Jumlah Dilihat (Views)</p>
                        <p class="text-[10px] text-slate-400">Mengembalikan counter dilihat produk ke angka 0 & membersihkan log kunjungan terkait.</p>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="reset_likes" value="1" class="mt-0.5 rounded text-rose-600 focus:ring-rose-500">
                    <div>
                        <p class="font-bold text-xs text-rose-700">❤️ Reset Jumlah Suka (Likes)</p>
                        <p class="text-[10px] text-slate-400">Mengembalikan counter suka ke angka 0 & menghapus data riwayat likes produk ini.</p>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="reset_comments" value="1" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                    <div>
                        <p class="font-bold text-xs text-blue-800">💬 Bersihkan Seluruh Komentar</p>
                        <p class="text-[10px] text-slate-400">Menghapus seluruh ulasan dan komentar pengunjung pada karya ini.</p>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-50 cursor-pointer">
                    <input type="checkbox" name="reset_all" value="1" id="chk-reset-all" onchange="toggleSelectAllProductReset(this)" class="mt-0.5 rounded text-rose-700 focus:ring-rose-500">
                    <div>
                        <p class="font-bold text-xs text-rose-800">⚠️ Reset Total Semuanya Sekaligus</p>
                        <p class="text-[10px] text-rose-600">Reset Dilihat, Suka, dan Komentar kembali bersih seperti baru di-upload.</p>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeProductResetModal()" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mengeksekusi reset interaksi pada karya ini? Tindakan ini akan dicatat di log audit sistem.')" class="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white hover:bg-rose-700 shadow-xs transition">
                    Eksekusi Reset Produk
                </button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================================
     MODAL 2: RESET MASSAL SISTEM (GLOBAL BULK RESET)
     ========================================================================= --}}
<div id="bulk-reset-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <span class="rounded-full bg-rose-100 text-rose-700 px-2.5 py-0.5 text-[10px] font-extrabold uppercase">Super Admin & Admin</span>
                <h3 class="text-base font-extrabold text-[#0F291E] mt-1">Reset Massal Interaksi Sistem</h3>
            </div>
            <button type="button" onclick="closeBulkResetModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form action="{{ route('admin.interactions.reset-bulk') }}" method="POST" class="space-y-4">
            @csrf

            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-900 space-y-1">
                <p class="font-extrabold flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>Perhatian Penting</span>
                </p>
                <p class="text-[11px] text-amber-800 leading-relaxed">
                    Fitur ini digunakan saat pembersihan data uji coba, evaluasi akhir semester, atau saat memulai tahun ajaran baru di SMK Plus Al Hilal. Seluruh tindakan reset akan terekam di Log Audit Sistem beserta identitas admin pelaksana.
                </p>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-2">Pilih Tipe Reset Massal:</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="reset_type" value="comments" class="text-[#0B6E4F] focus:ring-[#0B6E4F]">
                        <span class="font-bold text-xs text-[#0F291E]">💬 Reset Seluruh Komentar Saja (Bersihkan Semua Ulasan)</span>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="reset_type" value="likes" class="text-rose-600 focus:ring-rose-500">
                        <span class="font-bold text-xs text-rose-700">❤️ Reset Seluruh Suka (Likes) Saja (Set ke 0)</span>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="reset_type" value="views" class="text-[#0B6E4F] focus:ring-[#0B6E4F]">
                        <span class="font-bold text-xs text-[#0B6E4F]">👁️ Reset Seluruh Counter & Log Dilihat (Views) Saja</span>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 cursor-pointer">
                        <input type="radio" name="reset_type" value="all" checked class="text-rose-700 focus:ring-rose-600">
                        <span class="font-bold text-xs text-rose-800">🚨 Reset TOTAL Semua Interaksi (Komentar + Suka + Views)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeBulkResetModal()" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit" onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin melakukan RESET MASSAL pada seluruh data interaksi sistem? Tindakan ini tidak dapat dibatalkan.')" class="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white hover:bg-rose-700 shadow-xs transition">
                    Konfirmasi Reset Massal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openProductResetModal(productId, productName, views, likes, comments) {
        const modal = document.getElementById('product-reset-modal');
        const form = document.getElementById('product-reset-form');
        form.action = `/panel-admin/interaksi/produk/${productId}/reset`;

        document.getElementById('modal-product-name').textContent = productName;
        document.getElementById('modal-product-views').textContent = views + ' views';
        document.getElementById('modal-product-likes').textContent = likes + ' suka';
        document.getElementById('modal-product-comments').textContent = comments + ' komentar';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeProductResetModal() {
        const modal = document.getElementById('product-reset-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function toggleSelectAllProductReset(masterChk) {
        const form = document.getElementById('product-reset-form');
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(chk => {
            if (chk !== masterChk) {
                chk.checked = masterChk.checked;
            }
        });
    }

    function openBulkResetModal() {
        const modal = document.getElementById('bulk-reset-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeBulkResetModal() {
        const modal = document.getElementById('bulk-reset-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

